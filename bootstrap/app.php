<?php

use App\Http\Middleware\EnforceCanonicalHost;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Needs one cron entry on the server: * * * * * php artisan schedule:run (DEPLOY.md, A13).
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('leads:prune')->dailyAt('03:15')->withoutOverlapping();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Redirects to the scheme + host of APP_URL (https, www or non-www) in production.
        $middleware->prepend(EnforceCanonicalHost::class);
        // Security headers (HSTS, X-Frame-Options, report-only CSP) on every response.
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Never flash these back into a form after a validation error.
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation', 'passwordConfirmation', 'currentPassword', 'cf-turnstile-response', 'turnstile']);

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
