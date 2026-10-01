@php
    $block = $sections['sustainability'] ?? null;
@endphp
@if ($block)
    <section id="sustainability" class="sustain">
        @if ($block['image'])
            <div class="sustain-media" data-parallax>
                <x-ui.picture :image="$block['image']" sizes="100vw" />
            </div>
        @endif
        <div class="wrap">
            @if ($block['eyebrow'])
                <p class="eyebrow reveal">{{ $block['eyebrow'] }}</p>
            @endif
            <h2 class="reveal">{{ $block['title'] }}</h2>
            @if ($features['sustainability'])
                <div class="chapters" data-stagger>
                    @foreach ($features['sustainability'] as $i => $chapter)
                        <div class="chapter">
                            <span class="numeral">{{ sprintf('%02d', $i + 1) }}</span>
                            <h3>{{ $chapter['title'] }}</h3>
                            @if ($chapter['body'])
                                <p>{{ $chapter['body'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
