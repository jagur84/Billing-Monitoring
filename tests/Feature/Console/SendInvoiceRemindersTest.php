<?php

namespace Tests\Feature\Console;

use App\Jobs\SendWhatsAppMessage;
use App\Mail\InvoiceReminderMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendInvoiceRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeInvoice(string $dueDate, ?string $email = 'c@example.com', ?string $phone = '081234567890'): Invoice
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-'.uniqid(), 'name' => 'Test Customer', 'email' => $email, 'phone' => $phone,
            'package_id' => $package->id, 'billing_due_day' => 10, 'status' => 'active',
        ]);

        return Invoice::create([
            'invoice_number' => 'INV-'.uniqid(),
            'customer_id' => $customer->id,
            'package_id' => $package->id,
            'period_month' => 1,
            'period_year' => 2026,
            'amount' => 150000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 150000,
            'due_date' => $dueDate,
            'status' => 'unpaid',
        ]);
    }

    public function test_h_minus_3_sends_email_only_not_whatsapp(): void
    {
        Mail::fake();
        Bus::fake();
        Carbon::setTestNow('2026-07-07'); // due date is 2026-07-10 => 3 days before

        $invoice = $this->makeInvoice('2026-07-10');

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        Mail::assertQueued(InvoiceReminderMail::class, fn ($mail) => $mail->stage === 'reminder_h-3');
        Bus::assertNotDispatched(SendWhatsAppMessage::class);
        $this->assertTrue(NotificationLog::alreadySent($invoice, 'email', 'reminder_h-3'));
    }

    public function test_h0_sends_both_email_and_whatsapp(): void
    {
        Mail::fake();
        Bus::fake();
        Carbon::setTestNow('2026-07-10');

        $invoice = $this->makeInvoice('2026-07-10');

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        Mail::assertQueued(InvoiceReminderMail::class, fn ($mail) => $mail->stage === 'reminder_h0');
        Bus::assertDispatched(SendWhatsAppMessage::class);
        $this->assertTrue(NotificationLog::alreadySent($invoice, 'whatsapp', 'reminder_h0'));
    }

    public function test_no_reminder_fires_outside_configured_offsets(): void
    {
        Mail::fake();
        Bus::fake();
        Carbon::setTestNow('2026-07-05'); // 5 days before due date, not a configured offset

        $this->makeInvoice('2026-07-10');

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        Mail::assertNothingQueued();
        Bus::assertNotDispatched(SendWhatsAppMessage::class);
    }

    public function test_repeated_runs_do_not_duplicate_reminders(): void
    {
        Mail::fake();
        Bus::fake();
        Carbon::setTestNow('2026-07-10');

        $this->makeInvoice('2026-07-10');

        $this->artisan('notifications:send-reminders');
        $this->artisan('notifications:send-reminders');

        Mail::assertQueued(InvoiceReminderMail::class, 1);
        Bus::assertDispatched(SendWhatsAppMessage::class, 1);
    }

    public function test_paid_invoices_are_skipped(): void
    {
        Mail::fake();
        Bus::fake();
        Carbon::setTestNow('2026-07-10');

        $invoice = $this->makeInvoice('2026-07-10');
        $invoice->update(['status' => 'paid']);

        $this->artisan('notifications:send-reminders');

        Mail::assertNothingQueued();
        Bus::assertNotDispatched(SendWhatsAppMessage::class);
    }
}
