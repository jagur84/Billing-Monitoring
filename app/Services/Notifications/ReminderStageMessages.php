<?php

namespace App\Services\Notifications;

class ReminderStageMessages
{
    private const SUBJECTS = [
        'reminder_h-3' => 'Pengingat: Tagihan {{invoice_number}} akan jatuh tempo',
        'reminder_h0' => 'Tagihan {{invoice_number}} jatuh tempo hari ini',
        'reminder_h+3' => 'Tagihan {{invoice_number}} telah lewat jatuh tempo',
        'reminder_h+7' => 'Peringatan Terakhir: Tagihan {{invoice_number}} akan diisolir',
    ];

    private const MESSAGES = [
        'reminder_h-3' => 'akan jatuh tempo dalam 3 hari.',
        'reminder_h0' => 'jatuh tempo **hari ini**.',
        'reminder_h+3' => 'sudah lewat jatuh tempo **3 hari**. Mohon segera lakukan pembayaran.',
        'reminder_h+7' => 'sudah lewat jatuh tempo **7 hari**. Layanan Anda akan segera diisolir jika belum dibayar.',
    ];

    public static function subject(string $stage, string $invoiceNumber): string
    {
        $template = self::SUBJECTS[$stage] ?? "Pengingat Tagihan {{invoice_number}}";

        return strtr($template, ['{{invoice_number}}' => $invoiceNumber]);
    }

    /**
     * @param bool $whatsapp WhatsApp uses single-asterisk *bold*, not Markdown's **bold**.
     */
    public static function message(string $stage, bool $whatsapp = false): string
    {
        $message = self::MESSAGES[$stage] ?? 'akan jatuh tempo.';

        return $whatsapp ? str_replace('**', '*', $message) : $message;
    }
}
