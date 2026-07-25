<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Ubah Pengeluaran" subtitle="{{ $expense->category }}" />
    </x-slot>

    <form action="{{ route('expenses.update', $expense) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.expenses._form')
    </form>
</x-app-layout>
