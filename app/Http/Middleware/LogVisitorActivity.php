<?php

namespace App\Http\Middleware;

use App\Models\VisitorLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogVisitorActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        try {
            if (! $this->shouldLog($request)) {
                return;
            }

            $ua = (string) $request->userAgent();
            [$browser, $platform, $deviceType] = $this->parseUserAgent($ua);
            $isBot = $this->isBot($ua);
            $path = '/'.ltrim($request->path(), '/');
            $status = $response->getStatusCode();

            VisitorLog::create([
                'visited_at' => now(),
                'ip_address' => $this->clientIp($request),
                'user_type' => $this->userType(),
                'user_id' => $this->userId(),
                'session_id' => $this->sessionId($request),
                'method' => $request->method(),
                'path' => Str::limit($path, 500, ''),
                'full_url' => Str::limit($request->fullUrl(), 1000, ''),
                'route_name' => $request->route()?->getName(),
                'referrer' => Str::limit((string) $request->headers->get('referer'), 1000, ''),
                'user_agent' => Str::limit($ua, 1000, ''),
                'device_type' => $deviceType,
                'browser' => $browser,
                'platform' => $platform,
                'country' => $this->headerFirst($request, ['CF-IPCountry', 'X-AppEngine-Country', 'X-Country-Code', 'CloudFront-Viewer-Country']),
                'region' => $this->headerFirst($request, ['CF-Region', 'X-Region', 'X-AppEngine-Region']),
                'city' => $this->headerFirst($request, ['CF-IPCity', 'X-City', 'X-AppEngine-City']),
                'status_code' => $status,
                'is_bot' => $isBot,
                'is_suspicious' => $this->isSuspicious($request, $status, $path),
            ]);
        } catch (Throwable $e) {
            // Never break a live request because logging failed (e.g. table not migrated yet).
            report($e);
        }
    }

    private function shouldLog(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        // Skip framework/health noise.
        if ($request->is('up') || $request->is('_debugbar*') || $request->is('telescope*') || $request->is('horizon*')) {
            return false;
        }

        // Skip static assets and common non-page resources.
        if (preg_match('/\.(?:css|js|map|png|jpe?g|gif|webp|avif|svg|ico|woff2?|ttf|eot|otf|mp4|webm|mov|mp3|pdf|txt|xml|json)$/i', $path)) {
            return false;
        }

        if ($request->is('build/*') || $request->is('storage/*') || $request->is('vendor/*')) {
            return false;
        }

        // Log page views and meaningful requests (including errors). Skip preflight.
        if ($request->method() === 'OPTIONS') {
            return false;
        }

        return true;
    }

    private function clientIp(Request $request): ?string
    {
        $ip = $request->ip();

        return $ip ? Str::limit($ip, 45, '') : null;
    }

    private function userType(): string
    {
        if (Auth::guard('admin')->check()) {
            return 'admin';
        }
        if (Auth::guard('seller')->check()) {
            return 'seller';
        }
        if (Auth::guard('web')->check()) {
            return 'customer';
        }

        return 'guest';
    }

    private function userId(): ?int
    {
        foreach (['admin', 'seller', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->id();
            }
        }

        return null;
    }

    private function sessionId(Request $request): ?string
    {
        try {
            if ($request->hasSession()) {
                $id = $request->session()->getId();

                return $id ? substr(hash('sha256', $id), 0, 40) : null;
            }
        } catch (Throwable $e) {
            // ignore
        }

        return null;
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: string}
     */
    private function parseUserAgent(string $ua): array
    {
        if ($ua === '') {
            return [null, null, 'unknown'];
        }

        $platform = 'Other';
        foreach ([
            'Windows' => 'Windows',
            'Android' => 'Android',
            'iPhone|iPad|iPod' => 'iOS',
            'Macintosh|Mac OS X' => 'macOS',
            'Linux' => 'Linux',
        ] as $pattern => $name) {
            if (preg_match('/'.$pattern.'/i', $ua)) {
                $platform = $name;
                break;
            }
        }

        $browser = 'Other';
        foreach ([
            'Edg' => 'Edge',
            'OPR|Opera' => 'Opera',
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'MSIE|Trident' => 'Internet Explorer',
        ] as $pattern => $name) {
            if (preg_match('/'.$pattern.'/i', $ua)) {
                // Chrome token appears in Edge/Opera/Safari UAs; order above handles it.
                $browser = $name;
                break;
            }
        }

        $device = 'desktop';
        if (preg_match('/bot|crawl|spider|slurp|bingpreview/i', $ua)) {
            $device = 'bot';
        } elseif (preg_match('/tablet|ipad/i', $ua)) {
            $device = 'tablet';
        } elseif (preg_match('/mobile|android|iphone|ipod/i', $ua)) {
            $device = 'mobile';
        }

        return [$browser, $platform, $device];
    }

    private function isBot(string $ua): bool
    {
        return (bool) preg_match('/bot|crawl|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegrambot|discordbot|curl|wget|python-requests/i', $ua);
    }

    private function isSuspicious(Request $request, int $status, string $path): bool
    {
        $lower = strtolower($path.' '.$request->fullUrl());

        if (preg_match('#/(?:\.env|wp-admin|wp-login|xmlrpc\.php|phpmyadmin|\.git|cgi-bin|etc/passwd)#i', $lower)) {
            return true;
        }

        if (str_contains($lower, '../') || str_contains($lower, '..\\') || str_contains($lower, '%2e%2e')) {
            return true;
        }

        // Repeated auth probing is surfaced by logs; flag obvious failure spikes contextually via status on sensitive endpoints.
        if (in_array($status, [401, 403], true) || ($status === 404 && preg_match('/\.(php|asp|aspx|jsp)$/i', $path))) {
            return true;
        }

        return false;
    }

    /**
     * @param  list<string>  $headers
     */
    private function headerFirst(Request $request, array $headers): ?string
    {
        foreach ($headers as $header) {
            $value = $request->header($header);
            if (is_string($value) && $value !== '' && strtoupper($value) !== 'XX') {
                return Str::limit($value, 100, '');
            }
        }

        return null;
    }
}
