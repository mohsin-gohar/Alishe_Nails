<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkWithUsTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_with_us_route_is_removed_and_returns_404(): void
    {
        $response = $this->get('/work-with-us');

        $response->assertNotFound();
    }

    public function test_homepage_does_not_contain_work_with_us_banner(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee("Got an Audience? Let's Grow Together.", false);
        $response->assertDontSee('/work-with-us');
    }

    public function test_navbar_and_footer_do_not_contain_work_with_us_link(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('>Work with Us<', false);
    }
}
