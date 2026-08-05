<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OutstandingBalanceImportTemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'CUST-ABC123',
                7,
                2026,
                30000,
                'Sisa tagihan dari sistem lama',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Kode Pelanggan',
            'Bulan',
            'Tahun',
            'Sisa Tagihan',
            'Keterangan',
        ];
    }
}
