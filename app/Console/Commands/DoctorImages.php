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

        foreach ($productsWithoutMedia as $product) {
            $match = $this->matchFile($product, $files);

            if ($match === null) {
                continue;
            }

            $target = $product->slug.'.webp';

            if (! $optimizer->supportsWebp()) {
                $target = $product->slug.'.'.strtolower(pathinfo($match, PATHINFO_EXTENSION));
            }

            $destination = public_path('images/products/'.$target);

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
     * Finds the file that best matches a product by slug or name.
     *
     * @param  array<int, string>  $files
     */
    private function matchFile(Product $product, array $files): ?string
    {
        $needles = array_filter([
            $product->slug,
            Str::slug($product->name),
            Str::slug(str_replace(['with application kit', 'with Application Kit'], '', $product->name)),
        ]);

        foreach ($files as $file) {
            $basename = strtolower(pathinfo(basename($file), PATHINFO_FILENAME));

            foreach ($needles as $needle) {
                $needle = trim(strtolower((string) $needle), '-');

                if ($needle === '' || mb_strlen($needle) < 6) {
                    continue;
                }

                if (str_contains($basename, $needle) || str_contains($needle, $basename)) {
                    return $file;
                }
            }
        }

        return null;
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
        $repaired = 0;

        foreach ($productsWithoutMedia as $product) {
            $match = $this->matchFile($product, $files);

            if ($match === null) {
                continue;
            }

            $basename = basename($match);

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
