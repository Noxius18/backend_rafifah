<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Berkas;
use App\Models\Panitia;
use App\Models\Gelombang;
use App\Models\JadwalTes;
use App\Models\HasilTes;

class DashboardController extends Controller
{
    /**
     * Display dashboard with statistics
     */
    public function index()
    {
        // Langsung hitung tanpa Cache biar selalu Real-Time
        $stats = $this->calculateStatistics();

        return view('dashboard.dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * Calculate all dashboard statistics
     */
    private function calculateStatistics(): array
    {
        $totalMahasantri = User::count();
        $totalBerkas = Berkas::count();
        $totalPanitia = Panitia::count();

        // Mahasantri statistics by status
        $mahasantriByStatus = User::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Berkas statistics by download status
        $berkasByStatus = Berkas::selectRaw('download_status, COUNT(*) as count')
            ->groupBy('download_status')
            ->get()
            ->pluck('count', 'download_status')
            ->toArray();

        // Recent mahasantri (last 7 days)
        $recentMahasantriCount = User::where('tanggal_daftar', '>=', now()->subDays(7))
            ->count();

        // Berkas success rate
        $successfulBerkas = $berkasByStatus['success'] ?? 0;
        $berkasSuccessRate = $totalBerkas > 0 
            ? round(($successfulBerkas / $totalBerkas) * 100, 1)
            : 0;

        // Statistik beban kerja per panitia (berdasarkan penguji per aspek)
        $bebanKerja = Panitia::where('jabatan', 'Panitia')
            ->get()
            ->map(function ($pj) {
            $jadwalIds = \App\Models\JadwalPenguji::where('id_panitia', $pj->id_panitia)
                ->whereHas('jadwalTes', function ($q) {
                    $q->whereIn('status_jadwal', ['Disetujui', 'Aktif']);
                })
                ->pluck('id_jadwal');

                $totalTugas = $jadwalIds->count();

                $sudahDinilai = HasilTes::whereIn('id_jadwal', $jadwalIds)
                    ->whereIn('status', ['Lulus', 'Tidak Lulus'])
                    ->count();

                return [
                    'id_panitia'    => $pj->id_panitia,
                    'nama_lengkap'  => $pj->nama_lengkap,
                    'total_tugas'   => $totalTugas,
                    'sudah_dinilai' => $sudahDinilai,
                ];
            })
            ->sortByDesc('total_tugas')
            ->values()
            ->toArray();

        // Statistik per gelombang: kuota vs terdaftar
        $gelombangStats = Gelombang::orderBy('id')->get()->map(function ($g) {
            $prefix = date('y', strtotime($g->start_date)) . str_pad($g->id, 2, '0', STR_PAD_LEFT);
            $terdaftar = User::where('id_mahasantri', 'LIKE', $prefix . '%')->count();
            return [
                'nama'      => $g->nama,
                'periode'   => \Carbon\Carbon::parse($g->start_date)->isoFormat('D MMMM Y') . ' - ' . \Carbon\Carbon::parse($g->end_date)->isoFormat('D MMMM Y'),
                'kuota'     => $g->kuota,
                'terdaftar' => $terdaftar,
                'sisa_kuota' => max(0, $g->kuota - $terdaftar),
            ];
        })->toArray();

        return [
            'total_mahasantri' => $totalMahasantri,
            'total_berkas' => $totalBerkas,
            'total_panitia' => $totalPanitia,
            'mahasantri_by_status' => $mahasantriByStatus,
            'berkas_by_status' => $berkasByStatus,
            'recent_mahasantri_count' => $recentMahasantriCount,
            'berkas_success_rate' => $berkasSuccessRate,
            'successful_berkas' => $successfulBerkas,
            'failed_berkas' => $berkasByStatus['error'] ?? 0,
            'pending_berkas' => $berkasByStatus['pending'] ?? 0,
            'gelombang' => Gelombang::orderBy('id')->get(),
            'beban_kerja' => $bebanKerja,
            'gelombang_stats' => $gelombangStats,
        ];
    }

    /**
     * API endpoint for refreshing dashboard statistics (AJAX)
     */
    public function refresh()
    {
        $stats = $this->calculateStatistics();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}