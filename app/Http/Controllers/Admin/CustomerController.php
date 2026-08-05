<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CustomerImportTemplateExport;
use App\Exports\CustomersExport;
use App\Http\Controllers\Controller;
use App\Imports\CustomersImport;
use App\Models\Customer;
use App\Models\MikrotikRouter;
use App\Models\Package;
use App\Services\Mikrotik\MikrotikService;
use Illuminate\Http\Request;
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

    public function importTemplate()
    {
        return Excel::download(new CustomerImportTemplateExport, 'contoh-format-import-pelanggan.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        $import = new CustomersImport;
        Excel::import($import, $request->file('file'));

        $failures = $import->failures();

        if ($failures->isEmpty()) {
            return back()->with('status', "Berhasil mengimpor {$import->imported} pelanggan.");
        }

        $errors = $failures->map(function ($failure) {
            $row = $failure->row();
            $messages = implode(', ', $failure->errors());

            return "Baris {$row}: {$messages}";
        })->implode(' | ');

        return back()->with(
            'status',
            "Berhasil mengimpor {$import->imported} pelanggan. ".$failures->count().' baris dilewati karena tidak valid.'
        )->with('import_errors', $errors);
    }

    public function create()
    {
        $packages = Package::where('is_active', true)->orderBy('name')->get();
        $routers = MikrotikRouter::where('is_active', true)->orderBy('name')->get();

        return view('admin.customers.create', compact('packages', 'routers'));
    }

    /**
     * "Diskon Pelanggan" screen: set each customer's recurring discount percentage + note in
     * one grid. Applied automatically every time that customer's next invoice is generated
     * (see InvoiceService::generateForCustomer()) — not a one-off, per-invoice adjustment.
     */
    public function discounts()
    {
        $customers = Customer::where('status', '!=', 'inactive')
            ->whereNotNull('package_id')
            ->with('package')
            ->orderBy('name')
            ->get();

        return view('admin.customers.discounts', compact('customers'));
    }

    public function discountsStore(Request $request)
    {
        $data = $request->validate([
            'discount_type' => ['nullable', 'array'],
            'discount_type.*' => ['nullable', 'in:percent,nominal'],
            'discount_percent' => ['nullable', 'array'],
            'discount_percent.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_nominal' => ['nullable', 'array'],
            'discount_nominal.*' => ['nullable', 'numeric', 'min:0'],
            'discount_note' => ['nullable', 'array'],
            'discount_note.*' => ['nullable', 'string', 'max:255'],
        ]);

        $updated = 0;

        foreach ($data['discount_type'] ?? [] as $customerId => $type) {
            $customer = Customer::find($customerId);

            if (! $customer) {
                continue;
            }

            $percent = $data['discount_percent'][$customerId] ?? null;
            $nominal = $data['discount_nominal'][$customerId] ?? null;

            $customer->update([
                'discount_type' => $type ?: 'percent',
                'discount_percent' => $percent !== null && $percent !== '' ? (float) $percent : 0,
                'discount_nominal' => $nominal !== null && $nominal !== '' ? (float) $nominal : 0,
                'discount_note' => $data['discount_note'][$customerId] ?? null,
            ]);

            $updated++;
        }

        return redirect()->route('customers.discounts')->with('status', "Diskon {$updated} pelanggan berhasil disimpan.");
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['customer_code'] = Customer::generateCode();

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
            'phone' => ['required', 'string', 'max:30'],
            'nik' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'package_id' => ['required', 'exists:packages,id'],
            'pppoe_username' => ['required', 'string', 'max:255'],
            'pppoe_password' => ['required', 'string', 'max:255'],
            'router_id' => ['nullable', 'exists:mikrotik_routers,id'],
            'ip_address' => ['nullable', 'ip'],
            'installation_date' => ['nullable', 'date'],
            'billing_due_day' => ['required', 'integer', 'min:1', 'max:28'],
            'status' => ['required', 'in:active,isolated,inactive'],
            'notes' => ['nullable', 'string'],
        ]);
    }

}
