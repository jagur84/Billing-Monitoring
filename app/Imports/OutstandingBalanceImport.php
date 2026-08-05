<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\Billing\InvoiceService;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Migrates a customer's outstanding balance from an old system in as a regular past-period
 * invoice (status unpaid, full amount = the stated remaining balance). It then behaves exactly
 * like any other unresolved invoice: it shows up as arrears, gets marked overdue by the daily
 * job once its due date has passed, and automatically carries into that customer's next
 * generated invoice via InvoiceService::generateForCustomer()'s carry-over logic.
 */
class OutstandingBalanceImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use Importable, SkipsFailures;

    public int $imported = 0;

    public function __construct(private InvoiceService $invoiceService)
    {
    }

    public function model(array $row): Invoice
    {
        $this->imported++;

        $customer = Customer::where('customer_code', trim((string) $row['kode_pelanggan']))->first();
        $month = (int) $row['bulan'];
        $year = (int) $row['tahun'];
        $amount = round((float) $row['sisa_tagihan'], 2);
        $note = $this->blankToNull($row['keterangan'] ?? null);
        $dueDay = min($customer->billing_due_day, Carbon::create($year, $month, 1)->daysInMonth);

        return new Invoice([
            'invoice_number' => $this->invoiceService->generateInvoiceNumber($month, $year),
            'customer_id' => $customer->id,
            'package_id' => $customer->package_id,
            'package_name_snapshot' => $customer->package?->name,
            'period_month' => $month,
            'period_year' => $year,
            'amount' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'carry_over_amount' => 0,
            'total_amount' => $amount,
            'due_date' => Carbon::create($year, $month, $dueDay),
            'status' => 'unpaid',
            'notes' => 'Migrasi saldo sisa dari sistem lama'.($note ? " — {$note}" : ''),
        ]);
    }

    public function rules(): array
    {
        return [
            'kode_pelanggan' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! Customer::where('customer_code', trim($value))->exists()) {
                    $fail('Pelanggan dengan kode "'.$value.'" tidak ditemukan.');
                }
            }],
            'bulan' => ['required', 'integer', 'min:1', 'max:12'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2100'],
            'sisa_tagihan' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'kode_pelanggan' => 'Kode Pelanggan',
            'bulan' => 'Bulan',
            'tahun' => 'Tahun',
            'sisa_tagihan' => 'Sisa Tagihan',
        ];
    }

    /**
     * Cross-field check (customer + period together) that rules() can't express on its own,
     * since the invoices table enforces one invoice per customer per period. The validator
     * here covers every row in the batch at once (Laravel Excel validates rows collectively
     * via wildcard rules), keyed by actual spreadsheet row number, so errors must be added
     * against "{row}.kode_pelanggan" to land on the correct row's failure.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($validator->getData() as $rowIndex => $row) {
                if (empty($row['kode_pelanggan']) || empty($row['bulan']) || empty($row['tahun'])) {
                    continue;
                }

                $customer = Customer::where('customer_code', trim((string) $row['kode_pelanggan']))->first();

                if (! $customer) {
                    continue;
                }

                $exists = Invoice::where('customer_id', $customer->id)
                    ->where('period_month', (int) $row['bulan'])
                    ->where('period_year', (int) $row['tahun'])
                    ->exists();

                if ($exists) {
                    $validator->errors()->add("{$rowIndex}.kode_pelanggan", 'Tagihan periode ini untuk pelanggan tersebut sudah ada.');
                }
            }
        });
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
