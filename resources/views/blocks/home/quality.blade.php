@php
    $block = $sections['quality'] ?? null;
@endphp
@if ($block)
    <section id="quality" class="section section--light quality">
        <div class="wrap split">
            @if ($block['image'])
                <div class="split-media img-reveal" data-dir="l">
                    <x-ui.picture :image="$block['image']" sizes="(max-width: 980px) 100vw, 50vw" />
                </div>
            @endif
            <div>
                @if ($block['eyebrow'])
                    <p class="eyebrow reveal">{{ $block['eyebrow'] }}</p>
                @endif
                <h2 class="reveal">{{ $block['title'] }}</h2>
                @if ($features['quality'])
                    <div class="quality-list" data-stagger>
                        @foreach ($features['quality'] as $row)
                            <div class="quality-row">
                                <h3>{{ $row['title'] }}</h3>
                                @if ($row['body'])
                                    <p class="body-text">{{ $row['body'] }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
                @if ($features['checkpoint'])
                    <div class="tech-line reveal">
                        @foreach ($features['checkpoint'] as $point)
                            <span>{{ $point['title'] }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
