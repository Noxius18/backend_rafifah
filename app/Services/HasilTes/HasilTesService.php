<?php

namespace App\Services\HasilTes;

use App\Models\HasilTes;
use App\Models\JadwalPenguji;
use App\Models\JadwalTes;
use App\Models\User;

/**
 * Menyimpan aturan bisnis penilaian: input nilai per aspek, preview hasil,
 * finalisasi kelulusan, dan review ketua panitia.
 */
class HasilTesService
{
    /**
     * Menyiapkan payload yang dibutuhkan halaman input nilai dari struktur relasi
     * yang sudah ternormalisasi.
     */
    public function nilaiViewData(JadwalTes $jadwalTes): array
    {
        $jadwalTes->load(['penanggungJawab', 'mahasantri', 'jadwalPenguji.panitia']);
        $hasilTes = HasilTes::where('jadwal_id', $jadwalTes->id)->first();
        $user = auth()->user();
        $userId = $user->id_panitia;
        $isCreator = $jadwalTes->penanggung_jawab == $userId;

        $nilaiPerAspek = [];
        $tugas = [];
        foreach ($jadwalTes->jadwalPenguji as $jp) {
            $nilaiPerAspek[$jp->aspek_penguji] = [
                'nilai' => $jp->nilai,
                'catatan' => $jp->catatan_penguji,
                'penguji' => $jp->panitia?->nama_lengkap,
                'id_panitia' => $jp->id_panitia,
            ];

            if ($jp->id_panitia == $userId || $isCreator) {
                $tugas[] = $jp->aspek_penguji;
            }
        }

        $hasilTesCollection = collect();
        $mhsId = $jadwalTes->mahasantri?->id_mahasantri;
        if ($hasilTes && $mhsId) {
            $hasilTesCollection = collect([$mhsId => $hasilTes]);
        }

        $statusHasil = $hasilTes ? $hasilTes->status : 'Belum Tes';
        $isPertimbangan = $statusHasil === 'Pertimbangan';
        $isKetuaPanitia = $user->jabatan === 'Ketua Panitia';
        $isPanitia = $user->jabatan === 'Panitia';
        $isApproved = $jadwalTes->status_jadwal === 'Disetujui';

        return [
            'jadwalTes' => $jadwalTes,
            'mahasantris' => $jadwalTes->mahasantri ? collect([$jadwalTes->mahasantri]) : collect(),
            'hasilTes' => $hasilTesCollection,
            'nilaiPerAspek' => $nilaiPerAspek,
            'nilaiAspekCards' => $this->buildNilaiAspekCards($nilaiPerAspek, $tugas, $isCreator, $isPanitia, $isKetuaPanitia, $isApproved, $isPertimbangan),
            'pageState' => [
                'userId' => $userId,
                'isCreator' => $isCreator,
                'canInput' => count($tugas) > 0,
                'tugas' => $tugas,
                'isKetuaPanitia' => $isKetuaPanitia,
                'isPanitia' => $isPanitia,
                'isApproved' => $isApproved,
                'statusHasil' => $statusHasil,
                'isPertimbangan' => $isPertimbangan,
                'reviewId' => $hasilTes?->id_hasil,
                'formData' => [
                    'nilai_bacaan_al_quran' => (string) ($nilaiPerAspek['Bacaan Al-Quran']['nilai'] ?? ''),
                    'nilai_tajwid_tahsin' => (string) ($nilaiPerAspek['Tajwid/Tahsin']['nilai'] ?? ''),
                    'nilai_hafalan' => (string) ($nilaiPerAspek['Hafalan']['nilai'] ?? ''),
                    'nilai_wawancara' => (string) ($nilaiPerAspek['Wawancara']['nilai'] ?? ''),
                    'catatan_bacaan_al_quran' => (string) ($nilaiPerAspek['Bacaan Al-Quran']['catatan'] ?? ''),
                    'catatan_tajwid_tahsin' => (string) ($nilaiPerAspek['Tajwid/Tahsin']['catatan'] ?? ''),
                    'catatan_hafalan' => (string) ($nilaiPerAspek['Hafalan']['catatan'] ?? ''),
                    'catatan_wawancara' => (string) ($nilaiPerAspek['Wawancara']['catatan'] ?? ''),
                    'catatan_ketua' => (string) ($jadwalTes->catatan_ketua ?? ''),
                ],
            ],
        ];
    }

    /**
     * Panitia hanya boleh mengisi nilai untuk aspek yang ditugaskan,
     * kecuali pembuat jadwal yang diberi akses lebih luas.
     */
    public function storeNilai(array $validated, array $input, string $userId): void
    {
        $jadwal = JadwalTes::with('jadwalPenguji')
            ->where('kode_jadwal', $validated['id_jadwal'])
            ->firstOrFail();
        $isCreator = $jadwal->penanggung_jawab == $userId;

        foreach ($this->fieldToAspek() as $field => $map) {
            if (!array_key_exists($field, $input) || $input[$field] === null || $input[$field] === '') {
                continue;
            }

            $aspek = $map['aspek'];
            $isAssigned = $jadwal->jadwalPenguji
                ->where('id_panitia', $userId)
                ->where('aspek_penguji', $aspek)
                ->isNotEmpty();

            if (!$isAssigned && !$isCreator) {
                continue;
            }

            $pengujiRow = $jadwal->jadwalPenguji->where('aspek_penguji', $aspek)->first();
            if (!$pengujiRow) {
                continue;
            }

            $updateData = ['nilai' => (int) $input[$field]];
            $catatanField = $map['catatan'];
            if (array_key_exists($catatanField, $input) && $input[$catatanField] !== null && $input[$catatanField] !== '') {
                $updateData['catatan_penguji'] = $input[$catatanField];
            }

            JadwalPenguji::where('id_jadwal_penguji', $pengujiRow->id_jadwal_penguji)->update($updateData);
        }
    }

    /**
     * Preview dipakai untuk melihat status lulus/pertimbangan tanpa menyimpan hasil akhir.
     */
    public function previewHasil(JadwalTes $jadwal): array
    {
        $nilaiList = $jadwal->jadwalPenguji->whereNotNull('nilai')->pluck('nilai')->toArray();

        if (count($nilaiList) < 4) {
            return [
                'error' => 'Nilai belum lengkap (' . count($nilaiList) . '/4)',
                'filled' => count($nilaiList),
                'total' => 4,
            ];
        }

        return $this->calculateSummary($nilaiList);
    }

    /**
     * Finalisasi hasil akan memperbarui nilai per-aspek terlebih dahulu,
     * lalu menyimpan agregat ke hasil_seleksi dan sinkronisasi status mahasantri.
     */
    public function simpanHasil(array $validated, array $input): array
    {
        $jadwal = JadwalTes::with('jadwalPenguji')
            ->where('kode_jadwal', $validated['id_jadwal'])
            ->firstOrFail();
        $nilaiList = [];

        foreach ($this->fieldToAspek() as $field => $map) {
            $aspek = $map['aspek'];
            $catatanField = $map['catatan'];

            if (array_key_exists($field, $input) && $input[$field] !== null && $input[$field] !== '') {
                $nilaiList[] = (int) $input[$field];
                $updateData = ['nilai' => (int) $input[$field]];

                if (array_key_exists($catatanField, $input) && $input[$catatanField] !== null && $input[$catatanField] !== '') {
                    $updateData['catatan_penguji'] = $input[$catatanField];
                }

                JadwalPenguji::where('jadwal_id', $jadwal->id)
                    ->where('aspek_penguji', $aspek)
                    ->update($updateData);
            } else {
                $existing = $jadwal->jadwalPenguji->where('aspek_penguji', $aspek)->first();
                if ($existing && $existing->nilai !== null) {
                    $nilaiList[] = (int) $existing->nilai;
                }
            }
        }

        if (count($nilaiList) < 4) {
            throw new \RuntimeException('Nilai belum lengkap (' . count($nilaiList) . '/4)');
        }

        // Tabel hasil_seleksi diperlakukan sebagai agregat dari jadwal_penguji,
        // bukan sumber kebenaran utama untuk nilai per aspek.
        $summary = $this->calculateSummary($nilaiList);
        $hasil = HasilTes::where('jadwal_id', $jadwal->id)->first();

        if ($hasil) {
            $hasil->update([
                'total_nilai' => $summary['total_nilai'],
                'status' => $summary['status'],
            ]);
        } else {
            HasilTes::create([
                'jadwal_id' => $jadwal->id,
                'total_nilai' => $summary['total_nilai'],
                'status' => $summary['status'],
            ]);
        }

        $mhsStatus = in_array($summary['status'], ['Lulus', 'Tidak Lulus']) ? $summary['status'] : 'Terverifikasi';
        User::where('id_mahasantri', $validated['id_mahasantri'])->update(['status' => $mhsStatus]);

        return $summary;
    }

    /**
     * Ketua panitia dapat mengoreksi nilai/catatan per aspek sekaligus
     * memutuskan status final untuk kasus pertimbangan.
     */
    public function review(HasilTes $hasilTes, array $validated, array $input): int
    {
        $nilaiExisting = [];
        $catatanExisting = [];
        foreach (array_values($this->fieldToAspek()) as $map) {
            $jp = JadwalPenguji::where('jadwal_id', $hasilTes->jadwal_id)
                ->where('aspek_penguji', $map['aspek'])
                ->first();
            $nilaiExisting[$map['aspek']] = $jp?->nilai;
            $catatanExisting[$map['aspek']] = $jp?->catatan_penguji;
        }

        foreach ($this->fieldToAspek() as $field => $map) {
            $updateData = [];
            if (array_key_exists($field, $input) && $input[$field] !== null && $input[$field] !== '') {
                $updateData['nilai'] = (int) $validated[$field];
            } elseif ($nilaiExisting[$map['aspek']] !== null) {
                $updateData['nilai'] = (int) $nilaiExisting[$map['aspek']];
            }

            $catatanField = $map['catatan'];
            if (array_key_exists($catatanField, $input) && $input[$catatanField] !== null && $input[$catatanField] !== '') {
                $updateData['catatan_penguji'] = $input[$catatanField];
            } elseif ($catatanExisting[$map['aspek']] !== null) {
                $updateData['catatan_penguji'] = $catatanExisting[$map['aspek']];
            }

            if ($updateData !== []) {
                JadwalPenguji::where('jadwal_id', $hasilTes->jadwal_id)
                    ->where('aspek_penguji', $map['aspek'])
                    ->update($updateData);
            }
        }

        $jpReload = JadwalPenguji::where('jadwal_id', $hasilTes->jadwal_id)
            ->whereNotNull('nilai')
            ->pluck('nilai')
            ->toArray();
        $total = count($jpReload) > 0 ? round(array_sum($jpReload) / count($jpReload)) : 0;

        if (array_key_exists('catatan_ketua', $input) && $input['catatan_ketua'] !== null && $input['catatan_ketua'] !== '') {
            $hasilTes->jadwalTes->update(['catatan_ketua' => $input['catatan_ketua']]);
        }

        $hasilTes->update([
            'total_nilai' => $total,
            'status' => $validated['status'],
        ]);

        User::where('id_mahasantri', $hasilTes->jadwalTes->id_mahasantri)->update([
            'status' => $validated['status'],
        ]);

        return $total;
    }

    /**
     * Pemetaan field form dipusatkan di satu tempat agar controller dan service
     * tidak mengulang string aspek yang sama.
     */
    public function fieldToAspek(): array
    {
        return [
            'nilai_bacaan_al_quran' => ['aspek' => 'Bacaan Al-Quran', 'catatan' => 'catatan_bacaan_al_quran'],
            'nilai_tajwid_tahsin' => ['aspek' => 'Tajwid/Tahsin', 'catatan' => 'catatan_tajwid_tahsin'],
            'nilai_hafalan' => ['aspek' => 'Hafalan', 'catatan' => 'catatan_hafalan'],
            'nilai_wawancara' => ['aspek' => 'Wawancara', 'catatan' => 'catatan_wawancara'],
        ];
    }

    private function buildNilaiAspekCards(
        array $nilaiPerAspek,
        array $tugas,
        bool $isCreator,
        bool $isPanitia,
        bool $isKetuaPanitia,
        bool $isApproved,
        bool $isPertimbangan
    ): array {
        return collect([
            ['key' => 'Bacaan Al-Quran', 'field' => 'nilai_bacaan_al_quran', 'catatanField' => 'catatan_bacaan_al_quran', 'label' => "Bacaan Al-Qur'an"],
            ['key' => 'Tajwid/Tahsin', 'field' => 'nilai_tajwid_tahsin', 'catatanField' => 'catatan_tajwid_tahsin', 'label' => 'Tajwid & Tahsin'],
            ['key' => 'Hafalan', 'field' => 'nilai_hafalan', 'catatanField' => 'catatan_hafalan', 'label' => 'Hafalan'],
            ['key' => 'Wawancara', 'field' => 'nilai_wawancara', 'catatanField' => 'catatan_wawancara', 'label' => 'Wawancara'],
        ])->map(function (array $aspek) use ($nilaiPerAspek, $tugas, $isCreator, $isPanitia, $isKetuaPanitia, $isApproved, $isPertimbangan) {
            $nilaiAwal = $nilaiPerAspek[$aspek['key']]['nilai'] ?? '';
            $canEditScore = false;

            if ($isPanitia && $isApproved && (in_array($aspek['key'], $tugas, true) || $isCreator)) {
                $canEditScore = true;
            } elseif ($isKetuaPanitia && $isPertimbangan && (int) $nilaiAwal < 71 && $nilaiAwal !== '') {
                $canEditScore = true;
            }

            $canEditNote = $isPanitia && $isApproved && (in_array($aspek['key'], $tugas, true) || $isCreator);
            $isNilaiBelow71 = ((int) $nilaiAwal < 71 && $nilaiAwal !== '');

            return array_merge($aspek, [
                'pengujiNama' => $nilaiPerAspek[$aspek['key']]['penguji'] ?? '-',
                'canEditScore' => $canEditScore,
                'canEditNote' => $canEditNote,
                'isNilaiBelow71' => $isNilaiBelow71,
            ]);
        })->all();
    }

    /**
     * Rule kelulusan:
     * - tidak ada nilai di bawah 71 => Lulus
     * - satu nilai di bawah 71 => Pertimbangan
     * - lebih dari satu nilai di bawah 71 => Tidak Lulus
     */
    private function calculateSummary(array $nilaiList): array
    {
        $total = round(array_sum($nilaiList) / count($nilaiList));
        $below71Count = count(array_filter($nilaiList, fn($nilai) => $nilai < 71));

        if ($below71Count === 0) {
            $status = 'Lulus';
        } elseif ($below71Count === 1) {
            $status = 'Pertimbangan';
        } else {
            $status = 'Tidak Lulus';
        }

        return [
            'success' => true,
            'status' => $status,
            'total_nilai' => $total,
            'rata_rata' => $total,
            'message' => "Total: {$total} | Rata-rata: {$total} | Status: {$status}",
        ];
    }
}
