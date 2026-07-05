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
     * FITUR 2: Unggah Surat Kelulusan (Bisa Manual / Otomatis Otomatis Generate PDF)
     */
    public function unggahKelulusan(Request $request, $id_mahasantri)
    {
        $validator = Validator::make($request->all(), [
            'total_nilai' => 'required|integer',
            'surat_kelulusan' => 'nullable|file|mimes:pdf|max:4096', // Diubah jadi nullable bray
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

        $fileName = 'surat_lulus_' . $id_mahasantri . '.pdf';
        $path = 'private/surat_kelulusan/' . $fileName;

        // OPSI A: JIKA PANITIA MENGUNGGAH FILE SECARA MANUAl BRAY
        if ($request->hasFile('surat_kelulusan')) {
            $file = $request->file('surat_kelulusan');
            $path = $file->storeAs('private/surat_kelulusan', $fileName);
        } 
        // OPSI B: GENERATE PDF OTOMATIS DARI VIEW TEMPLATE (Sama seperti tombol Cetak Nilai bray!)
        else {
            $jadwal = \App\Models\JadwalTes::where('id_mahasantri', $id_mahasantri)
                ->with('hasilTes', 'jadwalPenguji.panitia')
                ->first();

            if (!$jadwal || !$jadwal->hasilTes) {
                return response()->json(['success' => false, 'message' => 'Hasil tes mahasantri belum lengkap.'], 400);
            }

            $hasilTes = collect([$jadwal->hasilTes]);
            $suratMeta = $this->workflowService->buildSuratKelulusanMeta($mahasantri);

            // Memanggil view cetak pdf yang sudah kamu sediakan bray
            $html = view('menu.laporan.pdf-nilai-single', [
                'mahasantri' => $mahasantri,
                'hasilTes' => $hasilTes,
                'jadwal' => $jadwal,
                'date' => now()->format('d/m/Y H:i'),
                'nomorSurat' => $suratMeta['nomor_surat'],
                'tahunAjaran' => $suratMeta['tahun_ajaran'],
                'tahunAjaranBerikutnya' => $suratMeta['tahun_ajaran_berikutnya'],
                'labelTahunAjaran' => $suratMeta['label_tahun_ajaran'],
            ])->render();

            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', false);

            $dompdf = new \Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            // Simpan langsung ke folder private storage bray
            Storage::put($path, $dompdf->output());
        }

        // Update status mahasantri utama menjadi Lulus
        $mahasantri->update(['status' => 'Lulus']);

        // Update atau buat data baru di tabel hasil_tes
        DB::table('hasil_tes')->updateOrInsert(
            ['id_mahasantri' => $id_mahasantri],
            [
                'id_hasil' => 'HSL' . $id_mahasantri,
                'status' => 'Lulus',
                'tanggal_pengumuman' => now()->format('Y-m-d'),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Surat kelulusan berhasil diproses dan status mahasantri diperbarui menjadi Lulus bray.',
            'storage_path' => $path
        ], 200);
    }
}
