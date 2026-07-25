<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Ubah Perangkat" :subtitle="$device->device_id" />
    </x-slot>

    <x-panel>
        <form action="{{ route('genieacs-devices.update', $device) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.genieacs-devices._form', ['device' => $device, 'customers' => $customers])

            <div class="flex justify-end gap-3">
                <a href="{{ route('genieacs-devices.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan Perubahan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
