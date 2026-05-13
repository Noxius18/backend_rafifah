<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class TestGoogleDriveConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-drive:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Google Drive API connection';

    /**
     * Execute the console command.
     */
    public function handle(GoogleDriveService $driveService)
    {
        $this->info('Testing Google Drive connection...');

        try {
            $result = $driveService->testConnection();

            if ($result) {
                $this->info('✓ Connection successful!');
                return Command::SUCCESS;
            } else {
                $this->error('✗ Connection failed. Check the logs for details.');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}