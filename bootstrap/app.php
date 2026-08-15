<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\CheckPlatformMaintenance::class,
            \App\Http\Middleware\HandleCurrency::class,
            \App\Http\Middleware\Log403Responses::class,
        ]);

        // This stops the "Page Expired" error on the login screen
        $middleware->validateCsrfTokens(except: [
            'admin/login',
            'super-admin/login',
            'api/payments/mpesa/callback', // Allow Safaricom callbacks
            'api/webhooks/flutterwave',    // Allow Flutterwave webhooks
        ]);

        $middleware->alias([
            'superadmin' => \App\Http\Middleware\EnsureUserIsSuperAdmin::class,
            'user.security' => \App\Http\Middleware\EnsureUserSecurity::class,
            'twofactor' => \App\Http\Middleware\EnsureTwoFactorVerified::class,
            'feature' => \App\Http\Middleware\EnsureFeatureEnabled::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Payment Ecosystem Scheduled Jobs
        $schedule->job(new \App\Jobs\ProcessSettlementJob)->dailyAt('02:00');
        // Financial reconciliation would normally be a service call here
        $schedule->job(new \App\Jobs\GenerateSettlementReportJob(null))->dailyAt('04:00');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
