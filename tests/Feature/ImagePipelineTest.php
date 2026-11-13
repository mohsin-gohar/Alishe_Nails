<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Guards the WebP image pipeline.
 *
 * A product uploaded as a JPG/PNG must be stored as WebP (with a compressed
 * JPEG fallback), downscaled to the maximum width, and the database must
 * point at the WebP file.
 */
class ImagePipelineTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        $admin = Admin::create([
            'name' => 'Pipeline Admin',
            'email' => 'pipeline-admin@alishenails.com',
            'password' => bcrypt('secret-password'),
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /** A 3x2 PNG built in memory — no binary fixtures needed. */
    private function pngUpload(): UploadedFile
    {
        $image = imagecreatetruecolor(3, 2);
        imagefilledrectangle($image, 0, 0, 2, 1, imagecolorallocate($image, 185, 122, 135));
        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();
        imagedestroy($image);

        return UploadedFile::fake()->createWithContent('pipeline-check.png', $bytes);
    }

    public function test_webp_support_is_available_on_the_server(): void
    {
        $this->assertTrue(
            extension_loaded('gd'),
            'The GD extension is required for the image pipeline. Enable extension=gd in php.ini.'
        );

        $this->assertTrue(
            ! empty(gd_info()['WebP Support']),
            'This GD build has no WebP encoder. Enable extension=gd (with WebP support) in php.ini.'
        );
    }

    public function test_product_images_are_stored_as_webp(): void
    {
        $this->admin();

        $this->post(route('admin.products.store'), [
            'name' => 'Pipeline Product',
            'price' => 1990,
            'stock' => 3,
            'media_files' => [$this->pngUpload()],
            'media_types' => ['image'],
        ])->assertSessionHasNoErrors();

        $product = Product::where('slug', 'pipeline-product')->firstOrFail();

        $this->assertNotEmpty($product->media);
        $this->assertSame('image', $product->media[0]['type']);
        $this->assertStringEndsWith('.webp', $product->media[0]['path']);

        $webpPath = public_path('images/products/'.$product->media[0]['path']);
        $this->assertFileExists($webpPath);

        // It really is a WebP file, not a JPEG with a WebP name.
        $this->assertSame('image/webp', mime_content_type($webpPath));

        // And the JPEG fallback exists for clients without WebP support.
        $fallback = public_path('images/products/'.pathinfo($product->media[0]['path'], PATHINFO_FILENAME).'.jpg');
        $this->assertFileExists($fallback);

        // Clean up the files the test created.
        @unlink($webpPath);
        @unlink($fallback);
    }

    public function test_stored_images_are_scaled_down_to_the_maximum_width(): void
    {
        $this->admin();

        $wide = imagecreatetruecolor(2400, 1200);
        imagefilledrectangle($wide, 0, 0, 2399, 1199, imagecolorallocate($wide, 43, 29, 29));
        ob_start();
        imagejpeg($wide, null, 95);
        $bytes = ob_get_clean();
        imagedestroy($wide);

        $this->post(route('admin.products.store'), [
            'name' => 'Wide Pipeline Product',
            'price' => 1990,
            'stock' => 2,
            'media_files' => [UploadedFile::fake()->createWithContent('wide.jpg', $bytes)],
            'media_types' => ['image'],
        ])->assertSessionHasNoErrors();

        $product = Product::where('slug', 'wide-pipeline-product')->firstOrFail();
        $webpPath = public_path('images/products/'.$product->media[0]['path']);

        $this->assertFileExists($webpPath);

        [$width, $height] = getimagesize($webpPath);

        $this->assertLessThanOrEqual(1600, $width, 'Images wider than the 1600px maximum must be scaled down.');
        $this->assertSame(1600, $width);
        $this->assertSame(800, $height, 'The aspect ratio must be preserved while scaling.');

        @unlink($webpPath);
        @unlink(public_path('images/products/'.pathinfo($product->media[0]['path'], PATHINFO_FILENAME).'.jpg'));
    }

    public function test_category_images_are_stored_as_webp(): void
    {
        $this->admin();

        $this->post(route('admin.categories.store'), [
            'name' => 'Pipeline Category',
            'sort_order' => 0,
            'is_active' => '1',
            'image' => $this->pngUpload(),
        ])->assertSessionHasNoErrors();

        $category = Category::where('slug', 'pipeline-category')->firstOrFail();

        $this->assertNotNull($category->image);
        $this->assertStringEndsWith('.webp', $category->image);

        $path = public_path('images/categories/'.$category->image);
        $this->assertFileExists($path);
        $this->assertSame('image/webp', mime_content_type($path));

        @unlink($path);
        @unlink(public_path('images/categories/'.pathinfo($category->image, PATHINFO_FILENAME).'.jpg'));
    }
}
