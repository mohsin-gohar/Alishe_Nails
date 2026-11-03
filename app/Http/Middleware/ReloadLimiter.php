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

        // Increased from 5 to 30 per minute per route per IP. The original
        // limit of 5 was far too aggressive and would lock out a normal
        // visitor after a handful of quick page refreshes.
        $key = 'reload:' . $request->ip() . ':' . $request->path();

        if (RateLimiter::tooManyAttempts($key, 30)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->view('errors.429', [], 429)
                ->header('Retry-After', (string) $seconds);
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}