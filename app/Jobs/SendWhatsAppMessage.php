<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppMessage implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = 10;

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
    ) {
    }

    public function handle(WhatsAppService $whatsAppService): void
    {
        $whatsAppService->send($this->phone, $this->message);
    }
}
