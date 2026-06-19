<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JadwalCancelledNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tanggal;
    public $pembatal;
    public $alasan;
    public $jenis;
    public $namaPenerima;

    public function __construct(string $tanggal, string $pembatal, string $alasan, string $jenis, ?string $namaPenerima = null)
    {
        $this->tanggal = $tanggal;
        $this->pembatal = $pembatal;
        $this->alasan = $alasan;
        $this->jenis = $jenis;
        $this->namaPenerima = $namaPenerima;
    }

    public function build()
    {
        $subject = $this->jenis === 'Dibatalkan'
            ? '❌ Jadwal ' . $this->tanggal . ' Dibatalkan'
            : '🔄 Jadwal ' . $this->tanggal . ' Dijadwalkan Ulang';

        return $this->subject($subject)
                    ->view('emails.jadwal-cancelled');
    }
}
