<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    seedSite();
});

it('shows the 3D hero by default', function () {
    // the bundle is built by `npm run build`; without it the page (correctly) falls back to the photograph
    if (! is_file(public_path('js/ocean3d.min.js'))) {
        $this->markTestSkipped('public/js/ocean3d.min.js is not built.');
    }

    $this->get('/')->assertOk()->assertSee('data-ocean3d', false)->assertSee('ocean3d.min.js', false);
    expect(SiteSetting::current()->toFrontend()['heroMode'] ?? null)->toBe('3d');
});

it('lets the admin switch the home hero between the 3D animation and the photograph', function () {
    if (! is_file(public_path('js/ocean3d.min.js'))) {
        $this->markTestSkipped('public/js/ocean3d.min.js is not built.');
    }

    $this->actingAs(User::query()->forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'x', 'is_admin' => true]));

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['hero_mode' => 'photo'])
        ->call('save')
        ->assertHasNoFormErrors();

    // photograph mode: no 3D layer and no 3D script, but the hero (photo, headline, calls to action) is still there
    $this->get('/')->assertOk()
        ->assertDontSee('data-ocean3d', false)
        ->assertDontSee('ocean3d.min.js', false)
        ->assertSee('hero-title', false);

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['hero_mode' => '3d'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get('/')->assertOk()->assertSee('data-ocean3d', false);
});

it('refuses an unknown hero mode and falls back to 3D if one ever reaches the database', function () {
    $this->actingAs(User::query()->forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'x', 'is_admin' => true]));

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['hero_mode' => 'hologram'])
        ->call('save')
        ->assertHasFormErrors(['hero_mode']);

    SiteSetting::query()->update(['hero_mode' => 'hologram']);

    expect(SiteSetting::current()->fresh()->toFrontend()['heroMode'])->toBe('3d');
});
