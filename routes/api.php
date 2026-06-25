<?php

use App\Http\Controllers\Api\GelombangController;
use App\Http\Controllers\Api\MahasantriAuthController;
use App\Http\Controllers\Api\MahasantriProfileController;
use App\Http\Controllers\Api\MahasantriRegistrationController;
use App\Http\Controllers\Api\MahasantriStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('mahasantri')->group(function () {
    Route::post('/register', [MahasantriRegistrationController::class, 'store']);
    Route::post('/login', [MahasantriAuthController::class, 'login']);

    Route::middleware('mahasantri.token')->group(function () {
        Route::post('/logout', [MahasantriAuthController::class, 'logout']);
        Route::get('/me', [MahasantriAuthController::class, 'me']);
        Route::get('/status', [MahasantriStatusController::class, 'show']);
        Route::patch('/profile', [MahasantriProfileController::class, 'updateProfile']);
        Route::put('/orangtua', [MahasantriProfileController::class, 'replaceOrangtua']);
        Route::post('/documents', [MahasantriProfileController::class, 'uploadDocuments']);
    });
});

Route::get('/gelombang/active', [GelombangController::class, 'active']);
