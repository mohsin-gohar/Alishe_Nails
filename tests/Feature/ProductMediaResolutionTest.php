<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reproduces the production image failure end to end:
 *
 *   - `products.media` holds WhatsApp filenames that are NOT on disk;
 *   - `products.image` / `products.gallery` hold filenames that ARE;
 *   - the storefront still rendered "Photo coming soon" because a
 *     non-empty-but-invalid `media` collection suppressed the legacy
 *     columns entirely.
 *
 * Every test here walks the real chain: database value → model accessor →
 * resolved URL → rendered Blade <img src>.
 */
class ProductMediaResolutionTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    /** A real 32×32 PNG so the "not a 1×1 stub" guard accepts the fixture. */
    private function pngBytes(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAAK0lEQVR42mM4sWUVTRHDqAWjFoxaMGrBqAWjFoxaMGrBqAWjFoxaMFQsAAAR05h5YZPaPgAAAABJRU5ErkJggg==');
    }

    private function putPublicImage(string $name): string
    {
        $dir = public_path('images/products');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir.'/'.$name;

        // Fixtures must never overwrite a real catalogue photo: tearDown
        // deletes everything it created, so clobbering a tracked image would
        // silently strip the storefront of an asset.
        if (file_exists($file)) {
            $this->fail('Fixture '.$name.' already exists in public/images/products; use a unique name.');
        }

        file_put_contents($file, $this->pngBytes());
        $this->created[] = $file;

        return $file;
    }

    private function product(array $attributes = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Resolution Test Set',
            'slug' => 'resolution-test-set',
            'sku' => 'ALN-RESO-1',
            'price' => 1000,
            'stock' => 5,
            'is_active' => true,
        ], $attributes));
    }

    /** The exact production shape: non-empty media that resolves to nothing. */
    private function deadMedia(): array
    {
        return [
            ['type' => 'image', 'path' => 'WhatsApp Image 2026-09-06 at 12.26.28 PM.jpg', 'order' => 0],
            ['type' => 'image', 'path' => 'gone-from-server.webp', 'order' => 1],
        ];
    }

    /* ------------------------------------------------------------------
     |  Priority 1 — invalid media must not suppress a valid legacy image
     * ---------------------------------------------------------------- */

    public function test_non_empty_invalid_media_does_not_hide_a_valid_legacy_image(): void
    {
        $this->putPublicImage('zz-resolution-legacy-cover.jpg');

        $product = $this->product([
            'image' => 'zz-resolution-legacy-cover.jpg',
            'media' => $this->deadMedia(),
        ]);

        $urls = $product->media_urls;

        $this->assertCount(1, $urls);
        $this->assertSame('image', $urls[0]['type']);
        $this->assertStringEndsWith('/images/products/zz-resolution-legacy-cover.jpg', $urls[0]['url']);
    }

    public function test_non_empty_invalid_media_falls_back_to_a_valid_legacy_gallery(): void
    {
        $this->putPublicImage('zz-resolution-legacy-gallery.jpg');

        $product = $this->product([
            'image' => null,
            'gallery' => ['zz-resolution-legacy-gallery.jpg'],
            'media' => $this->deadMedia(),
        ]);

        $urls = $product->media_urls;

        $this->assertCount(1, $urls);
        $this->assertStringEndsWith('/images/products/zz-resolution-legacy-gallery.jpg', $urls[0]['url']);
    }

    public function test_valid_unified_media_keeps_priority_over_legacy_fields(): void
    {
        $this->putPublicImage('zz-resolution-unified.jpg');
        $this->putPublicImage('zz-resolution-legacy-loser.jpg');

        $product = $this->product([
            'image' => 'zz-resolution-legacy-loser.jpg',
            'media' => [
                ['type' => 'image', 'path' => 'zz-resolution-unified.jpg', 'order' => 0],
            ],
        ]);

        $urls = $product->media_urls;

        $this->assertCount(1, $urls);
        $this->assertStringEndsWith('/images/products/zz-resolution-unified.jpg', $urls[0]['url']);
    }

    public function test_missing_everything_produces_no_image_url(): void
    {
        $product = $this->product([
            'image' => 'not-on-disk-at-all.jpg',
            'media' => $this->deadMedia(),
        ]);

        $this->assertSame([], $product->media_urls);
        $this->assertNull($product->cover_image_url);
    }

    public function test_valid_media_video_is_preserved_alongside_resolved_images(): void
    {
        $videoDir = public_path('videos/products');

        if (! is_dir($videoDir)) {
            mkdir($videoDir, 0775, true);
        }

        $video = $videoDir.'/zz-resolution-clip.mp4';
        file_put_contents($video, 'dummy-bytes');
        $this->created[] = $video;

        $this->putPublicImage('zz-resolution-after-video.jpg');

        $product = $this->product([
            'image' => 'zz-resolution-after-video.jpg',
            'media' => [
                ['type' => 'video', 'path' => 'zz-resolution-clip.mp4', 'order' => 0],
                ['type' => 'image', 'path' => 'dead-image.jpg', 'order' => 1],
            ],
        ]);

        $urls = $product->media_urls;

        $this->assertCount(2, $urls);
        $this->assertSame('video', $urls[0]['type']);
        $this->assertStringEndsWith('/videos/products/zz-resolution-clip.mp4', $urls[0]['url']);
        $this->assertSame('image', $urls[1]['type']);
        $this->assertStringEndsWith('/images/products/zz-resolution-after-video.jpg', $urls[1]['url']);
    }

    /* ------------------------------------------------------------------
     |  Priority 1 — WebP/JPEG interchange and encoded filenames
     * ---------------------------------------------------------------- */

    public function test_legacy_image_record_resolves_to_its_webp_sibling(): void
    {
        $this->putPublicImage('zz-resolution-webp-sibling.webp');

        $product = $this->product([
            'image' => 'zz-resolution-webp-sibling.jpg',
            'media' => $this->deadMedia(),
        ]);

        $urls = $product->media_urls;

        $this->assertCount(1, $urls);
        $this->assertStringEndsWith('/images/products/zz-resolution-webp-sibling.webp', $urls[0]['url']);
    }

    public function test_legacy_image_with_an_encoded_filename_resolves(): void
    {
        $this->putPublicImage('zz-resolution-blush-aura.jpg');

        $product = $this->product([
            'image' => 'zz-resolution-blush%20aura.jpg',
            'media' => $this->deadMedia(),
        ]);

        $urls = $product->media_urls;

        $this->assertCount(1, $urls);
        $this->assertStringEndsWith('/images/products/zz-resolution-blush-aura.jpg', $urls[0]['url']);
    }

    /* ------------------------------------------------------------------
     |  Cross-product safety — never borrow another product's photo
     * ---------------------------------------------------------------- */

    public function test_a_product_never_inherits_another_products_file(): void
    {
        // The only file on disk belongs to a DIFFERENT product and merely
        // shares its stem with this product's dead media record.
        $this->putPublicImage('zz-resolution-someone-else.jpg');

        $product = $this->product([
            'slug' => 'my-own-set',
            'name' => 'My Own Set',
            'image' => null,
            'media' => [
                ['type' => 'image', 'path' => 'zz-resolution-someone-else.jpg', 'order' => 0],
            ],
        ]);

        // The media record belongs to this row and the file exists, so it
        // resolves — what must NEVER happen is the slug fallback grabbing a
        // similar-but-unrelated file when nothing of this product exists.
        $other = $this->product([
            'slug' => 'different-product',
            'name' => 'Different Product',
            'sku' => 'ALN-RESO-2',
            'image' => 'missing-cover.jpg',
            'media' => $this->deadMedia(),
        ]);

        $this->assertSame([], $other->media_urls);
        $this->assertNull($other->image_url);

        // Sanity: the first product still resolves its own declared record.
        $this->assertCount(1, $product->media_urls);
    }

    /* ------------------------------------------------------------------
     |  Full rendering chain — card, detail page, shop, homepage
     * ---------------------------------------------------------------- */

    public function test_product_card_renders_the_legacy_image_url(): void
    {
        $this->putPublicImage('zz-resolution-card.jpg');

        $product = $this->product([
            'image' => 'zz-resolution-card.jpg',
            'media' => $this->deadMedia(),
        ]);

        $response = $this->get(route('shop.index'));

        $response->assertOk();
        $response->assertSee('/images/products/zz-resolution-card.jpg', false);
        $response->assertDontSee('Photo coming soon', false);
    }

    public function test_product_detail_page_renders_the_legacy_image(): void
    {
        $this->putPublicImage('zz-resolution-detail.jpg');

        $product = $this->product([
            'image' => 'zz-resolution-detail.jpg',
            'media' => $this->deadMedia(),
        ]);

        $response = $this->get(route('products.show', $product));

        $response->assertOk();
        $response->assertSee('/images/products/zz-resolution-detail.jpg', false);
        $response->assertDontSee('Photo coming soon', false);
    }

    public function test_homepage_showcases_render_the_legacy_image(): void
    {
        $this->putPublicImage('zz-resolution-home.jpg');

        $product = $this->product([
            'image' => 'zz-resolution-home.jpg',
            'media' => $this->deadMedia(),
            'is_best_seller' => true,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('/images/products/zz-resolution-home.jpg', false);
        $response->assertDontSee('Photo coming soon', false);
    }

    public function test_cover_accessors_skip_dead_media_and_use_the_legacy_image(): void
    {
        $this->putPublicImage('zz-resolution-cover.jpg');

        $product = $this->product([
            'image' => 'zz-resolution-cover.jpg',
            'media' => $this->deadMedia(),
        ]);

        $this->assertStringEndsWith(
            '/images/products/zz-resolution-cover.jpg',
            (string) $product->cover_image_url,
        );
        $this->assertSame('zz-resolution-cover.jpg', $product->cover_image_path);
        $this->assertSame('image', $product->cover_media['type']);
    }

    public function test_declared_media_stays_visible_to_admin_and_doctor_tools(): void
    {
        // media_items must keep reporting the DECLARED rows (including dead
        // ones) so the admin editor and Image Doctor can still see and repair
        // broken references — only the render path filters them.
        $product = $this->product([
            'image' => null,
            'media' => $this->deadMedia(),
        ]);

        $this->assertCount(2, $product->media_items);
        $this->assertSame(
            'WhatsApp Image 2026-09-06 at 12.26.28 PM.jpg',
            $product->media_items[0]['path'],
        );
    }
}
