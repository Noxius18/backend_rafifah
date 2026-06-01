<?php

namespace App\Http\Controllers;

use App\Models\JadwalTes;
use App\Models\Penguji;
use App\Models\Panitia;
use App\Models\User;
use Illuminate\Http\Request;

class JadwalTesController extends Controller
{
    /**
     * Display a listing of all jadwal tes (seleksi & penilaian)
     */
    public function index()
    {
        $jadwals = JadwalTes::with(['pengujiList.panitia', 'penanggungJawab', 'mahasantri'])->paginate(10);
        $panitias = Panitia::where('jabatan', 'Panitia')->get();

        return view('menu.jadwal-tes.index', [
            'jadwals' => $jadwals,
            'panitias' => $panitias,
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
            'gelombang'        => 'required|in:1,2',
            'tanggal'          => 'required|date',
            'jam_mulai'        => 'required|date_format:H:i',
            'interval'         => 'required|integer|min:5|max:120',
            'link_zoom'        => 'nullable|string',
            'penguji_tajwid'     => 'nullable|exists:panitia,id_panitia',
            'penguji_tahsin'     => 'nullable|exists:panitia,id_panitia',
            'penguji_kelancaran' => 'nullable|exists:panitia,id_panitia',
            'penguji_wawancara'  => 'nullable|exists:panitia,id_panitia',
        ], [
            'gelombang.required' => 'Gelombang wajib dipilih.',
            'gelombang.in'       => 'Gelombang yang dipilih tidak valid.',
            'tanggal.required'   => 'Tanggal tes wajib diisi.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'interval.required'  => 'Interval per mahasantri wajib diisi.',
        ]);

        // Filter mahasantri terverifikasi berdasarkan prefix ID (tahun + nomor gelombang)
        $prefixTahun = date('y');
        $nomorGelombang = str_pad($validated['gelombang'], 2, '0', STR_PAD_LEFT);
        $prefixId = $prefixTahun . $nomorGelombang;

        $mahasantris = User::where('id_mahasantri', 'LIKE', $prefixId . '%')
            ->where('status', 'Terverifikasi')
            ->get();

        if ($mahasantris->isEmpty()) {
            return redirect()->route('seleksi.index')
                ->with('error', 'Tidak ada mahasantri terverifikasi di Gelombang ' . $validated['gelombang'] . '.');
        }

        $jamMulai = \Carbon\Carbon::createFromFormat('H:i', $validated['jam_mulai']);
        $interval = (int) $validated['interval'];
        $count = 0;

        // Ambil last counter id_jadwal
        $last = JadwalTes::where('id_jadwal', 'LIKE', 'JDT%')
            ->orderBy('id_jadwal', 'desc')
            ->first();
        $urut = $last ? (int) substr($last->id_jadwal, 3) + 1 : 1;

        foreach ($mahasantris as $mhs) {
            $idJadwal = 'JDT' . str_pad($urut, 2, '0', STR_PAD_LEFT);
            $urut++;

            JadwalTes::create([
                'id_jadwal'         => $idJadwal,
                'id_mahasantri'     => $mhs->id_mahasantri,
                'tanggal'           => $validated['tanggal'],
                'jam'               => $jamMulai->format('H:i'),
                'link_zoom'         => $validated['link_zoom'] ?? null,
                'penanggung_jawab'  => auth()->user()->id_panitia,
            ]);

            // Assign penguji per aspek
            $aspekMapping = [
                'penguji_tajwid'     => 'Tajwid',
                'penguji_tahsin'     => 'Tahsin',
                'penguji_kelancaran' => 'Kelancaran',
                'penguji_wawancara'  => 'Wawancara',
            ];

            foreach ($aspekMapping as $field => $aspek) {
                if (!empty($validated[$field])) {
                    $lastPenguji = Penguji::where('id_penguji', 'LIKE', 'PGJ%')
                        ->orderBy('id_penguji', 'desc')
                        ->first();
                    $urutPg = $lastPenguji ? (int) substr($lastPenguji->id_penguji, 3) + 1 : 1;

                    Penguji::create([
                        'id_penguji' => 'PGJ' . str_pad($urutPg, 2, '0', STR_PAD_LEFT),
                        'id_jadwal'  => $idJadwal,
                        'id_panitia' => $validated[$field],
                        'aspek'      => $aspek,
                    ]);
                }
            }

            $jamMulai->addMinutes($interval);
            $count++;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Berhasil membuat {$count} jadwal untuk Gelombang {$validated['gelombang']}.",
            ], 201);
        }

        return redirect()->route('seleksi.index')
            ->with('success', "Berhasil membuat {$count} jadwal untuk Gelombang {$validated['gelombang']}.");
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
        $jadwalTes->load(['pengujiList.panitia', 'mahasantri']);

        if (request()->wantsJson()) {
            return response()->json($jadwalTes);
        }

        return view('menu.jadwal-tes.edit', [
            'jadwalTes' => $jadwalTes
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

        // Update jadwal
        $jadwalTes->update([
            'tanggal'    => $validated['tanggal'],
            'jam'        => $validated['jam'] ?? null,
            'link_zoom'  => $validated['link_zoom'] ?? null,
        ]);

        // Update/sync penguji per aspek
        $aspekMapping = [
            'penguji_tajwid'     => 'Tajwid',
            'penguji_tahsin'     => 'Tahsin',
            'penguji_kelancaran' => 'Kelancaran',
            'penguji_wawancara'  => 'Wawancara',
        ];

        foreach ($aspekMapping as $field => $aspek) {
            Penguji::where('id_jadwal', $jadwalTes->id_jadwal)
                ->where('aspek', $aspek)
                ->delete();

            if (!empty($validated[$field])) {
                $lastPenguji = Penguji::where('id_penguji', 'LIKE', 'PGJ%')
                    ->orderBy('id_penguji', 'desc')
                    ->first();
                $urutPg = $lastPenguji ? (int) substr($lastPenguji->id_penguji, 3) + 1 : 1;

                Penguji::create([
                    'id_penguji' => 'PGJ' . str_pad($urutPg, 2, '0', STR_PAD_LEFT),
                    'id_jadwal'  => $jadwalTes->id_jadwal,
                    'id_panitia' => $validated[$field],
                    'aspek'      => $aspek,
                ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Jadwal tes berhasil diperbarui']);
        }

        return redirect()->route('seleksi.index')->with('success', 'Jadwal tes berhasil diperbarui');
    }

    /**
     * Remove the specified jadwal tes
     */
    public function destroy(Request $request, JadwalTes $jadwalTes)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa menghapus jadwal tes');
        }

        Penguji::where('id_jadwal', $jadwalTes->id_jadwal)->delete();
        $jadwalTes->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Jadwal tes berhasil dihapus']);
        }

        return redirect()->route('seleksi.index')->with('success', 'Jadwal tes berhasil dihapus');
    }
}