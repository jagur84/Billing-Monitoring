<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\MikrotikRouter;
use App\Models\Package;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Billing\InvoiceService;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $packages = collect([
            ['name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 11],
            ['name' => 'Home 20 Mbps', 'speed_mbps' => 20, 'price' => 250000, 'tax_percent' => 11],
            ['name' => 'Business 50 Mbps', 'speed_mbps' => 50, 'price' => 500000, 'tax_percent' => 11],
        ])->map(fn ($data) => Package::create($data));

        $router = MikrotikRouter::create([
            'name' => 'Router Pusat',
            'host' => '192.168.88.1',
            'port' => 8728,
            'username' => 'admin',
            'password' => 'changeme',
            'use_ssl' => false,
            'is_active' => true,
        ]);

        $technician = User::factory()->create([
            'name' => 'Teknisi Lapangan',
            'email' => 'teknisi@sebilling.test',
        ]);
        $technician->assignRole('technician');

        $invoiceService = app(InvoiceService::class);

        $customers = collect([
            ['name' => 'Budi Santoso', 'phone' => '081234567890', 'status' => 'active', 'pppoe' => 'budi.santoso'],
            ['name' => 'Siti Aminah', 'phone' => '081234567891', 'status' => 'active', 'pppoe' => 'siti.aminah'],
            ['name' => 'Andi Wijaya', 'phone' => '081234567892', 'status' => 'isolated', 'pppoe' => 'andi.wijaya'],
            ['name' => 'Dewi Lestari', 'phone' => '081234567893', 'status' => 'active', 'pppoe' => 'dewi.lestari'],
        ])->map(function ($data, $index) use ($packages, $invoiceService, $router) {
            $customer = Customer::create([
                'customer_code' => 'CUST-'.str_pad($index + 1, 5, '0', STR_PAD_LEFT),
                'name' => $data['name'],
                'phone' => $data['phone'],
                'package_id' => $packages[$index % $packages->count()]->id,
                'pppoe_username' => $data['pppoe'],
                'router_id' => $router->id,
                'billing_due_day' => 10,
                'installation_date' => now()->subMonths(3),
                'status' => $data['status'],
            ]);

            $invoiceService->generateForCustomer($customer, now()->subMonth()->month, now()->subMonth()->year);
            $invoiceService->generateForCustomer($customer, now()->month, now()->year);

            return $customer;
        });

        Ticket::create([
            'ticket_number' => 'TKT-DEMO01',
            'customer_id' => $customers[2]->id,
            'subject' => 'Koneksi internet terputus-putus',
            'description' => 'Pelanggan melaporkan koneksi sering putus sejak kemarin malam.',
            'category' => 'connectivity',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $technician->id,
        ])->logs()->create([
            'user_id' => $technician->id,
            'status' => 'open',
            'note' => 'Tiket dibuat dari laporan pelanggan.',
        ]);

        collect([
            ['sku' => 'ONT-HG8145', 'name' => 'ONT Huawei HG8145', 'category' => 'ont', 'unit' => 'pcs', 'stock_qty' => 15, 'min_stock' => 5],
            ['sku' => 'CBL-FO-1M', 'name' => 'Kabel Fiber Optik Dropcore 1 Core', 'category' => 'cable', 'unit' => 'meter', 'stock_qty' => 500, 'min_stock' => 100],
            ['sku' => 'RTR-AC750', 'name' => 'Router Wireless AC750', 'category' => 'router', 'unit' => 'pcs', 'stock_qty' => 3, 'min_stock' => 5],
        ])->each(fn ($item) => InventoryItem::create($item));
    }
}
