<?php

namespace App\Services\Mahasantri;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CompressedDocumentUploadService
{
    private const PAS_FOTO_FIELDS = [
        'pas_foto' => true,
    ];

    /**
     * Store the uploaded document, recompressing image uploads into JPEG.
     *
     * @return array{path: string, extension: string}
     */
    public function store(
        UploadedFile $file,
        string $directory,
        string $filenameWithoutExtension,
        string $disk,
        string $field
    ): array {
        if (!$this->isCompressibleImage($file)) {
            $extension = strtolower($file->getClientOriginalExtension());
            $path = $file->storeAs($directory, "{$filenameWithoutExtension}.{$extension}", $disk);

            if (!is_string($path)) {
                throw new RuntimeException('Gagal menyimpan file upload.');
            }

            return [
                'path' => $path,
                'extension' => $extension,
            ];
        }

        $image = $this->createImageResource($file);
        if (!$image) {
            throw new RuntimeException('Gagal membaca file gambar yang diunggah.');
        }

        try {
            $normalized = $this->normalizeOrientation($image, $file);
            $resized = $this->resizeImage($normalized, $this->maxWidthFor($field), $this->maxHeightFor($field));
            $jpegBinary = $this->encodeJpeg($resized, $this->qualityFor($field));
        } finally {
            imagedestroy($image);

            if (isset($normalized) && $normalized !== $image) {
                imagedestroy($normalized);
            }

            if (isset($resized) && $resized !== ($normalized ?? $image)) {
                imagedestroy($resized);
            }
        }

        $path = "{$directory}/{$filenameWithoutExtension}.jpg";
        if (!Storage::disk($disk)->put($path, $jpegBinary)) {
            throw new RuntimeException('Gagal menyimpan file hasil kompresi.');
        }

        return [
            'path' => $path,
            'extension' => 'jpg',
        ];
    }

    private function isCompressibleImage(UploadedFile $file): bool
    {
        return str_starts_with((string) $file->getMimeType(), 'image/');
    }

    private function createImageResource(UploadedFile $file)
    {
        return match ($file->getMimeType()) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($file->getRealPath()),
            'image/png' => @imagecreatefrompng($file->getRealPath()),
            default => false,
        };
    }

    private function normalizeOrientation($image, UploadedFile $file)
    {
        if (!function_exists('exif_read_data') || !in_array($file->getMimeType(), ['image/jpeg', 'image/jpg'], true)) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated ?: $image;
    }

    private function resizeImage($image, int $maxWidth, int $maxHeight)
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxWidth && $height <= $maxHeight) {
            return $image;
        }

        $scale = min($maxWidth / $width, $maxHeight / $height);
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $canvas;
    }

    private function encodeJpeg($image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, $quality);
        $binary = ob_get_clean();

        if (!is_string($binary)) {
            throw new RuntimeException('Gagal mengubah gambar ke JPEG.');
        }

        return $binary;
    }

    private function qualityFor(string $field): int
    {
        return isset(self::PAS_FOTO_FIELDS[$field]) ? 82 : 78;
    }

    private function maxWidthFor(string $field): int
    {
        return isset(self::PAS_FOTO_FIELDS[$field]) ? 1200 : 2000;
    }

    private function maxHeightFor(string $field): int
    {
        return isset(self::PAS_FOTO_FIELDS[$field]) ? 1600 : 2000;
    }
}
