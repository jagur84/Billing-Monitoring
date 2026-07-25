@props(['label', 'value', 'icon' => 'chart-bar', 'accent' => 'indigo'])

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex items-center gap-4">
        <div @class([
            "flex h-11 w-11 shrink-0 items-center justify-center rounded-lg",
            "bg-indigo-50 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300" => $accent === 'indigo',
            "bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300" => $accent === 'emerald',
            "bg-amber-50 text-amber-600 dark:bg-amber-900/40 dark:text-amber-300" => $accent === 'amber',
            "bg-rose-50 text-rose-600 dark:bg-rose-900/40 dark:text-rose-300" => $accent === 'rose',
        ])>
            <x-dynamic-component :component="'heroicon-o-' . $icon" class="h-6 w-6" />
        </div>
        <div>
            <p class="text-sm text-gray-500">{{ $label }}</p>
            <p class="text-2xl font-semibold text-gray-900">{{ $value }}</p>
        </div>
    </div>
</div>
