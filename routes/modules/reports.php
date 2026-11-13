<?php

use App\Livewire\Reports\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->get('/laporan', Index::class)->name('reports.index');
