<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers applied to every response. The CSP allow-list
 * matches exactly the external hosts the app already uses (Google Fonts,
 * Font Awesome via cdnjs, Chart.js via cdnjs) so nothing already working
 * gets silently blocked.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        $scriptSrc = "'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com https://www.googletagmanager.com https://www.google-analytics.com https://www.clarity.ms https://connect.facebook.net";
        $styleSrc = "'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com";
        // Images: allow any https source (plus data/blob) so product photos,
        // Instagram CDN and analytics pixels always render. The previous
        // 'self'-only list silently blocked images whenever the APP_URL origin
        // differed from the page origin (http vs https, www vs non-www).
        $imgSrc = "'self' data: blob: https:";
        $connectSrc = "'self' https://www.google-analytics.com https://region1.google-analytics.com https://*.google-analytics.com https://analytics.google.com https://www.googletagmanager.com https://*.clarity.ms https://c.bing.com https://www.facebook.com https://connect.facebook.net https://graph.facebook.com https://graph.instagram.com";

        $csp = implode('; ', [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src {$imgSrc}",
            "media-src 'self' blob: https:",
            "connect-src {$connectSrc}",
            "frame-src 'self' https://www.googletagmanager.com",
            "frame-ancestors 'self'",
        ]);
        $response->headers->set('Content-Security-Policy', $csp);

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        return $response;
    }
}
