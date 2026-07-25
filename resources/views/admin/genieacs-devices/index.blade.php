<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Perangkat GenieACS" subtitle="Monitoring ONT/modem pelanggan via TR-069.">
            <x-slot name="actions">
                <a href="{{ route('genieacs-devices.create') }}">
                    <x-primary-button>+ Perangkat Baru</x-primary-button>
                </a>
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

        <x-panel>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">Pelanggan</th>
                            <th class="py-3 pr-4">Device ID</th>
                            <th class="py-3 pr-4">Model</th>
                            <th class="py-3 pr-4">Terakhir Lapor</th>
                            <th class="py-3 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($devices as $device)
                            <tr>
                                <td class="py-3 pr-4">
                                    <a href="{{ route('genieacs-devices.show', $device) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $device->customer->name }}</a>
                                </td>
                                <td class="py-3 pr-4 font-mono text-xs text-gray-500">{{ $device->device_id }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $device->manufacturer }} {{ $device->product_class }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ optional($device->last_inform_at)->diffForHumans() ?? '-' }}</td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('genieacs-devices.edit', $device) }}" class="text-indigo-600 hover:text-indigo-800">Ubah</a>
                                    <form action="{{ route('genieacs-devices.destroy', $device) }}" method="POST" class="inline" onsubmit="return confirm('Hapus perangkat ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-rose-600 hover:text-rose-800">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-gray-500">Belum ada perangkat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $devices->links() }}
    </div>
</x-app-layout>
