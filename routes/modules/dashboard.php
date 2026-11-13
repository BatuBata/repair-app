<?php

use App\Livewire\Dashboard\Index;
use Illuminate\Support\Facades\Route;

Route::get('/', Index::class)->middleware(['auth', 'admin'])->name('home');
