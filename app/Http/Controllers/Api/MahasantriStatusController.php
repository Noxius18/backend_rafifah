<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BerkasResource;
use App\Http\Resources\Api\HasilTesResource;
use App\Http\Resources\Api\JadwalTesResource;
use App\Http\Resources\Api\MahasantriResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL; // <-- WAJIB IMPORT INI BRAY

class MahasantriStatusController extends Controller
{
    public function show(Request $request)
    {
        $mahasantri = $request->user()->load(['orangtuas', 'berkas.riwayatUnduhan']);

        $jadwal = $mahasantri->jadwalTes()
            ->with(['penanggungJawab', 'jadwalPenguji.panitia', 'hasilTes'])
            ->latest('tanggal')
            ->first();

        $fileUrl = null;

        // FIX LOGIKA LINK: Generate URL tertanda sementara yang berlaku selama 15 menit bray
        if (in_array($mahasantri->status, ['Lulus', 'Tidak Lulus'])) {
            $fileUrl = URL::temporarySignedRoute(
                'api.mahasantri.download-skl', // Nama route yang kita buat di langkah 1
                now()->addMinutes(15),         // Masa berlaku link di browser
                ['id_mahasantri' => $mahasantri->id_mahasantri]
            );
        }

        return response()->json([
            'data' => [
                'mahasantri' => new MahasantriResource($mahasantri),
                'berkas' => BerkasResource::collection($mahasantri->berkas),
                'jadwal' => $jadwal ? new JadwalTesResource($jadwal) : null,
                'hasil' => $jadwal?->hasilTes ? array_merge((new HasilTesResource($jadwal->hasilTes))->resolve(), [
                    'file_url' => $fileUrl
                ]) : null,
            ],
        ]);
    }

    /**
     * Download SKL / Surat Keputusan Hasil Seleksi via Signed URL bray
     */
    /**
     * Download SKL / Surat Keputusan Hasil Seleksi via Signed URL bray
     */
    public function downloadSkl(Request $request, $id_mahasantri)
    {
        // Karena di luar token middleware, kita cari user berdasarkan id_mahasantri dari parameter bray
        $mahasantri = \App\Models\User::where('id_mahasantri', $id_mahasantri)->firstOrFail();
        
        $prefix = $mahasantri->status === 'Lulus' ? 'surat_lulus_' : 'surat_tidak_lulus_';
        $fileName = $prefix . $mahasantri->id_mahasantri . '.pdf';
        $path = 'private/surat_kelulusan/' . $fileName;

        if (!\Illuminate\Support\Facades\Storage::disk('local')->exists($path)) {
            return response()->json(['message' => 'Surat keputusan PDF belum diterbitkan oleh panitia.'], 404);
        }

        $downloadName = $mahasantri->status === 'Lulus' ? 'Surat_Kelulusan_' : 'Surat_Penolakan_';
        $absolutePath = \Illuminate\Support\Facades\Storage::disk('local')->path($path);
        
        // FIX: Gunakan response()->file() ditambah header 'inline' agar PDF langsung terbuka di dalam tab browser bray!
        return response()->file($absolutePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $downloadName . str_replace(' ', '_', $mahasantri->nama_lengkap) . '.pdf"'
        ]);
    }
}