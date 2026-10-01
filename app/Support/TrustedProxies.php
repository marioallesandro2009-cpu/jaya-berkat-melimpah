<?php

namespace App\Support;

/**
 * Turns the TRUSTED_PROXIES setting into what Laravel's TrustProxies middleware wants.
 */
final class TrustedProxies
{
    /**
     * @return array<int, string>|string|null null = trust nobody, "*" = trust everyone
     */
    public static function resolve(?string $setting): array|string|null
    {
        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $setting))));

        if ($parts === []) {
            return null;
        }

        if (in_array('*', $parts, true)) {
            return '*';
        }

        $proxies = [];

        foreach ($parts as $part) {
            if (strtolower($part) === 'cloudflare') {
                array_push($proxies, ...CloudflareIps::ranges());
            } else {
                $proxies[] = $part;
            }
        }

        return array_values(array_unique($proxies));
    }
}
