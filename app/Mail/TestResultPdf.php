<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\HasilTes;
use Barryvdh\DomPDF\Facade\Pdf; // Pastikan Facade PDF di-import

class TestResultPdf extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $mahasantri;
    public $hasil;

    // Parameter diubah, kita tidak lagi melempar PDF mentah dari luar
    public function __construct(User $mahasantri, HasilTes $hasil)
    {
        $this->mahasantri = $mahasantri;
        $this->hasil = $hasil;
    }

    public function build()
    {
        // Generate PDF DI DALAM email menggunakan template yang SAMA PERSIS dengan tombol Print
        $pdf = Pdf::loadView('menu.laporan.pdf-nilai-single', [
            'mahasantri' => $this->mahasantri,
            'hasil'      => $this->hasil,
        ])->setPaper('A4');

        return $this->subject('Hasil Ujian Mahasantri Baru')
                    ->view('emails.result-pdf')
                    ->attachData($pdf->output(), 'Surat-Kelulusan-'.$this->mahasantri->nama_lengkap.'.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}