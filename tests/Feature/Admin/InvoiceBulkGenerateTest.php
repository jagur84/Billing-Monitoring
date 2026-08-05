<?php

namespace Tests\Feature\Admin;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceBulkGenerateTest extends TestCase
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

    public function test_select_all_generates_invoices_for_every_eligible_customer(): void
    {
        Mail::fake();
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $a = Customer::create([
            'customer_code' => 'CUST-B1', 'name' => 'Customer A', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);
        $b = Customer::create([
            'customer_code' => 'CUST-B2', 'name' => 'Customer B', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $response = $this->post(route('invoices.bulk-store'), ['select_all' => '1']);

        $response->assertRedirect(route('invoices.index'));
        $this->assertSame(2, Invoice::count());
        $this->assertTrue(Invoice::where('customer_id', $a->id)->exists());
        $this->assertTrue(Invoice::where('customer_id', $b->id)->exists());
    }

    public function test_specific_customer_ids_only_generate_for_those_customers(): void
    {
        Mail::fake();
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $a = Customer::create([
            'customer_code' => 'CUST-B3', 'name' => 'Customer C', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);
        $b = Customer::create([
            'customer_code' => 'CUST-B4', 'name' => 'Customer D', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $this->post(route('invoices.bulk-store'), ['customer_ids' => [$a->id]]);

        $this->assertSame(1, Invoice::count());
        $this->assertTrue(Invoice::where('customer_id', $a->id)->exists());
        $this->assertFalse(Invoice::where('customer_id', $b->id)->exists());
    }

    public function test_customer_already_invoiced_this_month_is_skipped_without_duplicating(): void
    {
        Mail::fake();
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-B5', 'name' => 'Customer E', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        app(\App\Services\Billing\InvoiceService::class)->generateForCustomer($customer, now()->month, now()->year);

        $this->post(route('invoices.bulk-store'), ['select_all' => '1']);

        $this->assertSame(1, Invoice::count());
    }

    public function test_one_customer_failing_does_not_abort_the_rest_of_the_batch(): void
    {
        Mail::fake();
        $this->actingAsSuperAdmin();

        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $a = Customer::create([
            'customer_code' => 'CUST-B6', 'name' => 'Customer F', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);
        $b = Customer::create([
            'customer_code' => 'CUST-B7', 'name' => 'Customer G (will fail)', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);
        $c = Customer::create([
            'customer_code' => 'CUST-B8', 'name' => 'Customer H', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $realService = app(InvoiceService::class);

        $this->mock(InvoiceService::class, function ($mock) use ($b, $realService) {
            $mock->shouldReceive('generateAndNotify')
                ->andReturnUsing(function ($customer, $month, $year) use ($b, $realService) {
                    if ($customer->id === $b->id) {
                        throw new \RuntimeException('simulated failure for customer '.$customer->id);
                    }

                    return $realService->generateAndNotify($customer, $month, $year);
                });
        });

        $response = $this->post(route('invoices.bulk-store'), ['select_all' => '1']);

        $response->assertRedirect(route('invoices.index'));
        $response->assertSessionHas('status', fn ($status) => str_contains($status, 'gagal'));

        $this->assertTrue(Invoice::where('customer_id', $a->id)->exists());
        $this->assertFalse(Invoice::where('customer_id', $b->id)->exists());
        $this->assertTrue(Invoice::where('customer_id', $c->id)->exists());
    }
}
