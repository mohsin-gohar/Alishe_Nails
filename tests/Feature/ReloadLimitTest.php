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
        putenv('RELOAD_LIMIT_PER_MINUTE=5');
        $_ENV['RELOAD_LIMIT_PER_MINUTE'] = '5';
        $_SERVER['RELOAD_LIMIT_PER_MINUTE'] = '5';
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
