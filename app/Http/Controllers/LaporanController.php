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
            $jadwal = JadwalTes::with(['penanggungJawab', 'pengujiTajwid', 'pengujiTahsin', 'pengujiKelancaran', 'pengujiWawancara', 'mahasantri'])->findOrFail($idJadwal);
            $hasilTes = HasilTes::where('id_jadwal', $idJadwal)
                ->with(['mahasantri', 'jadwalTes.mahasantri', 'jadwalTes.penanggungJawab', 'jadwalTes.pengujiTajwid', 'jadwalTes.pengujiTahsin', 'jadwalTes.pengujiKelancaran', 'jadwalTes.pengujiWawancara'])
                ->get();
        } else {
            $jadwal = null;
            $hasilTes = HasilTes::with(['mahasantri', 'jadwalTes.mahasantri', 'jadwalTes.penanggungJawab', 'jadwalTes.pengujiTajwid', 'jadwalTes.pengujiTahsin', 'jadwalTes.pengujiKelancaran', 'jadwalTes.pengujiWawancara'])->get();
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
            ? 'nilai-' . str_replace(' ', '-', $jadwal->id_jadwal) . '.pdf'
            : 'nilai-semua-mahasantri.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    /**
     * Cetak PDF — laporan overall (summary & detail per gelombang)
     */
    public function cetakOverall()
    {
        $totalMahasantri = User::count();
        $totalLulus = HasilTes::where('status', 'Lulus')->count();
        $totalTidakLulus = HasilTes::where('status', 'Tidak Lulus')->count();
        $totalPertimbangan = HasilTes::where('status', 'Pertimbangan')->count();
        $totalBelumTes = HasilTes::where('status', 'Belum Tes')->count();

        $summary = [
            'total_mahasantri'   => $totalMahasantri,
            'total_lulus'        => $totalLulus,
            'total_tidak_lulus'  => $totalTidakLulus,
            'total_pertimbangan' => $totalPertimbangan,
            'total_belum_tes'    => $totalBelumTes,
            'total_jadwal'       => JadwalTes::count(),
        ];

        // Ambil semua data dengan eager loading
        $semuaHasil = HasilTes::with([
            'mahasantri',
            'jadwalTes.mahasantri',
            'jadwalTes.penanggungJawab',
            'jadwalTes.pengujiTajwid',
            'jadwalTes.pengujiTahsin',
            'jadwalTes.pengujiKelancaran',
            'jadwalTes.pengujiWawancara',
        ])->get();

        // Kelompokkan per gelombang berdasarkan id_mahasantri
        $gelombangData = [];
        foreach ($semuaHasil as $hasil) {
            $mhs = $hasil->jadwalTes->mahasantri ?? $hasil->mahasantri;
            if (!$mhs) continue;

            $gelombang = User::extractGelombangNama($mhs->id_mahasantri);

            if (!isset($gelombangData[$gelombang])) {
                $gelombangData[$gelombang] = [
                    'nama' => $gelombang,
                    'penanggung_jawab' => $hasil->jadwalTes->penanggungJawab?->nama_lengkap ?? '-',
                    'mahasantri' => [],
                ];
            }

            $gelombangData[$gelombang]['mahasantri'][] = $hasil;
        }

        // Urutkan gelombang
        ksort($gelombangData);

        $html = view('menu.laporan.pdf-overall', [
            'summary'       => $summary,
            'gelombangData' => $gelombangData,
            'date'          => now()->format('d/m/Y H:i'),
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