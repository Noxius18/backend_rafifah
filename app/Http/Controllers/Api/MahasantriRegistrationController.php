<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\MahasantriResource;
use App\Models\Gelombang;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MahasantriRegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_lengkap' => 'required|string|max:35',
            'email' => 'required|email|max:100|unique:mahasantri,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $tanggalDaftar = now();
        $gelombang = $this->activeGelombang($tanggalDaftar);

        if (!$gelombang) {
            throw ValidationException::withMessages([
                'gelombang' => ['Tidak ada gelombang aktif untuk tanggal pendaftaran saat ini.'],
            ]);
        }

        $mahasantri = User::create([
            'id_mahasantri' => User::generateId($tanggalDaftar->format('Y'), $gelombang->id),
            'nama_lengkap' => $validated['nama_lengkap'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'status' => 'Pendaftar Baru',
            'tanggal_daftar' => $tanggalDaftar,
        ]);

        return (new MahasantriResource($mahasantri->load(['orangtuas', 'berkas.riwayatUnduhan'])))
            ->additional(['message' => 'Pendaftaran berhasil.'])
            ->response()
            ->setStatusCode(201);
    }

    private function activeGelombang(Carbon $date): ?Gelombang
    {
        return Gelombang::whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->orderBy('start_date')
            ->first();
    }
}
