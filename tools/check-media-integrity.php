<?php

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Console\Kernel;

$root = dirname(__DIR__);

require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$missing = [];
$products = Product::all();

foreach ($products as $product) {
    foreach ($product->media_items as $item) {
        $folder = $item['type'] === 'video' ? 'videos/products' : 'images/products';
        $path = public_path($folder.'/'.basename($item['path']));

        if (! is_file($path)) {
            $missing[] = "product #{$product->id} ({$product->slug}): {$folder}/{$item['path']}";
        }
    }
}

echo 'Products checked: '.$products->count().PHP_EOL;
echo 'Missing media files: '.count($missing).PHP_EOL;

foreach ($missing as $line) {
    echo '  '.$line.PHP_EOL;
}

echo PHP_EOL.'--- categories ---'.PHP_EOL;
$missingCategories = 0;
foreach (Category::whereNotNull('image')->get() as $category) {
    if (! is_file(public_path('images/categories/'.$category->image))) {
        echo '  category '.$category->slug.': '.$category->image.PHP_EOL;
        $missingCategories++;
    }
}
echo 'Categories with a missing image: '.$missingCategories.PHP_EOL;

echo PHP_EOL.'--- orphaned files on disk ---'.PHP_EOL;
$referenced = [];
foreach ($products as $product) {
    foreach ($product->media_items as $item) {
        $referenced[pathinfo($item['path'], PATHINFO_FILENAME)] = true;
    }
}

$orphans = 0;
foreach (glob(public_path('images/products/*')) ?: [] as $file) {
    $name = basename($file);
    $stem = pathinfo($name, PATHINFO_FILENAME);

    if (! isset($referenced[$stem])) {
        echo '  '.$name.PHP_EOL;
        $orphans++;
    }
}
echo 'Orphaned product images: '.$orphans.PHP_EOL;
