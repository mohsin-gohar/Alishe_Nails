<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The "Alishe Press-Ons vs. Salon Acrylics" comparison matrix was removed
 * from the homepage at the owner's request. These tests make sure it (and
 * its supporting markup) never comes back unnoticed.
 */
class HomePageSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_does_not_render_the_press_ons_vs_acrylics_comparison(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee('Alishe Press-Ons vs. Salon Acrylics', false);
        $response->assertDontSee('Traditional Salon Acrylics', false);
        $response->assertDontSee('comparison-table', false);
        $response->assertDontSee('The Smart Luxury Choice', false);
    }

    public function test_homepage_still_renders_the_remaining_sections(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('Our Best')
            ->assertSee('Curated Collections');
    }
}
