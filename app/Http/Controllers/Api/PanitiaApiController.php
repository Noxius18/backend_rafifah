<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Berkas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User; // Mengacu pada model Mahasantri
use Illuminate\Support\Facades\DB;
use App\Services\Mahasantri\MahasantriWorkflowService;
use Illuminate\Validation\Rule;

class PanitiaApiController extends Controller
{
    public function __construct(private readonly MahasantriWorkflowService $workflowService)
    {
    }

    /**
     * FITUR 1: Review Berkas Spesifik
     */
    public function reviewBerkas(Request $request, Berkas $berkas)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(Berkas::reviewableVerificationStatuses())],
            'catatan_revisi' => 'required_if:status,' . Berkas::STATUS_DITOLAK . '|nullable|string|max:255',
        ], [
            'status.in' => 'Status harus berupa disetujui atau ditolak.',
            'catatan_revisi.required_if' => 'Catatan revisi wajib diisi jika berkas ditolak.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();
        $reviewed = $this->workflowService->reviewBerkas(
            $berkas,
            $validated['status'],
            $validated['catatan_revisi'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Berkas ' . $reviewed->tipe_berkas . ' berhasil di-review dengan status: ' . $reviewed->status_verifikasi,
            'data' => [
                'id_berkas' => $reviewed->id_berkas,
                'status_verifikasi' => $reviewed->status_verifikasi,
                'catatan_revisi' => $reviewed->catatan_revisi,
            ],
        ], 200);
    }

    /**
     * FITUR 2: Unggah Surat Kelulusan (PDF) Tanpa Mengubah Struktur Tabel Hasil
     */
    public function unggahKelulusan(Request $request, $id_mahasantri)
    {
        $validator = Validator::make($request->all(), [
            'total_nilai' => 'required|integer',
            'surat_kelulusan' => 'required|file|mimes:pdf|max:4096', // Max 4MB
        ], [
            'surat_kelulusan.mimes' => 'Dokumen kelulusan harus format PDF.',
            'surat_kelulusan.max' => 'Ukuran file surat kelulusan maksimal 4MB.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $mahasantri = User::where('id_mahasantri', $id_mahasantri)->first();
        if (!$mahasantri) {
            return response()->json(['success' => false, 'message' => 'Mahasantri tidak ditemukan.'], 404);
        }

        // Trik Simpan File Ke Private Storage dengan penamaan Kaku (Anti Ubah Kolom DB)
        $file = $request->file('surat_kelulusan');
        $fileName = 'surat_lulus_' . $id_mahasantri . '.' . $file->getClientOriginalExtension();
        
        // Simpan ke folder private (storage/app/private/surat_kelulusan)
        $path = $file->storeAs('private/surat_kelulusan', $fileName);

        // Update status mahasantri utama menjadi Lulus
        $mahasantri->update(['status' => 'Lulus']);

        // Update atau buat data baru di tabel hasil_tes menggunakan kolom bawaan ERD kamu saat ini
        DB::table('hasil_tes')->updateOrInsert(
            ['id_mahasantri' => $id_mahasantri],
            [
                'id_hasil' => 'HSL' . $id_mahasantri,
                'status' => 'Lulus',
                'tanggal_pengumuman' => now()->format('Y-m-d'),
                // Jika di ERD tabel hasil kamu ada kolom nilai_akhir / sejenisnya, sesuaikan di bawah ini:
                // 'nilai_akhir' => $request->total_nilai 
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Surat kelulusan berhasil diunggah dan status mahasantri diperbarui menjadi Lulus.',
            'storage_path' => $path
        ], 200);
    }
}
