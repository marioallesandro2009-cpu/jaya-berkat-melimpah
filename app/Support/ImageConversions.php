<?php

namespace App\Support;

/**
 * Single place that decides whether WebP conversions are registered.
 */
final class ImageConversions
{
    public const MODES = ['auto', 'on', 'off'];

    public static function mode(): string
    {
        $mode = strtolower((string) config('site.image_conversions', 'auto'));

        return in_array($mode, self::MODES, true) ? $mode : 'auto';
    }

    public static function driverAvailable(): bool
    {
        return extension_loaded('gd') || extension_loaded('imagick');
    }

    /**
     * The spatie/image driver to use, preferring GD.
     */
    public static function driver(): string
    {
        return extension_loaded('gd') || ! extension_loaded('imagick') ? 'gd' : 'imagick';
    }

    public static function enabled(): bool
    {
        return match (self::mode()) {
            'on' => true,
            'off' => false,
            default => self::driverAvailable(),
        };
    }

    /**
     * Human readable status for the admin panel.
     */
    public static function statusLabel(): string
    {
        if (self::enabled()) {
            return 'Konversi WebP: Aktif ('.strtoupper(self::driver()).')';
        }

        return self::mode() === 'off'
            ? 'Konversi WebP: Nonaktif (IMAGE_CONVERSIONS=off)'
            : 'Konversi WebP: Nonaktif (GD tidak tersedia)';
    }
}
