<?php

namespace App\Mail;

use App\Models\JadwalTes;
use App\Models\Panitia;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JadwalCreatedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $jadwalTes;
    public $pembuat;
    public $jumlah;
    public $namaKetua;
    public $isRevision; // <-- Tambahkan properti flag baru

    /**
     * Create a new message instance.
     */
    public function __construct(JadwalTes $jadwalTes, Panitia $pembuat, $jumlah, $namaKetua, $isRevision = false)
    {
        $this->jadwalTes = $jadwalTes;
        $this->pembuat = $pembuat;
        $this->jumlah = $jumlah;
        $this->namaKetua = $namaKetua;
        $this->isRevision = $isRevision; // <-- Set value flag
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Kondisikan subjek email berdasarkan status pembuatan / revisi
        $subject = $this->isRevision 
            ? 'Pemberitahuan: Perbaikan / Revisi Jadwal Ujian Seleksi Baru' 
            : 'Pemberitahuan: Pengajuan Jadwal Ujian Seleksi Baru';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.jadwal-created', // Pastikan mengarah ke file view email kamu
        );
    }
}