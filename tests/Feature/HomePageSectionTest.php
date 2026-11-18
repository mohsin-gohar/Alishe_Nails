<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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

    public function test_homepage_lineup_query_never_orders_by_a_missing_merchandising_column(): void
    {
        $connection = 'products_without_merchandising_columns';

        config(['database.connections.'.$connection => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection($connection)->create('products', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $previousDefault = config('database.default');
        $columnCache = new \ReflectionProperty(Product::class, 'columnCache');
        $columnCache->setAccessible(true);
        $columnCache->setValue(null, []);

        try {
            config(['database.default' => $connection]);
            DB::purge($connection);

            // This is exactly the query HomeController builds for the
            // homepage lineup. Ordering by homepage_order unconditionally
            // would throw "Unknown column" on MySQL before the merchandising
            // migration has run.
            $sql = Product::active()->isHomepage()->orderByHomepage()->toSql();

            $this->assertStringNotContainsString('is_homepage', $sql);
            $this->assertStringNotContainsString('homepage_order', $sql);
        } finally {
            config(['database.default' => $previousDefault]);
            DB::purge($connection);
            $columnCache->setValue(null, []);
        }
    }
}
