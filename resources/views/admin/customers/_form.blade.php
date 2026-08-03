@props(['customer' => null, 'packages', 'routers'])

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="name" value="Nama Lengkap" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $customer?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" value="Telepon *" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" value="{{ old('phone', $customer?->phone) }}" required />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $customer?->email) }}" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="nik" value="NIK" />
        <x-text-input id="nik" name="nik" type="text" class="mt-1 block w-full" value="{{ old('nik', $customer?->nik) }}" />
        <x-input-error :messages="$errors->get('nik')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="address" value="Alamat" />
        <x-textarea-input id="address" name="address" rows="2" class="mt-1 block w-full">{{ old('address', $customer?->address) }}</x-textarea-input>
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="package_id" value="Paket Langganan *" />
        <x-select-input id="package_id" name="package_id" class="mt-1 block w-full" required>
            <option value="">- Pilih Paket -</option>
            @foreach ($packages as $package)
                <option value="{{ $package->id }}" @selected(old('package_id', $customer?->package_id) == $package->id)>
                    {{ $package->name }} ({{ $package->speed_mbps }} Mbps)
                </option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('package_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="billing_due_day" value="Tanggal Jatuh Tempo *" />
        <x-text-input id="billing_due_day" name="billing_due_day" type="number" min="1" max="28" class="mt-1 block w-full" value="{{ old('billing_due_day', $customer?->billing_due_day ?? 1) }}" required />
        <x-input-error :messages="$errors->get('billing_due_day')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="pppoe_username" value="Username PPPoE *" />
        <x-text-input id="pppoe_username" name="pppoe_username" type="text" class="mt-1 block w-full" value="{{ old('pppoe_username', $customer?->pppoe_username) }}" required />
        <x-input-error :messages="$errors->get('pppoe_username')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="pppoe_password" value="Password PPPoE *" />
        <x-text-input id="pppoe_password" name="pppoe_password" type="text" class="mt-1 block w-full" value="{{ old('pppoe_password', $customer?->pppoe_password) }}" required />
        <x-input-error :messages="$errors->get('pppoe_password')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="ip_address" value="Alamat IP" />
        <x-text-input id="ip_address" name="ip_address" type="text" class="mt-1 block w-full" value="{{ old('ip_address', $customer?->ip_address) }}" />
        <x-input-error :messages="$errors->get('ip_address')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="router_id" value="Router MikroTik" />
        <x-select-input id="router_id" name="router_id" class="mt-1 block w-full">
            <option value="">- Tidak Ada -</option>
            @foreach ($routers as $router)
                <option value="{{ $router->id }}" @selected(old('router_id', $customer?->router_id) == $router->id)>{{ $router->name }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('router_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="installation_date" value="Tanggal Pemasangan" />
        <x-text-input id="installation_date" name="installation_date" type="date" class="mt-1 block w-full" value="{{ old('installation_date', optional($customer?->installation_date)->format('Y-m-d')) }}" />
        <x-input-error :messages="$errors->get('installation_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="status" value="Status" />
        <x-select-input id="status" name="status" class="mt-1 block w-full" required>
            @foreach (['active' => 'Aktif', 'isolated' => 'Terisolir', 'inactive' => 'Nonaktif'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $customer?->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('status')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="notes" value="Catatan" />
        <x-textarea-input id="notes" name="notes" rows="3" class="mt-1 block w-full">{{ old('notes', $customer?->notes) }}</x-textarea-input>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>
