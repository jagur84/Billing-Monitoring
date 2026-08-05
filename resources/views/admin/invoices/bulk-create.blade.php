<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Buat Tagihan Massal" subtitle="Buat tagihan periode {{ \Carbon\Carbon::create()->month($month)->translatedFormat('F') }} {{ $year }} untuk banyak pelanggan sekaligus." />
    </x-slot>

    <x-panel>
        <form action="{{ route('invoices.bulk-store') }}" method="POST" x-data="{ selectAll: false }">
            @csrf

            <div class="mb-4 flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="select_all" value="1" x-model="selectAll"
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    Pilih semua pelanggan ({{ $customers->count() }})
                </label>
                <p class="text-sm text-gray-500">Pelanggan yang sudah punya tagihan bulan ini tetap boleh dicentang — akan otomatis dilewati, tidak akan dobel.</p>
            </div>

            <div class="max-h-[28rem] overflow-y-auto rounded-md border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="sticky top-0 bg-white">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pl-4 pr-2"></th>
                            <th class="py-3 pr-4">Kode</th>
                            <th class="py-3 pr-4">Nama</th>
                            <th class="py-3 pr-4">Paket</th>
                            <th class="py-3 pr-4">Status Tagihan Bulan Ini</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($customers as $customer)
                            <tr>
                                <td class="py-2 pl-4 pr-2">
                                    <input type="checkbox" name="customer_ids[]" value="{{ $customer->id }}"
                                        :checked="selectAll"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                                </td>
                                <td class="py-2 pr-4 font-mono text-xs text-gray-500">{{ $customer->customer_code }}</td>
                                <td class="py-2 pr-4 text-gray-900">{{ $customer->name }}</td>
                                <td class="py-2 pr-4 text-gray-600">{{ $customer->package?->name ?? '-' }}</td>
                                <td class="py-2 pr-4">
                                    @if (in_array($customer->id, $alreadyInvoiced))
                                        <x-badge color="green">Sudah dibuat</x-badge>
                                    @else
                                        <x-badge color="amber">Belum dibuat</x-badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-gray-500">Tidak ada pelanggan aktif dengan paket.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('invoices.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Buat Tagihan Terpilih</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
