<?php

use App\Models\Gelombang;
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

function profilePayloadForApiTest(array $overrides = []): array
{
    return array_replace([
        'nik' => '1234567890123456',
        'nisn' => '1234567890',
        'jenis_kelamin' => 'L',
        'tempat_lahir' => 'Bandung',
        'alamat' => 'Jl. Pesantren No. 1',
        'tanggal_lahir' => '2010-01-10',
    ], $overrides);
}

function orangtuaPayloadForApiTest(array $overrides = []): array
{
    return array_replace_recursive([
        'orangtua' => [
            [
                'tipe_hubungan' => 'Ayah',
                'nama_lengkap' => 'Bapak Ahmad',
                'pekerjaan' => 'Wiraswasta',
                'alamat' => 'Jl. Pesantren No. 1',
                'no_wa' => '081234567890',
            ],
        ],
    ], $overrides);
}

function documentPayloadForApiTest(array $overrides = []): array
{
    return array_replace([
        'dokumen_ktp' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
        'dokumen_kk' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
        'dokumen_ijazah' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        'dokumen_surat_izin_orangtua' => UploadedFile::fake()->create('izin.pdf', 100, 'application/pdf'),
        'dokumen_pas_foto' => UploadedFile::fake()->image('foto.jpg'),
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
        ->assertJsonPath('data.jadwal', null)
        ->assertJsonPath('data.hasil', null);

    $this->postJson('/api/mahasantri/logout', [], [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk();

    $this->getJson('/api/mahasantri/status', [
        'Authorization' => 'Bearer '.$token,
    ])->assertUnauthorized();
});

test('mahasantri can complete profile after login', function () {
    $token = registerAndLoginForApiTest();

    $this->patchJson('/api/mahasantri/profile', profilePayloadForApiTest(), [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonPath('message', 'Profil berhasil diperbarui.')
        ->assertJsonPath('data.profile_completed', true);

    $this->assertDatabaseHas('mahasantri', [
        'email' => 'ahmad@example.com',
        'nik' => '1234567890123456',
        'nisn' => '1234567890',
    ]);
});

test('mahasantri can replace orangtua after login', function () {
    $token = registerAndLoginForApiTest();

    $this->putJson('/api/mahasantri/orangtua', orangtuaPayloadForApiTest(), [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonPath('message', 'Data orang tua/wali berhasil diperbarui.')
        ->assertJsonPath('data.orangtua_completed', true)
        ->assertJsonCount(1, 'data.orangtua');

    $this->assertDatabaseHas('orangtua', [
        'id_mahasantri' => '260101',
        'tipe_hubungan' => 'Ayah',
        'no_wa' => '081234567890',
    ]);
});

test('mahasantri can upload documents after login', function () {
    Storage::fake('private_berkas');
    $token = registerAndLoginForApiTest();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->withHeader('Accept', 'application/json')
        ->post('/api/mahasantri/documents', documentPayloadForApiTest())
        ->assertOk()
        ->assertJsonPath('message', 'Dokumen berhasil diunggah.')
        ->assertJsonPath('data.documents_completed', true)
        ->assertJsonCount(5, 'data.berkas');

    $this->assertDatabaseCount('berkas', 5);
    $this->assertDatabaseCount('downloads_log', 5);
});

test('completion endpoints require bearer token', function () {
    $this->patchJson('/api/mahasantri/profile', profilePayloadForApiTest())->assertUnauthorized();
    $this->putJson('/api/mahasantri/orangtua', orangtuaPayloadForApiTest())->assertUnauthorized();
    $this->postJson('/api/mahasantri/documents', [])->assertUnauthorized();
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
