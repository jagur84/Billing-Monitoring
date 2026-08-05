<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Package;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceRemainingBreakdownTest extends TestCase
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

    public function test_show_page_splits_old_and_current_remaining_once_a_partial_payment_is_made(): void
    {
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-RB1', 'name' => 'Customer RB1', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $service = app(InvoiceService::class);
        $service->generateForCustomer($customer, 7, 2026);
        $august = $service->generateForCustomer($customer, 8, 2026);
        $service->recordManualPayment($august, 50000, null);

        $response = $this->get(route('invoices.show', $august));

        $response->assertOk();
        $response->assertSee('Sisa Tagihan Lama');
        $response->assertSee('Sisa Tagihan Bulan Ini');
    }

    public function test_show_page_uses_a_single_remaining_line_when_there_is_no_carry_over(): void
    {
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-RB2', 'name' => 'Customer RB2', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $service = app(InvoiceService::class);
        $invoice = $service->generateForCustomer($customer, 8, 2026);
        $service->recordManualPayment($invoice, 50000, null);

        $response = $this->get(route('invoices.show', $invoice));

        $response->assertOk();
        $response->assertDontSee('Sisa Tagihan Lama');
    }
}
