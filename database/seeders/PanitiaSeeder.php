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
                'no_hp' => '0819287365',
                'password' => bcrypt('password'),
                'jabatan' => 'Pengawas',
            ],
            [
                'id_panitia' => 'PNT01',
                'nama_lengkap' => 'Panitia Satu',
                'username' => 'panitia',
                'no_hp' => '081726541767',
                'password' => bcrypt('password'),
                'jabatan' => 'Panitia',
            ],
        ];

        foreach ($data as $item) {
            Panitia::create($item);
        }
    }
}
