{{--
    Public layout. Everything crawlers need (meta, JSON-LD, content) is in the HTML;
    JavaScript only adds animation and interaction.
--}}
@props(['seo', 'settings', 'jsonLd' => null])
@php
    use App\Support\MediaPresenter;
    use Illuminate\Support\Facades\Vite;

    $preload = $seo['preload'];
    $nonce = Vite::cspNonce();

    // The minified copy (npm run build) while it is at least as new as the source, else the source.
    // The ?v= (file time) gives a changed file a new URL, so it can be cached for a year.
    $asset = function (string $path): string {
        $min = preg_replace('/\.(css|js)$/', '.min.$1', $path);
        $file = is_file(public_path($min)) && filemtime(public_path($min)) >= filemtime(public_path($path)) ? $min : $path;

        return asset($file).'?v='.filemtime(public_path($file));
    };
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="{{ $seo['themeColor'] }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="manifest" href="/site.webmanifest">

        <title>{{ $seo['title'] }}</title>
        <meta name="description" content="{{ $seo['description'] }}">
        <meta name="robots" content="index, follow, max-image-preview:large">
        <link rel="canonical" href="{{ $seo['canonical'] }}">
        @foreach ($seo['alternates'] as $hreflang => $url)
            <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $url }}">
        @endforeach
        <link rel="alternate" hreflang="x-default" href="{{ $seo['alternates'][\App\Support\Locales::default()] }}">

        <meta property="og:type" content="{{ $seo['type'] }}">
        <meta property="og:locale" content="{{ $seo['ogLocale'] }}">
        @foreach ($seo['ogAlternates'] as $ogLocale)
            <meta property="og:locale:alternate" content="{{ $ogLocale }}">
        @endforeach
        <meta property="og:site_name" content="{{ $seo['siteName'] }}">
        <meta property="og:title" content="{{ $seo['title'] }}">
        <meta property="og:description" content="{{ $seo['description'] }}">
        <meta property="og:url" content="{{ $seo['canonical'] }}">
        @if ($seo['image'])
            <meta property="og:image" content="{{ $seo['image']['url'] }}">
            <meta property="og:image:width" content="{{ $seo['image']['width'] }}">
            <meta property="og:image:height" content="{{ $seo['image']['height'] }}">
            <meta property="og:image:alt" content="{{ $seo['imageAlt'] }}">
        @endif
        <meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $seo['title'] }}">
        <meta name="twitter:description" content="{{ $seo['description'] }}">
        @if ($seo['image'])
            <meta name="twitter:image" content="{{ $seo['image']['url'] }}">
        @endif

        @if ($favicon = $settings['favicon'])
            @if ($favicon['sizes'][32] !== $favicon['url'])
                <link rel="icon" href="{{ $favicon['sizes'][32] }}" type="image/png" sizes="32x32">
            @endif
            <link rel="icon" href="{{ $favicon['url'] }}" type="{{ $favicon['mime'] }}">
            <link rel="apple-touch-icon" href="{{ $favicon['sizes'][180] }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
        @endif

        {{-- Marks the page as JS-enabled; the hidden "before animation" states only apply with this. --}}
        <script @if ($nonce) nonce="{{ $nonce }}" @endif>document.documentElement.classList.add('js');</script>

        @if ($preload)
            {{-- Same srcset/sizes as the <img>, so the browser reuses this request. --}}
            <link rel="preload" as="image" href="{{ $preload['large']['url'] }}" imagesrcset="{{ MediaPresenter::srcset($preload) }}" imagesizes="100vw" fetchpriority="high">
        @endif
        <link rel="preload" as="font" type="font/woff2" href="/fonts/schibsted-grotesk-latin-500-normal.woff2" crossorigin>

        <link rel="stylesheet" href="{{ $asset('css/style.css') }}">

        {{-- Brand colours changed in the admin (Pengaturan Situs › Warna); built from validated hex values only. --}}
        @if ($settings['cssVariables'])
            <style>:root{ {!! $settings['cssVariables'] !!} }</style>
        @endif

        @if ($jsonLd)
            <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
        @endif
    </head>
    <body>
        <a class="skip-link" href="#main">{{ $settings['texts']['skip_link'] }}</a>

        <x-layout.navbar :settings="$settings" />

        <main id="main">
            {{ $slot }}
        </main>

        <x-layout.footer :settings="$settings" />

        {{-- Inquiry list (products picked on the catalogue), shown by main.js only while the list is not empty. --}}
        <div class="inquiry-bar" data-inquiry-bar data-remove="{{ $settings['texts']['inquiry_remove'] }}" hidden>
            <div class="inquiry-panel" id="inquiry-panel" data-inquiry-panel hidden>
                <div class="inquiry-panel-head"><strong>{{ $settings['texts']['inquiry_list'] }}</strong><button type="button" data-inquiry-clear>{{ $settings['texts']['inquiry_clear'] }}</button></div>
                <ul class="inquiry-panel-list" data-inquiry-listbox></ul>
                <a class="btn btn--primary" href="{{ \App\Support\Links::section('contact') }}" data-no-transition>{{ $settings['texts']['hero_secondary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
            </div>
            <button type="button" class="inquiry-toggle" data-inquiry-toggle aria-expanded="false" aria-controls="inquiry-panel"><span class="inquiry-count" data-inquiry-count>0</span> {{ $settings['texts']['inquiry_list'] }}</button>
        </div>

        <script src="{{ $asset('js/main.js') }}" @if ($nonce) nonce="{{ $nonce }}" @endif defer></script>
    </body>
</html>
