<?php

use App\Livewire\Tts\Detail;
use App\Livewire\Tts\Form;
use App\Livewire\Tts\Index;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/tts', Index::class)->name('tts.index');
    Route::get('/tts/baru', Form::class)->name('tts.create');
    Route::get('/tts/{id}/edit', Form::class)->whereNumber('id')->name('tts.edit');
    Route::get('/tts/{id}/sukses', Detail::class)->whereNumber('id')->name('tts.success');
    Route::get('/tts/{id}', Detail::class)->whereNumber('id')->name('tts.show');
});
