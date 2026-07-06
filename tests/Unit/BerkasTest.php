<?php

namespace Tests\Unit;

use App\Models\Berkas;
use PHPUnit\Framework\TestCase;

class BerkasTest extends TestCase
{
    public function test_download_filename_uses_actual_file_extension(): void
    {
        $berkas = new Berkas([
            'id_berkas' => 'BR001',
            'tipe_berkas' => 'KTP',
            'file_path' => '260101/BR001.jpg',
        ]);

        $this->assertSame('jpg', $berkas->file_extension);
        $this->assertSame('BR001_KTP.jpg', $berkas->download_filename);
    }

    public function test_detects_image_from_actual_file_extension(): void
    {
        $imageBerkas = new Berkas([
            'id_berkas' => 'BR001',
            'tipe_berkas' => 'KTP',
            'file_path' => '260101/BR001.jpg',
        ]);

        $pdfBerkas = new Berkas([
            'id_berkas' => 'BR002',
            'tipe_berkas' => 'KK',
            'file_path' => '260101/BR002.pdf',
        ]);

        $this->assertTrue($imageBerkas->is_image);
        $this->assertFalse($pdfBerkas->is_image);
        $this->assertSame('BR002_KK.pdf', $pdfBerkas->download_filename);
    }
}
