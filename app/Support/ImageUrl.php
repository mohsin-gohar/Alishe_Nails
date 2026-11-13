<?php

namespace App\Support;

/**
 * Helpers that turn a stored image path into the markup a browser needs.
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
        if ($src === '') {
            return '';
        }

        // Absolute URL (http/https) -> keep only its path segment.
        if (preg_match('#^https?://#i', $src)) {
            $path = parse_url($src, PHP_URL_PATH) ?: '';
        } else {
            $path = $src;
        }

        // Storage-style URLs ("/storage/images/...") keep everything after
        // the storage segment.
        $path = preg_replace('#^.*?/storage/#', '', $path) ?? $path;

        return ltrim($path, '/');
    }

    /**
     * The WebP variant of the given image, when one exists on disk.
     *
     * When the stored file already IS a WebP the file itself is returned, so
     * the browser always gets the modern format through <source>.
     */
    public static function webpVariant(string $src): ?string
    {
        $relative = self::relativePath($src);

        if ($relative === '') {
            return null;
        }

        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

        if ($extension === 'webp') {
            return is_file(public_path($relative)) ? asset($relative) : null;
        }

        $candidate = pathinfo($relative, PATHINFO_DIRNAME).'/'.pathinfo($relative, PATHINFO_FILENAME).'.webp';
        $candidate = ltrim($candidate, './');

        if (is_file(public_path($candidate))) {
            return asset($candidate);
        }

        return null;
    }

    /**
     * The fallback image URL — the original file, or the JPEG sibling when
     * the stored file is itself a WebP.
     */
    public static function fallback(string $src): string
    {
        $relative = self::relativePath($src);

        if ($relative === '') {
            return $src;
        }

        $extension = strtolower(pathinfo($relative, PATHINFO_EXTENSION));

        if ($extension === 'webp') {
            $sibling = pathinfo($relative, PATHINFO_DIRNAME).'/'.pathinfo($relative, PATHINFO_FILENAME).'.jpg';
            $sibling = ltrim($sibling, './');

            if (is_file(public_path($sibling))) {
                return asset($sibling);
            }
        }

        return asset($relative);
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

        $path = public_path($relative);

        if (! is_file($path)) {
            // The stored file may be WebP while the size lives on its
            // JPEG sibling (or the other way round).
            $stem = pathinfo($relative, PATHINFO_FILENAME);
            $dir = ltrim(pathinfo($relative, PATHINFO_DIRNAME), './');

            foreach (['.jpg', '.jpeg', '.png', '.webp'] as $extension) {
                $candidate = ($dir === '.' ? '' : $dir.'/').$stem.$extension;

                if (is_file(public_path($candidate))) {
                    $path = public_path($candidate);
                    break;
                }
            }
        }

        $size = is_file($path) ? @getimagesize($path) : false;

        if ($size === false) {
            self::$dimensionCache[$relative] = false;

            return [null, null];
        }

        self::$dimensionCache[$relative] = [(int) $size[0], (int) $size[1]];

        return self::$dimensionCache[$relative];
    }
}
