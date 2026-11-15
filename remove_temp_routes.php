<?php
// Script to remove temporary routes from routes/web.php

$file = 'F:/Xampp/htdocs/alishe-nails/routes/web.php';
$content = file_get_contents($file);

// Remove all temporary routes using a more specific pattern
$lines = explode("\n", $content);
$newLines = [];
$inTempBlock = false;

foreach ($lines as $line) {
    // Check if we're starting a temporary block
    if (preg_match('/\/\/ -+\s*Temporary shared-hosting deploy helper/', $line) ||
        preg_match('/\/\/ -+\s*Temporary diagnostic endpoint/', $line) ||
        preg_match('/\/\/ -+\s*Temporary image-repair endpoint/', $line)) {
        $inTempBlock = true;
        continue;
    }
    
    // Check if we're ending a temporary block
    if ($inTempBlock && preg_match('/})->name\([\'\"]fix.images[\'\"]\);/', $line)) {
        $inTempBlock = false;
        $newLines[] = '// Temporary routes removed';
        continue;
    }
    
    // Skip lines in temporary block
    if ($inTempBlock) {
        continue;
    }
    
    $newLines[] = $line;
}

file_put_contents($file, implode("\n", $newLines));
echo "Temporary routes removed successfully\n";