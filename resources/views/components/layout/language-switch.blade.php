@props(['settings'])
@php
    use App\Support\Locales;

    $current = Locales::current();
@endphp
<div class="lang-switch" role="group" aria-label="{{ $settings['texts']['language'] }}">
    @foreach (Locales::switchOrder() as $locale)
        @if ($locale === $current)
            <span aria-current="true" lang="{{ $locale }}">{{ strtoupper($locale) }}</span>
        @else
            <a href="{{ Locales::pathFor(request(), $locale) }}" hreflang="{{ $locale }}" lang="{{ $locale }}" title="{{ Locales::nativeName($locale) }}" data-no-transition>{{ strtoupper($locale) }}</a>
        @endif
    @endforeach
</div>
