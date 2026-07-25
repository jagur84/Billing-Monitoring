@props(['href', 'active' => false, 'icon' => 'squares-2x2'])

<a href="{{ $href }}"
   @class([
        'group flex items-center gap-x-3 rounded-md px-3 py-2 text-sm font-medium transition',
        'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' => $active,
        'text-gray-600 hover:bg-gray-50 hover:text-gray-900' => ! $active,
   ])>
    <x-dynamic-component :component="'heroicon-o-' . $icon"
        @class([
            'h-5 w-5 shrink-0',
            'text-indigo-600 dark:text-indigo-300' => $active,
            'text-gray-400 group-hover:text-gray-600' => ! $active,
        ]) />
    {{ $slot }}
</a>
