@php $current = fn ($key, $default = null) => old($key, isset($expense) ? $expense->{$key} : $default); @endphp

<x-panel>
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <x-input-label for="expense_date" value="Tanggal" />
            <x-text-input id="expense_date" name="expense_date" type="date" class="mt-1 block w-full"
                          value="{{ old('expense_date', isset($expense) ? $expense->expense_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required />
            <x-input-error :messages="$errors->get('expense_date')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="category" value="Kategori" />
            <x-text-input id="category" name="category" type="text" list="category-suggestions" class="mt-1 block w-full"
                          value="{{ $current('category') }}" placeholder="Contoh: Gaji Teknisi" required />
            <datalist id="category-suggestions">
                <option value="Gaji &amp; Honor">
                <option value="Listrik">
                <option value="Sewa Tempat/Tiang">
                <option value="Bandwidth/Upstream">
                <option value="Perawatan Perangkat">
                <option value="Transportasi">
                <option value="Lain-lain">
            </datalist>
            <x-input-error :messages="$errors->get('category')" class="mt-2" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="description" value="Keterangan (opsional)" />
            <x-text-input id="description" name="description" type="text" class="mt-1 block w-full" value="{{ $current('description') }}" />
            <x-input-error :messages="$errors->get('description')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="amount" value="Jumlah" />
            <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="mt-1 block w-full" value="{{ $current('amount') }}" required />
            <x-input-error :messages="$errors->get('amount')" class="mt-2" />
        </div>
    </div>
</x-panel>

<div class="mt-6 flex justify-end gap-3">
    <a href="{{ route('expenses.index') }}"><x-secondary-button type="button">Kembali</x-secondary-button></a>
    <x-primary-button>Simpan</x-primary-button>
</div>
