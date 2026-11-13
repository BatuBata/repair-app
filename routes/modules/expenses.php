<?php

use App\Livewire\Expenses\Index;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->get('/pengeluaran', Index::class)->name('expenses.index');
