<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Package;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerDiscountTest extends TestCase
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

    public function test_saving_a_discount_updates_the_customer_and_applies_to_the_next_invoice(): void
    {
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 200000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-D2', 'name' => 'Customer D2', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $response = $this->post(route('customers.discounts.store'), [
            'discount_percent' => [$customer->id => '15'],
            'discount_note' => [$customer->id => 'Diskon pelanggan lama'],
        ]);

        $response->assertRedirect(route('customers.discounts'));
        $customer->refresh();
        $this->assertSame(15.0, (float) $customer->discount_percent);
        $this->assertSame('Diskon pelanggan lama', $customer->discount_note);

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 8, 2026);
        $this->assertSame(30000.0, (float) $invoice->discount_amount);
        $this->assertSame(170000.0, (float) $invoice->total_amount);
    }

    public function test_blank_percent_resets_discount_to_zero(): void
    {
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 200000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-D3', 'name' => 'Customer D3', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active', 'discount_percent' => 20,
        ]);

        $this->post(route('customers.discounts.store'), [
            'discount_percent' => [$customer->id => ''],
        ]);

        $customer->refresh();
        $this->assertSame(0.0, (float) $customer->discount_percent);
    }

    public function test_discounts_page_lists_customers(): void
    {
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 200000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        Customer::create([
            'customer_code' => 'CUST-D4', 'name' => 'Customer D4', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $response = $this->get(route('customers.discounts'));

        $response->assertOk();
        $response->assertSee('Customer D4');
    }
}
