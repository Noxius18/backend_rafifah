<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Konfigurasi Gelombang Pendaftaran
    |--------------------------------------------------------------------------
    |
    | Digunakan untuk auto-detect gelombang saat import data mahasantri
    | berdasarkan tanggal daftar.
    |
    | Setiap gelombang punya:
    |   - nama: label yang disimpan di database (kolom `gelombang`)
    |   - start: tanggal awal (inklusif)
    |   - end:   tanggal akhir (inklusif)
    |
    | nomor gelombang (key array) digunakan untuk generate ID mahasantri.
    | Contoh: Gelombang 1 tahun 2026 → prefix '2601'
    |
    */

    'gelombang' => [
        1 => [
            'nama' => 'Gelombang 1',
            'start' => '2026-01-01',
            'end'   => '2026-02-28',
        ],
        2 => [
            'nama' => 'Gelombang 2',
            'start' => '2026-03-01',
            'end'   => '2026-04-30',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Tahun
    |--------------------------------------------------------------------------
    |
    | Tahun default untuk generate ID. Biasanya tahun berjalan.
    |
    */
    'default_tahun' => date('Y'),
];
