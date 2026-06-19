<?php

namespace App\Http\Controllers;

use App\Models\HasilTes;
use App\Models\JadwalTes;
use App\Models\User;
use App\Models\Panitia;
use Illuminate\Http\Request;

class HasilTesController extends Controller
{
    /**
     * Display the input nilai page for a specific jadwal tes
     */
    public function index(JadwalTes $jadwalTes)
    {
        $jadwalTes->load([
            'penanggungJawab', 'mahasantri',
            'pengujiBacaanAlquran', 'pengujiTajwidTahsin',
            'pengujiHafalan', 'pengujiWawancara'
        ]);

        $mahasantris = collect();
        if ($jadwalTes->mahasantri) {
            $mahasantris = collect([$jadwalTes->mahasantri]);
        }

        $hasilTes = HasilTes::where('id_jadwal', $jadwalTes->id_jadwal)
            ->get()
            ->keyBy('id_mahasantri');

        return view('menu.jadwal-tes.nilai', [
            'jadwalTes'  => $jadwalTes,
            'mahasantris' => $mahasantris,
            'hasilTes'   => $hasilTes,
        ]);
    }

    /**
     * Store nilai individual per panitia
     * Cuma bisa input aspek yang ditugaskan
     * Jangan hitung status/total (kecuali creator) - itu via previewHasil + simpanHasil
     */
    public function store(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa input nilai');
        }

        $validated = $request->validate([
            'id_mahasantri'   => 'required|exists:mahasantri,id_mahasantri',
            'id_jadwal'       => 'required|exists:jadwal_tes,id_jadwal',
            'nilai_bacaan_al_quran'    => 'nullable|integer|min:0|max:100',
            'nilai_tajwid_tahsin'    => 'nullable|integer|min:0|max:100',
            'nilai_hafalan'    => 'nullable|integer|min:0|max:100',
            'nilai_wawancara' => 'nullable|integer|min:0|max:100',
            'catatan_penguji' => 'nullable|string',
        ]);

        // Cek aspek yang ditugaskan ke panitia ini
        $jadwal = JadwalTes::findOrFail($validated['id_jadwal']);
        $userId = auth()->user()->id_panitia;
        $isCreator = $jadwal->penanggung_jawab == $userId;

        $tugas = [];
        if ($jadwal->penguji_bacaan_al_quran == $userId) $tugas[] = 'nilai_bacaan_al_quran';
        if ($jadwal->penguji_tajwid_tahsin == $userId) $tugas[] = 'nilai_tajwid_tahsin';
        if ($jadwal->penguji_hafalan == $userId) $tugas[] = 'nilai_hafalan';
        if ($jadwal->penguji_wawancara == $userId) $tugas[] = 'nilai_wawancara';

        // Creator bisa input semua, selainnya cuma aspek yang ditugaskan
        $nilaiFields = ['nilai_bacaan_al_quran', 'nilai_tajwid_tahsin', 'nilai_hafalan', 'nilai_wawancara'];
        $updateData = [];
        foreach ($nilaiFields as $field) {
            if ($isCreator || in_array($field, $tugas)) {
                $updateData[$field] = $validated[$field] ?? null;
            }
        }
        $updateData['catatan_penguji'] = $validated['catatan_penguji'] ?? null;

        // Jangan hitung total/status saat store individual
        $updateData['total_nilai'] = null;
        $updateData['status'] = 'Belum Tes';

        $existing = HasilTes::where('id_mahasantri', $validated['id_mahasantri'])
            ->where('id_jadwal', $validated['id_jadwal'])
            ->first();

        if ($existing) {
            $existing->update($updateData);
        } else {
            $last = HasilTes::where('id_hasil', 'LIKE', 'HTL%')
                ->orderBy('id_hasil', 'desc')->first();
            $urut = $last ? (int) substr($last->id_hasil, 3) + 1 : 1;

            HasilTes::create(array_merge([
                'id_hasil'      => 'HTL' . str_pad($urut, 2, '0', STR_PAD_LEFT),
                'id_mahasantri' => $validated['id_mahasantri'],
                'id_jadwal'     => $validated['id_jadwal'],
            ], $updateData));
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Nilai berhasil disimpan']);
        }

        return redirect()->route('seleksi.nilai', $validated['id_jadwal'])
            ->with('success', 'Nilai berhasil disimpan');
    }

    /**
     * Preview hasil (hitung) - TANPA simpan
     * Hanya pembuat jadwal yang bisa
     */
    public function previewHasil(Request $request)
    {
        $jadwal = JadwalTes::findOrFail($request->id_jadwal);
        if (auth()->user()->id_panitia !== $jadwal->penanggung_jawab) {
            return response()->json(['error' => 'Hanya pembuat jadwal yang bisa menghitung hasil'], 403);
        }

        $hasil = HasilTes::where('id_mahasantri', $request->id_mahasantri)
            ->where('id_jadwal', $jadwal->id_jadwal)->first();

        if (!$hasil) return response()->json(['error' => 'Nilai tidak ditemukan'], 400);

        $nilaiFields = ['nilai_bacaan_al_quran', 'nilai_tajwid_tahsin', 'nilai_hafalan', 'nilai_wawancara'];
        $values = array_filter(array_map(fn($f) => $hasil->$f, $nilaiFields), fn($v) => $v !== null);

        if (count($values) < 4) {
            return response()->json([
                'error' => 'Nilai belum lengkap (' . count($values) . '/4)',
                'filled' => count($values), 'total' => 4,
            ], 400);
        }

        $total = round(array_sum($values) / count($values));
        $hasBelow70 = in_array(true, array_map(fn($n) => $n < 70, $values));
        $hasExactly70 = in_array(true, array_map(fn($n) => $n == 70, $values));

        if ($hasBelow70) $status = 'Tidak Lulus';
        elseif ($hasExactly70) $status = 'Pertimbangan';
        elseif ($total >= 70) $status = 'Lulus';
        else $status = 'Tidak Lulus';

        return response()->json([
            'total_nilai' => $total, 'rata_rata' => $total,
            'status' => $status, 'success' => true,
            'message' => "Total: {$total} | Rata-rata: {$total} | Status: {$status}",
        ]);
    }

    /**
     * Simpan hasil final - hanya pembuat jadwal
     */
    public function simpanHasil(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403);
        $jadwal = JadwalTes::findOrFail($request->id_jadwal);
        if (auth()->user()->id_panitia !== $jadwal->penanggung_jawab) {
            return response()->json(['error' => 'Hanya pembuat jadwal'], 403);
        }

        $hasil = HasilTes::where('id_mahasantri', $request->id_mahasantri)
            ->where('id_jadwal', $jadwal->id_jadwal)->first();
        if (!$hasil) return response()->json(['error' => 'Nilai tidak ditemukan'], 404);

        $nilaiFields = ['nilai_bacaan_al_quran', 'nilai_tajwid_tahsin', 'nilai_hafalan', 'nilai_wawancara'];
        $values = array_filter(array_map(fn($f) => $hasil->$f, $nilaiFields), fn($v) => $v !== null);
        if (count($values) < 4) return response()->json(['error' => 'Nilai belum lengkap'], 400);

        $total = round(array_sum($values) / 4);
        $hasBelow70 = in_array(true, array_map(fn($n) => $n < 70, $values));
        $hasExactly70 = in_array(true, array_map(fn($n) => $n == 70, $values));

        if ($hasBelow70) $status = 'Tidak Lulus';
        elseif ($hasExactly70) $status = 'Pertimbangan';
        elseif ($total >= 70) $status = 'Lulus';
        else $status = 'Tidak Lulus';

        $hasil->update(['total_nilai' => $total, 'status' => $status]);

        $mhsStatus = in_array($status, ['Lulus', 'Tidak Lulus']) ? $status : 'Terverifikasi';
        User::where('id_mahasantri', $hasil->id_mahasantri)->update(['status' => $mhsStatus]);

        return response()->json([
            'success' => true,
            'message' => "Hasil: Total {$total} | Rata-rata {$total} | {$status}",
            'status' => $status, 'total_nilai' => $total,
        ]);
    }

   /**
     * Ketua Panitia: review and update status (Lulus/Tidak Lulus) beserta nilai perbaikannya
     */
    public function review(Request $request, HasilTes $hasilTes)
    {
        if (auth()->user()->jabatan !== 'Ketua Panitia') {
            abort(403, 'Hanya Ketua Panitia yang bisa review pertimbangan');
        }

        // Validasi: Status wajib, nilai juga ditangkap untuk di-update
        $validated = $request->validate([
            'status'                => 'required|in:Lulus,Tidak Lulus',
            'nilai_bacaan_al_quran' => 'required|numeric|min:0|max:100',
            'nilai_tajwid_tahsin'   => 'required|numeric|min:0|max:100',
            'nilai_hafalan'         => 'required|numeric|min:0|max:100',
            'nilai_wawancara'       => 'required|numeric|min:0|max:100',
        ]);

        // Hitung ulang rata-rata berdasarkan nilai yang mungkin diedit pengawas
        $total = round(($validated['nilai_bacaan_al_quran'] + $validated['nilai_tajwid_tahsin'] + $validated['nilai_hafalan'] + $validated['nilai_wawancara']) / 4);

        // Update nilai dan status baru ke database
        $hasilTes->update([
            'nilai_bacaan_al_quran' => $validated['nilai_bacaan_al_quran'],
            'nilai_tajwid_tahsin'   => $validated['nilai_tajwid_tahsin'],
            'nilai_hafalan'         => $validated['nilai_hafalan'],
            'nilai_wawancara'       => $validated['nilai_wawancara'],
            'total_nilai'           => $total,
            'status'                => $validated['status']
        ]);

        // Sinkronisasi status mahasantri
        \App\Models\User::where('id_mahasantri', $hasilTes->id_mahasantri)->update([
            'status' => $validated['status']
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Status dan nilai berhasil diperbarui']);
        }

        return redirect()->back()->with('success', 'Status dan nilai berhasil diperbarui');
    }
}