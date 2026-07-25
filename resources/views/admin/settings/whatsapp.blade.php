<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pengaturan" subtitle="SMTP, Tripay, WhatsApp, dan template notifikasi." />
    </x-slot>

    @include('admin.settings._nav')

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-panel title="Status Koneksi">
            <div class="flex items-center gap-3">
                <span class="relative flex h-3 w-3">
                    <span @class([
                        'absolute inline-flex h-full w-full animate-ping rounded-full opacity-75',
                        'bg-emerald-400' => $status['connected'] ?? false,
                        'bg-rose-400' => ! ($status['connected'] ?? false),
                    ])></span>
                    <span @class([
                        'relative inline-flex h-3 w-3 rounded-full',
                        'bg-emerald-500' => $status['connected'] ?? false,
                        'bg-rose-500' => ! ($status['connected'] ?? false),
                    ])></span>
                </span>
                <span class="text-sm font-medium text-gray-900">
                    {{ ($status['connected'] ?? false) ? 'Terhubung' : 'Belum terhubung' }}
                </span>
            </div>
            <p class="mt-4 text-sm text-gray-600">
                @if ($status['connected'] ?? false)
                    WhatsApp engine sudah tertaut dan siap mengirim pesan.
                @else
                    Scan QR di samping menggunakan WhatsApp di HP Anda: buka
                    <strong>Pengaturan → Perangkat Tertaut → Tautkan Perangkat</strong>.
                @endif
            </p>
            <button onclick="location.reload()" class="mt-4 text-sm text-indigo-600 hover:text-indigo-800">Segarkan status</button>
        </x-panel>

        <x-panel title="QR Code" class="lg:col-span-2">
            @if ($status['connected'] ?? false)
                <p class="text-sm text-gray-500">Tidak perlu scan QR — perangkat sudah tertaut.</p>
            @else
                <iframe src="{{ route('settings.whatsapp.qr') }}" class="h-96 w-full rounded-lg border border-gray-200"></iframe>
            @endif
        </x-panel>
    </div>
</x-app-layout>
