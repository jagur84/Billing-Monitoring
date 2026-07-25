<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$device->customer->name" :subtitle="$device->device_id">
            <x-slot name="actions">
                <form action="{{ route('genieacs-devices.reboot', $device) }}" method="POST" onsubmit="return confirm('Reboot perangkat ini sekarang?');">
                    @csrf
                    <x-secondary-button type="submit">Reboot Perangkat</x-secondary-button>
                </form>
                <a href="{{ route('genieacs-devices.edit', $device) }}"><x-secondary-button type="button">Ubah</x-secondary-button></a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
        @endif

        <x-panel title="Informasi Perangkat">
            <dl class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                <div><dt class="text-gray-500">Manufacturer</dt><dd class="font-medium text-gray-900">{{ $device->manufacturer ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Model</dt><dd class="font-medium text-gray-900">{{ $device->product_class ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Serial Number</dt><dd class="font-medium text-gray-900">{{ $device->serial_number ?? '-' }}</dd></div>
                <div><dt class="text-gray-500">Terakhir Lapor</dt><dd class="font-medium text-gray-900">{{ optional($device->last_inform_at)->format('d M Y H:i') ?? '-' }}</dd></div>
            </dl>
        </x-panel>

        <x-panel title="Status Live dari GenieACS">
            @if ($liveData)
                <pre class="max-h-96 overflow-auto rounded-lg bg-gray-900 p-4 text-xs text-emerald-300">{{ json_encode($liveData, JSON_PRETTY_PRINT) }}</pre>
            @else
                <p class="text-sm text-gray-500">Tidak dapat mengambil data langsung dari server GenieACS. Pastikan server GenieACS dapat dijangkau dan Device ID sudah benar.</p>
            @endif
        </x-panel>
    </div>
</x-app-layout>
