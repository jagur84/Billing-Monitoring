<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\Billing\InvoiceService;
use App\Services\Notifications\ReminderStageMessages;
use App\Services\Notifications\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private ?string $payUrl = null;
    private ?array $rendered = null;

    /**
     * @param string $stage one of reminder_h-3, reminder_h0, reminder_h+3, reminder_h+7
     */
    public function __construct(public Invoice $invoice, public string $stage)
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
        $remaining = (float) $this->invoice->total_amount - app(InvoiceService::class)->totalPaid($this->invoice);

        return $this->rendered ??= app(TemplateRenderer::class)->render('email_invoice_reminder', [
            'customer_name' => $this->invoice->customer->name,
            'invoice_number' => $this->invoice->invoice_number,
            'period' => "{$this->invoice->period_month}/{$this->invoice->period_year}",
            'due_date' => $this->invoice->due_date->translatedFormat('d F Y'),
            'total' => number_format($remaining, 0, ',', '.'),
            'pay_url' => $this->payUrl(),
            'app_name' => config('app.name'),
            'status_subject' => ReminderStageMessages::subject($this->stage, $this->invoice->invoice_number),
            'status_message' => ReminderStageMessages::message($this->stage),
            'bank_info' => TemplateRenderer::bankTransferInfo(),
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => app(InvoiceService::class)->generatePdf($this->invoice), "{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
