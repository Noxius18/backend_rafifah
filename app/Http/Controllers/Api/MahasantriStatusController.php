<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\BerkasResource;
use App\Http\Resources\Api\HasilTesResource;
use App\Http\Resources\Api\JadwalTesResource;
use App\Http\Resources\Api\MahasantriResource;
use Illuminate\Http\Request;

class MahasantriStatusController extends Controller
{
    public function show(Request $request)
    {
        $mahasantri = $request->user()->load(['orangtuas', 'berkas.riwayatUnduhan']);

        $jadwal = $mahasantri->jadwalTes()
            ->with(['penanggungJawab', 'jadwalPenguji.panitia', 'hasilTes'])
            ->latest('tanggal')
            ->first();

        return response()->json([
            'data' => [
                'mahasantri' => new MahasantriResource($mahasantri),
                'berkas' => BerkasResource::collection($mahasantri->berkas),
                'jadwal' => $jadwal ? new JadwalTesResource($jadwal) : null,
                'hasil' => $jadwal?->hasilTes ? new HasilTesResource($jadwal->hasilTes) : null,
            ],
        ]);
    }
}
