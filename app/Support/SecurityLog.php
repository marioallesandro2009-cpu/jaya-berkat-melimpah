<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * One place for security-relevant events (storage/logs/security-YYYY-MM-DD.log, kept
 * LOG_SECURITY_DAYS days): logins, failures, lockouts, logouts, changes to accounts, deleted
 * leads, uploads, failed lead emails, Turnstile fail-open. Context is ids, counts and masked
 * values only: never a password, a token, a message text or a full email address.
 */
final class SecurityLog
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function event(string $event, array $context = []): void
    {
        Log::channel('security')->info($event, array_filter([
            'ip' => request()->ip(),
            'actor' => Auth::id(),
            ...$context,
        ], fn (mixed $value): bool => $value !== null));
    }

    /**
     * "owner@example.test" -> "o***@example.test": enough to recognise an attempt, not to use it.
     */
    public static function maskEmail(mixed $email): ?string
    {
        if (! is_string($email) || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'***@'.mb_substr($domain, 0, 80);
    }
}
