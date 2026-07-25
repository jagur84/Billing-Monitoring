<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Billing\InvoiceService;
use App\Services\Payment\TripayService;

class PublicPaymentController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService,
        private TripayService $tripayService,
    ) {
    }

    public function show(Invoice $invoice)
    {
        $invoice->load('customer');

        return view('public.invoice-pay', compact('invoice'));
    }

    public function checkout(Invoice $invoice)
    {
        abort_if(in_array($invoice->status, ['paid', 'cancelled']), 403, 'Tagihan ini tidak bisa dibayar.');

        $checkoutUrl = $this->invoiceService->resolveTripayCheckoutUrl($invoice, $this->tripayService);

        return redirect()->away($checkoutUrl);
    }

    public function pdf(Invoice $invoice)
    {
        $pdf = $this->invoiceService->generatePdf($invoice);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$invoice->invoice_number.'.pdf"',
        ]);
    }
}
