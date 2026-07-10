<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Drive;
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

        $credentialsBase64 = config('services.google_drive.credentials_b64');

        if (!is_string($credentialsBase64) || trim($credentialsBase64) === '') {
            throw new \RuntimeException(
                'Google Drive Service Account credentials not found. '
                . 'Set GOOGLE_DRIVE_SERVICE_ACCOUNT_CREDENTIALS_B64 in .env'
            );
        }

        $credentialsJson = base64_decode($credentialsBase64, true);
        if ($credentialsJson === false) {
            throw new \RuntimeException('Google Drive credentials base64 is not valid.');
        }

        $credentials = json_decode($credentialsJson, true);
        if (!is_array($credentials) || $credentials === []) {
            throw new \RuntimeException('Google Drive credentials JSON is not valid.');
        }

        $client = new GoogleClient();
        $client->setAuthConfig($credentials);
        $client->addScope(Drive::DRIVE_READONLY);
        $client->setAccessType('offline');

        $this->client = $client;
        return $client;
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

    public function downloadFile(string $url, string $idBerkas, string $tipeBerkas, string $idMahasantri): ?string
    {
        $fileId = $this->extractFileId($url);

        if (!$fileId) {
            Log::warning("Could not extract file ID from URL: {$url}");
            return null;
        }

        try {
            $drive = $this->drive();

            $file = $drive->files->get($fileId, ['fields' => 'mimeType, name, fileExtension']);

            $mimeType = $file->mimeType ?? null;
            $originalName = $file->name ?? null;
            $originalExtension = $file->fileExtension ?? null;
            $isGoogleNative = $this->isGoogleNativeMimeType($mimeType);

            if ($isGoogleNative) {
                $content = $drive->files->export($fileId, 'application/pdf', [
                    'alt' => 'media',
                ])->getBody()->getContents();
                $extension = 'pdf';
            } else {
                $content = $drive->files->get($fileId, [
                    'alt' => 'media',
                ])->getBody()->getContents();
                $extension = $this->resolveStoredExtension($originalName, $mimeType, $originalExtension, false);
            }

            $relativePath = $this->storeDownloadedContent($idMahasantri, $idBerkas, $tipeBerkas, $extension, $content);

            Log::info("Successfully downloaded file: {$relativePath} (ID: {$fileId}) for mahasantri: {$idMahasantri}");

            return $relativePath;
        } catch (\Google\Service\Exception $e) {
            $errorMsg = $e->getMessage();
            Log::error("Google Drive API error for file ID {$fileId}: {$errorMsg}");

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

        $metadataResponse = Http::withToken($token)
            ->timeout(120)
            ->get("https://www.googleapis.com/drive/v3/files/{$fileId}", [
                'fields' => 'mimeType,name,fileExtension',
            ]);

        $mimeType = null;
        $originalName = null;
        $originalExtension = null;

        if ($metadataResponse->successful()) {
            $metadata = $metadataResponse->json();
            $mimeType = $metadata['mimeType'] ?? null;
            $originalName = $metadata['name'] ?? null;
            $originalExtension = $metadata['fileExtension'] ?? null;
        }

        $isGoogleNative = $this->isGoogleNativeMimeType($mimeType);
        $exportUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}/export?mimeType=application/pdf";

        if ($isGoogleNative) {
            $response = Http::withToken($token)
                ->timeout(120)
                ->get($exportUrl);

            if ($response->successful()) {
                return $this->storeDownloadedContent($idMahasantri, $idBerkas, $tipeBerkas, 'pdf', $response->body());
            }
        }

        $downloadUrl = "https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media";
        $response = Http::withToken($token)
            ->timeout(120)
            ->get($downloadUrl);

        if ($response->successful()) {
            $extension = $this->resolveStoredExtension(
                $originalName,
                $mimeType ?? $response->header('Content-Type'),
                $originalExtension,
                $isGoogleNative
            );

            return $this->storeDownloadedContent($idMahasantri, $idBerkas, $tipeBerkas, $extension, $response->body());
        }

        return null;
    }

    private function isGoogleNativeMimeType(?string $mimeType): bool
    {
        return in_array($mimeType, [
            'application/vnd.google-apps.document',
            'application/vnd.google-apps.spreadsheet',
            'application/vnd.google-apps.presentation',
            'application/vnd.google-apps.drawing',
        ], true);
    }

    private function resolveStoredExtension(?string $originalName, ?string $mimeType, ?string $originalExtension, bool $forcePdf): string
    {
        if ($forcePdf) {
            return 'pdf';
        }

        $nameExtension = $this->extractExtensionFromName($originalName);
        if ($nameExtension !== null) {
            return $nameExtension;
        }

        if (is_string($originalExtension) && $originalExtension !== '') {
            return strtolower($originalExtension);
        }

        return $this->extensionFromMimeType($mimeType) ?? 'bin';
    }

    private function extractExtensionFromName(?string $name): ?string
    {
        if (!is_string($name) || $name === '') {
            return null;
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);

        return $extension !== '' ? strtolower($extension) : null;
    }

    private function extensionFromMimeType(?string $mimeType): ?string
    {
        if (!is_string($mimeType) || $mimeType === '') {
            return null;
        }

        $cleanMimeType = strtolower(trim(explode(';', $mimeType)[0]));

        return match ($cleanMimeType) {
            'application/pdf' => 'pdf',
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => null,
        };
    }

    private function storeDownloadedContent(
        string $idMahasantri,
        string $idBerkas,
        string $tipeBerkas,
        string $extension,
        string $content
    ): string {
        $filename = $idBerkas . '_' . $tipeBerkas . '.' . strtolower($extension);
        $relativePath = $idMahasantri . '/' . $filename;

        Storage::disk('private_berkas')->put($relativePath, $content);

        return $relativePath;
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
