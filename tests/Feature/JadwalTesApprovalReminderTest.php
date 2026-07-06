<?php

namespace Tests\Feature;

use App\Mail\JadwalApprovedNotification;
use App\Mail\ZoomLinkReminder;
use App\Models\JadwalTes;
use App\Models\Panitia;
use App\Models\ScheduleStatus;
use App\Models\User;
use App\Services\JadwalTes\JadwalTesApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class JadwalTesApprovalReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_by_tanggal_does_not_send_zoom_reminder_or_mark_schedule_status(): void
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

        $ketua = Panitia::create([
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
            'id_jadwal' => 'JDS01',
            'id_mahasantri' => $mahasantri->id_mahasantri,
            'tanggal' => now()->addDays(3)->toDateString(),
            'jam' => '08:30',
            'interval' => 30,
            'link_zoom' => 'https://zoom.us/j/123456789',
            'penanggung_jawab' => $creator->id_panitia,
            'status_jadwal' => 'Menunggu',
        ]);

        $result = app(JadwalTesApprovalService::class)->approveByTanggal($jadwal, $ketua->id_panitia);

        $this->assertSame($jadwal->tanggal, $result['tanggal']);
        $this->assertSame(1, $result['jumlah']);

        $this->assertDatabaseHas('jadwal_seleksi', [
            'id_jadwal' => 'JDS01',
            'status_jadwal' => 'Disetujui',
            'diproses_oleh' => $ketua->id_panitia,
        ]);

        $this->assertDatabaseCount('schedule_statuses', 0);
        $this->assertNull(ScheduleStatus::where('id_jadwal', 'JDS01')->first());

        Mail::assertNotQueued(ZoomLinkReminder::class);
        Mail::assertQueued(JadwalApprovedNotification::class, 1);
    }
}
