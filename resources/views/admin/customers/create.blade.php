<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pelanggan Baru" subtitle="Tambahkan data pelanggan baru." />
    </x-slot>

    <x-panel>
        <form action="{{ route('customers.store') }}" method="POST" class="space-y-6">
            @csrf
            @include('admin.customers._form', ['packages' => $packages, 'routers' => $routers])

            <div class="flex justify-end gap-3">
                <a href="{{ route('customers.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
