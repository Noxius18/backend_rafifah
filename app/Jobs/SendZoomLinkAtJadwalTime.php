<?php

namespace App\Jobs;

use App\Mail\ZoomLinkReminder;
use App\Models\JadwalTes;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendZoomLinkAtJadwalTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $jadwalId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $jadwalId)
    {
        $this->jadwalId = $jadwalId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jadwalTes = JadwalTes::find($this->jadwalId);
        if (!$jadwalTes || !$jadwalTes->link_zoom || !$jadwalTes->mahasantri) {
            return;
        }

        Mail::to($jadwalTes->mahasantri->email)
            ->send(new ZoomLinkReminder($jadwalTes, $jadwalTes->mahasantri));
    }
}