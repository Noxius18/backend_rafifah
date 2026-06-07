<?php

namespace App\Http\Controllers;

use App\Models\HasilTes;
use App\Models\JadwalTes;
use App\Models\User;
use Illuminate\Http\Request;

class HasilTesController extends Controller
{
    /**
     * Display the input nilai page for a specific jadwal tes
     */
    public function index(JadwalTes $jadwalTes)
    {
        $jadwalTes->load(['pengujiList.panitia', 'mahasantri']);

        // Get the mahasantri related to this jadwal
        $mahasantris = collect();
        if ($jadwalTes->mahasantri) {
            $mahasantris = collect([$jadwalTes->mahasantri]);
        }

        // Get existing hasil tes for this jadwal
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
     * Store/update hasil tes for a mahasantri
     * Hanya Panitia & Penguji yang bisa input/edit nilai
     */
    public function store(Request $request)
    {
        // Cek role: hanya Panitia yang bisa input nilai
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

        // Calculate total (average of 4 aspects)
        $nilai = array_filter([
            $validated['nilai_bacaan_al_quran'],
            $validated['nilai_tajwid_tahsin'],
            $validated['nilai_hafalan'],
            $validated['nilai_wawancara'],
        ], function($v) { return $v !== null; });

        $totalNilai = count($nilai) > 0 ? round(array_sum($nilai) / count($nilai)) : null;

        // Determine status
        $status = 'Belum Tes';
        if ($totalNilai !== null) {
            $hasPertimbangan = false;
            foreach (['nilai_bacaan_al_quran', 'nilai_tajwid_tahsin', 'nilai_hafalan', 'nilai_wawancara'] as $aspek) {
                if ($validated[$aspek] !== null && $validated[$aspek] == 70) {
                    $hasPertimbangan = true;
                    break;
                }
            }

            if ($hasPertimbangan) {
                $status = 'Pertimbangan';
            } elseif ($totalNilai >= 70) {
                $status = 'Lulus';
            } else {
                $status = 'Tidak Lulus';
            }
        }

        // Check if already exists — then update, else create
        $existing = HasilTes::where('id_mahasantri', $validated['id_mahasantri'])
            ->where('id_jadwal', $validated['id_jadwal'])
            ->first();

        if ($existing) {
            // Only update nilai if status is not 'Pertimbangan' or if the user is pengawas updating
            // For now, allow overwrite
            $existing->update([
                'nilai_bacaan_al_quran'     => $validated['nilai_bacaan_al_quran'],
                'nilai_tajwid_tahsin'     => $validated['nilai_tajwid_tahsin'],
                'nilai_hafalan' => $validated['nilai_hafalan'],
                'nilai_wawancara'  => $validated['nilai_wawancara'],
                'total_nilai'      => $totalNilai,
                'status'           => $status,
                'catatan_penguji'  => $validated['catatan_penguji'],
            ]);
        } else {
            // Generate ID
            $last = HasilTes::where('id_hasil', 'LIKE', 'HTL%')
                ->orderBy('id_hasil', 'desc')
                ->first();
            $urut = $last ? (int) substr($last->id_hasil, 3) + 1 : 1;

            HasilTes::create([
                'id_hasil'         => 'HTL' . str_pad($urut, 2, '0', STR_PAD_LEFT),
                'id_mahasantri'    => $validated['id_mahasantri'],
                'id_jadwal'        => $validated['id_jadwal'],
                'nilai_bacaan_al_quran'     => $validated['nilai_bacaan_al_quran'],
                'nilai_tajwid_tahsin'     => $validated['nilai_tajwid_tahsin'],
                'nilai_hafalan' => $validated['nilai_hafalan'],
                'nilai_wawancara'  => $validated['nilai_wawancara'],
                'total_nilai'      => $totalNilai,
                'status'           => $status,
                'catatan_penguji'  => $validated['catatan_penguji'],
            ]);
        }

        // =========================================================================
        // SINKRONISASI STATUS MAHASANTRI 
        // =========================================================================
        // Jika status hasil tes Lulus / Tidak Lulus -> Ubah status mahasantri
        // Jika Pertimbangan / Belum Tes -> Kembalikan atau jadikan Terverifikasi
        $mahasantriStatus = in_array($status, ['Lulus', 'Tidak Lulus']) ? $status : 'Terverifikasi';
        
        User::where('id_mahasantri', $validated['id_mahasantri'])->update([
            'status' => $mahasantriStatus
        ]);
        // =========================================================================

        // Return JSON for AJAX
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'message' => 'Nilai berhasil disimpan',
                'status'  => $status,
            ]);
        }

        return redirect()->route('seleksi.nilai', $validated['id_jadwal'])
            ->with('success', 'Nilai berhasil disimpan');
    }

    /**
     * Pengawas: review and update status (Lulus/Tidak Lulus) for pertimbangan
     * Hanya Pengawas yang bisa review
     */
    public function review(Request $request, HasilTes $hasilTes)
    {
        // Cek role: hanya Pengawas yang bisa review
        if (auth()->user()->jabatan !== 'Pengawas') {
            abort(403, 'Hanya pengawas yang bisa review pertimbangan');
        }

        $validated = $request->validate([
            'status'       => 'required|in:Lulus,Tidak Lulus',
            'nilai_tajwid_tahsin' => 'nullable|integer|min:0|max:100',
        ]);

        $updateData = ['status' => $validated['status']];

        // If pengawas changes the 70 value
        if (!empty($validated['nilai_tajwid_tahsin'])) {
            $updateData['nilai_tajwid_tahsin'] = $validated['nilai_tajwid_tahsin'];
            // Recalculate total
            $nilai = array_filter([
                $hasilTes->nilai_bacaan_al_quran,
                $validated['nilai_tajwid_tahsin'] ?? $hasilTes->nilai_tajwid_tahsin,
                $hasilTes->nilai_hafalan,
                $hasilTes->nilai_wawancara,
            ], function($v) { return $v !== null; });
            $updateData['total_nilai'] = count($nilai) > 0 ? round(array_sum($nilai) / count($nilai)) : null;
        }

        $hasilTes->update($updateData);

        // =========================================================================
        // SINKRONISASI STATUS MAHASANTRI SETELAH DIREVIEW PENGAWAS
        // =========================================================================
        // Karena pengawas sudah menyetujui, maka otomatis ubah status mahasantri-nya
        User::where('id_mahasantri', $hasilTes->id_mahasantri)->update([
            'status' => $validated['status']
        ]);
        // =========================================================================

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Status berhasil diperbarui']);
        }

        return redirect()->back()->with('success', 'Status berhasil diperbarui');
    }
}