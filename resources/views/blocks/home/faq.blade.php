@php
    $block = $sections['faq'] ?? null;
@endphp
@if ($faqs)
    <section id="faq" class="section section--white faq">
        <div class="wrap faq-grid">
            <div>
                @if ($block && $block['eyebrow'])
                    <p class="eyebrow reveal">{{ $block['eyebrow'] }}</p>
                @endif
                <h2 class="reveal">{{ $block['title'] ?? __('Frequently asked questions') }}</h2>
            </div>
            <div class="faq-list" data-stagger>
                @foreach ($faqs as $faq)
                    <details class="faq-item">
                        <summary>{{ $faq['question'] }}</summary>
                        <div class="faq-answer">{!! \App\Support\Html::paragraphs($faq['answer']) !!}</div>
                    </details>
                @endforeach
            </div>
        </div>
    </section>
@endif
