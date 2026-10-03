<?php

namespace App\Http\Controllers;

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
use App\Models\ProductCategory;
use App\Models\SiteSetting;
use App\Models\Species;
use App\Models\Stat;
use App\Models\TimelineItem;
use App\Models\TrustLogo;
use App\Support\FrontendData;
use App\Support\Links;
use App\Support\Locales;
use App\Support\Seo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * robots.txt, sitemap.xml, llms.txt and site.webmanifest, generated from the CMS data.
 */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $lines = [
            '# Search engines and AI crawlers are welcome.',
            'User-agent: *',
            'Allow: /',
            // A custom ADMIN_PATH is not announced here (that would publish it); the admin pages
            // carry "X-Robots-Tag: noindex" instead (SecurityHeaders).
            ...(config('security.admin_path') === 'admin' ? ['Disallow: /admin'] : []),
            'Disallow: /livewire',
            '',
            'Sitemap: '.Seo::baseUrl().'/sitemap.xml',
        ];

        return $this->text(implode("\n", $lines)."\n");
    }

    public function sitemap(): Response
    {
        $lastModified = collect(array_map(
            fn (string $model) => $model::query()->max('updated_at'),
            [SiteSetting::class, Species::class, PageSection::class, Stat::class, Product::class, ChainStep::class, Feature::class, TimelineItem::class, Leader::class, Certification::class, Post::class, MenuItem::class, HeroSlide::class, TrustLogo::class, Faq::class, Location::class],
        ))->filter()->map(fn ($date) => Carbon::parse($date))->max() ?? now();

        $urls = [];
        $add = function (array $alternates, string $priority, ?Carbon $modified = null) use (&$urls, $lastModified): void {
            foreach ($alternates as $url) {
                $urls[] = ['loc' => $url, 'lastmod' => ($modified ?? $lastModified)->toAtomString(), 'priority' => $priority, 'alternates' => $alternates];
            }
        };

        // Every page in every language, each listing all its language versions. The segment
        // differs per language (/company, /id/perusahaan), so each page builds its own list.
        $add(Locales::alternates('/'), '1.0');

        foreach (['company' => '0.8', 'products' => '0.8', 'news' => '0.7'] as $name => $priority) {
            $add(array_map(fn (string $path): string => Seo::baseUrl().$path, Links::pagePaths($name)), $priority);
        }

        // Published product pages: their own slug in each language.
        foreach (Product::query()->active()->withDetailPage()->get() as $product) {
            $alternates = [];

            foreach (Locales::all() as $locale) {
                $alternates[$locale] = Seo::baseUrl().$product->detailPath($locale);
            }

            $add($alternates, '0.7', $product->updated_at ? Carbon::parse($product->updated_at) : null);
        }

        // Category pages.
        foreach (ProductCategory::query()->active()->has('products')->get() as $category) {
            $alternates = [];

            foreach (Locales::all() as $locale) {
                $alternates[$locale] = Seo::baseUrl().$category->detailPath($locale);
            }

            $add($alternates, '0.7', $category->updated_at ? Carbon::parse($category->updated_at) : null);
        }

        // Species pages (the path differs per language, the slugs do not).
        foreach (Species::query()->active()->with('category')->get() as $species) {
            if ($species->category === null) {
                continue;
            }

            $alternates = [];

            foreach (Locales::all() as $locale) {
                $alternates[$locale] = Seo::baseUrl().$species->detailPath($locale);
            }

            $add($alternates, '0.7', $species->updated_at ? Carbon::parse($species->updated_at) : null);
        }

        // Published news articles: their own slug in each language, their own lastmod.
        foreach (Post::query()->active()->published()->get() as $post) {
            $alternates = [];

            foreach (Locales::all() as $locale) {
                $alternates[$locale] = Seo::baseUrl().$post->detailPath($locale);
            }

            $add($alternates, '0.6', $post->updated_at ? Carbon::parse($post->updated_at) : null);
        }

        $xml = view('seo.sitemap', ['urls' => $urls, 'defaultLocale' => Locales::default()])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Plain-language summary for LLM-based crawlers (https://llmstxt.org).
     */
    public function llms(): Response
    {
        $data = FrontendData::all();

        return $this->text(view('seo.llms', [
            'settings' => $data['settings'],
            'products' => $data['products'],
            'posts' => Post::query()->active()->published()->ordered()->take(20)->get()->map->toFrontend()->values()->all(),
            'baseUrl' => Seo::baseUrl(),
            'alternates' => Locales::alternates(),
        ])->render());
    }

    /**
     * Web app manifest: name, favicon and the brand colour for the browser UI.
     */
    public function manifest(): JsonResponse
    {
        $settings = FrontendData::all()['settings'];
        $favicon = $settings['favicon'];

        return response()->json([
            'name' => $settings['companyName'],
            'short_name' => $settings['companyName'],
            'start_url' => '/',
            'display' => 'browser',
            'theme_color' => $settings['colors']['deep-ocean'],
            'background_color' => $settings['colors']['deep-ocean'],
            'icons' => $favicon ? array_values(array_filter([
                $favicon['sizes'][192] !== $favicon['url'] ? ['src' => $favicon['sizes'][192], 'type' => 'image/png', 'sizes' => '192x192'] : null,
                ['src' => $favicon['url'], 'type' => $favicon['mime'], 'sizes' => $favicon['width'] > 0 ? "{$favicon['width']}x{$favicon['height']}" : 'any'],
            ])) : [],
        ], 200, ['Content-Type' => 'application/manifest+json'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function text(string $content): Response
    {
        return response($content, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
