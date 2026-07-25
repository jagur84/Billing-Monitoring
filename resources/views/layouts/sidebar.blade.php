<aside
    class="fixed inset-y-0 left-0 z-50 w-64 transform bg-white border-r border-gray-200 transition-transform duration-200 ease-in-out lg:translate-x-0"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
>
    <div class="flex h-16 items-center gap-2 px-6 border-b border-gray-200">
        @if ($logoPath = \App\Models\Setting::get('app_logo_path'))
            <img src="{{ Storage::disk('public')->url($logoPath) }}" alt="{{ config('app.name') }}" class="h-8 w-8 rounded-lg object-contain">
        @else
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold">{{ substr(config('app.name', 'A'), 0, 1) }}</div>
        @endif
        <span class="text-lg font-semibold tracking-tight text-gray-900">{{ config('app.name', 'Modal Nekad') }}</span>
    </div>

    <nav class="flex flex-col gap-6 px-4 py-6 overflow-y-auto" style="height: calc(100% - 4rem)">
        <div class="flex flex-col gap-1">
            <x-nav-item :href="route('dashboard')" :active="request()->routeIs('dashboard')" icon="home">
                {{ __('Dashboard') }}
            </x-nav-item>
        </div>

        @if (Route::has('customers.index') && (auth()->user()->can('customers') || auth()->user()->can('packages')))
        <div>
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Pelanggan') }}</p>
            <div class="mt-2 flex flex-col gap-1">
                @can('customers')
                    <x-nav-item :href="route('customers.index')" :active="request()->routeIs('customers.*')" icon="users">
                        {{ __('Pelanggan') }}
                    </x-nav-item>
                @endcan
                @can('packages')
                    <x-nav-item :href="route('packages.index')" :active="request()->routeIs('packages.*')" icon="wifi">
                        {{ __('Paket Langganan') }}
                    </x-nav-item>
                @endcan
            </div>
        </div>
        @endif

        @if (Route::has('invoices.index') && (auth()->user()->can('invoices') || auth()->user()->can('payments') || auth()->user()->can('reports')))
        <div>
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Keuangan') }}</p>
            <div class="mt-2 flex flex-col gap-1">
                @can('invoices')
                    <x-nav-item :href="route('invoices.index')" :active="request()->routeIs('invoices.*')" icon="document-text">
                        {{ __('Tagihan') }}
                    </x-nav-item>
                @endcan
                @can('payments')
                    <x-nav-item :href="route('payments.index')" :active="request()->routeIs('payments.*')" icon="banknotes">
                        {{ __('Pembayaran') }}
                    </x-nav-item>
                @endcan
                @can('reports')
                    <x-nav-item :href="route('reports.revenue')" :active="request()->routeIs('reports.*')" icon="chart-bar">
                        {{ __('Laporan Penghasilan') }}
                    </x-nav-item>
                @endcan
            </div>
        </div>
        @endif

        @if (Route::has('mikrotik-routers.index') && (auth()->user()->can('mikrotik') || auth()->user()->can('genieacs')))
        <div>
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Jaringan') }}</p>
            <div class="mt-2 flex flex-col gap-1">
                @can('mikrotik')
                    <x-nav-item :href="route('mikrotik-routers.index')" :active="request()->routeIs('mikrotik-routers.*')" icon="server-stack">
                        {{ __('MikroTik') }}
                    </x-nav-item>
                @endcan
                @can('genieacs')
                    <x-nav-item :href="route('genieacs-devices.index')" :active="request()->routeIs('genieacs-devices.*')" icon="cpu-chip">
                        {{ __('GenieACS') }}
                    </x-nav-item>
                @endcan
            </div>
        </div>
        @endif

        @if (Route::has('tickets.index') && (auth()->user()->can('tickets') || auth()->user()->can('inventory') || auth()->user()->can('expenses')))
        <div>
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Operasional') }}</p>
            <div class="mt-2 flex flex-col gap-1">
                @can('tickets')
                    <x-nav-item :href="route('tickets.index')" :active="request()->routeIs('tickets.*')" icon="wrench-screwdriver">
                        {{ __('Tiket Gangguan') }}
                    </x-nav-item>
                @endcan
                @can('inventory')
                    <x-nav-item :href="route('inventory-items.index')" :active="request()->routeIs('inventory-items.*')" icon="cube">
                        {{ __('Inventaris') }}
                    </x-nav-item>
                @endcan
                @can('expenses')
                    @if (Route::has('expenses.index'))
                        <x-nav-item :href="route('expenses.index')" :active="request()->routeIs('expenses.*')" icon="receipt-percent">
                            {{ __('Pengeluaran') }}
                        </x-nav-item>
                    @endif
                @endcan
            </div>
        </div>
        @endif

        @if (Route::has('users.index') && auth()->user()?->hasRole('super-admin'))
        <div>
            <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400">{{ __('Pengaturan') }}</p>
            <div class="mt-2 flex flex-col gap-1">
                <x-nav-item :href="route('users.index')" :active="request()->routeIs('users.*')" icon="shield-check">
                    {{ __('Pengguna & Peran') }}
                </x-nav-item>
                <x-nav-item :href="route('activity-logs.index')" :active="request()->routeIs('activity-logs.*')" icon="clipboard-document-list">
                    {{ __('Log Aktivitas') }}
                </x-nav-item>
                <x-nav-item :href="route('settings.smtp')" :active="request()->routeIs('settings.*')" icon="cog-6-tooth">
                    {{ __('Pengaturan Sistem') }}
                </x-nav-item>
            </div>
        </div>
        @endif
    </nav>
</aside>
