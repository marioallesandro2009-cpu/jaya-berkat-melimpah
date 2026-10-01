<?php

namespace App\Http\Middleware;

use App\Models\SiteSetting;
use App\Support\Links;
use App\Support\SecurityLog;
use App\Support\Turnstile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bot checks for the contact form, before any validation:
 *
 * - honeypot: a hidden field only bots fill in. The answer looks exactly like a success and
 *   nothing is stored, so the bot learns nothing;
 * - Cloudflare Turnstile (only when TURNSTILE_ENABLED=true; see App\Support\Turnstile).
 */
class GuardContactForm
{
    /** Hidden field that only bots fill in. */
    public const HONEYPOT = 'website';

    public function handle(Request $request, Closure $next): Response
    {
        if (filled($request->input(self::HONEYPOT))) {
            SecurityLog::event('contact.blocked', ['reason' => 'honeypot']);

            $success = SiteSetting::current()->uiTexts(app()->getLocale())['contact_success'];

            return $request->expectsJson()
                ? response()->json(['ok' => true, 'message' => $success])
                : redirect(Links::section('contact'))->with('contact_status', $success);
        }

        if (! Turnstile::passes($request->input(Turnstile::RESPONSE_FIELD), $request->ip())) {
            SecurityLog::event('contact.blocked', ['reason' => 'turnstile']);

            $failed = __('Security check failed. Reload the page and try again.');

            return $request->expectsJson()
                ? response()->json(['message' => $failed], 422)
                : redirect(Links::section('contact'))
                    ->with('contact_error', $failed)
                    ->withInput($request->except([self::HONEYPOT, Turnstile::RESPONSE_FIELD]));
        }

        return $next($request);
    }
}
