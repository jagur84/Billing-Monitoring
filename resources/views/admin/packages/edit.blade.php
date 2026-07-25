<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Ubah Paket" :subtitle="$package->name" />
    </x-slot>

    <x-panel>
        <form action="{{ route('packages.update', $package) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.packages._form', ['package' => $package])

            <div class="flex justify-end gap-3">
                <a href="{{ route('packages.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan Perubahan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
