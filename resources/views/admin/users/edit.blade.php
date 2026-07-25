@php
    $menuLabels = [
        'customers' => 'Pelanggan', 'packages' => 'Paket Langganan', 'invoices' => 'Tagihan',
        'payments' => 'Pembayaran', 'reports' => 'Laporan Penghasilan', 'mikrotik' => 'MikroTik',
        'genieacs' => 'GenieACS', 'tickets' => 'Tiket Gangguan', 'inventory' => 'Inventaris', 'expenses' => 'Pengeluaran',
    ];
    $userRole = $user->roles->first()?->name;
    $oldPermissions = old('permissions', $user->getPermissionNames()->all());
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Ubah Pengguna" :subtitle="$user->name" />
    </x-slot>

    <x-panel>
        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-6" x-data="{ role: '{{ old('role', $userRole) }}' }">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <x-input-label for="name" value="Nama" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $user->name) }}" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="email" value="Email" />
                    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" value="{{ old('email', $user->email) }}" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="phone" value="Telepon" />
                    <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" value="{{ old('phone', $user->phone) }}" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="role" value="Peran" />
                    <x-select-input id="role" name="role" class="mt-1 block w-full" x-model="role" required>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}" @selected(old('role', $userRole) === $role->name)>{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </x-select-input>
                    <x-input-error :messages="$errors->get('role')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="password" value="Kata Sandi Baru (opsional)" />
                    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div class="flex items-center gap-2 pt-6">
                    <input type="checkbox" id="is_active" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                    <x-input-label for="is_active" value="Akun aktif" />
                </div>
            </div>

            <div>
                <x-input-label value="Hak Akses Menu" />
                <template x-if="role === 'super-admin'">
                    <p class="mt-2 text-sm text-gray-500">Super Admin otomatis memiliki akses ke semua menu.</p>
                </template>
                <template x-if="role !== 'super-admin'">
                    <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
                        @foreach ($menuLabels as $slug => $label)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="permissions[]" value="{{ $slug }}" @checked(in_array($slug, $oldPermissions)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </template>
                <x-input-error :messages="$errors->get('permissions')" class="mt-2" />
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ route('users.index') }}"><x-secondary-button type="button">Batal</x-secondary-button></a>
                <x-primary-button>Simpan Perubahan</x-primary-button>
            </div>
        </form>
    </x-panel>
</x-app-layout>
