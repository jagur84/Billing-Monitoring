<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Ubah Tiket" :subtitle="$ticket->ticket_number" />
    </x-slot>

    <x-panel>
        <form action="{{ route('tickets.update', $ticket) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.tickets._form', ['ticket' => $ticket, 'technicians' => $technicians])

            <div class="flex justify-end gap-3">
                <a href="{{ route('tickets.show', $ticket) }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan Perubahan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
