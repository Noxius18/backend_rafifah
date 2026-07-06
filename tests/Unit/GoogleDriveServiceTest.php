<?php

namespace Tests\Unit;

use App\Services\GoogleDriveService;
use ReflectionMethod;
use Tests\TestCase;

class GoogleDriveServiceTest extends TestCase
{
    public function test_resolve_stored_extension_prefers_original_file_name_extension(): void
    {
        $service = new GoogleDriveService();

        $extension = $this->invokePrivateMethod($service, 'resolveStoredExtension', [
            'pas-foto.png',
            'application/pdf',
            'pdf',
            false,
        ]);

        $this->assertSame('png', $extension);
    }

    public function test_resolve_stored_extension_maps_image_mime_type_when_name_missing(): void
    {
        $service = new GoogleDriveService();

        $extension = $this->invokePrivateMethod($service, 'resolveStoredExtension', [
            null,
            'image/jpeg; charset=binary',
            null,
            false,
        ]);

        $this->assertSame('jpg', $extension);
    }

    public function test_resolve_stored_extension_forces_pdf_for_google_native_exports(): void
    {
        $service = new GoogleDriveService();

        $extension = $this->invokePrivateMethod($service, 'resolveStoredExtension', [
            'pas-foto',
            'application/vnd.google-apps.document',
            null,
            true,
        ]);

        $this->assertSame('pdf', $extension);
    }

    /**
     * @param  array<int, mixed>  $arguments
     */
    private function invokePrivateMethod(object $instance, string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod($instance, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($instance, $arguments);
    }
}
