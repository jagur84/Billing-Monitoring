<?php

namespace Tests\Unit\Services;

use App\Services\Payment\TripayService;
use PHPUnit\Framework\TestCase;

class TripayServiceTest extends TestCase
{
    public function test_it_verifies_a_valid_webhook_signature(): void
    {
        $privateKey = 'test-private-key';
        $service = new TripayService('https://tripay.co.id/api-sandbox', 'MERCHANT', 'api-key', $privateKey);

        $body = json_encode(['reference' => 'ABC123', 'status' => 'PAID']);
        $signature = hash_hmac('sha256', $body, $privateKey);

        $this->assertTrue($service->verifySignature($body, $signature));
    }

    public function test_it_rejects_an_invalid_webhook_signature(): void
    {
        $service = new TripayService('https://tripay.co.id/api-sandbox', 'MERCHANT', 'api-key', 'test-private-key');

        $body = json_encode(['reference' => 'ABC123', 'status' => 'PAID']);

        $this->assertFalse($service->verifySignature($body, 'not-the-right-signature'));
        $this->assertFalse($service->verifySignature($body, null));
    }
}
