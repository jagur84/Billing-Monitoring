<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Tiket Baru" subtitle="Buat laporan gangguan baru." />
    </x-slot>

    <x-panel>
        <form action="{{ route('tickets.store') }}" method="POST" class="space-y-6">
            @csrf
            @include('admin.tickets._form', ['customers' => $customers, 'technicians' => $technicians])

            <div class="flex justify-end gap-3">
                <a href="{{ route('tickets.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
