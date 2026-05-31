<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Orangtua;
use App\Models\Berkas;
use App\Models\JadwalTes;
use App\Models\HasilTes;
use App\Models\Penguji;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateExistingMHS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mahasantri:migrate-mhs
        {--gelombang=Gelombang 1 : Nama gelombang untuk data existing}
        {--nomor-gelombang=1 : Nomor gelombang (untuk prefix ID)}
        {--tahun=2026 : Tahun untuk prefix ID}
        {--dry-run : Hanya tampilkan perubahan tanpa eksekusi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrasi ID mahasantri dari format MHSxx ke format <tahun><gelombang><nomor>. Digunakan untuk data existing sebelum perubahan schema.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gelombangNama  = $this->option('gelombang');
        $nomorGelombang = (int) $this->option('nomor-gelombang');
        $tahun          = $this->option('tahun');
        $dryRun         = $this->option('dry-run');

        $prefix = substr($tahun, -2) . str_pad($nomorGelombang, 2, '0', STR_PAD_LEFT);
        $this->info("Prefix ID baru: {$prefix}xx");
        $this->line("Gelombang: {$gelombangNama}");

        // Ambil semua user dengan prefix MHS
        $users = User::where('id_mahasantri', 'LIKE', 'MHS%')->get();

        if ($users->isEmpty()) {
            $this->warn('Tidak ada data dengan ID MHSxx.');
            return 0;
        }

        $this->line("Ditemukan {$users->count()} data dengan ID MHSxx.");
        $this->newLine();

        if ($dryRun) {
            $this->warn('=== DRY RUN — Tidak ada perubahan nyata ===');
        }

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        $converted = 0;

        foreach ($users as $user) {
            // Generate ID baru
            $newId = User::generateId($tahun, $nomorGelombang);

            // Update gelombang jika belum di-set
            if (empty($user->gelombang)) {
                $user->gelombang = $gelombangNama;
            }

            if ($dryRun) {
                $this->newLine();
                $this->line("  {$user->id_mahasantri} → {$newId} ({$user->nama_lengkap})");
                $bar->advance();
                continue;
            }

            try {
                DB::transaction(function () use ($user, $newId) {
                    $oldId = $user->id_mahasantri;

                    // Update relasi terlebih dahulu
                    Orangtua::where('id_mahasantri', $oldId)->update(['id_mahasantri' => $newId]);
                    Berkas::where('id_mahasantri', $oldId)->update(['id_mahasantri' => $newId]);
                    JadwalTes::where('id_mahasantri', $oldId)->update(['id_mahasantri' => $newId]);
                    HasilTes::where('id_mahasantri', $oldId)->update(['id_mahasantri' => $newId]);

                    // Update ID user
                    $user->id_mahasantri = $newId;
                    $user->save();
                });

                $converted++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  Gagal mengonversi {$user->id_mahasantri}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($dryRun) {
            $this->warn('=== DRY RUN — Tidak ada perubahan nyata ===');
            $this->info('Jalankan tanpa --dry-run untuk mengeksekusi.');
        } else {
            $this->info("Berhasil mengonversi {$converted} data.");
        }

        return 0;
    }
}
