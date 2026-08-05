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
                    Pilih tipe diskon per pelanggan: <strong>Persen</strong> (dari biaya paket) atau <strong>Nominal</strong> (potongan Rp tetap).
                    Dihitung otomatis setiap kali tagihan pelanggan tersebut dibuat (otomatis harian, manual, maupun massal).
                    Isi 0 atau kosongkan untuk pelanggan tanpa diskon.
                    Isi <strong>Berlaku Sampai</strong> untuk diskon yang punya batas waktu — setelah tanggal itu, tagihan berikutnya
                    otomatis kembali ke harga normal tanpa perlu diubah manual. Kosongkan untuk diskon tanpa batas waktu.
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
                                <th class="py-3 pr-4 w-32">Tipe</th>
                                <th class="py-3 pr-4 w-40">Nilai Diskon</th>
                                <th class="py-3 pr-4 w-56">Keterangan Diskon</th>
                                <th class="py-3 pr-4 w-40">Berlaku Sampai</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($customers as $customer)
                                <tr x-data="{ q: @js(strtolower($customer->name.' '.$customer->customer_code)), type: @js($customer->discount_type ?? 'percent') }"
                                    x-show="q.includes(search.toLowerCase())">
                                    <td class="py-2 pr-4 font-mono text-xs text-gray-500">{{ $customer->customer_code }}</td>
                                    <td class="py-2 pr-4 text-gray-900">{{ $customer->name }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $customer->package?->name ?? '-' }}</td>
                                    <td class="py-2 pr-4">
                                        <x-select-input name="discount_type[{{ $customer->id }}]" class="block w-full" x-model="type">
                                            <option value="percent">Persen</option>
                                            <option value="nominal">Nominal</option>
                                        </x-select-input>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <div x-show="type === 'percent'">
                                            <x-text-input type="number" step="0.01" min="0" max="100" name="discount_percent[{{ $customer->id }}]"
                                                class="block w-full" value="{{ (float) $customer->discount_percent ?: '' }}" placeholder="0 %" />
                                        </div>
                                        <div x-show="type === 'nominal'" x-cloak>
                                            <x-text-input type="number" step="0.01" min="0" name="discount_nominal[{{ $customer->id }}]"
                                                class="block w-full" value="{{ (float) $customer->discount_nominal ?: '' }}" placeholder="0" />
                                        </div>
                                    </td>
                                    <td class="py-2 pr-4">
                                        <x-text-input type="text" name="discount_note[{{ $customer->id }}]" maxlength="255"
                                            class="block w-full" value="{{ $customer->discount_note }}" placeholder="mis. diskon karyawan" />
                                    </td>
                                    <td class="py-2 pr-4">
                                        <x-text-input type="date" name="discount_valid_until[{{ $customer->id }}]"
                                            class="block w-full" value="{{ optional($customer->discount_valid_until)->format('Y-m-d') }}" />
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-6 text-center text-gray-500">Tidak ada pelanggan aktif dengan paket.</td></tr>
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
