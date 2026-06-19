<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\JadwalTes;
use App\Models\Panitia;

class JadwalApprovedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tanggal;
    public $ketua;
    public $jumlah;
    public $namaPenerima;

    public function __construct(string $tanggal, Panitia $ketua, int $jumlah, ?string $namaPenerima = null)
    {
        $this->tanggal = $tanggal;
        $this->ketua = $ketua;
        $this->jumlah = $jumlah;
        $this->namaPenerima = $namaPenerima;
    }

    public function build()
    {
        return $this->subject('✅ Jadwal Tanggal ' . $this->tanggal . ' Telah Disetujui')
                    ->markdown('emails.jadwal-approved');
    }
}
