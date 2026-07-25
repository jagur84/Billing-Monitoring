@props(['ticket' => null, 'customers' => null, 'technicians'])

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    @if ($customers)
        <div class="sm:col-span-2">
            <x-input-label for="customer_id" value="Pelanggan" />
            <x-select-input id="customer_id" name="customer_id" class="mt-1 block w-full" required>
                <option value="">- Pilih Pelanggan -</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected(old('customer_id', $ticket?->customer_id) == $customer->id)>{{ $customer->name }}</option>
                @endforeach
            </x-select-input>
            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
        </div>
    @endif

    <div class="sm:col-span-2">
        <x-input-label for="subject" value="Subjek" />
        <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full" value="{{ old('subject', $ticket?->subject) }}" required autofocus />
        <x-input-error :messages="$errors->get('subject')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" value="Deskripsi" />
        <x-textarea-input id="description" name="description" rows="3" class="mt-1 block w-full">{{ old('description', $ticket?->description) }}</x-textarea-input>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="category" value="Kategori" />
        <x-select-input id="category" name="category" class="mt-1 block w-full" required>
            @foreach (['connectivity' => 'Konektivitas', 'installation' => 'Instalasi', 'billing' => 'Tagihan', 'other' => 'Lainnya'] as $value => $label)
                <option value="{{ $value }}" @selected(old('category', $ticket?->category ?? 'connectivity') === $value)>{{ $label }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('category')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="priority" value="Prioritas" />
        <x-select-input id="priority" name="priority" class="mt-1 block w-full" required>
            @foreach (['low' => 'Rendah', 'medium' => 'Sedang', 'high' => 'Tinggi'] as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $ticket?->priority ?? 'medium') === $value)>{{ $label }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="assigned_to" value="Ditugaskan Kepada (Teknisi/Admin)" />
        <x-select-input id="assigned_to" name="assigned_to" class="mt-1 block w-full">
            <option value="">- Belum Ditugaskan -</option>
            @foreach ($technicians as $technician)
                <option value="{{ $technician->id }}" @selected(old('assigned_to', $ticket?->assigned_to) == $technician->id)>{{ $technician->name }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('assigned_to')" class="mt-2" />
    </div>
</div>
