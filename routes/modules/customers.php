<?php

use App\Livewire\Customers\Index;
use App\Livewire\Customers\Show;
use Illuminate\Support\Facades\Route;

Route::get('/pelanggan', Index::class)->middleware(['auth', 'admin'])->name('customers.index');
Route::get('/pelanggan/{id}', Show::class)->middleware(['auth', 'admin'])->whereNumber('id')->name('customers.show');
