<?php

use App\Livewire\Profile\ChangePassword;
use Illuminate\Support\Facades\Route;

Route::get('/profil', ChangePassword::class)->middleware('auth')->name('profile.edit');
