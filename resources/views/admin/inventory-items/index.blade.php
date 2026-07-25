<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Inventaris" subtitle="Kelola stok perangkat (ONT, kabel, router, dll).">
            <x-slot name="actions">
                <a href="{{ route('inventory-items.create') }}">
                    <x-primary-button>+ Item Baru</x-primary-button>
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <x-panel>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">SKU</th>
                            <th class="py-3 pr-4">Nama</th>
                            <th class="py-3 pr-4">Kategori</th>
                            <th class="py-3 pr-4">Stok</th>
                            <th class="py-3 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($items as $item)
                            <tr>
                                <td class="py-3 pr-4 font-mono text-xs text-gray-500">{{ $item->sku }}</td>
                                <td class="py-3 pr-4">
                                    <a href="{{ route('inventory-items.show', $item) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $item->name }}</a>
                                </td>
                                <td class="py-3 pr-4 text-gray-600">{{ ucfirst($item->category) }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="$item->stock_qty <= $item->min_stock ? 'red' : 'green'">
                                        {{ $item->stock_qty }} {{ $item->unit }}
                                    </x-badge>
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('inventory-items.edit', $item) }}" class="text-indigo-600 hover:text-indigo-800">Ubah</a>
                                    <form action="{{ route('inventory-items.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('Hapus item ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-rose-600 hover:text-rose-800">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-gray-500">Belum ada item.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $items->links() }}
    </div>
</x-app-layout>
