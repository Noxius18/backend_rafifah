<?php

namespace App\Services\Mahasantri;

use App\Jobs\DownloadGoogleDriveFile;
use App\Models\Berkas;
use App\Models\Gelombang;
use App\Models\HasilTes;
use App\Models\JadwalPenguji;
use App\Models\JadwalTes;
use App\Models\Orangtua;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Menyimpan workflow mahasantri yang melibatkan lebih dari satu model:
 * pembuatan ID, verifikasi, sinkronisasi berkas, dan hapus massal.
 */
class MahasantriWorkflowService
{
    /**
     * Membuat mahasantri baru dengan gelombang yang ditentukan otomatis dari tanggal daftar.
     */
    public function createMahasantri(array $validated): User
    {
        $tanggalDaftar = now();
        $gelombang = $this->detectGelombang($tanggalDaftar);

        if (!$gelombang) {
            throw new \RuntimeException('Tidak ada gelombang yang aktif untuk tanggal ini. Periksa konfigurasi gelombang.');
        }

        $validated['id_mahasantri'] = User::generateId($tanggalDaftar->format('Y'), $gelombang['id']);
        $validated['status'] = 'Pendaftar Baru';
        $validated['tanggal_daftar'] = $tanggalDaftar;

        return User::create($validated);
    }

    /**
     * Hapus satu tahun ajaran penuh dalam transaksi agar statistik dan data fisik
     * tetap konsisten.
     */
    public function deleteByTahunAjaran(bool $alsoDeleteFiles): array
    {
        $targetGelombang = Gelombang::activeOrFirst();

        if (!$targetGelombang) {
            throw new \RuntimeException('Gagal menghapus: belum ada konfigurasi gelombang di sistem.');
        }

        return DB::transaction(function () use ($targetGelombang, $alsoDeleteFiles) {
            $stats = $this->deleteBerkasFilesForPrefix($targetGelombang->tahunPrefix(), $alsoDeleteFiles);
            $stats['tahun_ajaran'] = $targetGelombang->tahunAjaran();

            return $stats;
        });
    }

   /**
     * Saat verifikasi manual berhasil, sistem mencoba menyalin pola jadwal terakhir
     * pada gelombang yang sama agar mahasantri baru langsung masuk antrean seleksi.
     */
    public function verifyAndSchedule(User $mahasantri): bool
    {
        $mahasantri->update(['status' => 'Terverifikasi']);

        $prefixGelombang = substr($mahasantri->id_mahasantri, 0, 4);
        $lastJadwal = JadwalTes::whereHas('mahasantri', function ($query) use ($prefixGelombang) {
            $query->where('id_mahasantri', 'LIKE', $prefixGelombang . '%');
        })->orderBy('tanggal', 'desc')->orderBy('jam', 'desc')->first();

        if (!$lastJadwal) {
            return false;
        }

        $interval = $lastJadwal->interval ?? 30;
        $newJam = Carbon::parse($lastJadwal->jam)->addMinutes($interval)->format('H:i:s');
        $nextJadwalNum = ((int) JadwalTes::query()
            ->pluck('id_jadwal')
            ->map(fn($id) => (int) preg_replace('/^\D+/', '', $id))
            ->max()) + 1;
        $newId = 'JDS' . str_pad((string) $nextJadwalNum, 2, '0', STR_PAD_LEFT);

        // Buat data jadwal baru status 'Menunggu'
        $newJadwal = JadwalTes::create([
            'id_jadwal' => $newId,
            'id_mahasantri' => $mahasantri->id_mahasantri,
            'tanggal' => $lastJadwal->tanggal,
            'jam' => $newJam,
            'interval' => $interval,
            'link_zoom' => $lastJadwal->link_zoom,
            'penanggung_jawab' => $lastJadwal->penanggung_jawab,
            'status_jadwal' => 'Menunggu',
        ]);

        foreach (JadwalPenguji::where('id_jadwal', $lastJadwal->id_jadwal)->get() as $pengujiRow) {
            JadwalPenguji::create([
                'id_jadwal' => $newId,
                'id_panitia' => $pengujiRow->id_panitia,
                'aspek_penguji' => $pengujiRow->aspek_penguji,
            ]);
        }

        // ====================================================================
        // FIX BUG 1: KIRIM EMAIL NOTIFIKASI APPROVAL KE KETUA PANITIA SECARA OTOMATIS
        // ====================================================================
        $ketuaPanitiaList = \App\Models\Panitia::where('jabatan', 'Ketua Panitia')->get();
        $pembuat = \App\Models\Panitia::find($newJadwal->penanggung_jawab);
        
        if ($pembuat) {
            foreach ($ketuaPanitiaList as $ketua) {
                if ($ketua->email) {
                    \Illuminate\Support\Facades\Mail::to($ketua->email)->send(new \App\Mail\JadwalCreatedNotification(
                        $newJadwal,
                        $pembuat,
                        1, // 1 Mahasantri baru auto-generate
                        $ketua->nama_lengkap,
                        false // Parameter isRevision = false karena ini jadwal baru
                    ));
                }
            }
        }

        return true;
    }

    /**
     * Update status verifikasi dokumen dan data pribadi sekaligus.
     * Jika seluruh syarat terpenuhi, status mahasantri dinaikkan otomatis.
     */
    public function updateBerkas(Berkas $berkas, array $validated, array $input): array
    {
        $berkas->update([
            'status_verifikasi' => $validated['status_verifikasi'],
        ]);

        $mahasantriData = [];
        foreach (['nik', 'nisn', 'tempat_lahir', 'tanggal_lahir'] as $field) {
            if (array_key_exists($field, $input)) {
                $mahasantriData[$field] = $validated[$field] ?? null;
            }
        }

        if ($mahasantriData !== []) {
            $berkas->mahasantri()->update($mahasantriData);
        }

        $mahasantri = $berkas->mahasantri;
        if ($mahasantri && $mahasantri->status === 'Pendaftar Baru') {
            // Auto-verifikasi hanya dilakukan bila semua dokumen yang berhasil
            // diunduh sudah diverifikasi dan biodata inti lengkap.
            $allBerkasValid = $mahasantri->berkas()
                ->where('status_verifikasi', false)
                ->whereHas('riwayatUnduhan', function ($query) {
                    $query->where('download_status', 'success');
                })
                ->doesntExist();

            $dataLengkap = !empty($mahasantri->nik)
                && !empty($mahasantri->nisn)
                && !empty($mahasantri->tempat_lahir)
                && !empty($mahasantri->tanggal_lahir);

            if ($allBerkasValid && $dataLengkap) {
                $mahasantri->update(['status' => 'Terverifikasi']);
            }
        }

        return [
            'status_verifikasi' => (bool) $validated['status_verifikasi'],
        ];
    }

    /**
     * Menjadwalkan ulang unduh berkas dengan membuat attempt baru lebih dulu
     * agar riwayat proses tetap terlacak.
     */
    public function retryDownload(Berkas $berkas): void
    {
        $berkas->riwayatUnduhan()->create([
            'download_status' => 'pending',
            'error_message' => null,
        ]);

        DownloadGoogleDriveFile::dispatch($berkas);
    }

    /**
     * Metadata surat dipisah ke service agar logic format nomor surat
     * tidak tersebar di controller/view.
     */
    public function buildSuratKelulusanMeta(User $mahasantri): array
    {
        $tahunAjaran = User::extractTahunAjaran($mahasantri->id_mahasantri);
        $tahunAjaranBerikutnya = $tahunAjaran + 1;
        $idNum = preg_replace('/[^0-9]/', '', $mahasantri->id_mahasantri ?? '001');

        return [
            'tahun_ajaran' => $tahunAjaran,
            'tahun_ajaran_berikutnya' => $tahunAjaranBerikutnya,
            'label_tahun_ajaran' => "{$tahunAjaran}/{$tahunAjaranBerikutnya}",
            'nomor_surat' => str_pad($idNum ?: '1', 3, '0', STR_PAD_LEFT) . "/PMB/RAMQ/{$tahunAjaran}",
        ];
    }

    /**
     * Menghitung statistik hapus sebelum delete benar-benar dijalankan agar
     * pesan hasil operasi bisa tetap informatif.
     */
    private function deleteBerkasFilesForPrefix(string $idPrefix, bool $alsoDeleteFiles): array
    {
        $deleted = ['mahasantri' => 0, 'orangtua' => 0, 'berkas' => 0, 'jadwal' => 0, 'hasil_tes' => 0, 'files' => 0];

        $mahasantriIds = User::where('id_mahasantri', 'LIKE', $idPrefix . '%')
            ->pluck('id_mahasantri')
            ->toArray();

        if ($mahasantriIds === []) {
            return $deleted;
        }

        $deleted['mahasantri'] = count($mahasantriIds);
        $deleted['orangtua'] = Orangtua::whereIn('id_mahasantri', $mahasantriIds)->count();
        $deleted['berkas'] = Berkas::whereIn('id_mahasantri', $mahasantriIds)->count();
        $deleted['jadwal'] = JadwalTes::whereIn('id_mahasantri', $mahasantriIds)->count();
        $deleted['hasil_tes'] = HasilTes::whereHas('jadwalTes', function ($query) use ($mahasantriIds) {
            $query->whereIn('id_mahasantri', $mahasantriIds);
        })->count();

        if ($alsoDeleteFiles) {
            foreach (Berkas::whereIn('id_mahasantri', $mahasantriIds)->whereNotNull('file_path')->get() as $berkas) {
                if ($berkas->file_exists) {
                    Storage::disk('private_berkas')->delete($berkas->file_path);
                    $deleted['files']++;
                }
            }
        }

        User::whereIn('id_mahasantri', $mahasantriIds)->delete();

        return $deleted;
    }

    private function detectGelombang(Carbon $tanggal): ?array
    {
        $gelombang = Gelombang::orderBy('start_date')->get(['id', 'nama', 'start_date', 'end_date']);

        foreach ($gelombang as $setting) {
            $start = Carbon::parse($setting->start_date)->startOfDay();
            $end = Carbon::parse($setting->end_date)->endOfDay();

            if ($tanggal->between($start, $end)) {
                return [
                    'nama' => $setting->nama,
                    'id' => $setting->id,
                ];
            }
        }

        return null;
    }
}
