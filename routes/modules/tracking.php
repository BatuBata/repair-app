<?php

use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::get('/t/{token}', [TrackingController::class, 'show'])->name('tracking.show');
Route::get('/tts/{id}/whatsapp', [TrackingController::class, 'whatsapp'])->whereNumber('id')->middleware(['auth', 'admin'])->name('tts.whatsapp');
