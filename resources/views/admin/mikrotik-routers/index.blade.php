<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Router MikroTik" subtitle="Kelola koneksi ke perangkat MikroTik untuk isolir/buka otomatis.">
            <x-slot name="actions">
                <a href="{{ route('mikrotik-routers.create') }}">
                    <x-primary-button>+ Router Baru</x-primary-button>
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
                            <th class="py-3 pr-4">Nama</th>
                            <th class="py-3 pr-4">Host</th>
                            <th class="py-3 pr-4">Pelanggan</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($routers as $router)
                            <tr>
                                <td class="py-3 pr-4 font-medium text-gray-900">{{ $router->name }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $router->host }}:{{ $router->port }}{{ $router->use_ssl ? ' (SSL)' : '' }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $router->customers_count }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="$router->is_active ? 'green' : 'gray'">{{ $router->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('mikrotik-routers.secrets.index', $router) }}" class="text-purple-600 hover:text-purple-800">PPPoE Secret</a>
                                    <a href="{{ route('mikrotik-routers.pppoe', $router) }}" class="ml-3 text-emerald-600 hover:text-emerald-800">Monitoring PPPoE</a>
                                    <a href="{{ route('mikrotik-routers.routing', $router) }}" class="ml-3 text-sky-600 hover:text-sky-800">IP Route</a>
                                    <a href="{{ route('mikrotik-routers.interfaces', $router) }}" class="ml-3 text-amber-600 hover:text-amber-800">Interface</a>
                                    <a href="{{ route('mikrotik-routers.edit', $router) }}" class="ml-3 text-indigo-600 hover:text-indigo-800">Ubah</a>
                                    <form action="{{ route('mikrotik-routers.destroy', $router) }}" method="POST" class="inline" onsubmit="return confirm('Hapus router ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-rose-600 hover:text-rose-800">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-gray-500">Belum ada router.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $routers->links() }}
    </div>
</x-app-layout>
