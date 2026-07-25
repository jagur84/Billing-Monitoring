<?php

namespace App\Services\Billing;

use App\Events\InvoicePaid;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Payment\TripayService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
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
        $total = $amount + $tax;

        $dueDay = min($customer->billing_due_day, Carbon::create($year, $month, 1)->daysInMonth);

        return Invoice::create([
            'invoice_number' => $this->generateInvoiceNumber($month, $year),
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'package_name_snapshot' => $package->name,
            'period_month' => $month,
            'period_year' => $year,
            'amount' => $amount,
            'tax_amount' => $tax,
            'discount_amount' => 0,
            'total_amount' => $total,
            'due_date' => Carbon::create($year, $month, $dueDay),
            'status' => 'unpaid',
        ]);
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
