<?php

namespace App\Console\Commands;

use App\Jobs\DownloadGoogleDriveFile;
use App\Models\Berkas;
use Illuminate\Console\Command;

class RetryFailedDownloads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'berkas:retry-failed {--limit=10 : Jumlah maksimal berkas yang akan di-retry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry download for all failed berkas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');

        $failedBerkas = Berkas::where('download_status', 'failed')
            ->whereNotNull('original_url')
            ->limit($limit)
            ->get();

        if ($failedBerkas->isEmpty()) {
            $this->info('Tidak ada berkas gagal yang perlu di-retry.');
            return Command::SUCCESS;
        }

        $count = 0;
        foreach ($failedBerkas as $berkas) {
            $berkas->update([
                'download_status' => 'pending',
                'error_message' => null,
            ]);

            DownloadGoogleDriveFile::dispatch($berkas);
            $count++;
        }

        $this->info("Berhasil mengirim {$count} berkas untuk di-retry.");
        return Command::SUCCESS;
    }
}