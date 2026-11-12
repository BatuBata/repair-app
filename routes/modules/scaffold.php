<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::view('/', 'dashboard')->name('home');
    Route::redirect('/dashboard', '/')->name('dashboard');
});
