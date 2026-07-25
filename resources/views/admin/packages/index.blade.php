<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Paket Langganan" subtitle="Kelola daftar paket internet yang ditawarkan.">
            <x-slot name="actions">
                <a href="{{ route('packages.create') }}">
                    <x-primary-button>+ Paket Baru</x-primary-button>
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
                            <th class="py-3 pr-4">Nama Paket</th>
                            <th class="py-3 pr-4">Kecepatan</th>
                            <th class="py-3 pr-4">Harga</th>
                            <th class="py-3 pr-4">Pajak</th>
                            <th class="py-3 pr-4">Pelanggan</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($packages as $package)
                            <tr>
                                <td class="py-3 pr-4 font-medium text-gray-900">{{ $package->name }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $package->speed_mbps }} Mbps</td>
                                <td class="py-3 pr-4 text-gray-600">Rp {{ number_format($package->price, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ rtrim(rtrim(number_format($package->tax_percent, 2), '0'), '.') }}%</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $package->customers_count }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="$package->is_active ? 'green' : 'gray'">
                                        {{ $package->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </x-badge>
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('packages.edit', $package) }}" class="text-indigo-600 hover:text-indigo-800">Ubah</a>
                                    <form action="{{ route('packages.destroy', $package) }}" method="POST" class="inline" onsubmit="return confirm('Hapus paket ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-rose-600 hover:text-rose-800">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-gray-500">Belum ada paket.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $packages->links() }}
    </div>
</x-app-layout>
