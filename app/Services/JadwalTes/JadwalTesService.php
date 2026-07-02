<?php

namespace App\Services\JadwalTes;

use App\Mail\JadwalCancelledNotification;
use App\Mail\JadwalCreatedNotification;
use App\Mail\TestResultPdf;
use App\Mail\ZoomLinkReminder;
use App\Models\Gelombang;
use App\Models\JadwalPenguji;
use App\Models\JadwalTes;
use App\Models\Panitia;
use App\Models\ScheduleStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Menampung orchestration operasional jadwal tes dari sisi panitia:
 * listing, pembuatan batch, edit, bulk update, dan notifikasi.
 */
class JadwalTesService
{
    /**
     * Menyiapkan seluruh data turunan yang dibutuhkan halaman index seleksi,
     * termasuk ringkasan modal dan grouping per tanggal.
     */
    public function indexData(): array
    {
        $jadwals = JadwalTes::with(['penanggungJawab', 'mahasantri', 'jadwalPenguji.panitia', 'hasilTes'])->paginate(10);

        return [
            'jadwals' => $jadwals,
            'panitias' => Panitia::where('jabatan', 'Panitia')->get(),
            'gelombangs' => Gelombang::orderBy('start_date')->get(['id', 'nama', 'start_date', 'end_date']),
            'activeGelombang' => Gelombang::activeOrFirst(Carbon::today()),
            'nilaiPerAspekByJadwal' => $this->buildNilaiPerAspekByJadwal($jadwals),
            'aspekMapping' => $this->aspectSlugMap(),
            'totalMenunggu' => $jadwals->filter(fn($jadwal) => in_array($jadwal->status_jadwal, ['Menunggu', 'Revisi']))->count(),
            'totalPerluReview' => $jadwals->filter(fn($jadwal) => $jadwal->hasilTes && $jadwal->hasilTes->status === 'Pertimbangan')->count(),
            'totalRevisi' => $jadwals->filter(fn($jadwal) => $jadwal->status_jadwal === 'Revisi')->count(),
            'jadwalsByTanggal' => $this->buildPendingGroups($jadwals),
            'jadwalsRevisiByTanggal' => $this->buildRevisionGroups($jadwals),
        ];
    }

    /**
     * Membuat satu batch jadwal untuk semua mahasantri terverifikasi yang
     * belum pernah dijadwalkan pada gelombang terkait.
     */
    public function createSchedules(array $validated, string $creatorId): array
    {
        $tanggal = Carbon::parse($validated['tanggal']);
        $gelombang = Gelombang::findByDate($tanggal);

        if (!$gelombang) {
            throw new \RuntimeException('Tanggal ' . $validated['tanggal'] . ' tidak masuk rentang gelombang manapun.');
        }

        $prefixId = $gelombang->tahunPrefix() . str_pad((string) $gelombang->id, 2, '0', STR_PAD_LEFT);
        $allMahasantri = User::with('jadwalTes')->where('id_mahasantri', 'LIKE', $prefixId . '%')->get();
        $verifiedMahasantriTotal = $allMahasantri->where('status', 'Terverifikasi');
        $verifiedMahasantri = $verifiedMahasantriTotal->filter(fn($m) => $m->jadwalTes->isEmpty());
        $unverifiedMahasantri = $allMahasantri->filter(fn($m) => $m->status === 'Pendaftar Baru');

        if ($verifiedMahasantriTotal->isEmpty()) {
            throw new \RuntimeException(
                'Tidak ada mahasantri dengan status Terverifikasi di ' . $gelombang->nama . '. Silakan verifikasi berkas pendaftar terlebih dahulu.'
            );
        }

        if ($verifiedMahasantri->isEmpty()) {
            throw new \RuntimeException('Semua mahasantri terverifikasi di ' . $gelombang->nama . ' sudah memiliki jadwal seleksi.');
        }

        $jamMulai = Carbon::createFromFormat('H:i', $validated['jam_mulai']);
        $interval = (int) $validated['interval'];
        $urut = ((int) JadwalTes::query()
            ->pluck('id_jadwal')
            ->map(fn($id) => (int) preg_replace('/^\D+/', '', $id))
            ->max()) + 1;
        $pengujiList = $this->extractPengujiList($validated);

        $count = 0;
        $newJadwal = null;

        // Satu tanggal input menghasilkan banyak row jadwal, masing-masing
        // diberi jam berurutan berdasarkan interval yang sama.
        foreach ($verifiedMahasantri as $mhs) {
            $idJadwal = 'JDS' . str_pad((string) $urut, 2, '0', STR_PAD_LEFT);
            $urut++;

            $newJadwal = JadwalTes::create([
                'id_jadwal' => $idJadwal,
                'id_mahasantri' => $mhs->id_mahasantri,
                'tanggal' => $validated['tanggal'],
                'jam' => $jamMulai->format('H:i'),
                'interval' => $interval,
                'link_zoom' => $validated['link_zoom'] ?? null,
                'penanggung_jawab' => $creatorId,
                'status_jadwal' => 'Menunggu',
            ]);

            foreach ($pengujiList as $penguji) {
                JadwalPenguji::create([
                    'id_jadwal' => $idJadwal,
                    'id_panitia' => $penguji['id_panitia'],
                    'aspek_penguji' => $penguji['aspek'],
                ]);
            }

            $jamMulai->addMinutes($interval);
            $count++;
        }

        $this->notifyKetuaJadwalCreated($newJadwal, $creatorId, $count);

        return [
            'count' => $count,
            'gelombang_nama' => $gelombang->nama,
            'unscheduledMahasantri' => $unverifiedMahasantri
                ->map(fn($m) => ['id_mahasantri' => $m->id_mahasantri, 'nama_lengkap' => $m->nama_lengkap])
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Menggeser satu kelompok jadwal pada tanggal tertentu ke tanggal/jam baru,
     * lalu membangun ulang penugasan penguji untuk seluruh row pada kelompok itu.
     */
    public function updateByDate(string $tanggal, array $validated, string $creatorId): array
    {
        $tanggalBaruCarbon = Carbon::parse($validated['tanggal_baru']);
        $gelombang = Gelombang::where('start_date', '<=', $tanggalBaruCarbon->copy()->endOfDay())
            ->where('end_date', '>=', $tanggalBaruCarbon->copy()->startOfDay())
            ->first();

        if (!$gelombang) {
            throw new \RuntimeException('Tanggal baru ' . $validated['tanggal_baru'] . ' tidak masuk rentang gelombang manapun.');
        }

        $jadwals = JadwalTes::where('tanggal', $tanggal)
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->orderBy('jam')
            ->get();

        if ($jadwals->isEmpty()) {
            throw new \RuntimeException("Tidak ada jadwal dengan status Revisi/Menunggu di tanggal {$tanggal}.");
        }

        $jamMulai = Carbon::createFromFormat('H:i', $validated['jam_mulai']);
        foreach ($jadwals as $jadwal) {
            $jadwal->update([
                'tanggal' => $validated['tanggal_baru'],
                'jam' => $jamMulai->format('H:i'),
                'interval' => $validated['interval'],
                'link_zoom' => $validated['link_zoom'],
                'status_jadwal' => 'Menunggu',
                'catatan_perubahan' => null,
            ]);
            $jamMulai->addMinutes((int) $validated['interval']);
        }

        $jadwalIds = $jadwals->pluck('id_jadwal');
        JadwalPenguji::whereIn('id_jadwal', $jadwalIds)->delete();

        // Penugasan penguji dibangun ulang supaya satu perubahan batch tidak
        // meninggalkan row penguji lama yang sudah tidak relevan.
        foreach ($jadwalIds as $idJadwal) {
            foreach ($this->aspectFieldMap() as $field => $aspek) {
                if (!empty($validated[$field])) {
                    JadwalPenguji::create([
                        'id_jadwal' => $idJadwal,
                        'id_panitia' => $validated[$field],
                        'aspek_penguji' => $aspek,
                    ]);
                }
            }
        }

        $ketua = Panitia::where('jabatan', 'Ketua Panitia')->first();
        if ($ketua && $ketua->email) {
            $pembuat = Panitia::findOrFail($creatorId);
            Mail::to($ketua->email)->send(new JadwalCreatedNotification(
                $jadwals->first(),
                $pembuat,
                $jadwals->count(),
                $ketua->nama_lengkap,
                true
            ));
        }

        return [
            'count' => $jadwals->count(),
            'formatted_date' => Carbon::parse($validated['tanggal_baru'])->format('d/m/Y'),
        ];
    }

    /**
     * Edit ringan pada satu jadwal tetap mengembalikan status ke Menunggu
     * agar ketua panitia melakukan review ulang.
     */
    public function updateSingleJadwal(JadwalTes $jadwalTes, array $validated, string $creatorId): void
    {
        if (Carbon::parse($jadwalTes->tanggal)->lt(Carbon::today())) {
            throw new \RuntimeException('Jadwal tidak dapat diubah karena sudah berlangsung');
        }

        $jadwalTes->update(array_merge($validated, [
            'status_jadwal' => 'Menunggu',
            'catatan_ketua' => null,
            'catatan_perubahan' => null,
        ]));

        $pembuat = Panitia::findOrFail($creatorId);
        foreach (Panitia::where('jabatan', 'Ketua Panitia')->get() as $ketua) {
            if ($ketua->email) {
                Mail::to($ketua->email)->send(new JadwalCreatedNotification(
                    $jadwalTes,
                    $pembuat,
                    1,
                    $ketua->nama_lengkap,
                    true
                ));
            }
        }
    }

    public function updateLinkZoomMassal(string $tanggal, string $linkZoom): int
    {
        return JadwalTes::where('tanggal', $tanggal)->update(['link_zoom' => $linkZoom]);
    }

    /**
     * Notifikasi update memakai tabel status pengiriman untuk menandai bahwa
     * reminder berikutnya perlu dihitung ulang.
     */
    public function sendUpdateNotification(JadwalTes $jadwalTes): void
    {
        ScheduleStatus::updateOrCreate(
            ['id_jadwal' => $jadwalTes->id_jadwal],
            ['zoom_reminder_sent' => false, 'sent_at' => null]
        );

        Mail::to($jadwalTes->mahasantri->email)->send(new ZoomLinkReminder($jadwalTes, $jadwalTes->mahasantri));
    }

    /**
     * Pembatalan massal hanya berlaku untuk jadwal yang sudah disetujui,
     * lalu mengirim notifikasi ringkas ke ketua panitia.
     */
    public function bulkCancel(string $jenisPembatalan, string $alasanPembatalan): int
    {
        $tanggalLabel = $this->tanggalLabel(JadwalTes::where('status_jadwal', 'Disetujui'));

        $updated = JadwalTes::where('status_jadwal', 'Disetujui')->update([
            'status_jadwal' => $jenisPembatalan,
            'diproses_oleh' => auth()->user()->id_panitia,
            'catatan_perubahan' => $alasanPembatalan,
        ]);

        $ketua = Panitia::where('jabatan', 'Ketua Panitia')->first();
        if ($ketua && $ketua->email) {
            Mail::to($ketua->email)->send(new JadwalCancelledNotification(
                $tanggalLabel,
                auth()->user()->nama_lengkap,
                $alasanPembatalan,
                $jenisPembatalan,
                $ketua->nama_lengkap
            ));
        }

        return $updated;
    }

    /**
     * Menjadwalkan email hasil tes untuk gelombang aktif/terakhir.
     * Hanya mahasantri dengan status final yang akan diproses.
     */
    public function sendBulkResults(Carbon $waktuKirim): array
    {
        $today = Carbon::today();
        $activeGelombang = Gelombang::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first() ?? Gelombang::orderBy('end_date', 'desc')->first();

        if (!$activeGelombang) {
            throw new \RuntimeException('Gagal: Belum ada konfigurasi gelombang di sistem.');
        }

        $scheduled = JadwalTes::with(['mahasantri', 'hasilTes'])
            ->where('status_jadwal', 'Disetujui')
            ->whereBetween('tanggal', [$activeGelombang->start_date, $activeGelombang->end_date])
            ->get();

        if ($scheduled->isEmpty()) {
            throw new \RuntimeException("Tidak ada jadwal seleksi pada {$activeGelombang->nama}.");
        }

        $sentCount = 0;
        foreach ($scheduled as $jadwal) {
            $mahasantri = $jadwal->mahasantri;
            $hasil = $jadwal->hasilTes;

            // Status "Pertimbangan" sengaja dilewati karena masih menunggu keputusan ketua.
            if (!$mahasantri || !$hasil || !in_array($mahasantri->status, ['Lulus', 'Tidak Lulus'])) {
                continue;
            }

            Mail::to($mahasantri->email)->later($waktuKirim, new TestResultPdf($mahasantri, $hasil));
            $sentCount++;
        }

        return [
            'gelombang_nama' => $activeGelombang->nama,
            'sent_count' => $sentCount,
            'waktu_kirim' => $waktuKirim,
        ];
    }

    public function aspectFieldMap(): array
    {
        return [
            'penguji_bacaan_al_quran' => 'Bacaan Al-Quran',
            'penguji_tajwid_tahsin' => 'Tajwid/Tahsin',
            'penguji_hafalan' => 'Hafalan',
            'penguji_wawancara' => 'Wawancara',
        ];
    }

    private function aspectSlugMap(): array
    {
        return [
            'Bacaan Al-Quran' => 'bacaan_al_quran',
            'Tajwid/Tahsin' => 'tajwid_tahsin',
            'Hafalan' => 'hafalan',
            'Wawancara' => 'wawancara',
        ];
    }

    /**
     * Mengubah hasil relasi jadwal_penguji menjadi struktur key-value yang
     * lebih mudah langsung dipakai oleh modal di layer view.
     */
    private function buildNilaiPerAspekByJadwal($jadwals): array
    {
        $data = [];
        foreach ($jadwals as $jadwal) {
            foreach ($jadwal->jadwalPenguji as $jp) {
                $data[$jadwal->id_jadwal][$jp->aspek_penguji] = [
                    'nilai' => $jp->nilai,
                    'catatan' => $jp->catatan_penguji,
                    'penguji' => $jp->panitia?->nama_lengkap,
                    'id_panitia' => $jp->id_panitia,
                ];
            }
        }

        return $data;
    }

    /**
     * Grouping ini dipakai untuk modal review ketua panitia, sehingga data yang
     * disimpan cukup representatif per tanggal, bukan per row jadwal.
     */
    private function buildPendingGroups($jadwals): array
    {
        $groups = [];
        foreach ($jadwals->filter(fn($j) => in_array($j->status_jadwal, ['Menunggu', 'Revisi'])) as $jadwal) {
            $tgl = $jadwal->tanggal;
            $groups[$tgl] ??= [
                'tanggal' => $tgl,
                'total' => 0,
                'penguji' => [],
                'penanggung_jawab' => null,
            ];
            $groups[$tgl]['total']++;

            if ($groups[$tgl]['penguji'] === []) {
                foreach ($jadwal->jadwalPenguji as $jp) {
                    $groups[$tgl]['penguji'][$jp->aspek_penguji] = $jp->panitia?->nama_lengkap ?? '-';
                }
                $groups[$tgl]['penanggung_jawab'] = $jadwal->penanggungJawab?->nama_lengkap ?? '-';
            }
        }

        return array_values($groups);
    }

    /**
     * Grouping revisi membawa payload yang lebih kaya karena dipakai ulang
     * untuk form edit massal oleh panitia.
     */
    private function buildRevisionGroups($jadwals): array
    {
        $revisi = $jadwals->filter(fn($j) => $j->status_jadwal === 'Revisi');
        $groups = [];

        foreach ($revisi as $jadwal) {
            $tgl = $jadwal->tanggal;
            if (!isset($groups[$tgl])) {
                $minJam = $revisi->where('tanggal', $tgl)->min('jam');
                $penguji = [];

                foreach ($jadwal->jadwalPenguji as $jp) {
                    $key = array_search($jp->aspek_penguji, $this->aspectFieldMap(), true);
                    if ($key) {
                        $penguji[$key] = [
                            'id_panitia' => $jp->id_panitia,
                            'nama' => $jp->panitia?->nama_lengkap ?? '-',
                        ];
                    }
                }

                $groups[$tgl] = [
                    'tanggal' => $tgl,
                    'total' => 0,
                    'jam_mulai' => $minJam ? Carbon::parse($minJam)->format('H:i') : '',
                    'interval' => $jadwal->interval ?? 30,
                    'link_zoom' => $jadwal->link_zoom ?? '',
                    'catatan_perubahan' => $jadwal->catatan_perubahan ?? '',
                    'penanggung_jawab' => $jadwal->penanggungJawab?->nama_lengkap ?? '-',
                    'penguji' => $penguji,
                ];
            }

            $groups[$tgl]['total']++;
        }

        return array_values($groups);
    }

    /**
     * Menyamakan field form dengan label aspek yang dipakai di tabel jadwal_penguji.
     */
    private function extractPengujiList(array $validated): array
    {
        $pengujiList = [];
        foreach ($this->aspectFieldMap() as $field => $aspek) {
            if (!empty($validated[$field])) {
                $pengujiList[] = [
                    'id_panitia' => $validated[$field],
                    'aspek' => $aspek,
                ];
            }
        }

        return $pengujiList;
    }

    /**
     * Setelah batch jadwal dibuat, ketua panitia perlu diberi satu notifikasi
     * yang mewakili batch tersebut untuk proses approval.
     */
    private function notifyKetuaJadwalCreated(?JadwalTes $jadwal, string $creatorId, int $count): void
    {
        $ketuaPanitia = Panitia::where('jabatan', 'Ketua Panitia')->first();
        if (!$ketuaPanitia || !$ketuaPanitia->email) {
            return;
        }

        $pembuat = Panitia::findOrFail($creatorId);
        foreach (Panitia::where('jabatan', 'Ketua Panitia')->get() as $ketua) {
            Mail::to($ketua->email)->send(new JadwalCreatedNotification(
                $jadwal,
                $pembuat,
                $count,
                $ketua->nama_lengkap
            ));
        }
    }

    /**
     * Utility untuk menampilkan rentang tanggal yang manusiawi di notifikasi massal.
     */
    private function tanggalLabel($query): string
    {
        $tanggalRange = $query->selectRaw('MIN(tanggal) as tgl_awal, MAX(tanggal) as tgl_akhir')->first();

        if (!$tanggalRange || !$tanggalRange->tgl_awal) {
            return '-';
        }

        return $tanggalRange->tgl_awal === $tanggalRange->tgl_akhir
            ? $tanggalRange->tgl_awal
            : $tanggalRange->tgl_awal . ' s.d. ' . $tanggalRange->tgl_akhir;
    }
}
