<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Ubah Pelanggan" :subtitle="$customer->name" />
    </x-slot>

    <x-panel>
        <form action="{{ route('customers.update', $customer) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.customers._form', ['customer' => $customer, 'packages' => $packages, 'routers' => $routers])

            <div class="flex justify-end gap-3">
                <a href="{{ route('customers.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan Perubahan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
