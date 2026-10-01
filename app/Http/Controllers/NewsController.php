<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\FrontendData;
use App\Support\Links;
use App\Support\Locales;
use App\Support\Seo;
use App\Support\StructuredData;
use Illuminate\Contracts\View\View;

/**
 * News: the index (/news, /id/berita) and one article (/news/{slug}, /id/berita/{slug}).
 */
class NewsController extends Controller
{
    public function index(): View
    {
        $data = FrontendData::all();
        $section = $data['sections']['news'] ?? null;
        $posts = array_values(Post::query()->active()->published()->ordered()->with('media')->get()->map->toFrontend()->all());
        $seo = Seo::page($data, 'news', (string) ($section['title'] ?? __('News')), $section['body'] ?? null);

        Locales::setPagePaths(Links::pagePaths('news'));

        return view('news.index', [
            ...$data,
            'posts' => $posts,
            'seo' => $seo,
            'jsonLd' => StructuredData::page($data, $seo),
        ]);
    }

    public function show(string $slug): View
    {
        $post = Post::query()->active()->published()->with('media')->get()
            ->first(fn (Post $post): bool => in_array($slug, array_filter([...(array) $post->slugs, $post->slug]), true));

        abort_if($post === null, 404);

        $settings = FrontendData::settings();
        $paths = [];

        foreach (Locales::all() as $locale) {
            $paths[$locale] = $post->detailPath($locale);
        }

        Locales::setPagePaths($paths);

        $page = FrontendData::remember('post.'.$post->id.'.'.Locales::current(), fn (): array => $post->toDetail());
        $seo = Seo::newsPost($page, $settings, $paths);

        $more = array_values(array_filter(
            FrontendData::all()['recentPosts'],
            fn (array $recent): bool => $recent['id'] !== $post->id,
        ));

        return view('news.show', [
            'settings' => $settings,
            'page' => $page,
            'more' => $more,
            'seo' => $seo,
            'jsonLd' => StructuredData::newsPost($page, $settings, $seo),
        ]);
    }
}
