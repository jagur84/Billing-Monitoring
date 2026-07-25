<x-mail::message>
{!! $body !!}

@isset($payUrl)
<x-mail::button :url="$payUrl">
Bayar Sekarang
</x-mail::button>
@endisset

{{ config('app.name') }}
</x-mail::message>
