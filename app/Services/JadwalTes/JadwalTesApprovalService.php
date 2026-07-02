<?php

namespace App\Services\JadwalTes;

use App\Mail\JadwalApprovedNotification;
use App\Mail\JadwalRejectedNotification;
use App\Mail\ZoomLinkReminder;
use App\Models\JadwalTes;
use App\Models\Panitia;
use Illuminate\Support\Collection;
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
        JadwalTes::where('tanggal', $jadwalTes->tanggal)
            ->whereIn('status_jadwal', ['Menunggu', 'Revisi'])
            ->update([
                'status_jadwal' => 'Disetujui',
                'diproses_oleh' => $ketuaId,
            ]);

        $approvedJadwals = JadwalTes::with('mahasantri')
            ->where('tanggal', $jadwalTes->tanggal)
            ->where('status_jadwal', 'Disetujui')
            ->get();

        // Setelah jadwal sah disetujui, link Zoom langsung dikirim ke mahasantri.
        foreach ($approvedJadwals as $approved) {
            $mhs = $approved->mahasantri;
            if ($approved->link_zoom && $mhs && $mhs->email) {
                Mail::to($mhs->email)->send(new ZoomLinkReminder($approved, $mhs));
            }
        }

        $pembuat = Panitia::find($jadwalTes->penanggung_jawab);
        if ($pembuat && $pembuat->email) {
            $ketua = Panitia::findOrFail($ketuaId);
            Mail::to($pembuat->email)->send(new JadwalApprovedNotification(
                $jadwalTes->tanggal,
                $ketua,
                $approvedJadwals->count(),
                $pembuat->nama_lengkap
            ));
        }

        return [
            'tanggal' => $jadwalTes->tanggal,
            'jumlah' => $approvedJadwals->count(),
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
     * Approval massal mengirim notifikasi terpisah per penanggung jawab agar
     * masing-masing pembuat hanya menerima ringkasan jadwal yang relevan.
     */
    public function approveAll(string $ketuaId): int
    {
        $updatedIds = JadwalTes::whereIn('status_jadwal', ['Menunggu', 'Revisi'])->pluck('id_jadwal');

        JadwalTes::whereIn('id_jadwal', $updatedIds)->update([
            'status_jadwal' => 'Disetujui',
            'diproses_oleh' => $ketuaId,
        ]);

        $approvedJadwals = JadwalTes::with('mahasantri')->whereIn('id_jadwal', $updatedIds)->get();
        foreach ($approvedJadwals as $approved) {
            $mhs = $approved->mahasantri;
            if ($approved->link_zoom && $mhs && $mhs->email) {
                Mail::to($mhs->email)->send(new ZoomLinkReminder($approved, $mhs));
            }
        }

        $tanggalLabel = $this->tanggalLabel(
            JadwalTes::whereIn('id_jadwal', $updatedIds)
        );
        $ketua = Panitia::findOrFail($ketuaId);

        foreach ($approvedJadwals->pluck('penanggung_jawab')->unique() as $pjId) {
            $pembuat = Panitia::find($pjId);
            if ($pembuat && $pembuat->email) {
                Mail::to($pembuat->email)->send(new JadwalApprovedNotification(
                    $tanggalLabel,
                    $ketua,
                    $approvedJadwals->where('penanggung_jawab', $pjId)->count(),
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
