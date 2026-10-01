{{-- Editorial news list: one large feature story, then the others beside it. --}}
@props(['posts', 'settings'])
@php
    $feature = $posts[0] ?? null;
    $others = array_slice($posts, 1);
@endphp
@if ($feature)
    <div class="news-layout">
        <article class="news-feature">
            @if ($feature['image'])
                <a href="{{ $feature['url'] }}" class="news-media img-reveal" data-dir="l" tabindex="-1" aria-hidden="true">
                    <x-ui.picture :image="$feature['image']" sizes="(max-width: 980px) 100vw, 60vw" alt="" />
                </a>
            @endif
            <div class="reveal">
                @if ($feature['publishedAtDisplay'])
                    <time class="news-date" datetime="{{ $feature['publishedAt'] }}">{{ $feature['publishedAtDisplay'] }}</time>
                @endif
                <h3 class="news-title"><a href="{{ $feature['url'] }}">{{ $feature['title'] }}</a></h3>
                @if ($feature['excerpt'])
                    <p class="body-text">{{ $feature['excerpt'] }}</p>
                @endif
                <a href="{{ $feature['url'] }}" class="link-arrow">{{ $settings['texts']['news_read_more'] }} <span class="arrow" aria-hidden="true">→</span></a>
            </div>
        </article>

        @if ($others)
            <div class="news-side" data-stagger>
                @foreach ($others as $post)
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
    </div>
@endif
