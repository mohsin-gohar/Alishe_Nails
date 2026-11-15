<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReloadLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The limiter reads config('reload.limit_per_minute'), so the test
        // lowers the limit there instead of relying on a runtime env() call.
        config(['reload.limit_per_minute' => 5]);
    }

    public function test_homepage_blocks_reload_spam_after_configured_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->get('/')->assertOk();
        }

        $response = $this->get('/');

        $response->assertStatus(429);
        $response->assertSee('Too Many Requests');
    }

    public function test_login_page_blocks_reload_spam_after_configured_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->get('/login')->assertOk();
        }

        $this->get('/login')->assertStatus(429);
    }

    public function test_register_page_blocks_reload_spam_after_configured_limit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->get('/register')->assertOk();
        }

        $this->get('/register')->assertStatus(429);
    }
}
