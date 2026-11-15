<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_responses(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Content-Security-Policy');
    }

    /**
     * Apache serves CSS, JS and images without booting Laravel, so the same
     * headers are declared in .htaccess. When the two declarations drift apart
     * the browser enforces the intersection of both policies and silently
     * blocks anything only one of them allows — a blocked font or script that
     * looks like "the CDN is down".
     */
    public function test_htaccess_content_security_policy_matches_the_middleware(): void
    {
        $htaccess = (string) @file_get_contents(base_path('.htaccess'));
        $this->assertNotSame('', $htaccess, '.htaccess is missing from the repository.');

        $this->assertSame(
            1,
            preg_match('/Header\s+always\s+set\s+Content-Security-Policy\s+"([^"]+)"/i', $htaccess, $matches),
            'The .htaccess file must declare exactly one Content-Security-Policy header.'
        );

        $response = $this->get(route('home'));
        $response->assertStatus(200);

        $this->assertSame(
            $response->headers->get('Content-Security-Policy'),
            $matches[1],
            '.htaccess and SecurityHeaders.php disagree about the Content-Security-Policy.'
        );
    }

    public function test_htaccess_content_security_policy_does_not_allow_eval_or_wildcard_defaults(): void
    {
        $htaccess = (string) @file_get_contents(base_path('.htaccess'));

        $this->assertSame(1, preg_match('/Header\s+always\s+set\s+Content-Security-Policy\s+"([^"]+)"/i', $htaccess, $matches));

        $policy = $matches[1];

        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
        $this->assertStringNotContainsString('default-src \'self\' data:', $policy);
        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'self'", $policy);
    }
}
