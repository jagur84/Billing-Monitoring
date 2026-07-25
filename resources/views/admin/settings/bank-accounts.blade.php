<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pengaturan" subtitle="Rekening bank untuk pembayaran transfer manual." />
    </x-slot>

    @include('admin.settings._nav')

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <x-panel title="Rekening Bank">
        <x-slot name="actions">
            <x-primary-button type="button" x-data="" x-on:click="$dispatch('open-modal', 'add-bank-account')">+ Tambah Rekening</x-primary-button>
        </x-slot>

        <p class="mb-4 text-sm text-gray-500">Rekening aktif akan ditampilkan sebagai pilihan saat mencatat pembayaran transfer, dan otomatis muncul di reminder tagihan email &amp; WhatsApp.</p>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <th class="py-3 pr-4">Bank</th>
                        <th class="py-3 pr-4">Nomor Rekening</th>
                        <th class="py-3 pr-4">Atas Nama</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3 pr-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($bankAccounts as $account)
                        <tr>
                            <td class="py-3 pr-4 font-medium text-gray-900">{{ $account->bank_name }}</td>
                            <td class="py-3 pr-4 font-mono text-gray-600">{{ $account->account_number }}</td>
                            <td class="py-3 pr-4 text-gray-600">{{ $account->account_holder ?? '-' }}</td>
                            <td class="py-3 pr-4">
                                <x-badge :color="$account->is_active ? 'green' : 'gray'">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                            </td>
                            <td class="py-3 pr-4 text-right">
                                <button type="button" x-data="" x-on:click="$dispatch('open-modal', 'edit-bank-{{ $account->id }}')" class="text-indigo-600 hover:text-indigo-800">Ubah</button>
                                <form action="{{ route('settings.bank-accounts.destroy', $account) }}" method="POST" class="inline" onsubmit="return confirm('Hapus rekening {{ $account->bank_name }} ini?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="ml-3 text-rose-600 hover:text-rose-800">Hapus</button>
                                </form>
                            </td>
                        </tr>

                        <x-modal name="edit-bank-{{ $account->id }}" maxWidth="md">
                            <form method="POST" action="{{ route('settings.bank-accounts.update', $account) }}" class="p-6">
                                @csrf
                                @method('PUT')

                                <h2 class="text-lg font-medium text-gray-900">Ubah Rekening Bank</h2>

                                <div class="mt-6 space-y-4">
                                    <div>
                                        <x-input-label for="bank_name_{{ $account->id }}" value="Nama Bank" />
                                        <x-text-input id="bank_name_{{ $account->id }}" name="bank_name" type="text" class="mt-1 block w-full" value="{{ $account->bank_name }}" required />
                                    </div>
                                    <div>
                                        <x-input-label for="account_number_{{ $account->id }}" value="Nomor Rekening" />
                                        <x-text-input id="account_number_{{ $account->id }}" name="account_number" type="text" class="mt-1 block w-full" value="{{ $account->account_number }}" required />
                                    </div>
                                    <div>
                                        <x-input-label for="account_holder_{{ $account->id }}" value="Atas Nama" />
                                        <x-text-input id="account_holder_{{ $account->id }}" name="account_holder" type="text" class="mt-1 block w-full" value="{{ $account->account_holder }}" />
                                    </div>
                                    <label class="flex items-center gap-2 text-sm text-gray-600">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($account->is_active) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        Aktif
                                    </label>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button type="button" x-on:click="$dispatch('close')">Kembali</x-secondary-button>
                                    <x-primary-button type="submit">Simpan</x-primary-button>
                                </div>
                            </form>
                        </x-modal>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-gray-500">Belum ada rekening bank.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-panel>

    <x-modal name="add-bank-account" maxWidth="md">
        <form method="POST" action="{{ route('settings.bank-accounts.store') }}" class="p-6">
            @csrf

            <h2 class="text-lg font-medium text-gray-900">Tambah Rekening Bank</h2>

            <div class="mt-6 space-y-4">
                <div>
                    <x-input-label for="new_bank_name" value="Nama Bank" />
                    <x-text-input id="new_bank_name" name="bank_name" type="text" class="mt-1 block w-full" placeholder="Contoh: BCA" required />
                </div>
                <div>
                    <x-input-label for="new_account_number" value="Nomor Rekening" />
                    <x-text-input id="new_account_number" name="account_number" type="text" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="new_account_holder" value="Atas Nama" />
                    <x-text-input id="new_account_holder" name="account_holder" type="text" class="mt-1 block w-full" />
                </div>
                <label class="flex items-center gap-2 text-sm text-gray-600">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Aktif
                </label>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Kembali</x-secondary-button>
                <x-primary-button type="submit">Simpan</x-primary-button>
            </div>
        </form>
    </x-modal>
</x-app-layout>
