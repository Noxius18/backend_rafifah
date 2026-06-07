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
        // Send zoom reminders every minute for exams starting in next 5 minutes
        $schedule->command('zoom:send-reminders')
                 ->everyMinute()
                 ->withoutOverlapping();

        // Allow manual triggering of bulk results - this will be triggered by user button click
        // The actual scheduling for bulk results will be done via the web interface where
        // the user selects date/time and clicks a button to trigger the command
        // For demo purposes, we can also schedule it to run at specific times if needed
        // $schedule->command('results:send-bulk --date=yesterday')
        //          ->dailyAt('08:00')
        //          ->withoutOverlapping();
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