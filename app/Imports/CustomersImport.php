<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Package;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CustomersImport implements SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use Importable, SkipsFailures;

    public int $imported = 0;

    public function model(array $row): Customer
    {
        $this->imported++;

        return new Customer([
            'customer_code' => Customer::generateCode(),
            'name' => trim((string) $row['nama']),
            'email' => $this->blankToNull($row['email'] ?? null),
            'phone' => $this->blankToNull($row['telepon'] ?? null),
            'nik' => $this->blankToNull($row['nik'] ?? null),
            'address' => $this->blankToNull($row['alamat'] ?? null),
            'package_id' => $this->resolvePackageId($row['paket'] ?? null),
            'pppoe_username' => $this->blankToNull($row['username_pppoe'] ?? null),
            'installation_date' => $this->parseDate($row['tanggal_instalasi'] ?? null),
            'billing_due_day' => $this->resolveBillingDueDay($row['jatuh_tempo'] ?? null),
            'status' => $this->resolveStatus($row['status'] ?? null),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:30'],
            'nik' => ['nullable', 'string', 'max:30'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'nama' => 'Nama',
            'email' => 'Email',
            'telepon' => 'Telepon',
            'nik' => 'NIK',
        ];
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolvePackageId(mixed $value): ?int
    {
        $name = $this->blankToNull($value);

        if ($name === null) {
            return null;
        }

        return Package::whereRaw('LOWER(name) = ?', [Str::lower($name)])->value('id');
    }

    private function resolveBillingDueDay(mixed $value): int
    {
        $day = (int) $value;

        return $day >= 1 && $day <= 28 ? $day : 10;
    }

    private function resolveStatus(mixed $value): string
    {
        return match (Str::lower(trim((string) $value))) {
            'terisolir', 'isolated' => 'isolated',
            'nonaktif', 'inactive' => 'inactive',
            default => 'active',
        };
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['d-m-Y', 'd/m/Y', 'Y-m-d'] as $format) {
            try {
                return Carbon::createFromFormat($format, trim((string) $value))->format('Y-m-d');
            } catch (\Throwable) {
                continue;
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }
}
