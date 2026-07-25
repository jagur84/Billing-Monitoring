<?php

namespace App\Console\Commands\Mikrotik;

use App\Models\Customer;
use App\Services\Mikrotik\MikrotikService;
use Illuminate\Console\Command;

class RestorePaidCustomers extends Command
{
    protected $signature = 'mikrotik:restore-paid';

    protected $description = 'Safety-net sweep: restore isolated customers who no longer have unpaid/overdue invoices';

    public function handle(MikrotikService $mikrotikService): int
    {
        $restored = 0;

        Customer::where('status', 'isolated')
            ->whereNotNull('router_id')
            ->whereNotNull('pppoe_username')
            ->whereDoesntHave('invoices', fn ($query) => $query->whereIn('status', ['unpaid', 'overdue']))
            ->chunkById(50, function ($customers) use (&$restored, $mikrotikService) {
                foreach ($customers as $customer) {
                    if ($mikrotikService->restoreCustomer($customer)) {
                        $restored++;
                    }
                }
            });

        $this->info("Restored {$restored} customer(s).");

        return self::SUCCESS;
    }
}
