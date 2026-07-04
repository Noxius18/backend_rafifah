<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\User; // Mengacu pada model Mahasantri
use Illuminate\Support\Facades\DB;

class PanitiaApiController extends Controller
{
    /**
     * FITUR 1: Review Berkas Spesifik
     */
    public function reviewBerkas(Request $request, $id_berkas)
    {
        // Validasi input request
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:disetujui,ditolak',
            'catatan_revisi' => 'required_if:status,ditolak|nullable|string|max:255',
        ], [
            'status.in' => 'Status harus berupa disetujui atau ditolak.',
            'catatan_revisi.required_if' => 'Catatan revisi wajib diisi jika berkas ditolak.',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Cari data berkas di DB menggunakan Query Builder
        $berkas = DB::table('berkas')->where('id_berkas', $id_berkas)->first();

        if (!$berkas) {
            return response()->json(['success' => false, 'message' => 'Data berkas tidak ditemukan.'], 404);
        }

        // UPDATE DATA BERKAS: Masukkan status string verifikasi dan catatan revisi
        DB::table('berkas')->where('id_berkas', $id_berkas)->update([
            'status_verifikasi' => $request->status,
            'catatan_revisi' => $request->status === 'ditolak' ? $request->catatan_revisi : null,
        ]);

        // FIX ENUM ERROR: Kita hapus perubahan status mahasantri ke 'Revisi Berkas' 
        // agar tidak memicu SQLSTATE[01000] Data truncated di MariaDB lu.
        // Sebagai gantinya, status mahasantri dibiarkan sesuai ENUM bawaan database lu saat ini.

        return response()->json([
            'success' => true,
            'message' => 'Berkas ' . $berkas->tipe_berkas . ' berhasil di-review dengan status: ' . $request->status
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