@props(['package' => null])

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="name" value="Nama Paket" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $package?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="speed_mbps" value="Kecepatan (Mbps)" />
        <x-text-input id="speed_mbps" name="speed_mbps" type="number" class="mt-1 block w-full" value="{{ old('speed_mbps', $package?->speed_mbps) }}" required />
        <x-input-error :messages="$errors->get('speed_mbps')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="price" value="Harga (Rp)" />
        <x-text-input id="price" name="price" type="number" step="0.01" class="mt-1 block w-full" value="{{ old('price', $package?->price) }}" required />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="tax_percent" value="Pajak (%)" />
        <x-text-input id="tax_percent" name="tax_percent" type="number" step="0.01" class="mt-1 block w-full" value="{{ old('tax_percent', $package?->tax_percent ?? 0) }}" required />
        <x-input-error :messages="$errors->get('tax_percent')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" value="Deskripsi" />
        <x-textarea-input id="description" name="description" rows="3" class="mt-1 block w-full">{{ old('description', $package?->description) }}</x-textarea-input>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('is_active', $package?->is_active ?? true) ? 'checked' : '' }}>
        <x-input-label for="is_active" value="Paket aktif" />
    </div>
</div>
