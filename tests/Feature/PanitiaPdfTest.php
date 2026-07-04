<?php

namespace Tests\Feature;

use App\Models\Panitia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanitiaPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_ketua_panitia_to_open_the_panitia_pdf_report(): void
    {
        $ketua = Panitia::create([
            'id_panitia' => 'KPN01',
            'nama_lengkap' => 'Ketua Satu',
            'username' => 'ketua01',
            'no_hp' => '081234567890',
            'password' => bcrypt('password123'),
            'jabatan' => 'Ketua Panitia',
        ]);

        Panitia::create([
            'id_panitia' => 'PNT01',
            'nama_lengkap' => 'Panitia Satu',
            'username' => 'panitia01',
            'no_hp' => '081234567891',
            'password' => bcrypt('password123'),
            'jabatan' => 'Panitia',
        ]);

        $response = $this->actingAs($ketua, 'panitia')->get(route('panitia.cetak-pdf'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertHeader('content-disposition', 'inline; filename="daftar-panitia.pdf"');
    }

    public function test_forbids_non_ketua_panitia_from_opening_the_panitia_pdf_report(): void
    {
        $panitia = Panitia::create([
            'id_panitia' => 'PNT01',
            'nama_lengkap' => 'Panitia Satu',
            'username' => 'panitia01',
            'no_hp' => '081234567891',
            'password' => bcrypt('password123'),
            'jabatan' => 'Panitia',
        ]);

        $response = $this->actingAs($panitia, 'panitia')->get(route('panitia.cetak-pdf'));

        $response->assertForbidden();
    }
}
