<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\JadwalTes;
use App\Models\User;

class ZoomLinkReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $jadwalTes;
    public $mahasantri;

    /**
     * Create a new message instance.
     */
    public function __construct(JadwalTes $jadwalTes, User $mahasantri)
    {
        $this->jadwalTes = $jadwalTes;
        $this->mahasantri = $mahasantri;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Jadwal Ujian Anda: Link Zoom Siap')
                    ->view('emails.zoom-reminder');
    }
}