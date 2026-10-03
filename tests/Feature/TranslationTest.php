<?php

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Support\AutoTranslate;
use App\Models\Product;
use App\Models\User;
use App\Support\FrontendData;
use App\Support\Translation\TranslationFailed;
use App\Support\Translation\Translator;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    seedSite();
    config(['services.translation.driver' => null, 'services.translation.key' => null, 'services.translation.url' => null]);
});

/** The seeded catalogue is fully translated; these tests need a product that still lacks its Indonesian texts. */
function withoutIndonesian(Product $product): Product
{
    foreach (Product::TRANSLATABLE as $field) {
        $values = (array) $product->getAttribute($field);
        unset($values['id']);
        $product->setAttribute($field, $values);
    }

    $product->setAttribute('translation_status', null);
    $product->save();

    return $product;
}

function useTranslator(string $driver): void
{
    config(['services.translation.driver' => $driver, 'services.translation.key' => 'test-key']);
}

it('is off until a driver and a key are configured', function () {
    expect(Translator::enabled())->toBeFalse();
    expect(fn () => Translator::draft('Hello', 'en', 'id'))->toThrow(TranslationFailed::class);

    useTranslator('anthropic');
    expect(Translator::enabled())->toBeTrue();

    config(['services.translation.driver' => 'unknown']);
    expect(Translator::enabled())->toBeFalse();
});

it('drafts a translation with each of the three services', function () {
    Http::fake([
        'api-free.deepl.com/*' => Http::response(['translations' => [['text' => 'Halo dunia']]]),
        'translation.googleapis.com/*' => Http::response(['data' => ['translations' => [['translatedText' => 'Halo dunia']]]]),
        'api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'Halo dunia']]]),
    ]);

    foreach (['deepl', 'google', 'anthropic'] as $driver) {
        config(['services.translation.driver' => $driver, 'services.translation.key' => $driver === 'deepl' ? 'abc:fx' : 'test-key']);

        expect(Translator::draft('Hello world', 'en', 'id'))->toBe('Halo dunia');
    }

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.anthropic.com/v1/messages')
        && $request->hasHeader('x-api-key', 'test-key')
        && $request['model'] === 'claude-haiku-4-5-20251001'
        && str_contains($request['system'], 'Indonesian'));
});

it('reports a refusal from the service instead of failing silently', function () {
    useTranslator('anthropic');
    Http::fake(['api.anthropic.com/*' => Http::response(['error' => 'bad key'], 401)]);

    expect(fn () => Translator::draft('Hello', 'en', 'id'))->toThrow(TranslationFailed::class, 'HTTP 401');
});

it('drafts every missing Indonesian text of the selected records, never over existing ones, and as drafts', function () {
    useTranslator('anthropic');
    Http::fake(['api.anthropic.com/*' => fn ($request) => Http::response(['content' => [['type' => 'text', 'text' => 'ID: '.$request['messages'][0]['content']]]])]);

    $loin = withoutIndonesian(Product::query()->where('product_code', 'YFT-LOIN-001')->firstOrFail());
    $loin->update(['intro' => ['en' => $loin->translation('intro', 'en'), 'id' => 'Sudah ditulis manual.']]);

    $result = AutoTranslate::run(Product::query()->whereKey($loin->id)->get(), 'id');
    $loin->refresh();

    expect($result['texts'])->toBeGreaterThan(0)->and($result['failed'])->toBeNull()
        ->and($loin->translation('description', 'id'))->toStartWith('ID: ')
        ->and($loin->translationState('description', 'id'))->toBe('draft')
        // an existing translation is left alone, product names stay English, rich text is skipped
        ->and($loin->translation('intro', 'id'))->toBe('Sudah ditulis manual.')
        ->and($loin->translation('name', 'id'))->toBeNull()
        ->and($result['skipped'])->toBeGreaterThan(0)
        ->and($loin->translation('content', 'id'))->toBeNull();

    // a draft is not shown on the site until it is checked
    $this->get('/id/produk/yellowfin-tuna-loin')->assertDontSee('ID: Trimmed');
    $loin->markTranslationsReviewed('id')->save();
    FrontendData::flush();
    $this->get('/id/produk/yellowfin-tuna-loin')->assertSee('ID: Trimmed');
});

it('stops cleanly when the service fails and says how many records are left', function () {
    useTranslator('anthropic');
    Http::fake(['api.anthropic.com/*' => Http::response([], 500)]);

    Product::query()->limit(3)->get()->each(fn (Product $product) => withoutIndonesian($product));

    $result = AutoTranslate::run(Product::query()->limit(3)->get(), 'id');

    expect($result['texts'])->toBe(0)->and($result['failed'])->toContain('HTTP 500')->and($result['left'])->toBe(3);
});

it('offers the bulk action in the product list and explains itself while the service is off', function () {
    $this->actingAs(User::query()->forceCreate(['name' => 'Admin', 'email' => 'tr@example.test', 'password' => 'x', 'is_admin' => true]));
    Http::fake();

    Livewire::test(ListProducts::class)
        ->assertTableBulkActionExists('draftMissing')
        ->callTableBulkAction('draftMissing', Product::query()->limit(2)->get())
        ->assertNotified('Terjemahan otomatis belum aktif');

    Http::assertNothingSent();
});
