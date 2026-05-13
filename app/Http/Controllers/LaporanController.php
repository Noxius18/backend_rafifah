<?php

namespace App\Http\Controllers;

use App\Models\HasilTes;
use App\Models\JadwalTes;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    /**
     * Cetak PDF — nilai per mahasantri (per jadwal atau semua)
     */
    public function cetakNilai(Request $request)
    {
        $idJadwal = $request->get('id_jadwal');

        if ($idJadwal) {
            $jadwal = JadwalTes::with(['pengujiList.panitia', 'picPanitia'])->findOrFail($idJadwal);
            $hasilTes = HasilTes::where('id_jadwal', $idJadwal)
                ->with('mahasantri')
                ->get();
        } else {
            $jadwal = null;
            $hasilTes = HasilTes::with(['mahasantri', 'jadwalTes'])->get();
        }

        $html = view('menu.laporan.pdf-nilai', [
            'jadwal'      => $jadwal,
            'hasilTes'    => $hasilTes,
            'date'        => now()->format('d/m/Y H:i'),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = $jadwal
            ? 'nilai-' . str_replace(' ', '-', $jadwal->periode) . '.pdf'
            : 'nilai-semua-mahasantri.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * Cetak PDF — laporan overall (summary)
     */
    public function cetakOverall()
    {
        $jadwals = JadwalTes::with(['pengujiList.panitia', 'picPanitia'])->get();

        $summary = [
            'total_mahasantri'  => User::count(),
            'total_lulus'       => HasilTes::where('status', 'Lulus')->count(),
            'total_tidak_lulus' => HasilTes::where('status', 'Tidak Lulus')->count(),
            'total_pertimbangan'=> HasilTes::where('status', 'Pertimbangan')->count(),
            'total_belum_tes'   => HasilTes::where('status', 'Belum Tes')->count(),
            'total_jadwal'      => $jadwals->count(),
        ];

        // Data per jadwal
        $perJadwal = [];
        foreach ($jadwals as $j) {
            $hasil = HasilTes::where('id_jadwal', $j->id_jadwal)->get();
            $perJadwal[] = [
                'jadwal'           => $j,
                'total'            => $hasil->count(),
                'lulus'            => $hasil->where('status', 'Lulus')->count(),
                'tidak_lulus'      => $hasil->where('status', 'Tidak Lulus')->count(),
                'pertimbangan'     => $hasil->where('status', 'Pertimbangan')->count(),
                'belum_tes'        => $hasil->where('status', 'Belum Tes')->count(),
            ];
        }

        $html = view('menu.laporan.pdf-overall', [
            'summary'  => $summary,
            'perJadwal'=> $perJadwal,
            'date'     => now()->format('d/m/Y H:i'),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="laporan-overall.pdf"');
    }
}
