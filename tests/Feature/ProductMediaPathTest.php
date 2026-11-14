<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The catalogue must keep rendering when a hosting migration moves the
 * files, when the uploader saved a WebP next to the original JPEG, or when
 * an admin simply drops a photo named after the product slug.
 */
class ProductMediaPathTest extends TestCase
{
    use RefreshDatabase;

    private function pngBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');
    }

    private function product(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Path Test Set',
            'slug' => 'path-test-set',
            'sku' => 'ALN-PATH',
            'price' => 1000,
            'stock' => 5,
            'is_active' => true,
        ], $attributes));
    }

    private function putPublicImage(string $name): string
    {
        $dir = public_path('images/products');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir.'/'.$name;
        file_put_contents($file, $this->pngBytes());

        return $file;
    }

    public function test_jpg_record_resolves_to_the_webp_derivative_on_disk(): void
    {
        $this->putPublicImage('path-test-cover.webp');

        $product = $this->product([
            'image' => 'path-test-cover.jpg',
        ]);

        $this->assertStringEndsWith('/images/products/path-test-cover.webp', $product->image_url);
    }

    public function test_media_path_with_public_prefix_and_subfolders_still_resolves(): void
    {
        $this->putPublicImage('path-test-prefixed.jpg');

        $product = $this->product([
            'media' => [
                ['type' => 'image', 'path' => 'public/images/products/path-test-prefixed.jpg', 'order' => 0],
            ],
        ]);

        $urls = $product->media_urls;

        $this->assertCount(1, $urls);
        $this->assertStringEndsWith('/images/products/path-test-prefixed.jpg', $urls[0]['url']);
    }

    public function test_missing_gallery_falls_back_to_a_photo_named_after_the_slug(): void
    {
        $this->putPublicImage('path-test-slug-set.jpg');

        $product = $this->product([
            'slug' => 'path-test-slug-set',
            'media' => [
                ['type' => 'image', 'path' => 'gone-forever.jpg', 'order' => 0],
            ],
        ]);

        $urls = $product->media_urls;

        $this->assertCount(1, $urls);
        $this->assertSame('image', $urls[0]['type']);
        $this->assertStringEndsWith('/images/products/path-test-slug-set.jpg', $urls[0]['url']);
    }

    public function test_root_level_images_folder_is_searched_for_legacy_layouts(): void
    {
        $dir = base_path('images/products');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir.'/path-test-root.jpg';
        file_put_contents($file, $this->pngBytes());

        try {
            $product = $this->product([
                'image' => 'path-test-root.jpg',
            ]);

            $this->assertStringEndsWith('/images/products/path-test-root.jpg', $product->image_url);
        } finally {
            @unlink($file);
            @rmdir($dir);
        }
    }

    public function test_a_stored_path_can_never_escape_the_media_folders(): void
    {
        $product = $this->product([
            'image' => '../../.env',
        ]);

        $this->assertNull($product->image_url);
        $this->assertSame([], $product->media_urls);
    }

    public function test_existing_media_wins_over_the_slug_fallback(): void
    {
        $this->putPublicImage('path-test-real.jpg');
        $this->putPublicImage('path-test-fallback-set.jpg');

        $product = $this->product([
            'slug' => 'path-test-fallback-set',
            'media' => [
                ['type' => 'image', 'path' => 'path-test-real.jpg', 'order' => 0],
            ],
        ]);

        $urls = $product->media_urls;

        $this->assertCount(1, $urls);
        $this->assertStringEndsWith('/images/products/path-test-real.jpg', $urls[0]['url']);
    }
}
