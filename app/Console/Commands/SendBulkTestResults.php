<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestResultPdf;
use App\Models\JadwalTes; 

class SendBulkTestResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'results:send-bulk {--date= : Format YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim email hasil tes massal beserta lampiran PDF otomatis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ambil tanggal dari opsi command, atau gunakan tanggal hari ini sebagai default
        $date = $this->option('date') ?? now()->toDateString();
        
        $this->info("Memulai pengiriman hasil tes untuk tanggal: {$date}");

        // Ambil jadwal yang sesuai (pastikan query ini sesuai dengan struktur database-mu)
        // Kita juga memuat relasi mahasantri dan hasilTes agar lebih cepat (eager loading)
        $scheduled = JadwalTes::with(['mahasantri', 'hasilTes'])
            ->where('tanggal', $date)
            ->get();

        if ($scheduled->isEmpty()) {
            $this->info('Tidak ada jadwal tes untuk tanggal tersebut.');
            return 0;
        }

        $sentCount = 0;

        foreach ($scheduled as $jadwal) {
            $mahasantri = $jadwal->mahasantri;
            
            // PERBAIKAN DI SINI: hapus ->first() karena relasinya hasOne
            $hasil = $jadwal->hasilTes;

            // Skip jika data mahasantri atau hasil tesnya belum ada
            if (!$mahasantri || !$hasil) {
                $this->warn("No mahasiswa or result found for jadwal {$jadwal->id_jadwal} - skipping");
                continue;
            }

            // Siapkan string judul tanggal untuk dikirim ke Mailable
            $judulTanggal = $jadwal->tanggal . ' – ' . $jadwal->jam;

            // Eksekusi Mailable, PDF akan di-generate otomatis di dalam file TestResultPdf
            Mail::to($mahasantri->email)->send(new TestResultPdf($mahasantri, $hasil, $judulTanggal));
            
            $sentCount++;
            $this->line("Sent test result to {$mahasantri->nama_lengkap} ({$mahasantri->id_mahasantri})");
        }

        $this->info("Selesai! Berhasil mengirim {$sentCount} email hasil tes.");
        return 0;
    }
}