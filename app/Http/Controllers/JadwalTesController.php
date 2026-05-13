<?php

namespace App\Http\Controllers;

use App\Models\JadwalTes;
use App\Models\Penguji;
use App\Models\Panitia;
use Illuminate\Http\Request;

class JadwalTesController extends Controller
{
    /**
     * Display a listing of all jadwal tes
     */
    public function index()
    {
        $jadwals = JadwalTes::with(['pengujiList.panitia', 'picPanitia'])->get();
        $panitias = Panitia::where('jabatan', 'Panitia')->get();

        return view('menu.jadwal-tes.index', [
            'jadwals' => $jadwals,
            'panitias' => $panitias,
        ]);
    }

    /**
     * Store a newly created jadwal tes
     */
    public function store(Request $request)
    {
        // Hanya panitia yang bisa buat jadwal
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa menambah jadwal tes');
        }

        $validated = $request->validate([
            'periode'     => 'required|string|max:20',
            'keterangan'  => 'required|string|max:50',
            'tanggal'     => 'required|date',
            'link_zoom'   => 'nullable|string',
            'penguji_tajwid'     => 'nullable|exists:panitia,id_panitia',
            'penguji_tahsin'     => 'nullable|exists:panitia,id_panitia',
            'penguji_kelancaran' => 'nullable|exists:panitia,id_panitia',
            'penguji_wawancara'  => 'nullable|exists:panitia,id_panitia',
        ], [
            'periode.required'   => 'Periode seleksi wajib diisi.',
            'keterangan.required' => 'Keterangan wajib diisi.',
            'tanggal.required'    => 'Tanggal tes wajib diisi.',
        ]);

        // Generate ID
        $last = JadwalTes::where('id_jadwal', 'LIKE', 'JDT%')
            ->orderBy('id_jadwal', 'desc')
            ->first();

        $urut = 1;
        if ($last) {
            $urut = (int) substr($last->id_jadwal, 3) + 1;
        }

        $idJadwal = 'JDT' . str_pad($urut, 2, '0', STR_PAD_LEFT);

        // Create jadwal — otomatis set PIC ke panitia yang login
        JadwalTes::create([
            'id_jadwal'  => $idJadwal,
            'periode'    => $validated['periode'],
            'keterangan' => $validated['keterangan'],
            'tanggal'    => $validated['tanggal'],
            'link_zoom'  => $validated['link_zoom'] ?? null,
            'pic'        => auth()->user()->id_panitia,
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

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Jadwal tes berhasil ditambahkan'], 201);
        }

        return redirect()->route('jadwal-tes.index')->with('success', 'Jadwal tes berhasil ditambahkan');
    }

    /**
     * Show the form for editing the specified jadwal tes
     */
    public function edit(JadwalTes $jadwalTes)
    {
        $jadwalTes->load('pengujiList.panitia');

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
        // Hanya panitia yang bisa edit jadwal
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa mengubah jadwal tes');
        }

        $validated = $request->validate([
            'periode'     => 'required|string|max:20',
            'keterangan'  => 'required|string|max:50',
            'tanggal'     => 'required|date',
            'link_zoom'   => 'nullable|string',
            'penguji_tajwid'     => 'nullable|exists:panitia,id_panitia',
            'penguji_tahsin'     => 'nullable|exists:panitia,id_panitia',
            'penguji_kelancaran' => 'nullable|exists:panitia,id_panitia',
            'penguji_wawancara'  => 'nullable|exists:panitia,id_panitia',
        ]);

        // Update jadwal
        $jadwalTes->update([
            'periode'    => $validated['periode'],
            'keterangan' => $validated['keterangan'],
            'tanggal'    => $validated['tanggal'],
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
            // Hapus penguji lama untuk aspek ini
            Penguji::where('id_jadwal', $jadwalTes->id_jadwal)
                ->where('aspek', $aspek)
                ->delete();

            // Buat penguji baru jika diisi
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

        return redirect()->route('jadwal-tes.index')->with('success', 'Jadwal tes berhasil diperbarui');
    }

    /**
     * Remove the specified jadwal tes
     */
    public function destroy(Request $request, JadwalTes $jadwalTes)
    {
        // Hanya panitia yang bisa hapus jadwal
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa menghapus jadwal tes');
        }

        // Hapus penguji terkait
        Penguji::where('id_jadwal', $jadwalTes->id_jadwal)->delete();
        $jadwalTes->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Jadwal tes berhasil dihapus']);
        }

        return redirect()->route('jadwal-tes.index')->with('success', 'Jadwal tes berhasil dihapus');
    }
}