<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddSecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_csp_and_referrer_policy_are_present_over_plain_http(): void
    {
        $response = $this->get('/login');

        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_hsts_and_upgrade_insecure_requests_are_absent_over_plain_http(): void
    {
        // Forcing an HTTPS upgrade on a deployment that only serves HTTP (e.g. bare-IP access
        // with no certificate) would break every asset load — see AddSecurityHeaders.
        $response = $this->get('/login');

        $response->assertHeaderMissing('Strict-Transport-Security');
        $this->assertStringNotContainsString('upgrade-insecure-requests', $response->headers->get('Content-Security-Policy'));
    }

    public function test_hsts_and_upgrade_insecure_requests_are_present_over_https(): void
    {
        $response = $this->get('https://localhost/login');

        $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $this->assertStringContainsString('upgrade-insecure-requests', $response->headers->get('Content-Security-Policy'));
    }
}
