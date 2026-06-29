<?php

namespace App\Http\Controllers;

use App\Models\JadwalTes;
use App\Models\JadwalPenguji;
use App\Models\Panitia;
use App\Models\ScheduleStatus;
use App\Models\User;
use App\Models\Gelombang;
use App\Models\HasilTes;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestResultPdf;
use App\Mail\ZoomLinkReminder;
use App\Mail\JadwalCreatedNotification;
use App\Mail\JadwalApprovedNotification;
use App\Mail\JadwalRejectedNotification;

class JadwalTesController extends Controller
{
    /**
     * Display a listing of all jadwal tes (seleksi & penilaian)
     */
    public function index()
    {
        $jadwals = JadwalTes::with(['penanggungJawab', 'mahasantri', 'jadwalPenguji.panitia', 'hasilTes'])->paginate(10);
        $panitias = Panitia::where('jabatan', 'Panitia')->get();

        $gelombangs = Gelombang::orderBy('start_date')->get(['id', 'nama', 'start_date', 'end_date']);

        $today = Carbon::today();
        $activeGelombang = Gelombang::activeOrFirst($today);

        // Build nilaiPerAspek for each jadwal for the modal
        $nilaiPerAspekByJadwal = [];
        foreach ($jadwals as $j) {
            $nilaiPerAspek = [];
            foreach ($j->jadwalPenguji as $jp) {
                $nilaiPerAspek[$jp->aspek_penguji] = [
                    'nilai'    => $jp->nilai,
                    'catatan'  => $jp->catatan_penguji,
                    'penguji'  => $jp->panitia?->nama_lengkap,
                    'id_panitia' => $jp->id_panitia,
                ];
            }
            $nilaiPerAspekByJadwal[$j->id_jadwal] = $nilaiPerAspek;
        }

        // Hitung total jadwal yang menunggu persetujuan Ketua Panitia
        $totalMenunggu = $jadwals->filter(fn($j) => in_array($j->status_jadwal, ['Menunggu', 'Revisi']))->count();
        // Hitung total hasil yang perlu review Ketua Panitia (status Pertimbangan)
        $totalPerluReview = $jadwals->filter(fn($j) => $j->hasilTes && $j->hasilTes->status === 'Pertimbangan')->count();

        // Group jadwal by tanggal untuk modal review Ketua Panitia — hanya yang Menunggu/Revisi
        $jadwalsMenunggu = $jadwals->filter(fn($j) => in_array($j->status_jadwal, ['Menunggu', 'Revisi']));
        $jadwalsByTanggal = [];
        foreach ($jadwalsMenunggu as $j) {
            $tgl = $j->tanggal;
            if (!isset($jadwalsByTanggal[$tgl])) {
                $jadwalsByTanggal[$tgl] = [
                    'tanggal' => $tgl,
                    'total' => 0,
                    'penguji' => [],  // mapping aspek => nama_panitia
                    'penanggung_jawab' => null,
                ];
            }
            $jadwalsByTanggal[$tgl]['total']++;
            // Ambil data penguji dari jadwal pertama di tanggal ini (sama semua per tanggal)
            if (empty($jadwalsByTanggal[$tgl]['penguji'])) {
                foreach ($j->jadwalPenguji as $jp) {
                    $jadwalsByTanggal[$tgl]['penguji'][$jp->aspek_penguji] = $jp->panitia?->nama_lengkap ?? '-';
                }
                $jadwalsByTanggal[$tgl]['penanggung_jawab'] = $j->penanggungJawab?->nama_lengkap ?? '-';
            }
        }
        $jadwalsByTanggal = array_values($jadwalsByTanggal); // reset keys

        // Hitung total jadwal Revisi untuk tombol "Edit Jadwal Revisi" (Panitia)
        $totalRevisi = $jadwals->filter(fn($j) => $j->status_jadwal === 'Revisi')->count();
        // Group by tanggal untuk modal edit revisi — data yang di-pass sebagai JSON
        $jadwalsRevisi = $jadwals->filter(fn($j) => $j->status_jadwal === 'Revisi');
        $jadwalsRevisiByTanggal = [];
        foreach ($jadwalsRevisi as $j) {
            $tgl = $j->tanggal;
            if (!isset($jadwalsRevisiByTanggal[$tgl])) {
                // Ambil jam_mulai = min(jam) dari jadwal di tanggal ini
                $minJam = $jadwalsRevisi->where('tanggal', $tgl)->min('jam');
                // Ambil data penguji dari jadwal pertama
                $pengujiKeys = [
                    'Bacaan Al-Quran' => 'penguji_bacaan_al_quran',
                    'Tajwid/Tahsin'   => 'penguji_tajwid_tahsin',
                    'Hafalan'         => 'penguji_hafalan',
                    'Wawancara'       => 'penguji_wawancara',
                ];
                $penguji = [];
                foreach ($j->jadwalPenguji as $jp) {
                    $key = $pengujiKeys[$jp->aspek_penguji] ?? null;
                    if ($key) {
                        $penguji[$key] = [
                            'id_panitia' => $jp->id_panitia,
                            'nama' => $jp->panitia?->nama_lengkap ?? '-',
                        ];
                    }
                }
                $jadwalsRevisiByTanggal[$tgl] = [
                    'tanggal' => $tgl,
                    'total' => 0,
                    'jam_mulai' => $minJam ? Carbon::parse($minJam)->format('H:i') : '',
                    'interval' => $j->interval ?? 30,
                    'link_zoom' => $j->link_zoom ?? '',
                    'catatan_perubahan' => $j->catatan_perubahan ?? '',
                    'penanggung_jawab' => $j->penanggungJawab?->nama_lengkap ?? '-',
                    'penguji' => $penguji,
                ];
            }
            $jadwalsRevisiByTanggal[$tgl]['total']++;
        }
        $jadwalsRevisiByTanggal = array_values($jadwalsRevisiByTanggal); // reset keys

        $aspekMapping = [
            'Bacaan Al-Quran' => 'bacaan_al_quran',
            'Tajwid/Tahsin'   => 'tajwid_tahsin',
            'Hafalan'         => 'hafalan',
            'Wawancara'       => 'wawancara',
        ];

        return view('menu.jadwal-tes.index', [
            'jadwals' => $jadwals,
            'panitias' => $panitias,
            'gelombangs' => $gelombangs,
            'activeGelombang' => $activeGelombang,
            'nilaiPerAspekByJadwal' => $nilaiPerAspekByJadwal,
            'aspekMapping' => $aspekMapping,
            'totalMenunggu' => $totalMenunggu,
            'totalPerluReview' => $totalPerluReview,
            'totalRevisi' => $totalRevisi,
            'jadwalsByTanggal' => $jadwalsByTanggal,
            'jadwalsRevisiByTanggal' => $jadwalsRevisiByTanggal,
        ]);
    }

    /**
     * Store a newly created jadwal tes
     */
    public function store(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa menambah jadwal tes');
        }

        $validated = $request->validate([
            'tanggal'          => 'required|date',
            'jam_mulai'        => 'required|date_format:H:i',
            'interval'         => 'required|integer|min:5|max:120',
            'link_zoom'        => 'nullable|string',
            'penguji_bacaan_al_quran' => 'nullable|exists:panitia,id_panitia',
            'penguji_tajwid_tahsin'   => 'nullable|exists:panitia,id_panitia',
            'penguji_hafalan'         => 'nullable|exists:panitia,id_panitia',
            'penguji_wawancara'       => 'nullable|exists:panitia,id_panitia',
        ]);

        $tanggal = Carbon::parse($validated['tanggal']);
        $gelombang = Gelombang::findByDate($tanggal);

        if (!$gelombang) {
            return redirect()->route('seleksi.index')
                ->with('error', 'Tanggal ' . $validated['tanggal'] . ' tidak masuk rentang gelombang manapun.');
        }

        $prefixId = $gelombang->tahunPrefix() . str_pad($gelombang->id, 2, '0', STR_PAD_LEFT);
        
        // Eager load relasi jadwalTes untuk memvalidasi double scheduling
        $allMahasantri = User::with('jadwalTes')->where('id_mahasantri', 'LIKE', $prefixId . '%')->get();
        
        // Saring mahasantri yang berstatus Terverifikasi murni
        $verifiedMahasantriTotal = $allMahasantri->where('status', 'Terverifikasi');
        
        // FIX DOUBLE SCHEDULING: Hanya pilih yang berstatus Terverifikasi DAN belum punya jadwal tes samsek
        $verifiedMahasantri = $verifiedMahasantriTotal->filter(fn($m) => $m->jadwalTes->isEmpty());
        
        // FIX BUG LOGIKA PERINGATAN: Yang masuk status belum terverifikasi HANYA yang berstatus 'Pendaftar Baru' (Lulus/Tidak Lulus dibuang)
        $unverifiedMahasantri = $allMahasantri->filter(fn($m) => $m->status === 'Pendaftar Baru');

        // FIX ERROR MESSAGE: Bedakan error message kalau memang belum ada yang diverifikasi atau semuanya sudah dijadwalkan
        if ($verifiedMahasantriTotal->isEmpty()) {
            return redirect()->route('seleksi.index')->with('error', 'Tidak ada mahasantri dengan status Terverifikasi di ' . $gelombang->nama . '. Silakan verifikasi berkas pendaftar terlebih dahulu.');
        }

        if ($verifiedMahasantri->isEmpty()) {
            return redirect()->route('seleksi.index')->with('error', 'Semua mahasantri terverifikasi di ' . $gelombang->nama . ' sudah memiliki jadwal seleksi.');
        }

        $jamMulai = Carbon::createFromFormat('H:i', $validated['jam_mulai']);
        $interval = (int) $validated['interval'];
        $count = 0;

        $lastNumber = JadwalTes::query()
            ->pluck('id_jadwal')
            ->map(fn($id) => (int) preg_replace('/^\D+/', '', $id))
            ->max();
        $urut = ($lastNumber ?? 0) + 1;

        // Mapping aspek penguji dari input form ke value di DB
        $aspekMapping = [
            'penguji_bacaan_al_quran' => 'Bacaan Al-Quran',
            'penguji_tajwid_tahsin'   => 'Tajwid/Tahsin',
            'penguji_hafalan'         => 'Hafalan',
            'penguji_wawancara'       => 'Wawancara',
        ];

        // Kumpulkan panitia penguji dari input (hanya yang diisi)
        $pengujiList = [];
        foreach ($aspekMapping as $field => $aspek) {
            if (!empty($validated[$field])) {
                $pengujiList[] = [
                    'id_panitia' => $validated[$field],
                    'aspek'      => $aspek,
                ];
            }
        }

        // Buat jadwal per mahasantri
        foreach ($verifiedMahasantri as $mhs) {
            $idJadwal = 'JDS' . str_pad($urut, 2, '0', STR_PAD_LEFT);
            $urut++;

            $newJadwal = JadwalTes::create([
                'id_jadwal'         => $idJadwal,
                'id_mahasantri'     => $mhs->id_mahasantri,
                'tanggal'           => $validated['tanggal'],
                'jam'               => $jamMulai->format('H:i'),
                'interval'          => $interval,
                'link_zoom'         => $validated['link_zoom'] ?? null,
                'penanggung_jawab'  => auth()->user()->id_panitia,
                'status_jadwal'     => 'Menunggu',
            ]);

            // Buat rows jadwal_penguji untuk setiap penguji yang ditugaskan
            foreach ($pengujiList as $penguji) {
                JadwalPenguji::create([
                    'id_jadwal'     => $idJadwal,
                    'id_panitia'    => $penguji['id_panitia'],
                    'aspek_penguji' => $penguji['aspek'],
                ]);
            }

            $jamMulai->addMinutes($interval);
            $count++;
        }

        // Kirim notifikasi ke Ketua Panitia tentang jadwal baru yang perlu approval
        $ketuaPanitia = Panitia::where('jabatan', 'Ketua Panitia')->first();
        if ($ketuaPanitia && $ketuaPanitia->email) {
            $pembuat = Panitia::findOrFail(auth()->user()->id_panitia);
            
            // Kirim ke semua ketua panitia
            foreach (Panitia::where('jabatan', 'Ketua Panitia')->get() as $kp) {
                Mail::to($kp->email)->send(new JadwalCreatedNotification(
                    $newJadwal ?? null, $pembuat, $count, $kp->nama_lengkap
                ));
            }
        }

        // Prepare unscheduled mahasantri data for modal (only id and name)
        $unscheduledData = $unverifiedMahasantri->map(fn($m) => ['id_mahasantri' => $m->id_mahasantri, 'nama_lengkap' => $m->nama_lengkap])->values()->toArray();

        return redirect()->route('seleksi.index')
            ->with('success', "Berhasil membuat {$count} jadwal untuk {$gelombang->nama}.")
            ->with('unscheduledMahasantri', $unscheduledData);
    }

    public function updateLinkZoomMassal(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403);
        $request->validate(['tanggal' => 'required|date', 'link_zoom' => 'required|string']);
        $updated = JadwalTes::where('tanggal', $request->tanggal)->update(['link_zoom' => $request->link_zoom]);
        return redirect()->route('seleksi.index')->with('success', "Link Zoom berhasil diperbarui untuk {$updated} jadwal.");
    }

    /**
     * Panitia: Update semua jadwal di tanggal tertentu (jam_mulai, interval, link_zoom, penguji)
     * Digunakan saat status Revisi — setelah Ketua Panitia mengajukan perubahan.
     */
    public function updateByDate(Request $request, string $tanggal)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403);

        $validated = $request->validate([
            'tanggal_baru'                => 'required|date',
            'jam_mulai'                   => 'required|date_format:H:i',
            'interval'                    => 'required|integer|min:5|max:120',
            'link_zoom'                   => 'nullable|string',
            'penguji_bacaan_al_quran'     => 'nullable|exists:panitia,id_panitia',
            'penguji_tajwid_tahsin'       => 'nullable|exists:panitia,id_panitia',
            'penguji_hafalan'             => 'nullable|exists:panitia,id_panitia',
            'penguji_wawancara'           => 'nullable|exists:panitia,id_panitia',
        ]);

        $tanggalBaruCarbon = Carbon::parse($validated['tanggal_baru']);
        $gelombang = Gelombang::where('start_date', '<=', $tanggalBaruCarbon->copy()->endOfDay())
            ->where('end_date', '>=', $tanggalBaruCarbon->copy()->startOfDay())
            ->first();

        if (!$gelombang) {
            return redirect()->route('seleksi.index')
                ->with('error', 'Tanggal baru ' . $validated['tanggal_baru'] . ' tidak masuk rentang gelombang manapun.');
        }

        // Ambil semua jadwal di tanggal + status Menunggu/Revisi
        $jadwals = JadwalTes::where('tanggal', $tanggal)
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->orderBy('jam')
            ->get();

        if ($jadwals->isEmpty()) {
            return redirect()->route('seleksi.index')->with('error', "Tidak ada jadwal dengan status Revisi/Menunggu di tanggal {$tanggal}.");
        }

        // Recalculate jam untuk setiap jadwal
        $jamMulai = Carbon::createFromFormat('H:i', $validated['jam_mulai']);
        foreach ($jadwals as $j) {
            $j->update([
                'tanggal'           => $validated['tanggal_baru'],
                'jam'               => $jamMulai->format('H:i'),
                'interval'          => $validated['interval'],
                'link_zoom'         => $validated['link_zoom'],
                'status_jadwal'     => 'Menunggu',
                'catatan_perubahan' => null,
            ]);
            $jamMulai->addMinutes((int) $validated['interval']);
        }

        // Hapus & buat ulang jadwal_penguji untuk semua jadwal di tanggal ini
        $jadwalIds = $jadwals->pluck('id_jadwal');
        JadwalPenguji::whereIn('id_jadwal', $jadwalIds)->delete();

        $aspekMapping = [
            'penguji_bacaan_al_quran' => 'Bacaan Al-Quran',
            'penguji_tajwid_tahsin'   => 'Tajwid/Tahsin',
            'penguji_hafalan'         => 'Hafalan',
            'penguji_wawancara'       => 'Wawancara',
        ];

        foreach ($jadwalIds as $idJadwal) {
            foreach ($aspekMapping as $field => $aspek) {
                if (!empty($validated[$field])) {
                    JadwalPenguji::create([
                        'id_jadwal'     => $idJadwal,
                        'id_panitia'    => $validated[$field],
                        'aspek_penguji' => $aspek,
                    ]);
                }
            }
        }

        // Notifikasi ke Ketua Panitia bahwa jadwal sudah diperbaiki
       $ketua = Panitia::where('jabatan', 'Ketua Panitia')->first();
        if ($ketua && $ketua->email) {
            $pembuat = Panitia::findOrFail(auth()->user()->id_panitia);
            
            // Tambahkan true di parameter ke-5 sebagai penanda REVISI
            Mail::to($ketua->email)->send(new JadwalCreatedNotification(
                $jadwals->first(), $pembuat, $jadwals->count(), $ketua->nama_lengkap, true
            ));
        }

        return redirect()->route('seleksi.index')
            ->with('success', $jadwals->count() . ' jadwal berhasil diperbarui ke tanggal ' . Carbon::parse($validated['tanggal_baru'])->format('d/m/Y') . '.');
    }

    public function edit(JadwalTes $jadwalTes)
    {
        // Guard: cegah edit jika sudah disetujui
        if ($jadwalTes->status_jadwal === 'Disetujui') {
            return redirect()->route('seleksi.index')->with('error', 'Jadwal sudah disetujui, tidak bisa diedit');
        }

        $jadwalTes->load(['penanggungJawab', 'mahasantri', 'jadwalPenguji.panitia']);
        return view('menu.jadwal-tes.edit', ['jadwalTes' => $jadwalTes, 'gelombangs' => Gelombang::orderBy('start_date')->get()]);
    }

    public function update(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403);

        // Auto-lock: cek jika jadwal sudah lewat
        $jadwalDate = Carbon::parse($jadwalTes->tanggal);
        if ($jadwalDate->lt(Carbon::today())) {
            return redirect()->route('seleksi.index')->with('error', 'Jadwal tidak dapat diubah karena sudah berlangsung');
        }

        // Hanya terima jam dan link_zoom untuk edit
        $validated = $request->validate([
            'jam'       => 'nullable|date_format:H:i',
            'link_zoom' => 'nullable|string',
        ]);

        // Reset status ke 'Menunggu' + hapus catatan agar Ketua bisa review ulang
        $jadwalTes->update(array_merge($validated, [
            'status_jadwal' => 'Menunggu',
            'catatan_ketua' => null,
            'catatan_perubahan' => null,
        ]));

        // Kirim Notifikasi ke Ketua Panitia
      $ketuaPanitias = Panitia::where('jabatan', 'Ketua Panitia')->get();
        $pembuat = Panitia::findOrFail(auth()->user()->id_panitia);
        
        foreach ($ketuaPanitias as $kp) {
            if ($kp->email) {
                // Tambahkan true di parameter ke-5 sebagai penanda REVISI / EDITAN
                Mail::to($kp->email)->send(new JadwalCreatedNotification(
                    $jadwalTes, $pembuat, 1, $kp->nama_lengkap, true
                ));
            }
        }

        return redirect()->route('seleksi.index')->with('success', 'Jadwal tes berhasil diperbarui');
    }

    /**
     * Ketua Panitia: Approve semua jadwal di tanggal yang sama (digital handshake)
     */
    public function approve(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Ketua Panitia') abort(403, 'Hanya Ketua Panitia yang bisa menyetujui jadwal');

        // Approve semua jadwal di tanggal yang sama
        JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->update([
                'status_jadwal' => 'Disetujui',
                'diproses_oleh' => auth()->user()->id_panitia,
            ]);

        $jumlah = JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->where('status_jadwal', 'Disetujui')
            ->count();

        // Ambil jadwal yang baru disetujui
        $approvedJadwals = JadwalTes::with('mahasantri')
            ->where('tanggal', $jadwalTes->tanggal)
            ->where('status_jadwal', 'Disetujui')
            ->get();

        // FIX NOTIF MAHASANTRI: Kirim langsung menggunakan ->send() tanpa delay H-3
        foreach ($approvedJadwals as $aj) {
            $mhs = $aj->mahasantri;
            if ($aj->link_zoom && $mhs && $mhs->email) {
                Mail::to($mhs->email)->send(new ZoomLinkReminder($aj, $mhs));
            }
        }

        // FIX NOTIF PANITIA: Kirim eksklusif hanya ke email penanggung jawab pembuat jadwal
        $pembuat = Panitia::find($jadwalTes->penanggung_jawab);
        if ($pembuat && $pembuat->email) {
            $ketua = Panitia::findOrFail(auth()->user()->id_panitia);
            Mail::to($pembuat->email)->send(new JadwalApprovedNotification(
                $jadwalTes->tanggal, $ketua, $jumlah, $pembuat->nama_lengkap
            ));
        }

        return redirect()->route('seleksi.index')->with('success', 'Semua jadwal di tanggal ' . $jadwalTes->tanggal . ' berhasil disetujui');
    }

    /**
     * Ketua Panitia: Ajukan perubahan untuk semua jadwal di tanggal yang sama
     */
    public function reject(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Ketua Panitia') abort(403, 'Hanya Ketua Panitia yang bisa mengajukan perubahan');

        $request->validate([
            'catatan_perubahan' => 'required|string',
        ]);

        // Ambil data jadwal sebelum status diubah untuk melacak pembuatnya
        $updatedJadwals = JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->get();

        // Reject semua jadwal di tanggal yang sama
        JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->update([
                'status_jadwal' => 'Revisi',
                'catatan_perubahan' => $request->catatan_perubahan,
            ]);

        // FIX NOTIF REVISI: Hanya dikirim ke Penanggung Jawab murni pembuat jadwal tersebut
        $penanggungJawabs = $updatedJadwals->pluck('penanggung_jawab')->unique();
        foreach ($penanggungJawabs as $pjId) {
            $p = Panitia::find($pjId);
            if ($p && $p->email) {
                Mail::to($p->email)->send(new JadwalRejectedNotification(
                    $jadwalTes->tanggal, $request->catatan_perubahan, $p->nama_lengkap
                ));
            }
        }

        return redirect()->route('seleksi.index')->with('success', "{$updatedJadwals->count()} jadwal diajukan perubahan, menunggu Panitia merespon");
    }

    public function sendUpdateNotification(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403);

        // Guard: hanya kirim jika jadwal sudah disetujui
        if ($jadwalTes->status_jadwal !== 'Disetujui') {
            return redirect()->back()->with('error', 'Jadwal belum disetujui, notifikasi tidak dapat dikirim.');
        }

        $mahasantri = $jadwalTes->mahasantri;
        if (!$mahasantri || !$mahasantri->email) return redirect()->back()->with('error', 'Mahasiswa tidak memiliki email');

        ScheduleStatus::updateOrCreate(
            ['id_jadwal' => $jadwalTes->id_jadwal],
            ['zoom_reminder_sent' => false, 'sent_at' => null]
        );
        Mail::to($mahasantri->email)->send(new ZoomLinkReminder($jadwalTes, $mahasantri));
        return redirect()->back()->with('success', 'Notifikasi update terkirim ke ' . $mahasantri->nama_lengkap);
    }

    /**
     * Bulk cancel semua jadwal yang sudah Disetujui (Panitia)
     */
    public function bulkCancel(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403);

        $request->validate([
            'jenis_pembatalan' => 'required|in:Dibatalkan,Rescheduled',
            'alasan_pembatalan' => 'required|string',
        ]);

        // Ambil rentang tanggal SEBELUM update
        $tanggalRange = JadwalTes::where('status_jadwal', 'Disetujui')
            ->selectRaw('MIN(tanggal) as tgl_awal, MAX(tanggal) as tgl_akhir')
            ->first();
        $tanggalLabel = $tanggalRange && $tanggalRange->tgl_awal
            ? ($tanggalRange->tgl_awal === $tanggalRange->tgl_akhir
                ? $tanggalRange->tgl_awal
                : $tanggalRange->tgl_awal . ' s.d. ' . $tanggalRange->tgl_akhir)
            : '-';

        $updateData = [
            'status_jadwal' => $request->jenis_pembatalan,
            'diproses_oleh' => auth()->user()->id_panitia,
        ];
        if (in_array($request->jenis_pembatalan, ['Dibatalkan', 'Rescheduled'])) {
            $updateData['catatan_perubahan'] = $request->alasan_pembatalan;
        }

        $updated = JadwalTes::where('status_jadwal', 'Disetujui')->update($updateData);

        $ketua = Panitia::where('jabatan', 'Ketua Panitia')->first();
        if ($ketua && $ketua->email) {
            Mail::to($ketua->email)->send(new \App\Mail\JadwalCancelledNotification(
                $tanggalLabel,
                auth()->user()->nama_lengkap,
                $request->alasan_pembatalan,
                $request->jenis_pembatalan,
                $ketua->nama_lengkap
            ));
        }

        return redirect()->route('seleksi.index')->with('success', "{$updated} jadwal berhasil di" . strtolower($request->jenis_pembatalan));
    }

    /**
     * Ketua Panitia: Approve semua jadwal yang Menunggu
     */
    public function approveAll(Request $request)
    {
        if (auth()->user()->jabatan !== 'Ketua Panitia') abort(403);

        // Ambil rentang tanggal SEBELUM update
        $tanggalRange = JadwalTes::whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->selectRaw('MIN(tanggal) as tgl_awal, MAX(tanggal) as tgl_akhir')
            ->first();

        $tanggalLabel = $tanggalRange && $tanggalRange->tgl_awal
            ? ($tanggalRange->tgl_awal === $tanggalRange->tgl_akhir
                ? $tanggalRange->tgl_awal
                : $tanggalRange->tgl_awal . ' s.d. ' . $tanggalRange->tgl_akhir)
            : '-';

        // Ambil ID jadwal yang akan diupdate
        $updatedIds = JadwalTes::whereIn('status_jadwal', ['Menunggu', 'Revisi'])->pluck('id_jadwal');

        JadwalTes::whereIn('id_jadwal', $updatedIds)->update([
            'status_jadwal' => 'Disetujui',
            'diproses_oleh' => auth()->user()->id_panitia,
        ]);

        /**
         * @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\JadwalTes> $approvedJadwals
         */
        $approvedJadwals = JadwalTes::with('mahasantri')
            ->whereIn('id_jadwal', $updatedIds)
            ->get();

        $jamMulai = $approvedJadwals->min('jam');

        /** @var \App\Models\JadwalTes|null $firstJadwal */
        $firstJadwal = $approvedJadwals->first();

        if ($jamMulai && $firstJadwal) {
            /** @var \App\Models\JadwalTes $aj */
            foreach ($approvedJadwals as $aj) {
                /** @var \App\Models\User|null $mhs */
                $mhs = $aj->mahasantri;

                if ($aj->link_zoom && $mhs && $mhs->email) {
                    // FIX NOTIF MAHASANTRI MASSAL: Kirim langsung menggunakan ->send()
                    Mail::to($mhs->email)->send(new ZoomLinkReminder($aj, $mhs));
                }
            }
        }

        // FIX NOTIF PANITIA: Hanya kirim ke Penanggung Jawab masing-masing pembuat jadwal
        $penanggungJawabs = $approvedJadwals->pluck('penanggung_jawab')->unique();
        $ketuaPanitiaModel = Panitia::findOrFail(auth()->user()->id_panitia);

        foreach ($penanggungJawabs as $pjId) {
            $pembuat = Panitia::find($pjId);
            if ($pembuat && $pembuat->email) {
                $countJadwalPj = $approvedJadwals->where('penanggung_jawab', $pjId)->count();
                Mail::to($pembuat->email)->send(new JadwalApprovedNotification(
                    $tanggalLabel,
                    $ketuaPanitiaModel,
                    $countJadwalPj,
                    $pembuat->nama_lengkap
                ));
            }
        }

        return redirect()->route('seleksi.index')
            ->with('success', $updatedIds->count() . " jadwal berhasil disetujui");
    }

    /**
     * Ketua Panitia: Reject semua jadwal yang Menunggu
     */
    public function rejectAll(Request $request)
    {
        if (auth()->user()->jabatan !== 'Ketua Panitia') abort(403);

        $request->validate([
            'catatan_perubahan' => 'required|string',
        ]);

        // Ambil rentang tanggal SEBELUM update
        $tanggalRange = JadwalTes::whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->selectRaw('MIN(tanggal) as tgl_awal, MAX(tanggal) as tgl_akhir')
            ->first();
        $tanggalLabel = $tanggalRange && $tanggalRange->tgl_awal
            ? ($tanggalRange->tgl_awal === $tanggalRange->tgl_akhir
                ? $tanggalRange->tgl_awal
                : $tanggalRange->tgl_awal . ' s.d. ' . $tanggalRange->tgl_akhir)
            : '-';

        // Ambil data sebelum status berubah untuk melacak pembuatnya
        $updatedJadwals = JadwalTes::whereIn('status_jadwal', ['Menunggu', 'Revisi'])->get();

        JadwalTes::whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->update([
                'status_jadwal' => 'Revisi',
                'catatan_perubahan' => $request->catatan_perubahan,
            ]);

        // FIX NOTIF REVISI MASSAL: Hanya dikirim eksklusif ke Penanggung Jawab pembuat jadwal terkait
        $penanggungJawabs = $updatedJadwals->pluck('penanggung_jawab')->unique();
        foreach ($penanggungJawabs as $pjId) {
            $p = Panitia::find($pjId);
            if ($p && $p->email) {
                Mail::to($p->email)->send(new JadwalRejectedNotification(
                    $tanggalLabel, $request->catatan_perubahan, $p->nama_lengkap
                ));
            }
        }

        return redirect()->route('seleksi.index')->with('success', "{$updatedJadwals->count()} jadwal diajukan perubahan");
    }

    /**
     * Jadwalkan pengiriman email otomatis berdasarkan Gelombang Aktif
     */
    public function sendBulkResults(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403, 'Hanya panitia yang bisa kirim hasil test');

        $request->validate([
            'tanggal_kirim' => 'required|date',
            'jam_kirim'     => 'required',
        ]);

        $waktuKirim = Carbon::parse($request->tanggal_kirim . ' ' . $request->jam_kirim);

        $today = Carbon::today();
        $activeGelombang = Gelombang::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        if (!$activeGelombang) {
            $activeGelombang = Gelombang::orderBy('end_date', 'desc')->first();
        }

        if (!$activeGelombang) {
            return back()->with('error', 'Gagal: Belum ada konfigurasi gelombang di sistem.');
        }

        $scheduled = JadwalTes::with(['mahasantri', 'hasilTes'])
            ->where('status_jadwal', 'Disetujui')
            ->whereBetween('tanggal', [$activeGelombang->start_date, $activeGelombang->end_date])
            ->get();

        if ($scheduled->isEmpty()) {
            return back()->with('error', "Tidak ada jadwal seleksi pada {$activeGelombang->nama}.");
        }

        $sentCount = 0;

        foreach ($scheduled as $jadwal) {
            $mahasantri = $jadwal->mahasantri;
            
            $hasil = $jadwal->hasilTes;

            if (!$mahasantri || !$hasil || !in_array($mahasantri->status, ['Lulus', 'Tidak Lulus'])) {
                continue;
            }

            Mail::to($mahasantri->email)
                ->later($waktuKirim, new TestResultPdf($mahasantri, $hasil));
            
            $sentCount++;
        }

        if ($sentCount === 0) {
            return back()->with('error', "Gagal menjadwalkan. Pastikan nilai mahasantri pada {$activeGelombang->nama} sudah direview (Lulus/Tidak Lulus) semua.");
        }

        return back()->with('success', "Berhasil! {$sentCount} email kelulusan dijadwalkan untuk {$activeGelombang->nama} dan akan meluncur pada {$waktuKirim->format('d/m/Y H:i')}.");
    }
}
