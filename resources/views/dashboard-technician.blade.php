<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Dashboard Teknisi" subtitle="Tiket yang ditugaskan kepada Anda." />
    </x-slot>

    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-stat-card label="Tiket Aktif Saya" :value="$myOpenCount" icon="clipboard-document-list" accent="indigo" />
            <x-stat-card label="Prioritas Tinggi" :value="$myHighPriorityCount" icon="exclamation-triangle" accent="rose" />
            <x-stat-card label="Selesai Bulan Ini" :value="$resolvedThisMonth" icon="check-circle" accent="emerald" />
            <x-stat-card label="Total Tiket Terbuka (Semua)" :value="$openTicketsTotal" icon="wrench-screwdriver" accent="amber" />
        </div>

        <x-panel title="Tiket Ditugaskan ke Saya">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">No. Tiket</th>
                            <th class="py-3 pr-4">Pelanggan</th>
                            <th class="py-3 pr-4">Subjek</th>
                            <th class="py-3 pr-4">Prioritas</th>
                            <th class="py-3 pr-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($myTickets as $ticket)
                            <tr>
                                <td class="py-3 pr-4">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $ticket->ticket_number }}</a>
                                </td>
                                <td class="py-3 pr-4 text-gray-900">{{ $ticket->customer?->name ?? '-' }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $ticket->subject }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="match($ticket->priority) { 'high' => 'red', 'medium' => 'amber', default => 'gray' }">
                                        {{ match($ticket->priority) { 'high' => 'Tinggi', 'medium' => 'Sedang', default => 'Rendah' } }}
                                    </x-badge>
                                </td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="$ticket->status === 'in_progress' ? 'indigo' : 'amber'">
                                        {{ $ticket->status === 'in_progress' ? 'Diproses' : 'Terbuka' }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-gray-500">Tidak ada tiket aktif yang ditugaskan kepada Anda. 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        @if ($lowStockItems->isNotEmpty())
            <x-panel title="Stok Menipis">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="py-2 pr-4">Item</th>
                                <th class="py-2 pr-4">Stok</th>
                                <th class="py-2 pr-4">Minimum</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($lowStockItems as $item)
                                <tr>
                                    <td class="py-2 pr-4 text-gray-900">{{ $item->name }}</td>
                                    <td class="py-2 pr-4 font-semibold text-rose-600">{{ $item->stock_qty }} {{ $item->unit }}</td>
                                    <td class="py-2 pr-4 text-gray-600">{{ $item->min_stock }} {{ $item->unit }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-panel>
        @endif
    </div>
</x-app-layout>
