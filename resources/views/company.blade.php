@php
    use App\Support\Links;

    $header = $sections['company_header'] ?? null;
    $closing = $sections['company_closing'] ?? null;
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">

    {{-- ==================== PAGE HEADER (always first) ==================== --}}
    @if ($header)
        <section class="page-header">
            @if ($header['image'])
                <div class="page-header-media">
                    <x-ui.picture :image="$header['image']" sizes="100vw" alt="" :fetch="true" />
                </div>
            @endif
            <div class="wrap">
                @if ($header['eyebrow'])
                    <p class="eyebrow on-dark" data-hero-in="1">{{ $header['eyebrow'] }}</p>
                @endif
                <h1 class="on-dark" data-hero-in="2">{{ $header['title'] }}</h1>
                @if ($header['body'])
                    <p class="lede on-dark" data-hero-in="3">{{ $header['body'] }}</p>
                @endif
            </div>
        </section>
    @endif

    {{-- ==================== BLOCKS, in the order set in the admin (Blok Halaman) ==================== --}}
    @foreach ($blocks['company'] as $key)
        @if ($sections[$key]['custom'] ?? false)
            @include('blocks.custom', ['block' => $sections[$key]])
        @else
            @include('blocks.company.'.substr($key, strlen('company_')))
        @endif
    @endforeach

    {{-- ==================== CLOSING (always last) ==================== --}}
    @if ($closing)
        <section class="section section--secondary closing">
            <div class="wrap">
                <div class="cta-block reveal">
                    <h2 class="on-dark">{{ $closing['title'] }}</h2>
                    @if ($closing['cta'])
                        <a href="{{ $closing['ctaUrl'] ?: Links::section('contact') }}" class="btn btn--primary">{{ $closing['cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
                    @endif
                </div>
            </div>
        </section>
    @endif

</x-layouts::app>
