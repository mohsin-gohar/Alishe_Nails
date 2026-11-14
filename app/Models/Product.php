<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'seller_id',
        'name',
        'slug',
        'sku',
        'price',
        'compare_at_price',
        'short_description',
        'description',
        'shape',
        'length',
        'finish',
        'image',
        'gallery',
        'media',
        'badge',
        'is_best_seller',
        'is_featured',
        'is_homepage',
        'homepage_order',
        'meta_title',
        'meta_description',
        'tags',
        'stock',
        'is_active',
        'whats_included',
    ];

    protected $casts = [
        'gallery' => 'array',
        'media' => 'array',
        'whats_included' => 'array',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'is_best_seller' => 'boolean',
        'is_featured' => 'boolean',
        'is_homepage' => 'boolean',
        'homepage_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public const LOW_STOCK_THRESHOLD = 5;

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('home_collections_and_bestsellers');
            Cache::forget('home_page_data');
            Cache::forget('shop_sidebar_facets');
        });

        static::deleted(function () {
            Cache::forget('home_collections_and_bestsellers');
            Cache::forget('home_page_data');
            Cache::forget('shop_sidebar_facets');
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Only approved reviews — use this on any public-facing page. */
    public function approvedReviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlistedBy()
    {
        return $this->belongsToMany(User::class, 'wishlists')->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where(function ($query) {
            $query->whereNull('seller_id')
                ->orWhereHas('seller', fn ($seller) => $seller->where('status', 'approved')->whereHas('subscriptions', function ($subscription) {
                    $subscription->where('status', 'active')->where('expires_at', '>', now());
                }));
        });
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= self::LOW_STOCK_THRESHOLD;
    }

    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Average rating rounded to 1 decimal. Falls back to 0 when no reviews exist,
     * so views must guard against an empty star display.
     */
    public function getAverageRatingAttribute(): float
    {
        // Prefer the withAvg() precomputed attribute so listing pages
        // don't run one AVG query per product.
        if (array_key_exists('approved_reviews_avg_rating', $this->attributes)) {
            return round((float) $this->attributes['approved_reviews_avg_rating'], 1);
        }

        return round($this->approvedReviews()->avg('rating') ?? 0, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        // Prefer the withCount() precomputed attribute on listing pages.
        if (array_key_exists('approved_reviews_count', $this->attributes)) {
            return (int) $this->attributes['approved_reviews_count'];
        }

        return $this->approvedReviews()->count();
    }

    /* ------------------------------------------------------------------
     |  Media path resolution
     |
     |  Product files are stored in public/images/products (the canonical
     |  location). Shared hosts sometimes serve the project root directly
     |  or restore files into a legacy folder, so lookups also check the
     |  other places these files realistically end up. Extensions are
     |  interchangeable too: the uploader converts JPEG to WebP but the
     |  database keeps the original filename.
     * ------------------------------------------------------------------ */

    /**
     * Folders (relative to the project root or public path) that may hold
     * product media. Covers both standard Laravel layout (public/ inside
     * project root) and cPanel layouts where public files sit in the
     * document root directly (e.g. public_html/images/products).
     *
     * @return list<string>
     */
    protected static function mediaSearchDirectories(string $type): array
    {
        $dirs = $type === 'video'
            ? [
                'public/videos/products',
                'videos/products',
                'public/storage/products',
                'storage/app/public/products',
            ]
            : [
                'public/images/products',
                'images/products',
                'public/images',
                'images',
                'public/storage/products',
                'public/storage/images/products',
                'storage/app/public/products',
                'storage/app/public/images/products',
            ];

        // Also search relative to public_path() for cPanel setups where
        // public/ contents are placed directly in the web root.
        $publicBase = rtrim(str_replace('\\', '/', public_path()), '/');
        $appBase = rtrim(str_replace('\\', '/', base_path()), '/');
        if ($publicBase !== $appBase && str_starts_with($publicBase, $appBase.'/')) {
            $publicRel = substr($publicBase, strlen($appBase) + 1);
            $sub = $type === 'video' ? 'videos/products' : 'images/products';
            array_unshift($dirs, $publicRel.'/'.$sub);
        }

        // If public_path() is different from base_path()/public (e.g. symlinked
        // or custom), add its subdirectories directly.
        $sub = $type === 'video' ? 'videos/products' : 'images/products';
        $pubSub = $publicBase.'/'.$sub;
        if (is_dir($pubSub)) {
            // Will be handled via absolute path check below
        }

        return array_values(array_unique($dirs));
    }

    /**
     * Locate a media file on disk and build the URL that serves it.
     *
     * Only the basename of the database value is used, so a malformed or
     * malicious `../` path can never escape the media folders.
     *
     * @return array{file:string,url:string,name:string}|null
     */
    protected function resolveMediaFile(string $type, string $path): ?array
    {
        $type = $type === 'video' ? 'video' : 'image';

        $path = trim(str_replace('\\', '/', (string) $path));
        if ($path === '') {
            return null;
        }

        foreach (['public/storage/', 'storage/app/public/', 'public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        $path = basename($path);
        if ($path === '' || $path === '.' || $path === '..') {
            return null;
        }

        $stem = pathinfo($path, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // Prefer the stored name, then the WebP derivative, then the
        // raster fallbacks. One upload can legitimately produce both a
        // `.webp` and a `.jpg`, and old rows may point at either.
        $names = [$path];
        $extensions = $type === 'video'
            ? ['mp4', 'webm', 'mov', 'm4v']
            : ['webp', 'jpg', 'jpeg', 'png'];

        foreach ($extensions as $candidate) {
            if ($extension !== $candidate) {
                $names[] = $stem.'.'.$candidate;
            }
        }

        foreach (self::mediaSearchDirectories($type) as $directory) {
            foreach ($names as $name) {
                // Try base_path + relative directory
                $file = base_path($directory.'/'.$name);
                if (is_file($file) && (int) @filesize($file) > 0) {
                    return [
                        'file' => $file,
                        'url' => $this->mediaFileUrl($file),
                        'name' => $name,
                    ];
                }
                // Also try as absolute path (for custom public_path layouts)
                $absFile = $directory.'/'.$name;
                if (str_starts_with($directory, '/') || (strlen($directory) > 1 && $directory[1] === ':')) {
                    if (is_file($absFile) && (int) @filesize($absFile) > 0) {
                        return [
                            'file' => $absFile,
                            'url' => $this->mediaFileUrl($absFile),
                            'name' => $name,
                        ];
                    }
                }
            }
        }

        // Direct check in public_path() subdirectory as last resort
        $sub = $type === 'video' ? 'videos/products' : 'images/products';
        foreach ($names as $name) {
            $file = public_path($sub.'/'.$name);
            if (is_file($file) && (int) @filesize($file) > 0) {
                return [
                    'file' => $file,
                    'url' => $this->mediaFileUrl($file),
                    'name' => $name,
                ];
            }
            // Also check storage public path directly (symlink may be missing/broken)
            $storageFile = storage_path('app/public/'.$sub.'/'.$name);
            if (is_file($storageFile) && (int) @filesize($storageFile) > 0) {
                return [
                    'file' => $storageFile,
                    'url' => $this->mediaFileUrl($storageFile),
                    'name' => $name,
                ];
            }
        }

        return null;
    }

    /** Turn an absolute media file path into a browser-facing URL. */
    protected function mediaFileUrl(string $file): string
    {
        $file = str_replace('\\', '/', $file);
        $public = rtrim(str_replace('\\', '/', public_path()), '/');

        if (str_starts_with($file, $public.'/')) {
            return $this->rootRelativeUrl(substr($file, strlen($public) + 1));
        }

        $base = rtrim(str_replace('\\', '/', base_path()), '/');
        $relative = str_starts_with($file, $base.'/')
            ? substr($file, strlen($base) + 1)
            : basename($file);

        if (str_starts_with($relative, 'storage/app/public/')) {
            $relative = 'storage/'.substr($relative, strlen('storage/app/public/'));
        }

        return $this->rootRelativeUrl($relative);
    }

    /**
     * Root-relative URL ("/images/products/foo.webp") computed from the
     * actual filesystem location. Works for both root-domain deployments
     * and subdirectory installs (e.g. localhost/alishe-nails/public/).
     * Avoids APP_URL mismatches that break image loading.
     */
    protected function rootRelativeUrl(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $public = rtrim(str_replace('\\', '/', public_path()), '/');
        $base = rtrim(str_replace('\\', '/', base_path()), '/');
        $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');

        // If it's an absolute filesystem path, compute web path from it
        if (str_starts_with($path, $public.'/')) {
            $relative = substr($path, strlen($public));
            // If document root is the public folder, serve from /
            if ($docRoot !== '' && $public === $docRoot) {
                return $relative;
            }
            // If document root is the base folder and public/ exists inside it
            if ($docRoot !== '' && $base === $docRoot && str_starts_with($public, $docRoot.'/')) {
                $publicRel = substr($public, strlen($docRoot));

                return $publicRel.$relative;
            }
            // Try using asset() path extraction as fallback
            $assetUrl = asset(ltrim($relative, '/'));
            $assetPath = parse_url($assetUrl, PHP_URL_PATH);
            if (is_string($assetPath) && $assetPath !== '') {
                return $assetPath;
            }

            return $relative;
        }

        if (str_starts_with($path, $base.'/')) {
            $relative = substr($path, strlen($base));
            if ($docRoot !== '' && $base === $docRoot) {
                return $relative;
            }
            $assetUrl = asset(ltrim($relative, '/'));
            $assetPath = parse_url($assetUrl, PHP_URL_PATH);
            if (is_string($assetPath) && $assetPath !== '') {
                return $assetPath;
            }

            return $relative;
        }

        // Already a relative/web path
        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }
        if (str_starts_with($path, 'storage/app/public/')) {
            $path = 'storage/'.substr($path, 19);
        }

        $clean = '/'.ltrim($path, '/');
        // Use asset() to respect subdirectory base path
        $assetUrl = asset(ltrim($clean, '/'));
        $assetPath = parse_url($assetUrl, PHP_URL_PATH);
        if (is_string($assetPath) && $assetPath !== '') {
            return $assetPath;
        }

        return $clean;
    }

    /**
     * Last-resort image discovery when DB media records are empty or all
     * point at missing files. Tries:
     *  1. Exact slug-named file (<slug>.webp/.jpg/...)
     *  2. Any file in the product image directories whose normalized name
     *     starts with or contains the product slug/name (handles admin
     *     uploads with timestamp suffixes, or manually placed files).
     *
     * @return array{file:string,url:string,name:string}|null
     */
    protected function resolveFallbackImage(): ?array
    {
        $stems = array_values(array_filter(array_unique([
            (string) $this->slug,
            Str::slug((string) $this->name),
            // Also try without common suffixes like "with-application-kit"
            preg_replace('/-with-application-kit$/', '', (string) $this->slug),
            preg_replace('/-with-application-kit$/', '', Str::slug((string) $this->name)),
        ])));

        // 1) Exact slug-named file
        foreach ($stems as $stem) {
            $resolved = $this->resolveMediaFile('image', $stem.'.webp');
            if ($resolved !== null) {
                return $resolved;
            }
        }

        // 2) Scan product image directories for best fuzzy match
        $candidates = $this->collectProductImageFiles();
        if ($candidates === []) {
            return null;
        }

        $bestFile = null;
        $bestScore = -1;

        foreach ($candidates as $file) {
            $basename = basename($file);
            $fileKey = Str::slug(pathinfo($basename, PATHINFO_FILENAME));

            foreach ($stems as $stem) {
                $stemKey = Str::slug($stem);
                if ($stemKey === '' || strlen($stemKey) < 3) {
                    continue;
                }

                $score = -1;
                if ($fileKey === $stemKey) {
                    $score = 100;
                } elseif (str_starts_with($fileKey, $stemKey.'-') || str_starts_with($fileKey, $stemKey.'_')) {
                    $score = 90;
                } elseif (str_starts_with($stemKey, $fileKey.'-') || str_starts_with($stemKey, $fileKey.'_')) {
                    $score = 80;
                } elseif (strlen($stemKey) >= 6 && str_contains($fileKey, $stemKey)) {
                    $score = 70;
                } elseif (strlen($stemKey) >= 10 && str_contains($stemKey, $fileKey) && strlen($fileKey) >= 6) {
                    $score = 60;
                }

                // Prefer webp over jpg for same score
                if ($score >= 0) {
                    $ext = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
                    $extBonus = match ($ext) {
                        'webp' => 3, 'jpg', 'jpeg' => 2, 'png' => 1, default => 0,
                    };
                    $total = $score * 10 + $extBonus;
                    if ($total > $bestScore) {
                        $bestScore = $total;
                        $bestFile = $file;
                    }
                }
            }
        }

        if ($bestFile !== null) {
            $name = basename($bestFile);

            return [
                'file' => $bestFile,
                'url' => $this->mediaFileUrl($bestFile),
                'name' => $name,
            ];
        }

        return null;
    }

    /**
     * Collect all image files from known product image directories.
     * Cached per-request via static to avoid repeated filesystem scans.
     *
     * @return list<string>
     */
    protected function collectProductImageFiles(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $files = [];
        $seen = [];

        foreach (self::mediaSearchDirectories('image') as $directory) {
            $dir = base_path($directory);
            if (! is_dir($dir)) {
                continue;
            }
            $entries = @scandir($dir);
            if ($entries === false) {
                continue;
            }
            foreach ($entries as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $full = $dir.'/'.$entry;
                if (! is_file($full) || (int) @filesize($full) <= 0) {
                    continue;
                }
                $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                    continue;
                }
                $key = strtolower($entry);
                if (isset($seen[$key])) {
                    continue;
                }
                $seen[$key] = true;
                $files[] = $full;
            }
        }

        // Also check public_path() subdirectory directly
        $pubDir = public_path('images/products');
        if (is_dir($pubDir)) {
            $entries = @scandir($pubDir);
            if ($entries !== false) {
                foreach ($entries as $entry) {
                    if ($entry === '.' || $entry === '..') {
                        continue;
                    }
                    $full = $pubDir.'/'.$entry;
                    if (! is_file($full) || (int) @filesize($full) <= 0) {
                        continue;
                    }
                    $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
                    if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                        continue;
                    }
                    $key = strtolower($entry);
                    if (isset($seen[$key])) {
                        continue;
                    }
                    $seen[$key] = true;
                    $files[] = $full;
                }
            }
        }

        $cache = $files;

        return $cache;
    }

    /**
     * Absolute public URL to the primary product image.
     * Returns null (not a fake path) when no image has been set,
     * so the Blade layer can render a clearly-labelled placeholder instead.
     */
    public function getImageUrlAttribute(): ?string
    {
        $resolved = $this->resolveMediaFile('image', (string) $this->image);
        if ($resolved !== null) {
            return $resolved['url'];
        }

        return $this->resolveFallbackImage()['url'] ?? null;
    }

    public function getGalleryUrlsAttribute(): array
    {
        return collect($this->gallery ?? [])
            ->map(fn ($file) => $this->resolveMediaFile('image', (string) $file)['url'] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /* ------------------------------------------------------------------
     |  Unified ordered media gallery ({ type: image|video, path, order })
     * ------------------------------------------------------------------ */

    /**
     * Ordered media items, always non-empty-safe. Falls back to the legacy
     * `image` + `gallery` columns for products saved before the `media`
     * column existed, so historical rows keep rendering unchanged.
     */
    public function getMediaItemsAttribute(): array
    {
        $items = collect($this->media ?? [])
            ->filter(fn ($item) => is_array($item) && ! empty($item['type']) && ! empty($item['path']))
            ->unique(fn ($item) => $item['type'].'|'.$item['path'])
            ->sortBy('order')
            ->values();

        if ($items->isNotEmpty()) {
            return $items->all();
        }

        $legacy = [];

        if (! empty($this->attributes['image'])) {
            $legacy[] = ['type' => 'image', 'path' => $this->attributes['image'], 'order' => 0];
        }

        foreach ((array) ($this->gallery ?? []) as $path) {
            // Older rows stored the cover image in both `image` and
            // `gallery`; skip the duplicate so galleries never repeat.
            if ($path === $this->attributes['image']) {
                continue;
            }

            $legacy[] = ['type' => 'image', 'path' => $path, 'order' => count($legacy)];
        }

        return $legacy;
    }

    public function mediaUrl(string $type, string $path): string
    {
        $resolved = $this->resolveMediaFile($type, $path);
        if ($resolved !== null) {
            return $resolved['url'];
        }

        $folder = $type === 'video' ? 'videos/products' : 'images/products';

        return $this->rootRelativeUrl($folder.'/'.basename($path));
    }

    public function mediaExists(string $type, string $path): bool
    {
        return $this->resolveMediaFile($type, $path) !== null;
    }

    /**
     * Render-ready media list: only items whose file actually exists, as
     * ['type', 'url']. Views render one slot per entry — a product with a
     * single image gets exactly one slot and never an empty placeholder.
     * When DB media items point at missing files, falls back to any
     * slug-named image found on disk (<slug>.webp / <slug>.jpg / …).
     */
    public function getMediaUrlsAttribute(): array
    {
        $urls = collect($this->media_items)
            ->map(function (array $item): ?array {
                $resolved = $this->resolveMediaFile($item['type'], $item['path']);

                return $resolved === null ? null : [
                    'type' => $item['type'],
                    'url' => $resolved['url'],
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($urls === []) {
            $fallback = $this->resolveFallbackImage();
            if ($fallback !== null) {
                $urls[] = ['type' => 'image', 'url' => $fallback['url']];
            }
        }

        return $urls;
    }

    /** First media item in the gallery (image or video). */
    public function getCoverMediaAttribute(): ?array
    {
        return $this->media_items[0] ?? null;
    }

    /**
     * URL of the first IMAGE in the gallery — used for small thumbnails
     * (cart rows, admin lists) and OG tags where a video won't do.
     */
    public function getCoverImageUrlAttribute(): ?string
    {
        foreach ($this->media_items as $item) {
            if ($item['type'] !== 'image') {
                continue;
            }

            $resolved = $this->resolveMediaFile('image', $item['path']);
            if ($resolved !== null) {
                return $resolved['url'];
            }
        }

        return $this->resolveFallbackImage()['url'] ?? null;
    }

    /** Filename of the first IMAGE in the gallery (stored in cart rows). */
    public function getCoverImagePathAttribute(): ?string
    {
        foreach ($this->media_items as $item) {
            if ($item['type'] !== 'image') {
                continue;
            }

            $resolved = $this->resolveMediaFile('image', $item['path']);
            if ($resolved !== null) {
                return $resolved['name'];
            }
        }

        return $this->resolveFallbackImage()['name'] ?? null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
