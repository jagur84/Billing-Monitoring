<?php

namespace App\Console\Commands\Invoices;

use App\Models\Customer;
use App\Services\Billing\InvoiceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateUpcomingInvoices extends Command
{
    protected $signature = 'invoices:generate-upcoming';

    protected $description = 'Generate each active customer\'s next invoice N days before their due date (config: billing.invoice_generate_days_before)';

    public function handle(InvoiceService $invoiceService): int
    {
        $daysBefore = (int) config('billing.invoice_generate_days_before');
        $today = now();
        $created = 0;
        $failed = 0;

        Customer::where('status', '!=', 'inactive')
            ->whereNotNull('package_id')
            ->chunkById(100, function ($customers) use (&$created, &$failed, $invoiceService, $today, $daysBefore) {
                foreach ($customers as $customer) {
                    $nextDue = $invoiceService->nextDueDateFor($customer);

                    if ($invoiceService->daysUntil($today, $nextDue) > $daysBefore) {
                        continue;
                    }

                    try {
                        if ($invoiceService->generateAndNotify($customer, $nextDue->month, $nextDue->year)) {
                            $created++;
                        }
                    } catch (\Throwable $e) {
                        // A single customer failing (e.g. racing a manual/bulk generation
                        // happening at the same moment) must not abort the whole day's run
                        // for every other customer still left in this and later chunks.
                        $failed++;
                        Log::error('Scheduled invoice generation failed', ['customer_id' => $customer->id, 'error' => $e->getMessage()]);
                    }
                }
            });

        $this->info("Generated {$created} invoice(s) ({$daysBefore} day(s) before due date)."
            .($failed > 0 ? " {$failed} failed — see log." : ''));

        return self::SUCCESS;
    }
}
