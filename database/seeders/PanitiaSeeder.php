<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Panitia;

class PanitiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                'id_panitia' => 'PNG01',
                'nama_lengkap' => 'Pengawas Satu',
                'username' => 'pengawas',
                'password' => bcrypt('password'),
                'jabatan' => 'Pengawas',
            ],
            [
                'id_panitia' => 'PNT01',
                'nama_lengkap' => 'Panitia Satu',
                'username' => 'panitia',
                'password' => bcrypt('password'),
                'jabatan' => 'Panitia',
            ],
            [
                'id_panitia' => 'PNJ01',
                'nama_lengkap' => 'Penguji Satu',
                'username' => 'penguji',
                'password' => bcrypt('password'),
                'jabatan' => 'Penguji',
            ],
        ];

        foreach ($data as $item) {
            Panitia::create($item);
        }
    }
}
