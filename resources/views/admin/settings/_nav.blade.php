@php
    $tabs = [
        'settings.general' => 'Aplikasi',
        'settings.smtp' => 'SMTP',
        'settings.tripay' => 'Tripay',
        'settings.whatsapp' => 'WhatsApp',
        'settings.billing' => 'Tagihan & Reminder',
        'settings.bank-accounts.index' => 'Rekening Bank',
        'settings.templates' => 'Template Notifikasi',
    ];
@endphp
<div class="mb-6 border-b border-gray-200">
    <nav class="-mb-px flex gap-6 overflow-x-auto">
        @foreach ($tabs as $route => $label)
            <a href="{{ route($route) }}"
               @class([
                    'whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium',
                    'border-indigo-600 text-indigo-600' => request()->routeIs($route),
                    'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => ! request()->routeIs($route),
               ])>
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
