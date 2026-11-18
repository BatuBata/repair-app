<?php

use App\Livewire\Customers\Index;
use Illuminate\Support\Facades\Route;

Route::get('/pelanggan', Index::class)->middleware(['auth', 'admin'])->name('customers.index');
