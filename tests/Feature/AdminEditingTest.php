<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\PageSections\Pages\ManagePageSections;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Stats\Pages\ManageStats;
use App\Models\PageSection;
use App\Models\Product;
use App\Models\Stat;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    seedSite();

    $this->actingAs(User::query()->forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'x', 'is_admin' => true]));
});

it('changes a product in the admin and the public page follows', function () {
    $product = Product::query()->where('is_featured', true)->orderBy('sort_order')->firstOrFail();

    Livewire::test(EditProduct::class, ['record' => $product->getKey()])
        ->fillForm([
            'name' => ['en' => 'Fresh Grouper Premium', 'id' => 'Kerapu Segar Premium'],
            'description' => ['en' => 'New description.', 'id' => 'Deskripsi baru.'],
            'is_active' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    // an Indonesian text is shown on /id once someone has checked it (the catalogue is seeded in English only)
    $product->fresh()->markTranslationsReviewed('id')->save();

    $this->get('/')->assertSee('Fresh Grouper Premium');
    $this->get('/id')->assertSee('Kerapu Segar Premium');
});

it('hides a product from the site when it is switched off', function () {
    $product = Product::query()->where('is_featured', true)->orderBy('sort_order')->firstOrFail();
    $name = $product->translation('name', 'en');

    $this->get('/')->assertSee($name);

    $product->update(['is_active' => false]);

    $this->get('/')->assertDontSee($name);
});

it('edits the hero headline through the page sections list', function () {
    $hero = PageSection::query()->where('key', 'hero')->firstOrFail();

    Livewire::test(ManagePageSections::class)
        ->callTableAction('edit', $hero, data: [
            'title' => ['en' => 'A brand new headline.', 'id' => 'Judul baru.'],
            'is_active' => true,
        ])
        ->assertHasNoTableActionErrors();

    $this->get('/')->assertSee('A brand new headline.');
});

it('clears the sample marker once a dummy figure is saved', function () {
    $stat = Stat::query()->whereNotNull('value')->orderBy('sort_order')->firstOrFail();
    expect($stat->is_sample)->toBeTrue();

    Livewire::test(ManageStats::class)
        ->callTableAction('edit', $stat, data: [
            'label' => ['en' => 'Years of experience', 'id' => 'Tahun pengalaman'],
            'value' => 16,
            'suffix' => '+',
            'is_active' => true,
        ])
        ->assertHasNoTableActionErrors();

    expect($stat->fresh()->is_sample)->toBeFalse();
});

it('saves the site settings', function () {
    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['email' => 'sales@jbmelimpah.test', 'phone' => '+62 21 000 0000'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get('/')->assertSee('sales@jbmelimpah.test');
});
