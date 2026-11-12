<?php

use App\Livewire\Settings\Categories;
use App\Livewire\Settings\Store;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/pengaturan/toko', Store::class)->name('settings.store');
    Route::get('/pengaturan/kategori', Categories::class)->name('settings.categories');
});

Route::middleware('auth')->group(function () {
    Route::redirect('/settings', '/profil')->name('settings.redirect');
    Route::redirect('/settings/profile', '/profil');
    Route::redirect('/settings/security', '/profil');
});
