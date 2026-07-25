@props(['item' => null])

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="sku" value="SKU" />
        <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full" value="{{ old('sku', $item?->sku) }}" required autofocus />
        <x-input-error :messages="$errors->get('sku')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="name" value="Nama Item" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $item?->name) }}" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category" value="Kategori" />
        <x-select-input id="category" name="category" class="mt-1 block w-full" required>
            @foreach (['ont' => 'ONT', 'cable' => 'Kabel', 'router' => 'Router', 'other' => 'Lainnya'] as $value => $label)
                <option value="{{ $value }}" @selected(old('category', $item?->category ?? 'other') === $value)>{{ $label }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="unit" value="Satuan" />
        <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full" value="{{ old('unit', $item?->unit ?? 'pcs') }}" required />
        <x-input-error :messages="$errors->get('unit')" class="mt-2" />
    </div>

    @unless ($item)
        <div>
            <x-input-label for="stock_qty" value="Stok Awal" />
            <x-text-input id="stock_qty" name="stock_qty" type="number" class="mt-1 block w-full" value="{{ old('stock_qty', 0) }}" />
            <x-input-error :messages="$errors->get('stock_qty')" class="mt-2" />
        </div>
    @endunless

    <div>
        <x-input-label for="min_stock" value="Stok Minimum" />
        <x-text-input id="min_stock" name="min_stock" type="number" class="mt-1 block w-full" value="{{ old('min_stock', $item?->min_stock ?? 0) }}" required />
        <x-input-error :messages="$errors->get('min_stock')" class="mt-2" />
    </div>
</div>
