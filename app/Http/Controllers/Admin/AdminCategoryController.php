<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class AdminCategoryController extends Controller
{
    public function index()
    {
        $categories = Category::withCount('products')
            ->ordered()
            ->paginate(15);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new Category]);
    }

    public function store(CategoryRequest $request)
    {
        $data = $this->prepareData($request);

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category added.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category)
    {
        $data = $this->prepareData($request, $category);

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete "'.$category->name.'" — it still has products assigned. Reassign or delete those products first.');
        }

        $this->deleteImage($category->image);
        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    /**
     * Normalizes the validated payload, keeps the slug unique and moves an
     * uploaded tile image into public/images/categories with a safe filename.
     */
    private function prepareData(CategoryRequest $request, ?Category $category = null): array
    {
        $data = $request->validated();

        $data['slug'] = $this->uniqueSlug($data['name'], $category);

        if (Category::hasColumn('sort_order')) {
            $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        } else {
            unset($data['sort_order']);
        }

        if (Category::hasColumn('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        } else {
            unset($data['is_active']);
        }

        if (Category::hasColumn('description')) {
            // description is already in $data from $request->validated();
        } else {
            unset($data['description']);
        }

        if (Category::hasColumn('image')) {
            // image may be set from file upload below; keep it for now,
            // and the conditional block below will unset it if the column
            // is missing and no file was uploaded.
        } else {
            unset($data['image']);
        }

        if ($request->hasFile('image') && Category::hasColumn('image')) {
            $newImage = $this->storeImage($request->file('image'));
            $this->deleteImage($category?->image);
            $data['image'] = $newImage;
        } else {
            unset($data['image']);
        }

        return $data;
    }

    /**
     * Stores the uploaded tile image through the image pipeline: EXIF is
     * stripped, the orientation flag applied, the resolution capped and a
     * WebP copy written. Returns the stored filename.
     */
    private function storeImage(UploadedFile $file): string
    {
        return app(ImageOptimizer::class)->store($file, 'images/categories', ImageOptimizer::TILE_MAX_WIDTH);
    }

    private function deleteImage(?string $filename): void
    {
        if (! $filename) {
            return;
        }

        $stem = pathinfo($filename, PATHINFO_FILENAME);

        // Remove the whole family ("<stem>.webp" + "<stem>.jpg" fallback).
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $extension) {
            $path = public_path('images/categories/'.$stem.'.'.$extension);

            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function uniqueSlug(string $name, ?Category $ignore = null): string
    {
        $baseSlug = Str::slug($name) ?: 'category';
        $slug = $baseSlug;
        $suffix = 2;

        while (Category::where('slug', $slug)
            ->when($ignore, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
