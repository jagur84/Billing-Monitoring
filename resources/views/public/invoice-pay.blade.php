<!DOCTYPE html>
<html lang="id" class="h-full bg-gray-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Tagihan {{ $invoice->invoice_number }} - {{ config('app.name') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full font-sans antialiased text-gray-900">
        <div class="flex min-h-full flex-col items-center justify-center px-4 py-12">
            <div class="mb-6 flex items-center gap-2">
                @if ($logoPath = \App\Models\Setting::get('app_logo_path'))
                    <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ config('app.name') }}" class="h-9 w-9 rounded-lg object-contain">
                @else
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold">{{ substr(config('app.name', 'A'), 0, 1) }}</div>
                @endif
                <span class="text-lg font-semibold text-gray-900">{{ config('app.name', 'Modal Nekad') }}</span>
            </div>

            <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">No. Invoice</p>
                        <p class="font-mono text-sm font-medium text-gray-900">{{ $invoice->invoice_number }}</p>
                    </div>
                    <x-badge :color="match($invoice->status) { 'paid' => 'green', 'overdue' => 'red', 'cancelled' => 'gray', default => 'amber' }">
                        {{ match($invoice->status) { 'unpaid' => 'Belum Bayar', 'paid' => 'Lunas', 'overdue' => 'Jatuh Tempo', default => 'Dibatalkan' } }}
                    </x-badge>
                </div>

                <dl class="space-y-2 border-t border-gray-100 pt-4 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Pelanggan</dt><dd class="font-medium text-gray-900">{{ $invoice->customer->name }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Kode Pelanggan</dt><dd class="text-gray-900">{{ $invoice->customer->customer_code }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Periode</dt><dd class="text-gray-900">{{ $invoice->period_month }}/{{ $invoice->period_year }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Jatuh Tempo</dt><dd class="text-gray-900">{{ $invoice->due_date->translatedFormat('d F Y') }}</dd></div>
                </dl>

                <div class="mt-4 rounded-lg bg-gray-50 p-4 text-center">
                    <p class="text-sm text-gray-500">Total Tagihan</p>
                    <p class="text-2xl font-semibold text-gray-900">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</p>
                </div>

                @if ($invoice->status === 'paid')
                    <div class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-center text-sm font-medium text-emerald-700">
                        Tagihan ini sudah lunas. Terima kasih!
                    </div>
                @elseif ($invoice->status === 'cancelled')
                    <div class="mt-6 rounded-lg bg-gray-100 px-4 py-3 text-center text-sm font-medium text-gray-600">
                        Tagihan ini telah dibatalkan.
                    </div>
                @else
                    <a href="{{ URL::signedRoute('public.invoice.checkout', ['invoice' => $invoice->id]) }}"
                       class="mt-6 block w-full rounded-md bg-indigo-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Bayar Sekarang via Tripay
                    </a>
                @endif
            </div>

            <p class="mt-6 text-xs text-gray-400">Hubungi kami jika ada pertanyaan mengenai tagihan ini.</p>
        </div>
    </body>
</html>
