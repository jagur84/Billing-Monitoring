<?php

namespace App\Services\GenieAcs;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenieAcsService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
    ) {
    }

    private function client()
    {
        $client = Http::baseUrl($this->baseUrl)->timeout(5);

        if ($this->username) {
            $client = $client->withBasicAuth($this->username, $this->password ?? '');
        }

        return $client;
    }

    /**
     * List devices from the GenieACS NBI, optionally filtered by a Mongo-style query.
     */
    public function listDevices(?array $query = null): array
    {
        try {
            $response = $this->client()->get('/devices', $query ? ['query' => json_encode($query)] : []);

            return $response->successful() ? $response->json() : [];
        } catch (Throwable $e) {
            Log::warning('GenieACS listDevices failed', ['error' => $e->getMessage()]);

            return [];
        }
    }

    public function getDevice(string $deviceId): ?array
    {
        try {
            $response = $this->client()->get('/devices', [
                'query' => json_encode(['_id' => $deviceId]),
            ]);

            if (! $response->successful()) {
                return null;
            }

            return $response->json()[0] ?? null;
        } catch (Throwable $e) {
            Log::warning('GenieACS getDevice failed', ['device_id' => $deviceId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    public function reboot(string $deviceId): bool
    {
        try {
            $response = $this->client()->post("/devices/{$deviceId}/tasks?connection_request", [
                'name' => 'reboot',
            ]);

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('GenieACS reboot failed', ['device_id' => $deviceId, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
