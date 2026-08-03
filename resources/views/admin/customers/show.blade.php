<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$customer->name" :subtitle="$customer->customer_code">
            <x-slot name="actions">
                @if ($customer->router_id && $customer->pppoe_username)
                    @if ($customer->status === 'isolated')
                        <form action="{{ route('customers.restore', $customer) }}" method="POST" onsubmit="return confirm('Pulihkan akses internet pelanggan ini?');">
                            @csrf
                            <x-primary-button type="submit">Pulihkan Akses</x-primary-button>
                        </form>
                    @else
                        <form action="{{ route('customers.isolate', $customer) }}" method="POST" onsubmit="return confirm('Isolir akses internet pelanggan ini?');">
                            @csrf
                            <x-secondary-button type="submit" class="!text-rose-600">Isolir Sekarang</x-secondary-button>
                        </form>
                    @endif
                @endif
                <a href="{{ route('customers.edit', $customer) }}"><x-secondary-button type="button">Ubah</x-secondary-button></a>
            </x-slot>
        </x-page-header>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-panel title="Informasi Pelanggan" class="lg:col-span-1">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Status</dt>
                    <dd>
                        <x-badge :color="match($customer->status) { 'active' => 'green', 'isolated' => 'red', default => 'gray' }">
                            {{ match($customer->status) { 'active' => 'Aktif', 'isolated' => 'Terisolir', default => 'Nonaktif' } }}
                        </x-badge>
                    </dd>
                </div>
                <div class="flex justify-between"><dt class="text-gray-500">Paket</dt><dd class="font-medium text-gray-900">{{ $customer->package?->name ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Telepon</dt><dd class="text-gray-900">{{ $customer->phone ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Email</dt><dd class="text-gray-900">{{ $customer->email ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">PPPoE</dt><dd class="text-gray-900">{{ $customer->pppoe_username ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Password PPPoE</dt><dd class="font-mono text-gray-900">{{ $customer->pppoe_password ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Router</dt><dd class="text-gray-900">{{ $customer->router?->name ?? '-' }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Jatuh Tempo</dt><dd class="text-gray-900">Tanggal {{ $customer->billing_due_day }}</dd></div>
                <div><dt class="text-gray-500">Alamat</dt><dd class="mt-1 text-gray-900">{{ $customer->address ?? '-' }}</dd></div>
                @if ($customer->notes)
                    <div><dt class="text-gray-500">Catatan</dt><dd class="mt-1 text-gray-900">{{ $customer->notes }}</dd></div>
                @endif
            </dl>
        </x-panel>

        <div class="lg:col-span-2 space-y-6">
            <x-panel title="Riwayat Tagihan">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="py-2 pr-4">No. Invoice</th>
                                <th class="py-2 pr-4">Periode</th>
                                <th class="py-2 pr-4">Total</th>
                                <th class="py-2 pr-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($customer->invoices as $invoice)
                                <tr>
                                    <td class="py-2 pr-4">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-800">{{ $invoice->invoice_number }}</a>
                                    </td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $invoice->period_month }}/{{ $invoice->period_year }}</td>
                                    <td class="py-2 pr-4 text-gray-600">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                                    <td class="py-2 pr-4">
                                        <x-badge :color="match($invoice->status) { 'paid' => 'green', 'overdue' => 'red', 'cancelled' => 'gray', default => 'amber' }">
                                            {{ ucfirst($invoice->status) }}
                                        </x-badge>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-center text-gray-500">Belum ada tagihan.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-panel>

            <x-panel title="Tiket Terbaru">
                <div class="space-y-3">
                    @forelse ($customer->tickets as $ticket)
                        <div class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3">
                            <div>
                                <p class="font-medium text-gray-900">{{ $ticket->subject }}</p>
                                <p class="text-xs text-gray-500">{{ $ticket->ticket_number }}</p>
                            </div>
                            <x-badge :color="match($ticket->status) { 'resolved', 'closed' => 'green', 'in_progress' => 'amber', default => 'sky' }">
                                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                            </x-badge>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada tiket.</p>
                    @endforelse
                </div>
            </x-panel>
        </div>
    </div>
</x-app-layout>
