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
     * Folders (relative to the project root) that may hold product media.
     * The canonical Laravel location always comes first.
     *
     * @return list<string>
     */
    protected static function mediaSearchDirectories(string $type): array
    {
        if ($type === 'video') {
            return [
                'public/videos/products',
                'videos/products',
                'public/storage/products',
                'storage/app/public/products',
            ];
        }

        return [
            'public/images/products',
            'images/products',
            'public/images',
            'images',
            'public/storage/products',
            'public/storage/images/products',
            'storage/app/public/products',
            'storage/app/public/images/products',
        ];
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
                $file = base_path($directory.'/'.$name);
                if (is_file($file) && (int) @filesize($file) > 0) {
                    return [
                        'file' => $file,
                        'url' => $this->mediaFileUrl($file),
                        'name' => $name,
                    ];
                }
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
            return asset(substr($file, strlen($public) + 1));
        }

        $base = rtrim(str_replace('\\', '/', base_path()), '/');
        $relative = str_starts_with($file, $base.'/')
            ? substr($file, strlen($base) + 1)
            : basename($file);

        if (str_starts_with($relative, 'storage/app/public/')) {
            $relative = 'storage/'.substr($relative, strlen('storage/app/public/'));
        }

        return asset($relative);
    }

    /**
     * Last-resort image named after the product itself
     * (`<slug>.webp`, `<slug>.jpg`, ...). This lets an admin drop a photo
     * into public/images/products without touching the media records.
     *
     * @return array{file:string,url:string,name:string}|null
     */
    protected function resolveFallbackImage(): ?array
    {
        $stems = array_filter(array_unique([
            (string) $this->slug,
            Str::slug((string) $this->name),
        ]));

        foreach ($stems as $stem) {
            $resolved = $this->resolveMediaFile('image', $stem.'.webp');
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
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

        return asset($folder.'/'.basename($path));
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
