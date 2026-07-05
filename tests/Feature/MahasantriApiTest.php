<?php

namespace Tests\Feature;

use App\Models\Berkas;
use App\Models\Gelombang;
use App\Models\HasilTes;
use App\Models\JadwalTes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MahasantriApiTest extends TestCase
{
    use RefreshDatabase;

    protected function activeGelombang(): void
    {
        Gelombang::create([
            'id' => 1,
            'nama' => 'Gelombang 1',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDay()->toDateString(),
        ]);
    }

    protected function minimalRegistrationPayload(array $overrides = []): array
    {
        return array_replace([
            'nama_lengkap' => 'Ahmad Rafif',
            'email' => 'ahmad@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    protected function pendaftaranSubmitPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'nik' => '1234567890123456',
            'nisn' => '1234567890',
            'tempat_lahir' => 'Bandung',
            'alamat' => 'Jl. Pesantren No. 1',
            'tanggal_lahir' => '2010-01-10',
            'orangtua' => [
                [
                    'tipe_hubungan' => 'Ayah',
                    'nama_lengkap' => 'Bapak Ahmad',
                    'pekerjaan' => 'Wiraswasta',
                    'alamat' => 'Jl. Pesantren No. 1',
                    'no_wa' => '081234567890',
                ],
                [
                    'tipe_hubungan' => 'Ibu',
                    'nama_lengkap' => 'Ibu Ahmad',
                    'pekerjaan' => 'Ibu Rumah Tangga',
                    'alamat' => 'Jl. Pesantren No. 1',
                    'no_wa' => '081298765432',
                ],
            ],
            'berkas' => [
                'ktp' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
                'kk' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
                'ijazah' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
                'surat_izin_orangtua' => UploadedFile::fake()->create('izin.pdf', 100, 'application/pdf'),
                'pas_foto' => UploadedFile::fake()->image('foto.jpg'),
            ],
        ], $overrides);
    }

    protected function registerAndLogin(): string
    {
        $this->activeGelombang();

        $this->postJson('/api/mahasantri/register', $this->minimalRegistrationPayload())->assertCreated();

        return $this->postJson('/api/mahasantri/login', [
            'email' => 'ahmad@example.com',
            'password' => 'password123',
            'device_name' => 'flutter-test',
        ])->assertOk()->json('access_token');
    }

    public function test_mahasantri_can_register_with_minimal_account_fields(): void
    {
        $this->activeGelombang();

        $response = $this->postJson('/api/mahasantri/register', $this->minimalRegistrationPayload());

        $response->assertCreated()
            ->assertJsonPath('message', 'Pendaftaran berhasil.')
            ->assertJsonPath('data.status', 'Pendaftar Baru')
            ->assertJsonPath('data.profile_completed', false)
            ->assertJsonPath('data.orangtua_completed', false)
            ->assertJsonPath('data.documents_completed', false);

        $this->assertDatabaseHas('mahasantri', [
            'id_mahasantri' => '260101',
            'email' => 'ahmad@example.com',
            'status' => 'Pendaftar Baru',
        ]);
        $this->assertDatabaseCount('orangtua', 0);
        $this->assertDatabaseCount('berkas', 0);
    }

    public function test_registration_fails_without_active_gelombang(): void
    {
        $response = $this->postJson('/api/mahasantri/register', $this->minimalRegistrationPayload());

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('gelombang');
        $this->assertDatabaseCount('mahasantri', 0);
    }

    public function test_mahasantri_can_login_and_access_status_with_bearer_token(): void
    {
        $token = $this->registerAndLogin();

        $this->getJson('/api/mahasantri/status', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.mahasantri.email', 'ahmad@example.com')
            ->assertJsonPath('data.mahasantri.profile_completed', false)
            ->assertJsonPath('data.mahasantri.orangtua_completed', false)
            ->assertJsonPath('data.mahasantri.documents_completed', false)
            ->assertJsonPath('data.jadwal', null)
            ->assertJsonPath('data.hasil', null);

        $this->postJson('/api/mahasantri/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->getJson('/api/mahasantri/status', [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnauthorized();
    }

    public function test_mahasantri_can_submit_full_pendaftaran_after_login(): void
    {
        Storage::fake('private_berkas');
        $token = $this->registerAndLogin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/mahasantri/pendaftaran/submit', $this->pendaftaranSubmitPayload())
            ->assertOk()
            ->assertJsonPath('message', 'Data pendaftaran berhasil diperbarui.')
            ->assertJsonPath('data.profile_completed', true)
            ->assertJsonPath('data.orangtua_completed', true)
            ->assertJsonPath('data.documents_completed', true)
            ->assertJsonCount(2, 'data.orangtua')
            ->assertJsonCount(5, 'data.berkas');

        $this->assertDatabaseHas('mahasantri', [
            'email' => 'ahmad@example.com',
            'nik' => '1234567890123456',
            'nisn' => '1234567890',
        ]);
        $this->assertDatabaseCount('orangtua', 2);
        $this->assertDatabaseCount('berkas', 5);
        $this->assertDatabaseCount('job_statuses', 5);
    }

    public function test_pendaftaran_submit_requires_ayah_and_ibu(): void
    {
        Storage::fake('private_berkas');
        $token = $this->registerAndLogin();

        $payload = [
            'nik' => '1234567890123456',
            'nisn' => '1234567890',
            'tempat_lahir' => 'Bandung',
            'alamat' => 'Jl. Pesantren No. 1',
            'tanggal_lahir' => '2010-01-10',
            'orangtua' => [
                [
                    'tipe_hubungan' => 'Ayah',
                    'nama_lengkap' => 'Bapak Ahmad',
                    'pekerjaan' => 'Wiraswasta',
                    'alamat' => 'Jl. Pesantren No. 1',
                    'no_wa' => '081234567890',
                ],
            ],
            'berkas' => [
                'ktp' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
                'kk' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
                'ijazah' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
                'surat_izin_orangtua' => UploadedFile::fake()->create('izin.pdf', 100, 'application/pdf'),
                'pas_foto' => UploadedFile::fake()->image('foto.jpg'),
            ],
        ];

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/mahasantri/pendaftaran/submit', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('orangtua');
    }

    public function test_pendaftaran_submit_requires_bearer_token(): void
    {
        $this->postJson('/api/mahasantri/pendaftaran/submit', [])->assertUnauthorized();
    }

    public function test_status_shows_completed_flags_after_final_submit(): void
    {
        Storage::fake('private_berkas');
        $token = $this->registerAndLogin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/mahasantri/pendaftaran/submit', $this->pendaftaranSubmitPayload())
            ->assertOk();

        $this->getJson('/api/mahasantri/status', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.mahasantri.profile_completed', true)
            ->assertJsonPath('data.mahasantri.orangtua_completed', true)
            ->assertJsonPath('data.mahasantri.documents_completed', true);
    }

    public function test_status_returns_document_review_status_and_revision_note(): void
    {
        Storage::fake('private_berkas');
        $token = $this->registerAndLogin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/mahasantri/pendaftaran/submit', $this->pendaftaranSubmitPayload())
            ->assertOk();

        Berkas::where('id_mahasantri', '260101')
            ->where('tipe_berkas', 'KTP')
            ->update([
                'status_verifikasi' => Berkas::STATUS_DITOLAK,
                'catatan_revisi' => 'File buram, mohon upload ulang.',
            ]);

        $response = $this->getJson('/api/mahasantri/status', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $response->assertJsonFragment([
            'id_berkas' => Berkas::where('id_mahasantri', '260101')->where('tipe_berkas', 'KTP')->value('id_berkas'),
            'status_verifikasi' => Berkas::STATUS_DITOLAK,
            'catatan_revisi' => 'File buram, mohon upload ulang.',
        ]);
    }

    public function test_reuploading_rejected_document_resets_status_to_menunggu(): void
    {
        Storage::fake('private_berkas');
        $token = $this->registerAndLogin();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/mahasantri/pendaftaran/submit', $this->pendaftaranSubmitPayload())
            ->assertOk();

        $berkas = Berkas::where('id_mahasantri', '260101')
            ->where('tipe_berkas', 'KTP')
            ->firstOrFail();

        $berkas->update([
            'status_verifikasi' => Berkas::STATUS_DITOLAK,
            'catatan_revisi' => 'Dokumen salah upload.',
        ]);

        $payload = $this->pendaftaranSubmitPayload([
            'berkas' => [
                'ktp' => UploadedFile::fake()->create('ktp-baru.pdf', 120, 'application/pdf'),
            ],
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/mahasantri/pendaftaran/submit', $payload)
            ->assertOk();

        $this->assertDatabaseHas('berkas', [
            'id_berkas' => $berkas->id_berkas,
            'status_verifikasi' => Berkas::STATUS_MENUNGGU,
            'catatan_revisi' => null,
        ]);
    }

    public function test_status_returns_jadwal_seleksi_and_zoom_link_for_mahasantri(): void
    {
        $token = $this->registerAndLogin();

        JadwalTes::create([
            'id_jadwal' => 'JDS01',
            'id_mahasantri' => '260101',
            'tanggal' => '2026-07-10',
            'jam' => '08:30',
            'interval' => 30,
            'link_zoom' => 'https://zoom.us/j/123456789',
            'status_jadwal' => 'Disetujui',
        ]);

        $this->getJson('/api/mahasantri/status', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.jadwal.id_jadwal', 'JDS01')
            ->assertJsonPath('data.jadwal.tanggal', '2026-07-10')
            ->assertJsonPath('data.jadwal.jam', '08:30')
            ->assertJsonPath('data.jadwal.link_zoom', 'https://zoom.us/j/123456789')
            ->assertJsonPath('data.jadwal.status_jadwal', 'Disetujui')
            ->assertJsonPath('data.hasil', null);
    }

    public function test_status_returns_hasil_seleksi_and_mahasantri_status(): void
    {
        $token = $this->registerAndLogin();

        User::where('id_mahasantri', '260101')->update([
            'status' => 'Lulus',
        ]);

        JadwalTes::create([
            'id_jadwal' => 'JDS01',
            'id_mahasantri' => '260101',
            'tanggal' => '2026-07-10',
            'jam' => '08:30',
            'interval' => 30,
            'link_zoom' => 'https://zoom.us/j/123456789',
            'status_jadwal' => 'Disetujui',
        ]);

        HasilTes::create([
            'id_hasil' => 'HSL01',
            'id_jadwal' => 'JDS01',
            'total_nilai' => 88,
            'status' => 'Lulus',
        ]);

        $this->getJson('/api/mahasantri/status', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.mahasantri.status', 'Lulus')
            ->assertJsonPath('data.hasil.id_hasil', 'HSL01')
            ->assertJsonPath('data.hasil.total_nilai', 88)
            ->assertJsonPath('data.hasil.status', 'Lulus');
    }

    public function test_legacy_imported_mahasantri_without_password_cannot_login(): void
    {
        User::create([
            'id_mahasantri' => '260101',
            'nama_lengkap' => 'Legacy User',
            'email' => 'legacy@example.com',
            'status' => 'Pendaftar Baru',
            'tanggal_daftar' => now(),
        ]);

        $this->postJson('/api/mahasantri/login', [
            'email' => 'legacy@example.com',
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }
}
