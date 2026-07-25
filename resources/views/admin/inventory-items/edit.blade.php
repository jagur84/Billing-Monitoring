<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Ubah Item" :subtitle="$item->name" />
    </x-slot>

    <x-panel>
        <form action="{{ route('inventory-items.update', $item) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.inventory-items._form', ['item' => $item])

            <div class="flex justify-end gap-3">
                <a href="{{ route('inventory-items.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan Perubahan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
