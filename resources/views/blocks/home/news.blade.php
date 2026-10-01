@php
    use App\Support\Links;

    $block = $sections['news'] ?? null;
    $texts = $settings['texts'];
@endphp
@if ($recentPosts)
    <section id="news" class="section section--light news-teaser">
        <div class="wrap">
            <div class="news-head">
                <div>
                    @if ($block && $block['eyebrow'])
                        <p class="eyebrow reveal">{{ $block['eyebrow'] }}</p>
                    @endif
                    <h2 class="reveal">{{ $block['title'] ?? $texts['news_latest'] }}</h2>
                </div>
                <a href="{{ Links::page('news') }}" class="link-arrow reveal">{{ $block['cta'] ?? __('All news') }} <span class="arrow" aria-hidden="true">→</span></a>
            </div>
            <x-sections.news-list :posts="$recentPosts" :settings="$settings" />
        </div>
    </section>
@endif
