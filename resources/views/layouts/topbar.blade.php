<header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white px-4 sm:px-6 lg:px-8">
    <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 lg:hidden">
        <x-heroicon-o-bars-3 class="h-6 w-6" />
    </button>

    <div class="flex flex-1 items-center justify-end gap-4">
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-2 rounded-full text-sm focus:outline-none">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                        {{ strtoupper(substr(auth()->user()->name ?? '?', 0, 1)) }}
                    </span>
                    <span class="hidden sm:block font-medium text-gray-700">{{ auth()->user()->name }}</span>
                    <x-heroicon-o-chevron-down class="hidden sm:block h-4 w-4 text-gray-400" />
                </button>
            </x-slot>

            <x-slot name="content">
                <x-dropdown-link :href="route('profile.edit')">
                    {{ __('Profil') }}
                </x-dropdown-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('Keluar') }}
                    </x-dropdown-link>
                </form>
            </x-slot>
        </x-dropdown>
    </div>
</header>
