<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMerchandisingTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Store Admin',
            'email' => 'merchandising-admin@example.com',
            'password' => Hash::make('AdminPass123'),
        ]);
    }

    private function product(string $name, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'sku' => 'ALN-'.strtoupper(substr(md5($name), 0, 6)),
            'price' => 2500,
            'stock' => 10,
            'is_active' => true,
            'media' => [['type' => 'image', 'path' => 'placeholder.jpg', 'order' => 0]],
        ], $overrides));
    }

    public function test_homepage_studio_saves_and_orders_the_lineup(): void
    {
        $admin = $this->admin();
        $first = $this->product('First Curated');
        $second = $this->product('Second Curated');
        $third = $this->product('Third Curated');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.homepage.update'), [
                'products' => [$third->id, $first->id, $second->id],
            ])
            ->assertRedirect(route('admin.homepage.index'));

        $this->assertDatabaseHas('products', ['id' => $third->id, 'is_homepage' => 1, 'homepage_order' => 1]);
        $this->assertDatabaseHas('products', ['id' => $first->id, 'is_homepage' => 1, 'homepage_order' => 2]);
        $this->assertDatabaseHas('products', ['id' => $second->id, 'is_homepage' => 1, 'homepage_order' => 3]);

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Third Curated', 'First Curated', 'Second Curated']);
    }

    public function test_homepage_studio_rejects_more_than_twenty_products(): void
    {
        $admin = $this->admin();
        $ids = collect(range(1, 21))->map(fn (int $i) => $this->product('Bulk Product '.$i)->id)->all();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.homepage.update'), ['products' => $ids])
            ->assertSessionHasErrors('products');

        $this->assertSame(0, Product::where('is_homepage', true)->count());
    }

    public function test_homepage_falls_back_to_automatic_merchandising_when_lineup_is_empty(): void
    {
        $this->product('Automatic Favourite', ['is_best_seller' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('The Home Edit')
            ->assertSee('Automatic Favourite');
    }

    public function test_product_seo_tags_and_homepage_flag_are_saved_and_cleared(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'SEO Category', 'slug' => 'seo-category']);
        $png = base64_decode(self::PNG);

        $payload = fn (array $overrides = []) => array_merge([
            'name' => 'SEO Chrome Set',
            'category_id' => $category->id,
            'price' => 3200,
            'stock' => 8,
            'is_active' => '1',
            'tags' => 'chrome, bridal, almond',
            'meta_title' => 'SEO Chrome Set | Alishe Nails',
            'meta_description' => 'A keyword rich description for the SEO chrome set.',
            'is_homepage' => '1',
            'media_files' => [UploadedFile::fake()->createWithContent('seo-cover.png', $png)],
            'media_types' => ['image'],
        ], $overrides);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), $payload())
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $product = Product::where('slug', 'seo-chrome-set')->firstOrFail();

        $this->assertSame('chrome, bridal, almond', $product->tags);
        $this->assertSame('SEO Chrome Set | Alishe Nails', $product->meta_title);
        $this->assertSame('A keyword rich description for the SEO chrome set.', $product->meta_description);
        $this->assertTrue($product->is_homepage);
        $this->assertSame(1, $product->homepage_order);

        $this->get('/products/'.$product->slug)
            ->assertOk()
            ->assertSee('<title>SEO Chrome Set | Alishe Nails</title>', false)
            ->assertSee('name="keywords" content="chrome, bridal, almond"', false);

        // Unchecking the homepage flag removes it from the lineup.
        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $product), $payload([
                'name' => 'SEO Chrome Set',
                'is_active' => '1',
                'existing_media' => ['image|'.$product->media[0]['path']],
                'media_files' => [],
                'media_types' => [],
                'is_homepage' => null,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertFalse($product->is_homepage);
        $this->assertNull($product->homepage_order);

        @unlink(public_path('images/products/'.$product->media[0]['path']));
    }

    public function test_category_image_and_visibility_control_the_storefront_tile(): void
    {
        $admin = $this->admin();
        $png = base64_decode(self::PNG);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.categories.store'), [
                'name' => 'AAA Merch Tile',
                'description' => 'Tile description for QA.',
                'sort_order' => 0,
                'is_active' => '1',
                'image' => UploadedFile::fake()->createWithContent('tile.png', $png),
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasNoErrors();

        $category = Category::where('slug', 'aaa-merch-tile')->firstOrFail();

        $this->assertSame('Tile description for QA.', $category->description);
        $this->assertTrue($category->is_active);
        $this->assertNotNull($category->image);
        $this->assertFileExists(public_path('images/categories/'.$category->image));

        // Homepage only lists tiles that contain an active product.
        $this->product('Tile Product', ['category_id' => $category->id]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Tile description for QA.')
            ->assertSee('images/categories/'.$category->image);

        // Hide the category: it must disappear from the homepage and shop.
        $this->actingAs($admin, 'admin')
            ->put(route('admin.categories.update', $category), [
                'name' => 'AAA Merch Tile',
                'description' => 'Tile description for QA.',
                'sort_order' => 0,
                'is_active' => '0',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertFalse($category->fresh()->is_active);

        $this->get('/')->assertOk()->assertDontSee('Tile description for QA.');
        $this->get('/shop')->assertOk()->assertDontSee('AAA Merch Tile');

        // Deleting the category removes its uploaded image. Reassign/remove
        // its product first so the "still has products" guard doesn't block.
        Product::where('category_id', $category->id)->delete();
        $image = $category->image;

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertFileDoesNotExist(public_path('images/categories/'.$image));
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $category = Category::create(['name' => 'Protected Category', 'slug' => 'protected-category']);
        $this->product('Protected Product', ['category_id' => $category->id]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.categories.destroy', $category))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_product_delete_removes_its_media_but_keeps_shared_files(): void
    {
        $admin = $this->admin();
        $png = base64_decode(self::PNG);
        $shared = $this->storeUploadedProduct($admin, 'Shared Media Product', $png, 'shared-cover.png');
        $sharedPath = $shared->media[0]['path'];

        // A second product reuses the same file path.
        $this->product('Shared Media Product Two', [
            'media' => [['type' => 'image', 'path' => $sharedPath, 'order' => 0]],
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.products.destroy', $shared))
            ->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $shared->id]);
        $this->assertFileExists(public_path('images/products/'.$sharedPath));

        @unlink(public_path('images/products/'.$sharedPath));
    }

    private function storeUploadedProduct(Admin $admin, string $name, string $png, string $filename): Product
    {
        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'name' => $name,
                'price' => 2100,
                'stock' => 4,
                'is_active' => '1',
                'media_files' => [UploadedFile::fake()->createWithContent($filename, $png)],
                'media_types' => ['image'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        return Product::where('slug', str($name)->slug()->toString())->firstOrFail();
    }
}
