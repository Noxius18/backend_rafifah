<?php

use App\Http\Controllers\Api\GelombangController;
use App\Http\Controllers\Api\MahasantriAuthController;
use App\Http\Controllers\Api\MahasantriPendaftaranController;
use App\Http\Controllers\Api\MahasantriRegistrationController;
use App\Http\Controllers\Api\MahasantriStatusController;
use App\Http\Controllers\Api\PanitiaApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('mahasantri')->group(function () {
    Route::post('/register', [MahasantriRegistrationController::class, 'store']);
    Route::post('/login', [MahasantriAuthController::class, 'login']);

    Route::middleware('mahasantri.token')->group(function () {
        Route::post('/logout', [MahasantriAuthController::class, 'logout']);
        Route::get('/me', [MahasantriAuthController::class, 'me']);
        Route::get('/status', [MahasantriStatusController::class, 'show']);
        Route::post('/pendaftaran/submit', [MahasantriPendaftaranController::class, 'submit']);
    });
});

Route::get('/gelombang/active', [GelombangController::class, 'active']);

Route::middleware(['web', 'auth:panitia', 'cek_jabatan:Panitia,Ketua Panitia'])->group(function () {
    Route::post('/panitia/berkas/{berkas}/review', [PanitiaApiController::class, 'reviewBerkas']);
    Route::post('/panitia/mahasantri/{id_mahasantri}/unggah-kelulusan', [PanitiaApiController::class, 'unggahKelulusan']);
});
