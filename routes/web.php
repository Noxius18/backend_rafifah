<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PanitiaAuthController;
use App\Http\Controllers\PanitiaController;
use App\Http\Controllers\MahasantriController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [PanitiaAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PanitiaAuthController::class, 'login']);
});

Route::middleware('auth:panitia')->group(function () {
    Route::post('/logout', [PanitiaAuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function () {
        return view('dashboard.dashboard');
    })->name('dashboard');
    
    // Panitia Management Routes
    Route::resource('panitia', PanitiaController::class)->parameters(['panitia' => 'panitia']);

    // Mahasantri Management Routes
    Route::resource('mahasantri', MahasantriController::class);
    Route::get('/mahasantri/import', [MahasantriController::class, 'import'])->name('mahasantri.import.form');
    Route::post('/mahasantri/import', [MahasantriController::class, 'processImport'])->name('mahasantri.import');

    // Berkas Management Routes
    Route::patch('/berkas/{berkas}', [MahasantriController::class, 'updateBerkas'])->name('berkas.update');
    Route::post('/berkas/{berkas}/retry-download', [MahasantriController::class, 'retryDownload'])->name('berkas.retry-download');
    Route::get('/berkas/{berkas}/download', [MahasantriController::class, 'downloadBerkas'])->name('berkas.download');
});

Route::get('/preview', function () {
    return view('menu.panitia');
});
