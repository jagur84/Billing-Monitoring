<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceEditDeleteTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'super-admin']);
        $user->assignRole('super-admin');

        $this->actingAs($user);

        return $user;
    }

    private function makeInvoice(string $code, string $status = 'unpaid'): Invoice
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => $code, 'name' => 'Customer '.$code, 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 8, 2026);

        if ($status !== 'unpaid') {
            $invoice->update(['status' => $status]);
        }

        return $invoice;
    }

    public function test_unpaid_invoice_can_be_edited_and_carry_over_is_preserved_in_new_total(): void
    {
        $this->actingAsSuperAdmin();
        $invoice = $this->makeInvoice('CUST-E1');
        $invoice->update(['carry_over_amount' => 20000, 'total_amount' => 170000]);

        $response = $this->put(route('invoices.update', $invoice), [
            'amount' => 160000,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'due_date' => $invoice->due_date->format('Y-m-d'),
            'notes' => 'updated',
        ]);

        $response->assertRedirect(route('invoices.show', $invoice));
        $invoice->refresh();
        $this->assertSame(180000.0, (float) $invoice->total_amount); // 160000 + 20000 carry-over preserved
    }

    public function test_partial_invoice_can_still_be_edited(): void
    {
        $this->actingAsSuperAdmin();
        $invoice = $this->makeInvoice('CUST-E2', 'partial');

        $response = $this->get(route('invoices.edit', $invoice));

        $response->assertOk();
    }

    public function test_paid_invoice_cannot_be_edited(): void
    {
        $this->actingAsSuperAdmin();
        $invoice = $this->makeInvoice('CUST-E3', 'paid');

        $this->get(route('invoices.edit', $invoice))->assertForbidden();
        $this->put(route('invoices.update', $invoice), [
            'amount' => 1, 'tax_amount' => 0, 'discount_amount' => 0,
            'due_date' => $invoice->due_date->format('Y-m-d'),
        ])->assertForbidden();
    }

    public function test_cancelled_invoice_cannot_be_edited(): void
    {
        $this->actingAsSuperAdmin();
        $invoice = $this->makeInvoice('CUST-E4', 'cancelled');

        $this->get(route('invoices.edit', $invoice))->assertForbidden();
    }

    public function test_invoice_without_payments_can_be_deleted(): void
    {
        $this->actingAsSuperAdmin();
        $invoice = $this->makeInvoice('CUST-E5');

        $response = $this->delete(route('invoices.destroy', $invoice));

        $response->assertRedirect(route('invoices.index'));
        $this->assertNull(Invoice::find($invoice->id));
    }

    public function test_invoice_with_a_recorded_payment_cannot_be_deleted(): void
    {
        $this->actingAsSuperAdmin();
        $invoice = $this->makeInvoice('CUST-E6');
        app(InvoiceService::class)->recordManualPayment($invoice, 50000, null);

        $response = $this->delete(route('invoices.destroy', $invoice));

        $response->assertForbidden();
        $this->assertNotNull(Invoice::find($invoice->id));
    }
}
