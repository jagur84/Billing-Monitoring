<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Tiket Gangguan" subtitle="Kelola laporan gangguan dan penugasan teknisi.">
            <x-slot name="actions">
                <a href="{{ route('tickets.create') }}">
                    <x-primary-button>+ Tiket Baru</x-primary-button>
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
                <x-select-input name="status" class="w-full sm:w-48">
                    <option value="">Semua Status</option>
                    @foreach (['open' => 'Terbuka', 'in_progress' => 'Diproses', 'resolved' => 'Selesai', 'closed' => 'Ditutup'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select-input>
                <x-secondary-button type="submit">Filter</x-secondary-button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">No. Tiket</th>
                            <th class="py-3 pr-4">Pelanggan</th>
                            <th class="py-3 pr-4">Subjek</th>
                            <th class="py-3 pr-4">Prioritas</th>
                            <th class="py-3 pr-4">Teknisi</th>
                            <th class="py-3 pr-4">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td class="py-3 pr-4">
                                    <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $ticket->ticket_number }}</a>
                                </td>
                                <td class="py-3 pr-4 text-gray-900">{{ $ticket->customer->name }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $ticket->subject }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="match($ticket->priority) { 'high' => 'red', 'medium' => 'amber', default => 'gray' }">
                                        {{ ucfirst($ticket->priority) }}
                                    </x-badge>
                                </td>
                                <td class="py-3 pr-4 text-gray-600">{{ $ticket->assignee?->name ?? '-' }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="match($ticket->status) { 'resolved', 'closed' => 'green', 'in_progress' => 'amber', default => 'sky' }">
                                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                    </x-badge>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-gray-500">Belum ada tiket.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $tickets->links() }}
    </div>
</x-app-layout>
