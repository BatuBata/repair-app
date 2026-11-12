<?php

use App\Livewire\Users\Index;
use Illuminate\Support\Facades\Route;

Route::get('/pengaturan/pengguna', Index::class)->middleware(['auth', 'admin'])->name('users.index');
