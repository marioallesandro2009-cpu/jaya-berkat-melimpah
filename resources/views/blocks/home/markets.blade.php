@php
    $block = $sections['markets'] ?? null;
@endphp
@if ($block)
    <section id="markets" class="section section--light markets">
        <div class="wrap">
            <div class="split">
                <h2 class="display reveal">{{ $block['title'] }}</h2>
                @if ($block['body'])
                    <p class="body-text reveal">{{ $block['body'] }}</p>
                @endif
            </div>
            <div class="route" role="img" aria-label="{{ __('Route from Jakarta, Indonesia to global buyers') }}">
                <svg viewBox="0 0 1000 150" aria-hidden="true">
                    <path class="route-line" pathLength="1" d="M20,110 C240,10 420,150 620,70 S900,20 980,50"/>
                    <circle class="route-dot" cx="20" cy="110" r="5"/>
                    <g class="route-end">
                        <circle class="route-dot route-dot--end" cx="980" cy="50" r="5"/>
                    </g>
                </svg>
                <div class="route-labels" aria-hidden="true">
                    <span class="route-label">{{ __('Jakarta, Indonesia') }}</span>
                    <span class="route-label route-label--end">{{ __('Global buyers') }}</span>
                </div>
            </div>
        </div>
    </section>
@endif
