<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestResultPdf;
use App\Models\JadwalTes; 
use App\Models\User; // Import Model Mahasantri
use Illuminate\Support\Facades\DB;

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
    protected $description = 'Kirim email hasil tes massal beserta lampiran PDF otomatis sekaligus sinkronisasi ke Flutter';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Ambil tanggal dari opsi command, atau gunakan tanggal hari ini sebagai default
        $date = $this->option('date') ?? now()->toDateString();
        
        $this->info("Memulai pengiriman hasil tes untuk tanggal: {$date}");

        // Ambil jadwal yang sesuai beserta eager loading relasi mahasantri dan hasilTes
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
            $hasil = $jadwal->hasilTes;

            // Skip jika data mahasantri atau hasil tesnya belum di-input nilainya oleh penguji/panitia
            if (!$mahasantri || !$hasil) {
                $this->warn("No mahasiswa or result found for jadwal {$jadwal->id_jadwal} - skipping");
                continue;
            }

            // ====================================================================
            // INTEGRASI MULTI-PLATFORM (WEB & FLUTTER SINKRONISASI MASSAL)
            // ====================================================================
            // Bungkus dalam Database Transaction agar aman anti-corrupt data
            DB::transaction(function () use ($mahasantri, $hasil, $jadwal) {
                
                // 1. Tentukan status kelulusan berdasarkan rekam data review di hasil_tes
                // (Mengambil status kelulusan 'Lulus' atau 'Tidak Lulus' yang sudah disiapkan panitia)
                $statusFinal = $hasil->status ?? 'Lulus'; 

                // 2. Update status ENUM di tabel utama mahasantri agar Flutter bisa baca via API
                User::where('id_mahasantri', $mahasantri->id_mahasantri)->update([
                    'status' => $statusFinal
                ]);

                // 3. Update tanggal pengumuman resmi di tabel hasil_tes sesuai hari eksekusi ini
                DB::table('hasil_tes')
                    ->where('id_mahasantri', $mahasantri->id_mahasantri)
                    ->update([
                        'tanggal_pengumuman' => now()->format('Y-m-d')
                    ]);
            });

            // Siapkan string judul tanggal untuk dikirim ke Mailable email
            $judulTanggal = $jadwal->tanggal . ' – ' . $jadwal->jam;

            // Eksekusi pengiriman email resmi beserta lampiran PDF-nya
            Mail::to($mahasantri->email)->send(new TestResultPdf($mahasantri, $hasil, $judulTanggal));
            
            $sentCount++;
            $this->line("Sent test result & synchronized Flutter for {$mahasantri->nama_lengkap} ({$mahasantri->id_mahasantri})");
        }

        $this->info("Selesai! Berhasil mengirim {$sentCount} email hasil tes dan sukses disinkronkan ke Flutter.");
        return 0;
    }
}