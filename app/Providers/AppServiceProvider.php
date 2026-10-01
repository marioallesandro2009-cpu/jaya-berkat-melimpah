<?php

namespace App\Providers;

use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\FaviconIco;
use App\Support\FrontendData;
use App\Support\Links;
use App\Support\MediaPresenter;
use App\Support\SecurityLog;
use App\Support\TrustedProxies;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;
use Spatie\MediaLibrary\MediaCollections\Events\MediaHasBeenAddedEvent;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMedia();
        $this->configureRateLimiting();
        $this->configureMail();
        $this->configureRequestSecurity();
        $this->configureSecurityLogging();

        // <x-layouts::app> resolves to resources/views/layouts/app.blade.php.
        Blade::anonymousComponentPath(resource_path('views/layouts'), 'layouts');
    }

    /**
     * https and proxies from config/security.php (read here, not in bootstrap/app.php, so it keeps
     * working after `config:cache`): FORCE_HTTPS makes every generated URL https; TRUSTED_PROXIES
     * lets Laravel read the visitor's real IP behind Cloudflare (default: trust nobody).
     */
    protected function configureRequestSecurity(): void
    {
        if (config('security.force_https')) {
            URL::forceScheme('https');
        }

        $proxies = TrustedProxies::resolve((string) config('security.trusted_proxies'));

        if ($proxies !== null) {
            TrustProxies::at($proxies);
        }
    }

    /**
     * Security events go to storage/logs/security.log (App\Support\SecurityLog): who logged in,
     * failed, got locked out or logged out; changes to accounts; deleted leads; uploads.
     */
    protected function configureSecurityLogging(): void
    {
        Event::listen(Login::class, fn (Login $event) => SecurityLog::event('login.success', ['user' => $event->user->getAuthIdentifier(), 'remember' => $event->remember]));
        Event::listen(Failed::class, fn (Failed $event) => SecurityLog::event('login.failed', [
            'user' => $event->user?->getAuthIdentifier(),
            'email' => SecurityLog::maskEmail($event->credentials['email'] ?? null),
        ]));
        Event::listen(Lockout::class, fn () => SecurityLog::event('login.lockout'));
        Event::listen(Logout::class, fn (Logout $event) => SecurityLog::event('logout', ['user' => $event->user->getAuthIdentifier()]));

        User::created(fn (User $user) => SecurityLog::event('user.created', ['user' => $user->id, 'is_admin' => $user->is_admin]));
        User::updated(function (User $user): void {
            $fields = array_values(array_intersect(array_keys($user->getChanges()), ['name', 'email', 'password', 'is_admin']));

            if ($fields !== []) {
                SecurityLog::event('user.updated', ['user' => $user->id, 'fields' => $fields]);
            }
        });
        User::deleted(fn (User $user) => SecurityLog::event('user.deleted', ['user' => $user->id]));
        ContactMessage::deleted(fn (ContactMessage $lead) => SecurityLog::event('lead.deleted', ['lead' => $lead->id]));
    }

    /**
     * Text that visitors type (name, message...) goes into Markdown mails. With secured
     * encoding every interpolated value is escaped so it cannot become a live link, an
     * external image or HTML in the team's inbox (see tests/Feature/SecurityXssTest.php).
     */
    protected function configureMail(): void
    {
        Markdown::withSecuredEncoding();
    }

    /**
     * Contact form: 5 submissions per 15 minutes per IP. A blocked visitor gets the
     * form's own error message (JSON for fetch, flash + redirect for a plain POST),
     * not a bare 429 page.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('contact', fn (Request $request): Limit => Limit::perMinutes(15, 5)
            ->by((string) $request->ip())
            ->response(function (Request $request, array $headers) {
                $text = SiteSetting::current()->uiTexts(app()->getLocale())['contact_error'];

                if ($request->expectsJson()) {
                    return response()->json(['message' => $text], 429, $headers);
                }

                return redirect(Links::section('contact'))->with('contact_error', $text)->withHeaders($headers);
            }));
    }

    /**
     * Keep the front page cache (and public/favicon.ico) in sync with uploaded media.
     */
    protected function configureMedia(): void
    {
        Event::listen(MediaHasBeenAddedEvent::class, function (MediaHasBeenAddedEvent $event): void {
            if (Auth::check()) {
                SecurityLog::event('upload.added', ['collection' => $event->media->collection_name, 'file' => $event->media->file_name, 'mime' => $event->media->mime_type, 'size' => $event->media->size]);
            }

            MediaPresenter::storeDimensions($event->media);
            FrontendData::flush();

            if ($event->media->collection_name === 'favicon' && $event->media->model_type === (new SiteSetting)->getMorphClass()) {
                FaviconIco::writeFromMedia($event->media);
            }
        });

        Event::listen(ConversionHasBeenCompletedEvent::class, fn () => FrontendData::flush());

        Media::saved(fn () => FrontendData::flush());
        Media::deleted(function (Media $media): void {
            FrontendData::flush();

            if (Auth::check()) {
                SecurityLog::event('upload.deleted', ['collection' => $media->collection_name, 'file' => $media->file_name]);
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // One strong rule for every password the app sets (admin:create, the admin profile,
        // the local seeder). Only the leaked-password lookup (an online check) is left out of
        // local development and tests.
        Password::defaults(function (): Password {
            $rule = Password::min(12)->mixedCase()->letters()->numbers()->symbols();

            return app()->isProduction() ? $rule->uncompromised() : $rule;
        });
    }
}
