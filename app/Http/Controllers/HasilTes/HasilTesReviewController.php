<?php

namespace App\Http\Controllers\HasilTes;

use App\Http\Controllers\Controller;
use App\Models\HasilTes;
use App\Services\HasilTes\HasilTesService;
use Illuminate\Http\Request;

/**
 * Review akhir untuk kasus hasil tes yang masih membutuhkan keputusan ketua panitia.
 */
class HasilTesReviewController extends Controller
{
    public function __construct(private readonly HasilTesService $hasilTesService)
    {
    }

    public function review(Request $request, HasilTes $hasilTes)
    {
        abort_unless(auth()->user()->jabatan === 'Ketua Panitia', 403, 'Hanya Ketua Panitia yang bisa review pertimbangan');

        $validated = $request->validate([
            'status' => 'required|in:Lulus,Tidak Lulus',
            'nilai_bacaan_al_quran' => 'sometimes|numeric|min:0|max:100',
            'nilai_tajwid_tahsin' => 'sometimes|numeric|min:0|max:100',
            'nilai_hafalan' => 'sometimes|numeric|min:0|max:100',
            'nilai_wawancara' => 'sometimes|numeric|min:0|max:100',
            'catatan_ketua' => 'nullable|string',
            'catatan_bacaan_al_quran' => 'nullable|string',
            'catatan_tajwid_tahsin' => 'nullable|string',
            'catatan_hafalan' => 'nullable|string',
            'catatan_wawancara' => 'nullable|string',
        ]);

        $this->hasilTesService->review($hasilTes, $validated, $request->all());

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Status dan nilai berhasil diperbarui']);
        }

        return redirect()->back()->with('success', 'Status dan nilai berhasil diperbarui');
    }
}
