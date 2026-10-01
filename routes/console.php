<?php

use App\Models\SiteSetting;
use App\Support\FaviconIco;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('site:favicon-ico', function (): int {
    $media = SiteSetting::current()->getFirstMedia('favicon');

    if (! $media || ! FaviconIco::writeFromMedia($media)) {
        $this->error('favicon.ico was not rebuilt (no PNG/WebP/JPEG/ICO favicon uploaded, or GD is missing).');

        return 1;
    }

    $this->info('Rebuilt '.FaviconIco::path().' (16, 32, 48 px).');

    return 0;
})->purpose('Rebuild public/favicon.ico from the favicon uploaded in the admin');
