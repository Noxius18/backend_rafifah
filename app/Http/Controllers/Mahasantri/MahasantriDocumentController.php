<?php

namespace App\Http\Controllers\Mahasantri;

use App\Http\Controllers\Controller;
use App\Models\JadwalTes;
use App\Models\User;
use App\Services\Mahasantri\MahasantriWorkflowService;
use Dompdf\Dompdf;
use Dompdf\Options;

class MahasantriDocumentController extends Controller
{
    public function __construct(private readonly MahasantriWorkflowService $workflowService)
    {
    }

    public function cetakPdf(User $mahasantri)
    {
        $jadwal = JadwalTes::where('id_mahasantri', $mahasantri->id_mahasantri)
            ->with('hasilTes', 'jadwalPenguji.panitia')
            ->first();

        $hasilTes = $jadwal && $jadwal->hasilTes ? collect([$jadwal->hasilTes]) : collect();
        $suratMeta = $this->workflowService->buildSuratKelulusanMeta($mahasantri);

        $html = view('menu.laporan.pdf-nilai-single', [
            'mahasantri' => $mahasantri,
            'hasilTes' => $hasilTes,
            'jadwal' => $jadwal,
            'date' => now()->format('d/m/Y H:i'),
            'nomorSurat' => $suratMeta['nomor_surat'],
            'tahunAjaran' => $suratMeta['tahun_ajaran'],
            'tahunAjaranBerikutnya' => $suratMeta['tahun_ajaran_berikutnya'],
            'labelTahunAjaran' => $suratMeta['label_tahun_ajaran'],
        ])->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'nilai-' . str_replace(' ', '-', $mahasantri->nama_lengkap) . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
