<?php

namespace App\Jobs;

use App\Models\Berkas;
use App\Services\GoogleDriveService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class DownloadGoogleDriveFile implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public int $maxExceptions = 2;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public array $backoff = [30, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Berkas $berkas
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleDriveService $driveService): void
    {
        if (!$this->berkas->original_url) {
            $this->fail(new \RuntimeException('No original URL found for berkas: ' . $this->berkas->id_berkas));
            return;
        }

        // Load relasi mahasantri untuk mendapatkan id_mahasantri
        $this->berkas->load('mahasantri');

        if (!$this->berkas->relationLoaded('mahasantri') || !$this->berkas->mahasantri) {
            $this->fail(new \RuntimeException('No mahasantri found for berkas: ' . $this->berkas->id_berkas));
            return;
        }

        // Mark as processing
        $this->berkas->update([
            'download_status' => 'processing',
            'error_message' => null,
        ]);

        try {
            $filePath = $driveService->downloadAsPdf(
                $this->berkas->original_url,
                $this->berkas->id_berkas,
                $this->berkas->tipe_dokumen,
                $this->berkas->mahasantri->id_mahasantri
            );

            if ($filePath) {
                $this->berkas->update([
                    'file_path' => $filePath,
                    'download_status' => 'success',
                    'error_message' => null,
                ]);

                Log::info("Download successful for berkas {$this->berkas->id_berkas}: {$filePath}");
            } else {
                $errorMsg = "Failed to download file from URL: {$this->berkas->original_url}";
                $this->berkas->update([
                    'download_status' => 'failed',
                    'error_message' => $errorMsg,
                ]);

                Log::error($errorMsg);
                $this->fail(new \RuntimeException($errorMsg));
            }
        } catch (\Exception $e) {
            $errorMsg = "Exception during download: " . $e->getMessage();
            $this->berkas->update([
                'download_status' => 'failed',
                'error_message' => $errorMsg,
            ]);

            Log::error($errorMsg);
            $this->fail($e);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        $errorMsg = $exception ? $exception->getMessage() : 'Unknown error';

        $this->berkas->update([
            'download_status' => 'failed',
            'error_message' => $errorMsg,
        ]);

        Log::error("Job failed for berkas {$this->berkas->id_berkas}: {$errorMsg}");
    }
}