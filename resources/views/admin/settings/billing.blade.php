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

    <form action="{{ route('settings.billing.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <x-panel title="Penagihan">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="invoice_generate_days_before" value="Buat tagihan otomatis (hari sebelum jatuh tempo)" />
                    <x-text-input id="invoice_generate_days_before" name="invoice_generate_days_before" type="number" min="0" max="60" class="mt-1 block w-full" value="{{ $current('invoice_generate_days_before', 7) }}" required />
                </div>
                <div>
                    <x-input-label for="isolation_grace_days" value="Masa tenggang sebelum isolir (hari setelah jatuh tempo)" />
                    <x-text-input id="isolation_grace_days" name="isolation_grace_days" type="number" min="0" max="60" class="mt-1 block w-full" value="{{ $current('isolation_grace_days', 3) }}" required />
                </div>
            </div>
        </x-panel>

        <x-panel title="Jadwal Reminder Email (4 tahap)">
            <p class="mb-4 text-sm text-gray-500">Jumlah hari relatif ke tanggal jatuh tempo. Positif = sebelum jatuh tempo, negatif = setelah lewat jatuh tempo.</p>
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                <div>
                    <x-input-label for="reminder_offset_email_h-3" value="Reminder 1" />
                    <x-text-input id="reminder_offset_email_h-3" name="reminder_offset_email_h-3" type="number" class="mt-1 block w-full" value="{{ $current('reminder_offset_email_h-3', 3) }}" required />
                </div>
                <div>
                    <x-input-label for="reminder_offset_email_h0" value="Reminder 2" />
                    <x-text-input id="reminder_offset_email_h0" name="reminder_offset_email_h0" type="number" class="mt-1 block w-full" value="{{ $current('reminder_offset_email_h0', 0) }}" required />
                </div>
                <div>
                    <x-input-label for="reminder_offset_email_h+3" value="Reminder 3" />
                    <x-text-input id="reminder_offset_email_h+3" name="reminder_offset_email_h+3" type="number" class="mt-1 block w-full" value="{{ $current('reminder_offset_email_h+3', -3) }}" required />
                </div>
                <div>
                    <x-input-label for="reminder_offset_email_h+7" value="Reminder 4" />
                    <x-text-input id="reminder_offset_email_h+7" name="reminder_offset_email_h+7" type="number" class="mt-1 block w-full" value="{{ $current('reminder_offset_email_h+7', -7) }}" required />
                </div>
            </div>
        </x-panel>

        <x-panel title="Jadwal Reminder WhatsApp (3 tahap)">
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4">
                <div>
                    <x-input-label for="reminder_offset_whatsapp_h0" value="Reminder 1" />
                    <x-text-input id="reminder_offset_whatsapp_h0" name="reminder_offset_whatsapp_h0" type="number" class="mt-1 block w-full" value="{{ $current('reminder_offset_whatsapp_h0', 0) }}" required />
                </div>
                <div>
                    <x-input-label for="reminder_offset_whatsapp_h+3" value="Reminder 2" />
                    <x-text-input id="reminder_offset_whatsapp_h+3" name="reminder_offset_whatsapp_h+3" type="number" class="mt-1 block w-full" value="{{ $current('reminder_offset_whatsapp_h+3', -3) }}" required />
                </div>
                <div>
                    <x-input-label for="reminder_offset_whatsapp_h+7" value="Reminder 3" />
                    <x-text-input id="reminder_offset_whatsapp_h+7" name="reminder_offset_whatsapp_h+7" type="number" class="mt-1 block w-full" value="{{ $current('reminder_offset_whatsapp_h+7', -7) }}" required />
                </div>
            </div>
        </x-panel>

        <div class="flex justify-end">
            <x-primary-button>Simpan</x-primary-button>
        </div>
    </form>
</x-app-layout>
