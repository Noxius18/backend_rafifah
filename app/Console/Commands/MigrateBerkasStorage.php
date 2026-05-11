<?php

namespace App\Console\Commands;

use App\Models\Berkas;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateBerkasStorage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'berkas:migrate-storage';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate berkas files from old disk (berkas) to new disk (private_berkas) with id_mahasantri folder structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $oldDisk = 'berkas';
        $newDisk = 'private_berkas';

        // Cek apakah old disk ada (mungkin sudah dihapus dari config)
        if (!array_key_exists($oldDisk, config('filesystems.disks'))) {
            $this->warn("Disk '{$oldDisk}' tidak ditemukan di config. Tidak ada migrasi yang diperlukan.");
            return Command::SUCCESS;
        }

        $berkasList = Berkas::whereNotNull('file_path')
            ->where('download_status', 'success')
            ->get();

        if ($berkasList->isEmpty()) {
            $this->info('Tidak ada berkas yang perlu dimigrasi.');
            return Command::SUCCESS;
        }

        $migrated = 0;
        $failed = 0;
        $skipped = 0;

        $bar = $this->output->createProgressBar($berkasList->count());
        $bar->start();

        foreach ($berkasList as $berkas) {
            $oldRelativePath = $berkas->file_path; // Contoh: DKM01/DKM01_KTP.pdf

            // Cek file di old disk
            if (!Storage::disk($oldDisk)->exists($oldRelativePath)) {
                $this->newLine();
                $this->warn("File tidak ditemukan di old disk: {$oldRelativePath}");
                $skipped++;
                $bar->advance();
                continue;
            }

            // Load mahasantri
            $berkas->load('mahasantri');

            if (!$berkas->mahasantri) {
                $this->newLine();
                $this->warn("Mahasantri tidak ditemukan untuk berkas: {$berkas->id_berkas}");
                $skipped++;
                $bar->advance();
                continue;
            }

            // Ambil filename dari old path (DKM01_KTP.pdf)
            $filename = basename($oldRelativePath);

            // Path baru: {id_mahasantri}/{filename}
            $newRelativePath = $berkas->mahasantri->id_mahasantri . '/' . $filename;

            // Cek apakah sudah ada di new disk
            if (Storage::disk($newDisk)->exists($newRelativePath)) {
                // Skip, file sudah ada di lokasi baru
                $berkas->update(['file_path' => $newRelativePath]);
                $skipped++;
                $bar->advance();
                continue;
            }

            // Copy file dari old disk ke new disk
            try {
                $content = Storage::disk($oldDisk)->get($oldRelativePath);
                Storage::disk($newDisk)->put($newRelativePath, $content);

                // Update database
                $berkas->update(['file_path' => $newRelativePath]);

                $migrated++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Gagal migrasi {$oldRelativePath}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->newLine();

        $this->info("Migrasi selesai:");
        $this->info("- Berhasil: {$migrated}");
        $this->info("- Dilewati: {$skipped}");
        $this->info("- Gagal: {$failed}");

        return Command::SUCCESS;
    }
}