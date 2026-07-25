<?php

namespace App\Console\Commands\Notifications;

use App\Jobs\SendWhatsAppMessage;
use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Services\Billing\InvoiceService;
use App\Services\Notifications\ReminderStageMessages;
use App\Services\Notifications\TemplateRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendInvoiceReminders extends Command
{
    protected $signature = 'notifications:send-reminders';

    protected $description = 'Send email (4-stage) and WhatsApp (3-stage) reminders for unpaid/overdue invoices, per config: billing.reminder_offsets';

    public function handle(InvoiceService $invoiceService, TemplateRenderer $templateRenderer): int
    {
        $today = now();
        $offsets = config('billing.reminder_offsets');
        $emailsSent = 0;
        $whatsappSent = 0;

        Invoice::with('customer')
            ->whereIn('status', ['unpaid', 'overdue', 'partial'])
            ->chunkById(100, function ($invoices) use (&$emailsSent, &$whatsappSent, $invoiceService, $templateRenderer, $today, $offsets) {
                foreach ($invoices as $invoice) {
                    $customer = $invoice->customer;
                    if (! $customer) {
                        continue;
                    }

                    $daysUntilDue = $invoiceService->daysUntil($today, $invoice->due_date);

                    $emailStage = $offsets['email'][$daysUntilDue] ?? null;
                    if ($emailStage && $customer->email && NotificationLog::record($invoice, 'email', $emailStage)) {
                        Mail::to($customer->email)->queue(new InvoiceReminderMail($invoice, $emailStage));
                        $emailsSent++;
                    }

                    $waStage = $offsets['whatsapp'][$daysUntilDue] ?? null;
                    if ($waStage && $customer->phone && NotificationLog::record($invoice, 'whatsapp', $waStage)) {
                        SendWhatsAppMessage::dispatch($customer->phone, $this->buildWhatsAppMessage($invoice, $waStage, $invoiceService, $templateRenderer));
                        $whatsappSent++;
                    }
                }
            });

        $this->info("Reminders queued: {$emailsSent} email(s), {$whatsappSent} WhatsApp message(s).");

        return self::SUCCESS;
    }

    private function buildWhatsAppMessage(Invoice $invoice, string $stage, InvoiceService $invoiceService, TemplateRenderer $templateRenderer): string
    {
        $remaining = (float) $invoice->total_amount - $invoiceService->totalPaid($invoice);

        $rendered = $templateRenderer->render('whatsapp_invoice_reminder', [
            'customer_name' => $invoice->customer->name,
            'invoice_number' => $invoice->invoice_number,
            'due_date' => $invoice->due_date->translatedFormat('d F Y'),
            'total' => number_format($remaining, 0, ',', '.'),
            'pay_url' => $invoiceService->publicPayUrl($invoice),
            'pdf_url' => $invoiceService->publicPdfUrl($invoice),
            'app_name' => config('app.name'),
            'status_message' => ReminderStageMessages::message($stage, whatsapp: true),
            'bank_info' => TemplateRenderer::bankTransferInfo(),
        ]);

        return $rendered['body'];
    }
}
