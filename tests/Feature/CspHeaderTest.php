<?php

namespace Tests\Feature;

use Tests\TestCase;

class CspHeaderTest extends TestCase
{
    public function test_csp_header_is_present_on_a_filament_panel_response(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertHeader('Content-Security-Policy');

        $csp = $response->headers->get('Content-Security-Policy');

        // Filament (Alpine + Livewire) requires these to function under CSP.
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval'", $csp);
        $this->assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);

        // Nonces must stay disabled, otherwise 'unsafe-inline' is ignored.
        $this->assertStringNotContainsString('nonce-', $csp);
    }
}
