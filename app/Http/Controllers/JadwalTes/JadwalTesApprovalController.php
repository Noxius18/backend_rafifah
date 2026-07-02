<?php

namespace App\Http\Controllers\JadwalTes;

use App\Http\Controllers\Controller;
use App\Models\JadwalTes;
use App\Services\JadwalTes\JadwalTesApprovalService;
use Illuminate\Http\Request;

/**
 * Entry point approval/revisi dari sisi ketua panitia.
 */
class JadwalTesApprovalController extends Controller
{
    public function __construct(private readonly JadwalTesApprovalService $approvalService)
    {
    }

    public function approve(Request $request, JadwalTes $jadwalTes)
    {
        abort_unless(auth()->user()->jabatan === 'Ketua Panitia', 403, 'Hanya Ketua Panitia yang bisa menyetujui jadwal');

        $result = $this->approvalService->approveByTanggal($jadwalTes, auth()->user()->id_panitia);

        return redirect()->route('seleksi.index')->with('success', 'Semua jadwal di tanggal ' . $result['tanggal'] . ' berhasil disetujui');
    }

    public function reject(Request $request, JadwalTes $jadwalTes)
    {
        abort_unless(auth()->user()->jabatan === 'Ketua Panitia', 403, 'Hanya Ketua Panitia yang bisa mengajukan perubahan');

        $request->validate([
            'catatan_perubahan' => 'required|string',
        ]);

        $count = $this->approvalService->rejectByTanggal($jadwalTes, $request->catatan_perubahan);

        return redirect()->route('seleksi.index')->with('success', "{$count} jadwal diajukan perubahan, menunggu Panitia merespon");
    }

    public function approveAll(Request $request)
    {
        abort_unless(auth()->user()->jabatan === 'Ketua Panitia', 403);

        $count = $this->approvalService->approveAll(auth()->user()->id_panitia);

        return redirect()->route('seleksi.index')->with('success', $count . ' jadwal berhasil disetujui');
    }

    public function rejectAll(Request $request)
    {
        abort_unless(auth()->user()->jabatan === 'Ketua Panitia', 403);

        $request->validate([
            'catatan_perubahan' => 'required|string',
        ]);

        $count = $this->approvalService->rejectAll($request->catatan_perubahan);

        return redirect()->route('seleksi.index')->with('success', "{$count} jadwal diajukan perubahan");
    }
}
