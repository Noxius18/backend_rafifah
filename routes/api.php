<?php

use App\Http\Controllers\Api\GelombangController;
use App\Http\Controllers\Api\MahasantriAuthController;
use App\Http\Controllers\Api\MahasantriPendaftaranController;
use App\Http\Controllers\Api\MahasantriRegistrationController;
use App\Http\Controllers\Api\MahasantriStatusController;
use App\Http\Controllers\Api\PanitiaApiController; // <-- IMPORT CONTROLLER BARU LU DI SINI
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

// ==============================================================================
// FIX: DAFTARKAN ROUTE API PANITIA (REVIEW BERKAS & UNGHAH SURAT KELULUSAN)
// ==============================================================================
Route::post('/panitia/berkas/{id_berkas}/review', [PanitiaApiController::class, 'reviewBerkas']);
Route::post('/panitia/mahasantri/{id_mahasantri}/unggah-kelulusan', [PanitiaApiController::class, 'unggahKelulusan']);