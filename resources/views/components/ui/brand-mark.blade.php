{{-- The brand's logo as a quiet set piece for an empty corner of a section: the uploaded logo (Pengaturan Situs > Logo),
     or the fish drawing when none is uploaded, floating gently inside two slow ripples. Decorative (aria-hidden); the
     animation is transform/opacity only, so it costs the compositor, not the main thread. CSS: "Brand mark". --}}
@props(['settings', 'id' => 'bm'])
@php $logo = $settings['logo'] ?? null; @endphp
<div {{ $attributes->class(['brand-mark']) }} aria-hidden="true">
    <span class="brand-mark-ring"></span>
    <span class="brand-mark-ring brand-mark-ring--b"></span>
    @if ($logo)
        <img src="{{ $logo['url'] }}" alt="" width="{{ $logo['width'] ?: 160 }}" height="{{ $logo['height'] ?: 160 }}" loading="lazy" decoding="async">
    @else
        <x-ui.fish :id="$id" class="brand-mark-fish" />
    @endif
</div>
