<?php

use App\Livewire\Invoice\Form;
use Illuminate\Support\Facades\Route;

Route::get('/tts/{id}/rincian', Form::class)->whereNumber('id')->middleware(['auth', 'admin'])->name('invoice.edit');
