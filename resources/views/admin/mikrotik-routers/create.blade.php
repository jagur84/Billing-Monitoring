<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Router Baru" subtitle="Tambahkan router MikroTik baru." />
    </x-slot>

    <x-panel>
        <form action="{{ route('mikrotik-routers.store') }}" method="POST" class="space-y-6">
            @csrf
            @include('admin.mikrotik-routers._form')

            <div class="flex justify-end gap-3">
                <a href="{{ route('mikrotik-routers.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
