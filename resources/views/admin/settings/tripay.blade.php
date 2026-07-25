@php
    $current = fn ($key, $default = null) => old($key, \App\Models\Setting::get($key, $default));
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pengaturan" subtitle="SMTP, Tripay, WhatsApp, dan template notifikasi." />
    </x-slot>

    @include('admin.settings._nav')

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-panel title="Pengaturan Tripay" class="lg:col-span-2">
            <form action="{{ route('settings.tripay.update') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="tripay_mode" value="Mode" />
                        <x-select-input id="tripay_mode" name="tripay_mode" class="mt-1 block w-full" required>
                            <option value="sandbox" @selected($current('tripay_mode', 'sandbox') === 'sandbox')>Sandbox (testing)</option>
                            <option value="production" @selected($current('tripay_mode') === 'production')>Production</option>
                        </x-select-input>
                    </div>

                    <div>
                        <x-input-label for="tripay_default_method" value="Metode Pembayaran Default" />
                        <x-text-input id="tripay_default_method" name="tripay_default_method" type="text" class="mt-1 block w-full" value="{{ $current('tripay_default_method', 'QRIS') }}" required />
                        <x-input-error :messages="$errors->get('tripay_default_method')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="tripay_merchant_code" value="Merchant Code" />
                        <x-text-input id="tripay_merchant_code" name="tripay_merchant_code" type="text" class="mt-1 block w-full" value="{{ $current('tripay_merchant_code') }}" />
                    </div>

                    <div>
                        <x-input-label for="tripay_api_key" value="API Key" />
                        <x-text-input id="tripay_api_key" name="tripay_api_key" type="text" class="mt-1 block w-full" value="{{ $current('tripay_api_key') }}" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-input-label for="tripay_private_key" value="Private Key" />
                        <x-text-input id="tripay_private_key" name="tripay_private_key" type="text" class="mt-1 block w-full" value="{{ $current('tripay_private_key') }}" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </x-panel>

        <x-panel title="Tes Koneksi">
            <p class="mb-4 text-sm text-gray-600">Cek apakah kredensial di atas valid dengan mengambil daftar channel pembayaran dari Tripay.</p>
            <form action="{{ route('settings.tripay.test') }}" method="POST">
                @csrf
                <x-secondary-button type="submit">Tes Koneksi</x-secondary-button>
            </form>
            <p class="mt-4 text-xs text-gray-500">
                Simpan pengaturan di atas dulu sebelum tes koneksi.
            </p>
            <p class="mt-4 text-xs text-gray-500">
                Webhook callback URL yang perlu didaftarkan di dashboard Tripay:
                <br><code class="break-all">{{ route('tripay.webhook') }}</code>
            </p>
        </x-panel>
    </div>
</x-app-layout>
