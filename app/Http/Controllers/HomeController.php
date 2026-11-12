<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\InstagramFeedService;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Total number of products surfaced by the two homepage product rails.
     */
    public const SHOWCASE_SIZE = 20;

    /**
     * Cards rendered per homepage product rail.
     */
    public const SHOWCASE_RAIL_SIZE = 10;

    /**
     * Hero video locations, in priority order. The first file that actually
     * exists on disk wins, so the homepage keeps working whether the clip was
     * uploaded as `videos/alishe.mp4` (the canonical name) or still lives in
     * the older `vedios/` folder from a previous deployment.
     *
     * @return list<string>
     */
    public static function heroVideoCandidates(): array
    {
        return [
            'videos/alishe.mp4',
            'vedios/headervedio.mp4',
        ];
    }

    public function index(InstagramFeedService $instagram)
    {
        [$collections, $bestSellers, $showcaseProducts] = Cache::remember('home_collections_and_bestsellers', 300, function () {
            return [
                Category::whereHas('products', fn ($q) => $q->where('is_active', true))
                    ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
                    ->take(4)
                    ->get(),
                Product::active()
                    ->where('is_best_seller', true)
                    ->with('seller')
                    ->withCount('approvedReviews')
                    ->withAvg('approvedReviews', 'rating')
                    ->latest()
                    ->take(4)
                    ->get(),
                // Homepage rails: best sellers first, then featured pieces, then
                // newest arrivals so a shopper always sees fresh stock too.
                Product::active()
                    ->with(['category', 'seller'])
                    ->withCount('approvedReviews')
                    ->withAvg('approvedReviews', 'rating')
                    ->orderByDesc('is_best_seller')
                    ->orderByDesc('is_featured')
                    ->latest()
                    ->take(self::SHOWCASE_SIZE)
                    ->get(),
            ];
        });

        // Two balanced rails of ten cards each (top 20 products in total).
        $showcasePrimary = $showcaseProducts->take(self::SHOWCASE_RAIL_SIZE)->values();
        $showcaseSecondary = $showcaseProducts->slice(self::SHOWCASE_RAIL_SIZE)->values();

        // Reads only from the local instagram_posts table (the sync cache),
        // so API/token problems can never crash or slow the homepage.
        $instagramPosts = $instagram->latestFeed((int) config('services.instagram.feed_size', 8));

        // Hero video: resolve to a browser-ready URL, or null when no clip has
        // been uploaded yet so the view can fall back to a still image.
        $heroVideo = collect(self::heroVideoCandidates())
            ->first(fn (string $path) => file_exists(public_path($path)));
        $heroVideoUrl = $heroVideo ? asset($heroVideo) : null;

        $heroPoster = collect(['images/collections/everyday-elegance.jpg', 'images/logo.jpeg'])
            ->first(fn (string $path) => file_exists(public_path($path)));
        $heroPosterUrl = $heroPoster ? asset($heroPoster) : null;

        return view('home.index', compact(
            'collections',
            'bestSellers',
            'showcasePrimary',
            'showcaseSecondary',
            'instagramPosts',
            'heroVideoUrl',
            'heroPosterUrl'
        ));
    }
}
