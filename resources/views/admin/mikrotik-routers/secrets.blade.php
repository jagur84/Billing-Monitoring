<x-app-layout>
    <x-slot name="header">
        <x-page-header title="PPPoE Secret" subtitle="{{ $router->name }} • {{ $router->host }}:{{ $router->port }}">
            <x-slot name="actions">
                <x-primary-button type="button" x-data="" x-on:click="$dispatch('open-modal', 'add-secret')">+ Tambah Secret</x-primary-button>
                <a href="{{ route('mikrotik-routers.pppoe', $router) }}">
                    <x-secondary-button type="button">Monitoring</x-secondary-button>
                </a>
                <a href="{{ route('mikrotik-routers.routing', $router) }}">
                    <x-secondary-button type="button">IP Route</x-secondary-button>
                </a>
                <a href="{{ route('mikrotik-routers.interfaces', $router) }}">
                    <x-secondary-button type="button">Interface</x-secondary-button>
                </a>
                <a href="{{ route('mikrotik-routers.index') }}">
                    <x-secondary-button type="button">&larr; Kembali</x-secondary-button>
                </a>
            </x-slot>
        </x-page-header>
    </x-slot>

    <div x-data="pppoeSecrets({{ $router->id }})" x-init="init()" class="space-y-6">
        @if (session('status'))
            <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
        @endif

        <x-panel>
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">Daftar Secret</h3>
                    <p class="text-sm text-gray-500">
                        <span x-text="total"></span> Secret &bull; <span x-text="onlineCount"></span> Online
                    </p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                      :class="online ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                    <span class="h-2 w-2 rounded-full" :class="online ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                    <span x-text="online ? 'API Online' : 'API Offline'"></span>
                </span>
            </div>

            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <x-text-input type="text" x-model="search" x-on:input="onSearch()" placeholder="Cari username PPPoE..." class="w-full sm:max-w-sm" />
                <x-secondary-button type="button" x-on:click="onSearch()">Cari</x-secondary-button>
                <x-secondary-button type="button" x-on:click="fetchData()">
                    <span x-show="!loading">Refresh</span>
                    <span x-show="loading">Memuat...</span>
                </x-secondary-button>
                <button type="button" x-on:click="toggleOnlineOnly()"
                        class="inline-flex items-center rounded-md px-4 py-2 text-xs font-semibold uppercase tracking-widest shadow-sm transition ease-in-out duration-150"
                        :class="onlineOnly ? 'bg-emerald-600 text-white' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50'">
                    Online
                </button>
            </div>

            <template x-if="!online">
                <div class="mb-4 rounded-md bg-rose-50 px-4 py-3 text-sm text-rose-700" x-text="message"></div>
            </template>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <th class="py-3 pr-4">No</th>
                            <th class="py-3 pr-4">Status</th>
                            <th class="py-3 pr-4">Username</th>
                            <th class="py-3 pr-4">Password</th>
                            <th class="py-3 pr-4">Profile</th>
                            <th class="py-3 pr-4">Service</th>
                            <th class="py-3 pr-4">Comment</th>
                            <th class="py-3 pr-4">Last Logout</th>
                            <th class="py-3 pr-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-if="!loading && filtered.length === 0">
                            <tr><td colspan="9" class="py-6 text-center text-gray-500">Tidak ada secret ditemukan.</td></tr>
                        </template>
                        <template x-for="(secret, index) in paged" :key="secret.id">
                            <tr>
                                <td class="py-2 pr-4 text-gray-600" x-text="(page - 1) * perPage + index + 1"></td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                          :class="secret.disabled ? 'bg-gray-200 text-gray-600' : (secret.online ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700')"
                                          x-text="secret.disabled ? 'Nonaktif' : (secret.online ? 'Online' : 'Offline')"></span>
                                </td>
                                <td class="py-2 pr-4 font-medium text-gray-900" x-text="secret.username"></td>
                                <td class="py-2 pr-4 font-mono text-xs text-rose-600" x-text="secret.password"></td>
                                <td class="py-2 pr-4">
                                    <span class="inline-flex items-center rounded-full border border-gray-300 px-2.5 py-0.5 text-xs font-medium text-gray-600" x-text="secret.profile"></span>
                                </td>
                                <td class="py-2 pr-4 text-gray-600" x-text="secret.service"></td>
                                <td class="py-2 pr-4 text-gray-500" x-text="secret.comment && secret.comment !== '' ? secret.comment : '-'"></td>
                                <td class="py-2 pr-4 text-gray-600" x-text="secret.last_logged_out"></td>
                                <td class="py-2 pr-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button type="button" x-on:click="openEdit(secret)" title="Edit"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-amber-500 text-white hover:bg-amber-600">
                                            <x-dynamic-component component="heroicon-o-pencil-square" class="h-4 w-4" />
                                        </button>
                                        <form method="POST" x-bind:action="toggleUrl(secret)" x-on:submit="if (! confirm((secret.disabled ? 'Aktifkan' : 'Nonaktifkan') + ' secret ' + secret.username + '?')) $event.preventDefault()">
                                            @csrf
                                            <input type="hidden" name="disabled" x-bind:value="secret.disabled ? '0' : '1'">
                                            <button type="submit" title="Aktif/Nonaktifkan"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-gray-500 text-white hover:bg-gray-600">
                                                <x-dynamic-component component="heroicon-o-lock-closed" class="h-4 w-4" />
                                            </button>
                                        </form>
                                        <form method="POST" x-bind:action="deleteUrl(secret)" x-on:submit="if (! confirm('Hapus secret ' + secret.username + '? Tindakan ini tidak bisa dibatalkan.')) $event.preventDefault()">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Hapus"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-rose-600 text-white hover:bg-rose-700">
                                                <x-dynamic-component component="heroicon-o-trash" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            @include('admin.mikrotik-routers._pagination')
        </x-panel>

    {{-- Tambah Secret --}}
    <x-modal name="add-secret" :show="$errors->any() && old('_form') === 'add-secret'" maxWidth="lg">
        <form method="POST" action="{{ route('mikrotik-routers.secrets.store', $router) }}" class="p-6">
            @csrf
            <input type="hidden" name="_form" value="add-secret">

            <h2 class="text-lg font-medium text-gray-900">Tambah PPPoE Secret</h2>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="add_username" value="Username PPPoE" />
                    <x-text-input id="add_username" name="username" type="text" class="mt-1 block w-full" value="{{ old('username') }}" required />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="add_password" value="Password PPPoE" />
                    <x-text-input id="add_password" name="password" type="text" class="mt-1 block w-full" value="{{ old('password') }}" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
                <div>
                    <x-input-label for="add_profile" value="Profile" />
                    <x-select-input id="add_profile" name="profile" class="mt-1 block w-full">
                        @forelse ($profiles as $profile)
                            <option value="{{ $profile }}" @selected(old('profile') === $profile)>{{ $profile }}</option>
                        @empty
                            <option value="default">default</option>
                        @endforelse
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="add_service" value="Service" />
                    <x-select-input id="add_service" name="service" class="mt-1 block w-full">
                        @foreach (['any', 'pppoe', 'pptp', 'l2tp', 'ovpn', 'sstp'] as $service)
                            <option value="{{ $service }}" @selected(old('service', 'any') === $service)>{{ $service }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="add_comment" value="Comment" />
                    <textarea id="add_comment" name="comment" rows="2" placeholder="Contoh: Pelanggan Area A"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('comment') }}</textarea>
                </div>
                <div class="sm:col-span-2" x-data="{ choice: '{{ old('customer_choice', '') }}' }">
                    <x-input-label for="add_customer_choice" value="Kaitkan dengan Pelanggan (opsional)" />
                    <x-select-input id="add_customer_choice" name="customer_choice" class="mt-1 block w-full" x-model="choice">
                        <option value="">Tidak dikaitkan</option>
                        <option value="__new__">+ Buat pelanggan baru</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected(old('customer_choice') == $customer->id)>{{ $customer->name }} ({{ $customer->customer_code }})</option>
                        @endforeach
                    </x-select-input>

                    <div class="mt-3" x-show="choice === '__new__'" x-cloak>
                        <x-input-label for="add_new_customer_name" value="Nama Pelanggan Baru" />
                        <x-text-input id="add_new_customer_name" name="new_customer_name" type="text" class="mt-1 block w-full" value="{{ old('new_customer_name') }}" />
                        <x-input-error :messages="$errors->get('new_customer_name')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Kembali</x-secondary-button>
                <x-primary-button type="submit">Simpan Secret</x-primary-button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Secret --}}
    <x-modal name="edit-secret" maxWidth="lg">
        <form method="POST" x-bind:action="editingSecret.url" class="p-6">
            @csrf
            @method('PUT')
            <input type="hidden" name="original_username" x-bind:value="editingSecret.username">

            <h2 class="text-lg font-medium text-gray-900">Edit PPPoE Secret</h2>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="edit_username" value="Username PPPoE" />
                    <x-text-input id="edit_username" name="username" type="text" class="mt-1 block w-full" x-model="editingSecret.username" required />
                </div>
                <div>
                    <x-input-label for="edit_password" value="Password PPPoE" />
                    <x-text-input id="edit_password" name="password" type="text" class="mt-1 block w-full" x-model="editingSecret.password" required />
                </div>
                <div>
                    <x-input-label for="edit_profile" value="Profile" />
                    <x-select-input id="edit_profile" name="profile" class="mt-1 block w-full" x-model="editingSecret.profile">
                        @forelse ($profiles as $profile)
                            <option value="{{ $profile }}">{{ $profile }}</option>
                        @empty
                            <option value="default">default</option>
                        @endforelse
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="edit_disabled" value="Status" />
                    <x-select-input id="edit_disabled" name="disabled" class="mt-1 block w-full" x-model="editingSecret.disabled">
                        <option value="0">Aktif</option>
                        <option value="1">Nonaktif</option>
                    </x-select-input>
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit_comment" value="Comment" />
                    <textarea id="edit_comment" name="comment" rows="2" x-model="editingSecret.comment"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                <div>
                    <x-input-label for="edit_service" value="Service" />
                    <x-select-input id="edit_service" name="service" class="mt-1 block w-full" x-model="editingSecret.service">
                        @foreach (['any', 'pppoe', 'pptp', 'l2tp', 'ovpn', 'sstp'] as $service)
                            <option value="{{ $service }}">{{ $service }}</option>
                        @endforeach
                    </x-select-input>
                </div>
                <div>
                    <x-input-label for="edit_last_logged_out" value="Last Logged Out" />
                    <x-text-input id="edit_last_logged_out" type="text" class="mt-1 block w-full bg-gray-50" x-bind:value="editingSecret.last_logged_out" disabled />
                </div>
                <div class="sm:col-span-2">
                    <x-input-label for="edit_customer_choice" value="Kaitkan dengan Pelanggan (opsional)" />
                    <x-select-input id="edit_customer_choice" name="customer_choice" class="mt-1 block w-full" x-model="editingSecret.customer_choice">
                        <option value="">Tidak dikaitkan</option>
                        <option value="__new__">+ Buat pelanggan baru</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->customer_code }})</option>
                        @endforeach
                    </x-select-input>

                    <div class="mt-3" x-show="editingSecret.customer_choice === '__new__'" x-cloak>
                        <x-input-label for="edit_new_customer_name" value="Nama Pelanggan Baru" />
                        <x-text-input id="edit_new_customer_name" name="new_customer_name" type="text" class="mt-1 block w-full" x-model="editingSecret.new_customer_name" />
                    </div>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">&larr; Kembali</x-secondary-button>
                <x-primary-button type="submit">Update Secret</x-primary-button>
            </div>
        </form>
    </x-modal>
    </div>

    @push('scripts')
        <script>
            function pppoeSecrets(routerId) {
                return {
                    secrets: [],
                    filtered: [],
                    search: '',
                    onlineOnly: false,
                    total: 0,
                    onlineCount: 0,
                    online: true,
                    loading: true,
                    message: '',
                    page: 1,
                    perPage: 25,
                    editingSecret: {
                        id: '', username: '', password: '', profile: '', service: 'any',
                        comment: '', disabled: '0', last_logged_out: '', url: '',
                        customer_choice: '', new_customer_name: '',
                    },
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
                            const response = await fetch(`/mikrotik-routers/${routerId}/secrets/data`, {
                                headers: { Accept: 'application/json' },
                            });
                            const data = await response.json();

                            this.secrets = data.secrets ?? [];
                            this.total = data.total ?? 0;
                            this.onlineCount = data.onlineCount ?? 0;
                            this.online = data.online ?? false;
                            this.message = data.message ?? '';
                            this.applyFilter();
                        } catch (e) {
                            this.online = false;
                            this.message = 'Gagal memuat data secret.';
                        } finally {
                            this.loading = false;
                        }
                    },
                    applyFilter() {
                        let list = this.secrets;
                        if (this.onlineOnly) {
                            list = list.filter((s) => s.online);
                        }
                        const q = this.search.trim().toLowerCase();
                        if (q) {
                            list = list.filter((s) => s.username.toLowerCase().includes(q));
                        }
                        this.filtered = list;
                        this.goToPage(this.page);
                    },
                    onSearch() {
                        this.page = 1;
                        this.applyFilter();
                    },
                    toggleOnlineOnly() {
                        this.onlineOnly = !this.onlineOnly;
                        this.onSearch();
                    },
                    openEdit(secret) {
                        this.editingSecret = {
                            ...secret,
                            disabled: secret.disabled ? '1' : '0',
                            url: `/mikrotik-routers/${routerId}/secrets/${encodeURIComponent(secret.id)}`,
                            customer_choice: secret.customer_id ? String(secret.customer_id) : '',
                            new_customer_name: '',
                        };
                        this.$dispatch('open-modal', 'edit-secret');
                    },
                    toggleUrl(secret) {
                        return `/mikrotik-routers/${routerId}/secrets/${encodeURIComponent(secret.id)}/toggle`;
                    },
                    deleteUrl(secret) {
                        return `/mikrotik-routers/${routerId}/secrets/${encodeURIComponent(secret.id)}`;
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
