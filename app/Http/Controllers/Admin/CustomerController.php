<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CustomersExport;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\Package;
use App\Models\Setting;
use App\Services\Mikrotik\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function __construct(private MikrotikService $mikrotikService)
    {
    }

    public function index(Request $request)
    {
        $customers = Customer::with('package')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function export(Request $request)
    {
        $customers = Customer::with('package')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('customer_code', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->get();

        return Excel::download(new CustomersExport($customers), 'pelanggan-'.now()->format('Y-m-d').'.xlsx');
    }

    public function create()
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $routers = MikrotikRouter::where('is_active', true)->orderBy('name')->get();

        return view('admin.customers.create', compact('packages', 'routers'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['customer_code'] = $this->generateCustomerCode();

        Customer::create($data);

        return redirect()->route('customers.index')->with('status', 'Pelanggan berhasil ditambahkan.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['package', 'router', 'invoices' => fn ($q) => $q->orderByDesc('id')->limit(12), 'tickets' => fn ($q) => $q->orderByDesc('id')->limit(5)]);

        return view('admin.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $routers = MikrotikRouter::where('is_active', true)->orderBy('name')->get();

        return view('admin.customers.edit', compact('customer', 'packages', 'routers'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validated($request, $customer->id);

        $customer->update($data);

        return redirect()->route('customers.index')->with('status', 'Data pelanggan berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Pelanggan berhasil dihapus.');
    }

    public function isolate(Customer $customer)
    {
        if ($this->mikrotikService->isolateCustomer($customer)) {
            return back()->with('status', 'Pelanggan berhasil diisolir.');
        }

        return back()->with('error', 'Gagal mengisolir pelanggan. Pastikan router dan username PPPoE sudah diatur.');
    }

    public function restore(Customer $customer)
    {
        if ($this->mikrotikService->restoreCustomer($customer)) {
            return back()->with('status', 'Akses pelanggan berhasil dipulihkan.');
        }

        return back()->with('error', 'Gagal memulihkan akses pelanggan. Pastikan router dan username PPPoE sudah diatur.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'nik' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'package_id' => ['nullable', 'exists:packages,id'],
            'pppoe_username' => ['nullable', 'string', 'max:255'],
            'router_id' => ['nullable', 'exists:mikrotik_routers,id'],
            'ip_address' => ['nullable', 'ip'],
            'installation_date' => ['nullable', 'date'],
            'billing_due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'status' => ['required', 'in:active,isolated,inactive'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function generateCustomerCode(): string
    {
        $prefix = Setting::get('customer_code_prefix', 'CUST');

        do {
            $code = "{$prefix}-".strtoupper(Str::random(6));
        } while (Customer::where('customer_code', $code)->exists());

        return $code;
    }
}
