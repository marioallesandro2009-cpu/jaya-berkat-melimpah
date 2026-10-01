<?php

/*
|--------------------------------------------------------------------------
| Security settings
|--------------------------------------------------------------------------
|
| Everything that differs between local development and production is read from
| .env here (never with env() elsewhere: with `config:cache` .env is not read at
| request time). Defaults are the safe choice for a first deploy without SSL.
| See SECURITY_AUDIT.md and DEPLOY.md.
|
*/

return [

    /*
     * Send every visitor to https (APP_URL must start with https://). Keep false until
     * AutoSSL has issued the certificate, otherwise the site cannot be opened.
     */
    'force_https' => (bool) env('FORCE_HTTPS', false),

    /*
     * Strict-Transport-Security, sent only on https responses. max-age 0 = off. Start small
     * (300 seconds) and raise it once everything works over https. No "preload".
     */
    'hsts' => [
        'max_age' => (int) env('HSTS_MAX_AGE', 0),
        'include_subdomains' => (bool) env('HSTS_INCLUDE_SUBDOMAINS', false),
    ],

    /*
     * Content-Security-Policy for the public pages (not the admin): off | report-only | enforce.
     * report-only only logs violations in the browser console, it blocks nothing.
     */
    'csp' => [
        'mode' => env('CSP_MODE', 'report-only'),
    ],

    /*
     * Proxies whose X-Forwarded-* headers are trusted, so the visitor's real IP reaches the
     * rate limiters. Empty = trust nobody (right when the site is reached directly).
     * "cloudflare" = Cloudflare's published ranges; "*" = everyone (only behind a firewall);
     * or a comma separated list of IPs/CIDRs. Values can be combined: "cloudflare,10.0.0.5".
     */
    'trusted_proxies' => env('TRUSTED_PROXIES', ''),

    /*
     * Path of the admin panel (default "admin"). Changing it only hides the address.
     */
    'admin_path' => trim((string) env('ADMIN_PATH', 'admin'), '/') ?: 'admin',

    /*
     * Cloudflare Turnstile on the public contact form and the admin login. Off by default;
     * when on, the CSP also allows challenges.cloudflare.com.
     */
    'turnstile' => [
        'enabled' => (bool) env('TURNSTILE_ENABLED', false),
        'site_key' => env('TURNSTILE_SITE_KEY'),
        'secret_key' => env('TURNSTILE_SECRET_KEY'),
    ],

];
