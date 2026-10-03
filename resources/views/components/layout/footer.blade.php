@props(['settings'])
@php
    use App\Support\FrontendData;
    use App\Support\Links;

    $data = FrontendData::all();
    $hasNews = ! empty($data['recentPosts']);
    $column = fn (string $location) => collect($data['menus'][$location])
        ->map(fn (array $item): ?array => ($link = Links::menu($item, $hasNews)) ? [...$item, ...$link] : null)
        ->filter()
        ->values();
    $texts = $settings['texts'];
@endphp
<footer class="footer">
    <div class="wrap">
        <div class="footer-top">
            <div class="footer-col">
                <div class="footer-wordmark">
                    @if ($logo = $settings['logo'])
                        <img src="{{ $logo['url'] }}" alt="" width="{{ $logo['width'] ?: 36 }}" height="{{ $logo['height'] ?: 36 }}" loading="lazy">
                    @endif
                    {{ $settings['companyName'] }}
                </div>
                @if ($settings['footerTagline'])
                    <p class="footer-tagline">{{ $settings['footerTagline'] }}</p>
                @endif
            </div>
            @foreach (['footer_explore' => $texts['footer_explore'], 'footer_company' => $texts['footer_company']] as $location => $heading)
                @if ($links = $column($location))
                    <div class="footer-col">
                        <h2 class="footer-h">{{ $heading }}</h2>
                        <ul>
                            @foreach ($links as $item)
                                <li><a href="{{ $item['href'] }}" @if ($item['newTab']) target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
            <div class="footer-col">
                <h2 class="footer-h">{{ $texts['footer_contact'] }}</h2>
                <ul>
                    @if ($settings['email'])
                        <li><a href="mailto:{{ $settings['email'] }}">{{ $settings['email'] }}</a></li>
                    @endif
                    @if ($settings['phone'])
                        <li><a href="tel:{{ preg_replace('/[^\d+]/', '', $settings['phone']) }}">{{ $settings['phone'] }}</a></li>
                    @endif
                    @if ($settings['whatsappUrl'])
                        <li><a href="{{ $settings['whatsappUrl'] }}" target="_blank" rel="noopener">{{ __('WhatsApp') }}</a></li>
                    @endif
                    @if ($settings['address'])
                        <li>{!! nl2br(e($settings['address'])) !!}</li>
                    @endif
                    @if ($settings['instagramUrl'])
                        <li><a href="{{ $settings['instagramUrl'] }}" target="_blank" rel="noopener">{{ __('Instagram') }}</a></li>
                    @endif
                    @if ($settings['linkedinUrl'])
                        <li><a href="{{ $settings['linkedinUrl'] }}" target="_blank" rel="noopener">{{ __('LinkedIn') }}</a></li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>&copy; {{ now()->year }} {{ $settings['legalName'] }}. {{ $texts['footer_copyright'] }}</span>
            <span class="footer-meta">
                @if (\App\Http\Controllers\CreditsController::exists())
                    <a href="{{ \App\Support\Links::page('credits') }}">{{ $texts['credits_title'] }}</a>
                @endif
                <span>Indonesia</span>
            </span>
        </div>
    </div>
</footer>
