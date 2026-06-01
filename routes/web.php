<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PanitiaAuthController;
use App\Http\Controllers\PanitiaController;
use App\Http\Controllers\MahasantriController;
use App\Http\Controllers\JadwalTesController;
use App\Http\Controllers\HasilTesController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GelombangController;

Route::middleware('guest')->group(function () {
    // / redirect ke halaman login
    Route::get('/', function () {
        return redirect()->route('login');
    });
    
    // Login routes
    Route::get('/login', [PanitiaAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PanitiaAuthController::class, 'login']);
});

// ──────────────────────────────────────────────
// GRUP PANITIA – operasional (create, update, delete)
// ──────────────────────────────────────────────
Route::middleware(['auth:panitia', 'cek_jabatan:Panitia'])->group(function () {
    // Mahasantri – write ops
    Route::post('/mahasantri', [MahasantriController::class, 'store'])->name('mahasantri.store');
    Route::put('/mahasantri/{mahasantri}', [MahasantriController::class, 'update'])->name('mahasantri.update');
    Route::delete('/mahasantri/{mahasantri}', [MahasantriController::class, 'destroy'])->name('mahasantri.destroy');
    Route::post('/mahasantri/import', [MahasantriController::class, 'processImport'])->name('mahasantri.import');
    Route::post('/mahasantri/{mahasantri}/verifikasi', [MahasantriController::class, 'verifikasi'])->name('mahasantri.verifikasi');

    // Seleksi (Jadwal Tes) – write ops
    Route::post('/seleksi', [JadwalTesController::class, 'store'])->name('seleksi.store');
    Route::put('/seleksi/{jadwalTes}', [JadwalTesController::class, 'update'])->name('seleksi.update');
    Route::delete('/seleksi/{jadwalTes}', [JadwalTesController::class, 'destroy'])->name('seleksi.destroy');
    Route::post('/seleksi/update-link-zoom', [JadwalTesController::class, 'updateLinkZoomMassal'])->name('seleksi.update-link-zoom');

    // Hasil Tes – input nilai (hanya Panitia)
    Route::post('/hasil-tes', [HasilTesController::class, 'store'])->name('hasil-tes.store');

    // Berkas – update status, retry download & data mahasantri
    Route::patch('/berkas/{berkas}', [MahasantriController::class, 'updateBerkas'])->name('berkas.update');
    Route::post('/berkas/{berkas}/retry-download', [MahasantriController::class, 'retryDownload'])->name('berkas.retry-download');
});

// ──────────────────────────────────────────────
// GRUP BERSAMA – view-only (Panitia & Pengawas)
// ──────────────────────────────────────────────
Route::middleware(['auth:panitia', 'cek_jabatan:Panitia,Pengawas'])->group(function () {
    Route::post('/logout', [PanitiaAuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');

    // Mahasantri – view & form
    Route::get('/mahasantri', [MahasantriController::class, 'index'])->name('mahasantri.index');
    Route::get('/mahasantri/{mahasantri}', [MahasantriController::class, 'show'])->name('mahasantri.show');
    Route::get('/mahasantri/{mahasantri}/edit', [MahasantriController::class, 'edit'])->name('mahasantri.edit');
    Route::get('/mahasantri/create', [MahasantriController::class, 'create'])->name('mahasantri.create');
    Route::get('/mahasantri/import', [MahasantriController::class, 'import'])->name('mahasantri.import.form');

    // Berkas
    Route::get('/berkas/{berkas}/download', [MahasantriController::class, 'downloadBerkas'])->name('berkas.download');
    Route::get('/berkas/{berkas}/preview', [MahasantriController::class, 'previewBerkas'])->name('berkas.preview');
    Route::get('/mahasantri/{mahasantri}/cetak-pdf', [MahasantriController::class, 'cetakPdf'])->name('mahasantri.cetak-pdf');

    // Seleksi (Jadwal Tes) – view only
    Route::get('/seleksi', [JadwalTesController::class, 'index'])->name('seleksi.index');
    Route::get('/seleksi/{jadwalTes}/edit', [JadwalTesController::class, 'edit'])->name('seleksi.edit');

    // Hasil Tes – view nilai per jadwal
    Route::get('/seleksi/{jadwalTes}/nilai', [HasilTesController::class, 'index'])->name('seleksi.nilai');
});

// ──────────────────────────────────────────────
// GRUP PENGAWAS – supervisi & review
// ──────────────────────────────────────────────
Route::middleware(['auth:panitia', 'cek_jabatan:Pengawas'])->group(function () {
    // Panitia Management (full CRUD)
    Route::resource('panitia', PanitiaController::class)->parameters(['panitia' => 'panitia']);

    // Hasil Tes – review pertimbangan
    Route::post('/hasil-tes/{hasilTes}/review', [HasilTesController::class, 'review'])->name('hasil-tes.review');

    // Laporan
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/cetak-nilai', [LaporanController::class, 'cetakNilai'])->name('cetak-nilai');
        Route::get('/cetak-overall', [LaporanController::class, 'cetakOverall'])->name('cetak-overall');
    });

    // Pengaturan Gelombang – hanya untuk Pengawas
    Route::get('/pengaturan-gelombang', [GelombangController::class, 'index'])->name('gelombang.index');
    Route::put('/pengaturan-gelombang/{gelombang}', [GelombangController::class, 'update'])->name('gelombang.update');
});