<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal untuk reminder zoom otomatis
Schedule::command('zoom:send-reminders')
    ->everyMinute()
    ->withoutOverlapping();

// Contoh jika fitur bulk results butuh dijalankan otomatis:
// Schedule::command('results:send-bulk --date=yesterday')
//     ->dailyAt('08:00')
//     ->withoutOverlapping();