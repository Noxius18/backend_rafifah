<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalTes;
use App\Models\User;
use Carbon\Carbon;
use Mail;
use App\Mail\ZoomLinkReminder;

class SendZoomReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zoom:send-reminders {--force : Send reminders regardless of timing window}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Zoom link email reminders for upcoming exams';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();
        $sent = 0;
        $window = 5; // minutes

        // Find all jadwal for today that have link_zoom
        $jadwals = JadwalTes::where('tanggal', $now->toDateString())
            ->whereNotNull('link_zoom')
            ->get();

        foreach ($jadwals as $jadwal) {
            // Convert jam string to Carbon object
            $jadwalTime = Carbon::parse($jadwal->jam);
            $jadwalDateTime = $now->copy()->setTimeFrom($jadwalTime);

            // Calculate 1 hour before jadwal time
            $reminderTime = $jadwalDateTime->subHour();

            // Check if we're within the window (now >= reminderTime AND <= reminderTime + window)
            if ($now->gte($reminderTime) && $now->lte($reminderTime->addMinutes($window))) {
                // Check if reminder hasn't been sent yet OR forced
                if (!$this->option('force') && $jadwal->zoom_reminder_sent) {
                    continue; // skip already sent (unless forced)
                }

                $mahasantri = $jadwal->mahasantri;
                if ($mahasantri) {
                    Mail::to($mahasantri->email)->send(new ZoomLinkReminder($jadwal, $mahasantri));
                    // Mark reminder as sent
                    $jadwal->update(['zoom_reminder_sent' => true]);
                    $sent++;
                    $this->line("Sent zoom reminder to {$mahasantri->nama_lengkap} for {$jadwal->tanggal} {$jadwal->jam}");
                }
            }
        }

        $this->info("Evaluated {$jadwals->count()} exams; sent reminder to {$sent}.");
        return 0;
    }
}