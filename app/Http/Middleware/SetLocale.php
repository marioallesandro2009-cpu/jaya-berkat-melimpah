<?php

namespace App\Http\Middleware;

use App\Support\Locales;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the language from the URL (route group), never from the browser or IP.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next, string $locale): Response
    {
        $locale = in_array($locale, Locales::all(), true) ? $locale : Locales::default();

        app()->setLocale($locale);
        Carbon::setLocale($locale);
        Number::useLocale($locale);

        return $next($request);
    }
}
