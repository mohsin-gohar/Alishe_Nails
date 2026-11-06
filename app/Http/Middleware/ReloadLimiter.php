<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents reload spam: a single IP may load the same page at most 5 times
 * per minute. After that, further requests are rejected with HTTP 429 and a
 * clear "you cannot reload again" message, protecting the server from being
 * hammered by repeated refreshes (including on /login and /register).
 */
class ReloadLimiter
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only count full page loads (GET). POST/PUT/PATCH/DELETE actions are
        // governed by their own, more specific throttle rules.
        if ($request->method() !== 'GET') {
            return $next($request);
        }

        // Exempt administrative and seller portal routes from reload limiting
        // so staff work and product management are never locked out.
        if ($request->is('admin*') || $request->is('seller*')) {
            return $next($request);
        }

        // Count each distinct URL separately (path + query string) so
        // navigating between filter/sort links is not mistaken for reload
        // spam. Only repeatedly refreshing the exact same URL is limited.
        $key = 'reload:' . $request->ip() . ':' . $request->getRequestUri();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->view('errors.429', [], 429)
                ->header('Retry-After', (string) $seconds);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}