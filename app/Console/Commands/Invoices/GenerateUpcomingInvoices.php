<?php

namespace App\Console\Commands\Invoices;

use App\Models\Customer;
use App\Services\Billing\InvoiceService;
use Illuminate\Console\Command;

class GenerateUpcomingInvoices extends Command
{
    protected $signature = 'invoices:generate-upcoming';

    protected $description = 'Generate each active customer\'s next invoice N days before their due date (config: billing.invoice_generate_days_before)';

    public function handle(InvoiceService $invoiceService): int
    {
        $daysBefore = (int) config('billing.invoice_generate_days_before');
        $today = now();
        $created = 0;

        Customer::where('status', '!=', 'inactive')
            ->whereNotNull('package_id')
            ->chunkById(100, function ($customers) use (&$created, $invoiceService, $today, $daysBefore) {
                foreach ($customers as $customer) {
                    $nextDue = $invoiceService->nextDueDateFor($customer);

                    if ($invoiceService->daysUntil($today, $nextDue) > $daysBefore) {
                        continue;
                    }

                    if ($invoiceService->generateAndNotify($customer, $nextDue->month, $nextDue->year)) {
                        $created++;
                    }
                }
            });

        $this->info("Generated {$created} invoice(s) ({$daysBefore} day(s) before due date).");

        return self::SUCCESS;
    }
}
