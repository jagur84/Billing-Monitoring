<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Tagihan" subtitle="Kelola tagihan bulanan pelanggan.">
            <x-slot name="actions">
                <a href="{{ route('invoices.outstanding-balance') }}">
                    <x-secondary-button type="button">Sisa Tagihan</x-secondary-button>
                </a>
                <a href="{{ route('invoices.bulk-create') }}">
                    <x-secondary-button type="button">+ Tagihan Massal</x-secondary-button>
                </a>
                <a href="{{ route('invoices.create') }}">
                    <x-primary-button>+ Buat Tagihan</x-primary-button>
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
        @endif

        <x-panel>
            <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-text-input type="text" name="search" placeholder="Cari no. invoice atau nama pelanggan..." class="w-full sm:w-72" value="{{ request('search') }}" />
                <x-select-input name="status" class="w-full sm:w-48">
                    <option value="">Semua Status</option>
                    @foreach (['unpaid' => 'Belum Bayar', 'partial' => 'Cicilan', 'paid' => 'Lunas', 'overdue' => 'Jatuh Tempo', 'cancelled' => 'Dibatalkan'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select-input>
                <x-secondary-button type="submit">Filter</x-secondary-button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">No. Invoice</th>
                            <th class="py-3 pr-4">Pelanggan</th>
                            <th class="py-3 pr-4">Periode</th>
                            <th class="py-3 pr-4">Total Tagihan</th>
                            <th class="py-3 pr-4">Tagihan Sebelumnya</th>
                            <th class="py-3 pr-4">Total Pembayaran</th>
                            <th class="py-3 pr-4">Sisa Pembayaran</th>
                            <th class="py-3 pr-4">Jatuh Tempo</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td class="py-3 pr-4">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td class="py-3 pr-4 text-gray-900">{{ $invoice->customer->name }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $invoice->period_month }}/{{ $invoice->period_year }}</td>
                                <td class="py-3 pr-4 text-gray-600">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    @if ($invoice->previous_arrears > 0)
                                        <span class="font-medium text-rose-600">Rp {{ number_format($invoice->previous_arrears, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-emerald-600">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    @if ($invoice->remaining_amount > 0)
                                        <span class="font-medium text-rose-600">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-gray-400">Rp 0</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-gray-600">{{ $invoice->due_date->format('d M Y') }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="match($invoice->status) { 'paid' => 'green', 'partial' => 'amber', 'overdue' => 'red', 'cancelled' => 'gray', default => 'amber' }">
                                        {{ match($invoice->status) { 'unpaid' => 'Belum Bayar', 'paid' => 'Lunas', 'partial' => 'Cicilan', 'overdue' => 'Jatuh Tempo', default => 'Dibatalkan' } }}
                                    </x-badge>
                                </td>
                                <td class="py-3 pr-4 text-right whitespace-nowrap">
                                    @if (in_array($invoice->status, ['unpaid', 'overdue', 'partial']))
                                        <a href="{{ route('invoices.edit', $invoice) }}" class="text-indigo-600 hover:text-indigo-800">Ubah</a>
                                    @endif
                                    @if ($invoice->payments_count === 0)
                                        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline" onsubmit="return confirm('Hapus tagihan ini? Tindakan ini tidak bisa dibatalkan.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-3 text-rose-600 hover:text-rose-800">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="py-6 text-center text-gray-500">Belum ada tagihan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $invoices->links() }}
    </div>
</x-app-layout>
