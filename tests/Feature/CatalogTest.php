<?php

use App\Http\Controllers\CreditsController;
use App\Models\ContactMessage;
use App\Models\Cut;
use App\Models\ProcessingMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Species;
use App\Models\User;
use Database\Seeders\SeafoodCatalogSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(fn () => seedSite());

it('seeds a consistent seafood catalogue', function () {
    expect(ProductCategory::count())->toBe(7)
        ->and(Species::count())->toBe(9)
        ->and(Cut::count())->toBe(17)
        ->and(ProcessingMethod::count())->toBe(8)
        ->and(Product::count())->toBe(31);

    // every product belongs to a category, species and cut, and the cut is sold for that species
    foreach (Product::query()->with('species')->get() as $product) {
        expect($product->species_id)->not->toBeNull()->and($product->cut_id)->not->toBeNull()
            ->and($product->category_id)->toBe($product->species->category_id)
            ->and(DB::table('cut_species')->where(['species_id' => $product->species_id, 'cut_id' => $product->cut_id, 'available_as_product' => 1])->exists())->toBeTrue()
            ->and($product->product_code)->toMatch('/^[A-Z]{3}-[A-Z]{3,4}-\d{3}$/')
            ->and(filter_var($product->image_url, FILTER_VALIDATE_URL))->not->toBeFalse()
            ->and($product->seo_title)->not->toBeEmpty()
            ->and($product->is_sample)->toBeTrue();
    }

    expect(Product::query()->distinct()->count('product_code'))->toBe(31)
        ->and(Product::query()->distinct()->count('slug'))->toBe(31)
        // a cut that exists for a species without being sold has no product
        ->and(DB::table('cut_species')->where('available_as_product', 0)->count())->toBeGreaterThan(0)
        ->and(Product::query()->whereHas('cut', fn ($q) => $q->where('slug', 'otoro'))->whereHas('species', fn ($q) => $q->where('slug', 'yellowfin-tuna'))->exists())->toBeFalse();
});

it('describes the demo yellowfin tuna loin like a processor would', function () {
    $loin = Product::query()->where('product_code', 'YFT-LOIN-001')->with(['processingMethods', 'species', 'cut'])->firstOrFail();

    expect($loin->translation('name', 'en'))->toBe('Yellowfin Tuna Loin')
        ->and($loin->species->scientific_name)->toBe('Thunnus albacares')
        ->and($loin->freezing_method)->toBe('super_frozen')
        ->and($loin->temperature)->toBe('-60°C')
        ->and($loin->processingMethods->map->translation('name', 'en')->all())->toEqualCanonicalizing(['Super Frozen', 'Skinless', 'Boneless', 'Trimmed'])
        ->and($loin->certification)->toBeNull();
});

it('is safe to run the catalogue seeder again and leaves rows edited in the admin alone', function () {
    Product::query()->where('product_code', 'YFT-LOIN-001')->update(['is_sample' => false, 'color' => 'edited in the admin']);

    $this->seed(SeafoodCatalogSeeder::class);

    expect(Product::count())->toBe(31)->and(Species::count())->toBe(9)->and(Cut::count())->toBe(17)
        ->and(DB::table('processing_method_product')->count())->toBe(96)
        ->and(Product::query()->where('product_code', 'YFT-LOIN-001')->value('color'))->toBe('edited in the admin');
});

it('lets an admin open the catalogue pages and edit a species with its cuts', function () {
    $this->actingAs(User::query()->forceCreate(['name' => 'Admin', 'email' => 'catalog@example.test', 'password' => 'x', 'is_admin' => true]));

    foreach (['/admin/species', '/admin/cuts', '/admin/processing-methods', '/admin/products', '/admin/product-categories'] as $path) {
        $this->get($path)->assertOk();
    }

    $yft = Product::query()->where('product_code', 'YFT-LOIN-001')->firstOrFail();
    $this->get("/admin/products/{$yft->id}/edit")->assertOk()->assertSee('Katalog');
});

it('serves species pages and filters the catalogue', function () {
    $this->get('/products/tuna/yellowfin-tuna')->assertOk()
        ->assertSee('Thunnus albacares')->assertSee('Kihada Maguro')->assertSee('Yellowfin Tuna Loin')->assertSee('Bluefin Tuna');
    $this->get('/id/produk/salmon/atlantic-salmon')->assertOk()->assertSee('Salmo salar');
    // wrong category for the species, or unknown slugs
    $this->get('/products/salmon/yellowfin-tuna')->assertNotFound();
    $this->get('/products/nope/none')->assertNotFound();
    // species pages are in the sitemap
    $this->get('/sitemap.xml')->assertSee('/products/tuna/yellowfin-tuna', false)->assertSee('/id/produk/tuna/yellowfin-tuna', false);

    $names = function (string $html): array {
        preg_match_all('/class="prow-name">(.*?)<\/h4>/s', $html, $m);

        return array_map(fn (string $name): string => trim(strip_tags($name)), $m[1]);
    };

    expect($names($this->get('/products')->getContent()))->toHaveCount(31)
        ->and($names($this->get('/products?species=yellowfin-tuna')->getContent()))->toHaveCount(6)
        ->and($names($this->get('/products?cut=saku')->getContent()))->toHaveCount(7)
        ->and($names($this->get('/products?storage=fresh')->getContent()))->toHaveCount(7)
        ->and($names($this->get('/products?cut=saku&storage=super_frozen')->getContent()))->toHaveCount(3)
        // odd values never break the page and just show everything
        ->and($names($this->get('/products?species[]=x&cut=%00&grade=nope')->getContent()))->toHaveCount(31);
});

it('accepts an inquiry list and keeps it in the lead message', function () {
    $this->postJson('/contact', [
        'name' => 'Ann Buyer', 'company' => 'Buyer Co', 'email' => 'ann@buyer.test', 'country' => 'Japan', 'product' => 'Other / multiple',
        'items' => "Yellowfin Tuna Loin (YFT-LOIN-001)\nBluefin Tuna Otoro (BFT-OTO-001)\n\x00bad\x1Fline",
        'message' => 'Need a quote for both.',
    ])->assertOk();

    $lead = ContactMessage::query()->latest('id')->firstOrFail();

    expect($lead->message)->toStartWith("Products of interest:\n- Yellowfin Tuna Loin (YFT-LOIN-001)\n- Bluefin Tuna Otoro (BFT-OTO-001)\n- bad line")
        ->and($lead->message)->toContain('Need a quote for both.')
        ->and($lead->product_title)->toBe('Other / multiple');
});

it('has category pages, a search box and a compact view', function () {
    $this->get('/products/tuna')->assertOk()->assertSee('Yellowfin Tuna')->assertSee('Bluefin Tuna')->assertSee('Bigeye Tuna')->assertSee('15 products');
    $this->get('/id/produk/salmon')->assertOk()->assertSee('Atlantic Salmon');
    // a product page with the same slug wins over a category page; unknown slugs are 404
    $this->get('/products/yellowfin-tuna-loin')->assertOk()->assertSee('Specifications');
    $this->get('/products/not-a-category')->assertNotFound();
    $this->get('/sitemap.xml')->assertSee('/products/tuna<', false)->assertSee('/id/produk/salmon<', false);

    $names = function (string $html): array {
        preg_match_all('/class="prow-name">(.*?)<\/h4>/s', $html, $m);

        return array_map(fn (string $name): string => trim(strip_tags($name)), $m[1]);
    };

    expect($names($this->get('/products?q=otoro')->getContent()))->toBe(['Bluefin Tuna Otoro'])
        ->and($names($this->get('/products?q=THUNNUS%20thynnus')->getContent()))->toHaveCount(5)
        ->and($names($this->get('/products?q=yft-saku')->getContent()))->toBe(['Yellowfin Tuna Saku'])
        ->and($names($this->get('/products?q='.str_repeat('x', 500))->getContent()))->toBe([]);

    $this->get('/products')->assertSee('class="shoal"', false);
    $this->get('/products?view=compact')->assertDontSee('class="shoal"', false)->assertSee('prow-name', false);
    $this->get('/products?q=otoro')->assertDontSee('class="shoal"', false);
});

it('has the whole catalogue in Indonesian, checked, so /id shows it', function () {
    foreach ([Product::class, Species::class, ProductCategory::class, Cut::class, ProcessingMethod::class] as $model) {
        foreach ($model::query()->get() as $record) {
            expect($record->missingTranslations('id'))->toBe([])->and($record->draftTranslations('id'))->toBe([]);
        }
    }

    $this->get('/id/produk/yellowfin-tuna-loin')->assertOk()
        ->assertSee('Loin Yellowfin Tuna yang dirapikan')->assertSee('Potongan')->assertSee('Penyimpanan')->assertSee('Divakum satuan, master carton');
    $this->get('/id/produk/tuna')->assertOk()->assertSee('Tuna madidihang');
    $this->get('/id/produk')->assertOk()->assertSee('Ikan Sashimi Lainnya');
});

it('lists the credit of every free-licence photo on a public page and links it from the footer', function () {
    $this->get('/photo-credits')->assertOk()->assertSee('Photo credits')
        ->assertSee('Corte de atún-10.jpg')->assertSee('Tamorlan')->assertSee('CC BY 3.0')
        ->assertSee('https://creativecommons.org/licenses/by/3.0/', false)
        ->assertSee('Photographs were cropped and resized');
    $this->get('/id/kredit-foto')->assertOk()->assertSee('Kredit foto')->assertSee('Penulis');
    $this->get('/')->assertSee('/photo-credits', false);

    expect(CreditsController::licenseUrl('CC BY-SA 4.0'))->toBe('https://creativecommons.org/licenses/by-sa/4.0/')
        ->and(CreditsController::licenseUrl('CC BY-SA 3.0 it'))->toBe('https://creativecommons.org/licenses/by-sa/3.0/it/')
        ->and(CreditsController::licenseUrl('CC0'))->toBe('https://creativecommons.org/publicdomain/zero/1.0/')
        ->and(CreditsController::licenseUrl('Public domain'))->toBeNull();
});

it('drops the credits when the photos are deleted in the admin', function () {
    expect(count(CreditsController::credits()))->toBeGreaterThan(30);

    foreach (Media::query()->get() as $media) {
        $media->delete();
    }

    Cache::forget('jbm.photo-credits.exists');

    expect(CreditsController::credits())->toBe([])
        ->and(CreditsController::exists())->toBeFalse();
    $this->get('/')->assertDontSee('/photo-credits', false);
    $this->get('/photo-credits')->assertOk()->assertSee('No photographs with a credit');
});
