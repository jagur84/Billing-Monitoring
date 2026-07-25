<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Services\Notifications\TemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketOpenedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private ?array $rendered = null;

    public function __construct(public Ticket $ticket)
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
            ],
        );
    }

    private function rendered(): array
    {
        return $this->rendered ??= app(TemplateRenderer::class)->render('email_ticket_opened', [
            'customer_name' => $this->ticket->customer->name,
            'ticket_number' => $this->ticket->ticket_number,
            'ticket_subject' => $this->ticket->subject,
            'ticket_priority' => ucfirst($this->ticket->priority),
            'app_name' => config('app.name'),
        ]);
    }

    public function attachments(): array
    {
        return [];
    }
}
