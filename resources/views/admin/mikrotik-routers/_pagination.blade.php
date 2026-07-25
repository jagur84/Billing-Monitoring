<div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between">
    <p>
        Menampilkan <span x-text="filtered.length === 0 ? 0 : (page - 1) * perPage + 1"></span>
        - <span x-text="Math.min(page * perPage, filtered.length)"></span>
        dari <span x-text="filtered.length"></span> data
    </p>
    <div class="flex items-center gap-3">
        <x-secondary-button type="button" x-on:click="goToPage(page - 1)" x-bind:disabled="page <= 1" class="disabled:cursor-not-allowed disabled:opacity-40">
            &larr; Sebelumnya
        </x-secondary-button>
        <span>Halaman <span x-text="page"></span> / <span x-text="totalPages"></span></span>
        <x-secondary-button type="button" x-on:click="goToPage(page + 1)" x-bind:disabled="page >= totalPages" class="disabled:cursor-not-allowed disabled:opacity-40">
            Berikutnya &rarr;
        </x-secondary-button>
    </div>
</div>
