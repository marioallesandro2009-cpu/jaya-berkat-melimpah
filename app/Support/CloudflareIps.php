<?php

namespace App\Support;

/**
 * Cloudflare's published proxy ranges (https://www.cloudflare.com/ips/), used when
 * TRUSTED_PROXIES contains "cloudflare". The list below is the bundled fallback; run
 * `php artisan security:cloudflare-ips` on the server to fetch the current list into
 * storage/app/cloudflare-ips.json, which then takes precedence.
 */
final class CloudflareIps
{
    /** @var list<string> */
    public const BUNDLED = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    public static function path(): string
    {
        return storage_path('app/cloudflare-ips.json');
    }

    /**
     * @return list<string>
     */
    public static function ranges(): array
    {
        $file = self::path();

        if (is_file($file)) {
            $saved = json_decode((string) file_get_contents($file), true);

            if (is_array($saved) && $saved !== [] && array_filter($saved, fn ($range): bool => ! is_string($range)) === []) {
                return array_values($saved);
            }
        }

        return self::BUNDLED;
    }
}
