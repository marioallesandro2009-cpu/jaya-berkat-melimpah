<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Support\FrontendData;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            // ADMIN_PATH in .env (default "admin"): hides the address, it is not a lock.
            ->path(config('security.admin_path'))
            ->login(Login::class)
            // Own name/email/password page (strong-password rule); without it a password could only
            // be changed from the command line.
            ->profile()
            ->brandName(fn (): string => self::brandName())
            // Ocean scale around the site's secondary ocean colour (#123A43): Filament draws buttons
            // and links with 600.
            ->colors([
                'primary' => [
                    50 => '#EEF8FB',
                    100 => '#D9EEF4',
                    200 => '#B5DDE9',
                    300 => '#82C4D8',
                    400 => '#48A0BD',
                    500 => '#2A7F9C',
                    600 => '#1E6680',
                    700 => '#184F65',
                    800 => '#123A43',
                    900 => '#0C2830',
                    950 => '#071A21',
                ],
            ])
            ->navigationGroups(['Konten Beranda', 'Katalog Produk', 'Halaman Perusahaan', 'Tampilan'])
            // Small AA fix for Filament's placeholder colour (see the view).
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => view('filament.admin-styles')->render())
            // Waves and bubbles behind the login card.
            ->renderHook(PanelsRenderHook::BODY_END, fn (): string => view('filament.ocean-decor')->render())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * "<company name> Admin", from Pengaturan Situs (cached front page data).
     */
    private static function brandName(): string
    {
        return FrontendData::settings()['companyName'].' Admin';
    }
}
