@php
    $vision = $sections['company_vision'] ?? null;
    $mission = $sections['company_mission'] ?? null;
@endphp
@if ($vision || $mission)
    <section id="company_vision" class="section section--dark">
        <div class="wrap vm">
            @foreach ([$vision, $mission] as $item)
                @if ($item)
                    <div class="reveal">
                        <p class="eyebrow">{{ $item['eyebrow'] }}</p>
                        <h3 class="on-dark">{{ $item['title'] }}</h3>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
@endif
