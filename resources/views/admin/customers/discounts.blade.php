<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Diskon Pelanggan" subtitle="Atur diskon langganan tetap per pelanggan — otomatis kepakai setiap tagihan baru dibuat." />
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <x-panel>
            <form method="POST" action="{{ route('customers.discounts.store') }}" x-data="{ search: '' }">
                @csrf

                <p class="mb-4 text-sm text-gray-500">
                    Diskon dalam persen (%) dari biaya paket, dihitung otomatis setiap kali tagihan pelanggan tersebut dibuat
                    (otomatis harian, manual, maupun massal). Isi 0 atau kosongkan untuk pelanggan tanpa diskon.
                    Perubahan di sini <strong>tidak</strong> mengubah tagihan yang sudah terbit — hanya berlaku untuk tagihan berikutnya.
                </p>

                <div class="mb-4">
                    <x-text-input type="text" x-model="search" placeholder="Cari nama atau kode pelanggan..." class="w-full sm:w-72" />
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="py-3 pr-4">Kode</th>
                                <th class="py-3 pr-4">Nama</th>
                                <th class="py-3 pr-4">Paket</th>
                                <th class="py-3 pr-4 w-32">Diskon (%)</th>
                                <th class="py-3 pr-4 w-64">Keterangan Diskon</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($customers as $customer)
                                <tr x-data="{ q: @js(strtolower($customer->name.' '.$customer->customer_code)) }" x-show="q.includes(search.toLowerCase())">
                                    <td class="py-2 pr-4 font-mono text-xs text-gray-500">{{ $customer->customer_code }}</td>
                                    <td class="py-2 pr-4 text-gray-900">{{ $customer->name }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $customer->package?->name ?? '-' }}</td>
                                    <td class="py-2 pr-4">
                                        <x-text-input type="number" step="0.01" min="0" max="100" name="discount_percent[{{ $customer->id }}]"
                                            class="block w-full" value="{{ (float) $customer->discount_percent ?: '' }}" placeholder="0" />
                                    </td>
                                    <td class="py-2 pr-4">
                                        <x-text-input type="text" name="discount_note[{{ $customer->id }}]" maxlength="255"
                                            class="block w-full" value="{{ $customer->discount_note }}" placeholder="mis. diskon karyawan" />
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-6 text-center text-gray-500">Tidak ada pelanggan aktif dengan paket.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="{{ route('customers.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                    <x-primary-button>Simpan Diskon</x-primary-button>
                </div>
            </form>
        </x-panel>
    </div>
</x-app-layout>
