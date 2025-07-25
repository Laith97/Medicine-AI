<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\CreateMonthlyInvoices;
use App\Jobs\SendInvoiceNotifications;
use App\Jobs\SyncStripeInvoices;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'doctor' => \App\Http\Middleware\EnsureUserIsDoctor::class,
            'stripe.configured' => \App\Http\Middleware\CheckStripeConfiguration::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Generate monthly invoices on the 1st of each month at 2 AM
        $schedule->job(new CreateMonthlyInvoices())->monthlyOn(1, '02:00');

        // Send invoice notifications daily at 9 AM
        $schedule->job(new SendInvoiceNotifications())->dailyAt('09:00');

        // Sync invoice statuses every 4 hours
        $schedule->job(new SyncStripeInvoices())->everyFourHours();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
