<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    public function __construct(private readonly string $baseUrl)
    {
    }

    public function send(string $phone, string $message): bool
    {
        try {
            $response = Http::baseUrl($this->baseUrl)->timeout(10)->post('/send', [
                'phone' => $phone,
                'message' => $message,
            ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp send failed', ['phone' => $phone, 'response' => $response->body()]);
            }

            return $response->successful();
        } catch (Throwable $e) {
            Log::warning('WhatsApp send failed', ['phone' => $phone, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function status(): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)->timeout(5)->get('/status');

            return $response->successful() ? $response->json() : ['connected' => false];
        } catch (Throwable) {
            return ['connected' => false];
        }
    }
}
