<?php

namespace App\Http\Controllers;

use App\Models\JadwalTes;
use App\Models\Panitia;
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
        $jadwals = JadwalTes::with(['penanggungJawab', 'mahasantri', 'pengujiBacaanAlquran', 'pengujiTajwidTahsin', 'pengujiHafalan', 'pengujiWawancara'])->paginate(10);
        $panitias = Panitia::where('jabatan', 'Panitia')->get();

        $gelombangs = Gelombang::orderBy('start_date')->get(['id', 'nama', 'start_date', 'end_date']);

        $today = Carbon::today();
        $activeGelombang = Gelombang::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date')
            ->first();

        if (!$activeGelombang && $gelombangs->isNotEmpty()) {
            $activeGelombang = $gelombangs->first();
        }

        return view('menu.jadwal-tes.index', [
            'jadwals' => $jadwals,
            'panitias' => $panitias,
            'gelombangs' => $gelombangs,
            'activeGelombang' => $activeGelombang,
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
        $prefixTahun = date('y');
        $gelombang = Gelombang::whereDate('start_date', '<=', $tanggal)
            ->whereDate('end_date', '>=', $tanggal)
            ->orderBy('start_date')
            ->first();

        if (!$gelombang) {
            return redirect()->route('seleksi.index')
                ->with('error', 'Tanggal ' . $validated['tanggal'] . ' tidak masuk rentang gelombang manapun.');
        }

        $prefixId = $prefixTahun . str_pad($gelombang->id, 2, '0', STR_PAD_LEFT);
        $allMahasantri = User::where('id_mahasantri', 'LIKE', $prefixId . '%')->get();
        
        $verifiedMahasantri = $allMahasantri->filter(fn($m) => $m->status === 'Terverifikasi');
        $unverifiedMahasantri = $allMahasantri->reject(fn($m) => $m->status === 'Terverifikasi');

        if ($verifiedMahasantri->isEmpty()) {
            return redirect()->route('seleksi.index')->with('error', 'Tidak ada mahasantri terverifikasi di ' . $gelombang->nama . '.');
        }

        $jamMulai = \Carbon\Carbon::createFromFormat('H:i', $validated['jam_mulai']);
        $interval = (int) $validated['interval'];
        $count = 0;

        $last = JadwalTes::where('id_jadwal', 'LIKE', 'JDT%')->orderBy('id_jadwal', 'desc')->first();
        $urut = $last ? (int) substr($last->id_jadwal, 3) + 1 : 1;

        // Tentukan waktu kirim reminder: 3 hari sebelum hari ujian, jam mulai pertama
        $waktuKirim = Carbon::parse($validated['tanggal'] . ' ' . $validated['jam_mulai'])->subDays(3);

        // Buat jadwal per mahasantri dengan status_konfirmasi = 'Menunggu'
        foreach ($verifiedMahasantri as $mhs) {
            $idJadwal = 'JDT' . str_pad($urut, 2, '0', STR_PAD_LEFT);
            $urut++;

            $newJadwal = JadwalTes::create([
                'id_jadwal'         => $idJadwal,
                'id_mahasantri'     => $mhs->id_mahasantri,
                'tanggal'           => $validated['tanggal'],
                'jam'               => $jamMulai->format('H:i'),
                'interval_minutes'  => $interval,
                'link_zoom'         => $validated['link_zoom'] ?? null,
                'penanggung_jawab'  => auth()->user()->id_panitia,
                'penguji_bacaan_al_quran' => $validated['penguji_bacaan_al_quran'] ?? null,
                'penguji_tajwid_tahsin'   => $validated['penguji_tajwid_tahsin'] ?? null,
                'penguji_hafalan'         => $validated['penguji_hafalan'] ?? null,
                'penguji_wawancara'       => $validated['penguji_wawancara'] ?? null,
                'status_konfirmasi' => 'Menunggu',
            ]);

            // Kirim email reminder Zoom 3 hari sebelum ujian
            if ($newJadwal->link_zoom && $mhs->email) {
                Mail::to($mhs->email)->later($waktuKirim, new ZoomLinkReminder($newJadwal, $mhs));
            }

            $jamMulai->addMinutes($interval);
            $count++;
        }

        // Kirim notifikasi ke Ketua Panitia tentang jadwal baru yang perlu approval
        $ketuaPanitia = Panitia::where('jabatan', 'Ketua Panitia')->first();
        if ($ketuaPanitia && $ketuaPanitia->email) {
            $pembuat = auth()->user();
            // Kirim ke semua ketua panitia
            foreach (Panitia::where('jabatan', 'Ketua Panitia')->get() as $kp) {
                Mail::to($kp->email)->send(new JadwalCreatedNotification(
                    $newJadwal, $pembuat, $count, $kp->nama_lengkap
                ));
            }
        }

        // Prepare unscheduled mahasantri data for modal (only id and name)
        $unscheduledData = $unverifiedMahasantri->map(function ($mhs) {
            return [
                'id_mahasantri' => $mhs->id_mahasantri,
                'nama_lengkap' => $mhs->nama_lengkap,
            ];
        });

        $unscheduledData = $unverifiedMahasantri->map(fn($m) => ['id_mahasantri' => $m->id_mahasantri, 'nama_lengkap' => $m->nama_lengkap]);

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

    public function edit(JadwalTes $jadwalTes)
    {
        // Guard: cegah edit jika sudah disetujui
        if ($jadwalTes->status_konfirmasi === 'Disetujui') {
            return redirect()->route('seleksi.index')->with('error', 'Jadwal sudah disetujui, tidak bisa diedit');
        }

        $jadwalTes->load(['penanggungJawab', 'mahasantri', 'pengujiBacaanAlquran', 'pengujiTajwidTahsin', 'pengujiHafalan', 'pengujiWawancara']);
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

        // Reset status ke 'Menunggu' agar Ketua bisa review ulang
        // Gabung dalam satu update agar atomic — tidak perlu cek kondisi karena
        // guard di edit() sudah mencegah akses ke jadwal "Disetujui"
        $jadwalTes->update(array_merge($validated, ['status_konfirmasi' => 'Menunggu']));

        // TODO: kirim notifikasi ke pengawas tentang perubahan jadwal
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
            ->whereIn('status_konfirmasi', ['Menunggu', 'Perlu Revisi'])
            ->update([
                'status_konfirmasi' => 'Disetujui',
                'dikonfirmasi_oleh' => auth()->user()->id_panitia,
                'dikonfirmasi_pada' => now(),
            ]);

        $jumlah = JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->where('status_konfirmasi', 'Disetujui')
            ->count();

        // Kirim notifikasi ke Panitia yang membuat jadwal
        $pembuat = Panitia::find($jadwalTes->penanggung_jawab);
        if ($pembuat && $pembuat->email) {
            $ketua = auth()->user();
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
            'catatan_ketua' => 'required|string',
        ]);

        // Reject semua jadwal di tanggal yang sama
        $updated = JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->whereIn('status_konfirmasi', ['Menunggu', 'Perlu Revisi'])
            ->update([
                'status_konfirmasi' => 'Perlu Revisi',
                'catatan_ketua' => $request->catatan_ketua,
            ]);

        // Kirim notifikasi ke semua Panitia
        $panitiaList = Panitia::where('jabatan', 'Panitia')->get();
        foreach ($panitiaList as $p) {
            if ($p->email) {
                Mail::to($p->email)->send(new JadwalRejectedNotification(
                    $jadwalTes->tanggal, $request->catatan_ketua, $p->nama_lengkap
                ));
            }
        }

        return redirect()->route('seleksi.index')->with('success', "{$updated} jadwal diajukan perubahan, menunggu Panitia merespon");
    }

    public function sendUpdateNotification(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403);
        $mahasantri = $jadwalTes->mahasantri;
        if (!$mahasantri || !$mahasantri->email) return redirect()->back()->with('error', 'Mahasiswa tidak memiliki email');

        $jadwalTes->update(['zoom_reminder_sent' => false]);
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
        $tanggalRange = JadwalTes::where('status_konfirmasi', 'Disetujui')
            ->selectRaw('MIN(tanggal) as tgl_awal, MAX(tanggal) as tgl_akhir')
            ->first();
        $tanggalLabel = $tanggalRange && $tanggalRange->tgl_awal
            ? ($tanggalRange->tgl_awal === $tanggalRange->tgl_akhir
                ? $tanggalRange->tgl_awal
                : $tanggalRange->tgl_awal . ' s.d. ' . $tanggalRange->tgl_akhir)
            : '-';

        $updated = JadwalTes::where('status_konfirmasi', 'Disetujui')
            ->update([
                'status' => $request->jenis_pembatalan,
                'alasan_pembatalan' => $request->alasan_pembatalan,
                'dibatalkan_oleh' => auth()->user()->id_panitia,
                'dibatalkan_pada' => now(),
            ]);

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
        $tanggalRange = JadwalTes::where('status_konfirmasi', 'Menunggu')
            ->selectRaw('MIN(tanggal) as tgl_awal, MAX(tanggal) as tgl_akhir')
            ->first();
        $tanggalLabel = $tanggalRange && $tanggalRange->tgl_awal
            ? ($tanggalRange->tgl_awal === $tanggalRange->tgl_akhir
                ? $tanggalRange->tgl_awal
                : $tanggalRange->tgl_awal . ' s.d. ' . $tanggalRange->tgl_akhir)
            : '-';

        $updated = JadwalTes::whereIn('status_konfirmasi', ['Menunggu', 'Perlu Revisi'])
            ->update([
                'status_konfirmasi' => 'Disetujui',
                'dikonfirmasi_oleh' => auth()->user()->id_panitia,
                'dikonfirmasi_pada' => now(),
            ]);

        // Kirim notifikasi ke semua Panitia
        $panitiaList = Panitia::where('jabatan', 'Panitia')->get();
        foreach ($panitiaList as $p) {
            if ($p->email) {
                Mail::to($p->email)->send(new JadwalApprovedNotification(
                    $tanggalLabel, auth()->user(), $updated, $p->nama_lengkap
                ));
            }
        }

        return redirect()->route('seleksi.index')->with('success', "{$updated} jadwal berhasil disetujui");
    }

    /**
     * Ketua Panitia: Reject semua jadwal yang Menunggu
     */
    public function rejectAll(Request $request)
    {
        if (auth()->user()->jabatan !== 'Ketua Panitia') abort(403);

        $request->validate([
            'catatan_ketua' => 'required|string',
        ]);

        // Ambil rentang tanggal SEBELUM update
        $tanggalRange = JadwalTes::where('status_konfirmasi', 'Menunggu')
            ->selectRaw('MIN(tanggal) as tgl_awal, MAX(tanggal) as tgl_akhir')
            ->first();
        $tanggalLabel = $tanggalRange && $tanggalRange->tgl_awal
            ? ($tanggalRange->tgl_awal === $tanggalRange->tgl_akhir
                ? $tanggalRange->tgl_awal
                : $tanggalRange->tgl_awal . ' s.d. ' . $tanggalRange->tgl_akhir)
            : '-';

        $updated = JadwalTes::whereIn('status_konfirmasi', ['Menunggu', 'Perlu Revisi'])
            ->update([
                'status_konfirmasi' => 'Perlu Revisi',
                'catatan_ketua' => $request->catatan_ketua,
            ]);

        // Kirim notifikasi ke semua Panitia
        $panitiaList = Panitia::where('jabatan', 'Panitia')->get();
        foreach ($panitiaList as $p) {
            if ($p->email) {
                Mail::to($p->email)->send(new JadwalRejectedNotification(
                    $tanggalLabel, $request->catatan_ketua, $p->nama_lengkap
                ));
            }
        }

        return redirect()->route('seleksi.index')->with('success', "{$updated} jadwal diajukan perubahan");
    }

    /**
     * PERBAIKAN: Jadwalkan pengiriman email otomatis berdasarkan Gelombang Aktif
     */
    public function sendBulkResults(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403, 'Hanya panitia yang bisa kirim hasil test');

        // Panitia sekarang HANYA menginput tanggal & jam kapan email mau diluncurkan
        $request->validate([
            'tanggal_kirim' => 'required|date',
            'jam_kirim'     => 'required',
        ]);

        $waktuKirim = Carbon::parse($request->tanggal_kirim . ' ' . $request->jam_kirim);

        // Cari Gelombang yang sedang aktif hari ini (Auto-detect)
        $today = Carbon::today();
        $activeGelombang = Gelombang::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->first();

        // Kalau tidak ada yang aktif (misal pendaftaran sudah tutup), jadikan gelombang paling terakhir sebagai patokan
        if (!$activeGelombang) {
            $activeGelombang = Gelombang::orderBy('end_date', 'desc')->first();
        }

        if (!$activeGelombang) {
            return back()->with('error', 'Gagal: Belum ada konfigurasi gelombang di sistem.');
        }

        // Ambil SEMUA jadwal ujian yang berada dalam RENTANG TANGGAL gelombang tersebut
        $scheduled = JadwalTes::with(['mahasantri', 'hasilTes'])
            ->whereBetween('tanggal', [$activeGelombang->start_date, $activeGelombang->end_date])
            ->get();

        if ($scheduled->isEmpty()) {
            return back()->with('error', "Tidak ada jadwal seleksi pada {$activeGelombang->nama}.");
        }

        $sentCount = 0;

        foreach ($scheduled as $jadwal) {
            $mahasantri = $jadwal->mahasantri;
            
            $hasil = $jadwal->hasilTes instanceof \Illuminate\Database\Eloquent\Collection 
                ? $jadwal->hasilTes->first() 
                : $jadwal->hasilTes;

            // Filter ganda: Skip jika mahasantri kosong atau status belum direview (belum final)
            if (!$mahasantri || !$hasil || !in_array($mahasantri->status, ['Lulus', 'Tidak Lulus'])) {
                continue;
            }

            // Titipkan tugas pengiriman email ke queue (antrean) background
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