{{--
    One on-brand page for every HTTP error the site can show (403, 404, 419, 429, 500, 503).
    It must work when the application itself is unhealthy, so it is fully self-contained:
    no database (no site settings, no logo), no Vite bundle, no scripts, inline CSS only,
    and never any exception detail. The language follows the URL (/id/... = Indonesian).
    Used by resources/views/errors/{code}.blade.php.
--}}
@php
    app()->setLocale(request()->is('id', 'id/*') ? 'id' : 'en');

    $pages = [
        403 => [__('Access denied'), __('You do not have permission to open this page.')],
        404 => [__('Page not found'), __('The page you are looking for does not exist or has moved.')],
        419 => [__('Session expired'), __('For your security the page was open for too long. Go back, reload it and try again.')],
        429 => [__('Too many requests'), __('Please wait a moment and try again.')],
        500 => [__('Something went wrong'), __('We are sorry, something failed on our side. Please try again in a few minutes.')],
        503 => [__('Back soon'), __('The site is being updated. Please come back in a few minutes.')],
    ];

    [$title, $text] = $pages[$code] ?? $pages[500];
    $home = request()->is('id', 'id/*') ? '/id' : '/';
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $title }} | {{ config('app.name') }}</title>
        <style>
            :root { color-scheme: dark; }
            * { box-sizing: border-box; }
            body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: #071A21; color: #fff; font-family: "Schibsted Grotesk", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif; }
            main { width: 100%; max-width: 520px; }
            .brand { margin: 0 0 40px; font-size: 14px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; color: #7CC4E4; }
            .code { margin: 0; font-size: clamp(72px, 22vw, 120px); font-weight: 800; line-height: 1; letter-spacing: -.04em; color: rgb(255 255 255 / .14); }
            h1 { margin: 8px 0 12px; font-size: clamp(26px, 6vw, 34px); line-height: 1.2; }
            p { margin: 0 0 32px; font-size: 17px; line-height: 1.6; color: rgb(255 255 255 / .82); }
            .actions { display: flex; flex-wrap: wrap; gap: 12px; }
            a { display: inline-flex; align-items: center; min-height: 48px; padding: 0 28px; border-radius: 999px; font-weight: 700; font-size: 16px; text-decoration: none; }
            a.primary { background: #7CC4E4; color: #071A21; }
            a.secondary { border: 1px solid rgb(255 255 255 / .55); color: #fff; }
            a:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
        </style>
    </head>
    <body>
        <main>
            <p class="brand">{{ config('app.name') }}</p>
            <p class="code" aria-hidden="true">{{ $code }}</p>
            <h1>{{ $title }}</h1>
            <p>{{ $text }}</p>
            <div class="actions">
                <a class="primary" href="{{ $home }}">{{ __('Back to home') }}</a>
                @if (in_array($code, [419, 429, 404], true))
                    <a class="secondary" href="{{ url()->previous($home) }}">{{ __('Go back') }}</a>
                @endif
            </div>
        </main>
    </body>
</html>
