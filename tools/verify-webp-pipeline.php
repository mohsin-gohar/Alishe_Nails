<?php

/**
 * End-to-end verification of the WebP upload pipeline.
 *
 * Logs into the admin panel over real HTTP, creates a product with a large
 * JPEG upload, and then checks what actually landed on disk.
 */

$base = 'http://127.0.0.1:8080';
$jar = tempnam(sys_get_temp_dir(), 'cookies');
$upload = 'C:/Users/mohsi/AppData/Local/Temp/opencode/test-upload.jpg';

function request(string $url, array $options = []): array
{
    global $jar;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HEADER => true,
    ] + $options);

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $status,
        'headers' => substr($response, 0, $headerSize),
        'body' => substr($response, $headerSize),
    ];
}

/**
 * Same as request() but never follows redirects, so the raw 302 and its
 * Location header stay visible.
 */
function rawRequest(string $url, array $options = []): array
{
    global $jar;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_HEADER => true,
    ] + $options);

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $status,
        'headers' => substr($response, 0, $headerSize),
        'body' => substr($response, $headerSize),
    ];
}

function csrfFrom(string $html): ?string
{
    if (preg_match('/name="_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/name="csrf-token"\s+content="([^"]+)"/', $html, $m)) {
        return $m[1];
    }

    return null;
}

// ---------- 1. Log in ----------
$login = request($base.'/admin/login');
$token = csrfFrom($login['body']);

if (! $token) {
    exit("Could not read the CSRF token from the login page.\n");
}

$login = request($base.'/admin/login', [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => $token,
        'email' => 'admin@alishenails.com',
        'password' => 'ChangeMe123!',
    ]),
]);

$dashboard = request($base.'/admin');
$loggedIn = str_contains($dashboard['body'], 'admin-shell');

echo 'Admin logged in: '.($loggedIn ? 'YES' : 'NO').PHP_EOL;

if (! $loggedIn) {
    exit("Login failed.\n");
}

// ---------- 2. Create the product with a large JPEG ----------
$form = request($base.'/admin/products/create');
$token = csrfFrom($form['body']);

$payload = [
    '_token' => $token,
    'name' => 'QA WebP Pipeline Check',
    'category_id' => '',
    'price' => '1490',
    'stock' => '5',
    'short_description' => 'Temporary product used to verify the WebP pipeline.',
    'description' => 'Created by the automated image-pipeline check. Safe to delete.',
    'is_active' => '1',
    'media_files[0]' => new CURLFile($upload, 'image/jpeg', 'test-upload.jpg'),
    'media_types[0]' => 'image',
];

$created = rawRequest($base.'/admin/products', [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
]);

echo 'Create product status: '.$created['status'].PHP_EOL;

if ($created['status'] !== 302) {
    preg_match_all('/class="error"[^>]*>([^<]+)/', $created['body'], $errors);
    echo 'Validation errors: '.implode(' | ', $errors[1] ?? []).PHP_EOL;
    exit("Product was not created.\n");
}

// ---------- 3. Inspect what landed on disk ----------
$root = dirname(__DIR__);
$files = glob($root.'/public/images/products/*test-upload*');
$webp = glob($root.'/public/images/products/*.webp');

echo PHP_EOL.'Files created for this upload:'.PHP_EOL;

foreach (glob($root.'/public/images/products/*qa-webp-pipeline-check*') as $file) {
    echo '  '.basename($file).' — '.round(filesize($file) / 1024, 1).' KB'.PHP_EOL;
}

echo PHP_EOL.'All recently created product files:'.PHP_EOL;
$recent = array_filter(glob($root.'/public/images/products/*'), function ($f) {
    return filemtime($f) > time() - 300;
});
foreach ($recent as $file) {
    echo '  '.basename($file).' — '.round(filesize($file) / 1024, 1).' KB'.PHP_EOL;
}

// ---------- 4. Confirm the DB points at the WebP ----------
$db = new PDO('sqlite:'.$root.'/database/database.sqlite');
$row = $db->query("SELECT name, image, media FROM products WHERE name = 'QA WebP Pipeline Check'")->fetch(PDO::FETCH_ASSOC);

echo PHP_EOL.'Database row:'.PHP_EOL;
echo '  name  = '.($row['name'] ?? 'NOT FOUND').PHP_EOL;
echo '  image = '.($row['image'] ?? '-').PHP_EOL;
echo '  media = '.($row['media'] ?? '-').PHP_EOL;

@unlink($jar);
