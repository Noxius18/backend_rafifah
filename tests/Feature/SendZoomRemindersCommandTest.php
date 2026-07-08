<?php

namespace Tests\Feature;

use App\Mail\ZoomLinkReminder;
use App\Models\JadwalTes;
use App\Models\ScheduleStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendZoomRemindersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_only_queues_reminders_for_eligible_h_plus_three_schedules(): void
    {
        Mail::fake();
        Carbon::setTestNow('2026-07-06 08:00:00');

        $eligible = $this->createUser('260101', 'eligible@example.com');
        $missingLink = $this->createUser('260102', 'missing-link@example.com');
        $wrongStatus = $this->createUser('260103', 'wrong-status@example.com');
        $alreadySent = $this->createUser('260104', 'already-sent@example.com');
        $missingEmail = $this->createUser('260105', '');

        $eligibleJadwal = JadwalTes::create([
            'kode_jadwal' => 'J260101',
            'id_mahasantri' => $eligible->id_mahasantri,
            'tanggal' => '2026-07-09',
            'jam' => '08:30',
            'interval' => 30,
            'link_zoom' => 'https://zoom.us/j/123456789',
            'status_jadwal' => 'Disetujui',
        ]);

        JadwalTes::create([
            'kode_jadwal' => 'J260102',
            'id_mahasantri' => $missingLink->id_mahasantri,
            'tanggal' => '2026-07-09',
            'jam' => '09:00',
            'interval' => 30,
            'link_zoom' => null,
            'status_jadwal' => 'Disetujui',
        ]);

        JadwalTes::create([
            'kode_jadwal' => 'J260103',
            'id_mahasantri' => $wrongStatus->id_mahasantri,
            'tanggal' => '2026-07-09',
            'jam' => '09:30',
            'interval' => 30,
            'link_zoom' => 'https://zoom.us/j/333333333',
            'status_jadwal' => 'Menunggu',
        ]);

        $alreadySentJadwal = JadwalTes::create([
            'kode_jadwal' => 'J260104',
            'id_mahasantri' => $alreadySent->id_mahasantri,
            'tanggal' => '2026-07-09',
            'jam' => '10:00',
            'interval' => 30,
            'link_zoom' => 'https://zoom.us/j/444444444',
            'status_jadwal' => 'Disetujui',
        ]);

        ScheduleStatus::create([
            'jadwal_id' => $alreadySentJadwal->id,
            'zoom_reminder_sent' => true,
            'sent_at' => now()->subMinute(),
        ]);

        $missingEmailJadwal = JadwalTes::create([
            'kode_jadwal' => 'J260105',
            'id_mahasantri' => $missingEmail->id_mahasantri,
            'tanggal' => '2026-07-09',
            'jam' => '10:30',
            'interval' => 30,
            'link_zoom' => 'https://zoom.us/j/555555555',
            'status_jadwal' => 'Disetujui',
        ]);

        $this->artisan('zoom:send-reminders')
            ->expectsOutput('Terkirim: Zoom reminder ke User 260101 untuk jadwal 2026-07-09 08:30')
            ->expectsOutput('Zoom reminder: 2 jadwal di tanggal 2026-07-09, 1 email terkirim.')
            ->assertExitCode(0);

        Mail::assertQueued(ZoomLinkReminder::class, 1);
        Mail::assertQueued(ZoomLinkReminder::class, function (ZoomLinkReminder $mail) use ($eligible) {
            return $mail->mahasantri->is($eligible)
                && $mail->jadwalTes->id_jadwal === 'J260101'
                && $mail->isUpdate === false;
        });

        $this->assertDatabaseHas('schedule_statuses', [
            'jadwal_id' => $eligibleJadwal->id,
            'zoom_reminder_sent' => true,
        ]);

        $this->assertDatabaseHas('schedule_statuses', [
            'jadwal_id' => $alreadySentJadwal->id,
            'zoom_reminder_sent' => true,
        ]);

        $this->assertDatabaseMissing('schedule_statuses', [
            'jadwal_id' => $missingEmailJadwal->id,
        ]);

        Carbon::setTestNow();
    }

    private function createUser(string $id, ?string $email): User
    {
        return User::create([
            'id_mahasantri' => $id,
            'nama_lengkap' => 'User ' . $id,
            'email' => $email,
            'status' => 'Pendaftar Baru',
            'tanggal_daftar' => now(),
        ]);
    }
}
