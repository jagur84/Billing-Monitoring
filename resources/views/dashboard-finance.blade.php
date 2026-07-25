<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Dashboard Keuangan" subtitle="Ringkasan pendapatan, pengeluaran, dan tagihan." />
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Pendapatan Bulan Ini</p>
                        <p class="mt-1 text-xl font-bold text-gray-900">Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</p>
                    </div>
                    <x-dynamic-component component="heroicon-o-arrow-trending-up" class="h-8 w-8 shrink-0 text-emerald-500" />
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-rose-600">Pengeluaran Bulan Ini</p>
                        <p class="mt-1 text-xl font-bold text-gray-900">Rp {{ number_format($monthlyExpense, 0, ',', '.') }}</p>
                    </div>
                    <x-dynamic-component component="heroicon-o-arrow-trending-down" class="h-8 w-8 shrink-0 text-rose-500" />
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Saldo Kas</p>
                        <p class="mt-1 text-xl font-bold text-gray-900">Rp {{ number_format($cashBalance, 0, ',', '.') }}</p>
                    </div>
                    <x-dynamic-component component="heroicon-o-wallet" class="h-8 w-8 shrink-0 text-blue-500" />
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">Total Nunggak</p>
                        <p class="mt-1 text-xl font-bold text-gray-900">Rp {{ number_format($totalArrears, 0, ',', '.') }}</p>
                    </div>
                    <x-dynamic-component component="heroicon-o-clock" class="h-8 w-8 shrink-0 text-amber-500" />
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Tagihan Belum Lunas (Periode Ini)</p>
                <p class="mt-1 text-2xl font-bold text-rose-600">{{ number_format($unpaidThisMonthCount) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-gray-500">Tagihan Lunas (Periode Ini)</p>
                <p class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($paidThisMonthCount) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 bg-emerald-700 px-5 py-3 text-white">
                    <x-dynamic-component component="heroicon-o-clock" class="h-5 w-5" />
                    <h3 class="font-semibold">10 Pembayaran Terakhir</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-5 py-3">Pelanggan</th>
                                <th class="px-5 py-3">Invoice</th>
                                <th class="px-5 py-3">Nominal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentPayments as $payment)
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-gray-900">{{ $payment->invoice?->customer?->name ?? '-' }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $payment->invoice?->invoice_number ?? '-' }}</td>
                                    <td class="px-5 py-3 font-semibold text-emerald-600">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-6 text-center text-gray-500">Belum ada pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-2 bg-rose-600 px-5 py-3 text-white">
                    <x-dynamic-component component="heroicon-o-exclamation-circle" class="h-5 w-5" />
                    <h3 class="font-semibold">Tagihan Belum Dibayar</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-5 py-3">Pelanggan</th>
                                <th class="px-5 py-3">Jatuh Tempo</th>
                                <th class="px-5 py-3">Sisa</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($unpaidInvoices as $invoice)
                                <tr>
                                    <td class="px-5 py-3 font-semibold text-gray-900">{{ $invoice->customer?->name ?? '-' }}</td>
                                    <td class="px-5 py-3 text-gray-600">{{ $invoice->due_date->format('d-m-Y') }}</td>
                                    <td class="px-5 py-3 font-semibold text-rose-600">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-5 py-6 text-center text-gray-500">Tidak ada tagihan yang belum dibayar.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <x-panel title="Tren Pendapatan (6 Bulan Terakhir)">
            <canvas id="revenueChart" height="90"></canvas>
        </x-panel>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: @json($revenueTrendLabels),
                datasets: [{
                    label: 'Pendapatan',
                    data: @json($revenueTrendData),
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.3,
                    fill: true,
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
    @endpush
</x-app-layout>
