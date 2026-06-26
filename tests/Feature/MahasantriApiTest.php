<?php

use App\Models\Gelombang;
use App\Models\HasilTes;
use App\Models\JadwalTes;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function activeGelombangForApiTest(): void
{
    Gelombang::create([
        'id' => 1,
        'nama' => 'Gelombang 1',
        'start_date' => now()->subDay()->toDateString(),
        'end_date' => now()->addDay()->toDateString(),
    ]);
}

function minimalRegistrationPayloadForApiTest(array $overrides = []): array
{
    return array_replace([
        'nama_lengkap' => 'Ahmad Rafif',
        'email' => 'ahmad@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

function pendaftaranSubmitPayloadForApiTest(array $overrides = []): array
{
    return array_replace_recursive([
        'nik' => '1234567890123456',
        'nisn' => '1234567890',
        'jenis_kelamin' => 'L',
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

function registerAndLoginForApiTest(): string
{
    activeGelombangForApiTest();

    test()->postJson('/api/mahasantri/register', minimalRegistrationPayloadForApiTest())->assertCreated();

    return test()->postJson('/api/mahasantri/login', [
        'email' => 'ahmad@example.com',
        'password' => 'password123',
        'device_name' => 'flutter-test',
    ])->assertOk()->json('access_token');
}

test('mahasantri can register with minimal account fields', function () {
    activeGelombangForApiTest();

    $response = $this->postJson('/api/mahasantri/register', minimalRegistrationPayloadForApiTest());

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
});

test('registration fails without active gelombang', function () {
    $response = $this->postJson('/api/mahasantri/register', minimalRegistrationPayloadForApiTest());

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('gelombang');
    $this->assertDatabaseCount('mahasantri', 0);
});

test('mahasantri can login and access status with bearer token', function () {
    $token = registerAndLoginForApiTest();

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
});

test('mahasantri can submit full pendaftaran after login', function () {
    Storage::fake('private_berkas');
    $token = registerAndLoginForApiTest();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('Accept', 'application/json')
        ->post('/api/mahasantri/pendaftaran/submit', pendaftaranSubmitPayloadForApiTest())
        ->assertOk()
        ->assertJsonPath('message', 'Data pendaftaran berhasil dikirim.')
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
});

test('pendaftaran submit requires ayah and ibu', function () {
    Storage::fake('private_berkas');
    $token = registerAndLoginForApiTest();

    $payload = [
        'nik' => '1234567890123456',
        'nisn' => '1234567890',
        'jenis_kelamin' => 'L',
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
});

test('pendaftaran submit requires bearer token', function () {
    $this->postJson('/api/mahasantri/pendaftaran/submit', [])->assertUnauthorized();
});

test('status shows completed flags after final submit', function () {
    Storage::fake('private_berkas');
    $token = registerAndLoginForApiTest();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('Accept', 'application/json')
        ->post('/api/mahasantri/pendaftaran/submit', pendaftaranSubmitPayloadForApiTest())
        ->assertOk();

    $this->getJson('/api/mahasantri/status', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonPath('data.mahasantri.profile_completed', true)
        ->assertJsonPath('data.mahasantri.orangtua_completed', true)
        ->assertJsonPath('data.mahasantri.documents_completed', true);
});

test('status returns jadwal seleksi and zoom link for mahasantri', function () {
    $token = registerAndLoginForApiTest();

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
});

test('status returns hasil seleksi and mahasantri status', function () {
    $token = registerAndLoginForApiTest();

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
});

test('legacy imported mahasantri without password cannot login', function () {
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
});
