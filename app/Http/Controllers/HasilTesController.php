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
            'catatan_penguji'       => 'nullable|string',
        ]);

        $jadwal = JadwalTes::with('jadwalPenguji')->findOrFail($validated['id_jadwal']);
        $userId = auth()->user()->id_panitia;
        $isCreator = $jadwal->penanggung_jawab == $userId;

        // Mapping nilai field ke aspek
        $nilaiToAspek = [
            'nilai_bacaan_al_quran' => 'Bacaan Al-Quran',
            'nilai_tajwid_tahsin'   => 'Tajwid/Tahsin',
            'nilai_hafalan'         => 'Hafalan',
            'nilai_wawancara'       => 'Wawancara',
        ];

        // Simpan nilai ke jadwal_penguji untuk setiap aspek yang diinput
        foreach ($nilaiToAspek as $field => $aspek) {
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
                    // Update existing row
                    JadwalPenguji::where('id_jadwal_penguji', $pengujiRow->id_jadwal_penguji)
                        ->update([
                            'nilai'           => (int) $request->$field,
                            'catatan_penguji' => $validated['catatan_penguji'],
                        ]);
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
        $hasBelow70 = in_array(true, array_map(fn($n) => $n < 70, $nilaiList));
        $hasExactly70 = in_array(true, array_map(fn($n) => $n == 70, $nilaiList));

        if ($hasBelow70) $status = 'Tidak Lulus';
        elseif ($hasExactly70) $status = 'Pertimbangan';
        elseif ($total >= 70) $status = 'Lulus';
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

        $jadwal = JadwalTes::with('jadwalPenguji')->findOrFail($request->id_jadwal);
        if (auth()->user()->id_panitia !== $jadwal->penanggung_jawab) {
            return response()->json(['error' => 'Hanya pembuat jadwal'], 403);
        }

        // Ambil nilai dari jadwal_penguji
        $nilaiList = $jadwal->jadwalPenguji
            ->whereNotNull('nilai')
            ->pluck('nilai')
            ->toArray();

        if (count($nilaiList) < 4) {
            return response()->json(['error' => 'Nilai belum lengkap'], 400);
        }

        $total = round(array_sum($nilaiList) / count($nilaiList));
        $hasBelow70 = in_array(true, array_map(fn($n) => $n < 70, $nilaiList));
        $hasExactly70 = in_array(true, array_map(fn($n) => $n == 70, $nilaiList));

        if ($hasBelow70) $status = 'Tidak Lulus';
        elseif ($hasExactly70) $status = 'Pertimbangan';
        elseif ($total >= 70) $status = 'Lulus';
        else $status = 'Tidak Lulus';

        // Simpan ke hasil_seleksi (agregat)
        $hasil = HasilTes::updateOrCreate(
            ['id_jadwal' => $jadwal->id_jadwal],
            [
                'total_nilai' => $total,
                'status'      => $status,
            ]
        );

        // Jika hasil sudah final, sinkronisasi status mahasantri
        $mhsStatus = in_array($status, ['Lulus', 'Tidak Lulus']) ? $status : 'Terverifikasi';
        User::where('id_mahasantri', $request->id_mahasantri)->update(['status' => $mhsStatus]);

        return response()->json([
            'success'      => true,
            'message'      => "Hasil: Total {$total} | Rata-rata {$total} | {$status}",
            'status'       => $status,
            'total_nilai'  => $total,
        ]);
    }

    /**
     * Ketua Panitia: review and update status (Lulus/Tidak Lulus) beserta nilai perbaikannya
     * Update nilai di jadwal_penguji + update status di hasil_seleksi
     */
    public function review(Request $request, HasilTes $hasilTes)
    {
        if (auth()->user()->jabatan !== 'Ketua Panitia') {
            abort(403, 'Hanya Ketua Panitia yang bisa review pertimbangan');
        }

        $validated = $request->validate([
            'status'                => 'required|in:Lulus,Tidak Lulus',
            'nilai_bacaan_al_quran' => 'sometimes|numeric|min:0|max:100',
            'nilai_tajwid_tahsin'   => 'sometimes|numeric|min:0|max:100',
            'nilai_hafalan'         => 'sometimes|numeric|min:0|max:100',
            'nilai_wawancara'       => 'sometimes|numeric|min:0|max:100',
        ]);

        // Ambil nilai existing dari jadwal_penguji sebagai fallback
        $nilaiExisting = [];
        foreach (['Bacaan Al-Quran', 'Tajwid/Tahsin', 'Hafalan', 'Wawancara'] as $aspek) {
            $jp = JadwalPenguji::where('id_jadwal', $hasilTes->id_jadwal)
                ->where('aspek_penguji', $aspek)
                ->first();
            $nilaiExisting[$aspek] = $jp?->nilai;
        }

        $nilaiToAspek = [
            'nilai_bacaan_al_quran' => 'Bacaan Al-Quran',
            'nilai_tajwid_tahsin'   => 'Tajwid/Tahsin',
            'nilai_hafalan'         => 'Hafalan',
            'nilai_wawancara'       => 'Wawancara',
        ];

        $nilaiBaru = [];
        foreach ($nilaiToAspek as $field => $aspek) {
            if ($request->has($field) && $request->$field !== null && $request->$field !== '') {
                $nilaiBaru[$aspek] = (int) $validated[$field];
            } else {
                // Pakai nilai existing jika tidak dikirim
                $nilaiBaru[$aspek] = $nilaiExisting[$aspek];
            }
        }

        // Hitung rata-rata (gunakan 0 jika null)
        $nilaiNumerik = array_map(fn($v) => (int) $v, $nilaiBaru);
        $total = round(array_sum($nilaiNumerik) / count($nilaiNumerik));

        // Update nilai di jadwal_penguji untuk setiap aspek
        foreach ($nilaiBaru as $aspek => $nilai) {
            if ($nilai !== null) {
                JadwalPenguji::where('id_jadwal', $hasilTes->id_jadwal)
                    ->where('aspek_penguji', $aspek)
                    ->update(['nilai' => (int) $nilai]);
            }
        }

        // Update status dan total di hasil_seleksi
        $hasilTes->update([
            'total_nilai' => $total,
            'status'      => $validated['status'],
        ]);

        // Sinkronisasi status mahasantri
        User::where('id_mahasantri', $hasilTes->mahasantri->id_mahasantri)->update([
            'status' => $validated['status'],
        ]);

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Status dan nilai berhasil diperbarui']);
        }

        return redirect()->back()->with('success', 'Status dan nilai berhasil diperbarui');
    }
}