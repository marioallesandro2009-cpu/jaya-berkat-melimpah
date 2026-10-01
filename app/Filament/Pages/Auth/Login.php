<?php

namespace App\Filament\Pages\Auth;

use App\Filament\Forms\Components\TurnstileField;
use App\Support\LoginThrottle;
use App\Support\SecurityLog;
use App\Support\Turnstile;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Validation\ValidationException;

/**
 * Admin login: Filament's own page plus (SECURITY_AUDIT C-1, C-5)
 *
 * - account-aware lockout (LoginThrottle: email+IP, email alone, IP alone), with one generic
 *   message that never reveals whether the email exists;
 * - an optional Turnstile check, only when TURNSTILE_ENABLED=true.
 *
 * Filament still does the rest: its per-minute limit, the constant-time credential check,
 * the session regeneration and the panel-access check.
 */
class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getRememberFormComponent(),
            ...(Turnstile::enabled() ? [TurnstileField::make('turnstile')->hiddenLabel()] : []),
        ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $throttle = new LoginThrottle((string) request()->ip(), (string) ($this->data['email'] ?? ''));

        if (($seconds = $throttle->secondsUntilAvailable()) > 0) {
            $this->notifyLocked($seconds);

            return null;
        }

        if (! Turnstile::passes($this->data['turnstile'] ?? null, request()->ip())) {
            $this->dispatch('turnstile-reset');

            throw ValidationException::withMessages(['data.turnstile' => 'Verifikasi keamanan gagal. Coba lagi.']);
        }

        try {
            $response = parent::authenticate();
        } catch (ValidationException $e) {
            $throttle->fail();
            $this->dispatch('turnstile-reset');

            if ($throttle->secondsUntilAvailable() > 0) {
                event(new Lockout(request()));
            }

            throw $e;
        }

        if ($response !== null) {
            $throttle->clear();
        }

        return $response;
    }

    private function notifyLocked(int $seconds): void
    {
        SecurityLog::event('login.blocked', ['email' => SecurityLog::maskEmail($this->data['email'] ?? null), 'retry_after' => $seconds]);

        Notification::make()
            ->title('Terlalu banyak percobaan login')
            ->body('Coba lagi dalam '.max(1, (int) ceil($seconds / 60)).' menit.')
            ->danger()
            ->send();
    }
}
