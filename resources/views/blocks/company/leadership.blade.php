@php
    $block = $sections['company_leadership'] ?? null;
@endphp
@if ($leaders)
    <section id="leadership" class="section section--light">
        <div class="wrap">
            <div class="section-head section-head--plain reveal">
                <h2>{{ $block['title'] ?? '' }}</h2>
            </div>
            <div class="leader-grid" data-stagger>
                @foreach ($leaders as $leader)
                    <div>
                        <div class="leader-photo">
                            @if ($leader['photo'])
                                <x-ui.picture :image="$leader['photo']" sizes="(max-width: 720px) 100vw, 30vw" :alt="$leader['name']" />
                            @else
                                <span aria-hidden="true">{{ $leader['initials'] }}</span>
                            @endif
                        </div>
                        <div class="leader-name">{{ $leader['name'] }}</div>
                        <div class="leader-title">{{ $leader['role'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
