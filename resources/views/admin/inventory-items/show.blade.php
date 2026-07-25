<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$item->name" :subtitle="$item->sku">
            <x-slot name="actions">
                <a href="{{ route('inventory-items.edit', $item) }}"><x-secondary-button type="button">Ubah</x-secondary-button></a>
            </x-slot>
        </x-page-header>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-panel title="Stok Saat Ini">
            <div class="text-center">
                <p class="text-4xl font-semibold {{ $item->stock_qty <= $item->min_stock ? 'text-rose-600' : 'text-gray-900' }}">{{ $item->stock_qty }}</p>
                <p class="mt-1 text-sm text-gray-500">{{ $item->unit }} &middot; min. {{ $item->min_stock }}</p>
            </div>
        </x-panel>

        <x-panel title="Catat Transaksi" class="lg:col-span-2">
            <form action="{{ route('inventory-items.transactions.store', $item) }}" method="POST" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                @csrf
                <div>
                    <x-input-label for="type" value="Jenis" />
                    <x-select-input id="type" name="type" class="mt-1 block w-full" required>
                        <option value="in">Masuk</option>
                        <option value="out">Keluar</option>
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="qty" value="Jumlah" />
                    <x-text-input id="qty" name="qty" type="number" min="1" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="customer_id" value="Pelanggan (opsional)" />
                    <x-select-input id="customer_id" name="customer_id" class="mt-1 block w-full">
                        <option value="">-</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="ticket_id" value="Tiket (opsional)" />
                    <x-select-input id="ticket_id" name="ticket_id" class="mt-1 block w-full">
                        <option value="">-</option>
                        @foreach ($tickets as $ticket)
                            <option value="{{ $ticket->id }}">{{ $ticket->ticket_number }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="note" value="Catatan" />
                    <x-text-input id="note" name="note" type="text" class="mt-1 block w-full" />
                </div>
                <div class="sm:col-span-2 flex justify-end">
                    <x-primary-button>Simpan Transaksi</x-primary-button>
                </div>
            </form>
        </x-panel>
    </div>

    <x-panel title="Riwayat Transaksi" class="mt-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="py-2 pr-4">Tanggal</th>
                        <th class="py-2 pr-4">Jenis</th>
                        <th class="py-2 pr-4">Jumlah</th>
                        <th class="py-2 pr-4">Pelanggan</th>
                        <th class="py-2 pr-4">Tiket</th>
                        <th class="py-2 pr-4">Catatan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($item->transactions as $tx)
                        <tr>
                            <td class="py-2 pr-4 text-gray-600">{{ $tx->created_at->format('d M Y H:i') }}</td>
                            <td class="py-2 pr-4">
                                <x-badge :color="$tx->type === 'in' ? 'green' : 'red'">{{ $tx->type === 'in' ? 'Masuk' : 'Keluar' }}</x-badge>
                            </td>
                            <td class="py-2 pr-4 text-gray-600">{{ $tx->qty }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $tx->customer?->name ?? '-' }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $tx->ticket?->ticket_number ?? '-' }}</td>
                            <td class="py-2 pr-4 text-gray-600">{{ $tx->note ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-4 text-center text-gray-500">Belum ada transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>
</x-app-layout>
