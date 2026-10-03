@php
    $texts = $settings['texts'];
@endphp
<x-layouts::app :seo="$seo" :settings="$settings" :json-ld="$jsonLd">
    <section class="page-header page-header--plain">
        <div class="wrap">
            <h1 class="on-dark" data-hero-in="1">{{ $texts['credits_title'] }}</h1>
            <p class="lede on-dark" data-hero-in="2">{{ $texts['credits_intro'] }}</p>
        </div>
    </section>

    <section class="credits">
        <div class="wrap">
            @forelse ($credits as $credit)
                @if ($loop->first)
                    <ul class="credits-list">
                @endif
                <li>
                    <a class="credit-title" href="{{ $credit['source'] }}" target="_blank" rel="noopener">{{ $credit['title'] }}</a>
                    <dl>
                        @if ($credit['author'])
                            <div><dt>{{ $texts['credits_author'] }}</dt><dd>{{ $credit['author'] }}</dd></div>
                        @endif
                        @if ($credit['license'])
                            <div><dt>{{ $texts['credits_licence'] }}</dt><dd>@if ($credit['licenseUrl'])<a href="{{ $credit['licenseUrl'] }}" target="_blank" rel="noopener license">{{ $credit['license'] }}</a>@else{{ $credit['license'] }}@endif</dd></div>
                        @endif
                    </dl>
                </li>
                @if ($loop->last)
                    </ul>
                @endif
            @empty
                <p class="credits-empty">{{ $texts['credits_empty'] }}</p>
            @endforelse
            @if ($credits)
                <p class="credits-note">{{ $texts['credits_changes'] }}</p>
            @endif
        </div>
    </section>
</x-layouts::app>
