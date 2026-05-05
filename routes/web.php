<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PanitiaAuthController;
use App\Http\Controllers\PanitiaController;

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
});

Route::get('/preview', function () {
    return view('menu.panitia');
});
