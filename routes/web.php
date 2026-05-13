<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PanitiaAuthController;
use App\Http\Controllers\PanitiaController;
use App\Http\Controllers\MahasantriController;
use App\Http\Controllers\JadwalTesController;
use App\Http\Controllers\HasilTesController;
use App\Http\Controllers\LaporanController;

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
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/refresh', [\App\Http\Controllers\DashboardController::class, 'refresh'])->name('dashboard.refresh');
    
    // Panitia Management Routes
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

    // Jadwal Tes Routes
    Route::resource('jadwal-tes', JadwalTesController::class);

    // Hasil Tes Routes (input nilai)
    Route::get('/jadwal-tes/{jadwalTes}/nilai', [HasilTesController::class, 'index'])->name('jadwal-tes.nilai');
    Route::post('/hasil-tes', [HasilTesController::class, 'store'])->name('hasil-tes.store');
    Route::post('/hasil-tes/{hasilTes}/review', [HasilTesController::class, 'review'])->name('hasil-tes.review');

    // Laporan Routes (hanya Pengawas)
    Route::middleware('role:pengawas')->prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/cetak-nilai', [LaporanController::class, 'cetakNilai'])->name('cetak-nilai');
        Route::get('/cetak-overall', [LaporanController::class, 'cetakOverall'])->name('cetak-overall');
    });

    // Cetak PDF per mahasantri (Panitia & Pengawas)
    Route::get('/mahasantri/{mahasantri}/cetak-pdf', [MahasantriController::class, 'cetakPdf'])->name('mahasantri.cetak-pdf');
});

Route::get('/preview', function () {
    return view('menu.panitia');
});