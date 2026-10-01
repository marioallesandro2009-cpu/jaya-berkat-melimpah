@php
    $section = $sections['news'] ?? null;
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    <section class="page-header page-header--plain">
        <div class="wrap">
            <p class="eyebrow on-dark" data-hero-in="1">{{ $section['eyebrow'] ?? $settings['texts']['news_latest'] }}</p>
            <h1 class="on-dark" data-hero-in="2">{{ $section['title'] ?? $settings['texts']['news_latest'] }}</h1>
            @if ($section['body'] ?? null)
                <p class="lede on-dark" data-hero-in="3">{{ $section['body'] }}</p>
            @endif
        </div>
    </section>

    <section class="section section--white news-page">
        <div class="wrap">
            @if ($posts)
                <x-sections.news-list :posts="array_slice($posts, 0, 7)" :settings="$settings" />

                @if (count($posts) > 7)
                    <div class="news-archive" data-stagger>
                        @foreach (array_slice($posts, 7) as $post)
                            <article class="news-item">
                                @if ($post['publishedAtDisplay'])
                                    <time class="news-date" datetime="{{ $post['publishedAt'] }}">{{ $post['publishedAtDisplay'] }}</time>
                                @endif
                                <h3 class="news-title news-title--sm"><a href="{{ $post['url'] }}">{{ $post['title'] }}</a></h3>
                                @if ($post['excerpt'])
                                    <p class="body-text">{{ $post['excerpt'] }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            @else
                <p class="body-text">{{ $settings['texts']['news_empty'] }}</p>
            @endif
        </div>
    </section>
</x-layouts::app>
