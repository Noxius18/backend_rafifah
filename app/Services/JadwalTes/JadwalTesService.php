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
            'columns' => $this->buildTableColumns(),
            'rows' => $this->buildTableRows($jadwals),
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
        $pengujiList = $this->extractPengujiList($validated);

        $count = 0;
        $newJadwal = null;

        foreach ($verifiedMahasantri as $mhs) {
            $newJadwal = JadwalTes::create([
                'kode_jadwal' => $this->generateKodeJadwal($gelombang),
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
                    'jadwal_id' => $newJadwal->id,
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
     * Menggeser satu kelompok jadwal pada tanggal tertentu ke tanggal/jam baru.
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

        $jadwalIds = $jadwals->pluck('id');
        JadwalPenguji::whereIn('jadwal_id', $jadwalIds)->delete();

        foreach ($jadwalIds as $idJadwal) {
            foreach ($this->aspectFieldMap() as $field => $aspek) {
                if (!empty($validated[$field])) {
                    JadwalPenguji::create([
                        'jadwal_id' => $idJadwal,
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
     * Edit ringan pada satu jadwal tetap mengembalikan status ke Menunggu.
     */
    public function updateSingleJadwal(JadwalTes $jadwalTes, array $validated, string $creatorId): void
    {
        if (Carbon::parse($jadwalTes->tanggal)->startOfDay()->lt(Carbon::today())) {
            throw new \RuntimeException('Jadwal yang sudah lewat tidak bisa diedit.');
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

        // === HAPUS / KOMENTAR BAGIAN INI ===
        // if ($jadwalTes->hasilTes && $jadwalTes->hasilTes->status === 'Pertimbangan') {
        //     if ($jadwalTes->mahasantri && $jadwalTes->mahasantri->email) {
        //         Mail::to($jadwalTes->mahasantri->email)->send(new \App\Mail\ZoomLinkReminder($jadwalTes, $jadwalTes->mahasantri, true));
        //     }
        // }
        // ===================================
    }
        

   public function updateLinkZoomMassal(string $tanggal, string $linkZoom): int
    {
        $updated = JadwalTes::where('tanggal', $tanggal)->update(['link_zoom' => $linkZoom]);
        
        if ($updated > 0) {
            // KIRIM NOTIFIKASI ZOOM TERUPDATE BRAY!
            \App\Helpers\FcmHelper::sendToTopic(
                'mahasantri', 
                'Ruang Ujian Zoom Diperbarui!', 
                'Panitia baru saja memperbarui link ruang Zoom ujian untuk tanggal ' . \Carbon\Carbon::parse($tanggal)->format('d/m/Y') . '. Silakan cek dashboard anda!'
            );
        }

        return $updated;
    }

    public function sendUpdateNotification(JadwalTes $jadwalTes): void
    {
        ScheduleStatus::updateOrCreate(
            ['jadwal_id' => $jadwalTes->id],
            ['zoom_reminder_sent' => false, 'sent_at' => null]
        );

        Mail::to($jadwalTes->mahasantri->email)->send(new ZoomLinkReminder($jadwalTes, $jadwalTes->mahasantri, true));
    }

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

   public function sendBulkResults(Carbon $waktuKirim): array
    {
        $today = Carbon::today();
        $activeGelombang = Gelombang::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first() ?? Gelombang::orderBy('end_date', 'desc')->first();

        if (!$activeGelombang) {
            throw new \RuntimeException('Gagal: Belum ada konfigurasi gelombang di sistem.');
        }

        // FIX EAGER LOADING: Muat sekalian relasi penguji agar layout PDF tidak kosong bray
        $scheduled = JadwalTes::with(['mahasantri', 'hasilTes', 'jadwalPenguji.panitia'])
            ->where('status_jadwal', 'Disetujui')
            ->whereBetween('tanggal', [$activeGelombang->start_date, $activeGelombang->end_date])
            ->get();

        if ($scheduled->isEmpty()) {
            throw new \RuntimeException("Tidak ada jadwal seleksi pada {$activeGelombang->nama}.");
        }

        $sentCount = 0;
        
        // Panggil service pendukung untuk data nomor surat kelulusan bray
        $workflowService = app(\App\Services\Mahasantri\MahasantriWorkflowService::class);

       foreach ($scheduled as $jadwal) {
            $mahasantri = $jadwal->mahasantri;
            $hasil = $jadwal->hasilTes;

            if (!$mahasantri || !$hasil || !in_array($mahasantri->status, ['Lulus', 'Tidak Lulus'])) {
                continue;
            }

            // 1. Antrekan Pengiriman Email Kelulusan
            Mail::to($mahasantri->email)->later($waktuKirim, new TestResultPdf($mahasantri, $hasil));
            
            // 2. FIX LOGIKA: Generate PDF secara dinamis untuk Lulus maupun Tidak Lulus bray!
            $prefix = $mahasantri->status === 'Lulus' ? 'surat_lulus_' : 'surat_tidak_lulus_';
            $fileName = $prefix . $mahasantri->id_mahasantri . '.pdf';
            $path = 'private/surat_kelulusan/' . $fileName;

            if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
                $hasilTesCollection = collect([$hasil]);
                $suratMeta = $workflowService->buildSuratKelulusanMeta($mahasantri);

                $html = view('menu.laporan.pdf-nilai-single', [
                    'mahasantri' => $mahasantri,
                    'hasilTes' => $hasilTesCollection,
                    'jadwal' => $jadwal,
                    'date' => now()->format('d/m/Y H:i'),
                    'nomorSurat' => $suratMeta['nomor_surat'],
                    'tahunAjaran' => $suratMeta['tahun_ajaran'],
                    'tahunAjaranBerikutnya' => $suratMeta['tahun_ajaran_berikutnya'],
                    'labelTahunAjaran' => $suratMeta['label_tahun_ajaran'],
                ])->render();

                $options = new \Dompdf\Options();
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isRemoteEnabled', false);

                $dompdf = new \Dompdf\Dompdf($options);
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();

                // Simpan ke disk local private storage
                \Illuminate\Support\Facades\Storage::disk('local')->put($path, $dompdf->output());
            }

            $sentCount++;
        }


        if ($sentCount > 0) {
                // KIRIM NOTIFIKASI SURAT KEPUTUSAN TERBIT BRAY!
                \App\Helpers\FcmHelper::sendToTopic(
                    'mahasantri', 
                    'Pengumuman Hasil Seleksi Terbit!', 
                    'Surat Keputusan Kelulusan resmi Ma\'had Rafifah Andalusia MQ gelombang ini sudah diterbitkan. Buka aplikasi untuk melihat hasil perjuanganmu bray, Ahay!'
                );
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

    private function buildNilaiPerAspekByJadwal($jadwals): array
    {
        $data = [];
        foreach ($jadwals as $jadwal) {
            foreach ($jadwal->jadwalPenguji as $jp) {
                $data[$jadwal->kode_jadwal][$jp->aspek_penguji] = [
                    'nilai' => $jp->nilai,
                    'catatan' => $jp->catatan_penguji,
                    'penguji' => $jp->panitia?->nama_lengkap,
                    'id_panitia' => $jp->id_panitia,
                ];
            }
        }
        return $data;
    }

    private function buildTableColumns(): array
    {
        return [
            ['label' => 'ID', 'field' => 'kode_jadwal', 'html' => 'id_html'],
            ['label' => 'Mahasantri', 'field' => 'mhs_nama', 'html' => 'mhs_html'],
            ['label' => 'Gelombang', 'field' => 'gelombang', 'html' => 'gelombang_html'],
            ['label' => 'Tanggal', 'field' => 'tanggal', 'html' => 'tgl_html'],
            ['label' => 'Jam', 'field' => 'jam', 'html' => 'jam_html'],
            ['label' => 'Status', 'field' => 'status_jadwal', 'html' => 'status_konfirmasi_html'],
            ['label' => 'Hasil', 'field' => 'hasil', 'html' => 'hasil_html'],
            ['label' => 'Link Zoom', 'field' => 'link_zoom', 'html' => 'link_html', 'class' => 'hidden lg:table-cell'],
            ['label' => 'Aksi', 'field' => 'kode_jadwal', 'html' => 'aksi_html', 'class' => 'text-right'],
        ];
    }

    private function buildTableRows($jadwals): array
    {
        $isPanitia = auth()->user()->jabatan === 'Panitia';
        $nilaiPerAspekByJadwal = $this->buildNilaiPerAspekByJadwal($jadwals);

        return $jadwals->map(function ($jadwal) use ($isPanitia, $nilaiPerAspekByJadwal) {
            $hasil = $jadwal->hasilTes;
            $statusHasil = $hasil ? $hasil->status : 'Belum Tes';

            $editPayload = htmlspecialchars(json_encode([
                'id' => $jadwal->kode_jadwal,
                'jam' => $jadwal->jam ? Carbon::parse($jadwal->jam)->format('H:i') : '',
                'link_zoom' => $jadwal->link_zoom ?? '',
                'tanggal' => $jadwal->tanggal,
                'status_jadwal' => $jadwal->status_jadwal,
            ]), ENT_QUOTES, 'UTF-8');

            $aksiHtml = "<div class='flex items-center justify-end gap-0.5'>";
            $aksiHtml .= "<a href='" . route('seleksi.nilai', $jadwal) . "' class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-indigo-600 hover:bg-indigo-50' title='Detail Nilai'>
                            <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z'/></svg>
                          </a>";

            if ($isPanitia && !in_array($jadwal->status_jadwal, ['Dibatalkan', 'Rescheduled', 'Revisi'], true)) {
                $aksiHtml .= "<button type='button' onclick='window.menuJadwalTesIndex?.openEditModalFromRow({$editPayload})' class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-indigo-600 hover:bg-indigo-50' title='Edit'>
                                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10'/></svg>
                              </button>";
            }
            $aksiHtml .= '</div>';

            return [
                'id_jadwal' => $jadwal->kode_jadwal,
                'kode_jadwal' => $jadwal->kode_jadwal,
                'gelombang' => $jadwal->mahasantri ? User::extractGelombangNama($jadwal->mahasantri->id_mahasantri) : '-',
                'status_jadwal' => $jadwal->status_jadwal ?? 'Menunggu',
                'search' => strtolower("{$jadwal->kode_jadwal} {$jadwal->mahasantri?->nama_lengkap} {$jadwal->tanggal} {$jadwal->status_jadwal}"),
                'id_html' => "<code class='rounded bg-black/[0.05] px-1.5 py-0.5 text-xs text-black'>{$jadwal->kode_jadwal}</code>",
                'mhs_html' => $jadwal->mahasantri
                    ? "<span class='font-medium text-black'>" . e($jadwal->mahasantri->nama_lengkap) . "</span><br><span class='text-[10px] text-black/60'>" . e($jadwal->mahasantri->id_mahasantri) . '</span>'
                    : "<span class='text-black/50 text-xs'>-</span>",
                'gelombang_html' => $jadwal->mahasantri
                    ? "<span class='rounded-md bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 ring-1 ring-purple-200'>" . e(User::extractGelombangNama($jadwal->mahasantri->id_mahasantri)) . '</span>'
                    : "<span class='text-slate-400'>-</span>",
                'tgl_html' => "<span class='text-xs text-black'>" . Carbon::parse($jadwal->tanggal)->format('d/m/Y') . '</span>',
                'jam_html' => $jadwal->jam
                    ? "<span class='rounded-md bg-slate-50 px-2 py-0.5 text-xs font-mono font-medium text-slate-600 ring-1 ring-slate-200'>" . Carbon::parse($jadwal->jam)->format('H:i') . '</span>'
                    : "<span class='text-slate-400 text-xs'>-</span>",
                'status_konfirmasi_html' => $this->jadwalStatusBadge($jadwal->status_jadwal ?? 'Menunggu'),
                'hasil_html' => $this->hasilStatusBadge($statusHasil),
                'link_html' => $jadwal->link_zoom
                ? "<a href='" . e($this->formatLinkZoom($jadwal->link_zoom)) . "' target='_blank' onclick='event.stopPropagation();' class='inline-flex items-center justify-center rounded-md p-2 text-black transition hover:text-blue-600 hover:bg-blue-50' title='Buka Zoom'><svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor' stroke-width='2'><path stroke-linecap='round' stroke-linejoin='round' d='M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9A2.25 2.25 0 0013.5 5.25h-9A2.25 2.25 0 002.25 7.5v9A2.25 2.25 0 004.5 18.75z'/></svg></a>"
                : "<span class='text-black/50 text-xs'>-</span>",
                'aksi_html' => $aksiHtml,
            ];
        })->toArray();
    }

    private function generateKodeJadwal(Gelombang $gelombang): string
    {
        $prefix = 'J' . $gelombang->tahunPrefix() . str_pad((string) $gelombang->id, 2, '0', STR_PAD_LEFT);

        $last = JadwalTes::where('kode_jadwal', 'like', $prefix . '%')
            ->orderByDesc('kode_jadwal')
            ->first();

        $urut = $last ? ((int) substr($last->kode_jadwal, -2)) + 1 : 1;

        return $prefix . str_pad((string) $urut, 2, '0', STR_PAD_LEFT);
    }

    private function formatLinkZoom(?string $link): ?string
    {
        if (!$link) {
            return null;
        }
        return preg_match('#^https?://#i', $link) ? $link : 'https://' . $link;
    }

    private function jadwalStatusBadge(string $status): string
    {
        return match ($status) {
            'Menunggu' => "<span class='rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200'>⏳ Menunggu</span>",
            'Disetujui' => "<span class='rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200'>✅ Disetujui</span>",
            'Revisi' => "<span class='rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200'>❌ Perlu Revisi</span>",
            'Dibatalkan' => "<span class='rounded-md bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-gray-200'>🚫 Dibatalkan</span>",
            'Rescheduled' => "<span class='rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 ring-1 ring-blue-200'>🔄 Dijadwalkan Ulang</span>",
            default => "<span class='text-xs text-slate-500'>" . e($status) . '</span>',
        };
    }

    private function hasilStatusBadge(string $status): string
    {
        return match ($status) {
            'Lulus' => "<span class='rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200'>✅ Lulus</span>",
            'Tidak Lulus' => "<span class='rounded-md bg-rose-50 px-2 py-0.5 text-xs font-medium text-rose-700 ring-1 ring-rose-200'>❌ Tidak Lulus</span>",
            'Pertimbangan' => "<span class='rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-200'>⚠️ Pertimbangan</span>",
            default => "<span class='rounded-md bg-slate-50 px-2 py-0.5 text-xs font-medium text-slate-400 ring-1 ring-slate-200'>⏳ Belum Tes</span>",
        };
    }

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

    private function notifyKetuaJadwalCreated(?JadwalTes $jadwal, string $creatorId, int $count): void
    {
        $ketuaPanitia = Panitia::where('jabatan', 'Ketua Panitia')->first();
        if (!$ketuaPanitia || !$ketuaPanitia->email) {
            return;
        }

        // FIX BUG: Tarik data panitia pengirim/pembuat dari database menggunakan $creatorId agar terdefinisi sempurna
        $pembuat = Panitia::find($creatorId);
        if (!$pembuat) {
            return;
        }

        foreach (Panitia::where('jabatan', 'Ketua Panitia')->get() as $ketua) {
            if ($ketua->email) {
                Mail::to($ketua->email)->send(new JadwalCreatedNotification(
                    $jadwal,
                    $pembuat,
                    $count,
                    $ketua->nama_lengkap
                ));
            }
        }
    }

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
