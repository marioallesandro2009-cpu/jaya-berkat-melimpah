@php
    $block = $sections['locations'] ?? null;
@endphp
@if ($locations)
    <section id="locations" class="section section--light locations">
        <div class="wrap">
            @if ($block)
                <div class="locations-head">
                    @if ($block['eyebrow'])
                        <p class="eyebrow reveal">{{ $block['eyebrow'] }}</p>
                    @endif
                    <h2 class="reveal">{{ $block['title'] }}</h2>
                </div>
            @endif
            <div class="location-list">
                @foreach ($locations as $i => $location)
                    <article class="location reveal">
                        @if ($location['photo'])
                            <div class="location-media img-reveal" data-dir="{{ $i % 2 === 1 ? 'r' : 'l' }}">
                                <x-ui.picture :image="$location['photo']" sizes="(max-width: 980px) 100vw, 40vw" />
                            </div>
                        @endif
                        <div class="location-body">
                            @if ($location['kind'])
                                <p class="eyebrow">{{ $location['kind'] }}</p>
                            @endif
                            <h3>{{ $location['name'] }}</h3>
                            @if ($location['address'])
                                <p class="body-text">{!! nl2br(e($location['address'])) !!}</p>
                            @endif
                            @if ($location['mapsUrl'])
                                <a href="{{ $location['mapsUrl'] }}" class="link-arrow" target="_blank" rel="noopener">{{ __('Open in Maps') }} <span class="arrow" aria-hidden="true">→</span></a>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
