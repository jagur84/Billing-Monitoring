<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Buat Tagihan" subtitle="Buat tagihan manual untuk satu periode." />
    </x-slot>

    <x-panel>
        <form action="{{ route('invoices.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="sm:col-span-1">
                    <x-input-label for="customer_id" value="Pelanggan" />
                    <x-select-input id="customer_id" name="customer_id" class="mt-1 block w-full" required>
                        <option value="">- Pilih Pelanggan -</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_id') == $customer->id)>{{ $customer->name }} ({{ $customer->customer_code }})</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="period_month" value="Bulan" />
                    <x-select-input id="period_month" name="period_month" class="mt-1 block w-full" required>
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" @selected(old('period_month', now()->month) == $m)>{{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('period_month')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="period_year" value="Tahun" />
                    <x-text-input id="period_year" name="period_year" type="number" class="mt-1 block w-full" value="{{ old('period_year', now()->year) }}" required />
                    <x-input-error :messages="$errors->get('period_year')" class="mt-2" />
                </div>
            </div>

            <p class="text-sm text-gray-500">Jumlah, pajak, dan tanggal jatuh tempo akan dihitung otomatis berdasarkan paket dan tanggal jatuh tempo pelanggan.</p>

            <div class="flex justify-end gap-3">
                <a href="{{ route('invoices.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Buat Tagihan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
