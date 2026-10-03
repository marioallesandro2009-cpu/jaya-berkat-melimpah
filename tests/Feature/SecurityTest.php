<?php

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\MenuItem;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\FrontendData;
use App\Support\Links;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(fn () => seedSite());

$payload = fn (array $extra = []): array => [
    'name' => 'Ann Buyer', 'email' => 'ann@buyer.test', 'company' => 'Buyer Ltd', 'country' => 'Japan',
    'product' => 'Yellowfin Tuna Whole', ...$extra,
];

it('sends the standard security headers on every page', function () {
    $response = $this->get('/');

    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
    expect($response->headers->get('Permissions-Policy'))->toContain('camera=()');
});

it('ships a nonce-based CSP without unsafe-eval, and enforces it when asked', function () {
    config(['security.csp.mode' => 'report-only']);
    $policy = $this->get('/')->headers->get('Content-Security-Policy-Report-Only');
    expect($policy)->toContain("script-src 'self' 'nonce-")->not->toContain('unsafe-eval')->toContain("object-src 'none'")->toContain("frame-ancestors 'self'");

    config(['security.csp.mode' => 'enforce']);
    $response = $this->get('/');
    expect($response->headers->get('Content-Security-Policy'))->not->toBeNull();
    // The inline "js" flag script and main.js carry the same nonce as the header.
    preg_match("/'nonce-([^']+)'/", (string) $response->headers->get('Content-Security-Policy'), $match);
    $response->assertSee('nonce="'.$match[1].'"', false);
});

it('keeps the admin out of search engines', function () {
    $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    $this->get('/robots.txt')->assertSee('Disallow: /admin');
});

it('sends HSTS only over https and only when configured', function () {
    config(['security.hsts.max_age' => 300]);

    $this->get('http://localhost/')->assertHeaderMissing('Strict-Transport-Security');
    $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=300');
});

it('redirects visitors who are not logged in away from the admin', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('answers the health check and a branded 404', function () {
    $this->get('/up')->assertOk();
    $this->get('/no-such-page')->assertNotFound()->assertSee('Page not found');
});

it('limits the inquiry form to 5 per 15 minutes per visitor', function () use ($payload) {
    RateLimiter::clear('contact');

    foreach (range(1, 5) as $i) {
        $this->postJson('/contact', $payload())->assertOk();
    }

    $this->postJson('/contact', $payload())->assertStatus(429)->assertJsonStructure(['message']);
    expect(ContactMessage::query()->count())->toBe(5);
});

it('escapes everything an admin or visitor can type', function () use ($payload) {
    $xss = '<script>alert(1)</script>';

    $product = Product::query()->orderBy('sort_order')->firstOrFail();
    $product->update(['name' => ['en' => 'Fish '.$xss], 'description' => ['en' => $xss], 'detail_status' => Product::PUBLISHED, 'intro' => ['en' => $xss], 'specs' => [['label' => ['en' => $xss], 'value' => ['en' => $xss]]]]);
    PageSection::query()->where('key', 'origin')->firstOrFail()->update(['body' => ['en' => $xss.PHP_EOL.PHP_EOL.$xss]]);
    FrontendData::flush();

    foreach (['/', '/products/'.$product->slug] as $path) {
        $html = $this->get($path)->assertOk()->getContent();
        expect($html)->not->toContain('<script>alert(1)')->toContain('&lt;script&gt;alert(1)');
    }

    // Rich text is sanitised on output, not escaped: a script tag must be dropped entirely.
    Post::query()->create(['title' => 'Safe', 'slug' => 'safe', 'content' => ['en' => '<p>Fine</p>'.$xss.'<img src=x onerror=alert(1)>'], 'status' => Post::PUBLISHED, 'published_at' => now()->subDay(), 'is_active' => true]);
    $this->get('/news/safe')->assertOk()->assertSee('Fine')->assertDontSee('<script>alert(1)', false)->assertDontSee('onerror', false);

    // The mail to the team escapes the visitor's text too.
    $this->postJson('/contact', $payload(['name' => 'Eve <b>x</b>', 'message' => '[click](https://evil.test) '.$xss]))->assertOk();
    $html = (new ContactMessageReceived(ContactMessage::query()->firstOrFail()))->render();
    expect($html)->not->toContain('<script>alert(1)')->not->toContain('<b>x</b>');
});

it('never renders a javascript: link typed in the admin', function () {
    expect(Links::safe('javascript:alert(1)'))->toBeNull()
        ->and(Links::safe(' data:text/html;base64,AAAA'))->toBeNull()
        ->and(Links::safe('//evil.test'))->toBeNull()
        ->and(Links::safe('/ok'))->toBe('/ok')
        ->and(Links::safe('#contact'))->toBe('#contact')
        ->and(Links::safe('https://example.org/x'))->toBe('https://example.org/x')
        ->and(Links::safe('mailto:a@b.test'))->toBe('mailto:a@b.test');

    MenuItem::query()->create(['location' => MenuItem::HEADER, 'type' => 'url', 'target' => 'javascript:alert(1)', 'label' => 'Bad', 'sort_order' => 99]);
    PageSection::query()->where('key', 'statement')->firstOrFail()->update(['cta_url' => 'javascript:alert(2)']);
    FrontendData::flush();

    $this->get('/')->assertOk()->assertDontSee('javascript:', false);
});

it('emails the team about an inquiry, with the visitor as reply-to', function () use ($payload) {
    Mail::fake();
    RateLimiter::clear('contact');

    $this->postJson('/contact', $payload(['message' => 'Need 5 tons per month.']))->assertOk();

    Mail::assertSent(ContactMessageReceived::class, function (ContactMessageReceived $mail): bool {
        return $mail->hasTo('info@jbmelimpah.com') && $mail->hasReplyTo('ann@buyer.test');
    });
    expect(ContactMessage::query()->first()->email_failed)->toBeFalse();
});

it('flags the inquiry instead of failing when the email cannot be sent', function () use ($payload) {
    RateLimiter::clear('contact');
    Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));

    $this->postJson('/contact', $payload())->assertOk()->assertJson(['ok' => true]);

    expect(ContactMessage::query()->first()->email_failed)->toBeTrue();
});

it('renders the notification mail with every field', function () use ($payload) {
    RateLimiter::clear('contact');
    $this->postJson('/contact', $payload(['volume' => '5 tons', 'message' => 'Hello']))->assertOk();

    $html = (new ContactMessageReceived(ContactMessage::query()->firstOrFail()))->render();

    expect($html)->toContain('Ann Buyer')->toContain('Japan')->toContain('Yellowfin Tuna Whole')->toContain('5 tons')->toContain('Hello');
});

it('never errors on odd category filters and keeps category slugs unique', function () {
    foreach (['/products?category[]=x', '/products?category[a]=b', '/products?category=%00', '/id/produk?category=<script>'] as $url) {
        $this->get($url)->assertOk()->assertDontSee('<script>alert', false);
    }

    $a = ProductCategory::query()->create(['name' => ['en' => 'Reef'], 'color' => 'red;}</style>']);
    $b = ProductCategory::query()->create(['name' => ['en' => 'Reef']]);

    expect($a->slug)->toBe('reef')->and($b->slug)->toBe('reef-2')
        ->and($a->color)->toBe(ProductCategory::DEFAULT_COLOR)
        ->and($a->toFrontend()['color'])->toMatch('/^#[0-9a-f]{6}$/i');
});
