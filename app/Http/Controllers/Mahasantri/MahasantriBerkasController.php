<?php

namespace App\Http\Controllers\Mahasantri;

use App\Http\Controllers\Controller;
use App\Models\Berkas;
use App\Services\Mahasantri\MahasantriWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MahasantriBerkasController extends Controller
{
    public function __construct(private readonly MahasantriWorkflowService $workflowService)
    {
    }

    public function downloadBerkas(Berkas $berkas)
    {
        if (!$berkas->file_path || !$berkas->file_exists) {
            return redirect()->back()->with('error', 'File belum tersedia atau belum diunduh.');
        }

        $fullPath = $berkas->storage_path;
        if (!file_exists($fullPath)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di penyimpanan.');
        }

        return response()->download($fullPath, $berkas->download_filename);
    }

    public function previewBerkas(Berkas $berkas)
    {
        if (!$berkas->file_path || !$berkas->file_exists) {
            abort(404, 'File belum tersedia atau belum diunduh.');
        }

        $fullPath = $berkas->storage_path;
        if (!file_exists($fullPath)) {
            abort(404, 'File tidak ditemukan di penyimpanan.');
        }

        return response()->file($fullPath, [
            'Content-Type' => mime_content_type($fullPath) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $berkas->download_filename . '"',
        ]);
    }

    public function retryDownload(Request $request, Berkas $berkas)
    {
        $latest = $berkas->riwayatUnduhan;
        if ($latest && $latest->download_status === 'success') {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Berkas ini sudah berhasil diunduh.'], 400);
            }

            return redirect()->back()->with('info', 'Berkas ini sudah berhasil diunduh.');
        }

        $this->workflowService->retryDownload($berkas);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Proses unduh ulang telah dimulai.']);
        }

        return redirect()->back()->with('success', 'Proses unduh ulang telah dimulai.');
    }

    public function updateBerkas(Request $request, Berkas $berkas)
    {
        $validated = $request->validate([
            'status_verifikasi' => ['nullable', Rule::in(Berkas::reviewableVerificationStatuses())],
            'catatan_revisi' => 'required_if:status_verifikasi,' . Berkas::STATUS_DITOLAK . '|nullable|string|max:255',
            'nik' => 'nullable|size:16|unique:mahasantri,nik,' . $berkas->id_mahasantri . ',id_mahasantri',
            'nisn' => 'nullable|size:10|unique:mahasantri,nisn,' . $berkas->id_mahasantri . ',id_mahasantri',
            'tempat_lahir' => 'nullable|string|max:50',
            'tanggal_lahir' => 'nullable|date',
        ], [
            'status_verifikasi.in' => 'Status verifikasi harus berupa disetujui atau ditolak.',
            'catatan_revisi.required_if' => 'Catatan revisi wajib diisi saat berkas ditolak.',
            'nik.size' => 'NIK harus 16 karakter.',
            'nik.unique' => 'NIK sudah terdaftar.',
            'nisn.size' => 'NISN harus 10 karakter.',
            'nisn.unique' => 'NISN sudah terdaftar.',
            'tempat_lahir.max' => 'Tempat lahir maksimal 50 karakter.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
        ]);

        $result = $this->workflowService->updateBerkas($berkas, $validated, $request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Data dokumen dan mahasantri berhasil diperbarui',
                'status_verifikasi' => $result['status_verifikasi'],
            ]);
        }

        return redirect()->back()->with('success', 'Data dokumen dan mahasantri berhasil diperbarui');
    }
}
