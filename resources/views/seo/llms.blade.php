# {!! $settings['companyName'] !!}

> {!! $settings['seo']['description'] !!}

{!! trim((string) ($settings['companyDescription'] ?? '')) !!}

## Products

@foreach ($products as $product)
- @if ($product['url'])[{!! $product['name'] !!}]({!! $baseUrl !!}{!! $product['url'] !!})@else{!! $product['name'] !!}@endif{!! $product['description'] ? ': '.$product['description'] : '' !!}
@endforeach

@if ($posts)
## News

@foreach ($posts as $post)
- [{!! $post['title'] !!}]({!! $baseUrl !!}{!! $post['url'] !!}){!! $post['excerpt'] ? ': '.$post['excerpt'] : '' !!}
@endforeach

@endif
## Languages

@foreach ($alternates as $locale => $url)
- {!! \App\Support\Locales::nativeName($locale) !!}: {!! $url !!}
@endforeach

## Contact

- Website: {!! $baseUrl !!}/
@if ($settings['email'])
- Email: {!! $settings['email'] !!}
@endif
@if ($settings['phone'])
- Phone: {!! $settings['phone'] !!}
@endif
@if ($settings['whatsappUrl'])
- WhatsApp: {!! $settings['whatsappUrl'] !!}
@endif
@if ($settings['address'])
- Address: {!! str_replace(["\r", "\n"], ' ', $settings['address']) !!}
@endif
@foreach ($settings['sameAs'] as $profile)
- {!! $profile !!}
@endforeach

## Links

- [Sitemap]({!! $baseUrl !!}/sitemap.xml)
