@php
    $labels = [
        'email_invoice_created' => 'Email: Tagihan Baru',
        'email_invoice_reminder' => 'Email: Reminder Tagihan',
        'email_ticket_opened' => 'Email: Tiket Dibuka',
        'email_ticket_closed' => 'Email: Tiket Selesai',
        'email_ticket_assigned' => 'Email: Tiket Ditugaskan',
        'whatsapp_invoice_reminder' => 'WhatsApp: Reminder Tagihan',
        'whatsapp_ticket_opened' => 'WhatsApp: Tiket Dibuka',
        'whatsapp_ticket_closed' => 'WhatsApp: Tiket Selesai',
        'whatsapp_ticket_assigned' => 'WhatsApp: Tiket Ditugaskan',
    ];
    $tokens = [
        'email_invoice_created' => ['customer_name', 'invoice_number', 'period', 'due_date', 'total', 'pay_url', 'app_name'],
        'email_invoice_reminder' => ['customer_name', 'invoice_number', 'period', 'due_date', 'total', 'pay_url', 'app_name', 'status_subject (subjek saja)', 'status_message', 'bank_info'],
        'email_ticket_opened' => ['customer_name', 'ticket_number', 'ticket_subject', 'ticket_priority', 'app_name'],
        'email_ticket_closed' => ['customer_name', 'ticket_number', 'ticket_subject', 'app_name'],
        'email_ticket_assigned' => ['assignee_name', 'customer_name', 'ticket_number', 'ticket_subject', 'ticket_priority', 'app_name'],
        'whatsapp_invoice_reminder' => ['customer_name', 'invoice_number', 'due_date', 'total', 'pay_url', 'pdf_url', 'app_name', 'status_message', 'bank_info'],
        'whatsapp_ticket_opened' => ['customer_name', 'ticket_number', 'ticket_subject', 'app_name'],
        'whatsapp_ticket_closed' => ['customer_name', 'ticket_number', 'ticket_subject', 'app_name'],
        'whatsapp_ticket_assigned' => ['assignee_name', 'customer_name', 'ticket_number', 'ticket_subject', 'ticket_priority', 'app_name'],
    ];
    $formatToken = fn ($t) => '{{'.$t.'}}';
@endphp
<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Pengaturan" subtitle="SMTP, Tripay, WhatsApp, dan template notifikasi." />
    </x-slot>

    @include('admin.settings._nav')

    @if (session('status'))
        <div class="mb-4 rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
    @endif

    <form action="{{ route('settings.templates.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        @foreach ($templates as $key => $tpl)
            <x-panel :title="$labels[$key]">
                <div class="space-y-4">
                    @if ($tpl['subject'] !== null)
                        <div>
                            <x-input-label :for="'template_'.$key.'_subject'" value="Subjek" />
                            <x-text-input :id="'template_'.$key.'_subject'" :name="'template_'.$key.'_subject'" type="text" class="mt-1 block w-full" value="{{ old('template_'.$key.'_subject', $tpl['subject']) }}" />
                        </div>
                    @endif
                    <div>
                        <x-input-label :for="'template_'.$key.'_body'" value="Isi Pesan" />
                        <x-textarea-input :id="'template_'.$key.'_body'" :name="'template_'.$key.'_body'" rows="6" class="mt-1 block w-full font-mono text-sm">{{ old('template_'.$key.'_body', $tpl['body']) }}</x-textarea-input>
                    </div>
                    <p class="text-xs text-gray-500">
                        Token yang tersedia: @foreach ($tokens[$key] as $token) <code class="rounded bg-gray-100 px-1">{{ $formatToken($token) }}</code>@if(!$loop->last), @endif @endforeach
                    </p>
                </div>
            </x-panel>
        @endforeach

        <div class="flex justify-end">
            <x-primary-button>Simpan Semua Template</x-primary-button>
        </div>
    </form>
</x-app-layout>
