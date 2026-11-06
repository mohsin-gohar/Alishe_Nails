<?php
/**
 * Storage Link Helper for cPanel / Shared Hosting (AlifHost)
 * Place this file temporarily in your public directory / public_html and visit it once via browser:
 * https://yourdomain.com/storage_link.php
 * DELETE THIS FILE IMMEDIATELY AFTER RUNNING!
 */

$target = __DIR__ . '/../storage/app/public';
$shortcut = __DIR__ . '/storage';

// If running inside public_html where laravel core is in a sibling folder:
if (!file_exists($target)) {
    // Check if laravel core is in /home/user/alishe-nails or ../alishe-nails
    if (file_exists(__DIR__ . '/../alishe-nails/storage/app/public')) {
        $target = __DIR__ . '/../alishe-nails/storage/app/public';
    } elseif (file_exists(__DIR__ . '/storage/app/public')) {
        $target = __DIR__ . '/storage/app/public';
        $shortcut = __DIR__ . '/public/storage';
    }
}

header('Content-Type: text/html; charset=utf-8');
echo '<h2>Alishe Nails - Storage Link Setup (AlifHost cPanel)</h2>';

if (file_exists($shortcut)) {
    echo '<p style="color:green;"><strong>Status:</strong> The storage symlink/folder already exists at <code>' . htmlspecialchars($shortcut) . '</code>.</p>';
} else {
    if (function_exists('symlink')) {
        $success = @symlink($target, $shortcut);
        if ($success) {
            echo '<p style="color:green;"><strong>SUCCESS:</strong> Symlink created from <code>' . htmlspecialchars($target) . '</code> to <code>' . htmlspecialchars($shortcut) . '</code>!</p>';
        } else {
            echo '<p style="color:orange;"><strong>Warning:</strong> symlink() function is disabled or failed. Attempting directory junction or copy...</p>';
            // Fallback: copy folder
            copy_directory($target, $shortcut);
            echo '<p style="color:green;"><strong>SUCCESS:</strong> Storage directory copied successfully!</p>';
        }
    } else {
        copy_directory($target, $shortcut);
        echo '<p style="color:green;"><strong>SUCCESS:</strong> symlink() was disabled in PHP, so storage directory was copied successfully!</p>';
    }
}

echo '<p style="color:red;"><strong>IMPORTANT:</strong> Delete <code>storage_link.php</code> now for security reasons.</p>';

function copy_directory($src, $dst) {
    @mkdir($dst, 0755, true);
    foreach (scandir($src) as $file) {
        if ($file !== '.' && $file !== '..') {
            if (is_dir("$src/$file")) {
                copy_directory("$src/$file", "$dst/$file");
            } else {
                copy("$src/$file", "$dst/$file");
            }
        }
    }
}
