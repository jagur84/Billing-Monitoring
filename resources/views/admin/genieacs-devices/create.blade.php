<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Perangkat Baru" subtitle="Tautkan perangkat ONT/modem GenieACS ke pelanggan." />
    </x-slot>

    <x-panel>
        <form action="{{ route('genieacs-devices.store') }}" method="POST" class="space-y-6">
            @csrf
            @include('admin.genieacs-devices._form', ['customers' => $customers])

            <div class="flex justify-end gap-3">
                <a href="{{ route('genieacs-devices.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
