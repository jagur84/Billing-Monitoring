<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\Billing\InvoiceService;
use App\Services\Payment\TripayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TripayWebhookController extends Controller
{
    public function __construct(
        private TripayService $tripayService,
        private InvoiceService $invoiceService,
    ) {
    }

    public function handle(Request $request)
    {
        $rawBody = $request->getContent();
        $signature = $request->header('X-Callback-Signature');

        if (! $this->tripayService->verifySignature($rawBody, $signature)) {
            Log::warning('Tripay webhook: invalid signature', ['payload' => $rawBody]);

            return response()->json(['success' => false, 'message' => 'Invalid signature'], 403);
        }

        $payload = $request->json()->all();
        $reference = $payload['reference'] ?? null;
        $status = strtoupper($payload['status'] ?? '');

        $payment = Payment::where('gateway', 'tripay')
            ->where('gateway_reference', $reference)
            ->first();

        if (! $payment) {
            Log::warning('Tripay webhook: payment not found', ['reference' => $reference]);

            return response()->json(['success' => false, 'message' => 'Payment not found'], 404);
        }

        $payment->update(['raw_response' => $payload]);

        match ($status) {
            'PAID' => $this->invoiceService->markPaidFromGateway($payment),
            'EXPIRED' => $payment->update(['status' => 'expired']),
            'FAILED', 'REFUND' => $payment->update(['status' => 'failed']),
            default => null,
        };

        return response()->json(['success' => true]);
    }
}
