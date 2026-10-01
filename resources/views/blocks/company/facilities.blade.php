@php
    $block = $sections['company_facilities'] ?? null;
@endphp
@if ($block)
    <section id="company_facilities" class="band band--short">
        @if ($block['image'])
            <div class="band-media" data-parallax>
                <x-ui.picture :image="$block['image']" sizes="100vw" />
            </div>
        @endif
        <div class="wrap">
            <div class="band-copy reveal">
                @if ($block['eyebrow'])
                    <p class="eyebrow">{{ $block['eyebrow'] }}</p>
                @endif
                <h2>{{ $block['title'] }}</h2>
                <div class="body-text">{!! $block['bodyHtml'] !!}</div>
            </div>
        </div>
        <x-ui.wave />
    </section>
@endif
