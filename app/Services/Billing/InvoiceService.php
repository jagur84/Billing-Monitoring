<?php

namespace App\Services\Billing;

use App\Events\InvoicePaid;
use App\Mail\InvoiceCreatedMail;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Payment\TripayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class InvoiceService
{
    public function generateForCustomer(Customer $customer, int $month, int $year): ?Invoice
    {
        if (! $customer->package_id || $customer->status === 'inactive') {
            return null;
        }

        $exists = Invoice::where('customer_id', $customer->id)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->exists();

        if ($exists) {
            return null;
        }

        $package = $customer->package;
        $amount = (float) $package->price;
        $tax = round($amount * ((float) $package->tax_percent / 100), 2);
        $discount = round($amount * ((float) $customer->discount_percent / 100), 2);
        $dueDay = min($customer->billing_due_day, Carbon::create($year, $month, 1)->daysInMonth);

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber($month, $year),
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'package_name_snapshot' => $package->name,
            'period_month' => $month,
            'period_year' => $year,
            'amount' => $amount,
            'tax_amount' => $tax,
            'discount_amount' => $discount,
            'carry_over_amount' => 0,
            'total_amount' => $amount + $tax - $discount,
            'due_date' => Carbon::create($year, $month, $dueDay),
            'status' => 'unpaid',
        ]);

        $this->applyOutstandingCarryOver($customer);

        return $invoice->fresh();
    }

    /**
     * Manually record a customer's known remaining balance from a past period (e.g. migrating
     * a customer in from an old system) as a plain unpaid invoice for that period. Immediately
     * tries to fold it into whichever invoice is currently this customer's latest open one —
     * if that invoice was already generated earlier (the common migration case), it gets
     * retroactively topped up rather than waiting for the customer's next billing cycle.
     */
    public function recordOutstandingBalance(Customer $customer, int $month, int $year, float $amount): ?Invoice
    {
        if ($amount <= 0) {
            return null;
        }

        $exists = Invoice::where('customer_id', $customer->id)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->exists();

        if ($exists) {
            return null;
        }

        $dueDay = min($customer->billing_due_day, Carbon::create($year, $month, 1)->daysInMonth);
        $amount = round($amount, 2);

        $invoice = Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber($month, $year),
            'customer_id' => $customer->id,
            'package_id' => $customer->package_id,
            'package_name_snapshot' => $customer->package?->name,
            'period_month' => $month,
            'period_year' => $year,
            'amount' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'carry_over_amount' => 0,
            'total_amount' => $amount,
            'due_date' => Carbon::create($year, $month, $dueDay),
            'status' => 'unpaid',
            'notes' => 'Migrasi saldo sisa dari sistem lama',
        ]);

        $this->applyOutstandingCarryOver($customer);

        return $invoice->fresh();
    }

    /**
     * Fold every one of a customer's still-outstanding invoices into whichever invoice is
     * currently their latest open one (by period) — closing the older ones out via a synthetic
     * carry_over payment. Called both right after a brand new invoice is generated (the new
     * invoice naturally becomes the latest) and right after a past-period balance is entered
     * manually (which may need to retroactively top up an invoice generated earlier).
     */
    public function applyOutstandingCarryOver(Customer $customer): void
    {
        $target = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['unpaid', 'overdue', 'partial'])
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderByDesc('id')
            ->first();

        if (! $target) {
            return;
        }

        [$carryOver, $note, $sourceInvoices] = $this->pendingBalanceFor($customer, $target->id);

        if ($carryOver <= 0) {
            return;
        }

        $target->update([
            'carry_over_amount' => round((float) $target->carry_over_amount + $carryOver, 2),
            'carry_over_note' => $target->carry_over_note ? "{$target->carry_over_note}; {$note}" : $note,
            'total_amount' => round((float) $target->total_amount + $carryOver, 2),
        ]);

        $this->closeCarriedOverInvoices($sourceInvoices, $target);
        $this->refreshPaymentStatus($target->fresh());
    }

    /**
     * Generate the next invoice for a customer and, if one was created, queue the
     * "invoice created" email — shared by the daily scheduler and the manual bulk-generate
     * action so both send exactly one notification per invoice via NotificationLog.
     */
    public function generateAndNotify(Customer $customer, int $month, int $year): ?Invoice
    {
        $invoice = $this->generateForCustomer($customer, $month, $year);

        if ($invoice && $customer->email && NotificationLog::record($invoice, 'email', 'invoice_created')) {
            Mail::to($customer->email)->queue(new InvoiceCreatedMail($invoice));
        }

        return $invoice;
    }

    /**
     * Sum of this customer's still-outstanding invoices (unpaid/overdue/partial), excluding the
     * carry-over target itself, to fold in as a carry-over. Returns [amount, note, invoices] so
     * the caller can both stamp the target invoice and close out the sources afterward.
     */
    private function pendingBalanceFor(Customer $customer, int $excludeInvoiceId): array
    {
        $outstanding = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['unpaid', 'overdue', 'partial'])
            ->where('id', '!=', $excludeInvoiceId)
            ->get();

        $carryOver = 0.0;
        $noteLines = [];

        foreach ($outstanding as $old) {
            $remaining = round((float) $old->total_amount - $this->totalPaid($old), 2);

            if ($remaining <= 0) {
                continue;
            }

            $carryOver += $remaining;
            $noteLines[] = sprintf('Sisa dari %s (Rp%s)', $old->invoice_number, number_format($remaining, 0, ',', '.'));
        }

        $carryOver = round($carryOver, 2);

        return [$carryOver, $noteLines ? implode('; ', $noteLines) : null, $outstanding];
    }

    /**
     * Close out the older invoices absorbed into a new invoice's carry-over amount by
     * recording a synthetic "carry_over" payment for each one's remaining balance — this
     * reuses refreshPaymentStatus() to flip them to paid so they stop double-counting as
     * outstanding arrears, while keeping an audit trail of where the balance moved to.
     */
    private function closeCarriedOverInvoices(iterable $sourceInvoices, Invoice $newInvoice): void
    {
        foreach ($sourceInvoices as $old) {
            $remaining = round((float) $old->total_amount - $this->totalPaid($old), 2);

            if ($remaining <= 0) {
                continue;
            }

            $old->payments()->create([
                'gateway' => 'carry_over',
                'amount' => $remaining,
                'status' => 'paid',
                'paid_at' => now(),
                'raw_response' => ['note' => "Digabung ke {$newInvoice->invoice_number}"],
            ]);

            $this->refreshPaymentStatus($old);
        }
    }

    /**
     * Record a manually-collected payment (cash or bank transfer) against an invoice. Supports
     * installments: if $amount doesn't cover the full remaining balance, the invoice is marked
     * "partial" rather than "paid", and further payments can be recorded later until settled.
     */
    public function recordManualPayment(Invoice $invoice, float $amount, ?int $recordedBy, ?string $note = null, string $method = 'cash', ?int $bankAccountId = null): Payment
    {
        $payment = $invoice->payments()->create([
            'gateway' => 'manual',
            'gateway_payment_method' => $method,
            'amount' => $amount,
            'status' => 'paid',
            'paid_at' => now(),
            'recorded_by' => $recordedBy,
            'bank_account_id' => $method === 'transfer' ? $bankAccountId : null,
            'raw_response' => $note ? ['note' => $note] : null,
        ]);

        $this->refreshPaymentStatus($invoice);

        return $payment;
    }

    /**
     * Total of all payments actually collected (status=paid) toward this invoice so far.
     */
    public function totalPaid(Invoice $invoice): float
    {
        return (float) $invoice->payments()->where('status', 'paid')->sum('amount');
    }

    /**
     * Recompute the invoice's status from its accumulated paid-payments total: fully settled
     * flips it to "paid" (and fires InvoicePaid only on that transition), a non-zero partial
     * total marks it "partial", otherwise it's left as unpaid/overdue/cancelled.
     */
    private function refreshPaymentStatus(Invoice $invoice): void
    {
        $totalPaid = $this->totalPaid($invoice);
        $wasPaid = $invoice->status === 'paid';

        if ($totalPaid >= (float) $invoice->total_amount) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);

            if (! $wasPaid) {
                InvoicePaid::dispatch($invoice->fresh());
            }
        } elseif ($totalPaid > 0 && ! in_array($invoice->status, ['cancelled'], true)) {
            $invoice->update(['status' => 'partial']);
        }
    }

    public function markPaidFromGateway(Payment $payment): void
    {
        $payment->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $invoice = $payment->invoice;

        if ($invoice->status !== 'paid') {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            InvoicePaid::dispatch($invoice->fresh());
        }
    }

    /**
     * Reuse a still-pending Tripay checkout for this invoice, or create a fresh one.
     */
    public function resolveTripayCheckoutUrl(Invoice $invoice, TripayService $tripayService): string
    {
        $pending = $invoice->payments()
            ->where('gateway', 'tripay')
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($pending && ($pending->raw_response['checkout_url'] ?? null)) {
            return $pending->raw_response['checkout_url'];
        }

        $data = $tripayService->createTransaction($invoice, config('tripay.default_method'));

        $invoice->payments()->create([
            'gateway' => 'tripay',
            'gateway_reference' => $data['reference'] ?? null,
            'gateway_payment_method' => $data['payment_method'] ?? config('tripay.default_method'),
            'amount' => $invoice->total_amount,
            'status' => 'pending',
            'raw_response' => $data,
        ]);

        return $data['checkout_url'];
    }

    /**
     * A signed, unauthenticated link a customer can open to view and pay this invoice.
     */
    public function publicPayUrl(Invoice $invoice): string
    {
        return URL::temporarySignedRoute('public.invoice.pay', now()->addDays(14), ['invoice' => $invoice->id]);
    }

    /**
     * A signed, unauthenticated link to download this invoice as a PDF — used in email
     * attachments' companion text and in the WhatsApp reminder (which can't attach files).
     */
    public function publicPdfUrl(Invoice $invoice): string
    {
        return URL::temporarySignedRoute('public.invoice.pdf', now()->addDays(14), ['invoice' => $invoice->id]);
    }

    /**
     * Render the invoice as a PDF binary, suitable for emailing as an attachment or streaming
     * as a download.
     */
    public function generatePdf(Invoice $invoice): string
    {
        $invoice->loadMissing('customer', 'package');

        $totalPaid = $this->totalPaid($invoice);

        return Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'totalPaid' => $totalPaid,
            'remaining' => max((float) $invoice->total_amount - $totalPaid, 0),
            'bankAccounts' => BankAccount::where('is_active', true)->orderBy('bank_name')->get(),
        ])->output();
    }

    public function generateInvoiceNumber(int $month, int $year): string
    {
        $prefix = Setting::get('invoice_number_prefix', 'INV');

        do {
            $number = sprintf('%s-%04d%02d-%s', $prefix, $year, $month, strtoupper(Str::random(5)));
        } while (Invoice::where('invoice_number', $number)->exists());

        return $number;
    }

    /**
     * The customer's next due date: this month's if it hasn't passed yet, otherwise next month's.
     */
    public function nextDueDateFor(Customer $customer): Carbon
    {
        $today = Carbon::today();

        $dueDayThisMonth = min($customer->billing_due_day, $today->daysInMonth);
        $candidate = Carbon::create($today->year, $today->month, $dueDayThisMonth);

        if ($candidate->lt($today)) {
            $nextMonth = $today->copy()->addMonthNoOverflow();
            $dueDayNextMonth = min($customer->billing_due_day, $nextMonth->daysInMonth);

            return Carbon::create($nextMonth->year, $nextMonth->month, $dueDayNextMonth);
        }

        return $candidate;
    }

    /**
     * Signed day count from $today to $target: positive if $target is in the future,
     * negative if it has already passed.
     */
    public function daysUntil(Carbon $today, Carbon $target): int
    {
        return (int) $today->copy()->startOfDay()->diffInDays($target->copy()->startOfDay(), false);
    }
}
