<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\MenuItems\Pages\ManageMenuItems;
use App\Filament\Resources\PageSections\Pages\ManagePageSections;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Filament\Resources\Posts\Pages\ListPosts;
use App\Filament\Resources\ProductCategories\Pages\ManageProductCategories;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\Location;
use App\Models\MenuItem;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SiteSetting;
use App\Models\TrustLogo;
use App\Models\User;
use App\Support\FrontendData;
use Filament\Actions\CreateAction;
use Livewire\Livewire;

beforeEach(function () {
    seedSite();

    $this->actingAs(User::query()->forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'x', 'is_admin' => true]));
});

function seedPhoto(string $name = 'fish1'): string
{
    return (string) file_get_contents(database_path("seeders/images/{$name}.jpg"));
}

// ---- 1. menus ------------------------------------------------------------------------------

it('builds the navbar and footer from the menu items', function () {
    $this->get('/')->assertSee('Business')->assertSee('Company Profile');

    $item = MenuItem::query()->where('location', MenuItem::HEADER)->where('target', 'quality')->firstOrFail();

    Livewire::test(ManageMenuItems::class)
        ->callTableAction('edit', $item, data: [
            'location' => MenuItem::HEADER,
            'label' => ['en' => 'Our Standards', 'id' => 'Standar Kami'],
            'type' => MenuItem::SECTION,
            'target' => 'quality',
            'is_active' => true,
        ])
        ->assertHasNoTableActionErrors();

    $this->get('/')->assertSee('Our Standards');
    $this->get('/id')->assertSee('Standar Kami');
});

it('can point a menu item to an external url that opens in a new tab', function () {
    MenuItem::query()->create(['location' => MenuItem::FOOTER_COMPANY, 'type' => 'url', 'target' => 'https://example.org/brochure', 'label' => 'Brochure', 'new_tab' => true, 'sort_order' => 99]);

    $this->get('/')->assertSee('href="https://example.org/brochure"', false)->assertSee('target="_blank"', false)->assertSee('Brochure');
});

// ---- 2. page builder -----------------------------------------------------------------------

it('adds a custom block and renders it in the admin-chosen order', function () {
    Livewire::test(ManagePageSections::class)
        ->callAction('create', data: [
            'page' => 'home',
            'layout' => 'text_only',
            'background' => 'dark',
            'title' => ['en' => 'A custom announcement', 'id' => 'Pengumuman khusus'],
            'body' => ['en' => 'Hello from the builder.', 'id' => 'Halo dari pembuat halaman.'],
            // An Indonesian text is shown on /id only once it is marked as checked.
            'translation_status' => ['id' => ['title' => 'reviewed', 'body' => 'reviewed']],
            'is_active' => true,
        ])
        ->assertHasNoActionErrors();

    $custom = PageSection::query()->where('is_custom', true)->firstOrFail();
    expect($custom->key)->toStartWith('custom-');

    $this->get('/')->assertSee('A custom announcement')->assertSee('id="'.$custom->key.'"', false);
    $this->get('/id')->assertSee('Pengumuman khusus');
});

it('reorders blocks and hides inactive ones', function () {
    $position = fn (string $html, string $needle): int => (int) strpos($html, $needle);

    $html = $this->get('/')->getContent();
    expect($position($html, 'id="quality"'))->toBeLessThan($position($html, 'id="products"'));

    PageSection::query()->where('key', 'quality')->update(['sort_order' => 900]);
    FrontendData::flush();

    $html = $this->get('/')->getContent();
    expect($position($html, 'id="products"'))->toBeLessThan($position($html, 'id="quality"'));

    PageSection::query()->where('key', 'quality')->update(['is_active' => false]);
    FrontendData::flush();

    $this->get('/')->assertDontSee('id="quality"', false);
});

// ---- 3. colours ----------------------------------------------------------------------------

it('applies brand colours chosen in the admin', function () {
    $this->get('/')->assertDontSee('--deep-ocean:', false);

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['colors' => ['deep-ocean' => '#102A43']])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get('/')->assertSee('--deep-ocean:#102A43', false)->assertSee('--deep-ocean-rgb:16, 42, 67', false);
});

it('rejects an invalid colour', function () {
    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['colors' => ['aqua' => 'not-a-colour']])
        ->call('save')
        ->assertHasFormErrors();
});

// ---- 4. labels -----------------------------------------------------------------------------

it('lets the admin change interface labels per language', function () {
    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['ui_texts' => ['en' => ['form_submit' => 'Ask for prices'], 'id' => ['form_submit' => 'Tanya harga']]])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get('/')->assertSee('Ask for prices');
    $this->get('/id')->assertSee('Tanya harga');
});

// ---- 5. product pages ----------------------------------------------------------------------

it('only serves a product page once it is published', function () {
    $product = Product::query()->orderBy('sort_order')->firstOrFail();
    expect($product->slug)->not->toBeNull();

    $this->get('/products/'.$product->slug)->assertNotFound();
    $this->get('/')->assertDontSee('/products/'.$product->slug, false);

    $product->update([
        'detail_status' => Product::PUBLISHED,
        'intro' => ['en' => 'A detailed intro.', 'id' => 'Pengantar rinci.'],
        'specs' => [['label' => ['en' => 'Size', 'id' => 'Ukuran'], 'value' => ['en' => '2-4 kg', 'id' => '2-4 kg']]],
    ]);
    $product->markTranslationsReviewed('id')->save();

    $this->get('/products/'.$product->slug)->assertOk()->assertSee('A detailed intro.')->assertSee('Size')->assertSee('2-4 kg');
    $this->get('/id/produk/'.$product->slug)->assertOk()->assertSee('Pengantar rinci.')->assertSee('Ukuran');
    $this->get('/')->assertSee('/products/'.$product->slug, false);
    $this->get('/sitemap.xml')->assertSee('/products/'.$product->slug, false);
});

it('preselects the product when the form is opened from a product page', function () {
    $product = Product::query()->orderBy('sort_order')->firstOrFail();
    $name = $product->translation('name', 'en');

    $this->get('/?product='.urlencode($name))->assertSee('value="'.$name.'" selected', false);
});

// ---- 6. hero slides, partners, FAQ, locations ---------------------------------------------

it('runs a hero slideshow when there are two slides', function () {
    $this->get('/')->assertDontSee('data-slides', false);

    foreach ([1, 2] as $n) {
        $slide = HeroSlide::query()->create(['label' => "Slide {$n}", 'image_alt' => "Alt {$n}", 'is_active' => true]);
        $slide->addMediaFromString(seedPhoto('fish'.$n))->usingFileName("slide{$n}.jpg")->toMediaCollection('image');
    }

    $this->get('/')->assertSee('data-slides="6"', false)->assertSee('Alt 2');
});

it('shows partner logos, the FAQ and locations only when they exist', function () {
    $this->get('/')->assertDontSee('id="partners"', false)->assertDontSee('id="faq"', false)->assertDontSee('id="locations"', false);

    $logo = TrustLogo::query()->create(['name' => 'Acme Seafood Buyers', 'is_active' => true]);
    $logo->addMediaFromString(seedPhoto())->usingFileName('acme.png')->toMediaCollection('logo');
    Faq::query()->create(['question' => ['en' => 'Do you export?', 'id' => 'Apakah mengekspor?'], 'answer' => ['en' => 'Yes, to many markets.', 'id' => 'Ya.'], 'is_active' => true]);
    Location::query()->create(['name' => 'Jakarta Office', 'kind' => 'Head office', 'address' => 'Jl. Contoh 1', 'maps_url' => 'https://maps.example/jkt', 'is_active' => true]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->toContain('id="partners"')->toContain('Acme Seafood Buyers')
        ->toContain('id="faq"')->toContain('Do you export?')->toContain('"@type":"FAQPage"')
        ->toContain('id="locations"')->toContain('Jakarta Office')->toContain('https://maps.example/jkt');
});

it('keeps the default colours when nothing was changed', function () {
    expect(SiteSetting::current()->cssVariables())->toBe('');
});

// ---- blog SEO score --------------------------------------------------------------------------

it('shows an SEO score per language in the blog list and scores a post', function () {
    $post = Post::query()->create([
        'title' => ['en' => 'Fresh grouper export guide'],
        'slugs' => ['en' => 'fresh-grouper-export-guide'],
        'excerpt' => ['en' => 'Short summary'],
        'content' => ['en' => '<h2>Grouper</h2><p>Fresh grouper export starts at the dock.</p>'],
        'focus_keyword' => ['en' => 'fresh grouper'],
        'status' => Post::PUBLISHED,
        'is_active' => true,
    ]);

    expect($post->seoScore('en'))->toBeInt()->toBeBetween(1, 100)
        ->and($post->seoScore('id'))->toBeLessThan($post->seoScore('en'));

    Livewire::test(ListPosts::class)
        ->assertSuccessful()
        ->assertSee('SEO EN')
        ->assertSee($post->seoScore('en').'/100');

    Livewire::test(EditPost::class, ['record' => $post->getKey()])
        ->assertSuccessful()
        ->assertSee('Skor SEO');
});

// ---- product categories ----------------------------------------------------------------------

it('manages product categories in the admin and filters the all-products page', function () {
    Livewire::test(ManageProductCategories::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords(ProductCategory::all())
        ->callAction(CreateAction::class, ['name' => ['en' => 'Deep sea', 'id' => 'Laut dalam'], 'color' => '#112233', 'is_active' => true])
        ->assertHasNoActionErrors();

    $deep = ProductCategory::query()->where('slug', 'deep-sea')->firstOrFail();
    $tuna = Product::query()->whereJsonContains('name->en', 'Yellowfin Tuna')->firstOrFail();
    $tuna->update(['category_id' => $deep->id]);
    FrontendData::flush();

    $this->get('/products')->assertOk()->assertSee('Deep sea')->assertSee('?category=deep-sea', false);
    // the shoal lists only the filtered products ("what we supply" below always lists every species)
    $shoal = function (string $html): array {
        preg_match_all('/class="catch-name">(.*?)<\/h2>/s', $html, $names);

        return array_map(fn (string $name): string => trim(strip_tags($name)), $names[1]);
    };
    $filtered = $this->get('/products?category=deep-sea')->assertOk()->getContent();
    expect($shoal($filtered))->toHaveCount(1)->and($shoal($filtered)[0])->toContain('Yellowfin Tuna');
    $marine = $this->get('/id/produk?category=marine')->assertOk()->getContent();
    expect($shoal($marine))->toHaveCount(2)->and(implode(' ', $shoal($marine)))->toContain('Kerapu Segar')->not->toContain('Tuna Sirip Kuning');
    // an empty or hidden category is not offered
    $this->get('/products')->assertDontSee('?category=freshwater', false);

    // deleting a category keeps its products
    $deep->delete();
    expect($tuna->fresh()->category_id)->toBeNull();
    $this->get('/products')->assertSee('Yellowfin Tuna');
});

it('lets the product list change categories in bulk and shows the category column', function () {
    $freshwater = ProductCategory::query()->where('slug', 'freshwater')->firstOrFail();

    Livewire::test(ListProducts::class)
        ->assertSuccessful()
        ->assertTableColumnExists('category.name')
        ->callTableBulkAction('category', Product::all(), ['category_id' => $freshwater->id]);

    expect(Product::query()->where('category_id', $freshwater->id)->count())->toBe(Product::count());
});
