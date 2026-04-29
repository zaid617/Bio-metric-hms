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
        // Scheduled device sync every 6 hours (0 */6 * * *)
        $schedule->command('attendance:schedule-sync')
            ->everySixHours()
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}
