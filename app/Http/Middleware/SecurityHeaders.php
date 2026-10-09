<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security headers for every web response. All switches live in config/security.php (.env).
 *
 * - HSTS only over https and only when HSTS_MAX_AGE > 0 (start with 300, raise later).
 * - The Content-Security-Policy covers the public pages only (not /admin, /livewire):
 *   Filament and Livewire need inline scripts. CSP_MODE=report-only (default) logs
 *   violations in the browser console and blocks nothing; "enforce" blocks.
 *   Scripts need this request's nonce (the layout's inline "js" flag script and main.js); there is no
 *   'unsafe-eval' because the public pages run plain JavaScript, no Alpine.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $mode = (string) config('security.csp.mode', 'report-only');
        $csp = in_array($mode, ['report-only', 'enforce'], true) && ! $this->isAdminArea($request);

        if ($csp) {
            Vite::useCspNonce();
        }

        $response = $next($request);

        // Hide the PHP version (also set expose_php = Off in the hosting's PHP settings).
        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');

        $headers = $response->headers;
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($this->isAdminArea($request)) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        $maxAge = (int) config('security.hsts.max_age', 0);

        if ($request->isSecure() && $maxAge > 0) {
            $headers->set('Strict-Transport-Security', "max-age={$maxAge}".(config('security.hsts.include_subdomains') ? '; includeSubDomains' : ''));
        }

        if ($csp && str_contains((string) $headers->get('Content-Type'), 'text/html')) {
            $headers->set($mode === 'enforce' ? 'Content-Security-Policy' : 'Content-Security-Policy-Report-Only', $this->policy((string) Vite::cspNonce()));
        }

        return $response;
    }

    private function isAdminArea(Request $request): bool
    {
        $admin = (string) config('security.admin_path', 'admin');

        return $request->is($admin, "{$admin}/*", 'livewire*', 'livewire-*/*', 'filament*');
    }

    private function policy(string $nonce): string
    {
        $turnstile = (bool) config('security.turnstile.enabled');
        $cloudflare = $turnstile ? ' https://challenges.cloudflare.com' : '';

        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}'{$cloudflare}",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",   // blob: = the hero's glTF textures, decoded by the browser itself
            "font-src 'self'",
            "connect-src 'self' blob:{$cloudflare}",
            $turnstile ? 'frame-src https://challenges.cloudflare.com' : "frame-src 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        if (config('security.force_https')) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
