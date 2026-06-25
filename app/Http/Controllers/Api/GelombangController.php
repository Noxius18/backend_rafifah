<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\GelombangResource;
use App\Models\Gelombang;
use Carbon\Carbon;

class GelombangController extends Controller
{
    public function active()
    {
        $today = Carbon::today();
        $gelombang = Gelombang::whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('start_date')
            ->first();

        if (!$gelombang) {
            return response()->json([
                'message' => 'Tidak ada gelombang aktif.',
                'data' => null,
            ], 404);
        }

        return new GelombangResource($gelombang);
    }
}
