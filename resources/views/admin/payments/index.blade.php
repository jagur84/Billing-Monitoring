<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pembayaran" subtitle="Riwayat seluruh transaksi pembayaran." />
    </x-slot>

    <div class="space-y-4">
        <x-panel>
            <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-select-input name="status" class="w-full sm:w-48">
                    <option value="">Semua Status</option>
                    @foreach (['pending' => 'Pending', 'paid' => 'Lunas', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select-input>
                <x-secondary-button type="submit">Filter</x-secondary-button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">Tanggal</th>
                            <th class="py-3 pr-4">Invoice</th>
                            <th class="py-3 pr-4">Pelanggan</th>
                            <th class="py-3 pr-4">Gateway</th>
                            <th class="py-3 pr-4">Jumlah</th>
                            <th class="py-3 pr-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="py-3 pr-4 text-gray-600">{{ $payment->created_at->format('d M Y H:i') }}</td>
                                <td class="py-3 pr-4">
                                    <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-indigo-600 hover:text-indigo-800">{{ $payment->invoice->invoice_number }}</a>
                                </td>
                                <td class="py-3 pr-4 text-gray-900">{{ $payment->invoice->customer->name }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ ucfirst($payment->gateway) }}</td>
                                <td class="py-3 pr-4 text-gray-600">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="match($payment->status) { 'paid' => 'green', 'failed', 'expired' => 'red', default => 'amber' }">
                                        {{ ucfirst($payment->status) }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-gray-500">Belum ada pembayaran.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $payments->links() }}
    </div>
</x-app-layout>
