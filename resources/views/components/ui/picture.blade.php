{{--
    <img> for an image payload from App\Support\MediaPresenter::image():
    srcset with the small and large size, intrinsic width/height (no layout shift).
--}}
@props(['image', 'sizes' => '100vw', 'lazy' => true, 'alt' => null, 'fetch' => false])
@php
    use App\Support\MediaPresenter;
@endphp
@if ($image)
    <img src="{{ $image['large']['url'] }}"
         srcset="{{ MediaPresenter::srcset($image) }}"
         sizes="{{ $sizes }}"
         width="{{ $image['large']['width'] ?: '' }}"
         height="{{ $image['large']['height'] ?: '' }}"
         alt="{{ $alt ?? $image['alt'] }}"
         @if ($fetch) fetchpriority="high" @elseif ($lazy) loading="lazy" decoding="async" @endif
         {{ $attributes }}>
@endif
