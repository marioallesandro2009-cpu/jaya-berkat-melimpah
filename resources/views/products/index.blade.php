@php
    use App\Support\Links;

    $block = $sections['products'] ?? null;
    $texts = $settings['texts'];
    $forms = $features['product_form'] ?? [];
    $depth = fn (int $i): int => intdiv(20 + 35 * $i + 5, 10) * 10;   // -20, -60, -90, -130 ... m: a visual marker, not a sourcing depth
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    {{-- ==================== HEAD ==================== --}}
    <section class="page-header page-header--plain catch-head">
        <svg class="catch-head-lines" viewBox="0 0 1200 300" preserveAspectRatio="xMidYMax slice" aria-hidden="true" focusable="false">
            <path d="M-20 210 C 220 150, 420 270, 660 205 S 1040 140, 1240 220" />
            <path d="M-20 262 C 200 206, 440 300, 680 255 S 1020 196, 1240 270" />
        </svg>
        <div class="wrap">
            @if ($block['eyebrow'] ?? null)
                <p class="eyebrow on-dark" data-hero-in="1">{{ $block['eyebrow'] }}</p>
            @endif
            <h1 class="on-dark" data-hero-in="2">{{ $block['title'] ?? $texts['product_all'] }}</h1>
            @if ($block['body'] ?? null)
                <p class="lede on-dark" data-hero-in="3">{{ $block['body'] }}</p>
            @endif
            <dl class="catch-meta" data-hero-in="4">
                <div><dt>{{ $texts['products_species'] }}</dt><dd>{{ sprintf('%02d', $speciesCount) }}</dd></div>
                <div><dt>{{ $texts['products_count_label'] }}</dt><dd>{{ sprintf('%02d', $totalProducts) }}</dd></div>
                @if (count($categories) > 0)
                    <div><dt>{{ $texts['products_categories_label'] }}</dt><dd>{{ collect($categories)->pluck('name')->implode(' / ') }}</dd></div>
                @endif
                @if (filled($texts['products_source_value']))
                    <div><dt>{{ $texts['products_source'] }}</dt><dd>{{ $texts['products_source_value'] }}</dd></div>
                @endif
            </dl>
            <span class="catch-surface" aria-hidden="true">0 m</span>
        </div>
    </section>

    {{-- ==================== PRODUCTS ==================== --}}
    <section class="catch" aria-label="{{ $texts['product_all'] }}">
        {{-- Ocean life, purely decorative and kept quiet: contour lines, drifting motes, a few bubbles, fish, seaweed. --}}
        <div class="catch-decor" aria-hidden="true">
            <svg class="catch-contours" viewBox="0 0 1200 1800" preserveAspectRatio="xMidYMin slice" focusable="false">
                <path d="M-20 140 C 200 60, 380 220, 620 150 S 1020 60, 1240 160" />
                <path d="M-20 380 C 180 300, 420 460, 640 380 S 1000 300, 1240 400" />
                <path d="M-20 640 C 220 560, 400 720, 650 640 S 1040 560, 1240 660" />
                <path d="M-20 900 C 200 820, 440 980, 660 900 S 1000 820, 1240 920" />
                <path d="M-20 1160 C 240 1080, 420 1240, 640 1160 S 1020 1080, 1240 1180" />
                <path d="M-20 1420 C 200 1340, 400 1500, 650 1420 S 1040 1340, 1240 1440" />
                <path d="M-20 1680 C 220 1600, 420 1760, 660 1680 S 1020 1600, 1240 1700" />
            </svg>
            <div class="catch-motes"></div>
            <x-ui.fish id="fish-a" class="catch-fish catch-fish--a" />
            <x-ui.fish id="fish-b" class="catch-fish catch-fish--b" />
            @for ($b = 0; $b < 4; $b++)
                <span class="bubble"></span>
            @endfor
        </div>

        <div class="depth-gauge" data-depth-gauge data-depth-max="{{ $depth(max(count($products) - 1, 0)) + 30 }}" aria-hidden="true">
            <div class="depth-gauge-track">
                <span class="depth-gauge-top">0 m</span>
                <span class="depth-gauge-now" data-depth-now>0 m</span>
            </div>
        </div>

        <div class="wrap">
            @if ($categories)
                <nav class="catch-filter" aria-label="{{ $texts['product_categories'] }}">
                    <span class="catch-filter-label">{{ $texts['products_count_label'] }} <b>{{ sprintf('%02d', $totalProducts) }}</b></span>
                    <a href="{{ Links::page('products') }}" class="chip {{ $currentCategory ? '' : 'is-active' }}" @if (! $currentCategory) aria-current="page" @endif>
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
                    <li class="catch-item catch-item--{{ ($i % 4) + 1 }}" id="product-{{ $product['id'] }}">
                        <span class="fish-wrap fish-wrap--{{ ($i % 4) + 1 }}" data-drift-x="{{ $i % 2 === 0 ? 28 : -28 }}" aria-hidden="true"><x-ui.fish id="fish-n{{ $i }}" class="catch-fish-near" /></span>
                        <span class="catch-depth" data-drift="14" aria-hidden="true">−{{ $depth($i) }} m</span>
                        @if ($product['image'])
                            <{{ $tag }} class="catch-media img-reveal" data-dir="{{ $i % 2 === 0 ? 'l' : 'r' }}" data-drift="22" @if ($product['url']) href="{{ $product['url'] }}" tabindex="-1" aria-hidden="true" @endif>
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
                            @if ($product['category'] || $product['specs'] || $product['origin'])
                                <dl class="catch-specs">
                                    @if ($product['category'])
                                        <div><dt>{{ $texts['product_category'] }}</dt><dd>{{ $product['category']['name'] }}</dd></div>
                                    @endif
                                    @if ($product['origin'])
                                        <div><dt>{{ $texts['product_origin'] }}</dt><dd>{{ $product['origin'] }}</dd></div>
                                    @endif
                                    @foreach ($product['specs'] as $row)
                                        <div><dt>{{ $row['label'] }}</dt><dd>{{ $row['value'] }}</dd></div>
                                    @endforeach
                                </dl>
                            @endif
                            <div class="product-links">
                                @if ($product['url'])
                                    <a href="{{ $product['url'] }}" class="link-arrow">{{ $texts['product_learn_more'] }} <span class="arrow" aria-hidden="true">→</span></a>
                                    <a href="{{ Links::contactFor($product['value']) }}" class="link-quiet" data-product="{{ $product['value'] }}">{{ $texts['hero_secondary_cta'] }}</a>
                                @else
                                    <a href="{{ Links::contactFor($product['value']) }}" class="link-arrow" data-product="{{ $product['value'] }}">{{ $texts['hero_secondary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
                                @endif
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

    {{-- ==================== WHAT WE SUPPLY: the species, then the product forms the admin lists (Fitur > Bentuk produk) ==================== --}}
    @if ($allProducts)
        <section class="pforms">
            <div class="wrap">
                <h2 class="pforms-title reveal">{{ $texts['products_forms_title'] }}</h2>
                <ul class="pforms-list" data-stagger>
                    @foreach ($allProducts as $item)
                        <li>
                            @if ($item['url'])
                                <a href="{{ $item['url'] }}" class="pforms-name">{{ $item['name'] }}</a>
                            @else
                                <span class="pforms-name">{{ $item['name'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
                @if ($forms)
                    <p class="pforms-label reveal">{{ $texts['products_forms_note'] }}</p>
                    <ul class="pforms-list pforms-list--forms" data-stagger>
                        @foreach ($forms as $form)
                            <li><span class="pforms-name">{{ $form['title'] }}</span>@if ($form['body'])<span class="pforms-note">{{ $form['body'] }}</span>@endif</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    @endif

    {{-- ==================== SOURCE TO EXPORT (the same steps as the home page journey) ==================== --}}
    @if ($chain)
        <section class="pjourney">
            <div class="wrap">
                <h2 class="pjourney-title reveal">{{ $texts['products_journey_title'] }}</h2>
                <ol class="pjourney-steps" data-stagger>
                    @foreach ($chain as $n => $step)
                        <li>
                            <span class="pjourney-num">{{ sprintf('%02d', $n + 1) }}</span>
                            <h3>{{ $step['title'] }}</h3>
                            @if ($step['body'])
                                <p>{{ $step['body'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif

    {{-- ==================== BRIDGE TO SUSTAINABILITY ==================== --}}
    @if ($sections['sustainability'] ?? null)
        <section class="pbridge">
            <div class="wrap pbridge-inner">
                <div class="reveal">
                    <h2>{{ $texts['products_bridge_title'] }}</h2>
                    <p>{{ $texts['products_bridge_body'] }}</p>
                </div>
                <a href="{{ Links::section('sustainability') }}" class="link-arrow reveal">{{ $texts['products_bridge_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
            </div>
        </section>
    @endif

    {{-- ==================== FINAL CALL TO ACTION ==================== --}}
    <section class="pcta">
        <svg class="pcta-horizon" viewBox="0 0 1200 200" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path d="M0 120 C 200 80, 420 150, 640 110 S 1000 70, 1200 120" />
            <path d="M0 150 C 220 112, 440 180, 660 142 S 1010 104, 1200 150" />
            <path d="M0 180 C 240 146, 460 206, 680 172 S 1020 138, 1200 180" />
        </svg>
        <div class="wrap pcta-inner">
            <h2 class="reveal">{{ $texts['products_cta_title'] }}</h2>
            <p class="reveal">{{ $texts['products_cta_body'] }}</p>
            <a href="{{ Links::section('contact') }}" class="btn btn--primary reveal">{{ $settings['ctaLabel'] ?: $texts['hero_secondary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
        </div>
    </section>
</x-layouts::app>
