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
    public $judulTanggal; // Simpan judul tanggal sebagai string biasa

    public function __construct(User $mahasantri, HasilTes $hasil, $judulTanggal)
    {
        $this->mahasantri = $mahasantri;
        $this->hasil = $hasil;
        $this->judulTanggal = $judulTanggal;
    }

    public function build()
    {
        // Generate PDF DI DALAM sini, sesaat sebelum email dikirim
        $pdf = Pdf::loadView('menu.laporan.pdf-single', [
            'judulTanggal' => $this->judulTanggal,
            'mahasantri'   => $this->mahasantri,
            'hasil'        => $this->hasil,
        ])->setPaper('A4');

        return $this->subject('Hasil Ujian Mahasantri Baru')
                    ->view('emails.result-pdf')
                    ->attachData($pdf->output(), 'hasil-ujian-'.$this->mahasantri->id_mahasantri.'.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}