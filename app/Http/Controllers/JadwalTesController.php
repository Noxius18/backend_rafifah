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
            ]);

            // Kirim email reminder Zoom otomatis setelah jadwal dibuat
            if ($newJadwal->link_zoom && $mhs->email) {
                Mail::to($mhs->email)->send(new ZoomLinkReminder($newJadwal, $mhs));
            }

            $jamMulai->addMinutes($interval);
            $count++;
        }

        // Prepare unscheduled mahasantri data for modal (only id and name)
        $unscheduledData = $unverifiedMahasantri->map(function ($mhs) {
            return [
                'id_mahasantri' => $mhs->id_mahasantri,
                'nama_lengkap' => $mhs->nama_lengkap,
            ];
        });

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
        $jadwalTes->load(['penanggungJawab', 'mahasantri', 'pengujiBacaanAlquran', 'pengujiTajwidTahsin', 'pengujiHafalan', 'pengujiWawancara']);
        return view('menu.jadwal-tes.edit', ['jadwalTes' => $jadwalTes, 'gelombangs' => Gelombang::orderBy('start_date')->get()]);
    }

    public function update(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403);
        
        // SUDAH DIPERBAIKI: Penamaan validation disesuaikan dengan DB
        $validated = $request->validate([
            'tanggal'                 => 'required|date', 
            'jam'                     => 'nullable|date_format:H:i', 
            'link_zoom'               => 'nullable|string',
            'penguji_bacaan_al_quran' => 'nullable|exists:panitia,id_panitia', 
            'penguji_tajwid_tahsin'   => 'nullable|exists:panitia,id_panitia',
            'penguji_hafalan'         => 'nullable|exists:panitia,id_panitia', 
            'penguji_wawancara'       => 'nullable|exists:panitia,id_panitia',
        ]);

        $jadwalTes->update($validated);
        return redirect()->route('seleksi.index')->with('success', 'Jadwal tes berhasil diperbarui');
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

    public function destroy(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403);
        $jadwalTes->delete();
        return redirect()->route('seleksi.index')->with('success', 'Jadwal tes dihapus');
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