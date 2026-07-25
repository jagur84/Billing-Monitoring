<?php

namespace Tests\Feature\Console;

use App\Mail\InvoiceCreatedMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GenerateUpcomingInvoicesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_generates_invoice_and_emails_customer_exactly_at_the_configured_offset(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-08-03'); // 7 days before Aug 10 due date

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-00001', 'name' => 'Test Customer', 'email' => 'customer@example.com',
            'package_id' => $package->id, 'billing_due_day' => 10, 'status' => 'active',
        ]);

        $this->artisan('invoices:generate-upcoming')->assertSuccessful();

        $invoice = Invoice::where('customer_id', $customer->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame(8, $invoice->period_month);
        $this->assertSame(2026, $invoice->period_year);

        Mail::assertQueued(InvoiceCreatedMail::class);
        $this->assertTrue(NotificationLog::alreadySent($invoice, 'email', 'invoice_created'));
    }

    public function test_it_does_not_generate_invoice_outside_the_configured_offset(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-08-01'); // 9 days before due date, not the configured 7

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        Customer::create([
            'customer_code' => 'CUST-00002', 'name' => 'Test Customer 2', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $this->artisan('invoices:generate-upcoming')->assertSuccessful();

        $this->assertSame(0, Invoice::count());
        Mail::assertNothingQueued();
    }

    public function test_it_does_not_send_duplicate_invoice_created_email_on_repeated_runs(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-08-03');

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        Customer::create([
            'customer_code' => 'CUST-00003', 'name' => 'Test Customer 3', 'email' => 'c3@example.com',
            'package_id' => $package->id, 'billing_due_day' => 10, 'status' => 'active',
        ]);

        $this->artisan('invoices:generate-upcoming');
        $this->artisan('invoices:generate-upcoming');

        Mail::assertQueued(InvoiceCreatedMail::class, 1);
    }
}
