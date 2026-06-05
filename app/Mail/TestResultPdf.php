<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\HasilTes;
use Barryvdh\DomPDF\PDF as DomPDF;

class TestResultPdf extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $mahasantri;
    public $hasil;
    public $pdfInline;

    /**
     * Create a new message instance.
     */
    public function __construct(User $mahasantri, HasilTes $hasil, $pdfOutput)
    {
        $this->mahasantri = $mahasantri;
        $this->hasil = $hasil;
        $this->pdfInline = $pdfOutput;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Hasil Ujian Mahasantri Baru')
                    ->view('emails.result-pdf')
                    ->attachData($this->pdfInline, 'hasil-ujian-'.$this->mahasantri->id_mahasantri.'.pdf', [
                        'mime' => 'application/pdf',
                    ]);
    }
}