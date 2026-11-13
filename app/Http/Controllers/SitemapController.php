<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Builds the XML sitemap.
     *
     * It lists the static pages, every active category and every active
     * product — each product with its cover image so the catalogue is also
     * discoverable through Google Images. The whole thing is cached for an
     * hour so a crawler hitting the URL repeatedly never touches the
     * database.
     */
    public function __invoke(): Response
    {
        $urls = Cache::remember('sitemap_urls', 3600, function () {
            $static = [
                ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
                ['loc' => route('shop.index'), 'priority' => '0.9', 'changefreq' => 'daily'],
                ['loc' => route('about.index'), 'priority' => '0.6', 'changefreq' => 'monthly'],
                ['loc' => route('how-to-apply.index'), 'priority' => '0.6', 'changefreq' => 'monthly'],
                ['loc' => route('policies.index'), 'priority' => '0.4', 'changefreq' => 'yearly'],
                ['loc' => route('contact.index'), 'priority' => '0.5', 'changefreq' => 'monthly'],
                ['loc' => route('seller.apply'), 'priority' => '0.3', 'changefreq' => 'monthly'],
            ];

            $categories = Category::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['name', 'slug', 'updated_at'])
                ->map(fn (Category $category) => [
                    'loc' => route('shop.index', ['category' => $category->slug]),
                    'lastmod' => optional($category->updated_at)->toAtomString(),
                    'priority' => '0.7',
                    'changefreq' => 'weekly',
                ])
                ->all();

            $products = [];

            Product::active()
                ->with('category')
                ->select(['id', 'name', 'slug', 'image', 'media', 'updated_at', 'category_id'])
                ->chunkById(200, function ($chunk) use (&$products) {
                    foreach ($chunk as $product) {
                        $entry = [
                            'loc' => route('products.show', $product),
                            'lastmod' => optional($product->updated_at)->toAtomString(),
                            'priority' => '0.8',
                            'changefreq' => 'weekly',
                        ];

                        if ($image = $product->cover_image_url) {
                            $entry['image'] = [
                                'loc' => $image,
                                'title' => $product->name,
                            ];
                        }

                        $products[] = $entry;
                    }
                });

            return array_merge($static, $categories, $products);
        });

        return response()
            ->view('sitemap', compact('urls'))
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
