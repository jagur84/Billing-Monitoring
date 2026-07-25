<x-app-layout>
    <x-slot name="header">
        <x-page-header title="IP Route" subtitle="{{ $router->name }} • {{ $router->host }}:{{ $router->port }}">
            <x-slot name="actions">
                <a href="{{ route('mikrotik-routers.secrets.index', $router) }}">
                    <x-secondary-button type="button">PPPoE Secret</x-secondary-button>
                </a>
                <a href="{{ route('mikrotik-routers.interfaces', $router) }}">
                    <x-secondary-button type="button">Interface</x-secondary-button>
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

    <div x-data="ipRouteMonitor({{ $router->id }})" x-init="init()" class="space-y-6">
        <x-panel>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Daftar Routing</h3>
                    <p class="text-sm text-gray-500">Total <span x-text="count"></span> route ditemukan</p>
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
                <x-text-input type="text" x-model="search" x-on:input="onSearch()" placeholder="Cari destination atau gateway..." class="w-full max-w-sm" />
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
                            <th class="py-3 pr-4">Destination</th>
                            <th class="py-3 pr-4">Gateway</th>
                            <th class="py-3 pr-4">Distance</th>
                            <th class="py-3 pr-4">Routing Table</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Comment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="!loading && filtered.length === 0">
                            <tr><td colspan="7" class="py-6 text-center text-gray-500">Tidak ada route ditemukan.</td></tr>
                        </template>
                        <template x-for="(route, index) in paged" :key="index">
                            <tr>
                                <td class="py-2 pr-4 text-gray-600" x-text="(page - 1) * perPage + index + 1"></td>
                                <td class="py-2 pr-4 font-mono text-xs text-gray-900" x-text="route.destination"></td>
                                <td class="py-2 pr-4 font-mono text-xs text-gray-600" x-text="route.gateway"></td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center justify-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700" x-text="route.distance"></span>
                                </td>
                                <td class="py-2 pr-4 text-gray-600" x-text="route.routing_table"></td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                          :class="{
                                              'bg-emerald-100 text-emerald-700': route.status === 'active',
                                              'bg-rose-100 text-rose-700': route.status === 'disabled',
                                              'bg-gray-100 text-gray-700': route.status === 'inactive',
                                          }"
                                          x-text="route.status === 'active' ? 'Active' : (route.status === 'disabled' ? 'Disabled' : 'Inactive')"></span>
                                    <span class="ml-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                          :class="route.type === 'dynamic' ? 'bg-sky-100 text-sky-700' : 'bg-indigo-100 text-indigo-700'"
                                          x-text="route.type === 'dynamic' ? 'Dynamic' : 'Static'"></span>
                                </td>
                                <td class="py-2 pr-4 text-gray-500" x-text="route.comment && route.comment !== '' ? route.comment : '-'"></td>
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
            function ipRouteMonitor(routerId) {
                return {
                    routes: [],
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
                            const response = await fetch(`/mikrotik-routers/${routerId}/routing/data`, {
                                headers: { Accept: 'application/json' },
                            });
                            const data = await response.json();

                            this.routes = data.routes ?? [];
                            this.count = data.count ?? 0;
                            this.online = data.online ?? false;
                            this.message = data.message ?? '';
                            this.applyFilter();
                        } catch (e) {
                            this.online = false;
                            this.message = 'Gagal memuat data routing.';
                        } finally {
                            this.loading = false;
                        }
                    },
                    applyFilter() {
                        const q = this.search.trim().toLowerCase();
                        this.filtered = q
                            ? this.routes.filter((r) =>
                                  r.destination.toLowerCase().includes(q) || r.gateway.toLowerCase().includes(q)
                              )
                            : this.routes;
                        this.goToPage(this.page);
                    },
                    onSearch() {
                        this.page = 1;
                        this.applyFilter();
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
