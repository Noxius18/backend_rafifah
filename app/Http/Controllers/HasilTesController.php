<?php

namespace App\Http\Controllers;

use App\Models\HasilTes;
use App\Models\JadwalTes;
use App\Models\JadwalPenguji;
use App\Models\User;
use App\Models\Panitia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HasilTesController extends Controller
{
    /**
     * Display the input nilai page for a specific jadwal tes
     */
    public function index(JadwalTes $jadwalTes)
    {
        // Guard: hanya bisa input nilai jika jadwal sudah disetujui Ketua Panitia
        if ($jadwalTes->status_jadwal !== 'Disetujui') {
            return redirect()->route('seleksi.index')
                ->with('error', 'Jadwal belum disetujui oleh Ketua Panitia. Nilai hanya bisa diinput setelah jadwal disetujui.');
        }

        $jadwalTes->load([
            'penanggungJawab', 'mahasantri',
            'jadwalPenguji.panitia',
        ]);

        $mahasantris = collect();
        if ($jadwalTes->mahasantri) {
            $mahasantris = collect([$jadwalTes->mahasantri]);
        }

        $hasilTes = HasilTes::where('id_jadwal', $jadwalTes->id_jadwal)->first();

        // Convert jadwal_penguji data to key-value for view compatibility
        // Nilai per-aspek diambil dari jadwal_penguji
        $nilaiPerAspek = [];
        foreach ($jadwalTes->jadwalPenguji as $jp) {
            $nilaiPerAspek[$jp->aspek_penguji] = [
                'nilai'    => $jp->nilai,
                'catatan'  => $jp->catatan_penguji,
                'penguji'  => $jp->panitia?->nama_lengkap,
                'id_panitia' => $jp->id_panitia,
            ];
        }

        // Build hasilTes collection keyed by mahasantri ID (via relationship)
        $hasilTesCollection = collect();
        if ($hasilTes) {
            $mhsId = $jadwalTes->mahasantri?->id_mahasantri;
            if ($mhsId) {
                $hasilTesCollection = collect([$mhsId => $hasilTes]);
            }
        }

        return view('menu.jadwal-tes.nilai', [
            'jadwalTes'     => $jadwalTes,
            'mahasantris'   => $mahasantris,
            'hasilTes'      => $hasilTesCollection,
            'nilaiPerAspek' => $nilaiPerAspek,
        ]);
    }

    /**
     * Store nilai individual per aspek ke jadwal_penguji
     * Panitia hanya bisa input aspek yang ditugaskan
     */
    public function store(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') {
            abort(403, 'Hanya panitia yang bisa input nilai');
        }

        $validated = $request->validate([
            'id_mahasantri'   => 'required|exists:mahasantri,id_mahasantri',
            'id_jadwal'       => 'required|exists:jadwal_seleksi,id_jadwal',
            'nilai_bacaan_al_quran' => 'nullable|integer|min:0|max:100',
            'nilai_tajwid_tahsin'   => 'nullable|integer|min:0|max:100',
            'nilai_hafalan'         => 'nullable|integer|min:0|max:100',
            'nilai_wawancara'       => 'nullable|integer|min:0|max:100',
            'catatan_bacaan_al_quran' => 'nullable|string',
            'catatan_tajwid_tahsin'   => 'nullable|string',
            'catatan_hafalan'         => 'nullable|string',
            'catatan_wawancara'       => 'nullable|string',
        ]);

        $jadwal = JadwalTes::with('jadwalPenguji')->findOrFail($validated['id_jadwal']);

        // Guard: hanya bisa simpan nilai jika jadwal sudah disetujui
        if ($jadwal->status_jadwal !== 'Disetujui') {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['error' => 'Jadwal belum disetujui Ketua Panitia'], 403);
            }
            return redirect()->route('seleksi.index')
                ->with('error', 'Jadwal belum disetujui oleh Ketua Panitia.');
        }

        $userId = auth()->user()->id_panitia;
        $isCreator = $jadwal->penanggung_jawab == $userId;

        // Mapping nilai field + catatan field ke aspek
        $fieldToAspek = [
            'nilai_bacaan_al_quran' => ['aspek' => 'Bacaan Al-Quran', 'catatan' => 'catatan_bacaan_al_quran'],
            'nilai_tajwid_tahsin'   => ['aspek' => 'Tajwid/Tahsin',   'catatan' => 'catatan_tajwid_tahsin'],
            'nilai_hafalan'         => ['aspek' => 'Hafalan',          'catatan' => 'catatan_hafalan'],
            'nilai_wawancara'       => ['aspek' => 'Wawancara',        'catatan' => 'catatan_wawancara'],
        ];

        // Simpan nilai ke jadwal_penguji untuk setiap aspek yang diinput
        foreach ($fieldToAspek as $field => $map) {
            $aspek = $map['aspek'];
            $catatanField = $map['catatan'];

            if ($request->has($field) && $request->$field !== null && $request->$field !== '') {
                // Cek apakah user berhak input aspek ini
                $isAssigned = $jadwal->jadwalPenguji
                    ->where('id_panitia', $userId)
                    ->where('aspek_penguji', $aspek)
                    ->isNotEmpty();

                if (!$isAssigned && !$isCreator) {
                    continue; // Skip aspek yang bukan tugasnya
                }

                $pengujiRow = $jadwal->jadwalPenguji
                    ->where('aspek_penguji', $aspek)
                    ->first();

                if ($pengujiRow) {
                    $updateData = ['nilai' => (int) $request->$field];
                    // Catatan per-aspek
                    if ($request->has($catatanField) && $request->$catatanField !== null && $request->$catatanField !== '') {
                        $updateData['catatan_penguji'] = $request->$catatanField;
                    }
                    JadwalPenguji::where('id_jadwal_penguji', $pengujiRow->id_jadwal_penguji)
                        ->update($updateData);
                }
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['message' => 'Nilai berhasil disimpan']);
        }

        return redirect()->route('seleksi.nilai', $validated['id_jadwal'])
            ->with('success', 'Nilai berhasil disimpan');
    }

    /**
     * Preview hasil (hitung dari jadwal_penguji) - TANPA simpan
     * Hanya pembuat jadwal yang bisa
     */
    public function previewHasil(Request $request)
    {
        $jadwal = JadwalTes::with('jadwalPenguji')->findOrFail($request->id_jadwal);
        if (auth()->user()->id_panitia !== $jadwal->penanggung_jawab) {
            return response()->json(['error' => 'Hanya pembuat jadwal yang bisa menghitung hasil'], 403);
        }

        // Ambil nilai dari jadwal_penguji
        $nilaiList = $jadwal->jadwalPenguji
            ->whereNotNull('nilai')
            ->pluck('nilai')
            ->toArray();

        if (count($nilaiList) < 4) {
            return response()->json([
                'error'  => 'Nilai belum lengkap (' . count($nilaiList) . '/4)',
                'filled' => count($nilaiList),
                'total'  => 4,
            ], 400);
        }

        $total = round(array_sum($nilaiList) / count($nilaiList));
        $below71Count = count(array_filter($nilaiList, fn($n) => $n < 71));

        if ($below71Count === 0) $status = 'Lulus';
        elseif ($below71Count === 1) $status = 'Pertimbangan';
        else $status = 'Tidak Lulus';

        return response()->json([
            'total_nilai' => $total,
            'rata_rata'   => $total,
            'status'      => $status,
            'success'     => true,
            'message'     => "Total: {$total} | Rata-rata: {$total} | Status: {$status}",
        ]);
    }

    /**
     * Simpan hasil final - hanya pembuat jadwal
     * Simpan total & status ke hasil_seleksi
     */
    public function simpanHasil(Request $request)
    {
        if (auth()->user()->jabatan !== 'Panitia') abort(403);

        $validated = $request->validate([
            'id_mahasantri'   => 'required|exists:mahasantri,id_mahasantri',
            'id_jadwal'       => 'required|exists:jadwal_seleksi,id_jadwal',
            'nilai_bacaan_al_quran' => 'nullable|integer|min:0|max:100',
            'nilai_tajwid_tahsin'   => 'nullable|integer|min:0|max:100',
            'nilai_hafalan'         => 'nullable|integer|min:0|max:100',
            'nilai_wawancara'       => 'nullable|integer|min:0|max:100',
            'catatan_bacaan_al_quran' => 'nullable|string',
            'catatan_tajwid_tahsin'   => 'nullable|string',
            'catatan_hafalan'         => 'nullable|string',
            'catatan_wawancara'       => 'nullable|string',
        ]);

        $jadwal = JadwalTes::with('jadwalPenguji')->findOrFail($validated['id_jadwal']);

        // Guard: hanya bisa simpan hasil jika jadwal sudah disetujui
        if ($jadwal->status_jadwal !== 'Disetujui') {
            return response()->json(['error' => 'Jadwal belum disetujui Ketua Panitia'], 403);
        }

        if (auth()->user()->id_panitia !== $jadwal->penanggung_jawab) {
            return response()->json(['error' => 'Hanya pembuat jadwal'], 403);
        }

        // Mapping nilai field ke aspek
        $fieldToAspek = [
            'nilai_bacaan_al_quran' => ['aspek' => 'Bacaan Al-Quran', 'catatan' => 'catatan_bacaan_al_quran'],
            'nilai_tajwid_tahsin'   => ['aspek' => 'Tajwid/Tahsin',   'catatan' => 'catatan_tajwid_tahsin'],
            'nilai_hafalan'         => ['aspek' => 'Hafalan',          'catatan' => 'catatan_hafalan'],
            'nilai_wawancara'       => ['aspek' => 'Wawancara',        'catatan' => 'catatan_wawancara'],
        ];

        // Simpan nilai + catatan ke jadwal_penguji dulu
        $nilaiList = [];
        foreach ($fieldToAspek as $field => $map) {
            $aspek = $map['aspek'];
            $catatanField = $map['catatan'];

            if ($request->has($field) && $request->$field !== null && $request->$field !== '') {
                $nilaiList[] = (int) $request->$field;

                $updateData = ['nilai' => (int) $request->$field];
                if ($request->has($catatanField) && $request->$catatanField !== null && $request->$catatanField !== '') {
                    $updateData['catatan_penguji'] = $request->$catatanField;
                }

                JadwalPenguji::where('id_jadwal', $jadwal->id_jadwal)
                    ->where('aspek_penguji', $aspek)
                    ->update($updateData);
            } else {
                // Fallback ke nilai yang sudah ada di DB
                $existing = $jadwal->jadwalPenguji
                    ->where('aspek_penguji', $aspek)
                    ->first();
                if ($existing && $existing->nilai !== null) {
                    $nilaiList[] = (int) $existing->nilai;
                }
            }
        }

        if (count($nilaiList) < 4) {
            return response()->json(['error' => 'Nilai belum lengkap (' . count($nilaiList) . '/4)'], 400);
        }

        $total = round(array_sum($nilaiList) / count($nilaiList));
        $below71Count = count(array_filter($nilaiList, fn($n) => $n < 71));

        if ($below71Count === 0) $status = 'Lulus';
        elseif ($below71Count === 1) $status = 'Pertimbangan';
        else $status = 'Tidak Lulus';

        // Simpan ke hasil_seleksi (agregat)
        $existingHasil = HasilTes::where('id_jadwal', $jadwal->id_jadwal)->first();

        if ($existingHasil) {
            $existingHasil->update([
                'total_nilai' => $total,
                'status'      => $status,
            ]);
        } else {
            // Generate id_hasil: HSL + nomor urut
            $last = HasilTes::where('id_hasil', 'LIKE', 'HSL%')->orderBy('id_hasil', 'desc')->first();
            $urut = $last ? (int) substr($last->id_hasil, 3) + 1 : 1;
            $idHasil = 'HSL' . str_pad($urut, 2, '0', STR_PAD_LEFT);

            HasilTes::create([
                'id_hasil'    => $idHasil,
                'id_jadwal'   => $jadwal->id_jadwal,
                'total_nilai' => $total,
                'status'      => $status,
            ]);
        }

        // Sinkronisasi status mahasantri
        // Lulus → Lulus, Tidak Lulus → Tidak Lulus, Pertimbangan → Terverifikasi (review Ketua)
        $mhsStatus = in_array($status, ['Lulus', 'Tidak Lulus']) ? $status : 'Terverifikasi';
        User::where('id_mahasantri', $validated['id_mahasantri'])->update(['status' => $mhsStatus]);

        return response()->json([
            'success'      => true,
            'message'      => "Hasil: Total {$total} | Rata-rata {$total} | {$status}",
            'status'       => $status,
            'total_nilai'  => $total,
        ]);
    }

    /**
     * Ketua Panitia: review and update status (Lulus/Tidak Lulus) beserta nilai perbaikannya
     * Update nilai di jadwal_penguji + catatan_ketua + update status di hasil_seleksi
     */
    public function review(Request $request, HasilTes $hasilTes)
    {
        if (auth()->user()->jabatan !== 'Ketua Panitia') {
            abort(403, 'Hanya Ketua Panitia yang bisa review pertimbangan');
        }

        $validated = $request->validate([
            'status'                  => 'required|in:Lulus,Tidak Lulus',
            'nilai_bacaan_al_quran'   => 'sometimes|numeric|min:0|max:100',
            'nilai_tajwid_tahsin'     => 'sometimes|numeric|min:0|max:100',
            'nilai_hafalan'           => 'sometimes|numeric|min:0|max:100',
            'nilai_wawancara'         => 'sometimes|numeric|min:0|max:100',
            'catatan_ketua'           => 'nullable|string',
            'catatan_bacaan_al_quran' => 'nullable|string',
            'catatan_tajwid_tahsin'   => 'nullable|string',
            'catatan_hafalan'         => 'nullable|string',
            'catatan_wawancara'       => 'nullable|string',
        ]);

        // Ambil nilai existing dari jadwal_penguji sebagai fallback
        $nilaiExisting = [];
        $catatanExisting = [];
        foreach (['Bacaan Al-Quran', 'Tajwid/Tahsin', 'Hafalan', 'Wawancara'] as $aspek) {
            $jp = JadwalPenguji::where('id_jadwal', $hasilTes->id_jadwal)
                ->where('aspek_penguji', $aspek)
                ->first();
            $nilaiExisting[$aspek] = $jp?->nilai;
            $catatanExisting[$aspek] = $jp?->catatan_penguji;
        }

        $nilaiToAspek = [
            'nilai_bacaan_al_quran' => ['aspek' => 'Bacaan Al-Quran', 'catatan' => 'catatan_bacaan_al_quran'],
            'nilai_tajwid_tahsin'   => ['aspek' => 'Tajwid/Tahsin',   'catatan' => 'catatan_tajwid_tahsin'],
            'nilai_hafalan'         => ['aspek' => 'Hafalan',          'catatan' => 'catatan_hafalan'],
            'nilai_wawancara'       => ['aspek' => 'Wawancara',        'catatan' => 'catatan_wawancara'],
        ];

        // Update nilai + catatan di jadwal_penguji untuk setiap aspek yang dikirim
        foreach ($nilaiToAspek as $field => $map) {
            $aspek = $map['aspek'];
            $catatanField = $map['catatan'];

            $updateData = [];

            // Nilai: pakai dari request jika ada, fallback ke existing
            if ($request->has($field) && $request->$field !== null && $request->$field !== '') {
                $updateData['nilai'] = (int) $validated[$field];
            } elseif ($nilaiExisting[$aspek] !== null) {
                $updateData['nilai'] = (int) $nilaiExisting[$aspek];
            }

            // Catatan per-aspek: pakai dari request jika ada, fallback ke existing
            if ($request->has($catatanField) && $request->$catatanField !== null && $request->$catatanField !== '') {
                $updateData['catatan_penguji'] = $request->$catatanField;
            } elseif ($catatanExisting[$aspek] !== null) {
                $updateData['catatan_penguji'] = $catatanExisting[$aspek];
            }

            if (!empty($updateData)) {
                JadwalPenguji::where('id_jadwal', $hasilTes->id_jadwal)
                    ->where('aspek_penguji', $aspek)
                    ->update($updateData);
            }
        }

        // Hitung rata-rata ulang dari jadwal_penguji (sudah diupdate)
        $jpReload = JadwalPenguji::where('id_jadwal', $hasilTes->id_jadwal)
            ->whereNotNull('nilai')
            ->pluck('nilai')
            ->toArray();

        $total = count($jpReload) > 0
            ? round(array_sum($jpReload) / count($jpReload))
            : 0;

        // Update catatan_ketua di jadwal_seleksi
        if ($request->has('catatan_ketua') && $request->catatan_ketua !== null && $request->catatan_ketua !== '') {
            $hasilTes->jadwalTes->update(['catatan_ketua' => $request->catatan_ketua]);
        }

        // Update status dan total di hasil_seleksi
        $hasilTes->update([
            'total_nilai' => $total,
            'status'      => $validated['status'],
        ]);

        // Sinkronisasi status mahasantri
        User::where('id_mahasantri', $hasilTes->jadwalTes->id_mahasantri)->update([
            'status' => $validated['status'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Status dan nilai berhasil diperbarui']);
        }

        return redirect()->back()->with('success', 'Status dan nilai berhasil diperbarui');
    }
}