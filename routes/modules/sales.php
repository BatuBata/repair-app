<?php

use App\Livewire\Sales\Form;
use App\Livewire\Sales\Index;
use App\Livewire\Sales\Show;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/penjualan', Index::class)->name('sales.index');
    Route::get('/penjualan/baru', Form::class)->name('sales.create');
    Route::get('/penjualan/{id}', Show::class)->whereNumber('id')->name('sales.show');
});
