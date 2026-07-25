@props(['title' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
            @if ($title)
                <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
            @endif
            @isset($actions)
                <div>{{ $actions }}</div>
            @endisset
        </div>
    @endif
    <div class="p-5">
        {{ $slot }}
    </div>
</div>
