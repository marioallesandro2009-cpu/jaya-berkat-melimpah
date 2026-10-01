<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 301-redirects to the host of APP_URL (www or non-www), so the canonical domain is
 * configured in .env instead of .htaccess. The scheme (http to https) is enforced too,
 * but ONLY when FORCE_HTTPS=true (config/security.php): SSL is installed after the first
 * deploy, and forcing https before that would make the site unreachable.
 * Only active in production.
 */
class EnforceCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->isProduction()) {
            return $next($request);
        }

        $canonical = parse_url((string) config('app.url'));
        $host = $canonical['host'] ?? null;

        if ($host === null) {
            return $next($request);
        }

        // https only when the admin switched it on (and APP_URL says https); else keep what the visitor used.
        $forceHttps = (bool) config('security.force_https') && ($canonical['scheme'] ?? 'https') === 'https';
        $scheme = $forceHttps ? 'https' : $request->getScheme();

        if ($request->getHost() !== $host || $request->getScheme() !== $scheme) {
            return redirect()->to($scheme.'://'.$host.$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
