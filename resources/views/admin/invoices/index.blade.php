<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Tagihan" subtitle="Kelola tagihan bulanan pelanggan.">
            <x-slot name="actions">
                <x-secondary-button type="button" x-data="" x-on:click="$dispatch('open-modal', 'import-outstanding-balance')">Import Saldo Sisa</x-secondary-button>
                <a href="{{ route('invoices.bulk-create') }}">
                    <x-secondary-button type="button">+ Tagihan Massal</x-secondary-button>
                </a>
                <a href="{{ route('invoices.create') }}">
                    <x-primary-button>+ Buat Tagihan</x-primary-button>
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
        @if (session('import_errors'))
            <div class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-700">
                <p class="font-medium">Baris yang dilewati:</p>
                <p class="mt-1">{{ session('import_errors') }}</p>
            </div>
        @endif

        <x-panel>
            <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-text-input type="text" name="search" placeholder="Cari no. invoice atau nama pelanggan..." class="w-full sm:w-72" value="{{ request('search') }}" />
                <x-select-input name="status" class="w-full sm:w-48">
                    <option value="">Semua Status</option>
                    @foreach (['unpaid' => 'Belum Bayar', 'partial' => 'Cicilan', 'paid' => 'Lunas', 'overdue' => 'Jatuh Tempo', 'cancelled' => 'Dibatalkan'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select-input>
                <x-secondary-button type="submit">Filter</x-secondary-button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">No. Invoice</th>
                            <th class="py-3 pr-4">Pelanggan</th>
                            <th class="py-3 pr-4">Periode</th>
                            <th class="py-3 pr-4">Total Tagihan</th>
                            <th class="py-3 pr-4">Tagihan Sebelumnya</th>
                            <th class="py-3 pr-4">Total Pembayaran</th>
                            <th class="py-3 pr-4">Sisa Pembayaran</th>
                            <th class="py-3 pr-4">Jatuh Tempo</th>
                            <th class="py-3 pr-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($invoices as $invoice)
                            <tr>
                                <td class="py-3 pr-4">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $invoice->invoice_number }}</a>
                                </td>
                                <td class="py-3 pr-4 text-gray-900">{{ $invoice->customer->name }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $invoice->period_month }}/{{ $invoice->period_year }}</td>
                                <td class="py-3 pr-4 text-gray-600">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    @if ($invoice->previous_arrears > 0)
                                        <span class="font-medium text-rose-600">Rp {{ number_format($invoice->previous_arrears, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-emerald-600">Rp {{ number_format($invoice->total_paid, 0, ',', '.') }}</td>
                                <td class="py-3 pr-4">
                                    @if ($invoice->remaining_amount > 0)
                                        <span class="font-medium text-rose-600">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-gray-400">Rp 0</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 text-gray-600">{{ $invoice->due_date->format('d M Y') }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="match($invoice->status) { 'paid' => 'green', 'partial' => 'amber', 'overdue' => 'red', 'cancelled' => 'gray', default => 'amber' }">
                                        {{ match($invoice->status) { 'unpaid' => 'Belum Bayar', 'paid' => 'Lunas', 'partial' => 'Cicilan', 'overdue' => 'Jatuh Tempo', default => 'Dibatalkan' } }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-6 text-center text-gray-500">Belum ada tagihan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $invoices->links() }}
    </div>

    <x-modal name="import-outstanding-balance" maxWidth="md">
        <form method="POST" action="{{ route('invoices.import-outstanding') }}" enctype="multipart/form-data" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">Import Saldo Sisa (Migrasi Data Lama)</h2>
            <p class="mt-1 text-sm text-gray-500">
                Untuk pelanggan migrasi dari sistem lama yang masih punya sisa tagihan.
                Belum punya file-nya?
                <a href="{{ route('invoices.import-outstanding-template') }}" class="font-medium text-indigo-600 hover:text-indigo-800">Download contoh format import</a>
                lalu isi baris-baris baru mengikuti contoh tersebut.
            </p>

            <div class="mt-6">
                <x-input-label for="import_outstanding_file" value="File Excel (.xlsx, .xls, atau .csv)" />
                <input id="import_outstanding_file" name="file" type="file" accept=".xlsx,.xls,.csv" required
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                <x-input-error :messages="$errors->get('file')" class="mt-2" />
                <p class="mt-2 text-xs text-gray-500">Kolom wajib: Kode Pelanggan (harus sudah ada di sistem), Bulan, Tahun, dan Sisa Tagihan. Setiap baris akan dibuat sebagai tagihan periode lama berstatus belum bayar, dan otomatis ikut tergabung ke tagihan berikutnya pelanggan tersebut.</p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Kembali</x-secondary-button>
                <x-primary-button type="submit">Import</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
