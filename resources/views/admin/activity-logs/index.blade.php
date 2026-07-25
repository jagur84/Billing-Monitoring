<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Log Aktivitas" subtitle="Riwayat aktivitas pengguna di seluruh sistem.">
            <x-slot name="actions">
                <form action="{{ route('activity-logs.clear') }}" method="POST" onsubmit="return confirm('Hapus semua log aktivitas? Tindakan ini tidak bisa dibatalkan.');">
                    @csrf
                    @method('DELETE')
                    <x-secondary-button type="submit" class="!text-rose-600">Hapus Semua Log</x-secondary-button>
                </form>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="space-y-4">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        <x-panel>
            <form method="GET" class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-text-input type="text" name="search" placeholder="Cari deskripsi aktivitas..." class="w-full sm:w-72" value="{{ request('search') }}" />
                <x-select-input name="action" class="w-full sm:w-48">
                    <option value="">Semua Aksi</option>
                    <option value="created" @selected(request('action') === 'created')>Dibuat</option>
                    <option value="updated" @selected(request('action') === 'updated')>Diperbarui</option>
                    <option value="deleted" @selected(request('action') === 'deleted')>Dihapus</option>
                    <option value="login" @selected(request('action') === 'login')>Login</option>
                    <option value="logout" @selected(request('action') === 'logout')>Logout</option>
                </x-select-input>
                <x-secondary-button type="submit">Filter</x-secondary-button>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">Waktu</th>
                            <th class="py-3 pr-4">Pengguna</th>
                            <th class="py-3 pr-4">Aksi</th>
                            <th class="py-3 pr-4">Deskripsi</th>
                            <th class="py-3 pr-4">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($logs as $log)
                            <tr>
                                <td class="py-3 pr-4 whitespace-nowrap text-gray-600">{{ $log->created_at->format('d M Y H:i') }}</td>
                                <td class="py-3 pr-4 text-gray-900">{{ $log->user?->name ?? 'Sistem' }}</td>
                                <td class="py-3 pr-4">
                                    <x-badge :color="match($log->action) { 'created' => 'green', 'updated' => 'amber', 'deleted' => 'red', 'login' => 'indigo', default => 'gray' }">
                                        {{ match($log->action) { 'created' => 'Dibuat', 'updated' => 'Diperbarui', 'deleted' => 'Dihapus', 'login' => 'Login', 'logout' => 'Logout', default => ucfirst($log->action) } }}
                                    </x-badge>
                                </td>
                                <td class="py-3 pr-4 text-gray-600">{{ $log->description }}</td>
                                <td class="py-3 pr-4 font-mono text-xs text-gray-500">{{ $log->ip_address ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-gray-500">Belum ada aktivitas tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-panel>

        {{ $logs->links() }}
    </div>
</x-app-layout>
