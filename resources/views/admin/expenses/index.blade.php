<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pengeluaran & Operasional" subtitle="Catat biaya operasional bulanan.">
            <x-slot name="actions">
                <a href="{{ route('expenses.create') }}">
                    <x-primary-button>+ Catat Pengeluaran</x-primary-button>
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
            <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <x-text-input type="month" name="month" class="w-full sm:w-48" value="{{ $month }}" />
                    <x-text-input type="text" name="search" placeholder="Cari kategori..." class="w-full sm:w-64" value="{{ request('search') }}" />
                    <x-secondary-button type="submit">Filter</x-secondary-button>
                </div>
                <p class="text-sm text-gray-500">
                    Total periode ini: <span class="font-semibold text-rose-600">Rp {{ number_format($monthlyTotal, 0, ',', '.') }}</span>
                </p>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">Tanggal</th>
                            <th class="py-3 pr-4">Kategori</th>
                            <th class="py-3 pr-4">Keterangan</th>
                            <th class="py-3 pr-4">Jumlah</th>
                            <th class="py-3 pr-4">Dicatat Oleh</th>
                            <th class="py-3 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($expenses as $expense)
                            <tr>
                                <td class="py-3 pr-4 text-gray-600">{{ $expense->expense_date->format('d M Y') }}</td>
                                <td class="py-3 pr-4 font-medium text-gray-900">{{ $expense->category }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $expense->description ?? '-' }}</td>
                                <td class="py-3 pr-4 text-rose-600">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $expense->recordedBy?->name ?? '-' }}</td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('expenses.edit', $expense) }}" class="text-indigo-600 hover:text-indigo-800">Ubah</a>
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" class="inline" onsubmit="return confirm('Hapus catatan pengeluaran ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="ml-3 text-rose-600 hover:text-rose-800">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-gray-500">Belum ada pengeluaran pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $expenses->links() }}
    </div>
</x-app-layout>
