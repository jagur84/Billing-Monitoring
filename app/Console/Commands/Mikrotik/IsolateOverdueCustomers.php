<?php

namespace App\Console\Commands\Mikrotik;

use App\Models\Customer;
use App\Services\Mikrotik\MikrotikService;
use Illuminate\Console\Command;

class IsolateOverdueCustomers extends Command
{
    protected $signature = 'mikrotik:isolate-overdue';

    protected $description = 'Isolate (PPPoE disable) customers whose overdue invoices are past the grace period';

    public function handle(MikrotikService $mikrotikService): int
    {
        $graceDays = (int) config('billing.isolation_grace_days');
        $isolated = 0;

        Customer::where('status', 'active')
            ->whereNotNull('router_id')
            ->whereNotNull('pppoe_username')
            ->whereHas('invoices', function ($query) use ($graceDays) {
                $query->where('status', 'overdue')
                    ->whereDate('due_date', '<=', now()->subDays($graceDays)->toDateString());
            })
            ->chunkById(50, function ($customers) use (&$isolated, $mikrotikService) {
                foreach ($customers as $customer) {
                    if ($mikrotikService->isolateCustomer($customer)) {
                        $isolated++;
                    }
                }
            });

        $this->info("Isolated {$isolated} customer(s).");

        return self::SUCCESS;
    }
}
