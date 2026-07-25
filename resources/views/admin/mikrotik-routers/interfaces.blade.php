<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Interface" subtitle="{{ $router->name }} • {{ $router->host }}:{{ $router->port }}">
            <x-slot name="actions">
                <a href="{{ route('mikrotik-routers.secrets.index', $router) }}">
                    <x-secondary-button type="button">PPPoE Secret</x-secondary-button>
                </a>
                <a href="{{ route('mikrotik-routers.routing', $router) }}">
                    <x-secondary-button type="button">IP Route</x-secondary-button>
                </a>
                <a href="{{ route('mikrotik-routers.pppoe', $router) }}">
                    <x-secondary-button type="button">PPPoE</x-secondary-button>
                </a>
                <a href="{{ route('mikrotik-routers.index') }}">
                    <x-secondary-button type="button">&larr; Kembali</x-secondary-button>
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div x-data="interfaceMonitor({{ $router->id }})" x-init="init()" class="space-y-6">
        <x-panel>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Daftar Interface</h3>
                    <p class="text-sm text-gray-500">Total <span x-text="count"></span> interface ditemukan</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                          :class="online ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                        <span class="h-2 w-2 rounded-full" :class="online ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                        <span x-text="online ? 'API Online' : 'API Offline'"></span>
                    </span>
                    <x-secondary-button type="button" x-on:click="fetchData()">
                        <span x-show="!loading">Refresh</span>
                        <span x-show="loading">Memuat...</span>
                    </x-secondary-button>
                </div>
            </div>

            <div class="mb-4 flex items-center gap-2">
                <x-text-input type="text" x-model="search" x-on:input="onSearch()" placeholder="Cari nama interface..." class="w-full max-w-sm" />
                <x-secondary-button type="button" x-on:click="onSearch()">Cari</x-secondary-button>
            </div>

            <template x-if="!online">
                <div class="mb-4 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="message"></div>
            </template>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">No</th>
                            <th class="py-3 pr-4">Interface</th>
                            <th class="py-3 pr-4">Type</th>
                            <th class="py-3 pr-4">MAC Address</th>
                            <th class="py-3 pr-4">MTU</th>
                            <th class="py-3 pr-4">RX</th>
                            <th class="py-3 pr-4">TX</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Comment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="!loading && filtered.length === 0">
                            <tr><td colspan="9" class="py-6 text-center text-gray-500">Tidak ada interface ditemukan.</td></tr>
                        </template>
                        <template x-for="(iface, index) in paged" :key="index">
                            <tr>
                                <td class="py-2 pr-4 text-gray-600" x-text="(page - 1) * perPage + index + 1"></td>
                                <td class="py-2 pr-4">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-4 w-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21M6.75 6.75h10.5v10.5H6.75V6.75Z" />
                                            </svg>
                                        </span>
                                        <span class="font-medium text-gray-900" x-text="iface.name"></span>
                                    </div>
                                </td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full border border-gray-300 px-2.5 py-0.5 text-xs font-medium text-gray-600" x-text="iface.type"></span>
                                </td>
                                <td class="py-2 pr-4 font-mono text-xs text-rose-600" x-text="iface.mac_address"></td>
                                <td class="py-2 pr-4 text-gray-600" x-text="iface.mtu"></td>
                                <td class="py-2 pr-4 font-medium text-emerald-600">↓ <span x-text="formatBytes(iface.rx_bytes)"></span></td>
                                <td class="py-2 pr-4 font-medium text-indigo-600">↑ <span x-text="formatBytes(iface.tx_bytes)"></span></td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                          :class="iface.running ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-200 text-gray-600'"
                                          x-text="iface.running ? 'Running' : 'Not Running'"></span>
                                </td>
                                <td class="py-2 pr-4 text-gray-500" x-text="iface.comment && iface.comment !== '' ? iface.comment : '-'"></td>
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
            function interfaceMonitor(routerId) {
                return {
                    interfaces: [],
                    filtered: [],
                    search: '',
                    count: 0,
                    online: true,
                    loading: true,
                    message: '',
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
                        setInterval(() => this.fetchData(), 15000);
                    },
                    async fetchData() {
                        this.loading = true;
                        try {
                            const response = await fetch(`/mikrotik-routers/${routerId}/interfaces/data`, {
                                headers: { Accept: 'application/json' },
                            });
                            const data = await response.json();

                            this.interfaces = data.interfaces ?? [];
                            this.count = data.count ?? 0;
                            this.online = data.online ?? false;
                            this.message = data.message ?? '';
                            this.applyFilter();
                        } catch (e) {
                            this.online = false;
                            this.message = 'Gagal memuat data interface.';
                        } finally {
                            this.loading = false;
                        }
                    },
                    applyFilter() {
                        const q = this.search.trim().toLowerCase();
                        this.filtered = q
                            ? this.interfaces.filter((i) => i.name.toLowerCase().includes(q))
                            : this.interfaces;
                        this.goToPage(this.page);
                    },
                    onSearch() {
                        this.page = 1;
                        this.applyFilter();
                    },
                    formatBytes(bytes) {
                        bytes = Number(bytes) || 0;
                        if (bytes <= 0) return '0 B';
                        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
                        let value = bytes;
                        let unitIndex = 0;
                        while (value >= 1024 && unitIndex < units.length - 1) {
                            value /= 1024;
                            unitIndex++;
                        }
                        return `${value.toFixed(unitIndex > 0 ? 2 : 0)} ${units[unitIndex]}`;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
