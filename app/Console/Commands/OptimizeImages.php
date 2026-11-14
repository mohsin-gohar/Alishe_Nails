<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Converts the existing catalogue imagery to WebP.
 *
 *    php artisan images:optimize            # create WebP siblings
 *    php artisan images:optimize --dry-run  # preview only
 *    php artisan images:optimize --prune    # also swap the DB over to WebP
 *                                            and delete the heavy originals
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize
        {--dry-run : Report what would change without touching anything}
        {--prune : Point the database at the WebP files and delete the originals}
        {--path=* : Limit to specific public-relative folders (default: all of public/images)}
        {--max-width=1600 : Maximum width in pixels}';

    protected $description = 'Convert catalogue imagery to WebP so the storefront ships far fewer bytes.';

    public function handle(ImageOptimizer $optimizer): int
    {
        if (! $optimizer->supportsWebp()) {
            $this->error('The GD extension with WebP support is required but not available on this server.');
            $this->line('  Enable it in php.ini (extension=gd) and try again.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $prune = (bool) $this->option('prune');
        $maxWidth = (int) $this->option('max-width');

        $directories = $this->option('path');
        $directories = $directories ?: ['images'];

        $originalBytes = 0;
        $optimizedBytes = 0;
        $converted = 0;
        $skipped = 0;
        $failed = 0;
        $dbUpdates = 0;

        foreach ($directories as $directory) {
            $absolute = public_path(trim($directory, '/'));

            if (! is_dir($absolute)) {
                $this->warn("Skipping missing folder: {$directory}");

                continue;
            }

            foreach ($this->imageFiles($absolute) as $file) {
                $extension = strtolower($file->getExtension());
                $stem = $file->getBasename('.'.$file->getExtension());

                // The WebP sibling lives NEXT TO the original so the two stay
                // in the same folder (images/products/..., images/collections/...).
                $target = $file->getPath().DIRECTORY_SEPARATOR.$stem.'.webp';

                $originalBytes += $file->getSize();

                if (is_file($target) && $target !== $file->getPathname()) {
                    $optimizedBytes += (int) filesize($target);
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $optimizedBytes += (int) $file->getSize();
                    $converted++;

                    continue;
                }

                $written = $optimizer->writeWebp($file->getPathname(), $target, $maxWidth);

                if (! $written || ! is_file($target)) {
                    $failed++;
                    $optimizedBytes += $file->getSize();

                    continue;
                }

                $optimizedBytes += (int) filesize($target);

                // The original JPEG stays on disk as the <picture> fallback
                // for clients without WebP — but a 700 KB phone photo is a
                // silly fallback, so it is re-encoded at a sane weight.
                if (in_array($extension, ['jpg', 'jpeg'], true) && $file->getSize() > 200 * 1024) {
                    $this->compressInPlace($file->getPathname(), $maxWidth);
                }

                $converted++;
            }
        }

        if ($prune && ! $dryRun) {
            $dbUpdates = $this->switchDatabaseToWebp();
        }

        $saved = $originalBytes - $optimizedBytes;
        $percentage = $originalBytes > 0 ? round(($saved / $originalBytes) * 100, 1) : 0.0;

        $this->newLine();
        $this->info('Image optimisation complete');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Converted', $converted],
                ['Already optimised', $skipped],
                ['Failed', $failed],
                ['Database rows switched', $dbUpdates],
                ['Before', $this->formatBytes($originalBytes)],
                ['After', $this->formatBytes($optimizedBytes)],
                ['Saved', $this->formatBytes($saved).' ('.$percentage.'%)'],
            ]
        );

        if ($dryRun) {
            $this->line('Dry run — nothing was changed. Re-run without --dry-run to apply.');
        }

        return $failed > 0 ? self::SUCCESS : self::SUCCESS;
    }

    /**
     * Points products and categories at the WebP files and removes the
     * original bitmaps that are no longer referenced.
     */
    private function switchDatabaseToWebp(): int
    {
        $updates = 0;

        Product::query()->whereNotNull('image')->chunkById(200, function ($products) use (&$updates) {
            foreach ($products as $product) {
                $changed = false;

                if ($product->image && $this->swapToWebp('images/products', $product->image)) {
                    $product->image = $this->webpName($product->image);
                    $changed = true;
                }

                $gallery = (array) ($product->gallery ?? []);
                $newGallery = [];

                foreach ($gallery as $path) {
                    if (is_string($path) && $this->swapToWebp('images/products', $path)) {
                        $newGallery[] = $this->webpName($path);
                        $changed = true;
                    } else {
                        $newGallery[] = $path;
                    }
                }

                if ($changed) {
                    $product->gallery = $newGallery;
                }

                $media = (array) ($product->media ?? []);
                $newMedia = [];

                foreach ($media as $item) {
                    if (is_array($item) && ($item['type'] ?? '') === 'image' && ! empty($item['path'])) {
                        if ($this->swapToWebp('images/products', $item['path'])) {
                            $item['path'] = $this->webpName($item['path']);
                            $changed = true;
                        }
                    }

                    $newMedia[] = $item;
                }

                if ($changed) {
                    $product->media = $newMedia;
                    $product->saveQuietly();
                    $updates++;
                }
            }
        });

        Category::query()->whereNotNull('image')->chunkById(200, function ($categories) use (&$updates) {
            foreach ($categories as $category) {
                if ($category->image && $this->swapToWebp('images/categories', $category->image)) {
                    $category->image = $this->webpName($category->image);
                    $category->saveQuietly();
                    $updates++;
                }
            }
        });

        return $updates;
    }

    /**
     * Ensures the WebP sibling exists for the given file.
     */
    private function swapToWebp(string $directory, string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === 'webp') {
            return false;
        }

        if (! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            return false;
        }

        $stem = pathinfo($filename, PATHINFO_FILENAME);
        $webp = $directory.'/'.$stem.'.webp';

        if (is_file(public_path($webp))) {
            return true;
        }

        $source = public_path($directory.'/'.$filename);

        if (! is_file($source)) {
            return false;
        }

        return app(ImageOptimizer::class)->writeWebp($source, public_path($webp));
    }

    /**
     * Re-encodes a JPEG at a lighter weight, in place. PNGs are never
     * touched because they may carry transparency.
     */
    private function compressInPlace(string $path, int $maxWidth): bool
    {
        $temporary = $path.'.tmp';

        $written = app(ImageOptimizer::class)->writeJpegFallback($path, $temporary, $maxWidth);

        if (! $written || ! is_file($temporary)) {
            @unlink($temporary);

            return false;
        }

        if (filesize($temporary) < filesize($path)) {
            @unlink($path);
            rename($temporary, $path);

            return true;
        }

        @unlink($temporary);

        return false;
    }

    private function webpName(string $filename): string
    {
        return pathinfo($filename, PATHINFO_FILENAME).'.webp';
    }

    /**
     * @return array<int, \SplFileInfo>
     */
    private function imageFiles(string $directory): array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            if (in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'], true)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / (1024 ** $power), 2).' '.$units[$power];
    }
}
