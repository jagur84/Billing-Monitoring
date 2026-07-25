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
        <x-panel title="Pengaturan SMTP" class="lg:col-span-2">
            <form action="{{ route('settings.smtp.update') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <x-input-label for="mail_mailer" value="Driver" />
                        <x-select-input id="mail_mailer" name="mail_mailer" class="mt-1 block w-full" required>
                            <option value="log" @selected($current('mail_mailer', 'log') === 'log')>Log (tidak benar-benar terkirim, untuk testing)</option>
                            <option value="smtp" @selected($current('mail_mailer', 'log') === 'smtp')>SMTP</option>
                        </x-select-input>
                        <x-input-error :messages="$errors->get('mail_mailer')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="mail_encryption" value="Enkripsi" />
                        <x-select-input id="mail_encryption" name="mail_encryption" class="mt-1 block w-full">
                            <option value="tls" @selected($current('mail_encryption', 'tls') === 'tls')>STARTTLS (port 587, umum untuk Gmail)</option>
                            <option value="ssl" @selected($current('mail_encryption') === 'ssl')>SSL/TLS (port 465)</option>
                            <option value="none" @selected($current('mail_encryption') === 'none')>Tanpa enkripsi</option>
                        </x-select-input>
                    </div>

                    <div>
                        <x-input-label for="mail_host" value="Host SMTP" />
                        <x-text-input id="mail_host" name="mail_host" type="text" class="mt-1 block w-full" value="{{ $current('mail_host', 'smtp.gmail.com') }}" placeholder="smtp.gmail.com" />
                    </div>

                    <div>
                        <x-input-label for="mail_port" value="Port" />
                        <x-text-input id="mail_port" name="mail_port" type="number" class="mt-1 block w-full" value="{{ $current('mail_port', 587) }}" />
                    </div>

                    <div>
                        <x-input-label for="mail_username" value="Username" />
                        <x-text-input id="mail_username" name="mail_username" type="text" class="mt-1 block w-full" value="{{ $current('mail_username') }}" placeholder="you@gmail.com" />
                    </div>

                    <div>
                        <x-input-label for="mail_password" value="Password / App Password (kosongkan jika tidak berubah)" />
                        <x-text-input id="mail_password" name="mail_password" type="password" class="mt-1 block w-full" />
                    </div>

                    <div>
                        <x-input-label for="mail_from_address" value="Alamat Pengirim" />
                        <x-text-input id="mail_from_address" name="mail_from_address" type="email" class="mt-1 block w-full" value="{{ $current('mail_from_address', 'billing@example.com') }}" required />
                        <x-input-error :messages="$errors->get('mail_from_address')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="mail_from_name" value="Nama Pengirim" />
                        <x-text-input id="mail_from_name" name="mail_from_name" type="text" class="mt-1 block w-full" value="{{ $current('mail_from_name', config('app.name')) }}" required />
                        <x-input-error :messages="$errors->get('mail_from_name')" class="mt-2" />
                    </div>
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Simpan</x-primary-button>
                </div>
            </form>
        </x-panel>

        <x-panel title="Kirim Email Tes">
            <form action="{{ route('settings.smtp.test') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <x-input-label for="test_email" value="Kirim ke" />
                    <x-text-input id="test_email" name="test_email" type="email" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('test_email')" class="mt-2" />
                </div>
                <x-secondary-button type="submit">Kirim Tes</x-secondary-button>
            </form>
            <p class="mt-4 text-xs text-gray-500">
                Simpan pengaturan di atas dulu sebelum mengirim tes, supaya kredensial terbaru yang dipakai.
            </p>
        </x-panel>
    </div>
</x-app-layout>
