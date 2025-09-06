<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Daily cleanup for ambient audio retention
        $schedule->command('ambient:cleanup --days=14')->dailyAt('03:10');
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
