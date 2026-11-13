<?php
/**
 * Image Optimizer for Alishe Nails
 * Resizes large images to optimal display size for performance
 */

$sourceDir = __DIR__ . "/public/images/";
$destDir = __DIR__ . "/public/images/optimized/";

if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

echo "Starting image optimization..." . PHP_EOL;

echo "" . PHP_EOL;

// Image files to optimize with max dimensions
$files = [
    "collections/everyday-elegance.jpg" => [800, 600],
    "collections/bold-chic.jpg" => [800, 600],
    "collections/bridal-collection.jpg" => [800, 600],
    "collections/luxury-edition.jpg" => [800, 600],
    "collections/soft-glam.jpg" => [800, 600],
    "products/blush-aura.jpg" => [600, 600],
    "products/classic-pearl-mauve.jpg" => [600, 600],
    "products/espresso-split.jpg" => [600, 600],
    "products/gilded-rose-dust.jpg" => [600, 600],
    "products/golden-french.jpg" => [600, 600],
    "products/ivory-blossom-vine.jpg" => [600, 600],
    "products/matte-rose.jpg" => [600, 600],
    "products/midnight-espresso.jpg" => [600, 600],
    "products/minimalist-dot.jpg" => [600, 600],
    "products/modern-french-matte.jpg" => [600, 600],
    "products/pink-marble.jpg" => [600, 600],
    "products/rosy-quartz-ombre.jpg" => [600, 600],
    "products/rosy-quartz-ombre-detail.jpg" => [600, 600],
    "products/rosy-quartz-ombre-kit.jpg" => [600, 600],
    "products/rosy-quartz-ombre-worn.jpg" => [600, 600],
];

echo "Optimizing " . count($files) . " images..." . PHP_EOL . PHP_EOL;

foreach ($files as $relativePath => [$maxWidth, $maxHeight]) {
    $source = __DIR__ . "/public/" . $relativePath;
    $destDir = dirname(__DIR__ . "/public/images/optimized/" . $relativePath);
    $dest = __DIR__ . "/public/images/optimized/" . $relativePath;

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    if (file_exists($source)) {
        $info = getimagesize($source);
        if ($info) {
            $originalSize = filesize($source);
            $originalKB = round($originalSize / 1024, 1);

            switch ($info[2]) {
                case IMAGETYPE_JPEG:
                    $src = imagecreatefromjpeg($source);
                    break;
                case IMAGETYPE_PNG:
                    $src = imagecreatefrompng($source);
                    break;
                default:
                    continue;
            }

            $wOrig = imagesx($src);
            $hOrig = imagesy($src);

            // Calculate new dimensions maintaining aspect ratio
            $ratio = min($maxWidth / $wOrig, $maxHeight / $hOrig);
            $newWidth = (int)($wOrig * $ratio);
            $newHeight = (int)($hOrig * $ratio);

            $dst = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $wOrig, $hOrig);

            // Save optimized image
            if (imagejpeg($dst, $dest, 85)) {
                $newSize = filesize($dest);
                $newKB = round($newSize / 1024, 1);
                $saved = round((1 - $newSize / $originalSize) * 100);
                echo "✓ " . $relativePath . ": " . $originalKB . "KB → " . $newKB . "KB (-" . $saved . "%)" . PHP_EOL;
            }

            imagedestroy($src);
            imagedestroy($dst);
        }
    }
}

echo PHP_EOL . "Done!" . PHP_EOL;
echo "Optimized images saved to: public/images/optimized/" . PHP_EOL;
?>
