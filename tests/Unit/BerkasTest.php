<?php

namespace Tests\Unit;

use App\Models\Berkas;
use PHPUnit\Framework\TestCase;

class BerkasTest extends TestCase
{
    public function test_download_filename_uses_actual_file_extension(): void
    {
        $berkas = new Berkas([
            'tipe_berkas' => 'KTP',
            'file_path' => '260101/1.jpg',
        ]);
        $berkas->id = 1;

        $this->assertSame('jpg', $berkas->file_extension);
        $this->assertSame('1_KTP.jpg', $berkas->download_filename);
    }

    public function test_detects_image_from_actual_file_extension(): void
    {
        $imageBerkas = new Berkas([
            'tipe_berkas' => 'KTP',
            'file_path' => '260101/1.jpg',
        ]);
        $imageBerkas->id = 1;

        $pdfBerkas = new Berkas([
            'tipe_berkas' => 'KK',
            'file_path' => '260101/2.pdf',
        ]);
        $pdfBerkas->id = 2;

        $this->assertTrue($imageBerkas->is_image);
        $this->assertFalse($pdfBerkas->is_image);
        $this->assertSame('2_KK.pdf', $pdfBerkas->download_filename);
    }
}
