<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    private ?Drive $driveService = null;
    private ?GoogleClient $client = null;

    /**
     * Initialize Google Drive client with Service Account credentials.
     */
    private function initClient(): GoogleClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $credentialsEnv = env('GOOGLE_DRIVE_SERVICE_ACCOUNT_CREDENTIALS');

        if (empty($credentialsEnv)) {
            throw new \RuntimeException(
                'Google Drive Service Account credentials not found. '
                . 'Set GOOGLE_DRIVE_SERVICE_ACCOUNT_CREDENTIALS in .env'
            );
        }

        // Try to decode as JSON string first
        $credentials = json_decode($credentialsEnv, true);

        // If not valid JSON, treat as file path
        if (json_last_error() !== JSON_ERROR_NONE) {
            $filePath = $this->resolveCredentialsPath($credentialsEnv);

            if (!file_exists($filePath)) {
                throw new \RuntimeException(
                    'Google Drive credentials file not found at: ' . $filePath
                );
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                throw new \RuntimeException(
                    'Could not read Google Drive credentials file at: ' . $filePath
                );
            }

            $credentials = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException(
                    'Google Drive credentials file is not valid JSON: ' . $filePath
                );
            }
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credentials);
        $client->addScope(Drive::DRIVE_READONLY);
        $client->setAccessType('offline');

        $this->client = $client;
        return $client;
    }

    /**
     * Resolve the credentials file path with multiple fallback strategies.
     */
    private function resolveCredentialsPath(string $path): string
    {
        // If it's already an absolute path, return as-is
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Remove 'storage/' prefix if present
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        // Try storage_path first (Laravel helper)
        $resolved = storage_path($path);
        if (file_exists($resolved)) {
            return $resolved;
        }

        // Try base_path (project root)
        $resolved = base_path($path);
        if (file_exists($resolved)) {
            return $resolved;
        }

        // Try with 'storage/' prefix
        $resolved = base_path('storage/' . $path);
        if (file_exists($resolved)) {
            return $resolved;
        }

        // Try with 'storage/app/' prefix
        $resolved = base_path('storage/app/' . $path);
        if (file_exists($resolved)) {
            return $resolved;
        }

        // Return the storage_path result as default (even if not found)
        return storage_path($path);
    }

    /**
     * Get authenticated Drive service instance.
     */
    private function drive(): Drive
    {
        if ($this->driveService !== null) {
            return $this->driveService;
        }

        $client = $this->initClient();
        $this->driveService = new Drive($client);
        return $this->driveService;
    }

    /**
     * Extract Google Drive file ID from a URL.
     *
     * Supports formats:
     * - https://drive.google.com/open?id=FILE_ID
     * - https://drive.google.com/file/d/FILE_ID/view
     * - https://drive.google.com/uc?id=FILE_ID
     * - https://docs.google.com/document/d/FILE_ID
     */
    public function extractFileId(string $url): ?string
    {
        $url = trim($url);

        // Extract the first URL if there's text with URL pattern in it
        // The Excel data might contain: "URL (URL)"
        preg_match('/https?:\/\/[^\s)]+/', $url, $matches);
        if (!empty($matches)) {
            $url = $matches[0];
        }

        // Pattern 1: open?id=FILE_ID
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 2: /file/d/FILE_ID/view
        if (preg_match('/\/file\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 3: /document/d/FILE_ID
        if (preg_match('/\/document\/d\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Pattern 4: uc?id=FILE_ID (direct download)
        if (preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Download a file from Google Drive as PDF.
     *
     * @param string $url The Google Drive URL
     * @param string $idBerkas The berkas ID for filename
     * @param string $tipeBerkas The document type for filename
     * @param string $idMahasantri The mahasantri ID for folder grouping
     * @return string|null The relative path of the saved file, or null on failure
     */
    public function downloadAsPdf(string $url, string $idBerkas, string $tipeBerkas, string $idMahasantri): ?string
    {
        $fileId = $this->extractFileId($url);

        if (!$fileId) {
            Log::warning("Could not extract file ID from URL: {$url}");
            return null;
        }

        try {
            $drive = $this->drive();
            $filename = $idBerkas . '_' . $tipeBerkas . '.pdf';

            // First, get the file metadata to check if it's a Google Doc/Sheet/Slides
            $file = $drive->files->get($fileId, ['fields' => 'mimeType, name']);

            // Determine if we need to export (Google native formats) or download directly
            $isGoogleNative = in_array($file->mimeType, [
                'application/vnd.google-apps.document',
                'application/vnd.google-apps.spreadsheet',
                'application/vnd.google-apps.presentation',
                'application/vnd.google-apps.drawing',
            ]);

            if ($isGoogleNative) {
                // Export Google Doc/Sheet/Slides as PDF
                $content = $drive->files->export($fileId, 'application/pdf', [
                    'alt' => 'media',
                ])->getBody()->getContents();
            } else {
                // Download the file directly
                $content = $drive->files->get($fileId, [
                    'alt' => 'media',
                ])->getBody()->getContents();
            }

            // Save to storage: private/berkas/{id_mahasantri}/{filename}
            $relativePath = $idMahasantri . '/' . $filename;
            Storage::disk('private_berkas')->put($relativePath, $content);

            Log::info("Successfully downloaded file: {$filename} (ID: {$fileId}) for mahasantri: {$idMahasantri}");

            return $relativePath;
        } catch (\Google\Service\Exception $e) {
            $errorMsg = $e->getMessage();
            Log::error("Google Drive API error for file ID {$fileId}: {$errorMsg}");

            // Fallback: try direct public download using the export link
            try {
                return $this->fallbackDownload($fileId, $idBerkas, $tipeBerkas, $idMahasantri);
            } catch (\Exception $fallbackEx) {
                Log::error("Fallback download also failed: " . $fallbackEx->getMessage());
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Failed to download file ID {$fileId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Fallback download using direct HTTP request with the export URL.
     */
    private function fallbackDownload(string $fileId, string $idBerkas, string $tipeBerkas, string $idMahasantri): ?string
    {
        $client = $this->initClient();

        // For Service Account, use assertion to get access token
        $client->fetchAccessTokenWithAssertion();
        $accessToken = $client->getAccessToken();

        $token = $accessToken['access_token'] ?? null;

        if (!$token) {
            Log::error("Could not obtain access token for fallback download");
            return null;
        }

        $exportUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}/export?mimeType=application/pdf";

        $response = Http::withToken($token)
            ->timeout(120)
            ->get($exportUrl);

        if ($response->successful()) {
            $filename = $idBerkas . '_' . $tipeBerkas . '.pdf';
            $relativePath = $idMahasantri . '/' . $filename;
            Storage::disk('private_berkas')->put($relativePath, $response->body());
            return $relativePath;
        }

        // Try direct download as fallback
        $downloadUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";
        $response = Http::withToken($token)
            ->timeout(120)
            ->get($downloadUrl);

        if ($response->successful()) {
            $filename = $idBerkas . '_' . $tipeBerkas . '.pdf';
            $relativePath = $idMahasantri . '/' . $filename;
            Storage::disk('private_berkas')->put($relativePath, $response->body());
            return $relativePath;
        }

        return null;
    }

    /**
     * Test the Google Drive connection.
     */
    public function testConnection(): bool
    {
        try {
            $drive = $this->drive();
            $drive->about->get(['fields' => 'user']);
            return true;
        } catch (\Exception $e) {
            Log::error("Google Drive connection test failed: " . $e->getMessage());
            return false;
        }
    }
}