<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\HasilTes;
use App\Models\JadwalTes;
use Barryvdh\DomPDF\Facade\Pdf; 

class TestResultPdf extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $mahasantri;
    public $hasil;

    public function __construct(User $mahasantri, HasilTes $hasil)
    {
        $this->mahasantri = $mahasantri;
        $this->hasil = $hasil;
    }

    public function build()
    {
        // 1. Tarik data jadwal beserta nilai dari pengujinya (relasi jadwalPenguji)
        // Kita cocokan berdasarkan id_jadwal yang ada di tabel hasil_seleksi
        $jadwal = JadwalTes::with('jadwalPenguji.panitia')
            ->where('id_jadwal', $this->hasil->id_jadwal)
            ->first();

        $suratMeta = $this->buildSuratKelulusanMeta();

        // 2. Generate PDF dan pastikan variabel $jadwal ikut dikirim!
        $pdf = Pdf::loadView('menu.laporan.pdf-nilai-single', [
            'mahasantri' => $this->mahasantri,
            'hasil'      => $this->hasil,
            'jadwal'     => $jadwal, // <-- Ini yang bikin error kemarin, sekarang sudah disuntikkan!
            'nomorSurat' => $suratMeta['nomor_surat'],
            'tahunAjaran' => $suratMeta['tahun_ajaran'],
            'tahunAjaranBerikutnya' => $suratMeta['tahun_ajaran_berikutnya'],
            'labelTahunAjaran' => $suratMeta['label_tahun_ajaran'],
        ])->setPaper('A4');

        return $this->subject('Hasil Ujian Mahasantri Baru')
                    ->view('emails.result-pdf')
                    ->attachData($pdf->output(), 'Surat-Kelulusan-'.$this->mahasantri->nama_lengkap.'.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }

    private function buildSuratKelulusanMeta(): array
    {
        $tahunAjaran = User::extractTahunAjaran($this->mahasantri->id_mahasantri);
        $tahunAjaranBerikutnya = $tahunAjaran + 1;
        $idNum = preg_replace('/[^0-9]/', '', $this->mahasantri->id_mahasantri ?? '001');

        return [
            'tahun_ajaran' => $tahunAjaran,
            'tahun_ajaran_berikutnya' => $tahunAjaranBerikutnya,
            'label_tahun_ajaran' => "{$tahunAjaran}/{$tahunAjaranBerikutnya}",
            'nomor_surat' => str_pad($idNum ?: '1', 3, '0', STR_PAD_LEFT) . "/PMB/RAMQ/{$tahunAjaran}",
        ];
    }
}
