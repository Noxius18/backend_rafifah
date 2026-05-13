<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Berkas;
use App\Models\Panitia;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Display dashboard with statistics
     */
    public function index()
    {
        // Cache statistics for 5 minutes to improve performance
        $stats = Cache::remember('dashboard_stats', 300, function () {
            return $this->calculateStatistics();
        });

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
        ];
    }

    /**
     * API endpoint for refreshing dashboard statistics (AJAX)
     */
    public function refresh()
    {
        // Clear cache and recalculate
        Cache::forget('dashboard_stats');
        $stats = $this->calculateStatistics();

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}