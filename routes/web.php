<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\GuardContactForm;
use App\Http\Middleware\SetLocale;
use App\Support\Locales;
use Illuminate\Support\Facades\Route;

// Public pages in every language: English at the root (/), the others under
// their prefix (/id). Route names: "home" for English, "id.home" for Indonesian.
foreach (Locales::all() as $locale) {
    $prefix = Locales::prefix($locale);
    $segment = fn (string $name): string => '/'.config("site.locales.{$locale}.segments.{$name}", $name);

    Route::middleware(SetLocale::class.':'.$locale)
        ->prefix($prefix)
        ->name($prefix === '' ? '' : $prefix.'.')
        ->group(function () use ($segment): void {
            Route::get('/', HomeController::class)->name('home');
            // /company, /id/perusahaan
            Route::get($segment('company'), CompanyController::class)->name('company');
            // All products: /products, /id/produk
            Route::get($segment('products'), [ProductController::class, 'index'])->name('products.index');
            // Species page: /products/tuna/yellowfin-tuna, /id/produk/tuna/yellowfin-tuna
            Route::get($segment('products').'/{category}/{species}', [ProductController::class, 'species'])
                ->where(['category' => '[a-z0-9-]+', 'species' => '[a-z0-9-]+'])
                ->name('products.species');
            // Product page: /products/{slug}, /id/produk/{slug} (the catalogue is the home page's #products block)
            Route::get($segment('products').'/{slug}', [ProductController::class, 'show'])
                ->where('slug', '[a-z0-9-]+')
                ->name('products.show');
            // News index and article: /news, /news/{slug}, /id/berita, /id/berita/{slug}
            Route::get($segment('news'), [NewsController::class, 'index'])->name('news.index');
            Route::get($segment('news').'/{slug}', [NewsController::class, 'show'])
                ->where('slug', '[a-z0-9-]+')
                ->name('news.show');
            // Inquiry form submit: /contact, /id/kontak. Limited to 5 per 15 minutes per IP
            // (limiter "contact", AppServiceProvider); works as plain POST and as fetch.
            Route::post($segment('contact'), ContactController::class)
                ->middleware(['throttle:contact', GuardContactForm::class])
                ->name('contact.store');
        });
}

// Machine-readable files for search engines and AI crawlers: no language prefix,
// written in the default language (English); the sitemap lists every language.
Route::middleware(SetLocale::class.':'.Locales::default())->group(function (): void {
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
    Route::get('/llms.txt', [SeoController::class, 'llms'])->name('llms');
    Route::get('/site.webmanifest', [SeoController::class, 'manifest'])->name('manifest');
});
