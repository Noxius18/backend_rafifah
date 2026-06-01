<?php

namespace Database\Seeders;

use App\Models\Gelombang;
use Illuminate\Database\Seeder;

class GelombangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gelombangData = [
            [
                'nomor' => 1,
                'nama'  => 'Gelombang 1',
                'start_date' => '2026-01-01',
                'end_date'   => '2026-02-28',
            ],
            [
                'nomor' => 2,
                'nama'  => 'Gelombang 2',
                'start_date' => '2026-03-01',
                'end_date'   => '2026-04-30',
            ],
        ];

        foreach ($gelombangData as $data) {
            Gelombang::updateOrCreate(
                ['nomor' => $data['nomor']],
                $data
            );
        }
    }
}