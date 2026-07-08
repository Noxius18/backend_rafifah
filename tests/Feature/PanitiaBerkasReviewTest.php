<?php

namespace Tests\Feature;

use App\Models\Berkas;
use App\Models\Panitia;
use App\Models\RiwayatUnduhan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanitiaBerkasReviewTest extends TestCase
{
    use RefreshDatabase;

    private function createPanitia(string $jabatan = 'Panitia'): Panitia
    {
        return Panitia::create([
            'id_panitia' => $jabatan === 'Panitia' ? 'PNT01' : 'KPN01',
            'nama_lengkap' => $jabatan . ' Satu',
            'username' => $jabatan === 'Panitia' ? 'panitia01' : 'ketua01',
            'no_hp' => $jabatan === 'Panitia' ? '081234567890' : '081234567891',
            'password' => bcrypt('password123'),
            'jabatan' => $jabatan,
        ]);
    }

    private function createBerkas(string $status = Berkas::STATUS_MENUNGGU): Berkas
    {
        User::create([
            'id_mahasantri' => '260101',
            'nama_lengkap' => 'Ahmad Rafif',
            'email' => 'ahmad@example.com',
            'nik' => '1234567890123456',
            'nisn' => '1234567890',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2010-01-10',
            'status' => 'Pendaftar Baru',
            'tanggal_daftar' => now(),
        ]);

        $berkas = Berkas::create([
            'id_mahasantri' => '260101',
            'tipe_berkas' => 'KTP',
            'status_verifikasi' => $status,
            'tanggal_upload' => now(),
        ]);

        RiwayatUnduhan::create([
            'berkas_id' => $berkas->id,
            'download_status' => 'success',
        ]);

        return $berkas;
    }

    public function test_panitia_can_reject_document_with_revision_note(): void
    {
        $panitia = $this->createPanitia();
        $berkas = $this->createBerkas();

        $this->actingAs($panitia, 'panitia')
            ->postJson(route('panitia.berkas.review', $berkas), [
                'status' => Berkas::STATUS_DITOLAK,
                'catatan_revisi' => 'Dokumen salah upload.',
            ])
            ->assertOk()
            ->assertJsonPath('data.status_verifikasi', Berkas::STATUS_DITOLAK)
            ->assertJsonPath('data.catatan_revisi', 'Dokumen salah upload.');

        $this->assertDatabaseHas('berkas', [
            'id' => $berkas->id,
            'status_verifikasi' => Berkas::STATUS_DITOLAK,
            'catatan_revisi' => 'Dokumen salah upload.',
        ]);
    }

    public function test_reject_requires_revision_note(): void
    {
        $panitia = $this->createPanitia();
        $berkas = $this->createBerkas();

        $this->actingAs($panitia, 'panitia')
            ->postJson(route('panitia.berkas.review', $berkas), [
                'status' => Berkas::STATUS_DITOLAK,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('catatan_revisi');
    }

    public function test_review_rejects_menunggu_status(): void
    {
        $panitia = $this->createPanitia();
        $berkas = $this->createBerkas();

        $this->actingAs($panitia, 'panitia')
            ->postJson(route('panitia.berkas.review', $berkas), [
                'status' => Berkas::STATUS_MENUNGGU,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_approving_last_pending_document_verifies_mahasantri(): void
    {
        $panitia = $this->createPanitia();
        $berkas = $this->createBerkas();

        $this->actingAs($panitia, 'panitia')
            ->postJson(route('panitia.berkas.review', $berkas), [
                'status' => Berkas::STATUS_DISETUJUI,
            ])
            ->assertOk()
            ->assertJsonPath('data.status_verifikasi', Berkas::STATUS_DISETUJUI);

        $this->assertDatabaseHas('mahasantri', [
            'id_mahasantri' => '260101',
            'status' => 'Terverifikasi',
        ]);
    }
}
