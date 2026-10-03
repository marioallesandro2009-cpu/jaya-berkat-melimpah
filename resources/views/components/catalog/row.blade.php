{{-- One product as a row of the compact catalogue (also used on species pages): small photo, name, species,
     the three facts a buyer compares (cut, storage, grade) and the actions. --}}
@props(['product', 'texts'])
<li class="prow" data-inquiry-item data-name="{{ $product['value'] }}" data-code="{{ $product['code'] }}" data-search="{{ \Illuminate\Support\Str::lower(implode(' ', array_filter([$product['name'], $product['code'], $product['species']['name'] ?? null, $product['species']['scientific'] ?? null, $product['cut'], $product['category']['name'] ?? null]))) }}">
    <a class="prow-thumb" @if ($product['url']) href="{{ $product['url'] }}" @endif tabindex="-1" aria-hidden="true">
        @if ($product['image'])
            <x-ui.picture :image="$product['image']" sizes="110px" alt="" />
        @endif
    </a>
    <div class="prow-main">
        @if ($product['code'])
            <span class="prow-code">{{ $product['code'] }}</span>
        @endif
        <h4 class="prow-name">
            @if ($product['url'])
                <a href="{{ $product['url'] }}">{{ $product['name'] }}</a>
            @else
                {{ $product['name'] }}
            @endif
        </h4>
        @if ($product['species'])
            <a class="prow-species" href="{{ $product['species']['url'] }}">{{ $product['species']['name'] }}@if ($product['species']['scientific']) <em>{{ $product['species']['scientific'] }}</em>@endif</a>
        @endif
    </div>
    <dl class="prow-meta">
        @if ($product['cut'])
            <div><dt>{{ $texts['filter_cut'] }}</dt><dd>{{ $product['cut'] }}</dd></div>
        @endif
        @if ($product['storage'])
            <div><dt>{{ $texts['filter_storage'] }}</dt><dd>{{ $product['storage'] }}</dd></div>
        @endif
        @if ($product['grade'])
            <div><dt>{{ $texts['filter_grade'] }}</dt><dd>{{ $product['grade'] }}</dd></div>
        @endif
    </dl>
    <div class="prow-actions">
        <button type="button" class="prow-add" data-inquiry-add data-label-add="{{ $texts['inquiry_add'] }}" data-label-added="{{ $texts['inquiry_added'] }}" aria-pressed="false">
            <span class="prow-add-icon" aria-hidden="true"></span><span class="prow-add-text">{{ $texts['inquiry_add'] }}</span>
        </button>
        @if ($product['url'])
            <a href="{{ $product['url'] }}" class="prow-link">{{ $texts['product_learn_more'] }} <span aria-hidden="true">→</span></a>
        @endif
    </div>
</li>
