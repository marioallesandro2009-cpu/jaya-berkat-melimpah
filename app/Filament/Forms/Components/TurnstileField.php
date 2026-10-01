<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * The Turnstile widget inside a Filament form (used on the admin login). The widget puts its
 * token into the field's state; the server checks it in App\Filament\Pages\Auth\Login.
 */
class TurnstileField extends Field
{
    protected string $view = 'filament.turnstile-field';
}
