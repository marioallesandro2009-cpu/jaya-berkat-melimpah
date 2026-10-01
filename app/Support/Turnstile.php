<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Cloudflare Turnstile (a CAPTCHA that mostly runs invisibly) for the public contact form and
 * the admin login. Off by default (TURNSTILE_ENABLED=false). Fail-safe:
 *
 * - switched off, or keys missing: everything passes;
 * - APP_ENV=local: everything passes, so development never needs Cloudflare;
 * - Cloudflare unreachable or answering with a server error, or our own secret key rejected:
 *   the visitor passes and the problem is logged. The honeypot and the rate limits still apply,
 *   and a Cloudflare outage must not take the contact form down;
 * - otherwise a missing or invalid token is refused.
 *
 * Note: Turnstile needs JavaScript, so with it on the contact form no longer works without JS
 * (the page then points those visitors to WhatsApp).
 */
final class Turnstile
{
    public const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public const RESPONSE_FIELD = 'cf-turnstile-response';

    public static function enabled(): bool
    {
        return (bool) config('security.turnstile.enabled')
            && filled(config('security.turnstile.site_key'))
            && filled(config('security.turnstile.secret_key'));
    }

    public static function siteKey(): string
    {
        return (string) config('security.turnstile.site_key');
    }

    public static function passes(?string $token, ?string $ip = null): bool
    {
        if (! self::enabled() || app()->environment('local')) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()->timeout(5)->post(self::VERIFY_URL, array_filter([
                'secret' => (string) config('security.turnstile.secret_key'),
                'response' => $token,
                'remoteip' => $ip,
            ]));
        } catch (ConnectionException $e) {
            SecurityLog::event('turnstile.fail_open', ['reason' => 'unreachable', 'error' => $e::class]);

            return true;
        }

        if ($response->serverError()) {
            SecurityLog::event('turnstile.fail_open', ['reason' => 'server_error', 'status' => $response->status()]);

            return true;
        }

        $codes = (array) $response->json('error-codes', []);

        if (array_intersect($codes, ['missing-input-secret', 'invalid-input-secret'])) {
            SecurityLog::event('turnstile.fail_open', ['reason' => 'bad_secret_key']);

            return true;
        }

        return (bool) $response->json('success', false);
    }
}
