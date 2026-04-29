<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PanitiaAuthController;

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
        return view('test');
    })->name('dashboard');
});

Route::get('/preview', function () {
    return view('auth.login');
});
