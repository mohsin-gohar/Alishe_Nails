<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `php artisan images:doctor --repair` may only re-link a photo when the match
 * is unambiguous in both directions. Attaching the wrong set of nails to a
 * listing is worse than leaving it without a photo, so ambiguous names are
 * reported and left for the admin.
 */
class ImageDoctorRepairTest extends TestCase
{
    use RefreshDatabase;

    private const PNG_32 = 'iVBORw0KGgoAAAANSUhEUgAAACAAAAAgCAIAAAD8GO2jAAAAK0lEQVR42mM4sWUVTRHDqAWjFoxaMGrBqAWjFoxaMGrBqAWjFoxaMFQsAAAR05h5YZPaPgAAAABJRU5ErkJggg==';

    /** @var list<string> */
    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function putImage(string $name): void
    {
        $dir = public_path('images/products');

        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $file = $dir.'/'.$name;
        file_put_contents($file, base64_decode(self::PNG_32));

        $this->created[] = $file;
    }

    private function product(string $slug, string $name): Product
    {
        return Product::create([
            'name' => $name,
            'slug' => $slug,
            'sku' => 'ALN-'.strtoupper(substr(md5($slug), 0, 6)),
            'price' => 1500,
            'stock' => 3,
            'is_active' => true,
        ]);
    }

    public function test_repair_links_an_unambiguous_photo_and_leaves_ambiguous_ones_alone(): void
    {
        $kept = $this->product('path-repair-kept', 'Path Repair Kept');
        $ambiguous = $this->product('path-repair-ambiguous', 'Path Repair Ambiguous');

        // One photo for the first product, named after its slug with the
        // uploader's own suffix.
        $this->putImage('path-repair-kept-1700000000-Ab12Cd.png');

        // Two different photos normalise to the same name for the second
        // product: it must be skipped rather than guessed.
        $this->putImage('path-repair-ambiguous.png');
        $this->putImage('path-repair-ambiguous (1).png');

        $this->artisan('images:doctor', ['--repair' => true])->assertSuccessful();

        $this->assertSame(
            'path-repair-kept-1700000000-Ab12Cd.png',
            $kept->fresh()->image,
            'A photo that matches one product by slug must be linked.',
        );

        $this->assertNull(
            $ambiguous->fresh()->image,
            'Two candidates for one product must never be auto-assigned.',
        );
    }

    public function test_repair_never_reuses_a_photo_another_product_already_uses(): void
    {
        $owner = $this->product('path-repair-owner', 'Path Repair Owner');
        $other = $this->product('path-repair-other', 'Path Repair Other');

        $this->putImage('path-repair-shared-1700000000-Ab12Cd.png');

        $owner->forceFill([
            'image' => 'path-repair-shared-1700000000-Ab12Cd.png',
        ])->save();

        $other->forceFill(['image' => 'path-repair-other-1700000000-Qq11Zz.png'])->save();

        $this->artisan('images:doctor', ['--repair' => true])->assertSuccessful();

        $this->assertSame('path-repair-shared-1700000000-Ab12Cd.png', $owner->fresh()->image);
        $this->assertNotSame(
            'path-repair-shared-1700000000-Ab12Cd.png',
            $other->fresh()->image,
            'A photo still used by another product must not be re-linked.',
        );
    }
}
