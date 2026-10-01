<?php

namespace App\Support;

use App\Models\Certification;
use App\Models\ChainStep;
use App\Models\Faq;
use App\Models\Feature;
use App\Models\HeroSlide;
use App\Models\Leader;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\Stat;
use App\Models\TimelineItem;
use App\Models\TrustLogo;
use Illuminate\Support\Facades\Cache;

/**
 * Cached data for the public site, one entry per language. Flushed (all
 * languages) whenever admin data changes.
 *
 * @phpstan-type Data array{settings: array<string, mixed>, menus: array<string, list<array<string, mixed>>>, blocks: array<string, list<string>>, heroSlides: list<array<string, mixed>>, trustLogos: list<array<string, mixed>>, faqs: list<array<string, mixed>>, locations: list<array<string, mixed>>, sections: array<string, array<string, mixed>>, stats: list<array<string, mixed>>, products: list<array<string, mixed>>, chain: list<array<string, mixed>>, features: array<string, list<array<string, mixed>>>, timeline: list<array<string, mixed>>, leaders: list<array<string, mixed>>, certifications: list<array<string, mixed>>, recentPosts: list<array<string, mixed>>}
 */
final class FrontendData
{
    /**
     * @return Data
     */
    public static function all(?string $locale = null): array
    {
        $locale ??= Locales::current();

        return Cache::rememberForever(self::key($locale), fn (): array => self::build($locale));
    }

    /**
     * The data in one language (texts use that language, falling back to English).
     *
     * @return Data
     */
    private static function build(string $locale): array
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            $features = [];

            foreach (array_keys(Feature::GROUPS) as $group) {
                $features[$group] = array_values(Feature::query()->group($group)->active()->ordered()->get()->map->toFrontend()->all());
            }

            $menus = [];

            foreach (array_keys(MenuItem::LOCATIONS) as $location) {
                $menus[$location] = array_values(MenuItem::query()->at($location)->active()->ordered()->get()->map->toFrontend()->all());
            }

            return [
                'settings' => SiteSetting::current()->toFrontend(),
                'menus' => $menus,
                'blocks' => self::blocks(),
                'heroSlides' => array_values(HeroSlide::query()->active()->ordered()->with('media')->get()->map->toFrontend()->filter()->all()),
                'trustLogos' => array_values(TrustLogo::query()->active()->ordered()->with('media')->get()->map->toFrontend()->filter()->all()),
                'faqs' => array_values(Faq::query()->active()->ordered()->get()->map->toFrontend()->all()),
                'locations' => array_values(Location::query()->active()->ordered()->with('media')->get()->map->toFrontend()->all()),
                // Keyed by section key; a hidden (inactive) section is simply missing.
                'sections' => PageSection::query()->active()->with('media')->get()->mapWithKeys(fn (PageSection $s): array => [$s->key => $s->toFrontend()])->all(),
                'stats' => array_values(Stat::query()->active()->ordered()->get()->map->toFrontend()->all()),
                'products' => array_values(Product::query()->active()->ordered()->with(['media', 'category'])->get()->map->toFrontend()->all()),
                'chain' => array_values(ChainStep::query()->active()->ordered()->with('media')->get()->map->toFrontend()->all()),
                'features' => $features,
                'timeline' => array_values(TimelineItem::query()->active()->ordered()->get()->map->toFrontend()->all()),
                'leaders' => array_values(Leader::query()->active()->ordered()->with('media')->get()->map->toFrontend()->all()),
                'certifications' => array_values(Certification::query()->active()->ordered()->get()->map->toFrontend()->all()),
                // The home page news teaser: one capped source, nothing else re-filters.
                'recentPosts' => array_values(Post::query()->active()->published()->ordered()->with('media')->take(Post::MAX_RECENT)->get()->map->toFrontend()->all()),
            ];
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * The blocks of each page that can be reordered, in the order the admin set: the page's own
     * blocks (PageSection::FLOW) and every custom block, active ones only.
     *
     * @return array<string, list<string>>
     */
    private static function blocks(): array
    {
        $blocks = [PageSection::HOME => [], PageSection::COMPANY => []];

        foreach (PageSection::query()->active()->ordered()->get() as $section) {
            if (isset($blocks[$section->page]) && ($section->is_custom || in_array($section->key, PageSection::FLOW[$section->page], true))) {
                $blocks[$section->page][] = $section->key;
            }
        }

        return $blocks;
    }

    /**
     * @return array<string, mixed>
     */
    public static function settings(?string $locale = null): array
    {
        return self::all($locale)['settings'];
    }

    public static function flush(): void
    {
        foreach (Locales::all() as $locale) {
            Cache::forget(self::key($locale));
        }

        // Other cached pages (a news article) carry this version in their key.
        Cache::forever(self::VERSION_KEY, (int) Cache::get(self::VERSION_KEY, 0) + 1);
    }

    private const VERSION_KEY = 'frontend.version';

    /**
     * Cache other public page data (e.g. a news article) until admin data changes.
     *
     * @template T
     *
     * @param  \Closure(): T  $build
     * @return T
     */
    public static function remember(string $key, \Closure $build): mixed
    {
        $version = (int) Cache::get(self::VERSION_KEY, 0);

        return Cache::rememberForever(config('site.cache_key').".{$key}.v{$version}", $build);
    }

    private static function key(?string $locale = null): string
    {
        return config('site.cache_key').'.'.($locale ?? Locales::current());
    }
}
