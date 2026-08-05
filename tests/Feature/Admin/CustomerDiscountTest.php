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

    public function test_an_inactive_customer_submitted_outside_the_grid_is_not_updated(): void
    {
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 200000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-D9', 'name' => 'Inactive Customer', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'inactive',
        ]);

        // Simulates a tampered submission targeting a customer id the grid never rendered
        // (the grid only lists status != inactive) rather than a real UI interaction.
        $this->post(route('customers.discounts.store'), [
            'discount_type' => [$customer->id => 'percent'],
            'discount_percent' => [$customer->id => '50'],
        ]);

        $customer->refresh();
        $this->assertSame(0.0, (float) $customer->discount_percent);
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
            'discount_type' => [$customer->id => 'percent'],
            'discount_percent' => [$customer->id => '15'],
            'discount_note' => [$customer->id => 'Diskon pelanggan lama'],
            'discount_valid_until' => [$customer->id => '2026-12-31'],
        ]);

        $response->assertRedirect(route('customers.discounts'));
        $customer->refresh();
        $this->assertSame('percent', $customer->discount_type);
        $this->assertSame(15.0, (float) $customer->discount_percent);
        $this->assertSame('Diskon pelanggan lama', $customer->discount_note);
        $this->assertSame('2026-12-31', $customer->discount_valid_until->format('Y-m-d'));

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 8, 2026);
        $this->assertSame(30000.0, (float) $invoice->discount_amount);
        $this->assertSame(170000.0, (float) $invoice->total_amount);
    }

    public function test_blank_valid_until_means_no_expiry(): void
    {
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 200000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-D8B', 'name' => 'Customer D8B', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active', 'discount_valid_until' => '2026-01-01',
        ]);

        $this->post(route('customers.discounts.store'), [
            'discount_type' => [$customer->id => 'percent'],
            'discount_percent' => [$customer->id => '10'],
            'discount_valid_until' => [$customer->id => ''],
        ]);

        $customer->refresh();
        $this->assertNull($customer->discount_valid_until);
    }

    public function test_nominal_discount_type_deducts_a_fixed_rupiah_amount(): void
    {
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 200000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-D5', 'name' => 'Customer D5', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $this->post(route('customers.discounts.store'), [
            'discount_type' => [$customer->id => 'nominal'],
            'discount_nominal' => [$customer->id => '25000'],
        ]);

        $customer->refresh();
        $this->assertSame('nominal', $customer->discount_type);
        $this->assertSame(25000.0, (float) $customer->discount_nominal);

        $invoice = app(InvoiceService::class)->generateForCustomer($customer, 8, 2026);
        $this->assertSame(25000.0, (float) $invoice->discount_amount);
        $this->assertSame(175000.0, (float) $invoice->total_amount);
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
            'discount_type' => [$customer->id => 'percent'],
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
