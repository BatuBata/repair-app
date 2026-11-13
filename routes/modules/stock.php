<?php

use App\Livewire\Stock\Form;
use App\Livewire\Stock\Index;
use App\Livewire\Stock\Restock;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/stok', Index::class)->name('stock.index');
    Route::get('/stok/baru', Form::class)->name('stock.create');
    Route::get('/stok/{id}/edit', Form::class)->whereNumber('id')->name('stock.edit');
    Route::get('/stok/{id}/restok', Restock::class)->whereNumber('id')->name('stock.restock');
});
