<?php

namespace App\Exports;

use App\Models\Invoice;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RevenueReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $invoices)
    {
    }

    public function collection(): Collection
    {
        return $this->invoices;
    }

    public function headings(): array
    {
        return [
            'Tanggal Bayar',
            'No. Invoice',
            'Pelanggan',
            'Periode Tagihan',
            'Jumlah',
        ];
    }

    /**
     * @param  Invoice  $invoice
     */
    public function map($invoice): array
    {
        return [
            optional($invoice->paid_at)->format('d-m-Y H:i'),
            $invoice->invoice_number,
            $invoice->customer?->name ?? '-',
            "{$invoice->period_month}/{$invoice->period_year}",
            (float) $invoice->total_amount,
        ];
    }
}
