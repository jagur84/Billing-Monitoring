<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerImportTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'Budi Santoso',
                'budi@contoh.com',
                '081234567890',
                '3201xxxxxxxxxxxx',
                'Jl. Contoh No. 1, Jakarta',
                'Home 10 Mbps',
                'budi.santoso',
                '15-01-2026',
                10,
                'Aktif',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Nama',
            'Email',
            'Telepon',
            'NIK',
            'Alamat',
            'Paket',
            'Username PPPoE',
            'Tanggal Instalasi',
            'Jatuh Tempo',
            'Status',
        ];
    }
}
