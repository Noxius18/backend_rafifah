<?php

namespace App\Http\Controllers;

use App\Models\HasilTes;
use App\Models\JadwalTes;
use App\Models\User;
use App\Models\Gelombang;
use Carbon\Carbon;
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
            $jadwal = JadwalTes::with(['penanggungJawab', 'mahasantri', 'jadwalPenguji.panitia'])
                ->where('kode_jadwal', $idJadwal)
                ->firstOrFail();
            $hasilTes = HasilTes::where('jadwal_id', $jadwal->id)
                ->with(['mahasantri', 'jadwalTes.mahasantri', 'jadwalTes.penanggungJawab', 'jadwalTes.jadwalPenguji.panitia'])
                ->get();
        } else {
            $jadwal = null;
            $hasilTes = HasilTes::with(['mahasantri', 'jadwalTes.mahasantri', 'jadwalTes.penanggungJawab', 'jadwalTes.jadwalPenguji.panitia'])->get();
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
            ? 'nilai-' . str_replace(' ', '-', $jadwal->kode_jadwal) . '.pdf'
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

        // Mahasantri belum tes (tidak ada di hasil_seleksi)
        $mahasantriBelumTes = User::where('status', 'Terverifikasi')
            ->whereNotIn('id_mahasantri', function($query) {
                $query->select('js.id_mahasantri')
                    ->from('hasil_seleksi as hs')
                    ->join('jadwal_seleksi as js', 'hs.jadwal_id', '=', 'js.id');
            })
            ->get();

        $summary = [
            'total_mahasantri'   => $totalMahasantri,
            'total_lulus'        => $totalLulus,
            'total_tidak_lulus'  => $totalTidakLulus,
            'total_pertimbangan' => $totalPertimbangan,
            'total_belum_tes'    => $totalBelumTes,
            'total_jadwal'       => JadwalTes::count(),
        ];

        $gelombangByTahunDanNama = Gelombang::orderBy('start_date')
            ->get()
            ->keyBy(function ($gelombang) {
                return $gelombang->tahunAjaran() . ':' . $gelombang->nama;
            });

        // Ambil semua data dengan eager loading
        $semuaHasil = HasilTes::with([
            'mahasantri',
            'jadwalTes.mahasantri',
            'jadwalTes.penanggungJawab',
            'jadwalTes.jadwalPenguji.panitia',
        ])->get();

        // Kelompokkan per gelombang berdasarkan id_mahasantri
        $gelombangData = [];
        foreach ($semuaHasil as $hasil) {
            $mhs = $hasil->jadwalTes->mahasantri ?? $hasil->mahasantri;
            if (!$mhs) {
                continue;
            }

            $tahunAjaran = User::extractTahunAjaran($mhs->id_mahasantri);
            $gelombangNama = User::extractGelombangNama($mhs->id_mahasantri);
            $groupKey = sprintf('%04d-%02d', $tahunAjaran, User::extractGelombangNomor($mhs->id_mahasantri));
            $gelombang = $gelombangByTahunDanNama->get($tahunAjaran . ':' . $gelombangNama);

            $periode = $gelombang
                ? Carbon::parse($gelombang->start_date)->locale('id')->translatedFormat('d F Y') . ' - ' .
                    Carbon::parse($gelombang->end_date)->locale('id')->translatedFormat('d F Y')
                : "Tahun Ajaran {$tahunAjaran}";

            if (!isset($gelombangData[$groupKey])) {
                $gelombangData[$groupKey] = [
                    'nama' => $gelombangNama,
                    'tahun_ajaran' => $tahunAjaran,
                    'penanggung_jawab' => $hasil->jadwalTes->penanggungJawab?->nama_lengkap ?? '-',
                    'mahasantri' => [],
                    'periode' => $periode,
                ];
            }

            $gelombangData[$groupKey]['mahasantri'][] = $hasil;
        }

        // Urutkan gelombang
        ksort($gelombangData);

        $reportYears = collect($gelombangData)
            ->pluck('tahun_ajaran')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $reportYearLabel = match ($reportYears->count()) {
            0 => 'Tanpa Tahun Ajaran',
            1 => 'Tahun Ajaran ' . $reportYears->first(),
            default => 'Lintas Tahun Ajaran (' . $reportYears->implode(', ') . ')',
        };

        $html = view('menu.laporan.pdf-overall', [
            'summary'          => $summary,
            'gelombangData'    => $gelombangData,
            'mahasantriBelumTes' => $mahasantriBelumTes,
            'date'             => now()->format('d/m/Y H:i'),
            'reportYearLabel'  => $reportYearLabel,
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

    /**
     * Cetak PDF — laporan panitia per gelombang (Penanggung jawab & List Penguji)
     */
    public function cetakLaporanPanitia(Request $request, $id)
    {
        // 1. Ambil data gelombang
        $gelombang = Gelombang::findOrFail($id);

        // 2. Ambil jadwal tes beserta Penanggung Jawab dan Penguji
        $jadwalTes = JadwalTes::with(['penanggungJawab', 'jadwalPenguji.panitia'])
            ->whereBetween('tanggal', [$gelombang->start_date, $gelombang->end_date])
            ->get();

        // 3. Ambil Penanggung Jawab dari jadwal tes (diambil dari jadwal pertama)
        $penanggungJawab = $jadwalTes->first()?->penanggungJawab;

        // 4. Ekstrak daftar Penguji unik & gabungkan bidang ujinya
        $pengujiList = collect();
        foreach ($jadwalTes as $jadwal) {
            foreach ($jadwal->jadwalPenguji as $jp) {
                if ($jp->panitia) {
                    $panitiaId = $jp->id_panitia;

                    // Jika penguji belum ada di list, masukkan
                    if (!$pengujiList->has($panitiaId)) {
                        $pengujiList->put($panitiaId, [
                            'nama'       => $jp->panitia->nama_lengkap,
                            'bidang_uji' => [] 
                        ]);
                    }

                    // Tarik data sementara untuk di-update
                    $dataPenguji = $pengujiList->get($panitiaId);
                    
                    // Jika bidang uji belum ada di array orang tersebut, tambahkan
                    if (!in_array($jp->aspek_penguji, $dataPenguji['bidang_uji'])) {
                        $dataPenguji['bidang_uji'][] = $jp->aspek_penguji;
                    }

                    // Kembalikan data yang sudah di-update ke collection
                    $pengujiList->put($panitiaId, $dataPenguji);
                }
            }
        }

        // 5. Ubah array bidang uji menjadi string yang dipisahkan koma
        $formattedPengujiList = $pengujiList->map(function ($item) {
            $item['bidang_uji'] = implode(', ', $item['bidang_uji']);
            return $item;
        })->values();

        // 6. Render HTML dari file blade
        $html = view('menu.laporan.pdf-panitia', [
            'gelombang'       => $gelombang,
            'penanggungJawab' => $penanggungJawab,
            'pengujiList'     => $formattedPengujiList, 
            'date'            => now()->format('d/m/Y H:i'),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'Laporan-Panitia-Seleksi-' . str_replace(' ', '-', $gelombang->nama ?? 'Gelombang') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }

    public function cetakSemuaMahasantri()
    {
        // Tarik seluruh data mahasantri lengkap dengan relasi orang tua
        $mahasantris = User::with('orangtuas')->orderBy('id_mahasantri')->get();

        $html = view('menu.laporan.pdf-semua-mahasantri', [
            'mahasantris' => $mahasantris,
            'date'        => now()->format('d/m/Y H:i'),
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        // Pakai landscape karena kolomnya sangat banyak mirip excel
        $dompdf->setPaper('A4', 'landscape'); 
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="data-seluruh-mahasantri.pdf"');
    }
}
