<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Diagnoses the catalogue imagery on the server.
 *
 *    php artisan images:doctor
 *    php artisan images:doctor --repair
 *
 * "Images show as unavailable" almost always means one of three things:
 *
 *  1. The GD extension (or its WebP encoder) is missing, so the pipeline
 *     cannot write WebP files.
 *  2. The database points at files that are not on disk — a deploy that
 *     shipped the code but not the images.
 *  3. The products have no image records at all, because the images were
 *     never attached when the product was created.
 *
 * This command tells you which of the three it is, and --repair can re-link
 * files that are on disk but missing from the database.
 */
class DoctorImages extends Command
{
    protected $signature = 'images:doctor
        {--repair : Try to re-attach files that exist on disk but are not linked to a product}
        {--from= : A folder of images to import and match against products that have no image}';

    protected $description = 'Diagnose (and optionally repair) the catalogue imagery on this server.';

    public function handle(ImageOptimizer $optimizer): int
    {
        $this->line('');
        $this->line('  Alishe Nails — image diagnostics');
        $this->line('  '.str_repeat('-', 52));
        $this->line('');

        // ---------- 1. Server capability ----------
        $this->components->info('Server capability');

        $this->line('   PHP version .............. '.PHP_VERSION);
        $this->line('   GD extension ............. '.(extension_loaded('gd') ? 'loaded' : 'MISSING'));
        $this->line('   WebP encoder ............. '.($optimizer->supportsWebp() ? 'available' : 'MISSING'));

        if (! $optimizer->supportsWebp()) {
            $this->error('   → The GD extension with WebP support is required.');
            $this->line('     hPanel → Advanced → PHP → Extensions → tick "gd".');
            $this->line('     Then re-run this command.');
        }

        $this->line('   upload_max_filesize ...... '.ini_get('upload_max_filesize'));
        $this->line('   post_max_size ............ '.ini_get('post_max_size'));
        $this->line('   memory_limit ............. '.ini_get('memory_limit'));

        // ---------- 2. Files on disk ----------
        $this->newLine();
        $this->components->info('Files on disk');

        $directories = [
            'images/products' => 'Product media',
            'images/categories' => 'Category tiles',
            'images/collections' => 'Collection banners',
        ];

        $disk = [];

        foreach ($directories as $directory => $label) {
            $path = public_path($directory);
            $files = is_dir($path) ? (glob($path.'/*') ?: []) : [];

            $bytes = 0;
            foreach ($files as $file) {
                $bytes += (int) filesize($file);
            }

            $webp = count(array_filter($files, fn ($f) => str_ends_with($f, '.webp')));
            $legacy = count($files) - $webp;

            $this->line(sprintf(
                '   %-22s %3d files  (%d webp, %d other)  %s',
                $label,
                count($files),
                $webp,
                $legacy,
                $this->formatBytes($bytes)
            ));

            $disk[$directory] = $files;
        }

        // ---------- 3. Database vs disk ----------
        $this->newLine();
        $this->components->info('Database records');

        $products = Product::all();
        $withMedia = 0;
        $withoutMedia = [];
        $missingFiles = [];
        $referenced = [];

        foreach ($products as $product) {
            $items = $product->media_items;

            if (empty($items)) {
                $withoutMedia[] = $product;

                continue;
            }

            $withMedia++;

            foreach ($items as $item) {
                $folder = $item['type'] === 'video' ? 'videos/products' : 'images/products';
                $referenced[pathinfo($item['path'], PATHINFO_FILENAME)] = true;

                if (! is_file(public_path($folder.'/'.basename($item['path'])))) {
                    $missingFiles[] = "#{$product->id} {$product->slug} → {$folder}/{$item['path']}";
                }
            }
        }

        $this->line('   Products .................. '.$products->count());
        $this->line('   With an image record ...... '.$withMedia);
        $this->line('   Without any image ......... '.count($withoutMedia));
        $this->line('   Records pointing at a missing file ... '.count($missingFiles));

        // ---------- 4. Problems ----------
        if ($withoutMedia !== []) {
            $this->newLine();
            $this->components->warn('Products with no image (these render as "Image unavailable")');

            foreach (array_slice($withoutMedia, 0, 25) as $product) {
                $this->line('   #'.$product->id.'  '.$product->name);
            }
            if (count($withoutMedia) > 25) {
                $this->line('   … and '.(count($withoutMedia) - 25).' more');
            }

            $this->newLine();
            $this->line('   Fix: open each product in the admin and attach its image again.');
            $this->line('   With GD enabled the upload is stored as WebP automatically.');
        }

        if ($missingFiles !== []) {
            $this->newLine();
            $this->components->error('Database points at files that are NOT on disk');

            foreach (array_slice($missingFiles, 0, 25) as $line) {
                $this->line('   '.$line);
            }
            if (count($missingFiles) > 25) {
                $this->line('   … and '.(count($missingFiles) - 25).' more');
            }

            $this->newLine();
            $this->line('   Fix: deploy the public/ folder (it holds the images), or run');
            $this->line('        php artisan images:optimize --prune');
            $this->line('   if the images are present but only the WebP variants are missing.');
        }

        // ---------- 5. Orphaned files ----------
        $orphans = [];
        foreach ($disk['images/products'] ?? [] as $file) {
            $stem = pathinfo(basename($file), PATHINFO_FILENAME);

            if (! isset($referenced[$stem])) {
                $orphans[] = basename($file);
            }
        }

        if ($orphans !== []) {
            $this->newLine();
            $this->components->warn('Files on disk that no product points at ('.count($orphans).')');

            foreach (array_slice($orphans, 0, 12) as $orphan) {
                $this->line('   '.$orphan);
            }
            if (count($orphans) > 12) {
                $this->line('   … and '.(count($orphans) - 12).' more');
            }

            $this->newLine();
            $this->line('   These are often images whose database record was lost.');
            $this->line('   Run with --repair to try to re-attach them to their product.');
        }

        // ---------- 6. Repair ----------
        if ($this->option('from')) {
            $this->newLine();
            $this->components->info('Importing from '.$this->option('from'));

            $this->importFromFolder($withoutMedia, (string) $this->option('from'));
        }

        if ($this->option('repair')) {
            $this->newLine();
            $this->components->info('Repairing');

            $repaired = $this->repair($withoutMedia, $disk['images/products'] ?? []);

            if ($repaired === 0) {
                $this->line('   Nothing could be matched automatically.');
                $this->line('   Please attach the images by hand in the admin panel.');
            }
        }

        // ---------- 7. Category tiles ----------
        $this->newLine();
        $this->components->info('Categories');

        $missingCategories = [];
        foreach (Category::whereNotNull('image')->get() as $category) {
            if (! is_file(public_path('images/categories/'.$category->image))) {
                $missingCategories[] = $category->slug.' → '.$category->image;
            }
        }

        $this->line('   Categories with an image .. '.Category::whereNotNull('image')->count());
        $this->line('   Missing from disk ........ '.count($missingCategories));

        foreach ($missingCategories as $line) {
            $this->line('   '.$line);
        }

        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Imports images from a folder and links them to the products they
     * belong to, matching on the slug or the product name.
     *
     * This is the rescue path for a catalogue whose images were uploaded
     * outside the admin panel: drop the files in a folder, point the command
     * at it, and every product that has no image gets the right one.
     *
     * @param  array<int, Product>  $productsWithoutMedia
     */
    private function importFromFolder(array $productsWithoutMedia, string $folder): void
    {
        $folder = rtrim(str_replace('\\', '/', $folder), '/');

        if (! is_dir($folder)) {
            $this->error('   Folder not found: '.$folder);

            return;
        }

        $files = array_filter(glob($folder.'/*') ?: [], function ($file) {
            return is_file($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true);
        });

        if ($files === []) {
            $this->error('   No JPG/PNG/WebP files in that folder.');

            return;
        }

        $this->line('   Found '.count($files).' image(s) in the folder.');

        $optimizer = app(ImageOptimizer::class);
        $linked = 0;

        // Same one-to-one rule as --repair: a file that two products could
        // claim, or a product with two candidate files, is left alone.
        $matches = $this->matchOneToOne($productsWithoutMedia, $files);

        foreach ($matches as $productId => $match) {
            $product = Product::find($productId);

            if ($product === null) {
                continue;
            }

            $target = $product->slug.'.webp';

            if (! $optimizer->supportsWebp()) {
                $target = $product->slug.'.'.strtolower(pathinfo($match, PATHINFO_EXTENSION));
            }

            $destination = public_path('images/products/'.$target);

            // Never overwrite a file that is already on the server.
            if (is_file($destination)) {
                $target = $product->slug.'-'.time().'-'.Str::random(6).(str_ends_with($target, '.webp') ? '.webp' : '.'.pathinfo($target, PATHINFO_EXTENSION));
                $destination = public_path('images/products/'.$target);
            }

            if ($optimizer->supportsWebp()) {
                if (! $optimizer->writeWebp($match, $destination, ImageOptimizer::DEFAULT_MAX_WIDTH)) {
                    $this->error('   Could not convert '.$match);

                    continue;
                }
            } else {
                copy($match, $destination);
            }

            $product->update([
                'image' => $target,
                'media' => [[
                    'type' => 'image',
                    'path' => $target,
                    'order' => 0,
                ]],
            ]);

            $this->line('   '.$product->name.' → '.$target);
            $linked++;
        }

        $this->line($linked > 0
            ? '   Linked '.$linked.' product(s).'
            : '   Nothing could be matched — the file names have to resemble the product names.');
    }

    /**
     * Builds product ⇄ file pairs and keeps only those that are unambiguous in
     * BOTH directions.
     *
     * A photo that two products could claim, or a product with two similarly
     * named photos, is never assigned automatically — the admin attaches those
     * by hand in the Image Doctor.
     *
     * @param  array<int, Product>  $productsWithoutMedia
     * @param  array<int, string>  $files
     * @return array<int, string> product id => absolute file path
     */
    private function matchOneToOne(array $productsWithoutMedia, array $files): array
    {
        // Only unclaimed photos are candidates: a file another product still
        // points at must never be moved onto a second listing, and a 1×1 stub
        // is not a photo at all.
        $claimed = $this->claimedStems();

        $candidates = array_values(array_filter($files, function (string $file) use ($claimed): bool {
            if (isset($claimed[pathinfo($file, PATHINFO_FILENAME)])) {
                return false;
            }

            $info = @getimagesize($file);

            return ! (is_array($info) && ((int) $info[0] < 16 || (int) $info[1] < 16));
        }));

        if ($candidates === []) {
            return [];
        }

        $byFile = [];

        foreach ($productsWithoutMedia as $product) {
            $slugKey = $this->alphaKey((string) $product->slug);
            $nameKey = $this->alphaKey((string) $product->name);

            if ($slugKey === '' && $nameKey === '') {
                continue;
            }

            foreach ($candidates as $file) {
                $fileKey = $this->alphaKey(pathinfo(basename($file), PATHINFO_FILENAME));

                if ($fileKey === '') {
                    continue;
                }

                $slugHit = $slugKey !== '' && strlen($slugKey) >= 6
                    && ($fileKey === $slugKey || str_starts_with($fileKey, $slugKey));
                $nameHit = $nameKey !== '' && strlen($nameKey) >= 6 && $fileKey === $nameKey;

                if ($slugHit || $nameHit) {
                    $byFile[$file][$product->id] = $product;
                }
            }
        }

        // A file claimed by more than one product is ambiguous.
        $byFile = array_filter($byFile, fn (array $products): bool => count($products) === 1);

        // A product that would receive more than one file is ambiguous too.
        $perProduct = [];

        foreach ($byFile as $file => $products) {
            $perProduct[array_key_first($products)][] = $file;
        }

        $matches = [];

        foreach ($perProduct as $productId => $productFiles) {
            if (count($productFiles) === 1) {
                $matches[(int) $productId] = $productFiles[0];

                continue;
            }

            $this->line('   … skipped an ambiguous match for product #'.$productId.' ('.count($productFiles).' candidate photos)');
        }

        return $matches;
    }

    /**
     * Tries to match an unreferenced file on disk to a product that has no
     * image, using the slug / name as the key.
     *
     * @param  array<int, Product>  $productsWithoutMedia
     * @param  array<int, string>  $files
     */
    private function repair(array $productsWithoutMedia, array $files): int
    {
        if ($productsWithoutMedia === []) {
            return 0;
        }

        $matches = $this->matchOneToOne($productsWithoutMedia, $files);

        if ($matches === []) {
            return 0;
        }

        $repaired = 0;

        foreach ($matches as $productId => $file) {
            $product = Product::find($productId);

            if ($product === null) {
                continue;
            }

            $basename = basename($file);

            $product->update([
                'image' => $basename,
                'media' => [[
                    'type' => 'image',
                    'path' => $basename,
                    'order' => 0,
                ]],
            ]);

            $this->line('   '.$product->name.' → '.$basename);
            $repaired++;
        }

        if ($repaired > 0) {
            $this->line('   Repaired '.$repaired.' product(s).');
        }

        return $repaired;
    }

    /**
     * Filename stems every product already points at, so a repair never
     * steals a photo that is still in use.
     *
     * @return array<string, true>
     */
    private function claimedStems(): array
    {
        $claimed = [];

        foreach (Product::query()->get(['image', 'gallery', 'media']) as $product) {
            $paths = [];

            if ($product->image) {
                $paths[] = (string) $product->image;
            }

            foreach ((array) ($product->gallery ?? []) as $path) {
                if (is_string($path) && $path !== '') {
                    $paths[] = $path;
                }
            }

            foreach ((array) ($product->media ?? []) as $item) {
                if (is_array($item) && ! empty($item['path'])) {
                    $paths[] = (string) $item['path'];
                }
            }

            foreach ($paths as $path) {
                $claimed[pathinfo(basename($path), PATHINFO_FILENAME)] = true;
            }
        }

        return $claimed;
    }

    /**
     * Lowercase alphanumerics only, with the uploader's timestamp/random
     * suffix and a "-2" duplicate counter removed — the same normalisation the
     * Product model uses, so a repair agrees with what the storefront can
     * already resolve.
     */
    private function alphaKey(string $stem): string
    {
        $stem = strtolower(trim($stem));
        $stem = preg_replace('/[-_ ]*\d{10}[-_ ]*[a-z0-9]{5,8}$/', '', $stem) ?? $stem;
        $stem = preg_replace('/[-_ ]*\(?\s*\d{1,4}\)?\s*$/', '', $stem) ?? $stem;

        return preg_replace('/[^a-z0-9]+/', '', $stem) ?? '';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
