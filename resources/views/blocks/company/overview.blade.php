@php
    $block = $sections['company_overview'] ?? null;
@endphp
@if ($block)
    <section id="company_overview" class="section section--white">
        <div class="wrap split">
            @if ($block['image'])
                <div class="split-media img-reveal" data-dir="l">
                    <x-ui.picture :image="$block['image']" sizes="(max-width: 980px) 100vw, 50vw" />
                </div>
            @endif
            <div class="reveal">
                @if ($block['eyebrow'])
                    <p class="eyebrow">{{ $block['eyebrow'] }}</p>
                @endif
                <h2 style="margin: var(--space-sm) 0 var(--space-md);">{{ $block['title'] }}</h2>
                <div class="body-text">{!! $block['bodyHtml'] !!}</div>
            </div>
        </div>
    </section>
@endif
