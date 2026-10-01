@php
    $block = $sections['chain'] ?? null;
@endphp
@if ($chain)
    <section id="journey" class="section section--dark chain" data-chain @if ($block) aria-labelledby="chain-title" @endif>
        <div class="wrap">
            @if ($block)
                <div class="chain-head">
                    @if ($block['eyebrow'])
                        <p class="eyebrow reveal">{{ $block['eyebrow'] }}</p>
                    @endif
                    <h2 id="chain-title" class="reveal">{{ $block['title'] }}</h2>
                </div>
            @endif

            <div class="chain-grid">
                <div class="chain-visual" aria-hidden="true">
                    @foreach ($chain as $i => $step)
                        <div class="chain-frame {{ $i === 0 ? 'is-active' : '' }}">
                            <x-ui.picture :image="$step['image']" sizes="(max-width: 980px) 0px, 42vw" alt="" />
                        </div>
                    @endforeach
                    <span class="chain-count">{{ sprintf('01 / %02d', count($chain)) }}</span>
                </div>

                <div class="chain-steps">
                    <span class="chain-track" aria-hidden="true"></span>
                    <span class="chain-fill" aria-hidden="true"></span>
                    <ol style="margin:0;padding:0;">
                        @foreach ($chain as $i => $step)
                            <li class="chain-step {{ $i === 0 ? 'is-active' : '' }}">
                                <span class="numeral">{{ sprintf('%02d', $i + 1) }}</span>
                                <h3>{{ $step['title'] }}</h3>
                                @if ($step['body'])
                                    <p>{{ $step['body'] }}</p>
                                @endif
                                @if ($step['image'])
                                    <div class="chain-step-img"><x-ui.picture :image="$step['image']" sizes="(max-width: 980px) 100vw, 0px" alt="" /></div>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>
    </section>
@endif
