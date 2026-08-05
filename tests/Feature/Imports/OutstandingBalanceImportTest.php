<?php

namespace Tests\Feature\Imports;

use App\Imports\OutstandingBalanceImport;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Package;
use App\Services\Billing\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class OutstandingBalanceImportTest extends TestCase
{
    use RefreshDatabase;

    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function spreadsheetPath(array $rows): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $i => $row) {
            $sheet->fromArray($row, null, 'A'.($i + 1));
        }

        $path = storage_path('framework/testing/outstanding-'.uniqid().'.xlsx');
        (new Xlsx($spreadsheet))->save($path);
        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_it_imports_a_valid_row_as_an_unpaid_invoice(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-M1', 'name' => 'Migrasi Satu', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        $path = $this->spreadsheetPath([
            ['Kode Pelanggan', 'Bulan', 'Tahun', 'Sisa Tagihan', 'Keterangan'],
            ['CUST-M1', 7, 2026, 30000, 'Sisa dari sistem lama'],
        ]);

        $import = new OutstandingBalanceImport(app(InvoiceService::class));
        Excel::import($import, $path);

        $this->assertSame(1, $import->imported);
        $this->assertTrue($import->failures()->isEmpty());

        $invoice = Invoice::where('customer_id', $customer->id)->first();
        $this->assertNotNull($invoice);
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame(30000.0, (float) $invoice->total_amount);
        $this->assertSame(7, $invoice->period_month);
        $this->assertSame(2026, $invoice->period_year);
        $this->assertStringContainsString('Migrasi saldo sisa', $invoice->notes);
    }

    public function test_it_fails_a_row_with_unknown_customer_code(): void
    {
        $path = $this->spreadsheetPath([
            ['Kode Pelanggan', 'Bulan', 'Tahun', 'Sisa Tagihan', 'Keterangan'],
            ['CUST-TIDAK-ADA', 7, 2026, 30000, ''],
        ]);

        $import = new OutstandingBalanceImport(app(InvoiceService::class));
        Excel::import($import, $path);

        $this->assertSame(0, $import->imported);
        $this->assertSame(0, Invoice::count());
        $this->assertFalse($import->failures()->isEmpty());
    }

    public function test_it_fails_a_row_that_duplicates_an_existing_period_for_the_customer(): void
    {
        $package = Package::create([
            'name' => 'Home 10 Mbps', 'speed_mbps' => 10, 'price' => 150000, 'tax_percent' => 0, 'is_active' => true,
        ]);
        $customer = Customer::create([
            'customer_code' => 'CUST-M2', 'name' => 'Migrasi Dua', 'package_id' => $package->id,
            'billing_due_day' => 10, 'status' => 'active',
        ]);

        app(InvoiceService::class)->generateForCustomer($customer, 7, 2026);

        $path = $this->spreadsheetPath([
            ['Kode Pelanggan', 'Bulan', 'Tahun', 'Sisa Tagihan', 'Keterangan'],
            ['CUST-M2', 7, 2026, 30000, ''],
        ]);

        $import = new OutstandingBalanceImport(app(InvoiceService::class));
        Excel::import($import, $path);

        $this->assertSame(0, $import->imported);
        $this->assertSame(1, Invoice::count()); // only the pre-existing one
        $this->assertFalse($import->failures()->isEmpty());
    }
}
