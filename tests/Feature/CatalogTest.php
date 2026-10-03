<?php

use App\Models\Cut;
use App\Models\ProcessingMethod;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Species;
use App\Models\User;
use Database\Seeders\SeafoodCatalogSeeder;
use Illuminate\Support\Facades\DB;

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
