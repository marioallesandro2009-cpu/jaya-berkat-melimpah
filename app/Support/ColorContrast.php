<?php

namespace App\Support;

/**
 * WCAG contrast between the brand colours that text is drawn in, checked after the admin saves
 * colours (Pengaturan Situs › Warna). Informational: the colours are saved either way.
 */
final class ColorContrast
{
    public const MIN_RATIO = 4.5;

    /** foreground token, background token, what the pair is used for. */
    private const PAIRS = [
        ['warm-off-white', 'deep-ocean', 'Teks krem di atas biru gelap (hero, navbar, bagian gelap)'],
        ['warm-off-white', 'secondary-ocean', 'Teks krem di atas biru laut (kontak, penutup)'],
        ['warm-off-white', 'near-black', 'Teks krem di atas hitam pekat (footer)'],
        ['ink', 'warm-off-white', 'Teks utama di atas krem'],
        ['aqua', 'deep-ocean', 'Aksen dingin di atas biru gelap'],
        ['accent-coral', 'deep-ocean', 'Aksen hangat di atas biru gelap'],
    ];

    /**
     * @param  array<string, string>  $colors  token => "#RRGGBB"
     * @return list<array{label: string, ratio: float}>
     */
    public static function failures(array $colors): array
    {
        $failures = [];

        foreach (self::PAIRS as [$foreground, $background, $label]) {
            $ratio = self::ratio($colors[$foreground], $colors[$background]);

            if ($ratio < self::MIN_RATIO) {
                $failures[] = ['label' => $label, 'ratio' => $ratio];
            }
        }

        return $failures;
    }

    public static function ratio(string $hexA, string $hexB): float
    {
        $a = self::luminance($hexA);
        $b = self::luminance($hexB);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    private static function luminance(string $hex): float
    {
        $linear = function (string $pair): float {
            $value = hexdec($pair) / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        };

        $hex = ltrim($hex, '#');

        return 0.2126 * $linear(substr($hex, 0, 2)) + 0.7152 * $linear(substr($hex, 2, 2)) + 0.0722 * $linear(substr($hex, 4, 2));
    }
}
