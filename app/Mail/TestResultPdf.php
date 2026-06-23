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

        // 2. Generate PDF dan pastikan variabel $jadwal ikut dikirim!
        $pdf = Pdf::loadView('menu.laporan.pdf-nilai-single', [
            'mahasantri' => $this->mahasantri,
            'hasil'      => $this->hasil,
            'jadwal'     => $jadwal, // <-- Ini yang bikin error kemarin, sekarang sudah disuntikkan!
        ])->setPaper('A4');

        return $this->subject('Hasil Ujian Mahasantri Baru')
                    ->view('emails.result-pdf')
                    ->attachData($pdf->output(), 'Surat-Kelulusan-'.$this->mahasantri->nama_lengkap.'.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}