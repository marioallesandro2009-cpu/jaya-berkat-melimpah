@php
    use App\Support\Links;

    $texts = $settings['texts'];
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    {{-- ==================== HEAD ==================== --}}
    <section class="page-header page-header--plain catch-head species-head">
        <div class="wrap">
            <p class="eyebrow on-dark" data-hero-in="1">
                <a href="{{ Links::page('products') }}" class="eyebrow-link">← {{ $texts['product_back'] }}</a>
                <span class="crumb-sep" aria-hidden="true">/</span>
                <a href="{{ $category['url'] }}" class="eyebrow-link">{{ $category['name'] }}</a>
            </p>
            <h1 class="on-dark" data-hero-in="2">{{ $species['name'] }}</h1>
            @if ($species['scientific'] || $species['japanese'])
                <p class="species-names" data-hero-in="3">
                    @if ($species['scientific'])<em>{{ $species['scientific'] }}</em>@endif
                    @if ($species['scientific'] && $species['japanese'])<span aria-hidden="true">·</span>@endif
                    @if ($species['japanese'])<span>{{ $species['japanese'] }}</span>@endif
                </p>
            @endif
            @if ($species['description'])
                <p class="lede on-dark" data-hero-in="4">{{ $species['description'] }}</p>
            @endif
        </div>
    </section>

    {{-- ==================== PHOTO + FACTS ==================== --}}
    <section class="species-body">
        <div class="wrap species-grid">
            @if ($species['image'])
                <figure class="species-photo img-reveal" data-dir="l">
                    <x-ui.picture :image="$species['image']" sizes="(max-width: 980px) 90vw, 42vw" :fetch="true" />
                </figure>
            @endif
            <dl class="species-facts reveal">
                @if ($species['scientific'])
                    <div><dt>{{ $texts['species_scientific'] }}</dt><dd><em>{{ $species['scientific'] }}</em></dd></div>
                @endif
                @if ($species['japanese'])
                    <div><dt>{{ $texts['species_japanese'] }}</dt><dd>{{ $species['japanese'] }}</dd></div>
                @endif
                <div><dt>{{ $texts['product_category'] }}</dt><dd><a href="{{ $category['url'] }}">{{ $category['name'] }}</a></dd></div>
                @if ($species['origin'])
                    <div><dt>{{ $texts['species_origin'] }}</dt><dd>{{ $species['origin'] }}</dd></div>
                @endif
                @if ($species['habitat'])
                    <div><dt>{{ $texts['species_habitat'] }}</dt><dd>{{ $species['habitat'] }}</dd></div>
                @endif
                <div><dt>{{ $texts['species_sashimi'] }}</dt><dd>{{ $species['sashimi'] ? $texts['species_yes'] : '-' }}</dd></div>
            </dl>
        </div>
    </section>

    {{-- ==================== CUTS ==================== --}}
    @if (collect($cuts)->contains('sold', true))
        <section class="species-cuts">
            <div class="wrap">
                <h2 class="species-cuts-title reveal">{{ $texts['species_cuts'] }}</h2>
                <ul class="species-cuts-list" data-stagger>
                    @foreach ($cuts as $cut)
                        @if ($cut['sold'])
                            <li><a href="{{ $cut['url'] }}">{{ $cut['name'] }} <span aria-hidden="true">→</span></a></li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ==================== PRODUCTS ==================== --}}
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

    {{-- ==================== OTHER SPECIES OF THE CATEGORY ==================== --}}
    @if ($siblings)
        <section class="species-others">
            <div class="wrap">
                <h2 class="species-cuts-title reveal">{{ $texts['species_others'] }}</h2>
                <ul class="species-others-list" data-stagger>
                    @foreach ($siblings as $other)
                        <li>
                            <a href="{{ $other['url'] }}">
                                <span class="so-name">{{ $other['name'] }}</span>
                                @if ($other['scientific'])<em>{{ $other['scientific'] }}</em>@endif
                                <span class="so-arrow" aria-hidden="true">→</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ==================== CTA ==================== --}}
    <section class="section section--secondary closing">
        <div class="wrap">
            <div class="cta-block reveal">
                <h2 class="on-dark">{{ $species['name'] }}</h2>
                <a href="{{ Links::contactFor($species['name']) }}" class="btn btn--primary">{{ $texts['hero_secondary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>
</x-layouts::app>
