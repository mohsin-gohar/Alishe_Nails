<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Support\ImageUrl;
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

    /** A real 32×32 PNG, so the "not a 1×1 stub" guard accepts the fixture. */
    private function pngBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAAK0lEQVR42mM4sWUVTRHDqAWjFoxaMGrBqAWjFoxaMGrBqAWjFoxaMFQsAAAR05h5YZPaPgAAAABJRU5ErkJggg==');
    }

    /** The 1×1 placeholder the old resize left behind on the server. */
    private function onePixelPngBytes(): string
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

    public function test_a_record_saved_with_the_original_upload_name_finds_the_slugged_file(): void
    {
        $this->putPublicImage('whatsapp-image-2026-09-06-at-122628-pm.jpg');

        $product = $this->product([
            'image' => 'WhatsApp Image 2026-09-06 at 12.26.28 PM.jpg',
        ]);

        $this->assertStringEndsWith(
            '/images/products/whatsapp-image-2026-09-06-at-122628-pm.jpg',
            (string) $product->image_url,
        );
    }

    public function test_original_upload_name_finds_the_slugged_and_deduplicated_file(): void
    {
        // Exactly the shape the live server holds: the record kept the client
        // filename, the uploader wrote the slugged name plus a "-1" counter
        // and its own timestamp/random suffix.
        $this->putPublicImage('whatsapp-image-2026-09-06-at-122628-pm-1-1788693359-w5NXXQ.jpeg');

        $product = $this->product([
            'image' => 'WhatsApp Image 2026-09-06 at 12.26.28 PM (1).jpeg',
        ]);

        $this->assertStringEndsWith(
            '/images/products/whatsapp-image-2026-09-06-at-122628-pm-1-1788693359-w5NXXQ.jpeg',
            (string) $product->image_url,
        );
    }

    public function test_an_ambiguous_name_match_is_never_guessed(): void
    {
        // Two different uploads normalise to the same key. Guessing either one
        // could put the wrong set of nails on a product page, so the record
        // stays empty instead.
        $this->putPublicImage('blush-aura-test-1-1700000000-Ab12Cd.jpeg');
        $this->putPublicImage('blush-aura-test (1).jpeg');

        $product = $this->product([
            'slug' => 'something-else',
            'image' => 'Blush Aura Test (1).jpeg',
        ]);

        $this->assertNull($product->image_url);
    }

    public function test_a_one_pixel_placeholder_is_not_used_as_a_product_photo(): void
    {
        $dir = public_path('images/products');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir.'/cover-1788807665-8nQCvl.png';
        file_put_contents($file, $this->onePixelPngBytes());

        try {
            $product = $this->product(['image' => 'cover-1788807665-8nQCvl.png']);

            $this->assertNull($product->image_url);
        } finally {
            @unlink($file);
        }
    }

    public function test_a_record_keeps_working_when_only_the_extension_changed(): void
    {
        $this->putPublicImage('path-test-extension.jpeg');

        $product = $this->product([
            'image' => 'path-test-extension.webp',
        ]);

        $this->assertStringEndsWith('/images/products/path-test-extension.jpeg', (string) $product->image_url);
    }

    public function test_the_uploader_timestamp_suffix_is_ignored_when_matching(): void
    {
        $this->putPublicImage('path-test-timestamped-1700000000-Ab12Cd.jpg');

        $product = $this->product([
            'slug' => 'path-test-timestamped',
            'image' => 'path-test-timestamped-1700000000-Ab12Cd.webp',
        ]);

        $this->assertStringEndsWith(
            '/images/products/path-test-timestamped-1700000000-Ab12Cd.jpg',
            $product->image_url,
        );
    }

    public function test_similar_but_unrelated_filenames_are_never_guessed(): void
    {
        $this->putPublicImage('path-test-other-product.jpg');

        $product = $this->product([
            'slug' => 'path-test',
            'image' => 'path-test.webp',
        ]);

        $this->assertNull($product->image_url);
    }

    public function test_a_storage_url_is_not_mangled_by_the_picture_component(): void
    {
        $absolute = storage_path('app/public/products/path-test-storage.png');

        if (! is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0775, true);
        }

        file_put_contents($absolute, $this->pngBytes());

        try {
            $this->assertSame(
                ImageUrl::urlForFile($absolute),
                ImageUrl::fallback('/storage/products/path-test-storage.png'),
            );
            $this->assertStringEndsWith('/storage/products/path-test-storage.png', ImageUrl::fallback('/storage/products/path-test-storage.png'));
        } finally {
            @unlink($absolute);
        }
    }
}
