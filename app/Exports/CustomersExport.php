<?php

namespace App\Exports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomersExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $customers)
    {
    }

    public function collection(): Collection
    {
        return $this->customers;
    }

    public function headings(): array
    {
        return [
            'Kode',
            'Nama',
            'Email',
            'Telepon',
            'NIK',
            'Alamat',
            'Paket',
            'PPPoE Username',
            'Tanggal Instalasi',
            'Tanggal Jatuh Tempo (per bulan)',
            'Status',
        ];
    }

    /**
     * @param  Customer  $customer
     */
    public function map($customer): array
    {
        return [
            $customer->customer_code,
            $customer->name,
            $customer->email,
            $customer->phone,
            $customer->nik,
            $customer->address,
            $customer->package?->name ?? '-',
            $customer->pppoe_username,
            optional($customer->installation_date)->format('d-m-Y'),
            $customer->billing_due_day,
            match ($customer->status) {
                'active' => 'Aktif',
                'isolated' => 'Terisolir',
                default => 'Nonaktif',
            },
        ];
    }
}
