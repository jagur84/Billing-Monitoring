<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Catat Pengeluaran" subtitle="Tambahkan biaya operasional baru." />
    </x-slot>

    <form action="{{ route('expenses.store') }}" method="POST">
        @csrf
        @include('admin.expenses._form')
    </form>
</x-app-layout>
