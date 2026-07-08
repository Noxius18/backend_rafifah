<?php

namespace Tests\Feature;

use App\Models\JadwalTes;
use App\Models\Panitia;
use App\Models\User;
use App\Services\JadwalTes\JadwalTesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class JadwalTesUpdateSingleTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_single_jadwal_rejects_past_schedules(): void
    {
        Mail::fake();

        $creator = Panitia::create([
            'id_panitia' => 'PNT01',
            'nama_lengkap' => 'Panitia Satu',
            'username' => 'panitia01',
            'email' => 'panitia@example.com',
            'no_hp' => '081234567890',
            'password' => bcrypt('password123'),
            'jabatan' => 'Panitia',
        ]);

        Panitia::create([
            'id_panitia' => 'KPN01',
            'nama_lengkap' => 'Ketua Panitia',
            'username' => 'ketua01',
            'email' => 'ketua@example.com',
            'no_hp' => '081234567891',
            'password' => bcrypt('password123'),
            'jabatan' => 'Ketua Panitia',
        ]);

        $mahasantri = User::create([
            'id_mahasantri' => '260101',
            'nama_lengkap' => 'Ahmad Rafif',
            'email' => 'ahmad@example.com',
            'status' => 'Pendaftar Baru',
            'tanggal_daftar' => now(),
        ]);

        $jadwal = JadwalTes::create([
            'kode_jadwal' => 'J260101',
            'id_mahasantri' => $mahasantri->id_mahasantri,
            'tanggal' => now()->subDay()->toDateString(),
            'jam' => '08:30',
            'interval' => 30,
            'link_zoom' => 'https://zoom.us/j/123456789',
            'penanggung_jawab' => $creator->id_panitia,
            'status_jadwal' => 'Revisi',
            'catatan_ketua' => 'Catatan lama',
            'catatan_perubahan' => 'Perubahan lama',
        ]);

        $service = app(JadwalTesService::class);

        try {
            $service->updateSingleJadwal($jadwal, [
                'jam' => '09:00',
                'link_zoom' => 'https://zoom.us/j/987654321',
            ], $creator->id_panitia);

            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame('Jadwal yang sudah lewat tidak bisa diedit.', $e->getMessage());
        }

        $this->assertDatabaseHas('jadwal_seleksi', [
            'kode_jadwal' => 'J260101',
            'tanggal' => now()->subDay()->toDateString(),
            'jam' => '08:30',
            'link_zoom' => 'https://zoom.us/j/123456789',
            'status_jadwal' => 'Revisi',
            'catatan_ketua' => 'Catatan lama',
            'catatan_perubahan' => 'Perubahan lama',
        ]);

        Mail::assertNothingSent();
    }
}
