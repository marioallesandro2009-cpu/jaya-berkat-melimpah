<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Meta data of a public page (title, description, canonical, hreflang, social image).
 *
 * @phpstan-type SeoMeta array{title: string, description: string, canonical: string, alternates: array<string, string>, ogLocale: string, ogAlternates: list<string>, siteName: string, image: array<string, mixed>|null, imageAlt: string, preload: array<string, mixed>|null, themeColor: string, type: string}
 */
final class Seo
{
    public const THEME_COLOR = '#071A21';

    public static function baseUrl(): string
    {
        return rtrim((string) config('app.url'), '/');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return SeoMeta
     */
    public static function home(array $data): array
    {
        $settings = $data['settings'];
        $hero = ($data['heroSlides'][0]['image'] ?? null) ?: ($data['sections']['hero']['image'] ?? null);

        return self::build(
            settings: $settings,
            title: (string) $settings['seo']['title'],
            description: (string) $settings['seo']['description'],
            paths: Locales::paths('/'),
            image: $settings['seo']['image'] ?? ($hero['large'] ?? null),
            imageAlt: (string) ($hero['alt'] ?? $settings['companyName']),
            preload: $hero,
        );
    }

    /**
     * A top-level page ("company", "news") with its own title and description.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $image
     * @return SeoMeta
     */
    public static function page(array $data, string $name, string $title, ?string $description, ?array $image = null): array
    {
        $settings = $data['settings'];

        return self::build(
            settings: $settings,
            title: $title.' | '.$settings['companyName'],
            description: (string) ($description ?: $settings['seo']['description']),
            paths: Links::pagePaths($name),
            image: $image['large'] ?? $settings['seo']['image'],
            imageAlt: (string) ($image['alt'] ?? $settings['companyName']),
            preload: $image,
        );
    }

    /**
     * A product's own page.
     *
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $paths  locale => path
     * @return SeoMeta
     */
    public static function product(array $page, array $settings, array $paths): array
    {
        return self::build(
            settings: $settings,
            title: (string) ($page['seoTitle'] ?? $page['name'].' | '.$settings['companyName']),
            description: (string) ($page['seoDescription'] ?? $page['intro'] ?? $settings['seo']['description']),
            paths: $paths,
            image: $page['image']['large'] ?? $settings['seo']['image'],
            imageAlt: (string) ($page['image']['alt'] ?? $page['name']),
            preload: $page['image'],
        );
    }

    /**
     * @param  array<string, mixed>  $page
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $paths  locale => path
     * @return SeoMeta
     */
    public static function newsPost(array $page, array $settings, array $paths): array
    {
        $meta = self::build(
            settings: $settings,
            title: (string) ($page['seoTitle'] ?? $page['title'].' | '.$settings['companyName']),
            description: (string) ($page['seoDescription'] ?? $page['excerpt'] ?? $settings['seo']['description']),
            paths: $paths,
            image: $page['image']['large'] ?? $settings['seo']['image'],
            imageAlt: (string) ($page['image']['alt'] ?? $page['title']),
            preload: $page['image'],
        );
        $meta['type'] = 'article';

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @param  array<string, string>  $paths  locale => path
     * @param  array<string, mixed>|null  $image
     * @param  array<string, mixed>|null  $preload
     * @return SeoMeta
     */
    private static function build(array $settings, string $title, string $description, array $paths, ?array $image, string $imageAlt, ?array $preload): array
    {
        $locale = Locales::current();
        $alternates = array_map(fn (string $path): string => self::baseUrl().$path, $paths);

        return [
            'title' => $title,
            'description' => Str::limit(trim(strip_tags($description)), 300, ''),
            'canonical' => $alternates[$locale] ?? self::baseUrl().$paths[$locale],
            'alternates' => $alternates,
            'ogLocale' => Locales::ogLocale($locale),
            'ogAlternates' => array_values(array_map(Locales::ogLocale(...), array_diff(Locales::all(), [$locale]))),
            'siteName' => (string) $settings['companyName'],
            'image' => $image,
            'imageAlt' => $imageAlt,
            'preload' => $preload,
            'themeColor' => (string) ($settings['colors']['deep-ocean'] ?? self::THEME_COLOR),
            'type' => 'website',
        ];
    }
}
