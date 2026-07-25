<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Laporan Penghasilan" subtitle="Ringkasan penghasilan berdasarkan periode pembayaran.">
            <x-slot name="actions">
                <a href="{{ route('reports.revenue.export', request()->query()) }}">
                    <x-secondary-button type="button">Export Excel</x-secondary-button>
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-6">
        <x-panel title="Filter Periode">
            <form action="{{ route('reports.revenue') }}" method="GET" class="flex flex-wrap items-end gap-4">
                <div>
                    <x-input-label for="start_period" value="Dari Bulan" />
                    <x-text-input id="start_period" name="start_period" type="month" class="mt-1 block" value="{{ $startPeriod }}" required />
                </div>
                <div>
                    <x-input-label for="end_period" value="Sampai Bulan" />
                    <x-text-input id="end_period" name="end_period" type="month" class="mt-1 block" value="{{ $endPeriod }}" required />
                </div>
                <x-primary-button type="submit">Tampilkan</x-primary-button>
            </form>
        </x-panel>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <x-panel title="Total Penghasilan">
                <p class="text-2xl font-semibold text-gray-900">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
            </x-panel>
            <x-panel title="Jumlah Tagihan Lunas">
                <p class="text-2xl font-semibold text-gray-900">{{ $totalInvoices }}</p>
            </x-panel>
            <x-panel title="Rata-rata per Tagihan">
                <p class="text-2xl font-semibold text-gray-900">Rp {{ number_format($totalInvoices > 0 ? $totalRevenue / $totalInvoices : 0, 0, ',', '.') }}</p>
            </x-panel>
        </div>

        <x-panel title="Ringkasan per Bulan">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-2 pr-4">Periode</th>
                            <th class="py-2 pr-4">Jumlah Tagihan Lunas</th>
                            <th class="py-2 pr-4">Total Penghasilan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($summary as $row)
                            <tr>
                                <td class="py-2 pr-4 text-gray-900">{{ $row['label'] }}</td>
                                <td class="py-2 pr-4 text-gray-600">{{ $row['count'] }}</td>
                                <td class="py-2 pr-4 text-gray-600">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>

        <x-panel title="Detail Tagihan Lunas">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-2 pr-4">Tanggal Bayar</th>
                            <th class="py-2 pr-4">No. Invoice</th>
                            <th class="py-2 pr-4">Pelanggan</th>
                            <th class="py-2 pr-4">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td class="py-2 pr-4 text-gray-600">{{ optional($invoice->paid_at)->format('d M Y') }}</td>
                                <td class="py-2 pr-4">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-800">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td class="py-2 pr-4 text-gray-600">{{ $invoice->customer?->name ?? '-' }}</td>
                                <td class="py-2 pr-4 text-gray-600">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-gray-500">Tidak ada tagihan lunas pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>
    </div>
</x-app-layout>
