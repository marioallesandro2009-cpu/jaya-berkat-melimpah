@php
    use App\Support\Links;

    $texts = $settings['texts'];
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">

    {{-- ==================== PRODUCT HERO ==================== --}}
    <section class="page-header product-hero">
        @if ($page['image'])
            <div class="page-header-media">
                <x-ui.picture :image="$page['image']" sizes="100vw" alt="" :fetch="true" />
            </div>
        @endif
        <div class="wrap">
            <p class="eyebrow on-dark" data-hero-in="1">
                <a href="{{ Links::page('products') }}" class="eyebrow-link">← {{ $texts['product_back'] }}</a>
            </p>
            <h1 class="on-dark" data-hero-in="2">{{ $page['name'] }}</h1>
            @if ($page['intro'])
                <p class="lede on-dark" data-hero-in="3">{{ $page['intro'] }}</p>
            @endif
            <div class="hero-cta" data-hero-in="4">
                <a href="{{ Links::contactFor($page['value']) }}" class="btn btn--primary" data-product="{{ $page['value'] }}">{{ $texts['hero_secondary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>

    {{-- ==================== SPECIFICATIONS + STORY ==================== --}}
    @if ($page['specs'] || $page['content'])
        <section class="section section--white product-detail">
            <div class="wrap product-detail-grid">
                @if ($page['specs'])
                    <aside class="specs reveal">
                        <h2 class="specs-title">{{ $texts['product_specs'] }}</h2>
                        <dl>
                            @foreach ($page['specs'] as $row)
                                <div class="spec-row">
                                    <dt>{{ $row['label'] }}</dt>
                                    <dd>{{ $row['value'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </aside>
                @endif
                @if ($page['content'])
                    <div class="prose reveal">{!! $page['content'] !!}</div>
                @endif
            </div>
        </section>
    @endif

    {{-- ==================== GALLERY ==================== --}}
    @if ($page['gallery'])
        <section class="section section--light gallery">
            <div class="wrap">
                <h2 class="reveal">{{ $texts['product_gallery'] }}</h2>
                <div class="gallery-grid">
                    @foreach ($page['gallery'] as $i => $photo)
                        <figure class="img-reveal" data-dir="{{ $i % 2 === 0 ? 'l' : 'r' }}">
                            <x-ui.picture :image="$photo" sizes="(max-width: 720px) 100vw, 50vw" />
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ==================== OTHER PRODUCTS ==================== --}}
    @if ($others)
        <section class="section section--white other-products">
            <div class="wrap">
                <h2 class="reveal">{{ $texts['product_other'] }}</h2>
                <ul class="other-list" data-stagger>
                    @foreach ($others as $other)
                        <li>
                            <a href="{{ $other['url'] ?: Links::section('products') }}">
                                <span>{{ $other['name'] }}</span>
                                <span class="arrow" aria-hidden="true">→</span>
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
                <h2 class="on-dark">{{ $page['name'] }}</h2>
                <a href="{{ Links::contactFor($page['value']) }}" class="btn btn--primary" data-product="{{ $page['value'] }}">{{ $texts['hero_secondary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
            </div>
        </div>
    </section>
</x-layouts::app>
