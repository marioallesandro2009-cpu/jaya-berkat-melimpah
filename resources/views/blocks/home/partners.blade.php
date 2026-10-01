@php
    $block = $sections['partners'] ?? null;
@endphp
@if ($trustLogos)
    <section id="partners" class="section section--white partners">
        <div class="wrap">
            @if ($block)
                <div class="partners-head">
                    @if ($block['eyebrow'])
                        <p class="eyebrow reveal">{{ $block['eyebrow'] }}</p>
                    @endif
                    <h2 class="reveal">{{ $block['title'] }}</h2>
                </div>
            @endif
            <ul class="logo-row" data-stagger>
                @foreach ($trustLogos as $partner)
                    @php($logo = $partner['logo'])
                    <li>
                        @if ($partner['url'])
                            <a href="{{ $partner['url'] }}" target="_blank" rel="noopener" aria-label="{{ $partner['name'] }}">
                        @endif
                        <picture>
                            @if ($logo['webp'])
                                <source type="image/webp" srcset="{{ $logo['webp'] }}">
                            @endif
                            <img src="{{ $logo['url'] }}" alt="{{ $partner['url'] ? '' : $partner['name'] }}" @if ($logo['width']) width="{{ $logo['width'] }}" height="{{ $logo['height'] }}" @endif loading="lazy" decoding="async">
                        </picture>
                        @if ($partner['url'])
                            </a>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
