<?php

use App\Models\ContactMessage;
use App\Models\Feature;
use App\Models\Post;
use App\Models\Product;
use App\Models\User;
use App\Support\FrontendData;

beforeEach(fn () => seedSite());

it('renders every public page in both languages', function (string $path) {
    $this->get($path)->assertOk();
})->with(['/', '/id', '/company', '/id/perusahaan', '/news', '/id/berita', '/robots.txt', '/sitemap.xml', '/llms.txt', '/site.webmanifest']);

it('shows the Indonesian text on /id and English on /', function () {
    $this->get('/')->assertSee('From the largest archipelago to the global market.');
    $this->get('/id')->assertSee('Dari kepulauan terbesar ke pasar global.');
});

it('hides the news link until a post is published, then shows it', function () {
    Post::query()->get()->each->delete();
    FrontendData::flush();

    $this->get('/')->assertDontSee('/news"', false);

    Post::query()->create(['title' => 'First catch', 'excerpt' => 'Hello', 'status' => Post::PUBLISHED, 'published_at' => now()->subDay(), 'is_active' => true]);
    FrontendData::flush();

    $this->get('/')->assertSee('First catch');
    $this->get('/news/first-catch')->assertOk()->assertSee('First catch');
    $this->get('/id/berita/first-catch')->assertOk();
});

it('lets an admin open every admin page', function (string $path) {
    $admin = (new User)->forceFill(['name' => 'Admin', 'email' => 'a@example.test', 'password' => 'x', 'is_admin' => true]);
    $admin->save();

    $this->actingAs($admin)->get($path)->assertOk();
})->with([
    '/admin', '/admin/settings', '/admin/page-sections', '/admin/stats', '/admin/products', '/admin/chain-steps',
    '/admin/features', '/admin/timeline-items', '/admin/leaders', '/admin/certifications', '/admin/posts', '/admin/posts/create',
    '/admin/contact-messages',
]);

it('blocks non-admins from the admin panel', function () {
    $user = User::query()->forceCreate(['name' => 'U', 'email' => 'u@example.test', 'password' => 'x']);

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('stores an inquiry and validates required fields', function () {
    $product = Product::query()->first();

    $this->postJson('/contact', ['name' => 'Ann', 'email' => 'ann@buyer.test', 'company' => 'Buyer Ltd', 'country' => 'Japan', 'product' => $product->translation('name', 'en'), 'volume' => '5 tons'])
        ->assertOk()->assertJson(['ok' => true]);

    $lead = ContactMessage::query()->firstOrFail();
    expect($lead->product_id)->toBe($product->id)->and($lead->country)->toBe('Japan');

    $this->postJson('/id/kontak', ['name' => 'x'])->assertStatus(422)->assertJsonValidationErrors(['email', 'company', 'country', 'product']);
});

it('ignores the form when the honeypot is filled', function () {
    $this->postJson('/contact', ['website' => 'spam', 'name' => 'Bot', 'email' => 'b@b.test'])->assertOk();

    expect(ContactMessage::query()->count())->toBe(0);
});

it('lists every product on the all-products page in both languages', function () {
    $this->get('/products')->assertOk()->assertSee('Fresh Grouper')->assertSee('shoal', false);
    $this->get('/id/produk')->assertOk()->assertSee('Kerapu Segar');
    $this->get('/')->assertSee('href="/products"', false);
    $this->get('/sitemap.xml')->assertSee('/products', false);
});

it('shows the refined all-products sections, with the product forms strip only when forms exist', function () {
    $this->get('/products')->assertOk()
        ->assertSee('From source to export')->assertSee('Looking for a specific product?')
        ->assertSee('Every catch has a story.')->assertSee('What we supply')->assertDontSee('pforms-list--forms', false);

    Feature::query()->create(['group' => Feature::PRODUCT_FORM, 'title' => ['en' => 'Fillet', 'id' => 'Fillet'], 'is_active' => true]);
    Product::query()->first()->update(['specs' => [['label' => ['en' => 'Handling'], 'value' => ['en' => 'Chilled']]]]);
    FrontendData::flush();

    $this->get('/products')->assertSee('pforms-list--forms', false)->assertSee('Fillet')->assertSee('Handling')->assertSee('Chilled');
    $this->get('/id/produk')->assertOk()->assertSee('Dari sumber hingga ekspor');
});
