@php
    $block = $sections['company_certifications'] ?? null;
@endphp
@if ($certifications)
    <section id="certifications" class="section section--light">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="section-index">{{ $block['eyebrow'] ?? '' }}</span>
                <h2>{{ $block['title'] ?? '' }}</h2>
            </div>
            <div class="cert-grid" data-stagger>
                @foreach ($certifications as $cert)
                    <div class="cert-item">
                        <h3>{{ $cert['name'] }}</h3>
                        <span class="cert-status">{{ $cert['status'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
