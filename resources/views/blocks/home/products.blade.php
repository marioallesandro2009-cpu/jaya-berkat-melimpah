@php
    $block = $sections['products'] ?? null;
    $texts = $settings['texts'];
@endphp
@if ($products)
    <section id="products" class="section section--white products">
        <div class="wrap">
            @if ($block)
                <div class="intro">
                    @if ($block['eyebrow'])
                        <p class="eyebrow reveal">{{ $block['eyebrow'] }}</p>
                    @endif
                    <h2 class="reveal">{{ $block['title'] }}</h2>
                </div>
            @endif

            @foreach ($products as $i => $product)
                <article class="product {{ $i % 2 === 1 ? 'product--reverse' : '' }}">
                    @if ($product['image'])
                        <div class="product-media img-reveal" data-dir="{{ $i % 2 === 1 ? 'r' : 'l' }}">
                            @if ($product['url'])
                                <a href="{{ $product['url'] }}" tabindex="-1" aria-hidden="true"><x-ui.picture :image="$product['image']" sizes="(max-width: 980px) 100vw, 58vw" /></a>
                            @else
                                <x-ui.picture :image="$product['image']" sizes="(max-width: 980px) 100vw, 58vw" />
                            @endif
                        </div>
                    @endif
                    <div class="product-body reveal">
                        <span class="product-num">{{ sprintf('%02d', $i + 1) }}</span>
                        <h3 class="product-name">
                            @if ($product['url'])
                                <a href="{{ $product['url'] }}">{{ $product['name'] }}</a>
                            @else
                                {{ $product['name'] }}
                            @endif
                        </h3>
                        @if ($product['description'])
                            <p class="product-desc">{{ $product['description'] }}</p>
                        @endif
                        <div class="product-links">
                            @if ($product['url'])
                                <a href="{{ $product['url'] }}" class="link-arrow">{{ $texts['product_learn_more'] }} <span class="arrow" aria-hidden="true">→</span></a>
                            @endif
                            <a href="#contact" class="link-arrow" data-product="{{ $product['value'] }}">{{ $texts['hero_secondary_cta'] }} <span class="arrow" aria-hidden="true">→</span></a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endif
