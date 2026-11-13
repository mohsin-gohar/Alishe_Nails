<?php

/**
 * Generates the branded social-sharing card (Open Graph / Twitter card).
 *
 * Social platforms render a 1200x630 landscape image best — the previous
 * asset was a 675x1200 portrait logo, which every platform cropped badly.
 *
 * Run:  php tools/generate-og-image.php
 */

$root = __DIR__.'/..';
$fontDir = 'C:/Users/mohsi/AppData/Local/Temp/opencode/fonts_ttf';

$width = 1200;
$height = 630;

$image = imagecreatetruecolor($width, $height);
imagealphablending($image, true);

$espresso = [0x2B, 0x1D, 0x1D];
$espressoDeep = [0x1A, 0x11, 0x12];
$rose = [0xC9, 0x8C, 0x99];
$gold = [0xD4, 0xAF, 0x37];
$cream = [0xFF, 0xF6, 0xF1];

// ---------- Background: vertical espresso gradient (one fill per row) ----------
for ($y = 0; $y < $height; $y++) {
    $t = $y / $height;

    $r = (int) round($espressoDeep[0] + ($espresso[0] - $espressoDeep[0]) * $t);
    $g = (int) round($espressoDeep[1] + ($espresso[1] - $espressoDeep[1]) * $t);
    $b = (int) round($espressoDeep[2] + ($espresso[2] - $espressoDeep[2]) * $t);

    imageline($image, 0, $y, $width, $y, imagecolorallocate($image, $r, $g, $b));
}

// ---------- Smooth rose spotlight (built small, then scaled up) ----------
$scale = 4;
$gw = (int) ($width / $scale);
$gh = (int) ($height / $scale);
$glow = imagecreatetruecolor($gw, $gh);
imagealphablending($glow, false);
imagesavealpha($glow, true);

$glowCenterX = $gw * 0.86;
$glowCenterY = $gh * 0.16;
$glowRadius = $gw * 0.55;

for ($y = 0; $y < $gh; $y++) {
    for ($x = 0; $x < $gw; $x++) {
        $dx = $x - $glowCenterX;
        $dy = ($y - $glowCenterY) * 1.35;
        $distance = sqrt($dx * $dx + $dy * $dy) / $glowRadius;

        if ($distance >= 1) {
            imagesetpixel($glow, $x, $y, imagecolorallocatealpha($glow, 0, 0, 0, 127));

            continue;
        }

        // Smooth falloff (quadratic), max intensity ~70/127.
        $intensity = (int) round((1 - $distance) * (1 - $distance) * 70);

        imagesetpixel($glow, $x, $y, imagecolorallocatealpha($glow, $rose[0], $rose[1], $rose[2], 127 - $intensity));
    }
}

imagealphablending($image, true);
imagecopyresampled($image, $glow, 0, 0, 0, 0, $width, $height, $gw, $gh);
imagedestroy($glow);

// ---------- Decorative gold frame ----------
$frame = imagecolorallocate($image, $gold[0], $gold[1], $gold[2]);
imagerectangle($image, 34, 34, $width - 35, $height - 35, $frame);
imagerectangle($image, 44, 44, $width - 45, $height - 45, imagecolorallocatealpha($image, $gold[0], $gold[1], $gold[2], 80));

// ---------- Text ----------
$serif = $fontDir.'/PlayfairDisplay-Bold.ttf';
$sans = $fontDir.'/Poppins-Medium.ttf';
$sansBold = $fontDir.'/Poppins-SemiBold.ttf';

/**
 * Draws centred text with manual letter spacing (GD has no tracking).
 *
 * Only ASCII is supported — GD renders the font byte-per-byte, so a UTF-8
 * middot would come out as two garbage characters.
 */
function drawTrackedText($image, string $font, int $size, string $text, int $centerX, int $y, array $rgb, int $tracking = 4): void
{
    $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    $length = strlen($text);
    $widths = [];

    for ($i = 0; $i < $length; $i++) {
        $box = imagettfbbox($size, 0, $font, $text[$i]);
        $widths[] = $box[2] - $box[0];
    }

    $total = array_sum($widths) + $tracking * ($length - 1);
    $x = (int) ($centerX - $total / 2);

    for ($i = 0; $i < $length; $i++) {
        imagettftext($image, $size, 0, $x, $y, $color, $font, $text[$i]);
        $x += $widths[$i] + $tracking;
    }
}

drawTrackedText($image, $serif, 76, 'ALISHE NAILS', 600, 268, $cream, 3);

// Gold divider with a small diamond in the middle.
$rule = imagecolorallocatealpha($image, $gold[0], $gold[1], $gold[2], 50);
imageline($image, 420, 310, 582, 310, $rule);
imageline($image, 618, 310, 780, 310, $rule);
imageline($image, 600, 301, 609, 310, $frame);
imageline($image, 609, 310, 600, 319, $frame);
imageline($image, 609, 301, 600, 310, $frame);
imageline($image, 600, 310, 609, 319, $frame);

drawTrackedText($image, $sans, 24, 'BESPOKE PRESS-ON NAILS', 600, 368, [0xEE, 0xD2, 0xD8], 6);
drawTrackedText($image, $sans, 17, 'KARACHI ATELIER   |   7-LAYER SALON GEL', 600, 408, [0xC4, 0xA4, 0xAC], 3);

// ---------- Bottom promise line ----------
$line = imagecolorallocatealpha($image, 0x00, 0x00, 0x00, 55);
imageline($image, 170, 470, 1030, 470, $line);
drawTrackedText($image, $sansBold, 16, 'ZERO DAMAGE   |   REUSABLE UP TO 5 TIMES   |   KARACHI DELIVERY 48-96 HOURS', 600, 512, [0xB8, 0x94, 0x9E], 1);

$target = $root.'/public/images/og/og-image.jpg';

if (! is_dir(dirname($target))) {
    mkdir(dirname($target), 0775, true);
}

imagejpeg($image, $target, 90);
imagedestroy($image);

echo 'Generated '.$target.' ('.round(filesize($target) / 1024, 1).' KB)'.PHP_EOL;
