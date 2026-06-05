<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JadwalTes;
use App\Models\User;
use App\Models\HasilTes;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestResultPdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class SendBulkTestResults extends Command
{
    protected $signature = 'results:send-bulk {--date=} {--time=}';
    protected $description = 'Send bulk test results PDFs to all mahasantri for a given date';

    public function handle()
    {
        $date = $this->option('date') ?: Carbon::today()->toDateString();
        $time = $this->option('time') ?: null;

        $query = JadwalTes::where('tanggal', $date);
        if ($time) {
            $query->where('jam', $time);
        }
        $scheduled = $query->with(['mahasantri', 'hasilTes'])->get();

        $this->info("Found {$scheduled->count()} scheduled exams for {$date}" . ($time ? " at {$time}" : '') . ". Sending results...");

        foreach ($scheduled as $jadwal) {
            $mahasantri = $jadwal->mahasantri;
            $hasil = $jadwal->hasilTes->first();

            if (!$mahasantri || !$hasil) {
                $this->warn("No mahasiswa or result found for jadwal {$jadwal->id_jadwal} - skipping");
                continue;
            }

            $pdf = Pdf::loadView('menu.laporan.pdf-single', [
                'judulTanggal' => $jadwal->tanggal . ' – ' . $jadwal->jam,
                'mahasantri'   => $mahasantri,
                'hasil'        => $hasil,
            ])->setPaper('A4');
            $pdfOutput = $pdf->output();

            Mail::to($mahasantri->email)->send(new TestResultPdf($mahasantri, $hasil, $pdfOutput));
            $this->line("Sent test result to {$mahasantri->nama_lengkap} ({$mahasantri->id_mahasantri})");
        }

        $this->info('Bulk test results sending completed.');
        return 0;
    }
}
