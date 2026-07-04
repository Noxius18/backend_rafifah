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
    public $isUpdate; // <-- Properti flag baru

    /**
     * Create a new message instance.
     */
    public function __construct(JadwalTes $jadwalTes, User $mahasantri, bool $isUpdate = false)
    {
        $this->jadwalTes = $jadwalTes;
        $this->mahasantri = $mahasantri;
        $this->isUpdate = $isUpdate; // Default false (pengingat biasa)
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // Subjek dinamis membedakan pengingat biasa dan perbaruan data info
        $subject = $this->isUpdate 
            ? '🔄 Perbaruan Informasi Jadwal Ujian Seleksi Anda' 
            : 'Jadwal Ujian Anda: Link Zoom Siap';

        return $this->subject($subject)
                    ->view('emails.zoom-reminder');
    }
}