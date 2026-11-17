<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    /**
     * Total number of products surfaced by the two homepage product rails.
     */
    public const SHOWCASE_SIZE = 20;

    /**
     * Hero video locations, in priority order. The first file that actually
     * exists on disk wins, so the homepage keeps working whether the clip was
     * uploaded as `videos/alishe.mp4` (the canonical name) or still lives in
     * the older `vedios/` folder from a previous deployment.
     *
     * The resolver scans the public `videos/` and `vedios/` folders, so any
     * newly dropped hero clip (or an updated re-upload of an existing one) is
     * picked up automatically without a code change.
     *
     * @return list<string>
     */
    public static function heroVideoCandidates(): array
    {
        $directories = [
            'videos' => public_path('videos'),
            'vedios' => public_path('vedios'),
        ];

        $candidates = [];

        foreach ($directories as $prefix => $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $extensions = ['mp4', 'webm'];

            foreach ($extensions as $extension) {
                foreach (glob($directory.'/*.'.strtolower($extension)) ?: [] as $file) {
                    // The user's named hero video always wins, even if its
                    // filemtime is older than a leftover clip in the folder.
                    $isHero = (strtolower(basename($file)) === 'headervedio.mp4');

                    $candidates[] = [
                        'path' => $prefix.'/'.basename($file),
                        'mtime' => $isHero ? PHP_INT_MAX : (int) filemtime($file),
                    ];
                }
            }
        }

        usort($candidates, fn (array $a, array $b) => $b['mtime'] <=> $a['mtime']);

        return array_values(array_map(fn (array $candidate) => $candidate['path'], $candidates));
    }

    public function index()
    {
        $data = Cache::remember('home_page_data', 300, function () {
            // Use Category::active() which has a guard: only applies where('is_active', true)
            // if the column exists in the database (protection for older deployments).
            $collections = Category::active()
                ->whereHas('products', function ($q) {
                    // This constraint runs on the PRODUCTS relation query,
                    // so guard against the products table (not categories).
                    if (Product::hasColumn('is_active')) {
                        $q->where('is_active', true);
                    }
                })
                ->withCount(['products' => function ($q) {
                    // The constraint runs on the PRODUCTS relation query, so
                    // guard against the products table (not categories).
                    if (Product::hasColumn('is_active')) {
                        $q->where('is_active', true);
                    }
                }])
                ->ordered()
                ->take(4)
                ->get();

            $bestSellers = Product::active()
                ->where('is_best_seller', true)
                ->with('seller')
                ->withCount('approvedReviews')
                ->withAvg('approvedReviews', 'rating')
                ->latest()
                ->take(4)
                ->get();

            // Hand-picked homepage lineup, in the exact order saved from the
            // admin Homepage Studio. If the admin has not curated anything
            // yet, fall back to the previous automatic merchandising logic so
            // the storefront is never empty.
            $showcaseProducts = Product::active()
                ->isHomepage()
                ->orderByHomepage()
                ->with(['category', 'seller'])
                ->withCount('approvedReviews')
                ->withAvg('approvedReviews', 'rating')
                ->orderBy('homepage_order')
                ->orderBy('id')
                ->take(self::SHOWCASE_SIZE)
                ->get();

            if ($showcaseProducts->isEmpty()) {
                $showcaseProducts = Product::active()
                    ->with(['category', 'seller'])
                    ->withCount('approvedReviews')
                    ->withAvg('approvedReviews', 'rating')
                    ->orderByDesc('is_best_seller')
                    ->orderByDesc('is_featured')
                    ->latest()
                    ->take(self::SHOWCASE_SIZE)
                    ->get();
            }

            return compact('collections', 'bestSellers', 'showcaseProducts');
        });

        $collections = $data['collections'];
        $bestSellers = $data['bestSellers'];
        $showcaseProducts = $data['showcaseProducts'];

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
            'showcaseProducts',
            'heroVideoUrl',
            'heroPosterUrl'
        ));
    }
}
