<?php

namespace Tests\Feature;

use App\Mail\ZoomLinkReminder;
use App\Models\JadwalTes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoomReminderMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_zoom_reminder_normalizes_schemeless_links_in_href(): void
    {
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
            'tanggal' => '2026-07-10',
            'jam' => '08:30',
            'interval' => 30,
            'link_zoom' => 'zoom.us/j/123456789',
            'status_jadwal' => 'Disetujui',
        ]);

        $html = (new ZoomLinkReminder($jadwal, $mahasantri))->render();

        $this->assertStringContainsString('href="https://zoom.us/j/123456789"', $html);
        $this->assertStringContainsString('zoom.us/j/123456789', $html);
    }
}
