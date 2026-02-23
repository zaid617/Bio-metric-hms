<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Schedule your application's commands.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Har mahine ke 1 tareekh ko 2 baje subah salary generate
       $schedule->command('app:generate-monthly-salaries')->monthlyOn(1, '2:00');

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
