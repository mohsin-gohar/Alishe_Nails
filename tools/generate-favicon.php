<?php

/**
 * Generates the square favicon / apple-touch-icon from the brand logo.
 *
 * The source logo is a tall 675x1200 portrait image, which every browser
 * crops awkwardly. This renders a clean, centred square crop with a subtle
 * gold ring so it stays legible at 16x16 in the browser tab.
 *
 * Run:  php tools/generate-favicon.php
 */
$root = __DIR__.'/..';
$source = $root.'/public/images/logo.jpeg';

if (! is_file($source)) {
    exit("Logo not found at {$source}\n");
}

$size = 512;

$info = getimagesize($source);
$logo = match ($info[2]) {
    IMAGETYPE_JPEG => imagecreatefromjpeg($source),
    IMAGETYPE_PNG => imagecreatefrompng($source),
    IMAGETYPE_WEBP => imagecreatefromwebp($source),
    default => exit("Unsupported logo format\n"),
};

$logoWidth = imagesx($logo);
$logoHeight = imagesy($logo);

// Centre-crop the source to a square.
$crop = min($logoWidth, $logoHeight);
$srcX = (int) (($logoWidth - $crop) / 2);
$srcY = (int) (($logoHeight - $crop) / 2);

$favicon = imagecreatetruecolor($size, $size);
imagealphablending($favicon, true);
imagecopyresampled($favicon, $logo, 0, 0, $srcX, $srcY, $size, $size, $crop, $crop);
imagedestroy($logo);

// Subtle gold ring so the mark reads on both light and dark browser chrome.
$gold = imagecolorallocatealpha($favicon, 0xD4, 0xAF, 0x37, 45);
imagesetthickness($favicon, 6);
imagerectangle($favicon, 8, 8, $size - 9, $size - 9, $gold);

$target = $root.'/public/images/favicon.png';
imagepng($favicon, $target, 9);
imagedestroy($favicon);

echo 'Generated '.$target.' ('.round(filesize($target) / 1024, 1).' KB)'.PHP_EOL;
