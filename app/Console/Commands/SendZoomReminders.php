<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Models\JadwalTes;
use App\Mail\ZoomLinkReminder;
use App\Models\ScheduleStatus;

class SendZoomReminders extends Command
{
    protected $signature = 'zoom:send-reminders {--force}';
    protected $description = 'Kirim reminder link zoom 3 hari sebelum ujian untuk jadwal yang sudah Disetujui';

    public function handle()
    {
        $targetDate = Carbon::now()->addDays(3)->toDateString();
        $sent = 0;

        // Cari jadwal Disetujui yang ujiannya 3 hari lagi, punya link_zoom, dan belum dikirim reminder
        $jadwals = JadwalTes::with('mahasantri')
            ->where('tanggal', $targetDate)
            ->where('status_jadwal', 'Disetujui')
            ->whereNotNull('link_zoom')
            ->where('link_zoom', '!=', '')
            ->whereDoesntHave('scheduleStatus', function ($query) {
                $query->where('zoom_reminder_sent', true);
            })
            ->get();

        foreach ($jadwals as $jadwal) {
            $mahasantri = $jadwal->mahasantri;
            if ($mahasantri && $mahasantri->email) {
                Mail::to($mahasantri->email)->send(new ZoomLinkReminder($jadwal, $mahasantri));
                ScheduleStatus::updateOrCreate(
                    ['id_jadwal' => $jadwal->id_jadwal],
                    ['zoom_reminder_sent' => true, 'sent_at' => now()]
                );
                $sent++;
                $this->line("Terkirim: Zoom reminder ke {$mahasantri->nama_lengkap} untuk jadwal {$jadwal->tanggal} {$jadwal->jam}");
            }
        }

        $this->info("Zoom reminder: {$jadwals->count()} jadwal di tanggal {$targetDate}, {$sent} email terkirim.");
        return 0;
    }
}
