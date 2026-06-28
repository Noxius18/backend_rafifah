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
        $stats = $this->calculateStatistics();

        return view('dashboard.dashboard', [
            'stats' => $stats,
        ]);
    }

    /**
     * Calculate all dashboard statistics
     */
   /**
     * Calculate all dashboard statistics
     */
    private function calculateStatistics(): array
    {
        $totalMahasantri = User::count();
        $totalPanitia = Panitia::count();
        $isKetuaPanitia = auth()->user()?->jabatan === 'Ketua Panitia';

        // 1. Hitung status kelulusan & pertimbangan dari tabel hasil_tes secara akurat
        $hasilStats = HasilTes::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $countLulus = $hasilStats['Lulus'] ?? 0;
        $countTidakLulus = $hasilStats['Tidak Lulus'] ?? 0;
        $countPertimbangan = $hasilStats['Pertimbangan'] ?? 0; // <-- SEKARANG COUTER INI DIJAMIN AKURAT!

        // 2. Hitung status pendaftaran awal murni dari tabel mahasantri
        $countBaru = User::where('status', 'Pendaftar Baru')->count();
        $countTerverifikasi = User::where('status', 'Terverifikasi')->count();

        // Recent mahasantri (last 7 days)
        $recentMahasantriCount = User::where('tanggal_daftar', '>=', now()->subDays(7))
            ->count();

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
            'recent_mahasantri_count' => $recentMahasantriCount,
            'lulus_count' => $countLulus,
            'tidak_lulus_count' => $countTidakLulus,
            'pertimbangan_count' => $countPertimbangan,
            'baru_count' => $countBaru,
            'terverif_count' => $countTerverifikasi,
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