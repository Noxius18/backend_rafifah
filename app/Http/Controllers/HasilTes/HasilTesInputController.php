<?php

namespace App\Http\Controllers\HasilTes;

use App\Http\Controllers\Controller;
use App\Models\JadwalTes;
use App\Services\HasilTes\HasilTesService;
use Illuminate\Http\Request;

/**
 * Controller untuk input dan finalisasi nilai dari sisi panitia.
 */
class HasilTesInputController extends Controller
{
    public function __construct(private readonly HasilTesService $hasilTesService)
    {
    }

    public function index(JadwalTes $jadwalTes)
    {
        if ($jadwalTes->status_jadwal !== 'Disetujui') {
            return redirect()->route('seleksi.index')
                ->with('error', 'Jadwal belum disetujui oleh Ketua Panitia. Nilai hanya bisa diinput setelah jadwal disetujui.');
        }

        return view('menu.jadwal-tes.nilai', $this->hasilTesService->nilaiViewData($jadwalTes));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403, 'Hanya panitia yang bisa input nilai');

        $validated = $this->validateNilaiPayload($request);
        $jadwal = JadwalTes::with('jadwalPenguji')
            ->where('kode_jadwal', $validated['id_jadwal'])
            ->firstOrFail();

        if ($jadwal->status_jadwal !== 'Disetujui') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Jadwal belum disetujui Ketua Panitia'], 403);
            }

            return redirect()->route('seleksi.index')->with('error', 'Jadwal belum disetujui oleh Ketua Panitia.');
        }

        $this->hasilTesService->storeNilai($validated, $request->all(), auth()->user()->id_panitia);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Nilai berhasil disimpan']);
        }

        return redirect()->route('seleksi.nilai', $validated['id_jadwal'])->with('success', 'Nilai berhasil disimpan');
    }

    public function previewHasil(Request $request)
    {
        $jadwal = JadwalTes::with('jadwalPenguji')
            ->where('kode_jadwal', $request->id_jadwal)
            ->firstOrFail();
        if (auth()->user()->id_panitia !== $jadwal->penanggung_jawab) {
            return response()->json(['error' => 'Hanya pembuat jadwal yang bisa menghitung hasil'], 403);
        }

        // Preview menjaga UX tetap cepat karena hasil bisa dihitung tanpa commit ke tabel agregat.
        $result = $this->hasilTesService->previewHasil($jadwal);
        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json($result);
    }

    public function simpanHasil(Request $request)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403);

        $validated = $this->validateNilaiPayload($request);
        $jadwal = JadwalTes::with('jadwalPenguji')
            ->where('kode_jadwal', $validated['id_jadwal'])
            ->firstOrFail();

        if ($jadwal->status_jadwal !== 'Disetujui') {
            return response()->json(['error' => 'Jadwal belum disetujui Ketua Panitia'], 403);
        }

        if (auth()->user()->id_panitia !== $jadwal->penanggung_jawab) {
            return response()->json(['error' => 'Hanya pembuat jadwal'], 403);
        }

        try {
            $result = $this->hasilTesService->simpanHasil($validated, $request->all());
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json([
            'success' => true,
            'message' => "Hasil: Total {$result['total_nilai']} | Rata-rata {$result['rata_rata']} | {$result['status']}",
            'status' => $result['status'],
            'total_nilai' => $result['total_nilai'],
        ]);
    }

    private function validateNilaiPayload(Request $request): array
    {
        return $request->validate([
            'id_mahasantri' => 'required|exists:mahasantri,id_mahasantri',
            'id_jadwal' => 'required|exists:jadwal_seleksi,kode_jadwal',
            'nilai_bacaan_al_quran' => 'nullable|integer|min:0|max:100',
            'nilai_tajwid_tahsin' => 'nullable|integer|min:0|max:100',
            'nilai_hafalan' => 'nullable|integer|min:0|max:100',
            'nilai_wawancara' => 'nullable|integer|min:0|max:100',
            'catatan_bacaan_al_quran' => 'nullable|string',
            'catatan_tajwid_tahsin' => 'nullable|string',
            'catatan_hafalan' => 'nullable|string',
            'catatan_wawancara' => 'nullable|string',
        ]);
    }
}
