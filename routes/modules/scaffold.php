<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::redirect('/dashboard', '/')->name('dashboard');
});
