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
use Illuminate\Http\Request;
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

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'package', 'payments' => fn ($q) => $q->with('bankAccount')->orderByDesc('id')]);

        $totalPaid = $this->invoiceService->totalPaid($invoice);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'totalPaid' => $totalPaid,
            'remaining' => max((float) $invoice->total_amount - $totalPaid, 0),
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
            'bankAccounts' => BankAccount::where('is_active', true)->orderBy('bank_name')->get(),
        ]);
    }

    public function edit(Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 403, 'Tagihan yang sudah dibayar tidak bisa diubah.');

        return view('admin.invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 403, 'Tagihan yang sudah dibayar tidak bisa diubah.');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'due_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['total_amount'] = $data['amount'] + $data['tax_amount'] - $data['discount_amount'];

        $invoice->update($data);

        return redirect()->route('invoices.show', $invoice)->with('status', 'Tagihan berhasil diperbarui.');
    }

    public function destroy(Invoice $invoice)
    {
        abort_if($invoice->status === 'paid', 403, 'Tagihan yang sudah dibayar tidak bisa dihapus.');

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
