@php
    use App\Support\Links;

    $block = $sections['statement'] ?? null;
@endphp
@if ($block)
    <section id="story" class="section section--dark statement">
        <div class="wrap">
            <div class="statement-grid">
                <h2 class="display reveal">{{ $block['title'] }}</h2>
                <div class="statement-copy reveal">
                    <x-ui.brand-mark :settings="$settings" id="bm-statement" class="brand-mark--statement" />
                    @if ($block['body'])
                        <p class="body-text">{{ $block['body'] }}</p>
                    @endif
                    @if ($block['cta'])
                        <a href="{{ $block['ctaUrl'] ?: Links::page('company') }}" class="link-arrow">{{ $block['cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
                    @endif
                </div>
            </div>

            @if ($stats)
                <div class="stats" data-stagger>
                    @foreach ($stats as $stat)
                        <div class="stat">
                            @if ($stat['value'] !== null)
                                <div class="numeral" data-count="{{ $stat['value'] }}" data-suffix="{{ $stat['suffix'] }}">{{ rtrim(rtrim(number_format($stat['value'], 2, '.', ''), '0'), '.') }}{{ $stat['suffix'] }}</div>
                            @else
                                <div class="numeral">{{ $stat['textValue'] }}</div>
                            @endif
                            <p class="stat-label">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
