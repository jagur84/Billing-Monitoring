<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Services\Mikrotik\MikrotikService;
use Illuminate\Contracts\Queue\ShouldQueue;

class RestoreMikrotikAccessOnPayment implements ShouldQueue
{
    public function __construct(private MikrotikService $mikrotikService)
    {
    }

    public function handle(InvoicePaid $event): void
    {
        $customer = $event->invoice->customer;

        if ($customer && $customer->status === 'isolated' && $customer->router_id) {
            $this->mikrotikService->restoreCustomer($customer);
        }
    }
}
