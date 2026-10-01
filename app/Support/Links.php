<?php

namespace App\Support;

/**
 * Links that work from every page: "#contact" on the home page, "/#contact"
 * (or "/id#contact") everywhere else, so the navbar, footer and buttons are the same
 * on every page. Also the localized paths of the pages (/company, /id/perusahaan, ...).
 */
final class Links
{
    public static function onHome(): bool
    {
        return request()->routeIs('home', '*.home');
    }

    public static function section(string $id): string
    {
        return (self::onHome() ? '' : Locales::path(Locales::current())).'#'.$id;
    }

    /**
     * URL segment of a page in a language: ("company", "id") -> "perusahaan".
     */
    public static function segment(string $name, ?string $locale = null): string
    {
        $locale ??= Locales::current();

        return (string) config("site.locales.{$locale}.segments.{$name}", $name);
    }

    /**
     * Path of a top-level page in a language: ("company") -> /company, /id/perusahaan.
     */
    public static function page(string $name, ?string $locale = null): string
    {
        $locale ??= Locales::current();

        return Locales::path($locale, '/'.self::segment($name, $locale));
    }

    /**
     * The same page in every language: [locale => path], for the language switch and hreflang.
     *
     * @return array<string, string>
     */
    public static function pagePaths(string $name): array
    {
        $paths = [];

        foreach (Locales::all() as $locale) {
            $paths[$locale] = self::page($name, $locale);
        }

        return $paths;
    }

    /**
     * Link of a menu item (MenuItem::toFrontend()) for the page being shown, or null when it
     * must not be shown (the News page while no article is published).
     *
     * @param  array<string, mixed>  $item
     * @return array{href: string, current: bool, newTab: bool}|null
     */
    public static function menu(array $item, bool $hasNews): ?array
    {
        $target = (string) $item['target'];

        if ($item['type'] === 'url') {
            return ['href' => $target, 'current' => false, 'newTab' => (bool) $item['newTab']];
        }

        if ($item['type'] === 'section') {
            return ['href' => self::section($target), 'current' => false, 'newTab' => false];
        }

        [$page, $anchor] = array_pad(explode('#', $target, 2), 2, null);

        if ($page === 'news' && ! $hasNews) {
            return null;
        }

        $path = $page === 'home' ? Locales::path(Locales::current()) : self::page($page);
        $routes = ['home' => ['home', '*.home'], 'company' => ['company', '*.company'], 'news' => ['news.*', '*.news.*']];

        return [
            'href' => $path.($anchor ? '#'.$anchor : ''),
            'current' => $anchor === null && request()->routeIs(...($routes[$page] ?? [])),
            'newTab' => false,
        ];
    }

    /**
     * A link that is safe to put in an href: an anchor, a path on this site, http(s), mailto or tel.
     * Anything else (javascript:, data:, vbscript:, "//host") becomes null. Applied to every link an
     * admin can type, as a second line of defence next to the admin form validation.
     */
    public static function safe(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || preg_match('/[\x00-\x1F\x7F\s]/u', $url)) {
            return null;
        }

        return preg_match('~^(#|/(?!/)|https?://|mailto:|tel:)~i', $url) === 1 ? $url : null;
    }

    /**
     * The inquiry form with a product already chosen: "#contact" on the home page, otherwise the
     * home page with ?product=<name> (the form reads it) and the #contact anchor.
     */
    public static function contactFor(string $product): string
    {
        return self::onHome()
            ? '#contact'
            : Locales::path(Locales::current()).'?'.http_build_query(['product' => $product]).'#contact';
    }

    /**
     * Where the inquiry form posts to: /contact, /id/kontak.
     */
    public static function contactAction(): string
    {
        return self::page('contact');
    }
}
