@props(['router' => null])

<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <x-input-label for="name" value="Nama Router" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $router?->name) }}" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="host" value="Host / IP" />
        <x-text-input id="host" name="host" type="text" class="mt-1 block w-full" value="{{ old('host', $router?->host) }}" required />
        <x-input-error :messages="$errors->get('host')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="port" value="Port API" />
        <x-text-input id="port" name="port" type="number" class="mt-1 block w-full" value="{{ old('port', $router?->port ?? 8728) }}" required />
        <x-input-error :messages="$errors->get('port')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="username" value="Username" />
        <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" value="{{ old('username', $router?->username) }}" required />
        <x-input-error :messages="$errors->get('username')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="password" :value="$router ? 'Password (kosongkan jika tidak berubah)' : 'Password'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="! $router" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div class="flex items-center gap-6 pt-6">
        <label class="flex items-center gap-2">
            <input type="checkbox" name="use_ssl" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('use_ssl', $router?->use_ssl) ? 'checked' : '' }}>
            <span class="text-sm text-gray-700">Gunakan SSL (API-SSL)</span>
        </label>
        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('is_active', $router?->is_active ?? true) ? 'checked' : '' }}>
            <span class="text-sm text-gray-700">Router aktif</span>
        </label>
    </div>
</div>
