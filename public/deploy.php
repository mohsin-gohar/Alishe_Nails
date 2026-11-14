<?php

/**
 * Alishe Nails — one-file shared-hosting deploy & media-repair tool.
 *
 * WHY THIS EXISTS
 *   AlifHost (cPanel) has no SSH/terminal. Upload this single file through
 *   cPanel File Manager, open it in a browser, and it can:
 *     1. back up the current code,
 *     2. download the latest `main` from GitHub and install it,
 *     3. run migrations / caches,
 *     4. find and restore product photos that went missing from
 *        public/images/products (including inside hosting backup archives),
 *     5. accept a ZIP of the original photos and match them back to the
 *        database records by filename.
 *
 * INSTALL
 *   Upload to:  <project-root>/public/deploy.php
 *   Open:       https://your-domain/deploy.php?token=YOUR_DEPLOY_TOKEN
 *   (If the root .htaccess does not rewrite, try /public/deploy.php.)
 *
 * SECURITY
 *   The token is read from DEPLOY_TOKEN in .env. If that value is empty the
 *   tool returns 404. DELETE THIS FILE after the deployment is finished.
 */

declare(strict_types=1);

@ini_set('display_errors', '0');
@ini_set('log_errors', '0');
error_reporting(E_ALL);
@set_time_limit(300);
@ignore_user_abort(true);

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex, nofollow');

/* -------------------------------------------------------------------------
 *  Small helpers
 * ---------------------------------------------------------------------- */

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function env_value(string $root, string $key): string
{
    $file = $root.'/.env';
    if (! is_file($file)) {
        return '';
    }

    $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (! str_starts_with($line, $key.'=')) {
            continue;
        }
        $value = trim(substr($line, strlen($key) + 1));
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        return $value;
    }

    return '';
}

function human_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return round($bytes / 1048576, 2).' MB';
    }
    if ($bytes >= 1024) {
        return round($bytes / 1024, 1).' KB';
    }

    return $bytes.' B';
}

function normalize_media_name(string $name): string
{
    $name = basename(str_replace('\\', '/', $name));
    $name = preg_replace('/\.[a-z0-9]{2,5}$/i', '', $name) ?? $name;
    // The uploader appends "-<unix timestamp>-<6 random chars>" to the name.
    $name = preg_replace('/-\d{10}-[a-z0-9]{6}$/i', '', $name) ?? $name;
    // WhatsApp sometimes appends " (1)" to a duplicate download.
    $name = preg_replace('/\s*\(\d+\)$/', '', $name) ?? $name;
    $name = strtolower($name);

    return preg_replace('/[^a-z0-9]+/i', '', $name) ?? '';
}

function media_extension(string $name): string
{
    return strtolower(pathinfo($name, PATHINFO_EXTENSION));
}

function is_image_name(string $name): bool
{
    return in_array(media_extension($name), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true);
}

function is_video_name(string $name): bool
{
    return in_array(media_extension($name), ['mp4', 'webm', 'mov', 'm4v'], true);
}

/* -------------------------------------------------------------------------
 *  Render / access control
 * ---------------------------------------------------------------------- */

function render_page(string $title, string $body, array $log = [], string $token = '', string $self = ''): never
{
    $logHtml = '';
    if ($log !== []) {
        $logHtml = '<pre class="log">'.h(implode("\n", $log)).'</pre>';
    }

    $nav = '';
    if ($token !== '' && $self !== '') {
        $link = static fn (string $action, string $label, string $class = 'btn'): string =>
            '<a class="'.$class.'" href="'.h($self.'?'.http_build_query(['token' => $token, 'action' => $action])).'">'.h($label).'</a>';

        $nav = '<div class="nav">'
            .$link('status', 'Status')
            .$link('update', '1 · Update code')
            .$link('recover', '2 · Recover images')
            .$link('run', '3 · Run migrations & caches')
            .$link('cleanup', 'Delete this tool', 'btn danger')
            .'</div>';
    }

    echo '<!doctype html><html lang="en"><head><meta charset="utf-8">'
        .'<meta name="viewport" content="width=device-width,initial-scale=1">'
        .'<meta name="robots" content="noindex,nofollow">'
        .'<title>'.h($title).' · Alishe Nails</title>'
        .'<style>'
        .'body{font-family:ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;background:#faf7f4;color:#2b1d1d;margin:0;padding:28px;line-height:1.55}'
        .'.wrap{max-width:1000px;margin:0 auto}'
        .'h1{font-size:24px;margin:0 0 6px}h2{font-size:18px;margin:26px 0 10px}'
        .'.card{background:#fff;border:1px solid #eadfd6;border-radius:14px;padding:20px 22px;margin:16px 0;box-shadow:0 8px 30px rgba(43,29,29,.05)}'
        .'.muted{color:#6e5b52;font-size:13px}.ok{color:#1b7f4b;font-weight:700}.bad{color:#b3261e;font-weight:700}.warn{color:#a15c00;font-weight:700}'
        .'.nav{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0 20px}'
        .'.btn{display:inline-block;background:#2b1d1d;color:#fff;text-decoration:none;border:0;border-radius:8px;padding:9px 14px;font-size:13px;font-weight:600;cursor:pointer}'
        .'.btn.alt{background:#f0e6dc;color:#2b1d1d}.btn.danger{background:#b3261e}'
        .'.btn:hover{opacity:.88}'
        .'.log{background:#171313;color:#f7efe8;border-radius:10px;padding:14px;overflow:auto;max-height:62vh;font-size:12.5px;white-space:pre-wrap;word-break:break-word}'
        .'table{width:100%;border-collapse:collapse;font-size:13px}td,th{text-align:left;padding:6px 8px;border-bottom:1px solid #efe5dc;vertical-align:top}th{color:#6e5b52;font-weight:600}'
        .'code{background:#f4ece5;padding:1px 5px;border-radius:4px;font-size:12px;word-break:break-all}'
        .'form{margin:10px 0}input[type=file]{font-size:13px}'
        .'</style></head><body><div class="wrap">'
        .'<h1>'.h($title).'</h1>'
        .'<p class="muted">Alishe Nails · temporary cPanel deploy & repair tool · <strong>delete this file when finished</strong></p>'
        .$nav
        .$body
        .$logHtml
        .'</div></body></html>';

    exit;
}

$root = dirname(__DIR__);

// When this file is included from a root-level deploy.php loader, __DIR__ is
// still the public/ directory, so $root is always the Laravel project root.
if (! is_file($root.'/artisan') || ! is_dir($root.'/app')) {
    http_response_code(500);
    echo '<h1>Project root not found</h1><p>Place this file in <code>public/deploy.php</code> inside the Laravel project.</p>';
    exit;
}

$token = env_value($root, 'DEPLOY_TOKEN');
$provided = (string) ($_GET['token'] ?? $_POST['token'] ?? '');

if ($token === '' || ! hash_equals($token, $provided)) {
    http_response_code(404);
    echo '<!doctype html><title>404 Not Found</title><h1>404 Not Found</h1>';
    exit;
}

$self = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/deploy.php'), '?');
if (! is_string($self) || $self === '') {
    $self = '/deploy.php';
}

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'status');
$log = [];
$link = static fn (string $a, string $label, string $class = 'btn'): string =>
    '<a class="'.$class.'" href="'.h($self.'?'.http_build_query(['token' => $token, 'action' => $a])).'">'.h($label).'</a>';

/* -------------------------------------------------------------------------
 *  Database
 * ---------------------------------------------------------------------- */

function db_connect(string $root): PDO
{
    $driver = env_value($root, 'DB_CONNECTION') ?: 'mysql';

    if ($driver === 'sqlite') {
        $path = env_value($root, 'DB_DATABASE') ?: ($root.'/database/database.sqlite');
        if ($path !== ':memory:' && ! preg_match('/^(?:[A-Za-z]:[\\\\\/]|\/)/', $path)) {
            $path = $root.'/'.ltrim($path, './\\');
        }

        return new PDO('sqlite:'.$path, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    $host = env_value($root, 'DB_HOST') ?: '127.0.0.1';
    $port = env_value($root, 'DB_PORT') ?: '3306';
    $database = env_value($root, 'DB_DATABASE');
    $username = env_value($root, 'DB_USERNAME');
    $password = env_value($root, 'DB_PASSWORD');
    $socket = env_value($root, 'DB_SOCKET');

    $dsn = $socket !== ''
        ? 'mysql:unix_socket='.$socket.';dbname='.$database.';charset=utf8mb4'
        : 'mysql:host='.$host.';port='.$port.';dbname='.$database.';charset=utf8mb4';

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}

/**
 * Every media path referenced by the product catalogue, keyed by basename.
 *
 * @return array<string, array{product_id:int, product:string, path:string, type:string}>
 */
function media_references(PDO $pdo): array
{
    try {
        $rows = $pdo->query('SELECT id, name, slug, image, gallery, media FROM products ORDER BY id')->fetchAll();
    } catch (Throwable) {
        $rows = $pdo->query('SELECT id, name, slug, image, media FROM products ORDER BY id')->fetchAll();
    }

    $references = [];

    $add = static function (?string $path, string $type, array $row) use (&$references): void {
        $path = trim((string) $path);
        if ($path === '') {
            return;
        }
        $base = basename(str_replace('\\', '/', $path));
        if ($base === '' || $base === '.' || $base === '..') {
            return;
        }
        $references[$base] = [
            'product_id' => (int) $row['id'],
            'product' => (string) $row['name'],
            'path' => $base,
            'type' => $type === 'video' ? 'video' : 'image',
        ];
    };

    foreach ($rows as $row) {
        $media = $row['media'] ?? null;
        if (is_string($media) && $media !== '') {
            $decoded = json_decode($media, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (is_array($item)) {
                        $add($item['path'] ?? '', (string) ($item['type'] ?? 'image'), $row);
                    } elseif (is_string($item)) {
                        $add($item, 'image', $row);
                    }
                }
            }
        }

        $gallery = $row['gallery'] ?? null;
        if (is_string($gallery) && $gallery !== '') {
            $decoded = json_decode($gallery, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (is_string($item)) {
                        $add($item, 'image', $row);
                    }
                }
            }
        }

        $add($row['image'] ?? '', 'image', $row);
    }

    return $references;
}

/**
 * Folders (relative to the project root) that may hold product media.
 * Mirrors App\Models\Product::mediaSearchDirectories().
 *
 * @return list<string>
 */
function media_search_directories(string $type): array
{
    if ($type === 'video') {
        return [
            'public/videos/products',
            'videos/products',
            'public/storage/products',
            'storage/app/public/products',
        ];
    }

    return [
        'public/images/products',
        'images/products',
        'public/images',
        'images',
        'public/storage/products',
        'public/storage/images/products',
        'storage/app/public/products',
        'storage/app/public/images/products',
    ];
}

/**
 * The stored filename plus the interchangeable derivatives an upload can
 * produce (e.g. the database points at `.jpg` but only `.webp` survives).
 *
 * @return list<string>
 */
function media_name_candidates(string $path, string $type = 'image'): array
{
    $path = basename(str_replace('\\', '/', $path));
    if ($path === '') {
        return [];
    }

    $stem = pathinfo($path, PATHINFO_FILENAME);
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $extensions = $type === 'video'
        ? ['mp4', 'webm', 'mov', 'm4v']
        : ['webp', 'jpg', 'jpeg', 'png'];

    $names = [$path];
    foreach ($extensions as $candidate) {
        if ($extension !== $candidate) {
            $names[] = $stem.'.'.$candidate;
        }
    }

    return $names;
}

/** Locate a referenced media file anywhere the site is allowed to look. */
function find_media_file(string $root, string $type, string $path): ?string
{
    foreach (media_search_directories($type) as $directory) {
        foreach (media_name_candidates($path, $type) as $name) {
            $file = $root.'/'.$directory.'/'.$name;
            if (is_file($file) && (int) @filesize($file) > 0) {
                return $file;
            }
        }
    }

    return null;
}

/**
 * @param array<string, array{product_id:int, product:string, path:string, type:string}> $references
 * @return array<string, array{product_id:int, product:string, path:string, type:string, file:string, dir:string}>
 */
function missing_media(string $root, array $references): array
{
    $missing = [];
    foreach ($references as $base => $reference) {
        $dir = $reference['type'] === 'video' ? 'videos/products' : 'images/products';

        if (find_media_file($root, $reference['type'], $base) !== null) {
            continue;
        }

        $file = $root.'/public/'.$dir.'/'.$base;
        $missing[$base] = $reference + ['file' => $file, 'dir' => $dir];
    }

    return $missing;
}

/* -------------------------------------------------------------------------
 *  File scanning
 * ---------------------------------------------------------------------- */

/**
 * Breadth-first scan of a directory, with depth and skip controls.
 *
 * @param list<string> $skipExact
 * @param callable(string):void $onFile
 */
function scan_tree(string $base, int $maxDepth, array $skipExact, callable $onFile, int $maxFiles = 250000): void
{
    $skipExact = array_map(static fn (string $path): string => rtrim(str_replace('\\', '/', $path), '/'), $skipExact);
    $queue = [[rtrim($base, '/\\'), 0]];
    $seen = 0;

    while ($queue !== []) {
        [$dir, $depth] = array_pop($queue);
        if ($depth > $maxDepth) {
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

            $path = $dir.DIRECTORY_SEPARATOR.$entry;
            $normalized = str_replace('\\', '/', $path);

            foreach ($skipExact as $skip) {
                if ($normalized === $skip || str_starts_with($normalized, $skip.'/')) {
                    continue 2;
                }
            }

            if (is_dir($path)) {
                if (! is_link($path)) {
                    $queue[] = [$path, $depth + 1];
                }
                continue;
            }

            $seen++;
            if ($seen > $maxFiles) {
                return;
            }

            $onFile($path);
        }
    }
}

/**
 * Copy a candidate image so it matches the database basename.
 */
function install_media(string $source, string $target): bool
{
    $dir = dirname($target);
    if (! is_dir($dir) && ! @mkdir($dir, 0755, true) && ! is_dir($dir)) {
        return false;
    }

    if (! @copy($source, $target)) {
        return false;
    }

    @chmod($target, 0644);

    return filesize($target) > 0;
}

/**
 * Recover missing media from loose files and backup archives in the home
 * directory. Returns a list of log lines.
 *
 * @param array<string, array{product_id:int, product:string, path:string, type:string, file:string, dir:string}> $missing
 * @return array{restored:int, log:list<string>}
 */
function recover_media(string $root, array $missing): array
{
    $log = [];
    $restored = 0;

    if ($missing === []) {
        return ['restored' => 0, 'log' => ['No missing media files were found in the database.']];
    }

    $home = dirname($root);
    $skip = [
        $root.'/vendor',
        $root.'/node_modules',
        $root.'/.git',
        $root.'/storage/framework',
        $root.'/storage/logs',
        $root.'/public/images/products',
        $root.'/public/videos/products',
        $root.'/public/webfonts',
        $root.'/public/build',
        $root.'/.alishe-deploy-backups',
        $home.'/mail',
        $home.'/etc',
        $home.'/ssl',
        $home.'/logs',
        $home.'/tmp',
        $home.'/virtfs',
        $home.'/.cpanel',
        $home.'/.trash',
        $home.'/node_modules',
    ];

    // Normalised name -> expected basenames (one photo can be shared).
    $normalized = [];
    foreach ($missing as $base => $reference) {
        $normalized[normalize_media_name($base)][] = $base;
    }

    $exactCandidates = [];   // basename => list of paths
    $normalizedCandidates = []; // normalized => list of paths
    $archives = [];

    $log[] = 'Scanning '.$home.' for the missing files…';

    scan_tree($home, 6, $skip, static function (string $path) use (&$exactCandidates, &$normalizedCandidates, &$archives, $missing, $normalized): void {
        $base = basename($path);
        $lower = strtolower($base);

        if (str_ends_with($lower, '.zip') || str_ends_with($lower, '.tar') || str_ends_with($lower, '.tar.gz') || str_ends_with($lower, '.tgz')) {
            $archives[] = $path;

            return;
        }

        if (! is_image_name($base) && ! is_video_name($base)) {
            return;
        }

        if (isset($missing[$base])) {
            $exactCandidates[$base][] = $path;
        }

        $normalizedKey = normalize_media_name($base);
        if ($normalizedKey !== '' && isset($normalized[$normalizedKey])) {
            $normalizedCandidates[$normalizedKey][] = $path;
        }
    });

    $log[] = 'Indexed loose files; found '.count($exactCandidates).' exact-name and '.count($normalizedCandidates).' normalised-name candidates.';

    // 1) Exact basename matches.
    foreach ($missing as $base => $reference) {
        $paths = $exactCandidates[$base] ?? [];
        foreach ($paths as $path) {
            if (install_media($path, $reference['file'])) {
                $restored++;
                $log[] = 'RESTORED  #'.$reference['product_id'].' '.$reference['product'].' → '.$base.'  (from '.$path.')';
                unset($missing[$base]);
                break;
            }
        }
    }

    // 2) Normalised WhatsApp-style matches.
    foreach ($missing as $base => $reference) {
        $key = normalize_media_name($base);
        $paths = $normalizedCandidates[$key] ?? [];
        foreach ($paths as $path) {
            if (install_media($path, $reference['file'])) {
                $restored++;
                $log[] = 'MATCHED   #'.$reference['product_id'].' '.$reference['product'].' → '.$base.'  (from '.basename($path).')';
                unset($missing[$base]);
                break;
            }
        }
    }

    // 3) Search backup archives if any media is still missing.
    if ($missing !== [] && $archives !== []) {
        $log[] = 'Searching '.count($archives).' archive(s) for the remaining files…';
        $deadline = microtime(true) + 90;
        $archives = array_slice($archives, 0, 12);

        foreach ($archives as $archive) {
            if (microtime(true) > $deadline) {
                $log[] = 'Archive search stopped at the 90 second limit.';
                break;
            }

            $log[] = 'Archive: '.$archive;
            $lower = strtolower($archive);

            try {
                if (str_ends_with($lower, '.zip')) {
                    $zip = new ZipArchive();
                    if ($zip->open($archive) !== true) {
                        $log[] = '  could not open ZIP';
                        continue;
                    }
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $entry = (string) $zip->getNameIndex($i);
                        $base = basename(str_replace('\\', '/', $entry));
                        if (! isset($missing[$base]) && ($normalizedKey = normalize_media_name($base)) !== '') {
                            // fall through to normalised lookup below
                            $matchedBase = null;
                            foreach ($missing as $missingBase => $reference) {
                                if (normalize_media_name($missingBase) === $normalizedKey) {
                                    $matchedBase = $missingBase;
                                    break;
                                }
                            }
                        } else {
                            $matchedBase = isset($missing[$base]) ? $base : null;
                        }

                        if ($matchedBase === null) {
                            continue;
                        }

                        $contents = $zip->getFromIndex($i);
                        if (! is_string($contents) || $contents === '' || strlen($contents) > 25 * 1024 * 1024) {
                            continue;
                        }

                        $reference = $missing[$matchedBase];
                        if (! is_dir(dirname($reference['file'])) && ! @mkdir(dirname($reference['file']), 0755, true) && ! is_dir(dirname($reference['file']))) {
                            continue;
                        }
                        if (@file_put_contents($reference['file'], $contents) !== false && filesize($reference['file']) > 0) {
                            @chmod($reference['file'], 0644);
                            $restored++;
                            $log[] = 'ARCHIVED  #'.$reference['product_id'].' '.$reference['product'].' → '.$matchedBase.'  (from '.$entry.')';
                            unset($missing[$matchedBase]);
                        }
                    }
                    $zip->close();
                } elseif (class_exists('PharData')) {
                    $phar = new PharData($archive);
                    $iterator = new RecursiveIteratorIterator($phar);
                    foreach ($iterator as $entry) {
                        if (! $entry->isFile()) {
                            continue;
                        }
                        $base = $entry->getBasename();
                        $matchedBase = isset($missing[$base]) ? $base : null;
                        if ($matchedBase === null) {
                            $normalizedKey = normalize_media_name($base);
                            foreach ($missing as $missingBase => $reference) {
                                if (normalize_media_name($missingBase) === $normalizedKey) {
                                    $matchedBase = $missingBase;
                                    break;
                                }
                            }
                        }
                        if ($matchedBase === null || $entry->getSize() > 25 * 1024 * 1024) {
                            continue;
                        }

                        $reference = $missing[$matchedBase];
                        $contents = $entry->getContent();
                        if (! is_string($contents) || $contents === '') {
                            continue;
                        }
                        if (! is_dir(dirname($reference['file'])) && ! @mkdir(dirname($reference['file']), 0755, true) && ! is_dir(dirname($reference['file']))) {
                            continue;
                        }
                        if (@file_put_contents($reference['file'], $contents) !== false && filesize($reference['file']) > 0) {
                            @chmod($reference['file'], 0644);
                            $restored++;
                            $log[] = 'ARCHIVED  #'.$reference['product_id'].' '.$reference['product'].' → '.$matchedBase.'  (from '.$entry->getPathname().')';
                            unset($missing[$matchedBase]);
                        }

                        if (microtime(true) > $deadline) {
                            break;
                        }
                    }
                }
            } catch (Throwable $e) {
                $log[] = '  archive error: '.$e->getMessage();
            }

            if ($missing === []) {
                break;
            }
        }
    }

    foreach ($missing as $base => $reference) {
        $log[] = 'STILL MISSING  #'.$reference['product_id'].' '.$reference['product'].' → '.$base;
    }

    return ['restored' => $restored, 'log' => $log];
}

/* -------------------------------------------------------------------------
 *  Code update / backup / rollback
 * ---------------------------------------------------------------------- */

function backup_root(string $root): string
{
    $dir = dirname($root).'/.alishe-deploy-backups';
    if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
        $dir = $root.'/storage/backups';
        if (! is_dir($dir) && ! @mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new RuntimeException('Cannot create a backup directory. Check permissions on the home directory or storage/.');
        }
    }

    $file = $dir.'/pre-deploy-'.date('Ymd-His').'.zip';
    $zip = new ZipArchive();
    if ($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('Cannot create the backup ZIP.');
    }

    $items = [
        'app', 'bootstrap', 'config', 'database/migrations', 'database/seeders',
        'public/index.php', 'public/.htaccess', 'public/css', 'public/js',
        'resources/views', 'routes', '.htaccess', 'artisan', 'composer.json', 'composer.lock',
    ];

    $add = static function (string $path, string $relative) use (&$add, $zip, $root): void {
        if (is_dir($path)) {
            foreach (@scandir($path) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }
                $add($path.'/'.$entry, $relative.'/'.$entry);
            }

            return;
        }

        if (is_file($path) && filesize($path) > 0) {
            $zip->addFile($path, ltrim(str_replace('\\', '/', $relative), '/'));
        }
    };

    foreach ($items as $item) {
        $path = $root.'/'.$item;
        if (file_exists($path)) {
            $add($path, $item);
        }
    }

    $zip->close();

    if (! is_file($file) || filesize($file) < 1024) {
        throw new RuntimeException('Backup ZIP looks empty.');
    }

    return $file;
}

/**
 * @return array{source:string, temp:string}
 */
function download_github_zip(string $url): array
{
    $temp = rtrim(sys_get_temp_dir(), '/\\').'/alishe-deploy-'.bin2hex(random_bytes(5));
    if (! @mkdir($temp, 0775, true) && ! is_dir($temp)) {
        throw new RuntimeException('Cannot create a temporary directory.');
    }

    $zipFile = $temp.'/main.zip';
    $ok = false;

    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        $out = @fopen($zipFile, 'wb');
        if ($handle !== false && $out !== false) {
            curl_setopt_array($handle, [
                CURLOPT_FILE => $out,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_TIMEOUT => 180,
                CURLOPT_USERAGENT => 'AlisheNailsDeploy/1.0',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $ok = curl_exec($handle) === true;
            curl_close($handle);
            fclose($out);
        }
    }

    if (! $ok) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 180,
                'follow_location' => 1,
                'user_agent' => 'AlisheNailsDeploy/1.0',
            ],
        ]);
        $data = @file_get_contents($url, false, $context);
        if (is_string($data) && $data !== '') {
            $ok = @file_put_contents($zipFile, $data) !== false;
        }
    }

    if (! $ok || ! is_file($zipFile) || filesize($zipFile) < 100000) {
        throw new RuntimeException('Could not download the GitHub ZIP. Upload the ZIP manually using the form below.');
    }

    return ['source' => $zipFile, 'temp' => $temp];
}

/**
 * Install a code ZIP into the project root. Additive only: existing files are
 * overwritten, but nothing is deleted, so uploads and .env stay untouched.
 */
function install_code_zip(string $zipFile, string $root): array
{
    $temp = rtrim(sys_get_temp_dir(), '/\\').'/alishe-extract-'.bin2hex(random_bytes(5));
    if (! @mkdir($temp, 0775, true) && ! is_dir($temp)) {
        throw new RuntimeException('Cannot create an extraction directory.');
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFile) !== true) {
        throw new RuntimeException('The ZIP could not be opened (is it a valid GitHub ZIP?).');
    }
    $zip->extractTo($temp);
    $zip->close();

    $directories = glob($temp.'/*', GLOB_ONLYDIR) ?: [];
    $source = null;
    foreach ($directories as $directory) {
        if (is_file($directory.'/artisan') && is_dir($directory.'/app')) {
            $source = $directory;
            break;
        }
    }

    if ($source === null) {
        throw new RuntimeException('Unexpected ZIP structure: could not find artisan/app.');
    }

    $skip = [
        '.git', 'vendor', 'node_modules', 'storage', '.env', '.env.local', '.env.production',
        'database/database.sqlite',
    ];

    $copied = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($source) + 1));
        foreach ($skip as $skipName) {
            if ($relative === $skipName || str_starts_with($relative, $skipName.'/')) {
                continue 2;
            }
        }

        $target = $root.'/'.$relative;
        if ($item->isDir()) {
            if (! is_dir($target) && ! @mkdir($target, 0755, true) && ! is_dir($target)) {
                throw new RuntimeException('Cannot create directory: '.$relative);
            }
            continue;
        }

        if (! is_dir(dirname($target)) && ! @mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
            throw new RuntimeException('Cannot create directory for: '.$relative);
        }
        if (! @copy($item->getPathname(), $target)) {
            throw new RuntimeException('Cannot write file: '.$relative);
        }
        @chmod($target, 0644);
        $copied++;
    }

    return ['copied' => $copied, 'source' => $source, 'temp' => $temp];
}

/**
 * Run Laravel artisan commands after a code update.
 *
 * @return list<string>
 */
function run_post_update(string $root): array
{
    $log = [];

    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }

    if (! is_file($root.'/vendor/autoload.php')) {
        $log[] = 'WARNING: vendor/autoload.php is missing. Upload vendor/ or run composer install.';

        return $log;
    }

    try {
        require_once $root.'/vendor/autoload.php';
        $app = require $root.'/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
    } catch (Throwable $e) {
        $log[] = 'ERROR: Laravel could not boot after the update: '.$e->getMessage();
        $log[] = 'Use the Rollback button or restore the backup ZIP manually.';

        return $log;
    }

    $call = static function (string $command, array $parameters = []) use ($kernel, &$log): void {
        try {
            $code = $kernel->call($command, $parameters);
            $output = trim((string) $kernel->output());
            $log[] = '['.$command.'] exit='.$code.($output !== '' ? "\n".$output : '');
        } catch (Throwable $e) {
            $log[] = '['.$command.'] FAILED: '.$e->getMessage();
        }
    };

    $call('optimize:clear');
    $call('migrate', ['--force' => true]);

    try {
        $count = (int) Illuminate\Support\Facades\DB::table('products')->count();
        $log[] = 'Products in database: '.$count;
        if ($count === 0) {
            $call('db:seed', ['--force' => true]);
        } else {
            $log[] = '[db:seed] skipped (products already exist)';
        }
    } catch (Throwable $e) {
        $log[] = 'Could not count products: '.$e->getMessage();
    }

    if (class_exists(App\Console\Commands\DoctorImages::class)) {
        $call('images:doctor', ['--from' => $root.'/public/images/products']);
    } else {
        $log[] = '[images:doctor] command not present in this code version';
    }

    $call('config:cache');
    $call('view:cache');
    $log[] = 'NOTE: route:cache is intentionally skipped because the temporary deploy routes use closures.';

    if (function_exists('opcache_reset')) {
        @opcache_reset();
    }

    return $log;
}

/**
 * Hard requirements for installing new code. Returns an error message, or
 * null when the server is ready. Called before anything is modified so a
 * wrong PHP version or missing extension can never leave a half-updated site.
 */
function update_preflight(string $root): ?string
{
    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        return 'This server runs PHP '.PHP_VERSION.'. Laravel 12 needs PHP 8.2 or newer. '
            .'Open cPanel → MultiPHP Manager, switch the domain to PHP 8.2/8.3, wait a minute, then reload this page. '
            .'Nothing was changed.';
    }

    if (! class_exists('ZipArchive')) {
        return 'The PHP zip extension (ZipArchive) is not enabled, so the code/image ZIPs cannot be opened. '
            .'Open cPanel → Select PHP Version → Extensions, tick "zip", save, then reload this page. '
            .'Nothing was changed.';
    }

    if (! is_writable($root) || ! is_writable($root.'/app')) {
        return 'The project files are not writable by PHP. Fix the permissions in cPanel File Manager '
            .'(folders 755, files 644) or ask Alif Host support. Nothing was changed.';
    }

    return null;
}

/* -------------------------------------------------------------------------
 *  Actions
 * ---------------------------------------------------------------------- */

if ($action === 'status') {
    $checks = [];
    $checks[] = ['PHP', PHP_VERSION, version_compare(PHP_VERSION, '8.2.0', '>=') ? 'ok' : 'bad'];
    $checks[] = ['ZipArchive', class_exists('ZipArchive') ? 'available' : 'missing', class_exists('ZipArchive') ? 'ok' : 'bad'];
    $checks[] = ['cURL', function_exists('curl_init') ? 'available' : 'missing (stream fallback)', function_exists('curl_init') ? 'ok' : 'warn'];
    $checks[] = ['PDO MySQL', extension_loaded('pdo_mysql') ? 'available' : 'missing', extension_loaded('pdo_mysql') ? 'ok' : 'bad'];
    $checks[] = ['GD (WebP)', function_exists('imagewebp') ? 'available' : 'missing', function_exists('imagewebp') ? 'ok' : 'warn'];
    $checks[] = ['DEPLOY_TOKEN', $token !== '' ? 'set' : 'empty (tool disabled)', $token !== '' ? 'ok' : 'bad'];
    $checks[] = ['public/images/products writable', is_writable($root.'/public/images/products') ? 'yes' : 'no', is_writable($root.'/public/images/products') ? 'ok' : 'bad'];
    $checks[] = ['public/images/products files', (string) count(glob($root.'/public/images/products/*') ?: []), 'ok'];
    $checks[] = ['legacy ./images/products folder', is_dir($root.'/images/products') ? count(glob($root.'/images/products/*') ?: []).' file(s)' : 'not present', is_dir($root.'/images/products') ? 'warn' : 'ok'];
    $checks[] = ['storage writable', is_writable($root.'/storage') ? 'yes' : 'no', is_writable($root.'/storage') ? 'ok' : 'bad'];
    $checks[] = ['bootstrap/cache writable', is_writable($root.'/bootstrap/cache') ? 'yes' : 'no', is_writable($root.'/bootstrap/cache') ? 'ok' : 'bad'];

    $rows = '';
    foreach ($checks as [$label, $value, $state]) {
        $rows .= '<tr><td>'.h($label).'</td><td class="'.$state.'">'.h($value).'</td></tr>';
    }

    $codeMarkers = [
        'New hero video (public/vedios/headervedio.mp4)' => is_file($root.'/public/vedios/headervedio.mp4'),
        'New image command (DoctorImages.php)' => is_file($root.'/app/Console/Commands/DoctorImages.php'),
        'Image optimizer class' => is_file($root.'/app/Services/ImageOptimizer.php'),
        'Self-hosted fontawesome' => is_file($root.'/public/webfonts/fa-solid-900.woff2'),
        'Temporary routes in routes/web.php' => is_file($root.'/routes/web.php') && str_contains((string) @file_get_contents($root.'/routes/web.php'), '/_deploy'),
    ];
    $markerRows = '';
    foreach ($codeMarkers as $label => $present) {
        $markerRows .= '<tr><td>'.h($label).'</td><td class="'.($present ? 'ok' : 'warn').'">'.($present ? 'present' : 'missing (old code)').'</td></tr>';
    }

    $dbRows = '';
    $missingRows = '';
    $missingCount = 0;
    $productCount = 0;

    try {
        $pdo = db_connect($root);
        $productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $references = media_references($pdo);
        $missing = missing_media($root, $references);
        $missingCount = count($missing);
        $dbRows .= '<tr><td>Database connection</td><td class="ok">OK</td></tr>';
        $dbRows .= '<tr><td>Products</td><td class="ok">'.$productCount.'</td></tr>';
        $dbRows .= '<tr><td>Media references</td><td>'.count($references).'</td></tr>';
        $dbRows .= '<tr><td>Missing media files</td><td class="'.($missingCount === 0 ? 'ok' : 'bad').'">'.$missingCount.'</td></tr>';

        $i = 0;
        foreach ($missing as $base => $reference) {
            if ($i++ >= 40) {
                $missingRows .= '<tr><td colspan="3">… and '.(count($missing) - 40).' more</td></tr>';
                break;
            }
            $missingRows .= '<tr><td>#'.h($reference['product_id']).'</td><td>'.h($reference['product']).'</td><td><code>'.h($base).'</code></td></tr>';
        }
    } catch (Throwable $e) {
        $dbRows .= '<tr><td>Database connection</td><td class="bad">FAILED: '.h($e->getMessage()).'</td></tr>';
    }

    $body = '<div class="card"><h2>Environment</h2><table>'.$rows.'</table></div>'
        .'<div class="card"><h2>Code version markers</h2><table>'.$markerRows.'</table></div>'
        .'<div class="card"><h2>Database &amp; product media</h2><table>'.$dbRows.'</table>'
        .($missingCount > 0 ? '<h2>Missing files</h2><table><tr><th>ID</th><th>Product</th><th>Expected file</th></tr>'.$missingRows.'</table>' : '')
        .'</div>'
        .'<div class="card"><h2>What to do</h2>'
        .'<p><strong>1.</strong> Update the code from GitHub (a backup ZIP is created first).</p>'
        .'<p><strong>2.</strong> Recover missing images from backup folders/archives on the server.</p>'
        .'<p><strong>3.</strong> Run migrations and clear caches.</p>'
        .'<p class="warn">If images are still missing, upload the original photos as a ZIP below (the tool matches WhatsApp filenames automatically, or names the photo after the product), or restore <code>public/images/products</code> from the hosting backup.</p>'
        .'<p>'.$link('update', '1 · Update code').' '.$link('recover', '2 · Recover images').' '.$link('run', '3 · Run migrations & caches').'</p>'
        .'<form method="post" enctype="multipart/form-data" action="'.h($self.'?'.http_build_query(['token' => $token, 'action' => 'upload_media'])).'">'
        .'<input type="hidden" name="token" value="'.h($token).'">'
        .'<input type="file" name="media_zip" accept=".zip" required> '
        .'<button class="btn alt" type="submit">Upload images ZIP</button>'
        .'</form>'
        .'</div>';

    render_page('Deploy & repair', $body, [], $token, $self);
}

if ($action === 'update') {
    $log[] = '=== UPDATE CODE FROM GITHUB ===';

    if (($blocked = update_preflight($root)) !== null) {
        render_page('Update aborted', '<div class="card"><p class="bad">Update blocked by a server check.</p><p>'.h($blocked).'</p><p>'.$link('status', 'Back to status').'</p></div>', $log, $token, $self);
    }

    try {
        $log[] = 'Creating a backup of the current code…';
        $backup = backup_root($root);
        $log[] = 'Backup created: '.$backup.' ('.human_bytes((int) filesize($backup)).')';
    } catch (Throwable $e) {
        render_page('Update aborted', '<div class="card"><p class="bad">Backup failed, so nothing was changed.</p><p>'.h($e->getMessage()).'</p><p>'.$link('status', 'Back to status').'</p></div>', $log, $token, $self);
    }

    $zipFile = null;
    $temp = null;

    try {
        $log[] = 'Downloading the latest main branch from GitHub…';
        $download = download_github_zip('https://codeload.github.com/mohsin-gohar/Alishe_Nails/zip/refs/heads/main');
        $zipFile = $download['source'];
        $temp = $download['temp'];
        $log[] = 'Downloaded '.human_bytes((int) filesize($zipFile)).'.';
    } catch (Throwable $e) {
        $log[] = $e->getMessage();
        $body = '<div class="card"><p class="bad">Automatic download failed.</p>'
            .'<p>Download the ZIP on your computer from <code>https://github.com/mohsin-gohar/Alishe_Nails/archive/refs/heads/main.zip</code> and upload it here:</p>'
            .'<form method="post" enctype="multipart/form-data" action="'.h($self.'?'.http_build_query(['token' => $token, 'action' => 'update_upload'])).'">'
            .'<input type="hidden" name="token" value="'.h($token).'">'
            .'<input type="file" name="code_zip" accept=".zip" required> '
            .'<button class="btn" type="submit">Upload &amp; install</button>'
            .'</form><p>'.$link('status', 'Back to status').'</p></div>';
        render_page('Update — manual upload', $body, $log, $token, $self);
    }

    try {
        $log[] = 'Installing files (existing uploads, .env and vendor are preserved)…';
        $installed = install_code_zip($zipFile, $root);
        $log[] = 'Copied '.$installed['copied'].' files.';
        $log[] = '';
        $log[] = '=== POST-UPDATE ===';
        foreach (run_post_update($root) as $line) {
            $log[] = $line;
        }
        $log[] = '';
        $log[] = 'Code update finished. Next: run "Recover images", then "Run migrations & caches".';
    } catch (Throwable $e) {
        $log[] = 'UPDATE FAILED: '.$e->getMessage();
        $log[] = 'Use the Rollback button or restore the backup ZIP manually.';
    }

    if (is_string($temp) && is_dir($temp)) {
        @unlink($temp.'/main.zip');
        @rmdir($temp);
    }

    render_page('Update complete', '<div class="card"><p>'.$link('recover', '2 · Recover images').' '.$link('run', '3 · Run migrations & caches').' '.$link('status', 'Status').' '.$link('rollback', 'Rollback', 'btn danger').'</p></div>', $log, $token, $self);
}

if ($action === 'update_upload') {
    $log[] = '=== UPDATE FROM UPLOADED ZIP ===';

    if (($blocked = update_preflight($root)) !== null) {
        render_page('Update aborted', '<div class="card"><p class="bad">Update blocked by a server check.</p><p>'.h($blocked).'</p><p>'.$link('status', 'Back to status').'</p></div>', $log, $token, $self);
    }

    try {
        $file = $_FILES['code_zip'] ?? null;
        if (! is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No ZIP was uploaded (or the upload exceeded the server limit).');
        }
        if (! is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Invalid upload.');
        }
        $log[] = 'Creating a backup of the current code…';
        $backup = backup_root($root);
        $log[] = 'Backup created: '.$backup.' ('.human_bytes((int) filesize($backup)).')';
        $log[] = 'Installing files…';
        $installed = install_code_zip($file['tmp_name'], $root);
        $log[] = 'Copied '.$installed['copied'].' files.';
        $log[] = '';
        $log[] = '=== POST-UPDATE ===';
        foreach (run_post_update($root) as $line) {
            $log[] = $line;
        }
    } catch (Throwable $e) {
        $log[] = 'UPDATE FAILED: '.$e->getMessage();
    }

    render_page('Update complete', '<div class="card"><p>'.$link('recover', '2 · Recover images').' '.$link('run', '3 · Run migrations & caches').' '.$link('status', 'Status').' '.$link('rollback', 'Rollback', 'btn danger').'</p></div>', $log, $token, $self);
}

if ($action === 'recover') {
    $log[] = '=== RECOVER MISSING PRODUCT MEDIA ===';

    try {
        $pdo = db_connect($root);
        $references = media_references($pdo);
        $missing = missing_media($root, $references);
        $log[] = 'Products: '.(int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $log[] = 'Media references: '.count($references);
        $log[] = 'Missing files: '.count($missing);
        $log[] = '';

        if ($missing === []) {
            $log[] = 'Nothing to recover — every referenced image is already on disk.';
        } else {
            $result = recover_media($root, $missing);
            foreach ($result['log'] as $line) {
                $log[] = $line;
            }
            $log[] = '';
            $log[] = 'Recovered: '.$result['restored'].' file(s).';
        }
    } catch (Throwable $e) {
        $log[] = 'RECOVERY FAILED: '.$e->getMessage();
    }

    render_page('Media recovery', '<div class="card"><p>'.$link('status', 'Status').' '.$link('run', '3 · Run migrations & caches').' '.$link('update', '1 · Update code').'</p>'
        .'<p class="muted">If files are still missing, restore <code>public/images/products</code> from the hosting backup, or upload the original photos as a ZIP.</p></div>', $log, $token, $self);
}

if ($action === 'upload_media') {
    $log[] = '=== MATCH UPLOADED IMAGES TO THE DATABASE ===';

    try {
        $file = $_FILES['media_zip'] ?? null;
        if (! is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No ZIP was uploaded (or the upload exceeded the server limit).');
        }
        if (! is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Invalid upload.');
        }
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive is not available on this server.');
        }

        $pdo = db_connect($root);
        $references = media_references($pdo);
        $missing = missing_media($root, $references);

        $zip = new ZipArchive();
        if ($zip->open($file['tmp_name']) !== true) {
            throw new RuntimeException('The uploaded file is not a valid ZIP.');
        }

        $restored = 0;
        $linked = 0;
        $unmatched = [];
        $normalizedMissing = [];
        foreach ($missing as $base => $reference) {
            $normalizedMissing[normalize_media_name($base)][] = $base;
        }

        // Photos dropped in without their database filename can still be
        // linked when they are named after the product (slug or title).
        $products = $pdo->query('SELECT id, name, slug FROM products ORDER BY id')->fetchAll();
        $productKeys = [];
        foreach ($products as $product) {
            foreach ([$product['slug'], $product['name']] as $value) {
                $key = normalize_media_name((string) $value);
                if ($key !== '' && ! isset($productKeys[$key])) {
                    $productKeys[$key] = $product;
                }
            }
        }
        $linkedSlugs = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);
            $base = basename(str_replace('\\', '/', $entry));
            if ($base === '' || ! is_image_name($base)) {
                continue;
            }

            $matchedBase = isset($missing[$base]) ? $base : null;
            if ($matchedBase === null) {
                $key = normalize_media_name($base);
                if (isset($normalizedMissing[$key])) {
                    $matchedBase = $normalizedMissing[$key][0];
                }
            }

            if ($matchedBase === null || ! isset($missing[$matchedBase])) {
                $contents = $zip->getFromIndex($i);
                $entryKey = normalize_media_name($base);
                $product = null;

                if ($entryKey !== '' && isset($productKeys[$entryKey])) {
                    $product = $productKeys[$entryKey];
                } else {
                    foreach ($productKeys as $key => $candidate) {
                        if ($key === '' || strlen($key) < 10 || $entryKey === '') {
                            continue;
                        }
                        if (str_contains($entryKey, $key) || (strlen($entryKey) >= 10 && str_contains($key, $entryKey))) {
                            $product = $candidate;
                            break;
                        }
                    }
                }

                $extension = media_extension($base);
                if (
                    $product !== null
                    && ! isset($linkedSlugs[$product['slug']])
                    && in_array($extension, ['webp', 'jpg', 'jpeg', 'png'], true)
                    && is_string($contents) && $contents !== ''
                ) {
                    $target = $root.'/public/images/products/'.$product['slug'].'.'.$extension;
                    if (! is_dir(dirname($target)) && ! @mkdir(dirname($target), 0755, true) && ! is_dir(dirname($target))) {
                        $target = null;
                    }
                    if ($target !== null && @file_put_contents($target, $contents) !== false && filesize($target) > 0) {
                        @chmod($target, 0644);
                        $linkedSlugs[$product['slug']] = true;
                        $linked++;
                        $log[] = 'LINKED    #'.$product['id'].' '.$product['name'].' → '.$product['slug'].'.'.$extension.'  (from '.$entry.')';
                        continue;
                    }
                }

                if (count($unmatched) < 15) {
                    $unmatched[] = $entry;
                }
                continue;
            }

            $contents = $zip->getFromIndex($i);
            if (! is_string($contents) || $contents === '') {
                continue;
            }

            $reference = $missing[$matchedBase];
            if (! is_dir(dirname($reference['file'])) && ! @mkdir(dirname($reference['file']), 0755, true) && ! is_dir(dirname($reference['file']))) {
                continue;
            }
            if (@file_put_contents($reference['file'], $contents) !== false && filesize($reference['file']) > 0) {
                @chmod($reference['file'], 0644);
                $restored++;
                $log[] = 'RESTORED  #'.$reference['product_id'].' '.$reference['product'].' → '.$matchedBase.'  (from '.$entry.')';
                unset($missing[$matchedBase]);
            }
        }
        $zip->close();

        $log[] = '';
        $log[] = 'Restored '.$restored.' exact file(s); linked '.$linked.' product-name photo(s). Still missing: '.count($missing).'.';
        if ($unmatched !== []) {
            $log[] = 'Unmatched entries (first 15):';
            foreach ($unmatched as $entry) {
                $log[] = '  '.$entry;
            }
        }
        if ($missing !== []) {
            $log[] = 'Still missing:';
            foreach ($missing as $base => $reference) {
                $log[] = '  #'.$reference['product_id'].' '.$reference['product'].' → '.$base;
            }
        }
    } catch (Throwable $e) {
        $log[] = 'UPLOAD FAILED: '.$e->getMessage();
    }

    render_page('Media upload', '<div class="card"><p>'.$link('status', 'Status').' '.$link('recover', '2 · Recover images').'</p></div>', $log, $token, $self);
}

if ($action === 'run') {
    $log[] = '=== MIGRATIONS & CACHES ===';
    foreach (run_post_update($root) as $line) {
        $log[] = $line;
    }

    render_page('Migrations & caches', '<div class="card"><p>'.$link('status', 'Status').' '.$link('recover', '2 · Recover images').'</p></div>', $log, $token, $self);
}

if ($action === 'rollback') {
    $log[] = '=== ROLLBACK ===';
    $dir = dirname($root).'/.alishe-deploy-backups';
    if (! is_dir($dir)) {
        $dir = $root.'/storage/backups';
    }
    $files = glob($dir.'/pre-deploy-*.zip') ?: [];
    rsort($files);
    $latest = $files[0] ?? null;

    if ($latest === null) {
        $log[] = 'No backup ZIP found.';
    } else {
        try {
            $log[] = 'Restoring '.$latest.'…';
            $temp = rtrim(sys_get_temp_dir(), '/\\').'/alishe-rollback-'.bin2hex(random_bytes(4));
            @mkdir($temp, 0775, true);
            $zip = new ZipArchive();
            if ($zip->open($latest) !== true) {
                throw new RuntimeException('Cannot open the backup ZIP.');
            }
            $zip->extractTo($temp);
            $zip->close();

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($temp, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            $copied = 0;
            foreach ($iterator as $item) {
                $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($temp) + 1));
                $target = $root.'/'.$relative;
                if ($item->isDir()) {
                    if (! is_dir($target)) {
                        @mkdir($target, 0755, true);
                    }
                    continue;
                }
                if (! is_dir(dirname($target))) {
                    @mkdir(dirname($target), 0755, true);
                }
                if (@copy($item->getPathname(), $target)) {
                    $copied++;
                }
            }
            $log[] = 'Restored '.$copied.' files from the backup.';
            $log[] = '';
            foreach (run_post_update($root) as $line) {
                $log[] = $line;
            }
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        } catch (Throwable $e) {
            $log[] = 'ROLLBACK FAILED: '.$e->getMessage();
        }
    }

    render_page('Rollback', '<div class="card"><p>'.$link('status', 'Status').'</p></div>', $log, $token, $self);
}

if ($action === 'cleanup') {
    $deleted = [];
    foreach ([__FILE__, $root.'/deploy.php'] as $file) {
        if (! is_file($file)) {
            continue;
        }
        if (@unlink($file) || ! is_file($file)) {
            $deleted[] = $file;
        }
    }

    $log[] = $deleted !== [] ? 'Deleted: '.implode(', ', $deleted) : 'Could not delete the file(s); remove them manually via cPanel File Manager.';
    $log[] = 'Also remove DEPLOY_TOKEN from .env if you no longer need the temporary /_deploy route.';

    render_page('Tool deleted', '<div class="card"><p class="ok">The deploy tool has been removed.</p><p>You can also remove the temporary <code>/_deploy</code> and <code>/check-db-products</code> routes from <code>routes/web.php</code>.</p></div>', $log, '', '');
}

render_page('Unknown action', '<div class="card"><p>Unknown action.</p><p>'.$link('status', 'Status').'</p></div>', [], $token, $self);
