<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TripayService
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $merchantCode,
        private readonly string $apiKey,
        private readonly string $privateKey,
    ) {
    }

    /**
     * Create a closed payment transaction on Tripay for the given invoice.
     */
    public function createTransaction(Invoice $invoice, string $method): array
    {
        $merchantRef = $invoice->invoice_number.'-'.now()->timestamp;
        $amount = (int) round($invoice->total_amount);

        $signature = hash_hmac('sha256', $this->merchantCode.$merchantRef.$amount, $this->privateKey);

        $response = Http::withToken($this->apiKey)
            ->asForm()
            ->post("{$this->baseUrl}/transaction/create", [
                'method' => $method,
                'merchant_ref' => $merchantRef,
                'amount' => $amount,
                'customer_name' => $invoice->customer->name,
                'customer_email' => $invoice->customer->email ?: 'customer@example.com',
                'customer_phone' => $invoice->customer->phone,
                'order_items' => json_encode([[
                    'sku' => $invoice->invoice_number,
                    'name' => 'Tagihan '.($invoice->package_name_snapshot ?? 'Internet'),
                    'price' => $amount,
                    'quantity' => 1,
                ]]),
                'return_url' => route('invoices.show', $invoice),
                'expired_time' => now()->addHours(24)->timestamp,
                'signature' => $signature,
            ]);

        if (! $response->successful() || ! ($response->json('success'))) {
            throw new RuntimeException('Tripay transaction creation failed: '.$response->body());
        }

        return $response->json('data');
    }

    public function listPaymentChannels(): array
    {
        $response = Http::withToken($this->apiKey)->get("{$this->baseUrl}/merchant/payment-channel");

        return $response->successful() ? $response->json('data', []) : [];
    }

    public function verifySignature(string $rawBody, ?string $signature): bool
    {
        if (! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $this->privateKey);

        return hash_equals($expected, $signature);
    }
}
