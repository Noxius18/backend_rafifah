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
                'nama_lengkap' => 'Budi Spakbor',
                'username' => 'budi',
                'no_hp' => '0819287365',
                'password' => bcrypt('password'),
                'jabatan' => 'Pengawas',
            ],
            [
                'id_panitia' => 'PNT01',
                'nama_lengkap' => 'Farhan Kebab',
                'username' => 'farhan',
                'no_hp' => '081726541767',
                'password' => bcrypt('password'),
                'jabatan' => 'Panitia',
            ],
            [
                'id_panitia' => 'PNG02',
                'nama_lengkap' => 'Ahmad Wijaya',
                'username' => 'ahmad',
                'no_hp' => '082134567890',
                'password' => bcrypt('password'),
                'jabatan' => 'Pengawas',
            ],
            [
                'id_panitia' => 'PNT02',
                'nama_lengkap' => 'Doni Pratama',
                'username' => 'doni',
                'no_hp' => '081987654321',
                'password' => bcrypt('password'),
                'jabatan' => 'Panitia',
            ],
            [
                'id_panitia' => 'PNT03',
                'nama_lengkap' => 'Rudi Hartono',
                'username' => 'rudi',
                'no_hp' => '082567890123',
                'password' => bcrypt('password'),
                'jabatan' => 'Panitia',
            ],
        ];

        foreach ($data as $item) {
            Panitia::create($item);
        }
    }
}
