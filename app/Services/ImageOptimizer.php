<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Enterprise image pipeline.
 *
 * Every image that enters the catalogue is normalised here so the storefront
 * never ships a 3 MB phone photo:
 *
 *  1. EXIF orientation is applied (photos shot in portrait stop rendering
 *     sideways) and all metadata is dropped — EXIF carries GPS coordinates,
 *     camera serials and the editing history of the shoot.
 *  2. Oversized images are downscaled to a sensible maximum width so a 6000px
 *     DSLR capture does not become a 9 MB download.
 *  3. A WebP sibling is written next to the original. WebP is roughly 30% of
 *     the JPEG weight at the same perceived quality and is supported by every
 *     browser in use today; the original stays on disk as the fallback for
 *     anything that cannot parse WebP.
 *
 * The service degrades gracefully: when the GD extension (or its WebP
 * support) is missing the file is stored untouched rather than failing the
 * upload, so the admin never loses work because of a server setting.
 */
class ImageOptimizer
{
    /** WebP encoder quality. 82 keeps photographs crisp at ~30% of JPEG size. */
    public const WEBP_QUALITY = 82;

    /** Quality used when re-encoding the JPEG fallback. */
    public const JPEG_QUALITY = 82;

    /** Default maximum width (px) for catalogue imagery. */
    public const DEFAULT_MAX_WIDTH = 1600;

    /** Maximum width used for category tiles and collection banners. */
    public const TILE_MAX_WIDTH = 1200;

    /** @var array<string, bool> */
    private array $capabilityCache = [];

    /**
     * Is the GD extension usable for image processing?
     */
    public function gdAvailable(): bool
    {
        return $this->capabilityCache['gd'] ??= (
            extension_loaded('gd')
            && function_exists('imagewebp')
            && function_exists('imagecreatetruecolor')
            && function_exists('imagecopyresampled')
        );
    }

    /**
     * Does the installed GD build include the WebP encoder?
     */
    public function supportsWebp(): bool
    {
        if (! $this->gdAvailable()) {
            return false;
        }

        return $this->capabilityCache['webp'] ??= (function_exists('gd_info')
            ? ! empty(gd_info()['WebP Support'])
            : false);
    }

    /**
     * Stores an uploaded image inside a public directory and returns the
     * filename that should be written to the database.
     *
     * The returned filename always ends in `.webp` when the server can encode
     * it (the requested behaviour: uploads become WebP), otherwise the
     * original extension is kept so the upload still succeeds.
     *
     * @param  string  $directory  Public-relative directory, e.g. "images/products".
     */
    public function store(UploadedFile $file, string $directory, ?int $maxWidth = null, ?string $baseName = null): string
    {
        $maxWidth ??= self::DEFAULT_MAX_WIDTH;
        $baseName ??= (string) Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        if ($baseName === '') {
            $baseName = 'image';
        }

        // Slug + timestamp + random suffix: the client filename is never
        // trusted, which rules out path traversal and double-extension tricks.
        $stem = $baseName.'-'.time().'-'.Str::random(6);

        $targetDirectory = public_path($directory);

        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $sourcePath = $file->getRealPath();

        if ($sourcePath === false || ! is_readable($sourcePath)) {
            // Last resort — should not happen, but never lose the upload.
            $extension = $this->safeExtension($file);
            $file->move($targetDirectory, $stem.'.'.$extension);

            return $stem.'.'.$extension;
        }

        if ($this->supportsWebp()) {
            $webpPath = $targetDirectory.'/'.$stem.'.webp';

            if ($this->writeWebp($sourcePath, $webpPath, $maxWidth)) {
                // Keep a compressed JPEG sibling so <picture> can fall back
                // for the (very small) set of clients without WebP support.
                $this->writeJpegFallback($sourcePath, $targetDirectory.'/'.$stem.'.jpg', $maxWidth);

                return $stem.'.webp';
            }
        }

        // No WebP encoder available — store the original bytes untouched.
        $extension = $this->safeExtension($file);
        $file->move($targetDirectory, $stem.'.'.$extension);

        return $stem.'.'.$extension;
    }

    /**
     * Converts an image that already lives in the public folder to WebP and
     * returns the new filename, or null when the file cannot be converted.
     *
     * @param  string  $directory  Public-relative directory, e.g. "images/products".
     */
    public function convertExisting(string $directory, string $filename, ?int $maxWidth = null, bool $keepOriginal = true): ?string
    {
        if (! $this->supportsWebp()) {
            return null;
        }

        $sourcePath = public_path($directory.'/'.$filename);

        if (! is_file($sourcePath)) {
            return null;
        }

        $stem = pathinfo($filename, PATHINFO_FILENAME);
        $webpName = $stem.'.webp';
        $webpPath = public_path($directory.'/'.$webpName);

        if (! $this->writeWebp($sourcePath, $webpPath, $maxWidth ?? self::DEFAULT_MAX_WIDTH)) {
            return null;
        }

        if (! $keepOriginal && strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'webp') {
            @unlink($sourcePath);
        }

        return $webpName;
    }

    /**
     * Writes a WebP version of the given image. Returns false on failure.
     */
    public function writeWebp(string $sourcePath, string $targetPath, int $maxWidth = self::DEFAULT_MAX_WIDTH): bool
    {
        $image = $this->loadImage($sourcePath);

        if ($image === null) {
            return false;
        }

        $image = $this->applyExifRotation($image, $sourcePath);
        $image = $this->downscale($image, $maxWidth);

        $ok = @imagewebp($image, $targetPath, self::WEBP_QUALITY);

        imagedestroy($image);

        return (bool) $ok;
    }

    /**
     * Writes a compressed JPEG fallback for clients without WebP support.
     */
    public function writeJpegFallback(string $sourcePath, string $targetPath, int $maxWidth = self::DEFAULT_MAX_WIDTH): bool
    {
        $image = $this->loadImage($sourcePath);

        if ($image === null) {
            return false;
        }

        $image = $this->applyExifRotation($image, $sourcePath);
        $image = $this->downscale($image, $maxWidth);

        // Flatten transparency onto white — JPEG has no alpha channel.
        $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
        $white = imagecolorallocate($flat, 255, 255, 255);
        imagefilledrectangle($flat, 0, 0, imagesx($flat), imagesy($flat), $white);
        imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);

        $ok = @imagejpeg($flat, $targetPath, self::JPEG_QUALITY);

        imagedestroy($flat);

        return (bool) $ok;
    }

    /**
     * Reads an image from disk into a GD resource.
     *
     * @return \GdImage|false|null
     */
    private function loadImage(string $path)
    {
        $info = @getimagesize($path);

        if ($info === false) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            default => false,
        };

        if ($image === false) {
            return null;
        }

        // Palette images must become truecolour before alpha handling,
        // otherwise their transparency is dropped from the generated WebP.
        if (function_exists('imagepalettetotruecolor') && ! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        // Preserve transparency for formats that can carry it. This runs
        // after the palette conversion (which makes the image truecolour),
        // so it must not be gated on imageistruecolor(); doing that skipped
        // the alpha channel and flattened transparent PNGs to black.
        if (in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)
            && function_exists('imagesavealpha')) {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
    }

    /**
     * Applies the EXIF orientation flag so portrait photos are not sideways.
     */
    private function applyExifRotation($image, string $path)
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);

        if (! is_array($exif) || ! isset($exif['Orientation'])) {
            return $image;
        }

        $orientation = (int) $exif['Orientation'];

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated !== null) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }

    /**
     * Downscales the image when it exceeds the maximum width. Images are
     * never upscaled — a 600px photo stays 600px.
     */
    private function downscale($image, int $maxWidth)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($maxWidth <= 0 || $width <= $maxWidth) {
            return $image;
        }

        $newWidth = $maxWidth;
        $newHeight = (int) round($height * ($maxWidth / $width));

        $resized = imagecreatetruecolor($newWidth, $newHeight);

        if (function_exists('imagealphablending')) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }

        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        imagedestroy($image);

        return $resized;
    }

    /**
     * Whitelisted extension for the original upload (used when WebP is not
     * available on the server).
     */
    private function safeExtension(UploadedFile $file): string
    {
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        return $allowed[$file->getMimeType()] ?? 'jpg';
    }
}
