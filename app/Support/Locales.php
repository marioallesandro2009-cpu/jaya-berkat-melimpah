<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Site languages (config/site.php "locales"). The first one is the default and
 * lives at the root (/...); the others live under their prefix (/id/...).
 */
final class Locales
{
    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys((array) config('site.locales'));
    }

    /**
     * Languages in the order of the navbar switch.
     *
     * @return list<string>
     */
    public static function switchOrder(): array
    {
        $order = array_values(array_intersect((array) config('site.locale_switch_order', []), self::all()));

        return [...$order, ...array_values(array_diff(self::all(), $order))];
    }

    public static function default(): string
    {
        return self::all()[0];
    }

    public static function current(): string
    {
        $locale = app()->getLocale();

        return in_array($locale, self::all(), true) ? $locale : self::default();
    }

    public static function prefix(string $locale): string
    {
        return (string) config("site.locales.{$locale}.prefix", '');
    }

    /**
     * Open Graph locale, e.g. en_US / id_ID.
     */
    public static function ogLocale(string $locale): string
    {
        return (string) config("site.locales.{$locale}.og", $locale);
    }

    /**
     * Name of the language in that language, e.g. "English", "Bahasa Indonesia".
     */
    public static function nativeName(string $locale): string
    {
        return (string) config("site.locales.{$locale}.native", strtoupper($locale));
    }

    /**
     * Path of a page in a language: ("/", "id") -> "/id", ("/about", "id") -> "/id/about".
     */
    public static function path(string $locale, string $page = '/'): string
    {
        $page = '/'.trim($page, '/');
        $prefix = self::prefix($locale);

        if ($prefix === '') {
            return $page;
        }

        return '/'.$prefix.($page === '/' ? '' : $page);
    }

    /**
     * Absolute URL of a page in a language, from APP_URL.
     */
    public static function url(string $locale, string $page = '/'): string
    {
        return Seo::baseUrl().self::path($locale, $page);
    }

    /**
     * Path of a page in every language: [locale => path], e.g. ("/") -> ["en" => "/", "id" => "/id"].
     *
     * @return array<string, string>
     */
    public static function paths(string $page = '/'): array
    {
        $paths = [];

        foreach (self::all() as $locale) {
            $paths[$locale] = self::path($locale, $page);
        }

        return $paths;
    }

    /**
     * The same page in every language: [locale => absolute URL].
     *
     * @return array<string, string>
     */
    public static function alternates(string $page = '/'): array
    {
        $urls = [];

        foreach (self::all() as $locale) {
            $urls[$locale] = self::url($locale, $page);
        }

        return $urls;
    }

    /**
     * Register the URL of the current page in every language, for pages whose path
     * differs per language (/services/cold-storage, /id/layanan/penyimpanan-dingin).
     *
     * @param  array<string, string>  $paths  locale => path
     */
    public static function setPagePaths(array $paths): void
    {
        request()->attributes->set('locale_paths', $paths);
    }

    /**
     * Path of the current page in a language (language switch).
     */
    public static function pathFor(Request $request, string $locale): string
    {
        $paths = (array) $request->attributes->get('locale_paths', []);

        return $paths[$locale] ?? self::path($locale, self::pagePath($request));
    }

    /**
     * Current page without its language prefix: "/id" -> "/", "/id/about" -> "/about".
     */
    public static function pagePath(Request $request): string
    {
        $segments = $request->segments();
        $prefix = self::prefix(self::current());

        if ($prefix !== '' && ($segments[0] ?? null) === $prefix) {
            array_shift($segments);
        }

        return '/'.implode('/', $segments);
    }
}
