<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceOutstandingBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function actingAsSuperAdmin(): User
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'super-admin']);
        $user->assignRole('super-admin');

        $this->actingAs($user);

        return $user;
    }

    public function test_entered_amounts_create_unpaid_invoices_and_zero_or_blank_rows_are_skipped(): void
    {
        Carbon::setTestNow('2026-08-05');
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 130000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $a = Customer::create([
            'customer_code' => 'CUST-O1', 'name' => 'Customer O1', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);
        $b = Customer::create([
            'customer_code' => 'CUST-O2', 'name' => 'Customer O2', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $response = $this->post(route('invoices.outstanding-balance.store'), [
            'month' => 7,
            'year' => 2026,
            'amounts' => [
                $a->id => '30000',
                $b->id => '0',
            ],
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Invoice::count());
        $this->assertTrue(Invoice::where('customer_id', $a->id)->where('period_month', 7)->exists());
        $this->assertFalse(Invoice::where('customer_id', $b->id)->exists());
    }

    public function test_resubmitting_the_same_customer_and_period_does_not_create_a_duplicate(): void
    {
        Carbon::setTestNow('2026-08-05');
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 130000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-O5', 'name' => 'Customer O5', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $this->post(route('invoices.outstanding-balance.store'), [
            'month' => 7, 'year' => 2026, 'amounts' => [$customer->id => '30000'],
        ]);

        // Admin accidentally submits the same period for the same customer a second time
        // (e.g. double form submit, or re-entering data already saved earlier).
        $response = $this->post(route('invoices.outstanding-balance.store'), [
            'month' => 7, 'year' => 2026, 'amounts' => [$customer->id => '30000'],
        ]);

        $response->assertRedirect();
        $this->assertSame(1, Invoice::where('customer_id', $customer->id)->where('period_month', 7)->count());
        $this->assertSame(30000.0, (float) Invoice::where('customer_id', $customer->id)->first()->total_amount);
    }

    public function test_rejects_current_or_future_period(): void
    {
        Carbon::setTestNow('2026-08-05');
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 130000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-O3', 'name' => 'Customer O3', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $response = $this->post(route('invoices.outstanding-balance.store'), [
            'month' => 8,
            'year' => 2026,
            'amounts' => [$customer->id => '30000'],
        ]);

        $response->assertSessionHas('error');
        $this->assertSame(0, Invoice::count());
    }

    public function test_page_renders_customer_grid_once_a_period_is_selected(): void
    {
        Carbon::setTestNow('2026-08-05');
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 130000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        Customer::create([
            'customer_code' => 'CUST-O4', 'name' => 'Customer O4', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $response = $this->get(route('invoices.outstanding-balance', ['period' => '7-2026']));

        $response->assertOk();
        $response->assertSee('Customer O4');
    }
}
