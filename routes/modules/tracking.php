<?php

use App\Http\Controllers\TrackingController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsureTenant;
use Illuminate\Support\Facades\Route;

Route::get('/t/{token}', [TrackingController::class, 'show'])->withoutMiddleware([EnsureActiveAccount::class, EnsureTenant::class])->name('tracking.show');
Route::get('/tts/{id}/whatsapp', [TrackingController::class, 'whatsapp'])->whereNumber('id')->middleware(['auth', 'admin'])->name('tts.whatsapp');
