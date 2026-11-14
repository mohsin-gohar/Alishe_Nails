<?php

/**
 * Root-level entry point for the one-file deploy tool.
 *
 * Some cPanel accounts serve the Laravel project root directly instead of
 * public/. This loader makes https://your-domain/deploy.php work in both
 * setups. The real tool lives in public/deploy.php and reads DEPLOY_TOKEN
 * from .env. Delete both files after deployment.
 */

declare(strict_types=1);

if (! is_file(__DIR__.'/public/deploy.php')) {
    http_response_code(404);
    echo '<!doctype html><title>404 Not Found</title><h1>404 Not Found</h1>';
    exit;
}

require __DIR__.'/public/deploy.php';
