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

function registrationPayloadForApiTest(array $overrides = []): array
{
    return array_replace_recursive([
        'nama_lengkap' => 'Ahmad Rafif',
        'email' => 'ahmad@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
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
        'dokumen_ktp' => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'),
        'dokumen_kk' => UploadedFile::fake()->create('kk.pdf', 100, 'application/pdf'),
        'dokumen_ijazah' => UploadedFile::fake()->create('ijazah.pdf', 100, 'application/pdf'),
        'dokumen_surat_izin_orangtua' => UploadedFile::fake()->create('izin.pdf', 100, 'application/pdf'),
        'dokumen_pas_foto' => UploadedFile::fake()->image('foto.jpg'),
    ], $overrides);
}

test('mahasantri can register with uploaded documents', function () {
    Storage::fake('private_berkas');
    activeGelombangForApiTest();

    $response = $this->withHeader('Accept', 'application/json')
        ->post('/api/mahasantri/register', registrationPayloadForApiTest());

    $response->assertCreated()
        ->assertJsonPath('message', 'Pendaftaran berhasil.')
        ->assertJsonPath('data.status', 'Pendaftar Baru')
        ->assertJsonCount(5, 'data.berkas');

    $this->assertDatabaseHas('mahasantri', [
        'id_mahasantri' => '260101',
        'email' => 'ahmad@example.com',
        'status' => 'Pendaftar Baru',
    ]);
    $this->assertDatabaseCount('berkas', 5);
    $this->assertDatabaseCount('downloads_log', 5);
});

test('registration fails without active gelombang', function () {
    Storage::fake('private_berkas');

    $response = $this->withHeader('Accept', 'application/json')
        ->post('/api/mahasantri/register', registrationPayloadForApiTest());

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('gelombang');
    $this->assertDatabaseCount('mahasantri', 0);
});

test('mahasantri can login and access status with bearer token', function () {
    Storage::fake('private_berkas');
    activeGelombangForApiTest();

    $this->post('/api/mahasantri/register', registrationPayloadForApiTest())->assertCreated();

    $login = $this->postJson('/api/mahasantri/login', [
        'email' => 'ahmad@example.com',
        'password' => 'password123',
        'device_name' => 'flutter-test',
    ]);

    $token = $login->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->json('access_token');

    $this->getJson('/api/mahasantri/status', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonPath('data.mahasantri.email', 'ahmad@example.com')
        ->assertJsonPath('data.jadwal', null)
        ->assertJsonPath('data.hasil', null);

    $this->postJson('/api/mahasantri/logout', [], [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk();

    $this->getJson('/api/mahasantri/status', [
        'Authorization' => 'Bearer '.$token,
    ])->assertUnauthorized();
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
