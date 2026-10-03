@php
    use App\Support\Links;

    $texts = $settings['texts'];
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    {{-- ==================== HEAD ==================== --}}
    <section class="page-header page-header--plain catch-head cat-head" style="--cat: {{ $category['color'] }}">
        <div class="wrap cat-head-grid">
            <div>
                <p class="eyebrow on-dark" data-hero-in="1">
                    <a href="{{ Links::page('products') }}" class="eyebrow-link">← {{ $texts['product_back'] }}</a>
                </p>
                <h1 class="on-dark" data-hero-in="2"><span class="cat-dot" aria-hidden="true"></span>{{ $category['name'] }}</h1>
                @if ($category['description'])
                    <p class="lede on-dark" data-hero-in="3">{{ $category['description'] }}</p>
                @endif
                <dl class="catch-meta" data-hero-in="4">
                    <div><dt>{{ $texts['products_species'] }}</dt><dd>{{ sprintf('%02d', count($species)) }}</dd></div>
                    <div><dt>{{ $texts['products_count_label'] }}</dt><dd>{{ sprintf('%02d', count($products)) }}</dd></div>
                </dl>
            </div>
            @if ($image)
                <figure class="cat-photo img-reveal" data-dir="r">
                    <x-ui.picture :image="$image" sizes="(max-width: 980px) 80vw, 36vw" :fetch="true" />
                </figure>
            @endif
        </div>
    </section>

    {{-- ==================== SPECIES ==================== --}}
    @if ($species)
        <section class="cat-species">
            <div class="wrap">
                <h2 class="species-cuts-title reveal">{{ $texts['cat_species'] }}</h2>
                <ul class="spec-grid" data-stagger>
                    @foreach ($species as $i => $row)
                        <li class="spec-card spec-card--{{ ($i % 3) + 1 }}">
                            <a href="{{ $row['url'] }}">
                                <span class="spec-photo">@if ($row['image'])<x-ui.picture :image="$row['image']" sizes="(max-width: 720px) 80vw, 24vw" alt="" />@endif</span>
                                <span class="spec-name">{{ $row['name'] }}</span>
                                @if ($row['scientific'])<em class="spec-sci">{{ $row['scientific'] }}</em>@endif
                                <span class="spec-count">{{ $row['count'] }} {{ $texts['catalog_count'] }} <span aria-hidden="true">→</span></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ==================== PRODUCTS (the compact rows) ==================== --}}
    <section class="catalogue">
        <div class="wrap">
            <div class="catalogue-head reveal">
                <h2>{{ $texts['products_count_label'] }}</h2>
                <p class="catalogue-count">{{ count($products) }} {{ $texts['catalog_count'] }}</p>
            </div>
            @if ($products)
                <ul class="plist" data-stagger>
                    @foreach ($products as $product)
                        <x-catalog.row :product="$product" :texts="$texts" />
                    @endforeach
                </ul>
            @else
                <p class="catalogue-empty">{{ $texts['catalog_no_results'] }}</p>
            @endif
        </div>
    </section>

    {{-- ==================== OTHER CATEGORIES ==================== --}}
    @if ($others)
        <section class="species-others">
            <div class="wrap">
                <h2 class="species-cuts-title reveal">{{ $texts['cat_other'] }}</h2>
                <ul class="species-others-list" data-stagger>
                    @foreach ($others as $other)
                        <li><a href="{{ $other['pageUrl'] }}"><span class="so-name">{{ $other['name'] }}</span><span class="so-arrow" aria-hidden="true">→</span></a></li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ==================== CTA ==================== --}}
    <section class="section section--secondary closing">
        <div class="wrap">
            <div class="cta-block reveal">
                <h2 class="on-dark">{{ $category['name'] }}</h2>
                <a href="{{ Links::contactFor($category['name']) }}" class="btn btn--primary">{{ $texts['hero_secondary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>
</x-layouts::app>
