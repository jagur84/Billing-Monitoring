<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Monitoring PPPoE Online" subtitle="{{ $router->name }} • {{ $router->host }}:{{ $router->port }}">
            <x-slot name="actions">
                <a href="{{ route('mikrotik-routers.secrets.index', $router) }}">
                    <x-secondary-button type="button">PPPoE Secret</x-secondary-button>
                </a>
                <a href="{{ route('mikrotik-routers.interfaces', $router) }}">
                    <x-secondary-button type="button">Interface</x-secondary-button>
                </a>
                <a href="{{ route('mikrotik-routers.routing', $router) }}">
                    <x-secondary-button type="button">IP Route</x-secondary-button>
                </a>
                <a href="{{ route('mikrotik-routers.index') }}">
                    <x-secondary-button type="button">&larr; Kembali</x-secondary-button>
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div x-data="pppoeMonitor({{ $router->id }})" x-init="init()" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                        <x-dynamic-component component="heroicon-o-signal" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">PPPoE Online</p>
                        <p class="text-2xl font-semibold text-gray-900" x-text="count"></p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg"
                         :class="online ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'">
                        <x-dynamic-component component="heroicon-o-check-circle" class="h-6 w-6" />
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status API</p>
                        <p class="text-2xl font-semibold" :class="online ? 'text-emerald-600' : 'text-rose-600'" x-text="online ? 'Online' : 'Offline'"></p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                        <span :class="{ 'animate-spin': loading }">
                            <x-dynamic-component component="heroicon-o-arrow-path" class="h-6 w-6" />
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Auto Refresh</p>
                        <p class="text-2xl font-semibold text-gray-900">10 Detik</p>
                    </div>
                </div>
            </div>
        </div>

        <template x-if="!online">
            <div class="rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="message"></div>
        </template>

        <x-panel>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex w-full max-w-sm items-center gap-2">
                    <x-text-input type="text" x-model="search" x-on:input="onSearch()" placeholder="Cari username PPPoE..." class="w-full" />
                    <x-secondary-button type="button" x-on:click="onSearch()">Cari</x-secondary-button>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-500">
                    <span x-show="lastUpdated" x-text="lastUpdated ? 'Diperbarui ' + lastUpdated : ''"></span>
                    <x-secondary-button type="button" x-on:click="fetchData()">
                        <span x-show="!loading">Refresh</span>
                        <span x-show="loading">Memuat...</span>
                    </x-secondary-button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">No</th>
                            <th class="py-3 pr-4">Username</th>
                            <th class="py-3 pr-4">IP Address</th>
                            <th class="py-3 pr-4">Uptime</th>
                            <th class="py-3 pr-4">Interface</th>
                            <th class="py-3 pr-4">Download</th>
                            <th class="py-3 pr-4">Upload</th>
                            <th class="py-3 pr-4">Caller ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="!loading && filtered.length === 0">
                            <tr><td colspan="8" class="py-6 text-center text-gray-500">Tidak ada sesi PPPoE aktif.</td></tr>
                        </template>
                        <template x-for="(session, index) in paged" :key="session.username + index">
                            <tr>
                                <td class="py-2 pr-4 text-gray-600" x-text="(page - 1) * perPage + index + 1"></td>
                                <td class="py-2 pr-4 font-medium text-gray-900" x-text="session.username"></td>
                                <td class="py-2 pr-4 font-mono text-xs text-rose-600" x-text="session.address"></td>
                                <td class="py-2 pr-4 text-gray-600" x-text="session.uptime"></td>
                                <td class="py-2 pr-4 font-mono text-xs text-gray-500" x-text="session.interface"></td>
                                <td class="py-2 pr-4 font-medium text-emerald-600" x-text="formatBps(session.rx_bps)"></td>
                                <td class="py-2 pr-4 font-medium text-indigo-600" x-text="formatBps(session.tx_bps)"></td>
                                <td class="py-2 pr-4 text-gray-500" x-text="session.caller_id"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            @include('admin.mikrotik-routers._pagination')
        </x-panel>
    </div>

    @push('scripts')
        <script>
            function pppoeMonitor(routerId) {
                return {
                    sessions: [],
                    filtered: [],
                    search: '',
                    count: 0,
                    online: true,
                    loading: true,
                    message: '',
                    lastUpdated: null,
                    page: 1,
                    perPage: 25,
                    get totalPages() {
                        return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
                    },
                    get paged() {
                        const start = (this.page - 1) * this.perPage;
                        return this.filtered.slice(start, start + this.perPage);
                    },
                    goToPage(p) {
                        this.page = Math.min(Math.max(1, p), this.totalPages);
                    },
                    init() {
                        this.fetchData();
                        setInterval(() => this.fetchData(), 10000);
                    },
                    async fetchData() {
                        this.loading = true;
                        try {
                            const response = await fetch(`/mikrotik-routers/${routerId}/pppoe/data`, {
                                headers: { Accept: 'application/json' },
                            });
                            const data = await response.json();

                            this.sessions = data.sessions ?? [];
                            this.count = data.count ?? 0;
                            this.online = data.online ?? false;
                            this.message = data.message ?? '';
                            this.lastUpdated = new Date().toLocaleTimeString('id-ID');
                            this.applyFilter();
                        } catch (e) {
                            this.online = false;
                            this.message = 'Gagal memuat data monitoring.';
                        } finally {
                            this.loading = false;
                        }
                    },
                    applyFilter() {
                        const q = this.search.trim().toLowerCase();
                        this.filtered = q
                            ? this.sessions.filter((s) => s.username.toLowerCase().includes(q))
                            : this.sessions;
                        this.goToPage(this.page);
                    },
                    onSearch() {
                        this.page = 1;
                        this.applyFilter();
                    },
                    formatBps(bps) {
                        bps = Number(bps) || 0;
                        if (bps <= 0) return '0 bps';
                        const units = ['bps', 'Kbps', 'Mbps', 'Gbps'];
                        let value = bps;
                        let unitIndex = 0;
                        while (value >= 1000 && unitIndex < units.length - 1) {
                            value /= 1000;
                            unitIndex++;
                        }
                        return `${value.toFixed(unitIndex > 0 ? 2 : 0)} ${units[unitIndex]}`;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
