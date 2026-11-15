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

    /** @var array{byLower: array<string,string>, byKey: array<string,list<string>>}|null */
    protected static ?array $imageMediaIndex = null;

    /** @var array{byLower: array<string,string>, byKey: array<string,list<string>>}|null */
    protected static ?array $videoMediaIndex = null;

    protected static ?string $imageMediaIndexSignature = null;

    protected static ?string $videoMediaIndexSignature = null;

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

        return array_values(array_unique($dirs));
    }

    /**
     * Locate a media file on disk and build the URL that serves it.
     *
     * Only the basename of the database value is used, so a malformed or
     * malicious `../` path can never escape the media folders.
     *
     * Lookup order:
     *   1. exact filename in every known media folder;
     *   2. the same name under one of the interchangeable extensions;
     *   3. case-insensitive filename (Linux is case-sensitive, uploads are not);
     *   4. the filename with its upload timestamp suffix stripped;
     *   5. the file whose normalised name is the ONLY match for the record.
     *
     * Every step is a real file check — nothing is guessed.
     *
     * @return array{file:string,url:string,name:string}|null
     */
    protected function resolveMediaFile(string $type, string $path): ?array
    {
        $type = $type === 'video' ? 'video' : 'image';

        $path = self::normaliseMediaPath($path);

        if ($path === '') {
            return null;
        }

        $basename = basename($path);

        if ($basename === '' || $basename === '.' || $basename === '..') {
            return null;
        }

        $stem = pathinfo($basename, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));

        // Prefer the stored name, then the WebP derivative, then the
        // raster fallbacks. One upload can legitimately produce both a
        // `.webp` and a `.jpg`, and old rows may point at either.
        $names = [$basename];

        foreach (self::mediaExtensions($type) as $candidate) {
            if ($extension !== $candidate) {
                $names[] = $stem.'.'.$candidate;
            }
        }

        // A record saved with the original upload name ("WhatsApp Image
        // 2026-09-06 at 12.26.28 PM.jpeg") still has to find the slugged file
        // the uploader wrote ("whatsapp-image-2026-09-06-at-122628-pm.jpeg").
        $slugStem = Str::slug($stem);

        if ($slugStem !== '' && $slugStem !== $stem) {
            $names[] = $slugStem.'.'.$extension;

            foreach (self::mediaExtensions($type) as $candidate) {
                if ($extension !== $candidate) {
                    $names[] = $slugStem.'.'.$candidate;
                }
            }
        }

        // 1. Direct hit in every known directory.
        foreach (self::mediaSearchDirectories($type) as $directory) {
            $dir = self::absoluteMediaDirectory($directory);

            foreach ($names as $name) {
                if (self::isUsableMediaFile($dir.'/'.$name)) {
                    return $this->foundMediaFile($dir.'/'.$name);
                }
            }
        }

        $index = self::mediaIndex($type);

        // 2. Case-insensitive match.
        foreach ($names as $name) {
            $match = $index['byLower'][strtolower($name)] ?? null;

            if ($match !== null) {
                return $this->foundMediaFile($match);
            }
        }

        // 3. Same normalised stem (handles a lost/changed extension and the
        //    "slug-<timestamp>-<random>" names written by the uploader).
        $key = self::mediaFileKey($stem);

        if ($key !== '' && isset($index['byKey'][$key])) {
            $photos = self::groupByPhoto($index['byKey'][$key]);

            // Several files share the stem: usually the WebP plus its JPEG
            // fallback of one upload. Only then is picking one safe — two
            // different photos with the same normalised name must never be
            // guessed.
            if (count($photos) === 1) {
                return $this->foundMediaFile(self::bestOfPhoto(array_values($photos)[0]));
            }
        }

        // 4. Punctuation-insensitive match. Rows saved by the old uploader
        //    keep the client filename ("WhatsApp Image 2026-09-06 at
        //    12.26.28 PM.jpeg") while the file on disk is the slugged,
        //    de-duplicated name ("whatsapp-image-2026-09-06-at-122628-pm-1-
        //    1788693359-w5NXXQ.jpeg"). Dropping every separator makes both
        //    sides comparable. A match is only accepted when exactly one file
        //    (or one photo's WebP/JPEG pair) carries that key, so a product
        //    can never inherit another product's photo.
        $alpha = self::mediaAlphaKey($stem);

        if ($alpha !== '' && strlen($alpha) >= 6 && isset($index['byAlpha'][$alpha])) {
            $photos = self::groupByPhoto($index['byAlpha'][$alpha]);

            if (count($photos) === 1) {
                return $this->foundMediaFile(self::bestOfPhoto(array_values($photos)[0]));
            }
        }

        return null;
    }

    /**
     * Normalises a stored media reference to a relative path.
     */
    protected static function normaliseMediaPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path));

        if ($path === '') {
            return '';
        }

        $path = (string) preg_split('/[?#]/', $path)[0];
        $path = rawurldecode($path);

        foreach (['public/storage/', 'storage/app/public/', 'public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));

                break;
            }
        }

        return trim($path);
    }

    /**
     * Groups matched files by the photo they belong to (extension stripped),
     * so a WebP + JPEG pair of one upload counts as a single candidate.
     *
     * @param  list<string>  $files
     * @return array<string, list<string>>
     */
    protected static function groupByPhoto(array $files): array
    {
        $groups = [];

        foreach ($files as $file) {
            $groups[pathinfo($file, PATHINFO_FILENAME)][] = $file;
        }

        return $groups;
    }

    /**
     * @param  list<string>  $files
     */
    protected static function bestOfPhoto(array $files): string
    {
        usort($files, fn (string $a, string $b) => self::mediaExtensionRank($a) <=> self::mediaExtensionRank($b));

        return $files[0];
    }

    /**
     * Extensions that may hold the media for the given type, best first.
     *
     * @return list<string>
     */
    protected static function mediaExtensions(string $type): array
    {
        return $type === 'video'
            ? ['mp4', 'webm', 'mov', 'm4v']
            : ['webp', 'jpg', 'jpeg', 'png'];
    }

    /**
     * Resolves a relative media directory to an absolute one, supporting
     * both the standard Laravel layout and cPanel document roots.
     */
    protected static function absoluteMediaDirectory(string $directory): string
    {
        $directory = rtrim(str_replace('\\', '/', $directory), '/');

        if (str_starts_with($directory, '/') || preg_match('#^[A-Za-z]:/#', $directory) === 1) {
            return $directory;
        }

        return rtrim(str_replace('\\', '/', base_path($directory)), '/');
    }

    protected static function isUsableMediaFile(string $file): bool
    {
        if (! is_file($file)) {
            return false;
        }

        $size = (int) @filesize($file);

        if ($size <= 0) {
            return false;
        }

        // Reject the 1×1 "cover" stubs an interrupted resize used to leave
        // behind: they render as a blank white card, which looks exactly like
        // a missing photo but is far more confusing. Only tiny files are
        // opened, so the normal path stays a single stat() call.
        if ($size < 4096) {
            $info = @getimagesize($file);

            if (is_array($info) && ((int) $info[0] < 16 || (int) $info[1] < 16)) {
                return false;
            }
        }

        return true;
    }

    protected static function mediaExtensionRank(string $file): int
    {
        return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'webp' => 0,
            'jpg', 'jpeg' => 1,
            'png' => 2,
            'mp4' => 0,
            'webm' => 1,
            default => 3,
        };
    }

    /**
     * A comparable key for a filename: slug of the stem with the uploader's
     * "slug-<unix time>-<random>" suffix removed.
     */
    protected static function mediaFileKey(string $stem): string
    {
        // Strip the uploader's "-<unix time>-<random>" suffix, slugify, then
        // drop the "-2" style de-duplication counter, e.g.
        // "blush-aura-1-1788693359-w5NXXQ" -> "blush-aura".
        $stem = preg_replace('/-\d{10}-[a-z0-9]{5,8}$/i', '', $stem) ?? $stem;
        $stem = Str::slug($stem);
        $stem = preg_replace('/-\d{1,4}$/', '', $stem) ?? $stem;

        return $stem;
    }

    /**
     * A punctuation-insensitive key: lowercase alphanumerics only.
     *
     * "WhatsApp Image 2026-09-06 at 12.26.28 PM (1)" and
     * "whatsapp-image-2026-09-06-at-12-26-28-pm-1-1788693359-w5NXXQ" collapse
     * to the same value, which lets a record saved with the original client
     * filename still find the file the uploader actually wrote.
     */
    protected static function mediaAlphaKey(string $stem): string
    {
        $stem = strtolower(trim($stem));

        // Uploader suffix: "-<10 digit unix time>-<5-8 random chars>".
        $stem = preg_replace('/[-_ ]*\d{10}[-_ ]*[a-z0-9]{5,8}$/', '', $stem) ?? $stem;

        // De-duplication counter the operating system or uploader added:
        // "photo (1)", "photo-1", "photo_2".
        $stem = preg_replace('/[-_ ]*\(?\s*\d{1,4}\)?\s*$/', '', $stem) ?? $stem;

        return preg_replace('/[^a-z0-9]+/', '', $stem) ?? '';
    }

    /**
     * Index of every media file on disk, keyed by lowercase filename and by
     * normalised stem. Built once per request — a catalogue page resolves
     * dozens of images and must not scan the directory each time.
     *
     * @return array{byLower: array<string,string>, byKey: array<string,list<string>>, byAlpha: array<string,list<string>>}
     */
    protected static function mediaIndex(string $type): array
    {
        $cache = $type === 'video' ? 'videoMediaIndex' : 'imageMediaIndex';

        $directories = self::mediaSearchDirectories($type);
        $directories[] = public_path($type === 'video' ? 'videos/products' : 'images/products');

        // The index lives on a static property, so it survives a long-running
        // worker (Octane) and a test run that creates fixtures mid-process.
        // Rebuild when a media folder's contents change. File count is part
        // of the signature because a modification time only has one-second
        // resolution: two uploads in the same second must still be seen.
        $signature = [];

        foreach (array_unique($directories) as $directory) {
            $dir = self::absoluteMediaDirectory($directory);
            $entries = is_dir($dir) ? @scandir($dir) : false;
            $count = $entries === false ? -1 : max(0, count($entries) - 2);

            $signature[] = $dir.'@'.
                ($entries === false ? '-' : (string) @filemtime($dir)).'#'.
                $count;
        }

        $signature = implode('|', $signature);
        $signatureCache = $cache.'Signature';

        if (static::$$cache !== null && static::$$signatureCache === $signature) {
            return static::$$cache;
        }

        $byLower = [];
        $byKey = [];
        $byAlpha = [];
        $seen = [];

        foreach (array_unique($directories) as $directory) {
            $dir = self::absoluteMediaDirectory($directory);

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

                if (! self::isUsableMediaFile($full)) {
                    continue;
                }

                $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

                if (! in_array($extension, self::mediaExtensions($type), true)) {
                    continue;
                }

                $real = (string) (@realpath($full) ?: $full);

                if (isset($seen[$real])) {
                    continue;
                }

                $seen[$real] = true;

                $byLower[strtolower($entry)] ??= $full;
                $entryStem = pathinfo($entry, PATHINFO_FILENAME);
                $key = self::mediaFileKey($entryStem);

                if ($key !== '') {
                    $byKey[$key][] = $full;
                }

                $alpha = self::mediaAlphaKey($entryStem);

                if ($alpha !== '') {
                    $byAlpha[$alpha][] = $full;
                }
            }
        }

        static::$$signatureCache = $signature;

        return static::$$cache = ['byLower' => $byLower, 'byKey' => $byKey, 'byAlpha' => $byAlpha];
    }

    /**
     * @return array{file:string,url:string,name:string}
     */
    protected function foundMediaFile(string $file): array
    {
        return [
            'file' => $file,
            'url' => $this->mediaFileUrl($file),
            'name' => basename($file),
        ];
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
     * point at missing files.
     *
     * Only an unambiguous match is accepted: a file whose normalised name
     * (uploader timestamp suffix stripped) is exactly one of the product's
     * own keys. Similar-looking photos belonging to other products are never
     * substituted, because a wrong image on a product page is worse than an
     * honest "image unavailable" placeholder.
     *
     * @return array{file:string,url:string,name:string}|null
     */
    protected function resolveFallbackImage(): ?array
    {
        $stems = array_values(array_filter(array_unique([
            (string) $this->slug,
            Str::slug((string) $this->name),
            (string) preg_replace('/-with-application-kit$/', '', (string) $this->slug),
            (string) preg_replace('/-with-application-kit$/', '', Str::slug((string) $this->name)),
        ])));

        // 1) A file literally named after the product slug, whatever its
        //    extension (WebP, JPEG, PNG, or a stale extension in the record).
        foreach ($stems as $stem) {
            $resolved = $this->resolveMediaFile('image', $stem.'.webp');

            if ($resolved !== null) {
                return $resolved;
            }
        }

        // 2) The same normalised key, so "product-slug-<time>-<random>.webp"
        //    still belongs to "product-slug".
        $index = self::mediaIndex('image');

        foreach ($stems as $stem) {
            $key = self::mediaFileKey((string) $stem);

            if ($key === '' || strlen($key) < 3) {
                continue;
            }

            $matches = $index['byKey'][$key] ?? [];

            if ($matches === []) {
                continue;
            }

            // A WebP + JPEG pair from one upload collapses to a single photo,
            // but two genuinely different photos sharing a key do not. Picking
            // the first of those would put the wrong product's picture on this
            // page, which is worse than an empty slot.
            $photos = self::groupByPhoto($matches);

            if (count($photos) !== 1) {
                continue;
            }

            return $this->foundMediaFile(self::bestOfPhoto(array_values($photos)[0]));
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
