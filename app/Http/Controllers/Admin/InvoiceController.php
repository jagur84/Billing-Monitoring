<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendWhatsAppMessage;
use App\Mail\InvoiceReminderMail;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Services\Billing\InvoiceService;
use App\Services\Notifications\ReminderStageMessages;
use App\Services\Notifications\TemplateRenderer;
use App\Services\Payment\TripayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private TripayService $tripayService,
    ) {
    }

    public function index(Request $request)
    {
        $paidSum = fn ($q) => $q->where('status', 'paid');

        $invoices = Invoice::with('customer')
            ->withSum(['payments as paid_amount' => $paidSum], 'amount')
            ->withCount('payments')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Other outstanding invoices per customer on this page, to show "tagihan sebelumnya"
        // (arrears carried over) alongside each row without an N+1 query per invoice.
        $customerIds = $invoices->pluck('customer_id')->unique();

        $outstandingByCustomer = Invoice::whereIn('customer_id', $customerIds)
            ->whereIn('status', ['unpaid', 'overdue', 'partial'])
            ->withSum(['payments as paid_amount' => $paidSum], 'amount')
            ->get(['id', 'customer_id', 'total_amount'])
            ->groupBy('customer_id');

        foreach ($invoices as $invoice) {
            $paid = (float) ($invoice->paid_amount ?? 0);
            $invoice->total_paid = $paid;
            $invoice->remaining_amount = max((float) $invoice->total_amount - $paid, 0);

            $invoice->previous_arrears = $outstandingByCustomer->get($invoice->customer_id, collect())
                ->where('id', '!=', $invoice->id)
                ->sum(fn ($other) => max((float) $other->total_amount - (float) ($other->paid_amount ?? 0), 0));
        }

        return view('admin.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $customers = Customer::where('status', '!=', 'inactive')->whereNotNull('package_id')->orderBy('name')->get();

        return view('admin.invoices.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $customer = Customer::findOrFail($data['customer_id']);
        $invoice = $this->invoiceService->generateForCustomer($customer, (int) $data['period_month'], (int) $data['period_year']);

        if (! $invoice) {
            return back()->with('error', 'Tagihan untuk periode ini sudah ada atau pelanggan belum memiliki paket.')->withInput();
        }

        return redirect()->route('invoices.show', $invoice)->with('status', 'Tagihan berhasil dibuat.');
    }

    public function bulkCreate()
    {
        $month = now()->month;
        $year = now()->year;

        $customers = Customer::where('status', '!=', 'inactive')
            ->whereNotNull('package_id')
            ->orderBy('name')
            ->get();

        $alreadyInvoiced = Invoice::where('period_month', $month)
            ->where('period_year', $year)
            ->whereIn('customer_id', $customers->pluck('id'))
            ->pluck('customer_id')
            ->all();

        return view('admin.invoices.bulk-create', compact('customers', 'alreadyInvoiced', 'month', 'year'));
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'select_all' => ['nullable', 'boolean'],
            'customer_ids' => ['required_without:select_all', 'array'],
            'customer_ids.*' => ['integer', 'exists:customers,id'],
        ]);

        $month = now()->month;
        $year = now()->year;

        $query = Customer::where('status', '!=', 'inactive')->whereNotNull('package_id');

        if (! $request->boolean('select_all')) {
            $query->whereIn('id', $data['customer_ids'] ?? []);
        }

        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($query->get() as $customer) {
            try {
                if ($this->invoiceService->generateAndNotify($customer, $month, $year)) {
                    $created++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                // One customer's generation failing (e.g. a race with the daily scheduler,
                // or an unexpected data issue) shouldn't abort the rest of the batch.
                $failed++;
                Log::error('Bulk invoice generation failed', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);
            }
        }

        $message = "{$created} tagihan berhasil dibuat. {$skipped} pelanggan dilewati (sudah ada tagihan bulan ini atau belum punya paket).";

        if ($failed > 0) {
            $message .= " {$failed} gagal karena kesalahan sistem — lihat log.";
        }

        return redirect()->route('invoices.index')->with('status', $message);
    }

    /**
     * "Sisa Tagihan" screen: pick a past period, then enter each customer's remaining balance
     * from the old system directly in a grid (no file upload) — used for migrating customers
     * in from a previous billing system.
     */
    public function outstandingBalance(Request $request)
    {
        $month = null;
        $year = null;

        if ($request->filled('period') && str_contains($request->string('period'), '-')) {
            [$month, $year] = array_map('intval', explode('-', $request->string('period')));
        }

        $customers = null;

        if ($month && $year) {
            $customers = Customer::where('status', '!=', 'inactive')
                ->whereNotNull('package_id')
                ->orderBy('name')
                ->get();
        }

        return view('admin.invoices.outstanding-balance', [
            'month' => $month,
            'year' => $year,
            'customers' => $customers,
            'periodOptions' => $this->pastPeriodOptions(),
        ]);
    }

    public function outstandingBalanceStore(Request $request)
    {
        $data = $request->validate([
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'amounts' => ['nullable', 'array'],
            'amounts.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $period = Carbon::create($data['year'], $data['month'], 1);

        if (! $period->lt(now()->startOfMonth())) {
            return back()->with('error', 'Periode harus sebelum bulan berjalan.');
        }

        $saved = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($data['amounts'] ?? [] as $customerId => $amount) {
            // Scoped to the same customers the grid actually shows — see the equivalent
            // guard in CustomerController::discountsStore().
            $customer = Customer::where('status', '!=', 'inactive')->whereNotNull('package_id')->find($customerId);

            if (! $customer || (float) $amount <= 0) {
                continue;
            }

            try {
                $invoice = $this->invoiceService->recordOutstandingBalance($customer, (int) $data['month'], (int) $data['year'], (float) $amount);

                if ($invoice) {
                    $saved++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                // One customer's entry failing (e.g. an unexpected DB error) shouldn't abort
                // the whole grid submission — log it and keep saving the rest.
                $failed++;
                Log::error('Outstanding balance entry failed', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);
            }
        }

        $message = "{$saved} saldo sisa berhasil disimpan. {$skipped} dilewati (sudah ada tagihan untuk periode tersebut).";

        if ($failed > 0) {
            $message .= " {$failed} gagal disimpan karena kesalahan sistem — lihat log.";
        }

        return redirect()->route('invoices.outstanding-balance', ['period' => "{$data['month']}-{$data['year']}"])->with('status', $message);
    }

    /**
     * Past months only (never the current or a future one), furthest-first is avoided since
     * admins almost always need last month first — newest-past-month first, going back 24 months.
     */
    private function pastPeriodOptions(): array
    {
        $options = [];
        $cursor = now()->startOfMonth()->subMonthNoOverflow();

        for ($i = 0; $i < 24; $i++) {
            $options[] = [
                'month' => $cursor->month,
                'year' => $cursor->year,
                'label' => $cursor->translatedFormat('F Y'),
            ];
            $cursor = $cursor->copy()->subMonthNoOverflow();
        }

        return $options;
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'package', 'payments' => fn ($q) => $q->with('bankAccount')->orderByDesc('id')]);

        $totalPaid = $this->invoiceService->totalPaid($invoice);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'totalPaid' => $totalPaid,
            'remaining' => max((float) $invoice->total_amount - $totalPaid, 0),
            'remainingBreakdown' => $this->invoiceService->remainingBreakdown($invoice),
            'bankAccounts' => BankAccount::where('is_active', true)->orderBy('bank_name')->get(),
        ]);
    }

    public function print(Invoice $invoice)
    {
        $invoice->load(['customer', 'package', 'payments' => fn ($q) => $q->with('bankAccount')->where('status', 'paid')->orderBy('paid_at')]);

        $totalPaid = $this->invoiceService->totalPaid($invoice);

        return view('admin.invoices.print', [
            'invoice' => $invoice,
            'totalPaid' => $totalPaid,
            'remaining' => max((float) $invoice->total_amount - $totalPaid, 0),
            'remainingBreakdown' => $this->invoiceService->remainingBreakdown($invoice),
            'bankAccounts' => BankAccount::where('is_active', true)->orderBy('bank_name')->get(),
        ]);
    }

    public function edit(Invoice $invoice)
    {
        abort_if(in_array($invoice->status, ['paid', 'cancelled'], true), 403, 'Tagihan yang sudah selesai tidak bisa diubah.');

        return view('admin.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        abort_if(in_array($invoice->status, ['paid', 'cancelled'], true), 403, 'Tagihan yang sudah selesai tidak bisa diubah.');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        // Preserve any carry-over already folded into this invoice — the edit form only
        // covers the package/tax/discount side, not the carried-over balance.
        $data['total_amount'] = $data['amount'] + $data['tax_amount'] - $data['discount_amount'] + (float) $invoice->carry_over_amount;

        $invoice->update($data);

        return redirect()->route('invoices.show', $invoice)->with('status', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(Invoice $invoice)
    {
        abort_if($invoice->payments()->exists(), 403, 'Tagihan yang sudah punya pembayaran tidak bisa dihapus.');

        $invoice->delete();

        return redirect()->route('invoices.index')->with('status', 'Tagihan berhasil dihapus.');
    }

    public function pay(Invoice $invoice)
    {
        abort_if(in_array($invoice->status, ['paid', 'cancelled']), 403, 'Tagihan ini tidak bisa dibayar.');

        $checkoutUrl = $this->invoiceService->resolveTripayCheckoutUrl($invoice, $this->tripayService);

        return redirect()->away($checkoutUrl);
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        abort_if(in_array($invoice->status, ['paid', 'cancelled']), 403, 'Tagihan ini tidak menerima pembayaran baru.');

        $remaining = (float) $invoice->total_amount - $this->invoiceService->totalPaid($invoice);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.max($remaining, 0.01)],
            'method' => ['required', 'in:cash,transfer'],
            'bank_account_id' => ['nullable', 'required_if:method,transfer', 'exists:bank_accounts,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->invoiceService->recordManualPayment(
            $invoice,
            (float) $data['amount'],
            $request->user()->id,
            $data['note'] ?? null,
            $data['method'],
            $data['bank_account_id'] ?? null,
        );

        return redirect()->route('invoices.show', $invoice)->with('status', 'Pembayaran berhasil dicatat.');
    }

    public function sendEmailReminder(Invoice $invoice)
    {
        $customer = $invoice->customer;

        if (! $customer->email) {
            return back()->with('error', 'Pelanggan tidak memiliki alamat email.');
        }

        $stage = $this->reminderStage('email', $invoice);

        NotificationLog::record($invoice, 'email', 'manual_'.now()->timestamp);
        Mail::to($customer->email)->queue(new InvoiceReminderMail($invoice, $stage));

        return back()->with('status', 'Pengingat email berhasil dikirim ke '.$customer->email.'.');
    }

    public function sendWhatsappReminder(Invoice $invoice, TemplateRenderer $templateRenderer)
    {
        $customer = $invoice->customer;

        if (! $customer->phone) {
            return back()->with('error', 'Pelanggan tidak memiliki nomor telepon.');
        }

        $stage = $this->reminderStage('whatsapp', $invoice);
        $remaining = (float) $invoice->total_amount - $this->invoiceService->totalPaid($invoice);

        $body = $templateRenderer->render('whatsapp_invoice_reminder', [
            'customer_name' => $customer->name,
            'invoice_number' => $invoice->invoice_number,
            'due_date' => $invoice->due_date->translatedFormat('d F Y'),
            'total' => number_format($remaining, 0, ',', '.'),
            'pay_url' => $this->invoiceService->publicPayUrl($invoice),
            'pdf_url' => $this->invoiceService->publicPdfUrl($invoice),
            'app_name' => config('app.name'),
            'status_message' => ReminderStageMessages::message($stage, whatsapp: true),
            'bank_info' => TemplateRenderer::bankTransferInfo(),
        ])['body'];

        NotificationLog::record($invoice, 'whatsapp', 'manual_'.now()->timestamp);
        SendWhatsAppMessage::dispatch($customer->phone, $body);

        return back()->with('status', 'Pengingat WhatsApp berhasil dikirim ke '.$customer->phone.'.');
    }

    /**
     * The reminder stage matching the invoice's current due-date offset, falling back to
     * "due today" wording when the invoice sits outside the configured reminder windows.
     */
    private function reminderStage(string $channel, Invoice $invoice): string
    {
        $daysUntilDue = $this->invoiceService->daysUntil(now(), $invoice->due_date);

        return config("billing.reminder_offsets.{$channel}")[$daysUntilDue] ?? 'reminder_h0';
    }
}
