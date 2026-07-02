<?php

namespace App\Http\Controllers\JadwalTes;

use App\Http\Controllers\Controller;
use App\Models\Gelombang;
use App\Models\JadwalTes;
use App\Services\JadwalTes\JadwalTesService;
use Illuminate\Http\Request;

class JadwalTesCrudController extends Controller
{
    public function __construct(private readonly JadwalTesService $jadwalTesService)
    {
    }

    public function index()
    {
        return view('menu.jadwal-tes.index', $this->jadwalTesService->indexData());
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403, 'Hanya panitia yang bisa menambah jadwal tes');

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'interval' => 'required|integer|min:5|max:120',
            'link_zoom' => 'nullable|string',
            'penguji_bacaan_al_quran' => 'nullable|exists:panitia,id_panitia',
            'penguji_tajwid_tahsin' => 'nullable|exists:panitia,id_panitia',
            'penguji_hafalan' => 'nullable|exists:panitia,id_panitia',
            'penguji_wawancara' => 'nullable|exists:panitia,id_panitia',
        ]);

        try {
            $result = $this->jadwalTesService->createSchedules($validated, auth()->user()->id_panitia);
        } catch (\RuntimeException $e) {
            return redirect()->route('seleksi.index')->with('error', $e->getMessage());
        }

        return redirect()->route('seleksi.index')
            ->with('success', "Berhasil membuat {$result['count']} jadwal untuk {$result['gelombang_nama']}.")
            ->with('unscheduledMahasantri', $result['unscheduledMahasantri']);
    }

    public function edit(JadwalTes $jadwalTes)
    {
        if ($jadwalTes->status_jadwal === 'Disetujui') {
            return redirect()->route('seleksi.index')->with('error', 'Jadwal sudah disetujui, tidak bisa diedit');
        }

        $jadwalTes->load(['penanggungJawab', 'mahasantri', 'jadwalPenguji.panitia']);

        return view('menu.jadwal-tes.edit', [
            'jadwalTes' => $jadwalTes,
            'gelombangs' => Gelombang::orderBy('start_date')->get(),
        ]);
    }

    public function update(Request $request, JadwalTes $jadwalTes)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403);

        $validated = $request->validate([
            'jam' => 'nullable|date_format:H:i',
            'link_zoom' => 'nullable|string',
        ]);

        try {
            $this->jadwalTesService->updateSingleJadwal($jadwalTes, $validated, auth()->user()->id_panitia);
        } catch (\RuntimeException $e) {
            return redirect()->route('seleksi.index')->with('error', $e->getMessage());
        }

        return redirect()->route('seleksi.index')->with('success', 'Jadwal tes berhasil diperbarui');
    }

    public function destroy(JadwalTes $jadwalTes)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403);

        $jadwalTes->delete();

        return redirect()->route('seleksi.index')->with('success', 'Jadwal tes berhasil dihapus');
    }
}
