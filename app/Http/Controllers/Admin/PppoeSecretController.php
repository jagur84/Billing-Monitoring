<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Services\Mikrotik\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class PppoeSecretController extends Controller
{
    public function __construct(private MikrotikService $mikrotikService)
    {
    }

    public function index(MikrotikRouter $mikrotikRouter)
    {
        try {
            $profiles = $this->mikrotikService->getProfiles($mikrotikRouter);
        } catch (Throwable $e) {
            $profiles = [];
        }

        $customers = Customer::orderBy('name')->get(['id', 'name', 'customer_code']);

        return view('admin.mikrotik-routers.secrets', [
            'router' => $mikrotikRouter,
            'profiles' => $profiles,
            'customers' => $customers,
        ]);
    }

    public function data(MikrotikRouter $mikrotikRouter)
    {
        try {
            $secrets = $this->mikrotikService->getSecrets($mikrotikRouter);

            $customerIdsByUsername = Customer::where('router_id', $mikrotikRouter->id)
                ->whereNotNull('pppoe_username')
                ->pluck('id', 'pppoe_username');

            foreach ($secrets as &$secret) {
                $secret['customer_id'] = $customerIdsByUsername[$secret['username']] ?? null;
            }
            unset($secret);

            return response()->json([
                'online' => true,
                'total' => count($secrets),
                'onlineCount' => count(array_filter($secrets, fn ($s) => $s['online'])),
                'secrets' => $secrets,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'online' => false,
                'total' => 0,
                'onlineCount' => 0,
                'secrets' => [],
                'message' => 'Gagal terhubung ke router. Periksa koneksi dan kredensial API.',
            ]);
        }
    }

    public function store(Request $request, MikrotikRouter $mikrotikRouter)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'profile' => ['nullable', 'string', 'max:255'],
            'service' => ['nullable', 'string', 'max:50'],
            'comment' => ['nullable', 'string', 'max:255'],
            'customer_choice' => ['nullable', 'string'],
            'new_customer_name' => ['nullable', 'string', 'max:255', 'required_if:customer_choice,__new__'],
        ]);

        try {
            $this->mikrotikService->createSecret($mikrotikRouter, $data);
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal membuat secret di router: '.$e->getMessage())->withInput();
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'subject_type' => MikrotikRouter::class,
            'subject_id' => $mikrotikRouter->id,
            'description' => "membuat PPPoE Secret {$data['username']} di router {$mikrotikRouter->name}",
            'ip_address' => $request->ip(),
        ]);

        $customerNote = $this->linkOrCreateCustomer($mikrotikRouter, $data);

        return redirect()->route('mikrotik-routers.secrets.index', $mikrotikRouter)
            ->with('status', "Secret PPPoE berhasil ditambahkan.{$customerNote}");
    }

    public function update(Request $request, MikrotikRouter $mikrotikRouter, string $secretId)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'profile' => ['nullable', 'string', 'max:255'],
            'service' => ['nullable', 'string', 'max:50'],
            'comment' => ['nullable', 'string', 'max:255'],
            'customer_choice' => ['nullable', 'string'],
            'new_customer_name' => ['nullable', 'string', 'max:255', 'required_if:customer_choice,__new__'],
        ]);

        $data['disabled'] = $request->boolean('disabled');

        try {
            $this->mikrotikService->updateSecret($mikrotikRouter, $secretId, $data);
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal memperbarui secret: '.$e->getMessage());
        }

        // Keep the linked customer's pppoe_username in sync if it was renamed from this form.
        $originalUsername = $request->input('original_username');
        if ($originalUsername && $originalUsername !== $data['username']) {
            Customer::where('router_id', $mikrotikRouter->id)
                ->where('pppoe_username', $originalUsername)
                ->update(['pppoe_username' => $data['username']]);
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'subject_type' => MikrotikRouter::class,
            'subject_id' => $mikrotikRouter->id,
            'description' => "memperbarui PPPoE Secret {$data['username']} di router {$mikrotikRouter->name}",
            'ip_address' => $request->ip(),
        ]);

        $customerNote = $this->linkOrCreateCustomer($mikrotikRouter, $data);

        return redirect()->route('mikrotik-routers.secrets.index', $mikrotikRouter)
            ->with('status', "Secret PPPoE berhasil diperbarui.{$customerNote}");
    }

    public function destroy(Request $request, MikrotikRouter $mikrotikRouter, string $secretId)
    {
        try {
            $this->mikrotikService->deleteSecret($mikrotikRouter, $secretId);
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal menghapus secret: '.$e->getMessage());
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted',
            'subject_type' => MikrotikRouter::class,
            'subject_id' => $mikrotikRouter->id,
            'description' => "menghapus PPPoE Secret (id {$secretId}) di router {$mikrotikRouter->name}",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', 'Secret PPPoE berhasil dihapus.');
    }

    public function toggle(Request $request, MikrotikRouter $mikrotikRouter, string $secretId)
    {
        $disabled = $request->boolean('disabled');

        try {
            $this->mikrotikService->toggleSecretDisabled($mikrotikRouter, $secretId, $disabled);
        } catch (Throwable $e) {
            return back()->with('error', 'Gagal mengubah status secret: '.$e->getMessage());
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'subject_type' => MikrotikRouter::class,
            'subject_id' => $mikrotikRouter->id,
            'description' => ($disabled ? 'menonaktifkan' : 'mengaktifkan')." PPPoE Secret (id {$secretId}) di router {$mikrotikRouter->name}",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('status', $disabled ? 'Secret berhasil dinonaktifkan.' : 'Secret berhasil diaktifkan.');
    }

    /**
     * Optionally link the new secret to an existing customer, or auto-create a new customer
     * for it — both are opt-in via the "customer_choice" field, not required.
     */
    private function linkOrCreateCustomer(MikrotikRouter $mikrotikRouter, array $data): string
    {
        $choice = $data['customer_choice'] ?? '';

        if ($choice === '') {
            return '';
        }

        if ($choice === '__new__') {
            Customer::create([
                'customer_code' => $this->generateCustomerCode(),
                'name' => $data['new_customer_name'],
                'pppoe_username' => $data['username'],
                'router_id' => $mikrotikRouter->id,
                'billing_due_day' => 10,
                'status' => 'active',
            ]);

            return ' Data pelanggan baru juga berhasil dibuat.';
        }

        Customer::where('id', $choice)->update([
            'pppoe_username' => $data['username'],
            'router_id' => $mikrotikRouter->id,
        ]);

        return ' Berhasil dikaitkan dengan pelanggan.';
    }

    private function generateCustomerCode(): string
    {
        do {
            $code = 'CUST-'.strtoupper(Str::random(6));
        } while (Customer::where('customer_code', $code)->exists());

        return $code;
    }
}
