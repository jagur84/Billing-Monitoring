<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pelanggan" subtitle="Kelola data pelanggan dan status langganan.">
            <x-slot name="actions">
                <a href="{{ route('customers.export', request()->query()) }}">
                    <x-secondary-button type="button">Export Excel</x-secondary-button>
                </a>
                <a href="{{ route('customers.create') }}">
                    <x-primary-button>+ Pelanggan Baru</x-primary-button>
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <x-panel>
            <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-text-input type="text" name="search" placeholder="Cari nama, kode, atau telepon..." class="w-full sm:w-72" value="{{ request('search') }}" />
                <x-select-input name="status" class="w-full sm:w-48">
                    <option value="">Semua Status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="isolated" @selected(request('status') === 'isolated')>Terisolir</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </x-select-input>
                <x-secondary-button type="submit">Filter</x-secondary-button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">Kode</th>
                            <th class="py-3 pr-4">Nama</th>
                            <th class="py-3 pr-4">Paket</th>
                            <th class="py-3 pr-4">Telepon</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($customers as $customer)
                            <tr>
                                <td class="py-3 pr-4 font-mono text-xs text-gray-500">{{ $customer->customer_code }}</td>
                                <td class="py-3 pr-4">
                                    <a href="{{ route('customers.show', $customer) }}" class="font-medium text-gray-900 hover:text-indigo-600">{{ $customer->name }}</a>
                                </td>
                                <td class="py-3 pr-4 text-gray-600">{{ $customer->package?->name ?? '-' }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $customer->phone ?? '-' }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="match($customer->status) { 'active' => 'green', 'isolated' => 'red', default => 'gray' }">
                                        {{ match($customer->status) { 'active' => 'Aktif', 'isolated' => 'Terisolir', default => 'Nonaktif' } }}
                                    </x-badge>
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('customers.edit', $customer) }}" class="text-indigo-600 hover:text-indigo-800">Ubah</a>
                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pelanggan ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-rose-600 hover:text-rose-800">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-gray-500">Belum ada pelanggan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $customers->links() }}
    </div>
</x-app-layout>
