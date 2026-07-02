<?php

namespace App\Http\Controllers\Mahasantri;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Mahasantri\MahasantriWorkflowService;
use Illuminate\Http\Request;

class MahasantriWorkflowController extends Controller
{
    public function __construct(private readonly MahasantriWorkflowService $workflowService)
    {
    }

    public function verifikasi(User $mahasantri)
    {
        $scheduled = $this->workflowService->verifyAndSchedule($mahasantri);

        return redirect()->back()->with(
            'success',
            $scheduled
                ? 'Mahasantri berhasil diverifikasi dan ditambahkan ke jadwal.'
                : 'Mahasantri berhasil diverifikasi.'
        );
    }

    public function destroyByTahunAjaran(Request $request)
    {
        abort_unless(
            auth()->user()->jabatan === 'Ketua Panitia',
            403,
            'Hanya Ketua Panitia yang bisa menghapus data mahasantri secara massal.'
        );

        $request->validate([
            'hapus_file_fisik' => 'nullable|boolean',
        ]);

        try {
            $stats = $this->workflowService->deleteByTahunAjaran($request->boolean('hapus_file_fisik'));
        } catch (\RuntimeException $e) {
            return redirect()->route('mahasantri.index')->with('error', $e->getMessage());
        }

        $msg = "Berhasil menghapus semua data tahun ajaran {$stats['tahun_ajaran']}: "
            . "{$stats['mahasantri']} mahasantri, {$stats['orangtua']} data ortu, "
            . "{$stats['berkas']} berkas, {$stats['jadwal']} jadwal, {$stats['hasil_tes']} hasil tes.";
        if ($request->boolean('hapus_file_fisik')) {
            $msg .= " File fisik dihapus: {$stats['files']}.";
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => $msg, 'stats' => $stats]);
        }

        return redirect()->route('mahasantri.index')->with('success', $msg);
    }
}
