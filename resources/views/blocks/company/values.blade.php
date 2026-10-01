@php
    $block = $sections['company_values'] ?? null;
@endphp
@if ($features['value'])
    <section id="company_values" class="section section--white">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="section-index">{{ $block['eyebrow'] ?? '' }}</span>
                <h2>{{ $block['title'] ?? '' }}</h2>
            </div>
            <div class="values-grid" data-stagger>
                @foreach ($features['value'] as $value)
                    <div class="value-item">
                        <h3>{{ $value['title'] }}</h3>
                        @if ($value['body'])
                            <p class="body-text" style="margin-top: var(--space-xs);">{{ $value['body'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
