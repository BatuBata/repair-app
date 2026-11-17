<?php

use App\Http\Controllers\Platform\PlatformController;
use Illuminate\Support\Facades\Route;

Route::prefix('platform')->name('platform.')->group(function () {
    Route::get('/login', [PlatformController::class, 'loginForm'])->name('login');
    Route::post('/login', [PlatformController::class, 'login'])->name('login.store');

    Route::middleware('platform.owner')->group(function () {
        Route::get('/', [PlatformController::class, 'index'])->name('index');
        Route::post('/logout', [PlatformController::class, 'logout'])->name('logout');
        Route::post('/tenants', [PlatformController::class, 'store'])->name('tenants.store');
        Route::patch('/tenants/{tenant}/status', [PlatformController::class, 'toggle'])->name('tenants.toggle');
    });
});
