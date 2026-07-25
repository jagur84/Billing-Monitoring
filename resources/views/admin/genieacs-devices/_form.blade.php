@props(['device' => null, 'customers'])

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="customer_id" value="Pelanggan" />
        <x-select-input id="customer_id" name="customer_id" class="mt-1 block w-full" required>
            <option value="">- Pilih Pelanggan -</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected(old('customer_id', $device?->customer_id) == $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="device_id" value="Device ID (GenieACS)" />
        <x-text-input id="device_id" name="device_id" type="text" class="mt-1 block w-full" value="{{ old('device_id', $device?->device_id) }}" required />
        <x-input-error :messages="$errors->get('device_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="serial_number" value="Serial Number" />
        <x-text-input id="serial_number" name="serial_number" type="text" class="mt-1 block w-full" value="{{ old('serial_number', $device?->serial_number) }}" />
        <x-input-error :messages="$errors->get('serial_number')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="product_class" value="Model / Product Class" />
        <x-text-input id="product_class" name="product_class" type="text" class="mt-1 block w-full" value="{{ old('product_class', $device?->product_class) }}" />
        <x-input-error :messages="$errors->get('product_class')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="manufacturer" value="Manufacturer" />
        <x-text-input id="manufacturer" name="manufacturer" type="text" class="mt-1 block w-full" value="{{ old('manufacturer', $device?->manufacturer) }}" />
        <x-input-error :messages="$errors->get('manufacturer')" class="mt-2" />
    </div>
</div>
