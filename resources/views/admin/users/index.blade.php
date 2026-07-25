<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pengguna & Peran" subtitle="Kelola akun staf dan hak akses.">
            <x-slot name="actions">
                <a href="{{ route('users.create') }}">
                    <x-primary-button>+ Pengguna Baru</x-primary-button>
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <x-panel>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">Nama</th>
                            <th class="py-3 pr-4">Email</th>
                            <th class="py-3 pr-4">Peran</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($users as $user)
                            <tr>
                                <td class="py-3 pr-4 font-medium text-gray-900">{{ $user->name }}</td>
                                <td class="py-3 pr-4 text-gray-600">{{ $user->email }}</td>
                                <td class="py-3 pr-4">
                                    @foreach ($user->roles as $role)
                                        <x-badge color="indigo">{{ $role->name }}</x-badge>
                                    @endforeach
                                </td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="$user->is_active ? 'green' : 'gray'">{{ $user->is_active ? 'Aktif' : 'Nonaktif' }}</x-badge>
                                </td>
                                <td class="py-3 pr-4 text-right">
                                    <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-800">Ubah</a>
                                    @if ($user->id !== auth()->id())
                                        <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus pengguna ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="ml-3 text-rose-600 hover:text-rose-800">Hapus</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $users->links() }}
    </div>
</x-app-layout>
