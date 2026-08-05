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

    public function test_it_applies_the_customers_recurring_discount_percent(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 200000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-D1', 'name' => 'Discounted Customer', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active', 'discount_percent' => 10, 'discount_note' => 'Diskon karyawan',
        ]);

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 8, 2026);

        $this->assertSame(20000.0, (float) $invoice->discount_amount);
        $this->assertSame(180000.0, (float) $invoice->total_amount);
    }

    public function test_discount_still_applies_when_due_date_is_on_or_before_the_expiry(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 200000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-D8', 'name' => 'Not Yet Expired Customer', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active', 'discount_percent' => 10,
            'discount_valid_until' => '2026-08-10',
        ]);

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 8, 2026);

        $this->assertSame(20000.0, (float) $invoice->discount_amount);
    }

    public function test_discount_no_longer_applies_once_the_invoice_due_date_is_past_the_expiry(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 200000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-D9', 'name' => 'Expired Customer', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active', 'discount_percent' => 10,
            'discount_valid_until' => '2026-07-31',
        ]);

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 8, 2026);

        $this->assertSame(0.0, (float) $invoice->discount_amount);
        $this->assertSame(200000.0, (float) $invoice->total_amount);
    }

    public function test_it_applies_a_fixed_nominal_discount(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 200000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-D6', 'name' => 'Nominal Discount Customer', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active', 'discount_type' => 'nominal', 'discount_nominal' => 35000,
        ]);

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 8, 2026);

        $this->assertSame(35000.0, (float) $invoice->discount_amount);
        $this->assertSame(165000.0, (float) $invoice->total_amount);
    }

    public function test_a_nominal_discount_larger_than_the_invoice_is_capped_at_zero_total(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 100000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-D7', 'name' => 'Overshot Discount Customer', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active', 'discount_type' => 'nominal', 'discount_nominal' => 999999,
        ]);

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 8, 2026);

        $this->assertSame(100000.0, (float) $invoice->discount_amount);
        $this->assertSame(0.0, (float) $invoice->total_amount);
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

    public function test_generating_next_invoice_carries_over_previous_unpaid_balance(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-00010', 'name' => 'Test Customer 10', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $service = app(InvoiceService::class);

        $julyInvoice = $service->generateForCustomer($customer, 7, 2026);
        $service->recordManualPayment($julyInvoice, 120000, null); // pays 120k of 150k, leaves 30k

        $augustInvoice = $service->generateForCustomer($customer, 8, 2026);

        $this->assertSame(30000.0, (float) $augustInvoice->carry_over_amount);
        $this->assertNotNull($augustInvoice->carry_over_note);
        $this->assertSame(180000.0, (float) $augustInvoice->total_amount); // 150k + 30k carried over

        $julyInvoice->refresh();
        $this->assertSame('paid', $julyInvoice->status);
        $this->assertSame(1, $julyInvoice->payments()->where('gateway', 'carry_over')->count());
    }

    public function test_carry_over_is_zero_when_no_previous_balance_exists(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-00011', 'name' => 'Test Customer 11', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 8, 2026);

        $this->assertSame(0.0, (float) $invoice->carry_over_amount);
        $this->assertNull($invoice->carry_over_note);
        $this->assertSame(150000.0, (float) $invoice->total_amount);
    }

    public function test_recording_outstanding_balance_retroactively_tops_up_an_already_existing_later_invoice(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 130000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-00012', 'name' => 'Test Customer 12', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $service = app(InvoiceService::class);

        // August invoice already exists (generated before the July balance was entered) —
        // mirrors an admin migrating a customer's old data in after their normal billing
        // already generated the next invoice.
        $augustInvoice = $service->generateForCustomer($customer, 8, 2026);
        $this->assertSame(130000.0, (float) $augustInvoice->total_amount);

        $julyInvoice = $service->recordOutstandingBalance($customer, 7, 2026, 30000);

        $this->assertNotNull($julyInvoice);
        $julyInvoice->refresh();
        $this->assertSame('paid', $julyInvoice->status);
        $this->assertSame(1, $julyInvoice->payments()->where('gateway', 'carry_over')->count());

        $augustInvoice->refresh();
        $this->assertSame(30000.0, (float) $augustInvoice->carry_over_amount);
        $this->assertSame(160000.0, (float) $augustInvoice->total_amount);
    }

    public function test_multiple_months_of_outstanding_balance_are_all_folded_into_the_next_invoice(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 130000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-00013', 'name' => 'Test Customer 13', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $service = app(InvoiceService::class);

        $service->recordOutstandingBalance($customer, 6, 2026, 20000);
        $service->recordOutstandingBalance($customer, 7, 2026, 30000);

        $augustInvoice = $service->generateForCustomer($customer, 8, 2026);

        $this->assertSame(50000.0, (float) $augustInvoice->carry_over_amount);
        $this->assertSame(180000.0, (float) $augustInvoice->total_amount); // 130k + 20k + 30k
    }

    public function test_carrying_a_balance_forward_across_three_consecutive_months_never_leaves_more_than_one_invoice_outstanding(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 100000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-00015', 'name' => 'Test Customer 15', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $service = app(InvoiceService::class);

        $june = $service->generateForCustomer($customer, 6, 2026);
        $july = $service->generateForCustomer($customer, 7, 2026);
        $august = $service->generateForCustomer($customer, 8, 2026);
        $september = $service->generateForCustomer($customer, 9, 2026);

        // Every month rolls the previous balance forward exactly once: 100k, then 200k,
        // then 300k, then 400k — never double-counted, never left behind on an old invoice.
        $june->refresh();
        $july->refresh();
        $august->refresh();
        $september->refresh();

        $this->assertSame('paid', $june->status);
        $this->assertSame('paid', $july->status);
        $this->assertSame('paid', $august->status);
        $this->assertSame('unpaid', $september->status);

        $this->assertSame(100000.0, (float) $june->total_amount);
        $this->assertSame(200000.0, (float) $july->total_amount);
        $this->assertSame(300000.0, (float) $august->total_amount);
        $this->assertSame(400000.0, (float) $september->total_amount);

        // Only one invoice should ever be outstanding at a time for this customer, and its
        // remaining balance should equal the full running total — not some multiple of it.
        $outstanding = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['unpaid', 'overdue', 'partial'])
            ->get();

        $this->assertCount(1, $outstanding);
        $this->assertSame($september->id, $outstanding->first()->id);
        $this->assertSame(400000.0, (float) $outstanding->sum('total_amount'));
    }

    public function test_remaining_breakdown_applies_payments_to_the_carried_over_balance_first(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-00016', 'name' => 'Test Customer 16', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $service = app(InvoiceService::class);
        $service->generateForCustomer($customer, 7, 2026); // 150k, left unpaid
        $august = $service->generateForCustomer($customer, 8, 2026); // 150k + 150k carried = 300k

        $this->assertSame(150000.0, (float) $august->carry_over_amount);

        // No payment yet: all of the old balance, plus all of this month's, still owed.
        $breakdown = $service->remainingBreakdown($august);
        $this->assertSame(150000.0, $breakdown['old']);
        $this->assertSame(150000.0, $breakdown['current']);

        // Partial payment smaller than the carried-over amount: only chips away at "old".
        $service->recordManualPayment($august, 50000, null);
        $breakdown = $service->remainingBreakdown($august->fresh());
        $this->assertSame(100000.0, $breakdown['old']);
        $this->assertSame(150000.0, $breakdown['current']);

        // Payment that finishes off the old balance and spills into the current charge.
        $service->recordManualPayment($august, 120000, null);
        $breakdown = $service->remainingBreakdown($august->fresh());
        $this->assertSame(0.0, $breakdown['old']);
        $this->assertSame(130000.0, $breakdown['current']);
    }

    public function test_recording_outstanding_balance_skips_a_period_that_already_has_an_invoice(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 130000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-00014', 'name' => 'Test Customer 14', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $service = app(InvoiceService::class);
        $service->generateForCustomer($customer, 7, 2026);

        $result = $service->recordOutstandingBalance($customer, 7, 2026, 30000);

        $this->assertNull($result);
        $this->assertSame(1, Invoice::where('customer_id', $customer->id)->where('period_month', 7)->count());
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
