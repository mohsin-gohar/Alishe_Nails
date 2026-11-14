<?php

use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;

/**
 * Removes the products created by verify-webp-pipeline.php and reports the
 * before/after size of the most recent upload.
 */
$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$products = Product::where('name', 'QA WebP Pipeline Check')->get();

foreach ($products as $product) {
    echo 'Product #'.$product->id.' media: '.json_encode($product->media).PHP_EOL;

    foreach ((array) $product->media as $item) {
        $path = public_path('images/products/'.basename($item['path']));

        if (is_file($path)) {
            echo '   removing '.basename($path).' ('.round(filesize($path) / 1024, 1).' KB)'.PHP_EOL;
            @unlink($path);
        }

        // The WebP / JPEG fallback family.
        $stem = pathinfo(basename($item['path']), PATHINFO_FILENAME);
        foreach (['webp', 'jpg', 'jpeg'] as $extension) {
            $sibling = public_path('images/products/'.$stem.'.'.$extension);
            if (is_file($sibling)) {
                echo '   removing '.basename($sibling).' ('.round(filesize($sibling) / 1024, 1).' KB)'.PHP_EOL;
                @unlink($sibling);
            }
        }
    }

    $product->delete();
}

echo PHP_EOL.'Remaining test products: '.Product::where('name', 'QA WebP Pipeline Check')->count().PHP_EOL;
