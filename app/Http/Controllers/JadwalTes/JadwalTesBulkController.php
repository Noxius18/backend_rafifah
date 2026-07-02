<?php

namespace App\Http\Controllers\JadwalTes;

use App\Http\Controllers\Controller;
use App\Models\JadwalTes;
use App\Services\JadwalTes\JadwalTesService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Menangani operasi jadwal yang sifatnya massal atau operasional,
 * seperti update per tanggal, notifikasi, dan kirim hasil batch.
 */
class JadwalTesBulkController extends Controller
{
    public function __construct(private readonly JadwalTesService $jadwalTesService)
    {
    }

    public function updateLinkZoomMassal(Request $request)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403);

        $request->validate([
            'tanggal' => 'required|date',
            'link_zoom' => 'required|string',
        ]);

        $updated = $this->jadwalTesService->updateLinkZoomMassal($request->tanggal, $request->link_zoom);

        return redirect()->route('seleksi.index')->with('success', "Link Zoom berhasil diperbarui untuk {$updated} jadwal.");
    }

    public function updateByDate(Request $request, string $tanggal)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403);

        $validated = $request->validate([
            'tanggal_baru' => 'required|date',
            'jam_mulai' => 'required|date_format:H:i',
            'interval' => 'required|integer|min:5|max:120',
            'link_zoom' => 'nullable|string',
            'penguji_bacaan_al_quran' => 'nullable|exists:panitia,id_panitia',
            'penguji_tajwid_tahsin' => 'nullable|exists:panitia,id_panitia',
            'penguji_hafalan' => 'nullable|exists:panitia,id_panitia',
            'penguji_wawancara' => 'nullable|exists:panitia,id_panitia',
        ]);

        try {
            $result = $this->jadwalTesService->updateByDate($tanggal, $validated, auth()->user()->id_panitia);
        } catch (\RuntimeException $e) {
            return redirect()->route('seleksi.index')->with('error', $e->getMessage());
        }

        return redirect()->route('seleksi.index')
            ->with('success', $result['count'] . ' jadwal berhasil diperbarui ke tanggal ' . $result['formatted_date'] . '.');
    }

    public function sendUpdateNotification(Request $request, JadwalTes $jadwalTes)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403);

        if ($jadwalTes->status_jadwal !== 'Disetujui') {
            return redirect()->back()->with('error', 'Jadwal belum disetujui, notifikasi tidak dapat dikirim.');
        }

        $mahasantri = $jadwalTes->mahasantri;
        if (!$mahasantri || !$mahasantri->email) {
            return redirect()->back()->with('error', 'Mahasiswa tidak memiliki email');
        }

        $this->jadwalTesService->sendUpdateNotification($jadwalTes);

        return redirect()->back()->with('success', 'Notifikasi update terkirim ke ' . $mahasantri->nama_lengkap);
    }

    public function bulkCancel(Request $request)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403);

        $request->validate([
            'jenis_pembatalan' => 'required|in:Dibatalkan,Rescheduled',
            'alasan_pembatalan' => 'required|string',
        ]);

        $updated = $this->jadwalTesService->bulkCancel($request->jenis_pembatalan, $request->alasan_pembatalan);

        return redirect()->route('seleksi.index')->with('success', "{$updated} jadwal berhasil di" . strtolower($request->jenis_pembatalan));
    }

    public function sendBulkResults(Request $request)
    {
        abort_unless(auth()->user()->jabatan === 'Panitia', 403, 'Hanya panitia yang bisa kirim hasil test');

        $request->validate([
            'tanggal_kirim' => 'required|date',
            'jam_kirim' => 'required',
        ]);

        try {
            $result = $this->jadwalTesService->sendBulkResults(
                Carbon::parse($request->tanggal_kirim . ' ' . $request->jam_kirim)
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($result['sent_count'] === 0) {
            return back()->with(
                'error',
                "Gagal menjadwalkan. Pastikan nilai mahasantri pada {$result['gelombang_nama']} sudah direview (Lulus/Tidak Lulus) semua."
            );
        }

        return back()->with(
            'success',
            "Berhasil! {$result['sent_count']} email kelulusan dijadwalkan untuk {$result['gelombang_nama']} dan akan meluncur pada {$result['waktu_kirim']->format('d/m/Y H:i')}."
        );
    }
}
