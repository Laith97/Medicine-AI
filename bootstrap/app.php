<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Broadcasting\BroadcastServiceProvider;
use Laravel\Sanctum\SanctumServiceProvider;
use App\Jobs\CreateMonthlyInvoices;
use App\Jobs\SendInvoiceNotifications;
use App\Jobs\SyncStripeInvoices;
use App\Jobs\ProcessOverdueInvoices;
use App\Jobs\ProcessInvoicePayments;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withProviders([
        SanctumServiceProvider::class,
        BroadcastServiceProvider::class,
    ])
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
            'eligibility.rate' => \App\Http\Middleware\EligibilityRateLimit::class,
            'eligibility.access' => \App\Http\Middleware\EligibilityAccessControl::class,
            'hep.rate' => \App\Http\Middleware\HEPRateLimit::class,
            'billing.rate' => \App\Http\Middleware\BillingRateLimit::class,
            'kiosk.rate-limit' => \App\Http\Middleware\KioskRateLimit::class,
            'kiosk.session-isolation' => \App\Http\Middleware\KioskSessionIsolation::class,
            'analytics.access' => \App\Http\Middleware\AnalyticsAccess::class,
            'metrics.collection' => \App\Http\Middleware\MetricsCollectionMiddleware::class,
        ]);

        // Apply access restrictions to authenticated routes
        $middleware->appendToGroup('web', \App\Http\Middleware\CheckAccessRestrictions::class);

        // Apply metrics collection to all web and api routes
        $middleware->appendToGroup('web', \App\Http\Middleware\MetricsCollectionMiddleware::class);
        $middleware->appendToGroup('api', \App\Http\Middleware\MetricsCollectionMiddleware::class);

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

        // Check for expiring eligibility daily at 3 AM
        $schedule->command('eligibility:check-expiring')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Refresh eligibility for recurring appointments daily at 4 AM
        $schedule->command('eligibility:refresh-recurring')
            ->dailyAt('04:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Process eligibility data retention monthly on the 1st at 2 AM
        $schedule->command('eligibility:retention-process --days=365')
            ->monthlyOn(1, '02:00')
            ->withoutOverlapping()
            ->runInBackground();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
