<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

/**
 * Serves robots.txt dynamically so the sitemap URL always matches the
 * configured APP_URL — a hard-coded domain in a static file silently breaks
 * search indexing the moment the domain changes.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            'Allow: /',
            '',
            '# Private areas — no value in the index, and they leak no data.',
            'Disallow: /admin',
            'Disallow: /admin/',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /account',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /logout',
            'Disallow: /track-order',
            'Disallow: /seller/',
            'Disallow: /search',
            '',
            '# Crawl politely.',
            'Crawl-delay: 1',
            '',
            'Sitemap: '.route('sitemap'),
            '',
        ];

        return response(implode(PHP_EOL, $lines))
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
