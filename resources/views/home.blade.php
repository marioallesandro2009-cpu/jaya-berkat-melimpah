@php
    $hero = $sections['hero'] ?? null;
    $texts = $settings['texts'];
    // Hero photos: the slideshow when there are slides, else the photo of the hero block.
    $heroImages = $heroSlides ? array_column($heroSlides, 'image') : array_filter([$hero['image'] ?? null]);
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">

    {{-- ==================== HERO (always first) ==================== --}}
    @if ($hero)
        <section class="hero">
            <div class="hero-media" @if (count($heroImages) > 1) data-slides="{{ $settings['heroSlideDuration'] }}" @endif>
                @foreach ($heroImages as $i => $image)
                    <div class="hero-slide {{ $i === 0 ? 'is-active' : '' }}">
                        <x-ui.picture :image="$image" sizes="100vw" :fetch="$i === 0" :lazy="$i > 0" />
                    </div>
                @endforeach
            </div>
            <div class="hero-scrim" aria-hidden="true"></div>
            <div class="hero-light" aria-hidden="true"></div>
            <div class="hero-content wrap">
                @if ($hero['eyebrow'])
                    <p class="eyebrow on-dark" data-hero-in="1">{{ $hero['eyebrow'] }}</p>
                @endif
                <h1 class="hero-title" data-hero-in="2">{{ $hero['title'] }}</h1>
                @if ($hero['body'])
                    <p class="hero-sub" data-hero-in="3">{{ $hero['body'] }}</p>
                @endif
                <div class="hero-cta" data-hero-in="4">
                    <a href="{{ $blocks['home'] ? '#'.\App\Models\PageSection::anchorFor($blocks['home'][0]) : '#contact' }}" class="btn btn--primary">{{ $texts['hero_primary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
                    <a href="#contact" class="link-arrow">{{ $texts['hero_secondary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
                </div>
            </div>
            <div class="hero-scroll" aria-hidden="true">{{ $texts['scroll'] }}</div>
        </section>
    @endif

    {{-- ==================== BLOCKS, in the order set in the admin (Blok Halaman) ==================== --}}
    @foreach ($blocks['home'] as $key)
        @if ($sections[$key]['custom'] ?? false)
            @include('blocks.custom', ['block' => $sections[$key]])
        @else
            @include('blocks.home.'.$key)
        @endif
    @endforeach

    {{-- ==================== CLOSING / CONTACT (always last) ==================== --}}
    <x-sections.contact :section="$sections['contact'] ?? null" :products="$products" :settings="$settings" />

</x-layouts::app>
