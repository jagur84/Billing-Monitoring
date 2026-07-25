<?php

namespace App\Services\Mikrotik;

use App\Models\Customer;
use App\Models\MikrotikRouter;
use Illuminate\Support\Facades\Log;
use RouterOS\Client;
use RouterOS\Config;
use RouterOS\Query;
use Throwable;

class MikrotikService
{
    private const TRAFFIC_BATCH_SIZE = 80;

    public function connect(MikrotikRouter $router): Client
    {
        $config = (new Config())
            ->set('host', $router->host)
            ->set('user', $router->username)
            ->set('pass', $router->password)
            ->set('port', $router->port)
            ->set('ssl', $router->use_ssl)
            ->set('timeout', 5);

        return new Client($config);
    }

    /**
     * Disable the customer's PPPoE secret and drop any active session, marking them isolated.
     */
    public function isolateCustomer(Customer $customer): bool
    {
        if (! $customer->router_id || ! $customer->pppoe_username) {
            return false;
        }

        try {
            $client = $this->connect($customer->router);

            $this->setSecretDisabled($client, $customer->pppoe_username, true);
            $this->dropActiveSession($client, $customer->pppoe_username);

            $customer->update(['status' => 'isolated']);

            return true;
        } catch (Throwable $e) {
            Log::error('Mikrotik isolate failed', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Re-enable the customer's PPPoE secret, marking them active again.
     */
    public function restoreCustomer(Customer $customer): bool
    {
        if (! $customer->router_id || ! $customer->pppoe_username) {
            return false;
        }

        try {
            $client = $this->connect($customer->router);

            $this->setSecretDisabled($client, $customer->pppoe_username, false);

            $customer->update(['status' => 'active']);

            return true;
        } catch (Throwable $e) {
            Log::error('Mikrotik restore failed', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function testConnection(MikrotikRouter $router): bool
    {
        try {
            $client = $this->connect($router);
            $client->query(new Query('/system/identity/print'))->read();

            return true;
        } catch (Throwable $e) {
            Log::warning('Mikrotik test connection failed', ['router_id' => $router->id, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * Live active PPPoE sessions straight from the router (/ppp/active/print), enriched with
     * live rx/tx throughput per session (/interface/monitor-traffic). Throws on connection
     * failure — callers should catch and surface an "offline" state rather than a hard error,
     * since the router being unreachable is an expected/frequent condition, not a bug.
     *
     * @return array<int, array{username: string, address: string, uptime: string, interface: string, caller_id: string, rx_bps: int, tx_bps: int}>
     */
    public function getActivePppoeSessions(MikrotikRouter $router): array
    {
        $client = $this->connect($router);

        $sessions = $client->query(new Query('/ppp/active/print'))->read();

        $interfaceNames = array_map(fn ($session) => '<pppoe-'.($session['name'] ?? '').'>', $sessions);
        $traffic = $this->monitorTraffic($client, $interfaceNames);

        return array_map(function ($session) use ($traffic) {
            $username = $session['name'] ?? '-';
            $interface = '<pppoe-'.$username.'>';
            $stats = $traffic[$interface] ?? null;

            return [
                'username' => $username,
                'address' => $session['address'] ?? '-',
                'uptime' => $session['uptime'] ?? '-',
                'interface' => $interface,
                'caller_id' => $session['caller-id'] ?? '-',
                'rx_bps' => (int) ($stats['rx-bits-per-second'] ?? 0),
                'tx_bps' => (int) ($stats['tx-bits-per-second'] ?? 0),
            ];
        }, $sessions);
    }

    /**
     * Live IP routing table straight from the router (/ip/route/print).
     *
     * @return array<int, array{destination: string, gateway: string, distance: string, routing_table: string, status: string, type: string, comment: string}>
     */
    public function getRoutes(MikrotikRouter $router): array
    {
        $client = $this->connect($router);

        $routes = $client->query(new Query('/ip/route/print'))->read();

        return array_map(function ($route) {
            $disabled = ($route['disabled'] ?? 'false') === 'true';
            $active = ($route['active'] ?? 'false') === 'true';
            $dynamic = ($route['dynamic'] ?? 'false') === 'true';

            return [
                'destination' => $route['dst-address'] ?? '-',
                'gateway' => $route['gateway'] ?? '-',
                'distance' => $route['distance'] ?? '-',
                'routing_table' => $route['routing-table'] ?? 'main',
                'status' => $disabled ? 'disabled' : ($active ? 'active' : 'inactive'),
                'type' => $dynamic ? 'dynamic' : 'static',
                'comment' => $route['comment'] ?? '-',
            ];
        }, $routes);
    }

    /**
     * All router interfaces with their total counters (/interface/print) — physical ports,
     * VLANs, and dynamic PPPoE interfaces alike.
     *
     * @return array<int, array{name: string, type: string, mac_address: string, mtu: string, rx_bytes: int, tx_bytes: int, running: bool, disabled: bool, comment: string}>
     */
    public function getInterfaces(MikrotikRouter $router): array
    {
        $client = $this->connect($router);

        $interfaces = $client->query(new Query('/interface/print'))->read();

        return array_map(function ($iface) {
            return [
                'name' => $iface['name'] ?? '-',
                'type' => $iface['type'] ?? '-',
                'mac_address' => $iface['mac-address'] ?? '-',
                'mtu' => $iface['mtu'] ?? '-',
                'rx_bytes' => (int) ($iface['rx-byte'] ?? 0),
                'tx_bytes' => (int) ($iface['tx-byte'] ?? 0),
                'running' => ($iface['running'] ?? 'false') === 'true',
                'disabled' => ($iface['disabled'] ?? 'false') === 'true',
                'comment' => $iface['comment'] ?? '-',
            ];
        }, $interfaces);
    }

    /**
     * One batched /interface/monitor-traffic call for all given interfaces (comma-separated),
     * rather than one round-trip per session — important once dozens/hundreds of PPPoE users
     * are online. Traffic stats are best-effort: if the call fails, sessions still render with
     * 0bps rather than the whole page failing.
     *
     * @return array<string, array<string, mixed>> keyed by interface name
     */
    private function monitorTraffic(Client $client, array $interfaceNames): array
    {
        $interfaceNames = array_values(array_filter($interfaceNames));

        if (empty($interfaceNames)) {
            return [];
        }

        // Batching keeps this to a handful of round-trips instead of one per session, but
        // batches larger than ~100 interfaces silently collapse to a single merged reply row
        // (observed against a real router with 164 active sessions) — chunk well under that.
        $byInterface = [];

        foreach (array_chunk($interfaceNames, self::TRAFFIC_BATCH_SIZE) as $chunk) {
            try {
                $result = $client->query(
                    (new Query('/interface/monitor-traffic'))
                        ->equal('interface', implode(',', $chunk))
                        ->equal('once')
                )->read();
            } catch (Throwable $e) {
                Log::warning('Mikrotik monitor-traffic failed', ['error' => $e->getMessage()]);

                continue;
            }

            foreach ($result as $row) {
                if (isset($row['name'])) {
                    $byInterface[$row['name']] = $row;
                }
            }
        }

        return $byInterface;
    }

    /**
     * All PPP secrets configured on the router (/ppp/secret/print), cross-referenced against
     * currently active sessions (/ppp/active/print) so each row can show a live online/offline
     * status alongside its static configuration.
     *
     * @return array<int, array{id: string, username: string, password: string, profile: string, service: string, comment: string, disabled: bool, online: bool, last_logged_out: string}>
     */
    public function getSecrets(MikrotikRouter $router): array
    {
        $client = $this->connect($router);

        $secrets = $client->query(new Query('/ppp/secret/print'))->read();
        $active = $client->query(new Query('/ppp/active/print'))->read();
        $onlineNames = array_column($active, 'name');

        return array_map(function ($secret) use ($onlineNames) {
            return [
                'id' => $secret['.id'] ?? '',
                'username' => $secret['name'] ?? '-',
                'password' => $secret['password'] ?? '-',
                'profile' => $secret['profile'] ?? '-',
                'service' => $secret['service'] ?? 'any',
                'comment' => $secret['comment'] ?? '',
                'disabled' => ($secret['disabled'] ?? 'false') === 'true',
                'online' => in_array($secret['name'] ?? null, $onlineNames, true),
                'last_logged_out' => $secret['last-logged-out'] ?? '-',
            ];
        }, $secrets);
    }

    /**
     * PPP profile names configured on the router (/ppp/profile/print), for populating the
     * profile dropdown when adding/editing a secret.
     *
     * @return array<int, string>
     */
    public function getProfiles(MikrotikRouter $router): array
    {
        $client = $this->connect($router);

        $profiles = $client->query(new Query('/ppp/profile/print'))->read();

        return array_values(array_unique(array_filter(array_map(fn ($p) => $p['name'] ?? null, $profiles))));
    }

    public function createSecret(MikrotikRouter $router, array $data): void
    {
        $client = $this->connect($router);

        $query = (new Query('/ppp/secret/add'))
            ->equal('name', $data['username'])
            ->equal('password', $data['password'])
            ->equal('profile', $data['profile'] ?: 'default')
            ->equal('service', $data['service'] ?: 'any');

        if (! empty($data['comment'])) {
            $query->equal('comment', $data['comment']);
        }

        $client->query($query)->read();
    }

    public function updateSecret(MikrotikRouter $router, string $secretId, array $data): void
    {
        $client = $this->connect($router);

        $query = (new Query('/ppp/secret/set'))->equal('.id', $secretId);

        $map = [
            'username' => 'name',
            'password' => 'password',
            'profile' => 'profile',
            'service' => 'service',
            'comment' => 'comment',
        ];

        foreach ($map as $key => $field) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                $query->equal($field, (string) $data[$key]);
            }
        }

        if (array_key_exists('disabled', $data)) {
            $query->equal('disabled', $data['disabled'] ? 'yes' : 'no');
        }

        $client->query($query)->read();
    }

    public function deleteSecret(MikrotikRouter $router, string $secretId): void
    {
        $client = $this->connect($router);

        $client->query((new Query('/ppp/secret/remove'))->equal('.id', $secretId))->read();
    }

    public function toggleSecretDisabled(MikrotikRouter $router, string $secretId, bool $disabled): void
    {
        $client = $this->connect($router);

        $client->query(
            (new Query('/ppp/secret/set'))->equal('.id', $secretId)->equal('disabled', $disabled ? 'yes' : 'no')
        )->read();
    }

    private function setSecretDisabled(Client $client, string $username, bool $disabled): void
    {
        $query = (new Query('/ppp/secret/print'))->where('name', $username);
        $secrets = $client->query($query)->read();

        foreach ($secrets as $secret) {
            $client->query(
                (new Query('/ppp/secret/set'))
                    ->equal('.id', $secret['.id'])
                    ->equal('disabled', $disabled ? 'yes' : 'no')
            )->read();
        }
    }

    private function dropActiveSession(Client $client, string $username): void
    {
        $query = (new Query('/ppp/active/print'))->where('name', $username);
        $sessions = $client->query($query)->read();

        foreach ($sessions as $session) {
            $client->query(
                (new Query('/ppp/active/remove'))->equal('.id', $session['.id'])
            )->read();
        }
    }
}
