<?php

use App\Filament\Pages\ManageSiteSettings;
use App\Models\SiteSetting;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    seedSite();
});

function setWhatsapp(?string $number, ?string $message = null): void
{
    SiteSetting::query()->update(['whatsapp_number' => $number, 'whatsapp_message' => $message]);
    SiteSetting::current()->touch();
}

it('shows the floating WhatsApp button with the number and a default greeting', function () {
    setWhatsapp('+62 812 3456 7890');

    $this->get('/')->assertOk()
        ->assertSee('class="wa-float"', false)
        ->assertSee('https://wa.me/6281234567890?text=', false)
        ->assertSee('has-wa', false);
});

it('leaves the button out when no WhatsApp number is set', function () {
    setWhatsapp(null);

    $this->get('/')->assertOk()->assertDontSee('wa-float', false);
});

it('types the admin-edited message into the chat', function () {
    setWhatsapp('0812 3456 7890', 'Halo JBM, saya mau tanya harga tuna.');

    $this->get('/')->assertSee('wa.me/6281234567890?text='.rawurlencode('Halo JBM, saya mau tanya harga tuna.'), false);
});

it('lets the admin change the number and the message', function () {
    $this->actingAs(User::query()->forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'x', 'is_admin' => true]));

    Livewire::test(ManageSiteSettings::class)
        ->fillForm(['whatsapp_number' => '+62 811 1111 2222', 'whatsapp_message' => 'Selamat siang, minta penawaran.'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->get('/')->assertSee('wa.me/6281111112222?text='.rawurlencode('Selamat siang, minta penawaran.'), false);
});

it('folds the optional contact fields into one block that is open without JavaScript', function () {
    $this->get('/')->assertOk()
        ->assertSee('<details class="field-more" open>', false)
        ->assertSee('id="f-phone"', false)
        ->assertSee('id="f-volume"', false)
        ->assertSee('id="f-message"', false);
});
