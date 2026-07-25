@php
    $current = fn ($key, $default = null) => old($key, \App\Models\Setting::get($key, $default));
    $logoPath = \App\Models\Setting::get('app_logo_path');
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pengaturan" subtitle="Nama aplikasi dan logo." />
    </x-slot>

    @include('admin.settings._nav')

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <form action="{{ route('settings.general.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <x-panel title="Identitas Aplikasi">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="app_name" value="Nama Aplikasi" />
                    <x-text-input id="app_name" name="app_name" type="text" class="mt-1 block w-full" value="{{ $current('app_name', config('app.name')) }}" required />
                    <x-input-error :messages="$errors->get('app_name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="logo" value="Logo" />
                    <div class="mt-1 flex items-center gap-4">
                        @if ($logoPath)
                            <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="Logo" class="h-12 w-12 rounded-lg border border-gray-200 bg-white object-contain p-1">
                        @else
                            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-600 font-bold text-white">{{ substr($current('app_name', config('app.name', 'A')), 0, 1) }}</div>
                        @endif
                        <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/svg+xml,image/webp" class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-gray-100 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-700 hover:file:bg-gray-200">
                    </div>
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    @if ($logoPath)
                        <label class="mt-2 flex items-center gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Hapus logo (kembali ke lambang default)
                        </label>
                    @endif
                </div>
            </div>
        </x-panel>

        <x-panel title="Format Penomoran">
            <p class="mb-4 text-sm text-gray-500">Awalan (prefix) untuk nomor invoice, kode pelanggan, dan nomor tiket yang dibuat otomatis.</p>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div>
                    <x-input-label for="invoice_number_prefix" value="Prefix Invoice" />
                    <x-text-input id="invoice_number_prefix" name="invoice_number_prefix" type="text" class="mt-1 block w-full" value="{{ $current('invoice_number_prefix', 'INV') }}" maxlength="10" required />
                    <p class="mt-1 text-xs text-gray-500">Contoh: {{ $current('invoice_number_prefix', 'INV') }}-202607-AB3XZ</p>
                    <x-input-error :messages="$errors->get('invoice_number_prefix')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="customer_code_prefix" value="Prefix Kode Pelanggan" />
                    <x-text-input id="customer_code_prefix" name="customer_code_prefix" type="text" class="mt-1 block w-full" value="{{ $current('customer_code_prefix', 'CUST') }}" maxlength="10" required />
                    <p class="mt-1 text-xs text-gray-500">Contoh: {{ $current('customer_code_prefix', 'CUST') }}-AB3XYZ</p>
                    <x-input-error :messages="$errors->get('customer_code_prefix')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="ticket_number_prefix" value="Prefix Nomor Tiket" />
                    <x-text-input id="ticket_number_prefix" name="ticket_number_prefix" type="text" class="mt-1 block w-full" value="{{ $current('ticket_number_prefix', 'TKT') }}" maxlength="10" required />
                    <p class="mt-1 text-xs text-gray-500">Contoh: {{ $current('ticket_number_prefix', 'TKT') }}-AB3XYZ</p>
                    <x-input-error :messages="$errors->get('ticket_number_prefix')" class="mt-2" />
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-500">Perubahan hanya berlaku untuk invoice/kode/tiket yang dibuat setelah disimpan — data lama tidak berubah.</p>
        </x-panel>

        <x-panel title="Tampilan &amp; Bahasa">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label value="Tema Tampilan" />
                    <div class="mt-2 flex gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="theme_mode" value="light" class="text-indigo-600 focus:ring-indigo-500" @checked($current('theme_mode', 'light') === 'light')>
                            Terang (Light)
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="theme_mode" value="dark" class="text-indigo-600 focus:ring-indigo-500" @checked($current('theme_mode', 'light') === 'dark')>
                            Gelap (Dark)
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('theme_mode')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="app_locale" value="Bahasa Aplikasi" />
                    <x-select-input id="app_locale" name="app_locale" class="mt-1 block w-full">
                        <option value="id" @selected($current('app_locale', 'id') === 'id')>Bahasa Indonesia</option>
                        <option value="en" @selected($current('app_locale', 'id') === 'en')>English</option>
                    </x-select-input>
                    <x-input-error :messages="$errors->get('app_locale')" class="mt-2" />
                </div>
            </div>
            <p class="mt-4 text-xs text-gray-500">Berlaku untuk semua pengguna. Cakupan bahasa saat ini: menu navigasi &amp; dashboard — halaman lain masih Bahasa Indonesia.</p>
        </x-panel>

        <div class="flex justify-end">
            <x-primary-button>Simpan</x-primary-button>
        </div>
    </form>
</x-app-layout>
