<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Ubah Router" :subtitle="$router->name" />
    </x-slot>

    <x-panel>
        <form action="{{ route('mikrotik-routers.update', $router) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.mikrotik-routers._form', ['router' => $router])

            <div class="flex justify-end gap-3">
                <a href="{{ route('mikrotik-routers.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan Perubahan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
