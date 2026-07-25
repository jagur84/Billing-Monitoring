<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Item Baru" subtitle="Tambahkan item inventaris baru." />
    </x-slot>

    <x-panel>
        <form action="{{ route('inventory-items.store') }}" method="POST" class="space-y-6">
            @csrf
            @include('admin.inventory-items._form')

            <div class="flex justify-end gap-3">
                <a href="{{ route('inventory-items.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
