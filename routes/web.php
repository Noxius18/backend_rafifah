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

// Route untuk semua jabatan (Panitia dan Pengawas)
Route::middleware(['auth:panitia', 'cek_jabatan:Panitia,Pengawas'])->group(function () {
    Route::post('/logout', [PanitiaAuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function () {
        return view('dashboard.dashboard');
    })->name('dashboard');

    // Mahasantri View (untuk Panitia dan Pengawas)
    Route::get('/mahasantri', [MahasantriController::class, 'index'])->name('mahasantri.index');
    Route::get('/mahasantri/{mahasantri}', [MahasantriController::class, 'show'])->name('mahasantri.show');
    Route::get('/mahasantri/{mahasantri}/edit', [MahasantriController::class, 'edit'])->name('mahasantri.edit');

    // Berkas View (untuk Panitia dan Pengawas)
    Route::get('/berkas/{berkas}/download', [MahasantriController::class, 'downloadBerkas'])->name('berkas.download');
    Route::get('/berkas/{berkas}/preview', [MahasantriController::class, 'previewBerkas'])->name('berkas.preview');
});

// Route khusus Pengawas (manajemen panitia, create, update, delete)
Route::middleware(['auth:panitia', 'cek_jabatan:Pengawas'])->group(function () {
    // Panitia Management Routes (hanya Pengawas)
    Route::resource('panitia', PanitiaController::class)->parameters(['panitia' => 'panitia']);

    // Mahasantri Management (Create, Update, Delete)
    Route::get('/mahasantri/create', [MahasantriController::class, 'create'])->name('mahasantri.create');
    Route::post('/mahasantri', [MahasantriController::class, 'store'])->name('mahasantri.store');
    Route::put('/mahasantri/{mahasantri}', [MahasantriController::class, 'update'])->name('mahasantri.update');
    Route::delete('/mahasantri/{mahasantri}', [MahasantriController::class, 'destroy'])->name('mahasantri.destroy');
    Route::get('/mahasantri/import', [MahasantriController::class, 'import'])->name('mahasantri.import.form');
    Route::post('/mahasantri/import', [MahasantriController::class, 'processImport'])->name('mahasantri.import');

    // Berkas Management
    Route::patch('/berkas/{berkas}', [MahasantriController::class, 'updateBerkas'])->name('berkas.update');
    Route::post('/berkas/{berkas}/retry-download', [MahasantriController::class, 'retryDownload'])->name('berkas.retry-download');
});