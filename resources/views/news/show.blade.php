@php
    use App\Support\Links;
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    <article>
        <header class="page-header page-header--plain">
            <div class="wrap">
                <p class="eyebrow on-dark" data-hero-in="1">
                    <a href="{{ Links::page('news') }}" class="eyebrow-link">← {{ $settings['texts']['news_back'] }}</a>
                </p>
                <h1 class="on-dark article-title" data-hero-in="2">{{ $page['title'] }}</h1>
                @if ($page['publishedAtDisplay'])
                    <p class="news-date on-dark" data-hero-in="3"><time datetime="{{ $page['publishedAt'] }}">{{ $page['publishedAtDisplay'] }}</time></p>
                @endif
            </div>
        </header>

        <div class="section section--white article">
            <div class="wrap article-wrap">
                @if ($page['image'])
                    <figure class="article-cover img-reveal" data-dir="l">
                        <x-ui.picture :image="$page['image']" sizes="(max-width: 980px) 100vw, 1100px" :fetch="true" />
                    </figure>
                @endif
                @if ($page['excerpt'])
                    <p class="lede article-lede">{{ $page['excerpt'] }}</p>
                @endif
                <div class="prose">{!! $page['content'] !!}</div>
            </div>
        </div>
    </article>

    @if ($more)
        <section class="section section--light news-more">
            <div class="wrap">
                <h2 class="reveal">{{ $settings['texts']['news_latest'] }}</h2>
                <x-sections.news-list :posts="$more" :settings="$settings" />
            </div>
        </section>
    @endif
</x-layouts::app>
