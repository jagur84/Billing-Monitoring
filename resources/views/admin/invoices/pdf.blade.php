<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: sans-serif; font-size: 12px; color: #1f2937; }
            .header-table { width: 100%; border-bottom: 2px solid #e5e7eb; padding-bottom: 12px; margin-bottom: 16px; }
            .brand-name { font-size: 16px; font-weight: bold; color: #111827; }
            .brand-sub { font-size: 11px; color: #6b7280; }
            .invoice-title { font-size: 20px; font-weight: bold; text-transform: uppercase; color: #111827; text-align: right; }
            .invoice-number { font-size: 11px; color: #4b5563; text-align: right; }
            .section-label { font-size: 10px; text-transform: uppercase; color: #9ca3af; font-weight: bold; letter-spacing: 0.5px; }
            .info-table td { padding: 2px 0; vertical-align: top; }
            .items-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            .items-table th { text-align: left; font-size: 10px; text-transform: uppercase; color: #6b7280; border-bottom: 1px solid #d1d5db; padding: 6px 0; }
            .items-table td { padding: 6px 0; border-bottom: 1px solid #f3f4f6; }
            .text-right { text-align: right; }
            .total-row td { border-top: 2px solid #d1d5db; font-weight: bold; font-size: 13px; padding-top: 8px; }
            .paid-row td { color: #059669; }
            .remaining-row td { color: #dc2626; font-weight: bold; }
            .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
            .badge-paid { background-color: #d1fae5; color: #047857; }
            .badge-partial { background-color: #fef3c7; color: #92400e; }
            .badge-overdue { background-color: #fee2e2; color: #b91c1c; }
            .badge-cancelled { background-color: #f3f4f6; color: #4b5563; }
            .bank-box { margin-top: 20px; padding: 10px; background-color: #f9fafb; border-radius: 4px; font-size: 11px; }
            .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #9ca3af; }
        </style>
    </head>
    <body>
        <table class="header-table">
            <tr>
                <td style="width: 50%;">
                    <div class="brand-name">{{ config('app.name', 'Modal Nekad') }}</div>
                    <div class="brand-sub">Invoice Tagihan Internet</div>
                </td>
                <td style="width: 50%;">
                    <div class="invoice-title">Invoice</div>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                </td>
            </tr>
        </table>

        <table class="info-table" style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <div class="section-label">Ditagihkan Kepada</div>
                    <div><strong>{{ $invoice->customer->name }}</strong></div>
                    <div>{{ $invoice->customer->customer_code }}</div>
                    @if ($invoice->customer->address)
                        <div>{{ $invoice->customer->address }}</div>
                    @endif
                    @if ($invoice->customer->phone)
                        <div>{{ $invoice->customer->phone }}</div>
                    @endif
                </td>
                <td style="width: 50%;" class="text-right">
                    <div>Tanggal Terbit: <strong>{{ $invoice->created_at->translatedFormat('d F Y') }}</strong></div>
                    <div>Jatuh Tempo: <strong>{{ $invoice->due_date->translatedFormat('d F Y') }}</strong></div>
                    <div>Periode: <strong>{{ $invoice->period_month }}/{{ $invoice->period_year }}</strong></div>
                    <div style="margin-top: 4px;">
                        <span class="badge badge-{{ $invoice->status === 'unpaid' ? 'partial' : $invoice->status }}">
                            {{ match($invoice->status) { 'unpaid' => 'Belum Bayar', 'paid' => 'Lunas', 'partial' => 'Cicilan', 'overdue' => 'Jatuh Tempo', default => 'Dibatalkan' } }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Deskripsi</th>
                    <th class="text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $invoice->package_name_snapshot ?? 'Biaya Langganan' }} — Periode {{ $invoice->period_month }}/{{ $invoice->period_year }}</td>
                    <td class="text-right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Pajak</td>
                    <td class="text-right">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td>Diskon</td>
                    <td class="text-right">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
                @if ($invoice->carry_over_amount > 0)
                    <tr>
                        <td>Sisa Tagihan Sebelumnya</td>
                        <td class="text-right">Rp {{ number_format($invoice->carry_over_amount, 0, ',', '.') }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>Total Tagihan</td>
                    <td class="text-right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
                @if ($totalPaid > 0)
                    <tr class="paid-row">
                        <td>Sudah Dibayar</td>
                        <td class="text-right">Rp {{ number_format($totalPaid, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="remaining-row">
                        <td>Sisa Tagihan</td>
                        <td class="text-right">Rp {{ number_format($remaining, 0, ',', '.') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if ($remaining > 0 && $bankAccounts->isNotEmpty())
            <div class="bank-box">
                <strong>Pembayaran Transfer ke:</strong><br>
                @foreach ($bankAccounts as $account)
                    {{ $account->bank_name }}: {{ $account->account_number }} @if($account->account_holder) a.n. {{ $account->account_holder }} @endif<br>
                @endforeach
            </div>
        @endif

        <p class="footer">Terima kasih telah menggunakan layanan {{ config('app.name') }}.</p>
    </body>
</html>
