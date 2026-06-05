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
use Illuminate\Support\Facades\Artisan;
use App\Mail\TestResultPdf;
use App\Mail\ZoomLinkReminder;
use Barryvdh\DomPDF\Facade\Pdf;

class JadwalTesController extends Controller
{
    /**
     * Display a listing of all jadwal tes (seleksi & penilaian)
     */
    public function index()
    {
        $jadwals = JadwalTes::with(['penanggungJawab', 'mahasantri', 'pengujiTajwid', 'pengujiTahsin', 'pengujiKelancaran', 'pengujiWawancara'])->paginate(10);
        $panitias = Panitia::where('jabatan', 'Panitia')->get();

        // Dapatkan semua gelombang untuk batasan tanggal (untuk JavaScript validation)
        $gelombangs = Gelombang::orderBy('start_date')->get(['id', 'nama', 'start_date', 'end_date']);

        // Tentukan gelombang yang aktif berdasarkan tanggal hari ini (for display purposes)
        $today = Carbon::today();
        $activeGelombang = Gelombang::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date')
            ->first();

        // Fallback: kalau gak ada gelombang aktif, gunakan gelombang pertama yang tersisa
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
     * Store a newly created jadwal tes — generate per mahasantri
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
            'penguji_tajwid'     => 'nullable|exists:panitia,id_panitia',
            'penguji_tahsin'     => 'nullable|exists:panitia,id_panitia',
            'penguji_kelancaran' => 'nullable|exists:panitia,id_panitia',
            'penguji_wawancara'  => 'nullable|exists:panitia,id_panitia',
        ], [
            'tanggal.required'   => 'Tanggal tes wajib diisi.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'interval.required'  => 'Interval per mahasantri wajib diisi.',
        ]);

        // Cari gelombang yang cocok dengan tanggal yang dipilih user
        $tanggal = Carbon::parse($validated['tanggal']);
        $prefixTahun = date('y');
        $gelombang = Gelombang::whereDate('start_date', '<=', $tanggal)
            ->whereDate('end_date', '>=', $tanggal)
            ->orderBy('start_date')
            ->first();

        // Hanya izinkan tanggal yang masuk dalam rentang gelombang
        if (!$gelombang) {
            return redirect()->route('seleksi.index')
                ->with('error', 'Tanggal ' . $validated['tanggal'] . ' tidak masuk dalam rentang gelombang manapun. Periksa tanggal yang dipilih.');
        }

        $prefixId = $prefixTahun . str_pad($gelombang->id, 2, '0', STR_PAD_LEFT);

        // Get all mahasantri with this prefix (both verified and unverified)
        $allMahasantri = User::where('id_mahasantri', 'LIKE', $prefixId . '%')->get();
        $verifiedMahasantri = $allMahasantri->filter(function ($mhs) {
            return $mhs->status === 'Terverifikasi';
        });
        $unverifiedMahasantri = $allMahasantri->reject(function ($mhs) {
            return $mhs->status === 'Terverifikasi';
        });

        if ($verifiedMahasantri->isEmpty()) {
            return redirect()->route('seleksi.index')
                ->with('error', 'Tidak ada mahasantri terverifikasi di ' . $gelombang->nama . '.');
        }

        $jamMulai = \Carbon\Carbon::createFromFormat('H:i', $validated['jam_mulai']);
        $interval = (int) $validated['interval'];
        $count = 0;

        // Ambil last counter id_jadwal
        $last = JadwalTes::where('id_jadwal', 'LIKE', 'JDT%')
            ->orderBy('id_jadwal', 'desc')
            ->first();
        $urut = $last ? (int) substr($last->id_jadwal, 3) + 1 : 1;

        foreach ($verifiedMahasantri as $mhs) {
            $idJadwal = 'JDT' . str_pad($urut, 2, '0', STR_PAD_LEFT);
            $urut++;

            JadwalTes::create([
                'id_jadwal'         => $idJadwal,
                'id_mahasantri'     => $mhs->id_mahasantri,
                'tanggal'           => $validated['tanggal'],
                'jam'               => $jamMulai->format('H:i'),
                'link_zoom'         => $validated['link_zoom'] ?? null,
                'penanggung_jawab'  => auth()->user()->id_panitia,
                'penguji_tajwid'    => $validated['penguji_tajwid'] ?? null,
                'penguji_tahsin'    => $validated['penguji_tahsin'] ?? null,
                'penguji_kelancaran' => $validated['penguji_kelancaran'] ?? null,
                'penguji_wawancara' => $validated['penguji_wawancara'] ?? null,
            ]);

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

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Berhasil membuat {$count} jadwal untuk Gelombang {$gelombang->nama}.",
                'unscheduledMahasantri' => $unscheduledData
            ], 201);
        }

        return redirect()->route('seleksi.index')
            ->with('success', "Berhasil membuat {$count} jadwal untuk Gelombang {$gelombang->nama}.")
            ->with('unscheduledMahasantri', $unscheduledData);
    }

    /**
     * Update link zoom massal berdasarkan tanggal
     */
    public function updateLinkZoomMassal(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa update link zoom');
        }

        $validated = $request->validate([
            'tanggal'   => 'required|date',
            'link_zoom' => 'required|string',
        ]);

        $updated = JadwalTes::where('tanggal', $validated['tanggal'])
            ->update(['link_zoom' => $validated['link_zoom']]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Link Zoom berhasil diperbarui untuk {$updated} jadwal.",
            ]);
        }

        return redirect()->route('seleksi.index')
            ->with('success', "Link Zoom berhasil diperbarui untuk {$updated} jadwal.");
    }

    /**
     * Show the form for editing the specified jadwal tes
     */
    public function edit(JadwalTes $jadwalTes)
    {
        $jadwalTes->load(['penanggungJawab', 'mahasantri', 'pengujiTajwid', 'pengujiTahsin', 'pengujiKelancaran', 'pengujiWawancara']);

        // Dapatkan semua gelombang untuk validasi tanggal di modal edit
        $gelombangs = Gelombang::orderBy('start_date')->get(['id', 'nama', 'start_date', 'end_date']);

        if (request()->wantsJson()) {
            return response()->json($jadwalTes);
        }

        return view('menu.jadwal-tes.edit', [
            'jadwalTes' => $jadwalTes,
            'gelombangs' => $gelombangs
        ]);
    }

    /**
     * Update the specified jadwal tes
     */
    public function update(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa mengubah jadwal tes');
        }

        $validated = $request->validate([
            'tanggal'     => 'required|date',
            'jam'         => 'nullable|date_format:H:i',
            'link_zoom'   => 'nullable|string',
            'penguji_tajwid'     => 'nullable|exists:panitia,id_panitia',
            'penguji_tahsin'     => 'nullable|exists:panitia,id_panitia',
            'penguji_kelancaran' => 'nullable|exists:panitia,id_panitia',
            'penguji_wawancara'  => 'nullable|exists:panitia,id_panitia',
        ]);

        // Validasi tanggal masuk rentang gelombang
        $tanggal = Carbon::parse($validated['tanggal']);
        $gelombang = Gelombang::whereDate('start_date', '<=', $tanggal)
            ->whereDate('end_date', '>=', $tanggal)
            ->orderBy('start_date')
            ->first();

        if (!$gelombang) {
            return redirect()->route('seleksi.index')
                ->with('error', 'Tanggal ' . $validated['tanggal'] . ' tidak masuk dalam rentang gelombang manapun. Periksa tanggal yang dipilih.');
        }

        // Update jadwal termasuk penguji
        $jadwalTes->update([
            'tanggal'           => $validated['tanggal'],
            'jam'               => $validated['jam'] ?? null,
            'link_zoom'         => $validated['link_zoom'] ?? null,
            'penguji_tajwid'    => $validated['penguji_tajwid'] ?? null,
            'penguji_tahsin'    => $validated['penguji_tahsin'] ?? null,
            'penguji_kelancaran' => $validated['penguji_kelancaran'] ?? null,
            'penguji_wawancara' => $validated['penguji_wawancara'] ?? null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Jadwal tes berhasil diperbarui']);
        }

        return redirect()->route('seleksi.index')->with('success', 'Jadwal tes berhasil diperbarui');
    }

    /**
     * Send update notification when jadwal is edited
     */
    public function sendUpdateNotification(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa kirim notifikasi');
        }

        $mahasantri = $jadwalTes->mahasantri;
        if (!$mahasantri || !$mahasantri->email) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Mahasiswa tidak memiliki email']);
            }
            return redirect()->back()->with('error', 'Mahasiswa tidak memiliki email');
        }

        // Reset flag so reminder will be sent again on next schedule run
        $jadwalTes->update(['zoom_reminder_sent' => false]);

        // Send immediate update notification
        Mail::to($mahasantri->email)->send(new ZoomLinkReminder($jadwalTes, $mahasantri));

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Notifikasi update terkirim ke ' . $mahasantri->nama_lengkap]);
        }

        return redirect()->back()->with('success', 'Notifikasi update terkirim ke ' . $mahasantri->nama_lengkap);
    }

    /**
     * Remove the specified jadwal tes
     */
    public function destroy(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa menghapus jadwal tes');
        }

        $jadwalTes->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Jadwal tes berhasil dihapus']);
        }

        return redirect()->route('seleksi.index')->with('success', 'Jadwal tes berhasil dihapus');
    }

    /**
     * Send bulk test results PDFs for all mahasantri in a jadwal group
     */
    public function sendBulkResults(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa kirim hasil test');
        }

        $request->validate([
            'tanggal' => 'required|date',
            'jam' => 'nullable|date_format:H:i',
        ]);

        $tanggal = $request->input('tanggal');
        $jam = $request->input('jam');

        $query = JadwalTes::where('tanggal', $tanggal);
        if ($jam) {
            $query->where('jam', $jam);
        }

        $jadwals = $query->with(['mahasantri', 'hasilTes'])->get();

        $count = 0;
        foreach ($jadwals as $jadwal) {
            $mahasantri = $jadwal->mahasantri;
            $hasil = $jadwal->hasilTes->first();

            if (!$mahasantri || !$hasil) {
                continue;
            }

            // Generate PDF
            $pdf = Pdf::loadView('menu.laporan.pdf-single', [
                'judulTanggal' => $jadwal->tanggal . ' – ' . $jadwal->jam,
                'mahasantri'   => $mahasantri,
                'hasil'        => $hasil,
            ])->setPaper('A4');
            $pdfOutput = $pdf->output();

            // Send email
            Mail::to($mahasantri->email)->send(new TestResultPdf($mahasantri, $hasil, $pdfOutput));
            $count++;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Berhasil mengirim {$count} hasil test ke email mahasantri.",
            ]);
        }

        return redirect()->back()
            ->with('success', "Berhasil mengirim {$count} hasil test ke email mahasantri.");
    }
}