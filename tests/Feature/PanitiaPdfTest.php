<?php

use App\Models\Panitia;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows ketua panitia to open the panitia pdf report', function () {
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
});

it('forbids non ketua panitia from opening the panitia pdf report', function () {
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
});
