<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\Billing\InvoiceService;
use App\Services\Notifications\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private ?string $payUrl = null;
    private ?array $rendered = null;

    public function __construct(public Invoice $invoice)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->rendered()['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.templated',
            with: [
                'body' => $this->rendered()['body'],
                'payUrl' => $this->payUrl(),
            ],
        );
    }

    private function payUrl(): string
    {
        return $this->payUrl ??= app(InvoiceService::class)->publicPayUrl($this->invoice);
    }

    private function rendered(): array
    {
        return $this->rendered ??= app(TemplateRenderer::class)->render('email_invoice_created', [
            'customer_name' => $this->invoice->customer->name,
            'invoice_number' => $this->invoice->invoice_number,
            'period' => "{$this->invoice->period_month}/{$this->invoice->period_year}",
            'due_date' => $this->invoice->due_date->translatedFormat('d F Y'),
            'total' => number_format($this->invoice->total_amount, 0, ',', '.'),
            'pay_url' => $this->payUrl(),
            'app_name' => config('app.name'),
        ]);
    }

    public function attachments(): array
    {
        return [];
    }
}
