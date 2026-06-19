<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JadwalRejectedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tanggal;
    public $catatan;
    public $namaPenerima;

    public function __construct(string $tanggal, string $catatan, ?string $namaPenerima = null)
    {
        $this->tanggal = $tanggal;
        $this->catatan = $catatan;
        $this->namaPenerima = $namaPenerima;
    }

    public function build()
    {
        return $this->subject('📝 Jadwal Tanggal ' . $this->tanggal . ' Perlu Revisi')
                    ->view('emails.jadwal-rejected');
    }
}
