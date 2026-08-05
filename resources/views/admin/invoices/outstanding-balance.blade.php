<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Sisa Tagihan" subtitle="Entri saldo sisa pelanggan dari sistem lama per periode — otomatis tergabung ke tagihan berikutnya." />
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
        @endif

        <x-panel>
            <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-select-input name="period" class="w-full sm:w-64" onchange="this.form.submit()">
                    <option value="">- Pilih Periode -</option>
                    @foreach ($periodOptions as $option)
                        <option value="{{ $option['month'] }}-{{ $option['year'] }}" @selected($month === $option['month'] && $year === $option['year'])>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                </x-select-input>
                <noscript><x-secondary-button type="submit">Tampilkan</x-secondary-button></noscript>
            </form>
        </x-panel>

        @if ($customers !== null)
            <x-panel>
                <form method="POST" action="{{ route('invoices.outstanding-balance.store') }}">
                    @csrf
                    <input type="hidden" name="month" value="{{ $month }}">
                    <input type="hidden" name="year" value="{{ $year }}">

                    <p class="mb-4 text-sm text-gray-500">
                        Masukkan sisa tagihan pelanggan untuk periode <strong>{{ \Carbon\Carbon::create()->month($month)->translatedFormat('F') }} {{ $year }}</strong>.
                        Kosongkan atau isi 0 untuk pelanggan yang tidak punya sisa tagihan periode ini — baris itu tidak akan disimpan.
                        Pelanggan yang sudah punya tagihan untuk periode ini akan otomatis dilewati.
                    </p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                    <th class="py-3 pr-4">Kode</th>
                                    <th class="py-3 pr-4">Nama</th>
                                    <th class="py-3 pr-4">Paket</th>
                                    <th class="py-3 pr-4 w-48">Sisa Tagihan (Rp)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($customers as $customer)
                                    <tr>
                                        <td class="py-2 pr-4 font-mono text-xs text-gray-500">{{ $customer->customer_code }}</td>
                                        <td class="py-2 pr-4 text-gray-900">{{ $customer->name }}</td>
                                        <td class="py-2 pr-4 text-gray-600">{{ $customer->package?->name ?? '-' }}</td>
                                        <td class="py-2 pr-4">
                                            <x-text-input type="number" step="0.01" min="0" name="amounts[{{ $customer->id }}]"
                                                class="block w-full" placeholder="0" />
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-6 text-center text-gray-500">Tidak ada pelanggan aktif dengan paket.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <a href="{{ route('invoices.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                        <x-primary-button>Simpan Sisa Tagihan</x-primary-button>
                    </div>
                </form>
            </x-panel>
        @endif
    </div>
</x-app-layout>
