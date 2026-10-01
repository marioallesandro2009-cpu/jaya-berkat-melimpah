@php
    $block = $sections['company_history'] ?? null;
@endphp
@if ($timeline)
    <section id="company_history" class="section section--light">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="section-index">{{ $block['eyebrow'] ?? '' }}</span>
                <h2>{{ $block['title'] ?? '' }}</h2>
            </div>
            <div class="timeline">
                @foreach ($timeline as $item)
                    <div class="timeline-item reveal">
                        <div class="timeline-year">{{ $item['year'] }}</div>
                        <div>
                            <h3>{{ $item['title'] }}</h3>
                            @if ($item['body'])
                                <p class="body-text" style="margin-top: var(--space-xs);">{{ $item['body'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
