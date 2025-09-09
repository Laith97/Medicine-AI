<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use App\Jobs\CreateMonthlyInvoices;
use App\Jobs\SendInvoiceNotifications;
use App\Jobs\SyncStripeInvoices;
use App\Jobs\ProcessOverdueInvoices;
use App\Jobs\ProcessInvoicePayments;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'admin.impersonation' => \App\Http\Middleware\AdminImpersonation::class,
            'doctor' => \App\Http\Middleware\EnsureUserIsDoctor::class,
            'patient' => \App\Http\Middleware\EnsureUserIsPatient::class,
            'hospital.admin' => \App\Http\Middleware\HospitalAdminMiddleware::class,
            'payment.responsible' => \App\Http\Middleware\PaymentResponsibleMiddleware::class,
            'role' => \App\Http\Middleware\EnsureUserRole::class,
            'stripe.configured' => \App\Http\Middleware\CheckStripeConfiguration::class,
            'access.restrictions' => \App\Http\Middleware\CheckAccessRestrictions::class,
            'sub.user.permissions' => \App\Http\Middleware\CheckSubUserPermissions::class,
        ]);

        // Apply access restrictions to authenticated routes
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckAccessRestrictions::class);

        // Handle doctor domains and subdomains
        $middleware->prependToGroup('web', \App\Http\Middleware\HandleDoctorDomains::class);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Generate monthly invoices on the 1st of each month at 2 AM
        $schedule->job(new CreateMonthlyInvoices())->monthlyOn(1, '02:00');

        // Process overdue invoices and send reminders daily at 9 AM
        $schedule->job(new ProcessOverdueInvoices())->dailyAt('09:00');

        // Process invoice payments and remove restrictions every 2 hours
        $schedule->job(new ProcessInvoicePayments())->everyTwoHours();

        // Send invoice notifications daily at 10 AM
        $schedule->job(new SendInvoiceNotifications())->dailyAt('10:00');

        // Sync invoice statuses every 4 hours
        $schedule->job(new SyncStripeInvoices())->everyFourHours();

        // Process expired trials daily at 1 AM
        $schedule->command('trials:process-expired')->dailyAt('01:00');

        // Process pending claims for denial risk scoring and underpayment detection daily at 2 AM
        $schedule->command('billing:process-pending-claims')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
