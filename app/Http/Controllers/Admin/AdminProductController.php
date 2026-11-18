<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category')->latest();

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where('name', 'like', "%{$search}%");
        }

        $products = $query->paginate(15)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();

        return view('admin.products.form', [
            'product' => new Product,
            'categories' => $categories,
        ]);
    }

    public function store(ProductRequest $request)
    {
        $validated = $this->validatedProductData($request);

        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product added.');
    }

    public function edit(Product $product)
    {
        $categories = Category::all();

        return view('admin.products.form', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $oldMedia = collect($product->media ?? [])->pluck('path')->filter()->all();

        $validated = $this->validatedProductData($request, $product);

        $product->update($validated);

        // Clean up files the admin removed from the gallery during this edit.
        $kept = collect($validated['media'] ?? [])->pluck('path')->all();
        $this->deleteMediaFiles(array_diff($oldMedia, $kept), $product->id);

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $mediaPaths = collect($product->media ?? [])->pluck('path')->filter()->all();

        if ($product->image) {
            $mediaPaths[] = $product->image;
        }

        $productId = $product->id;
        $product->delete();
        $this->deleteMediaFiles($mediaPaths, $productId);

        return back()->with('success', 'Product deleted.');
    }

    /**
     * Deletes orphaned media files from disk. Filenames are basenames only
     * (the database never stores paths), so path traversal is impossible.
     * Files still referenced by another product are always preserved.
     *
     * @param  array<int, string>  $paths
     */
    private function deleteMediaFiles(array $paths, ?int $exceptProductId = null): void
    {
        $candidates = collect($paths)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->map(fn (string $path) => basename($path))
            ->unique()
            ->values();

        if ($candidates->isEmpty()) {
            return;
        }

        // Build the set of filenames still in use by other products so an
        // image shared between two listings is never removed underneath them.
        // Stems are compared (not full names) because one upload produces a
        // "<stem>.webp" plus a "<stem>.jpg" fallback.
        $referenced = collect();

        Product::query()
            ->when($exceptProductId, fn ($query) => $query->whereKeyNot($exceptProductId))
            ->get(['image', 'gallery', 'media'])
            ->each(function (Product $other) use ($referenced) {
                if ($other->image) {
                    $referenced->push(pathinfo(basename($other->image), PATHINFO_FILENAME));
                }

                foreach ((array) ($other->gallery ?? []) as $path) {
                    if (is_string($path) && $path !== '') {
                        $referenced->push(pathinfo(basename($path), PATHINFO_FILENAME));
                    }
                }

                foreach ((array) ($other->media ?? []) as $item) {
                    if (is_array($item) && ! empty($item['path'])) {
                        $referenced->push(pathinfo(basename($item['path']), PATHINFO_FILENAME));
                    }
                }
            });

        $referenced = $referenced->flip();

        foreach ($candidates as $basename) {
            // An optimised image is stored as "<stem>.webp" plus a "<stem>.jpg"
            // fallback, so the whole family has to go — otherwise orphaned
            // files pile up in the public folder forever.
            $stem = pathinfo($basename, PATHINFO_FILENAME);

            if ($referenced->has($stem)) {
                continue;
            }

            $extensions = array_unique([pathinfo($basename, PATHINFO_EXTENSION), 'webp', 'jpg', 'jpeg', 'png']);

            foreach (['images/products', 'videos/products'] as $directory) {
                foreach ($extensions as $extension) {
                    if ($extension === '') {
                        continue;
                    }

                    $file = public_path($directory.'/'.$stem.'.'.$extension);

                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
            }
        }
    }

    private function validatedProductData(ProductRequest $request, ?Product $product = null): array
    {
        $validated = $request->validated();

        $validated['slug'] = $this->uniqueSlug($validated['name'], $product);
        $validated['sku'] = $validated['sku'] ?? 'ALN-'.strtoupper(Str::random(6));
        $validated['is_best_seller'] = $request->boolean('is_best_seller');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['is_active'] = $product === null
            ? ($request->has('is_active') ? $request->boolean('is_active') : true)
            : $request->boolean('is_active');

        // Homepage curation. A newly selected product is appended to the end
        // of the lineup; the dedicated Homepage Studio handles explicit
        // ordering. Unchecking clears the product from the lineup entirely.
        // Guarded so saving works on a database that has not yet run the
        // merchandising migration instead of throwing an unknown-column error.
        if (Product::hasColumn('is_homepage')) {
            $validated['is_homepage'] = $request->boolean('is_homepage');
        } else {
            unset($validated['is_homepage']);
        }

        if (Product::hasColumn('homepage_order')) {
            if (! empty($validated['is_homepage'])) {
                $validated['homepage_order'] = $product?->is_homepage && $product?->homepage_order
                    ? $product->homepage_order
                    : (int) Product::where('is_homepage', true)->max('homepage_order') + 1;
            } else {
                $validated['homepage_order'] = null;
            }
        } else {
            unset($validated['homepage_order']);
        }

        foreach (['meta_title', 'meta_description', 'tags'] as $seoColumn) {
            if (! Product::hasColumn($seoColumn)) {
                unset($validated[$seoColumn]);
            }
        }

        // Persist the ordered mixed media gallery (see buildOrderedMedia).
        $validated['media'] = $this->buildOrderedMedia($request);

        unset($validated['existing_media'], $validated['media_files'], $validated['media_types']);

        return $validated;
    }

    /**
     * Merges the already-uploaded items the admin kept ("type|path" packed
     * strings) with the newly picked files into ONE ordered gallery — the
     * array keys carry each item's position, so images and videos can be
     * freely interleaved. Returns [{ type, path, order }, ...].
     */
    private function buildOrderedMedia(ProductRequest $request): array
    {
        $items = [];

        foreach ($request->input('existing_media', []) as $position => $packed) {
            if (! is_string($packed) || ! str_contains($packed, '|')) {
                continue;
            }

            [$type, $path] = explode('|', $packed, 2);
            $items[(int) $position] = ['type' => $type, 'path' => $path];
        }

        foreach ($request->file('media_files', []) as $position => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $type = $request->input('media_types.'.$position) === 'video' ? 'video' : 'image';
            $items[(int) $position] = ['type' => $type, 'path' => $this->storeMediaFile($file, $type)];
        }

        ksort($items);

        return collect($items)->values()->map(fn (array $item, int $order) => [
            'type' => $item['type'],
            'path' => $item['path'],
            'order' => $order,
        ])->all();
    }

    /**
     * Saves an uploaded media file with the same safe-filename pattern as
     * before (slug + timestamp + random suffix) — the client-supplied
     * original filename is never trusted directly, which rules out path
     * traversal / double-extension tricks. ProductRequest's mimes + type
     * matching rules already reject bad uploads before this ever runs.
     * Images go to public/images/products, videos to public/videos/products.
     *
     * Images additionally pass through the image pipeline, which strips EXIF,
     * applies the orientation flag, caps the resolution and stores a WebP
     * copy — a 4 MB phone photo lands as a ~90 KB WebP.
     */
    private function storeMediaFile(UploadedFile $file, string $type): string
    {
        if ($type === 'image') {
            return app(ImageOptimizer::class)->store($file, 'images/products');
        }

        $mime = $file->getMimeType();
        $allowedExtensions = [
            'video/mp4' => 'mp4',
            'application/mp4' => 'mp4',
            'video/webm' => 'webm',
            'audio/webm' => 'webm',
        ];
        $extension = $allowedExtensions[$mime] ?? strtolower($file->getClientOriginalExtension());
        $filename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.time().'-'.Str::random(6).'.'.$extension;

        $directory = 'videos/products';

        if (! is_dir(public_path($directory))) {
            mkdir(public_path($directory), 0775, true);
        }

        $file->move(public_path($directory), $filename);

        return $filename;
    }

    private function uniqueSlug(string $name, ?Product $ignore = null): string
    {
        $baseSlug = Str::slug($name) ?: 'product';
        $slug = $baseSlug;
        $suffix = 2;

        while (Product::where('slug', $slug)
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
