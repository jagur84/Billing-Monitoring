<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="$ticket->subject" :subtitle="$ticket->ticket_number">
            <x-slot name="actions">
                <a href="{{ route('tickets.edit', $ticket) }}"><x-secondary-button type="button">Ubah</x-secondary-button></a>
            </x-slot>
        </x-page-header>
    </x-slot>

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-panel title="Informasi Tiket">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500">Pelanggan</dt>
                    <dd><a href="{{ route('customers.show', $ticket->customer) }}" class="font-medium text-indigo-600 hover:text-indigo-800">{{ $ticket->customer->name }}</a></dd>
                </div>
                <div class="flex justify-between"><dt class="text-gray-500">Status</dt>
                    <dd>
                        <x-badge :color="match($ticket->status) { 'resolved', 'closed' => 'green', 'in_progress' => 'amber', default => 'sky' }">
                            {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                        </x-badge>
                    </dd>
                </div>
                <div class="flex justify-between"><dt class="text-gray-500">Prioritas</dt>
                    <dd>
                        <x-badge :color="match($ticket->priority) { 'high' => 'red', 'medium' => 'amber', default => 'gray' }">{{ ucfirst($ticket->priority) }}</x-badge>
                    </dd>
                </div>
                <div class="flex justify-between"><dt class="text-gray-500">Kategori</dt><dd class="text-gray-900">{{ ucfirst($ticket->category) }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Teknisi</dt><dd class="text-gray-900">{{ $ticket->assignee?->name ?? '-' }}</dd></div>
                @if ($ticket->description)
                    <div><dt class="text-gray-500">Deskripsi</dt><dd class="mt-1 text-gray-900">{{ $ticket->description }}</dd></div>
                @endif
            </dl>
        </x-panel>

        <div class="lg:col-span-2 space-y-6">
            <x-panel title="Tambah Catatan / Ubah Status">
                <form action="{{ route('tickets.logs.store', $ticket) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="note" value="Catatan" />
                        <x-textarea-input id="note" name="note" rows="3" class="mt-1 block w-full" required></x-textarea-input>
                        <x-input-error :messages="$errors->get('note')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="status" value="Status Baru" />
                        <x-select-input id="status" name="status" class="mt-1 block w-full" required>
                            @foreach (['open' => 'Terbuka', 'in_progress' => 'Diproses', 'resolved' => 'Selesai', 'closed' => 'Ditutup'] as $value => $label)
                                <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                    <div class="flex justify-end">
                        <x-primary-button>Simpan Catatan</x-primary-button>
                    </div>
                </form>
            </x-panel>

            <x-panel title="Riwayat Aktivitas">
                <div class="space-y-4">
                    @forelse ($ticket->logs as $log)
                        <div class="border-l-2 border-indigo-100 pl-4">
                            <p class="text-xs text-gray-500">{{ $log->created_at->format('d M Y H:i') }} &middot; {{ $log->user?->name ?? 'Sistem' }}</p>
                            <p class="text-sm text-gray-900">{{ $log->note }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada aktivitas.</p>
                    @endforelse
                </div>
            </x-panel>
        </div>
    </div>
</x-app-layout>
