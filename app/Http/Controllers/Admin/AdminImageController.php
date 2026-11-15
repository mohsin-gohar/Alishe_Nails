<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ImageUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * "Image Doctor" — repairs products whose database record lost its image
 * reference while the photo itself is still sitting on the server.
 *
 * It never guesses and never writes to disk: it lists every real image file
 * found in the catalogue folders, shows which product records cannot resolve
 * an image, and lets the administrator attach an existing file to a product.
 * A file that is already used by another product is flagged before saving, so
 * two listings cannot be silently pointed at the same photo.
 */
class AdminImageController extends Controller
{
    public function index()
    {
        $products = Product::query()->orderBy('name')->get();

        $rows = $products->map(function (Product $product): array {
            return [
                'product' => $product,
                'image' => $product->cover_image_url,
                'stored' => $this->storedReferences($product),
            ];
        });

        $files = $this->availableFiles($products);

        return view('admin.images.index', [
            'rows' => $rows,
            'files' => $files,
            'missing' => $rows->filter(fn (array $row) => $row['image'] === null)->count(),
        ]);
    }

    public function attach(Request $request, Product $product)
    {
        $validated = $request->validate([
            'file' => ['required', 'string', 'max:255'],
        ]);

        $name = basename(str_replace('\\', '/', $validated['file']));
        $path = public_path('images/products/'.$name);

        if ($name === '' || ! is_file($path) || (int) @filesize($path) <= 0) {
            return back()->withErrors([
                'file' => 'That image file does not exist in images/products on this server any more.',
            ]);
        }

        // Keep every gallery item that still resolves, drop the dead ones,
        // then make the chosen photo the cover.
        $media = collect($product->media ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['type']) && ! empty($item['path']))
            ->filter(fn (array $item) => $product->mediaExists($item['type'], $item['path']))
            ->reject(fn (array $item) => $item['type'] === 'image' && basename($item['path']) === $name)
            ->values()
            ->all();

        array_unshift($media, ['type' => 'image', 'path' => $name]);

        $media = collect($media)
            ->values()
            ->map(fn (array $item, int $order) => [
                'type' => $item['type'],
                'path' => $item['path'],
                'order' => $order,
            ])
            ->all();

        $product->forceFill([
            'image' => $name,
            'media' => $media,
        ])->save();

        return back()->with('success', "Attached {$name} to {$product->name}.");
    }

    /**
     * The image references currently stored on the product, for display.
     *
     * @return list<string>
     */
    private function storedReferences(Product $product): array
    {
        $references = [];

        if (! blank($product->image)) {
            $references[] = (string) $product->image;
        }

        foreach ((array) ($product->gallery ?? []) as $path) {
            if (is_string($path) && $path !== '') {
                $references[] = $path;
            }
        }

        foreach ((array) ($product->media ?? []) as $item) {
            if (is_array($item) && ! empty($item['path'])) {
                $references[] = (string) $item['path'];
            }
        }

        return array_values(array_unique($references));
    }

    /**
     * Every image file available on the server, with the products already
     * pointing at it.
     *
     * @param  Collection<int, Product>  $products
     * @return Collection<int, array{name:string,url:string,size:int,used_by:list<string>,tiny:bool}>
     */
    private function availableFiles($products)
    {
        $directories = array_unique([
            public_path('images/products'),
            base_path('public/images/products'),
            storage_path('app/public/products'),
        ]);

        $usage = [];

        foreach ($products as $product) {
            foreach ($this->storedReferences($product) as $reference) {
                $usage[basename(str_replace('\\', '/', $reference))][] = $product->name;
            }
        }

        $files = collect();

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            foreach (@scandir($directory) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $path = $directory.'/'.$entry;

                if (! is_file($path) || (int) @filesize($path) <= 0) {
                    continue;
                }

                if (! in_array(strtolower(pathinfo($entry, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    continue;
                }

                if ($files->has($entry)) {
                    continue;
                }

                [$width, $height] = ImageUrl::dimensions($path);

                $files->put($entry, [
                    'name' => $entry,
                    'url' => ImageUrl::urlForFile($path),
                    'size' => (int) @filesize($path),
                    'used_by' => array_values(array_unique($usage[$entry] ?? [])),
                    // A 1×1 file is an earlier placeholder, not a photo.
                    'tiny' => $width !== null && $height !== null && $width <= 2 && $height <= 2,
                ]);
            }
        }

        return $files->sortKeys();
    }
}
