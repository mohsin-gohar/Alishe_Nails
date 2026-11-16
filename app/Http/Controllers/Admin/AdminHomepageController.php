<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminHomepageController extends Controller
{
    /**
     * Maximum number of products that can be featured on the homepage.
     * Keeps the storefront fast and the grid visually balanced.
     */
    public const MAX_ITEMS = 20;

    public function index()
    {
        $products = Product::with('category')
            ->orderBy('name')
            ->get();

        $selected = $products
            ->whereIsHomepage()
            ->sortBy(fn (Product $product) => [$product->homepage_order ?? PHP_INT_MAX, $product->id])
            ->values();

        return view('admin.homepage.index', [
            'products' => $products,
            'selected' => $selected,
            'maxItems' => self::MAX_ITEMS,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'products' => ['nullable', 'array', 'max:'.self::MAX_ITEMS],
            'products.*' => ['integer', 'distinct', 'exists:products,id'],
        ], [
            'products.max' => 'You can feature at most '.self::MAX_ITEMS.' products on the homepage.',
        ]);

        $ids = collect($validated['products'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($ids) {
            // Clear the previous lineup first. Use hasColumn guards so the
            // migration-less production DB does not crash.
            if (Product::hasColumn('is_homepage')) {
                Product::query()->update(['is_homepage' => false]);
            }
            if (Product::hasColumn('homepage_order')) {
                Product::query()->update(['homepage_order' => null]);
            }

            foreach ($ids as $index => $id) {
                $updates = [];
                if (Product::hasColumn('is_homepage')) {
                    $updates['is_homepage'] = true;
                }
                if (Product::hasColumn('homepage_order')) {
                    $updates['homepage_order'] = $index + 1;
                }
                Product::whereKey($id)->update($updates);
            }
        });

        Cache::forget('home_page_data');
        Cache::forget('home_collections_and_bestsellers');
        Cache::forget('shop_sidebar_facets');

        $message = $ids->isEmpty()
            ? 'Homepage lineup cleared. The storefront will use automatic merchandising until you pick products again.'
            : $ids->count().' product(s) saved to the homepage lineup.';

        return redirect()->route('admin.homepage.index')->with('success', $message);
    }
}
