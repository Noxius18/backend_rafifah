<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\JadwalTes;
use App\Models\Panitia;

class JadwalCreatedNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $jadwal;
    public $pembuat;
    public $jumlahMahasantri;

    public function __construct(JadwalTes $jadwal, Panitia $pembuat, int $jumlahMahasantri)
    {
        $this->jadwal = $jadwal;
        $this->pembuat = $pembuat;
        $this->jumlahMahasantri = $jumlahMahasantri;
    }

    public function build()
    {
        return $this->subject('🔔 Jadwal Baru Perlu Persetujuan - ' . $this->jadwal->tanggal)
                    ->view('emails.jadwal-created');
    }
}
