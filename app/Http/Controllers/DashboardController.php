<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $totalPanitia = Panitia::count();
        $isKetuaPanitia = auth()->user()?->jabatan === 'Ketua Panitia';

        // Mahasantri statistics by status
        $mahasantriByStatus = User::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Recent mahasantri (last 7 days)
        $recentMahasantriCount = User::where('tanggal_daftar', '>=', now()->subDays(7))
            ->count();

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

        $jadwalMenungguPersetujuan = [];
        if ($isKetuaPanitia) {
            $jadwalMenungguPersetujuan = JadwalTes::where('status_jadwal', 'Menunggu')
                ->select('tanggal', \DB::raw('COUNT(*) as jumlah'))
                ->groupBy('tanggal')
                ->orderBy('tanggal')
                ->get()
                ->map(function ($jadwal) {
                    return [
                        'tanggal' => $jadwal->tanggal,
                        'tanggal_label' => \Carbon\Carbon::parse($jadwal->tanggal)->isoFormat('D MMMM Y'),
                        'jumlah' => $jadwal->jumlah,
                    ];
                })
                ->toArray();
        }

        return [
            'total_mahasantri' => $totalMahasantri,
            'total_panitia' => $totalPanitia,
            'mahasantri_by_status' => $mahasantriByStatus,
            'recent_mahasantri_count' => $recentMahasantriCount,
            'beban_kerja' => $bebanKerja,
            'gelombang' => Gelombang::orderBy('id')->get(),
            'jadwal_menunggu_persetujuan' => $jadwalMenungguPersetujuan,
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
