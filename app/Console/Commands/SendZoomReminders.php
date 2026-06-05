<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Models\JadwalTes; // Sesuaikan dengan namespace model kamu
use App\Mail\ZoomLinkReminder; // Sesuaikan dengan namespace mailable kamu

class SendZoomReminders extends Command
{
    protected $signature = 'zoom:send-reminders {--force}';
    protected $description = 'Kirim reminder link zoom 1 jam tepat sebelum jadwal ujian';

    public function handle()
    {
        // 1. Ambil waktu sekarang, potong sampai menit saja (contoh: "2026-06-05 18:35")
        $now = Carbon::now()->format('Y-m-d H:i');
        $sent = 0;

        // 2. Cari jadwal hari ini yang punya link zoom
        $jadwals = JadwalTes::where('tanggal', Carbon::now()->toDateString())
            ->whereNotNull('link_zoom')
            ->get();

        foreach ($jadwals as $jadwal) {
            // 3. Gabungkan tanggal dan jam ujian
            $jadwalDateTime = Carbon::parse($jadwal->tanggal . ' ' . $jadwal->jam);
            
            // 4. Mundurkan 1 jam, lalu format sampai menit (contoh jadwal 19:35 jadi "2026-06-05 18:35")
            $reminderTime = $jadwalDateTime->copy()->subHour()->format('Y-m-d H:i');

            // 5. Jika waktu saat ini SAMA PERSIS dengan waktu reminder
            if ($now === $reminderTime) {
                
                // Cek apakah belum pernah dikirim atau dipaksa via --force
                if (!$this->option('force') && $jadwal->zoom_reminder_sent) {
                    continue; 
                }

                $mahasantri = $jadwal->mahasantri;
                if ($mahasantri) {
                    Mail::to($mahasantri->email)->send(new ZoomLinkReminder($jadwal, $mahasantri));
                    
                    // Tandai bahwa reminder sudah terkirim
                    $jadwal->update(['zoom_reminder_sent' => true]);
                    $sent++;
                    $this->line("Terkirim: Zoom reminder ke {$mahasantri->nama_lengkap} untuk jadwal {$jadwal->tanggal} {$jadwal->jam}");
                }
            }
        }

        $this->info("Pengecekan selesai: {$jadwals->count()} jadwal dievaluasi, {$sent} email terkirim.");
        return 0;
    }
}