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
            'phone' => trim((string) $row['telepon']),
            'nik' => $this->blankToNull($row['nik'] ?? null),
            'address' => $this->blankToNull($row['alamat'] ?? null),
            'package_id' => $this->resolvePackageId($row['paket']),
            'pppoe_username' => trim((string) $row['username_pppoe']),
            'pppoe_password' => trim((string) $row['password_pppoe']),
            'installation_date' => $this->parseDate($row['tanggal_instalasi'] ?? null),
            'billing_due_day' => (int) $row['jatuh_tempo'],
            'status' => $this->resolveStatus($row['status'] ?? null),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telepon' => ['required', 'string', 'max:30'],
            'nik' => ['nullable', 'string', 'max:30'],
            'paket' => ['required', 'string', function ($attribute, $value, $fail) {
                if (! Package::whereRaw('LOWER(name) = ?', [Str::lower(trim($value))])->exists()) {
                    $fail('Paket "'.$value.'" tidak ditemukan.');
                }
            }],
            'username_pppoe' => ['required', 'string', 'max:255'],
            'password_pppoe' => ['required', 'string', 'max:255'],
            'jatuh_tempo' => ['required', 'integer', 'min:1', 'max:28'],
        ];
    }

    public function customValidationAttributes(): array
    {
        return [
            'nama' => 'Nama',
            'email' => 'Email',
            'telepon' => 'Telepon',
            'nik' => 'NIK',
            'paket' => 'Paket',
            'username_pppoe' => 'Username PPPoE',
            'password_pppoe' => 'Password PPPoE',
            'jatuh_tempo' => 'Jatuh Tempo',
        ];
    }

    private function blankToNull(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolvePackageId(string $value): ?int
    {
        return Package::whereRaw('LOWER(name) = ?', [Str::lower(trim($value))])->value('id');
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
