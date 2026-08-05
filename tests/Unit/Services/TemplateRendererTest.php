<?php

namespace Tests\Unit\Services;

use App\Models\Setting;
use App\Services\Notifications\TemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_body_escapes_html_from_attacker_influenced_tokens(): void
    {
        $rendered = app(TemplateRenderer::class)->render('email_ticket_opened', [
            'customer_name' => '<script>alert(1)</script>',
            'ticket_number' => 'TCK-1',
            'ticket_subject' => '<img src=x onerror=alert(2)>',
            'ticket_priority' => 'high',
        ]);

        $this->assertStringNotContainsString('<script>', $rendered['body']);
        $this->assertStringNotContainsString('<img', $rendered['body']);
        $this->assertStringContainsString('&lt;script&gt;', $rendered['body']);
    }

    public function test_email_subject_is_never_html_escaped(): void
    {
        $rendered = app(TemplateRenderer::class)->render('email_ticket_opened', [
            'customer_name' => 'Bob & Co',
            'ticket_number' => 'TCK-1 & Co',
            'ticket_subject' => 'Subject',
            'ticket_priority' => 'high',
        ]);

        $this->assertStringContainsString('TCK-1 & Co', $rendered['subject']);
    }

    public function test_whatsapp_body_is_never_html_escaped(): void
    {
        $rendered = app(TemplateRenderer::class)->render('whatsapp_ticket_opened', [
            'customer_name' => 'Bob & Co <VIP>',
            'ticket_number' => 'TCK-1',
            'ticket_subject' => 'Subject',
            'app_name' => 'Billing',
        ]);

        $this->assertStringContainsString('Bob & Co <VIP>', $rendered['body']);
    }

    public function test_signed_urls_are_not_escaped_even_in_an_email_body(): void
    {
        // An admin-customized email body that embeds {{pay_url}} directly (the stock templates
        // never do — pay_url is normally passed as a separate mail-button variable — but a
        // custom template could). The "&" in a signed URL's query string must survive intact;
        // escaping it here would corrupt the link.
        Setting::set('template_email_invoice_reminder_body', 'Bayar di: {{pay_url}}');

        $rendered = app(TemplateRenderer::class)->render('email_invoice_reminder', [
            'customer_name' => 'Test',
            'status_message' => 'belum dibayar',
            'invoice_number' => 'INV-1',
            'due_date' => '10 Agustus 2026',
            'total' => '150.000',
            'bank_info' => '',
            'period' => '8/2026',
            'pay_url' => 'https://example.com/pay/5?signature=abc&expires=123',
            'app_name' => 'Billing',
        ]);

        $this->assertStringContainsString('https://example.com/pay/5?signature=abc&expires=123', $rendered['body']);
    }
}
