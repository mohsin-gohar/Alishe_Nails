<?php

namespace Tests\Feature\Admin;

use App\Models\Admin;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The Image Doctor re-attaches photos that already exist on the server to
 * product records that lost their image reference.
 */
class AdminImageDoctorTest extends TestCase
{
    use RefreshDatabase;

    private string $created = '';

    protected function tearDown(): void
    {
        if ($this->created !== '' && is_file($this->created)) {
            @unlink($this->created);
        }

        parent::tearDown();
    }

    private function admin(): Admin
    {
        return Admin::create([
            'name' => 'Store Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminPass123'),
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Image Doctor Set',
            'slug' => 'image-doctor-set',
            'sku' => 'ALN-DOC-1',
            'price' => 1500,
            'stock' => 4,
            'is_active' => true,
            'image' => null,
        ]);
    }

    /** A real 32×32 PNG, so it is treated as a photo and not a stub. */
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
        file_put_contents($file, $this->pngBytes());

        return $this->created = $file;
    }

    public function test_the_image_doctor_page_renders_for_an_admin(): void
    {
        $this->product();

        $this->actingAs($this->admin(), 'admin')
            ->get(route('admin.images.index'))
            ->assertOk()
            ->assertSee('Image Doctor');
    }

    public function test_a_customer_cannot_open_the_image_doctor(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->get(route('admin.images.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_an_admin_can_attach_an_existing_server_image_to_a_product(): void
    {
        $this->putPublicImage('image-doctor-attached.png');
        $product = $this->product();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.images.attach', $product), ['file' => 'image-doctor-attached.png'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $product->refresh();

        $this->assertSame('image-doctor-attached.png', $product->image);
        $this->assertSame('image-doctor-attached.png', $product->media[0]['path']);
        $this->assertNotNull($product->cover_image_url);
        $this->assertStringEndsWith('/images/products/image-doctor-attached.png', $product->cover_image_url);
    }

    public function test_a_one_pixel_placeholder_cannot_be_attached(): void
    {
        $dir = public_path('images/products');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->created = $dir.'/cover-stub.png';
        file_put_contents($this->created, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));

        $product = $this->product();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.images.attach', $product), ['file' => 'cover-stub.png'])
            ->assertSessionHasErrors('file');

        $this->assertNull($product->fresh()->image);
    }

    public function test_attaching_a_file_that_is_not_on_disk_is_rejected(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.images.attach', $product), ['file' => 'does-not-exist.png'])
            ->assertSessionHasErrors('file');

        $this->assertNull($product->fresh()->image);
    }

    public function test_path_traversal_in_the_attached_filename_is_neutralised(): void
    {
        $product = $this->product();

        $this->actingAs($this->admin(), 'admin')
            ->post(route('admin.images.attach', $product), ['file' => '../../.env'])
            ->assertSessionHasErrors('file');

        $this->assertNull($product->fresh()->image);
    }
}
