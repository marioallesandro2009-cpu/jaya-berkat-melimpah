@props(['settings'])
@php
    use App\Support\FrontendData;
    use App\Support\Links;
    use App\Support\Locales;

    $data = FrontendData::all();
    $hasNews = ! empty($data['recentPosts']);
    $items = collect($data['menus']['header'])
        ->map(fn (array $item): ?array => ($link = Links::menu($item, $hasNews)) ? [...$item, ...$link] : null)
        ->filter()
        ->values();
@endphp
<header class="nav">
    <div class="wrap nav-inner">
        <a href="{{ Locales::path(Locales::current()) }}" class="nav-mark" aria-label="{{ $settings['companyName'] }}">
            @if ($logo = $settings['logo'])
                <img src="{{ $logo['url'] }}" alt="" width="{{ $logo['width'] ?: 40 }}" height="{{ $logo['height'] ?: 40 }}" class="mark">
            @endif
            <span class="wordmark">{{ $settings['companyName'] }}</span>
        </a>
        <nav aria-label="{{ $settings['texts']['nav_primary'] }}">
            <button class="nav-toggle" type="button" aria-label="{{ $settings['texts']['menu_open'] }}" data-label-open="{{ $settings['texts']['menu_open'] }}" data-label-close="{{ $settings['texts']['menu_close'] }}" aria-expanded="false" aria-controls="nav-links"><span></span></button>
            <ul class="nav-links" id="nav-links">
                @foreach ($items->reject(fn (array $i): bool => $i['button']) as $item)
                    <li><a href="{{ $item['href'] }}" @if ($item['current']) class="is-active" aria-current="page" @endif @if ($item['newTab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a></li>
                @endforeach
                <li><x-layout.language-switch :settings="$settings" /></li>
                @foreach ($items->filter(fn (array $i): bool => $i['button']) as $item)
                    <li><a href="{{ $item['href'] }}" class="btn btn--outline" @if ($item['newTab']) target="_blank" rel="noopener" @endif>{{ $item['label'] ?: $settings['ctaLabel'] }}</a></li>
                @endforeach
            </ul>
        </nav>
    </div>
</header>
