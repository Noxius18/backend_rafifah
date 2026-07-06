<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PanitiaAuthController;
use App\Http\Controllers\PanitiaController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GelombangController;
use App\Http\Controllers\HasilTes\HasilTesInputController;
use App\Http\Controllers\HasilTes\HasilTesReviewController;
use App\Http\Controllers\JadwalTes\JadwalTesApprovalController;
use App\Http\Controllers\JadwalTes\JadwalTesBulkController;
use App\Http\Controllers\JadwalTes\JadwalTesCrudController;
use App\Http\Controllers\Mahasantri\MahasantriBerkasController;
use App\Http\Controllers\Mahasantri\MahasantriCrudController;
use App\Http\Controllers\Mahasantri\MahasantriDocumentController;
use App\Http\Controllers\Mahasantri\MahasantriImportController;
use App\Http\Controllers\Mahasantri\MahasantriWorkflowController;
use App\Http\Controllers\Api\PanitiaApiController;

Route::middleware('guest')->group(function () {
    // redirect ke halaman login
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
    Route::post('/mahasantri', [MahasantriCrudController::class, 'store'])->name('mahasantri.store');
    Route::put('/mahasantri/{mahasantri}', [MahasantriCrudController::class, 'update'])->name('mahasantri.update');
    Route::delete('/mahasantri/{mahasantri}', [MahasantriCrudController::class, 'destroy'])->name('mahasantri.destroy');
    Route::post('/mahasantri/import', [MahasantriImportController::class, 'processImport'])->name('mahasantri.import');
    Route::post('/mahasantri/{mahasantri}/verifikasi', [MahasantriWorkflowController::class, 'verifikasi'])->name('mahasantri.verifikasi');

    // Seleksi (Jadwal Tes) – write ops
    Route::post('/seleksi', [JadwalTesCrudController::class, 'store'])->name('seleksi.store');
    Route::put('/seleksi/{jadwalTes}', [JadwalTesCrudController::class, 'update'])->name('seleksi.update');
    Route::delete('/seleksi/{jadwalTes}', [JadwalTesCrudController::class, 'destroy'])->name('seleksi.destroy');
    Route::post('/seleksi/update-link-zoom', [JadwalTesBulkController::class, 'updateLinkZoomMassal'])->name('seleksi.update-link-zoom');

    // 👇 INI DIA ROUTENYA (Sekarang sudah aman terlindungi middleware Panitia)
    Route::post('/seleksi/send-bulk-results', [JadwalTesBulkController::class, 'sendBulkResults'])->name('seleksi.send-bulk-results');

    Route::post('/seleksi/{jadwalTes}/notify-update', [JadwalTesBulkController::class, 'sendUpdateNotification'])->name('seleksi.notify-update');

    // Bulk cancel (Panitia)
    Route::post('/seleksi/bulk-cancel', [JadwalTesBulkController::class, 'bulkCancel'])->name('seleksi.bulk-cancel');

    // Edit jadwal per tanggal (Panitia — saat status Revisi)
    Route::post('/seleksi/update-by-date/{tanggal}', [JadwalTesBulkController::class, 'updateByDate'])->name('seleksi.update-by-date');

    // Hasil Tes – input nilai (hanya Panitia)
    Route::post('/hasil-tes', [HasilTesInputController::class, 'store'])->name('hasil-tes.store');
    Route::post('/hasil-tes/preview-hasil', [HasilTesInputController::class, 'previewHasil'])->name('hasil-tes.preview-hasil');
    Route::post('/hasil-tes/simpan-hasil', [HasilTesInputController::class, 'simpanHasil'])->name('hasil-tes.simpan-hasil');

    // Berkas – update status, retry download & data mahasantri
    Route::patch('/berkas/{berkas}', [MahasantriBerkasController::class, 'updateBerkas'])->name('berkas.update');
    Route::post('/berkas/{berkas}/retry-download', [MahasantriBerkasController::class, 'retryDownload'])->name('berkas.retry-download');
});

// ──────────────────────────────────────────────
// GRUP BERSAMA – view-only (Panitia & Ketua Panitia)
// ──────────────────────────────────────────────
Route::middleware(['auth:panitia', 'cek_jabatan:Panitia,Ketua Panitia'])->group(function () {
    Route::post('/logout', [PanitiaAuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/refresh', [DashboardController::class, 'refresh'])->name('dashboard.refresh');

    // Mahasantri – view & form
    Route::get('/mahasantri/create', [MahasantriCrudController::class, 'create'])->name('mahasantri.create');
    Route::get('/mahasantri/import', [MahasantriImportController::class, 'import'])->name('mahasantri.import.form');
    Route::get('/mahasantri', [MahasantriCrudController::class, 'index'])->name('mahasantri.index');
    Route::get('/mahasantri/{mahasantri}/edit', [MahasantriCrudController::class, 'edit'])->name('mahasantri.edit');
    Route::get('/mahasantri/{mahasantri}', [MahasantriCrudController::class, 'show'])->name('mahasantri.show');

    // Berkas
    Route::get('/berkas/{berkas}/download', [MahasantriBerkasController::class, 'downloadBerkas'])->name('berkas.download');
    Route::get('/berkas/{berkas}/preview', [MahasantriBerkasController::class, 'previewBerkas'])->name('berkas.preview');
    Route::get('/mahasantri/{mahasantri}/cetak-pdf', [MahasantriDocumentController::class, 'cetakPdf'])->name('mahasantri.cetak-pdf');

    // Seleksi (Jadwal Tes) – view only
    Route::get('/seleksi/{jadwalTes}/edit', [JadwalTesCrudController::class, 'edit'])->name('seleksi.edit');
    Route::get('/seleksi', [JadwalTesCrudController::class, 'index'])->name('seleksi.index');

    // Hasil Tes – view nilai per jadwal
    Route::get('/seleksi/{jadwalTes}/nilai', [HasilTesInputController::class, 'index'])->name('seleksi.nilai');

    // Route API untuk Review Berkas & Unggah Kelulusan Panitia
    Route::post('/panitia/berkas/{berkas}/review', [PanitiaApiController::class, 'reviewBerkas'])->name('panitia.berkas.review');
    Route::post('/panitia/mahasantri/{id_mahasantri}/unggah-kelulusan', [PanitiaApiController::class, 'unggahKelulusan'])->name('panitia.mahasantri.unggah-kelulusan');

    // Laporan – cetak PDF (Panitia & Ketua Panitia)
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/cetak-nilai', [LaporanController::class, 'cetakNilai'])->name('cetak-nilai');
        Route::get('/cetak-overall', [LaporanController::class, 'cetakOverall'])->name('cetak-overall');
    });
    Route::get('/cetak-semua-mahasantri', [LaporanController::class, 'cetakSemuaMahasantri'])->name('cetak-semua-mahasantri');

    Route::get('/seleksi/gelombang/{id}/cetak-laporan', [LaporanController::class, 'cetakLaporanPanitia'])
        ->name('laporan.panitia.seleksi');

});

// ──────────────────────────────────────────────
// GRUP PENGAWAS – supervisi & review
// ──────────────────────────────────────────────
Route::middleware(['auth:panitia', 'cek_jabatan:Ketua Panitia'])->group(function () {
    // Panitia Management (full CRUD)
    Route::get('/panitia/cetak-pdf', [PanitiaController::class, 'cetakPdf'])->name('panitia.cetak-pdf');
    Route::resource('panitia', PanitiaController::class)->parameters(['panitia' => 'panitia']);

    // Hasil Tes – review pertimbangan
    Route::post('/hasil-tes/{hasilTes}/review', [HasilTesReviewController::class, 'review'])->name('hasil-tes.review');

    // Digital Handshake: Ketua Panitia approve/reject jadwal
    Route::post('/seleksi/{jadwalTes}/approve', [JadwalTesApprovalController::class, 'approve'])->name('seleksi.approve');
    Route::post('/seleksi/{jadwalTes}/reject', [JadwalTesApprovalController::class, 'reject'])->name('seleksi.reject');

    // Bulk approve/reject (Ketua Panitia)
    Route::post('/seleksi/approve-all', [JadwalTesApprovalController::class, 'approveAll'])->name('seleksi.approve-all');
    Route::post('/seleksi/reject-all', [JadwalTesApprovalController::class, 'rejectAll'])->name('seleksi.reject-all');

    // Pengaturan Gelombang – hanya untuk Ketua Panitia
    Route::get('/pengaturan-gelombang', [GelombangController::class, 'index'])->name('gelombang.index');
    Route::put('/pengaturan-gelombang/{gelombang}', [GelombangController::class, 'update'])->name('gelombang.update');

    // Hapus massal mahasantri – hanya Ketua Panitia
    Route::post('/mahasantri/hapus/semua', [MahasantriWorkflowController::class, 'destroyByTahunAjaran'])->name('mahasantri.destroy-by-tahun-ajaran');
});

Route::get('/clear-config', function () {
    Artisan::call('config:clear');
    Artisan::call('config:cache');
    return "Konfigurasi .env berhasil diperbarui di server!";
});
