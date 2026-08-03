<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pelanggan" subtitle="Kelola data pelanggan dan status langganan.">
            <x-slot name="actions">
                <a href="{{ route('customers.export', request()->query()) }}">
                    <x-secondary-button type="button">Export Excel</x-secondary-button>
                </a>
                <x-secondary-button type="button" x-data="" x-on:click="$dispatch('open-modal', 'import-customers')">Import Excel</x-secondary-button>
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

        @if (session('import_errors'))
            <div class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-700">
                <p class="font-medium">Baris yang dilewati:</p>
                <p class="mt-1">{{ session('import_errors') }}</p>
            </div>
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

    <x-modal name="import-customers" maxWidth="md">
        <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">Import Pelanggan dari Excel</h2>
            <p class="mt-1 text-sm text-gray-500">
                Belum punya file-nya?
                <a href="{{ route('customers.import-template') }}" class="font-medium text-indigo-600 hover:text-indigo-800">Download contoh format import</a>
                lalu isi baris-baris baru mengikuti contoh tersebut.
            </p>

            <div class="mt-6">
                <x-input-label for="import_file" value="File Excel (.xlsx, .xls, atau .csv)" />
                <input id="import_file" name="file" type="file" accept=".xlsx,.xls,.csv" required
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                <x-input-error :messages="$errors->get('file')" class="mt-2" />
                <p class="mt-2 text-xs text-gray-500">Kolom wajib: Nama, Telepon, Paket (harus sudah ada di sistem), Username PPPoE, Password PPPoE, dan Jatuh Tempo. Kolom lain boleh dikosongkan. Setiap baris akan ditambahkan sebagai pelanggan baru dengan kode otomatis.</p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Kembali</x-secondary-button>
                <x-primary-button type="submit">Import</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
