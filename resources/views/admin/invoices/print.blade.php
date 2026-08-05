<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Invoice {{ $invoice->invoice_number }} - {{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>
            @media print {
                .no-print { display: none !important; }
                body { padding: 0 !important; }
            }
        </style>
    </head>
    <body class="bg-gray-100 font-sans text-gray-900 antialiased">
        <div class="no-print flex justify-center gap-3 bg-white py-4 shadow-sm">
            <button type="button" onclick="window.print()" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                Cetak
            </button>
            <button type="button" onclick="window.close()" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Tutup
            </button>
        </div>

        <div class="mx-auto max-w-2xl bg-white p-8 print:p-0">
            <div class="flex items-start justify-between border-b border-gray-200 pb-6">
                <div class="flex items-center gap-3">
                    @if ($logoPath = \App\Models\Setting::get('app_logo_path'))
                        <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ config('app.name') }}" class="h-12 w-12 rounded-lg object-contain">
                    @else
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-600 text-xl font-bold text-white">{{ substr(config('app.name', 'A'), 0, 1) }}</div>
                    @endif
                    <div>
                        <p class="text-lg font-semibold text-gray-900">{{ config('app.name', 'Modal Nekad') }}</p>
                        <p class="text-sm text-gray-500">Invoice Tagihan Internet</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold uppercase tracking-wide text-gray-900">Invoice</p>
                    <p class="mt-1 font-mono text-sm text-gray-600">{{ $invoice->invoice_number }}</p>
                </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-6 text-sm">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Ditagihkan Kepada</p>
                    <p class="mt-1 font-medium text-gray-900">{{ $invoice->customer->name }}</p>
                    <p class="text-gray-600">{{ $invoice->customer->customer_code }}</p>
                    @if ($invoice->customer->address)
                        <p class="text-gray-600">{{ $invoice->customer->address }}</p>
                    @endif
                    @if ($invoice->customer->phone)
                        <p class="text-gray-600">{{ $invoice->customer->phone }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <p><span class="text-gray-500">Tanggal Terbit:</span> <span class="font-medium text-gray-900">{{ $invoice->created_at->translatedFormat('d F Y') }}</span></p>
                    <p><span class="text-gray-500">Jatuh Tempo:</span> <span class="font-medium text-gray-900">{{ $invoice->due_date->translatedFormat('d F Y') }}</span></p>
                    <p><span class="text-gray-500">Periode:</span> <span class="font-medium text-gray-900">{{ $invoice->period_month }}/{{ $invoice->period_year }}</span></p>
                    <p class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                            @class([
                                'bg-emerald-100 text-emerald-700' => $invoice->status === 'paid',
                                'bg-amber-100 text-amber-700' => in_array($invoice->status, ['unpaid', 'partial']),
                                'bg-rose-100 text-rose-700' => $invoice->status === 'overdue',
                                'bg-gray-100 text-gray-600' => $invoice->status === 'cancelled',
                            ])">
                            {{ match($invoice->status) { 'unpaid' => 'Belum Bayar', 'paid' => 'Lunas', 'partial' => 'Cicilan', 'overdue' => 'Jatuh Tempo', default => 'Dibatalkan' } }}
                        </span>
                    </p>
                </div>
            </div>

            <table class="mt-8 w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="py-2">Deskripsi</th>
                        <th class="py-2 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr>
                        <td class="py-2 text-gray-900">{{ $invoice->package_name_snapshot ?? 'Biaya Langganan' }} — Periode {{ $invoice->period_month }}/{{ $invoice->period_year }}</td>
                        <td class="py-2 text-right text-gray-900">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Pajak</td>
                        <td class="py-2 text-right text-gray-600">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="py-2 text-gray-600">Diskon</td>
                        <td class="py-2 text-right text-gray-600">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if ($invoice->carry_over_amount > 0)
                        <tr>
                            <td class="py-2 text-gray-600" title="{{ $invoice->carry_over_note }}">Sisa Tagihan Sebelumnya</td>
                            <td class="py-2 text-right text-gray-600">Rp {{ number_format($invoice->carry_over_amount, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-300">
                        <td class="py-2 font-semibold text-gray-900">Total Tagihan</td>
                        <td class="py-2 text-right font-semibold text-gray-900">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                    </tr>
                    @if ($totalPaid > 0)
                        <tr>
                            <td class="py-1 text-emerald-600">Sudah Dibayar</td>
                            <td class="py-1 text-right text-emerald-600">Rp {{ number_format($totalPaid, 0, ',', '.') }}</td>
                        </tr>
                        @if ($invoice->carry_over_amount > 0 && $remainingBreakdown['old'] > 0)
                            <tr>
                                <td class="py-1 text-rose-600">Sisa Tagihan Lama</td>
                                <td class="py-1 text-right text-rose-600">Rp {{ number_format($remainingBreakdown['old'], 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="py-1 font-semibold text-rose-600">Sisa Tagihan Bulan Ini</td>
                                <td class="py-1 text-right font-semibold text-rose-600">Rp {{ number_format($remainingBreakdown['current'], 0, ',', '.') }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="py-1 font-semibold text-rose-600">Sisa Tagihan</td>
                                <td class="py-1 text-right font-semibold text-rose-600">Rp {{ number_format($remaining, 0, ',', '.') }}</td>
                            </tr>
                        @endif
                    @endif
                </tfoot>
            </table>

            @if ($invoice->payments->isNotEmpty())
                <div class="mt-8">
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Riwayat Pembayaran</p>
                    <table class="mt-2 w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="py-2">Tanggal</th>
                                <th class="py-2">Metode</th>
                                <th class="py-2 text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($invoice->payments as $payment)
                                <tr>
                                    <td class="py-2 text-gray-600">{{ optional($payment->paid_at)->translatedFormat('d F Y') }}</td>
                                    <td class="py-2 text-gray-600">
                                        {{ match(true) {
                                            $payment->gateway === 'manual' && $payment->gateway_payment_method === 'cash' => 'Tunai',
                                            $payment->gateway === 'manual' && $payment->gateway_payment_method === 'transfer' => 'Transfer'.($payment->bankAccount ? " ({$payment->bankAccount->bank_name})" : ''),
                                            default => ucfirst($payment->gateway),
                                        } }}
                                    </td>
                                    <td class="py-2 text-right text-gray-900">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($remaining > 0 && $bankAccounts->isNotEmpty())
                <div class="mt-8 rounded-lg bg-gray-50 p-4 text-sm">
                    <p class="mb-2 font-semibold text-gray-900">Pembayaran Transfer ke:</p>
                    <ul class="space-y-1 text-gray-600">
                        @foreach ($bankAccounts as $account)
                            <li>{{ $account->bank_name }}: <span class="font-mono">{{ $account->account_number }}</span> @if($account->account_holder) a.n. {{ $account->account_holder }} @endif</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="mt-10 text-center text-xs text-gray-400">Terima kasih telah menggunakan layanan {{ config('app.name') }}.</p>
        </div>
    </body>
</html>
