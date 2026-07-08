<?php

namespace App\Services\JadwalTes;

use App\Mail\JadwalApprovedNotification;
use App\Mail\JadwalRejectedNotification;
use App\Models\JadwalTes;
use App\Models\Panitia;
use Illuminate\Support\Facades\Mail;

/**
 * Menampung workflow approval ketua panitia, baik per tanggal maupun massal.
 */
class JadwalTesApprovalService
{
    /**
     * Approval satu jadwal diterjemahkan sebagai approval semua jadwal
     * pada tanggal yang sama.
     */
    public function approveByTanggal(JadwalTes $jadwalTes, string $ketuaId): array
    {
        $jadwalsToApprove = JadwalTes::query()
            ->where('tanggal', $jadwalTes->tanggal)
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->get();

        JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->update([
                'status_jadwal' => 'Disetujui',
                'diproses_oleh' => $ketuaId,
            ]);

        $pembuat = Panitia::find($jadwalTes->penanggung_jawab);
        if ($pembuat && $pembuat->email) {
            $ketua = Panitia::findOrFail($ketuaId);
            Mail::to($pembuat->email)->send(new JadwalApprovedNotification(
                $jadwalTes->tanggal,
                $ketua,
                $jadwalsToApprove->count(),
                $pembuat->nama_lengkap
            ));
        }

        $totalApprovedOnDate = JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->where('status_jadwal', 'Disetujui')
            ->count();

        return [
            'tanggal' => $jadwalTes->tanggal,
            'jumlah' => $totalApprovedOnDate,
        ];
    }

    /**
     * Reject pada satu jadwal juga berlaku untuk seluruh batch tanggal yang sama.
     */
    public function rejectByTanggal(JadwalTes $jadwalTes, string $catatanPerubahan): int
    {
        $updatedJadwals = JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->get();

        JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->update([
                'status_jadwal' => 'Revisi',
                'catatan_perubahan' => $catatanPerubahan,
            ]);

        foreach ($updatedJadwals->pluck('penanggung_jawab')->unique() as $pjId) {
            $p = Panitia::find($pjId);
            if ($p && $p->email) {
                Mail::to($p->email)->send(new JadwalRejectedNotification(
                    $jadwalTes->tanggal,
                    $catatanPerubahan,
                    $p->nama_lengkap
                ));
            }
        }

        return $updatedJadwals->count();
    }

    /**
     * Approval massal mengirim notifikasi terpisah per penanggung jawab.
     */
    public function approveAll(string $ketuaId): int
    {
        $jadwalsToApprove = JadwalTes::query()
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->get();
        
        $updatedIds = $jadwalsToApprove->pluck('id');

        JadwalTes::whereIn('id', $updatedIds)->update([
            'status_jadwal' => 'Disetujui',
            'diproses_oleh' => $ketuaId,
        ]);

        $tanggalLabel = $this->tanggalLabel(
            JadwalTes::whereIn('id', $updatedIds)
        );
        $ketua = Panitia::findOrFail($ketuaId);

        foreach ($jadwalsToApprove->pluck('penanggung_jawab')->unique() as $pjId) {
            $pembuat = Panitia::find($pjId);
            if ($pembuat && $pembuat->email) {
                Mail::to($pembuat->email)->send(new JadwalApprovedNotification(
                    $tanggalLabel,
                    $ketua,
                    $jadwalsToApprove->where('penanggung_jawab', $pjId)->count(),
                    $pembuat->nama_lengkap
                ));
            }
        }

        return $updatedIds->count();
    }

    /**
     * Reject massal tetap mempertahankan grouping notifikasi per penanggung jawab.
     */
    public function rejectAll(string $catatanPerubahan): int
    {
        $updatedJadwals = JadwalTes::whereIn('status_jadwal', ['Menunggu', 'Revisi'])->get();
        $tanggalLabel = $this->tanggalLabel(
            JadwalTes::whereIn('status_jadwal', ['Menunggu', 'Revisi'])
        );

        JadwalTes::whereIn('status_jadwal', ['Menunggu', 'Revisi'])->update([
            'status_jadwal' => 'Revisi',
            'catatan_perubahan' => $catatanPerubahan,
        ]);

        foreach ($updatedJadwals->pluck('penanggung_jawab')->unique() as $pjId) {
            $p = Panitia::find($pjId);
            if ($p && $p->email) {
                Mail::to($p->email)->send(new JadwalRejectedNotification(
                    $tanggalLabel,
                    $catatanPerubahan,
                    $p->nama_lengkap
                ));
            }
        }

        return $updatedJadwals->count();
    }

    private function tanggalLabel($query): string
    {
        $tanggalRange = $query->selectRaw('MIN(tanggal) as tgl_awal, MAX(tanggal) as tgl_akhir')->first();

        if (!$tanggalRange || !$tanggalRange->tgl_awal) {
            return '-';
        }

        return $tanggalRange->tgl_awal === $tanggalRange->tgl_akhir
            ? $tanggalRange->tgl_awal
            : $tanggalRange->tgl_awal . ' s.d. ' . $tanggalRange->tgl_akhir;
    }
}
