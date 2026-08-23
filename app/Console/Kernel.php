<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Schedule nightly batch processing of pending claims
        $schedule->command('billing:process-pending-claims')
            ->dailyAt('02:00') // Run at 2:00 AM daily
            ->withoutOverlapping() // Prevent overlapping executions
            ->runInBackground(); // Run in background

        // Clean up expired kiosk sessions every 15 minutes
        $schedule->job(\App\Jobs\CleanupExpiredKioskSessions::class)
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Process workflow tasks and send reminders every hour
        $schedule->job(\App\Jobs\ProcessWorkflowTasks::class)
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        // Process claim workflow automation every 4 hours
        $schedule->job(\App\Jobs\ProcessClaimWorkflowAutomation::class)
            ->everyFourHours()
            ->withoutOverlapping()
            ->runInBackground();

        // Run system monitoring every 5 minutes
        $schedule->command('monitor:system --all')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();

        // Train ML models daily at 2 AM
        $schedule->command('models:train')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->evenInMaintenanceMode();

        // Retrain ML models (production pipeline with balancing & evaluation) daily 02:30
        $schedule->command('predictions:retrain')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->evenInMaintenanceMode();

        // Check model health every 6 hours
        $schedule->command('models:health')
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();

        // Generate predictions for next 7 days daily at 3 AM
        $schedule->command('predictions:generate')
            ->dailyAt('03:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->evenInMaintenanceMode();

        // Optional: Add other billing-related scheduled tasks here
        // For example, cleanup old alerts, generate reports, etc.
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
