<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        // Shared hosting can invoke a single cron every minute; the scheduler
        // then fans out to reminder commands and a short-lived queue worker.
        $schedule->command('zoom:send-reminders')
                 ->everyMinute()
                 ->withoutOverlapping();

        $schedule->command('queue:work database --stop-when-empty --tries=3 --max-time=50')
                 ->everyMinute()
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
