<?php

namespace App\Services\Notifications;

use App\Models\BankAccount;
use App\Models\Setting;

class TemplateRenderer
{
    /**
     * Default subject/body per template key. Admin-saved Settings (template_{key}_subject /
     * template_{key}_body) override these when present; otherwise these defaults are used.
     */
    private const DEFAULTS = [
        'email_invoice_created' => [
            'subject' => 'Tagihan Baru {{invoice_number}}',
            'body' => "# Tagihan Baru\n\nHalo {{customer_name}},\n\nTagihan internet baru untuk periode **{{period}}** telah terbit.\n\nNomor Invoice: {{invoice_number}}\nJatuh Tempo: {{due_date}}\nTotal Tagihan: Rp {{total}}",
        ],
        'email_invoice_reminder' => [
            'subject' => '{{status_subject}}',
            'body' => "# Pengingat Tagihan\n\nHalo {{customer_name}},\n\nTagihan internet Anda untuk periode **{{period}}** {{status_message}}\n\nNomor Invoice: {{invoice_number}}\nJatuh Tempo: {{due_date}}\nTotal Tagihan: Rp {{total}}\n\n{{bank_info}}",
        ],
        'email_ticket_opened' => [
            'subject' => 'Tiket Diterima: {{ticket_number}}',
            'body' => "# Tiket Gangguan Diterima\n\nHalo {{customer_name}},\n\nLaporan gangguan Anda telah kami terima dengan rincian berikut:\n\nNomor Tiket: {{ticket_number}}\nSubjek: {{ticket_subject}}\nPrioritas: {{ticket_priority}}\n\nTim kami akan segera menindaklanjuti laporan ini.",
        ],
        'email_ticket_closed' => [
            'subject' => 'Tiket Selesai: {{ticket_number}}',
            'body' => "# Tiket Gangguan Selesai\n\nHalo {{customer_name}},\n\nTiket gangguan Anda berikut ini telah selesai ditangani:\n\nNomor Tiket: {{ticket_number}}\nSubjek: {{ticket_subject}}\n\nJika masalah masih berlanjut, silakan hubungi kami kembali.",
        ],
        'email_ticket_assigned' => [
            'subject' => 'Tiket Ditugaskan: {{ticket_number}}',
            'body' => "# Tiket Baru Ditugaskan Kepada Anda\n\nHalo {{assignee_name}},\n\nTiket gangguan berikut telah ditugaskan kepada Anda:\n\nNomor Tiket: {{ticket_number}}\nPelanggan: {{customer_name}}\nSubjek: {{ticket_subject}}\nPrioritas: {{ticket_priority}}\n\nSilakan segera ditindaklanjuti.",
        ],
        'whatsapp_invoice_reminder' => [
            'body' => "Halo {{customer_name}},\n\n{{status_message}}\n\nNo. Invoice: {{invoice_number}}\nJatuh Tempo: {{due_date}}\nTotal: Rp {{total}}\n\n{{bank_info}}\nBayar di sini: {{pay_url}}\nUnduh invoice (PDF): {{pdf_url}}\n\nTerima kasih,\n{{app_name}}",
        ],
        'whatsapp_ticket_opened' => [
            'body' => "Halo {{customer_name}},\n\nLaporan gangguan Anda (No. {{ticket_number}}: {{ticket_subject}}) telah kami terima dan akan segera ditindaklanjuti.\n\nTerima kasih,\n{{app_name}}",
        ],
        'whatsapp_ticket_closed' => [
            'body' => "Halo {{customer_name}},\n\nTiket gangguan Anda (No. {{ticket_number}}: {{ticket_subject}}) telah selesai ditangani.\n\nTerima kasih,\n{{app_name}}",
        ],
        'whatsapp_ticket_assigned' => [
            'body' => "Halo {{assignee_name}},\n\nTiket baru ditugaskan kepada Anda:\n\nNo. Tiket: {{ticket_number}}\nPelanggan: {{customer_name}}\nSubjek: {{ticket_subject}}\nPrioritas: {{ticket_priority}}\n\nSilakan segera ditindaklanjuti.\n\nTerima kasih,\n{{app_name}}",
        ],
    ];

    /**
     * Tokens that can carry attacker-influenced free text (a customer's name, a ticket
     * subject/number a customer typed, a staff assignee's name) — HTML-escaped when the
     * template is destined for an email, since the body is rendered as Markdown-to-HTML with
     * raw HTML passthrough allowed. Other tokens (URLs, formatted amounts/dates, app-generated
     * status text) are never escaped: they're either not attacker-controlled, or — in the case
     * of pay_url/pdf_url — escaping would corrupt the "&" in a signed URL's query string.
     */
    private const HTML_ESCAPED_TOKENS = ['customer_name', 'ticket_subject', 'ticket_number', 'assignee_name'];

    /**
     * @return array{subject: ?string, body: string}
     */
    public function render(string $key, array $tokens): array
    {
        $defaults = self::DEFAULTS[$key] ?? throw new \InvalidArgumentException("Unknown template key: {$key}");
        $isEmail = str_starts_with($key, 'email_');

        $subjectTemplate = Setting::get("template_{$key}_subject", $defaults['subject'] ?? null);
        $bodyTemplate = Setting::get("template_{$key}_body", $defaults['body']);

        return [
            // Subject is a plain email header, never HTML-rendered — left unescaped so it
            // reads naturally in the recipient's inbox.
            'subject' => $subjectTemplate ? $this->substitute($subjectTemplate, $tokens, false) : null,
            'body' => $this->substitute($bodyTemplate, $tokens, $isEmail),
        ];
    }

    public static function defaultTemplate(string $key): array
    {
        return self::DEFAULTS[$key] ?? throw new \InvalidArgumentException("Unknown template key: {$key}");
    }

    public static function keys(): array
    {
        return array_keys(self::DEFAULTS);
    }

    /**
     * A "transfer to <bank> <account>" line per active bank account (Pengaturan → Rekening
     * Bank), or '' when none are configured — callers just pass this straight into the
     * {{bank_info}} token without needing to check for blankness.
     */
    public static function bankTransferInfo(): string
    {
        $accounts = BankAccount::where('is_active', true)->orderBy('bank_name')->get();

        if ($accounts->isEmpty()) {
            return '';
        }

        $lines = $accounts->map(function (BankAccount $account) {
            $line = "{$account->bank_name}: {$account->account_number}";

            return $account->account_holder ? "{$line} a.n. {$account->account_holder}" : $line;
        });

        return "Transfer manual ke:\n".$lines->implode("\n");
    }

    private function substitute(string $template, array $tokens, bool $escapeForHtml): string
    {
        $replacements = [];
        foreach ($tokens as $token => $value) {
            $value = (string) $value;

            if ($escapeForHtml && in_array($token, self::HTML_ESCAPED_TOKENS, true)) {
                $value = e($value);
            }

            $replacements['{{'.$token.'}}'] = $value;
        }

        return strtr($template, $replacements);
    }
}
