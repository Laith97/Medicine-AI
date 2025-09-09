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
