<?php

namespace Tests\Unit\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Services\Billing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_generates_an_invoice_with_correct_tax_and_total(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps',
            'speed_mbps' => 10,
            'price' => 150000,
            'tax_percent' => 11,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUST-00001',
            'name' => 'Test Customer',
            'package_id' => $package->id,
            'billing_due_day' => 10,
            'status' => 'active',
        ]);

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 3, 2026);

        $this->assertNotNull($invoice);
        $this->assertSame(150000.0, (float) $invoice->amount);
        $this->assertSame(16500.0, (float) $invoice->tax_amount);
        $this->assertSame(166500.0, (float) $invoice->total_amount);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame(10, $invoice->due_date->day);
    }

    public function test_it_does_not_duplicate_an_invoice_for_the_same_period(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 11, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-00002', 'name' => 'Test Customer 2', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $service = app(InvoiceService::class);
        $first = $service->generateForCustomer($customer, 3, 2026);
        $second = $service->generateForCustomer($customer, 3, 2026);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(1, Invoice::count());
    }

    public function test_manual_payment_marks_invoice_paid(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-00003', 'name' => 'Test Customer 3', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $service = app(InvoiceService::class);
        $invoice = $service->generateForCustomer($customer, 3, 2026);
        $service->recordManualPayment($invoice, (float) $invoice->total_amount, null);

        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(1, $invoice->payments()->count());
    }

    public function test_next_due_date_is_this_month_when_not_yet_passed(): void
    {
        Carbon::setTestNow('2026-07-05');

        $customer = Customer::make(['billing_due_day' => 10]);
        $nextDue = app(InvoiceService::class)->nextDueDateFor($customer);

        $this->assertSame('2026-07-10', $nextDue->toDateString());
    }

    public function test_next_due_date_rolls_over_to_next_month_when_already_passed(): void
    {
        Carbon::setTestNow('2026-07-15');

        $customer = Customer::make(['billing_due_day' => 10]);
        $nextDue = app(InvoiceService::class)->nextDueDateFor($customer);

        $this->assertSame('2026-08-10', $nextDue->toDateString());
    }

    public function test_next_due_date_clamps_to_days_in_month(): void
    {
        Carbon::setTestNow('2026-01-30');

        $customer = Customer::make(['billing_due_day' => 31]);
        $nextDue = app(InvoiceService::class)->nextDueDateFor($customer);

        // January 31 hasn't passed yet relative to Jan 30 "today", so it stays in January.
        $this->assertSame('2026-01-31', $nextDue->toDateString());
    }

    /** @dataProvider daysUntilProvider */
    public function test_days_until_is_signed_correctly(string $today, string $target, int $expected): void
    {
        $service = app(InvoiceService::class);

        $this->assertSame($expected, $service->daysUntil(Carbon::parse($today), Carbon::parse($target)));
    }

    public static function daysUntilProvider(): array
    {
        return [
            'target in future' => ['2026-07-10', '2026-07-13', 3],
            'target is today' => ['2026-07-10', '2026-07-10', 0],
            'target in past' => ['2026-07-10', '2026-07-03', -7],
        ];
    }
}
