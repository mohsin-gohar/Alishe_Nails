<?php

namespace App\Support;

/**
 * Helpers that turn a stored image path (or an already-resolved media URL)
 * into the markup a browser needs.
 *
 * The catalogue keeps the original file on disk and writes a WebP sibling
 * next to it. This class resolves the two variants plus the intrinsic
 * dimensions so Blade can render:
 *
 *     <picture>
 *         <source type="image/webp" srcset="....webp">
 *         <img src="....jpg" width="1600" height="1200" loading="lazy" decoding="async">
 *     </picture>
 *
 * Emitting width/height removes the layout shift that lazy-loaded images
 * normally cause (CLS), which is both a Core Web Vitals win and an SEO win.
 *
 * Every method accepts either a storage path ("images/products/foo.jpg"),
 * a root-relative URL ("/images/products/foo.jpg") or an absolute URL.
 * Anything that cannot be found on disk is left untouched, so a caller can
 * never corrupt a working URL by passing it through here a second time.
 */
class ImageUrl
{
    /** @var array<string, array{0:int,1:int}|false> */
    private static array $dimensionCache = [];

    /**
     * Normalises any image reference to a public-relative path such as
     * "images/products/foo.jpg".
     */
    public static function relativePath(string $src): string
    {
        $src = trim($src);

        if ($src === '') {
            return '';
        }

        $src = str_replace('\\', '/', $src);

        // Absolute URL (http/https) -> keep only its path segment.
        if (preg_match('#^https?://#i', $src)) {
            $src = (string) (parse_url($src, PHP_URL_PATH) ?? '');
        }

        // Drop query strings / fragments and decode %20-style escapes so a
        // browser-encoded filename still maps back to the file on disk.
        $src = (string) preg_split('/[?#]/', $src)[0];
        $src = rawurldecode($src);
        $src = preg_replace('#^\./#', '', $src) ?? $src;

        return ltrim($src, '/');
    }

    /**
     * Absolute filesystem path of the file behind the given reference,
     * or null when nothing exists on disk.
     */
    public static function publicFile(string $src): ?string
    {
        $relative = self::relativePath($src);

        if ($relative === '' || str_contains($relative, '..')) {
            return null;
        }

        $candidates = [];

        // "/storage/..." is served through the public/storage symlink, but a
        // shared host may have skipped `storage:link`, so also look at the
        // real storage directory.
        if (str_starts_with($relative, 'storage/')) {
            $candidates[] = public_path($relative);
            $candidates[] = storage_path('app/public/'.substr($relative, strlen('storage/')));
        }

        $candidates[] = public_path($relative);
        $candidates[] = base_path('public/'.$relative);

        // A reference that still carries a "public/" prefix (legacy rows).
        if (str_starts_with($relative, 'public/')) {
            $candidates[] = public_path(substr($relative, strlen('public/')));
        }

        $candidates[] = base_path($relative);

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Root-relative URL ("/images/products/foo.webp") for an absolute file.
     * Respects subdirectory installs and storage files without a symlink.
     */
    public static function urlForFile(string $file): string
    {
        $file = str_replace('\\', '/', $file);
        $public = rtrim(str_replace('\\', '/', public_path()), '/');
        $base = rtrim(str_replace('\\', '/', base_path()), '/');
        $storage = rtrim(str_replace('\\', '/', storage_path()), '/');

        if (str_starts_with($file, $public.'/')) {
            return self::relativeUrl(substr($file, strlen($public) + 1));
        }

        if (str_starts_with($file, $storage.'/app/public/')) {
            return self::relativeUrl('storage/'.substr($file, strlen($storage.'/app/public/')));
        }

        if (str_starts_with($file, $base.'/')) {
            return self::relativeUrl(substr($file, strlen($base) + 1));
        }

        return self::relativeUrl(basename($file));
    }

    /**
     * Root-relative URL ("/images/products/foo.webp"). Uses asset() path
     * extraction to respect subdirectory installs while staying same-origin.
     * Falls back to doc-root-relative computation for CLI contexts.
     */
    public static function relativeUrl(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $public = rtrim(str_replace('\\', '/', public_path()), '/');
        $base = rtrim(str_replace('\\', '/', base_path()), '/');
        $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');

        if (str_starts_with($path, $public.'/')) {
            $relative = substr($path, strlen($public));
            if ($docRoot !== '' && $public === $docRoot) {
                return $relative;
            }
            if ($docRoot !== '' && $base === $docRoot && str_starts_with($public, $docRoot.'/')) {
                return substr($public, strlen($docRoot)).$relative;
            }
            $assetPath = parse_url(asset(ltrim($relative, '/')), PHP_URL_PATH);
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
            $assetPath = parse_url(asset(ltrim($relative, '/')), PHP_URL_PATH);
            if (is_string($assetPath) && $assetPath !== '') {
                return $assetPath;
            }

            return $relative;
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, 7);
        }
        if (str_starts_with($path, 'storage/app/public/')) {
            $path = 'storage/'.substr($path, 19);
        }

        $clean = '/'.ltrim($path, '/');
        $assetPath = parse_url(asset(ltrim($clean, '/')), PHP_URL_PATH);
        if (is_string($assetPath) && $assetPath !== '') {
            return $assetPath;
        }

        return $clean;
    }

    /**
     * The WebP variant of the given image, when one exists on disk.
     *
     * When the stored file already IS a WebP the file itself is returned, so
     * the browser always gets the modern format through <source>.
     */
    public static function webpVariant(string $src): ?string
    {
        $file = self::publicFile($src);

        if ($file === null) {
            return null;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($extension === 'webp') {
            return self::urlForFile($file);
        }

        $webp = substr($file, 0, strlen($file) - strlen($extension)).'webp';

        return is_file($webp) ? self::urlForFile($webp) : null;
    }

    /**
     * The fallback image URL — the original file, or a raster sibling when
     * the stored file is itself a WebP. References that do not exist on
     * disk are returned untouched instead of being rewritten into a guess.
     */
    public static function fallback(string $src): string
    {
        $file = self::publicFile($src);

        if ($file === null) {
            return $src;
        }

        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if ($extension === 'webp') {
            foreach (['jpg', 'jpeg', 'png'] as $siblingExtension) {
                $sibling = substr($file, 0, strlen($file) - strlen($extension)).$siblingExtension;

                if (is_file($sibling)) {
                    return self::urlForFile($sibling);
                }
            }
        }

        return self::urlForFile($file);
    }

    /**
     * Intrinsic dimensions of the image as [width, height] so the browser
     * can reserve space before the file arrives. Returns [null, null] when
     * the size cannot be determined.
     *
     * @return array{0: int|null, 1: int|null}
     */
    public static function dimensions(string $src): array
    {
        $relative = self::relativePath($src);

        if ($relative === '') {
            return [null, null];
        }

        if (array_key_exists($relative, self::$dimensionCache)) {
            $cached = self::$dimensionCache[$relative];

            return $cached === false ? [null, null] : $cached;
        }

        $path = self::publicFile($relative);

        if ($path === null) {
            self::$dimensionCache[$relative] = false;

            return [null, null];
        }

        $size = @getimagesize($path);

        if ($size === false) {
            self::$dimensionCache[$relative] = false;

            return [null, null];
        }

        self::$dimensionCache[$relative] = [(int) $size[0], (int) $size[1]];

        return self::$dimensionCache[$relative];
    }
}
