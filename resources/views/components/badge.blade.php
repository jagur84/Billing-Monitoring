@props(['color' => 'gray'])

<span @class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
    'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => $color === 'gray',
    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $color === 'green',
    'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' => $color === 'amber',
    'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' => $color === 'red',
    'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' => $color === 'indigo',
    'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' => $color === 'sky',
])>
    {{ $slot }}
</span>
