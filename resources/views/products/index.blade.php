@php
    $block = $sections['products'] ?? null;
    $texts = $settings['texts'];
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    <section class="page-header page-header--plain catch-head">
        <div class="wrap">
            @if ($block['eyebrow'] ?? null)
                <p class="eyebrow on-dark" data-hero-in="1">{{ $block['eyebrow'] }}</p>
            @endif
            <h1 class="on-dark" data-hero-in="2">{{ $block['title'] ?? $texts['product_all'] }}</h1>
            @if ($block['body'] ?? null)
                <p class="lede on-dark" data-hero-in="3">{{ $block['body'] }}</p>
            @endif
        </div>
    </section>

    <section class="catch" aria-label="{{ $texts['product_all'] }}">
        {{-- Sea life, purely decorative: rising bubbles, two swimming fish, contour lines, seaweed. --}}
        <div class="catch-decor" aria-hidden="true">
            <svg class="catch-contours" viewBox="0 0 1200 1600" preserveAspectRatio="xMidYMin slice" focusable="false">
                <path d="M-20 140 C 200 60, 380 220, 620 150 S 1020 60, 1240 160" />
                <path d="M-20 380 C 180 300, 420 460, 640 380 S 1000 300, 1240 400" />
                <path d="M-20 640 C 220 560, 400 720, 650 640 S 1040 560, 1240 660" />
                <path d="M-20 900 C 200 820, 440 980, 660 900 S 1000 820, 1240 920" />
                <path d="M-20 1160 C 240 1080, 420 1240, 640 1160 S 1020 1080, 1240 1180" />
                <path d="M-20 1420 C 200 1340, 400 1500, 650 1420 S 1040 1340, 1240 1440" />
            </svg>
            <svg class="catch-fish catch-fish--a" viewBox="0 0 70 40" focusable="false"><path d="M0 20 C12 3 36 3 52 20 C36 37 12 37 0 20Z M50 20 L69 6 L69 34Z" /><circle cx="14" cy="17" r="1.6" class="eye" /></svg>
            <svg class="catch-fish catch-fish--b" viewBox="0 0 70 40" focusable="false"><path d="M0 20 C12 3 36 3 52 20 C36 37 12 37 0 20Z M50 20 L69 6 L69 34Z" /><circle cx="14" cy="17" r="1.6" class="eye" /></svg>
            @for ($b = 0; $b < 12; $b++)
                <span class="bubble"></span>
            @endfor
        </div>

        <div class="wrap">
            @if ($categories)
                <nav class="catch-filter" aria-label="{{ $texts['product_categories'] }}">
                    <a href="{{ \App\Support\Links::page('products') }}" class="chip {{ $currentCategory ? '' : 'is-active' }}" @if (! $currentCategory) aria-current="page" @endif>
                        {{ $texts['product_all'] }} <span class="chip-count">{{ $totalProducts }}</span>
                    </a>
                    @foreach ($categories as $category)
                        <a href="{{ $category['url'] }}" class="chip {{ ($currentCategory['id'] ?? null) === $category['id'] ? 'is-active' : '' }}" style="--cat: {{ $category['color'] }}" @if (($currentCategory['id'] ?? null) === $category['id']) aria-current="page" @endif>
                            <span class="chip-dot" aria-hidden="true"></span>{{ $category['name'] }} <span class="chip-count">{{ $category['count'] }}</span>
                        </a>
                    @endforeach
                </nav>
                @if ($currentCategory && $currentCategory['description'])
                    <p class="catch-filter-note">{{ $currentCategory['description'] }}</p>
                @endif
            @endif

            <ol class="shoal">
                @foreach ($products as $i => $product)
                    @php($tag = $product['url'] ? 'a' : 'div')
                    <li class="catch-item catch-item--{{ ($i % 4) + 1 }}">
                        <span class="catch-depth" aria-hidden="true">−{{ ($i + 1) * 20 }} m</span>
                        @if ($product['image'])
                            <{{ $tag }} class="catch-media img-reveal" data-dir="{{ $i % 2 === 0 ? 'l' : 'r' }}" @if ($product['url']) href="{{ $product['url'] }}" tabindex="-1" aria-hidden="true" @endif>
                                <x-ui.picture :image="$product['image']" sizes="(max-width: 980px) 86vw, 40vw" alt="" />
                            </{{ $tag }}>
                        @endif
                        <div class="catch-body reveal">
                            <span class="catch-num">{{ sprintf('%02d', $i + 1) }}@if ($product['category']) <span class="catch-cat" style="--cat: {{ $product['category']['color'] }}"><span class="chip-dot" aria-hidden="true"></span>{{ $product['category']['name'] }}</span>@endif</span>
                            <h2 class="catch-name">
                                @if ($product['url'])
                                    <a href="{{ $product['url'] }}">{{ $product['name'] }}</a>
                                @else
                                    {{ $product['name'] }}
                                @endif
                            </h2>
                            @if ($product['description'])
                                <p class="catch-desc">{{ $product['description'] }}</p>
                            @endif
                            <div class="product-links">
                                @if ($product['url'])
                                    <a href="{{ $product['url'] }}" class="link-arrow">{{ $texts['product_learn_more'] }} <span class="arrow" aria-hidden="true">→</span></a>
                                @endif
                                <a href="{{ \App\Support\Links::contactFor($product['value']) }}" class="link-arrow" data-product="{{ $product['value'] }}">{{ $texts['hero_secondary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <svg class="catch-weed" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path class="w1" d="M60 120 C 40 90, 80 60, 56 20 M100 120 C 120 80, 84 50, 108 0 M150 120 C 130 96, 160 70, 144 36" />
            <path class="w2" d="M520 120 C 500 84, 540 56, 520 14 M560 120 C 580 90, 548 60, 566 26" />
            <path class="w1" d="M1020 120 C 1000 88, 1040 58, 1018 16 M1062 120 C 1082 84, 1050 54, 1072 4 M1110 120 C 1092 94, 1124 70, 1108 40" />
        </svg>
    </section>
</x-layouts::app>
