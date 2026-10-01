{{--
    A block added in the admin (Blok Halaman > Tambah blok sendiri). $block is
    App\Models\PageSection::toFrontend(); layout and background come from the admin.
--}}
@php
    use App\Support\Links;

    $layout = $block['layout'];
    $bg = $block['background'];
    $cta = $block['cta'] ? ($block['ctaUrl'] ?: Links::section('contact')) : null;
@endphp

@if ($layout === 'full_bleed' && $block['image'])
    <section id="{{ $block['key'] }}" class="band band--short">
        <div class="band-media" data-parallax>
            <x-ui.picture :image="$block['image']" sizes="100vw" />
        </div>
        <div class="wrap">
            <div class="band-copy reveal">
                @if ($block['eyebrow'])
                    <p class="eyebrow">{{ $block['eyebrow'] }}</p>
                @endif
                <h2>{{ $block['title'] }}</h2>
                <div class="body-text">{!! $block['bodyHtml'] !!}{!! $block['extraHtml'] !!}</div>
                @if ($cta)
                    <a href="{{ $cta }}" class="btn btn--primary" style="margin-top: var(--space-md);">{{ $block['cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
                @endif
            </div>
        </div>
    </section>
@elseif ($layout === 'text_only' || ! $block['image'])
    <section id="{{ $block['key'] }}" class="section section--{{ in_array($bg, ['dark', 'ocean'], true) ? ($bg === 'ocean' ? 'secondary' : 'dark') : ($bg === 'light' ? 'light' : 'white') }} custom-statement">
        <div class="wrap custom-statement-grid">
            <div class="reveal">
                @if ($block['eyebrow'])
                    <p class="eyebrow">{{ $block['eyebrow'] }}</p>
                @endif
                <h2 class="display" style="margin-top: var(--space-sm);">{{ $block['title'] }}</h2>
            </div>
            <div class="reveal">
                <div class="body-text">{!! $block['bodyHtml'] !!}{!! $block['extraHtml'] !!}</div>
                @if ($cta)
                    <a href="{{ $cta }}" class="link-arrow" style="margin-top: var(--space-md);">{{ $block['cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
                @endif
            </div>
        </div>
    </section>
@else
    <section id="{{ $block['key'] }}" class="section section--{{ in_array($bg, ['dark', 'ocean'], true) ? ($bg === 'ocean' ? 'secondary' : 'dark') : ($bg === 'light' ? 'light' : 'white') }}">
        <div class="wrap split {{ $layout === 'image_left' ? '' : 'split--reverse' }}">
            <div class="split-media img-reveal" data-dir="{{ $layout === 'image_left' ? 'l' : 'r' }}">
                <x-ui.picture :image="$block['image']" sizes="(max-width: 980px) 100vw, 50vw" />
            </div>
            <div class="reveal">
                @if ($block['eyebrow'])
                    <p class="eyebrow">{{ $block['eyebrow'] }}</p>
                @endif
                <h2 style="margin: var(--space-sm) 0 var(--space-md);">{{ $block['title'] }}</h2>
                <div class="body-text">{!! $block['bodyHtml'] !!}{!! $block['extraHtml'] !!}</div>
                @if ($cta)
                    <a href="{{ $cta }}" class="link-arrow" style="margin-top: var(--space-md);">{{ $block['cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
                @endif
            </div>
        </div>
    </section>
@endif
