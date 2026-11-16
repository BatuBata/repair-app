<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/layout-preview', function () {
    abort_unless(app()->environment('local', 'testing'), 404);

    $technician = request('role') === 'teknisi';
    $user = new User;
    $user->forceFill(['name' => $technician ? 'Rizky' : 'Pak Arif', 'role' => $technician ? 'teknisi' : 'admin']);
    $previousUser = Auth::user();
    Auth::setUser($user);

    $paths = ['servis' => '/tts/123', 'buat' => '/tts/baru', 'menu' => '/stok', 'profil' => '/profil'];

    try {
        return response()->view('pages.layout-preview', ['navigationPath' => $paths[request('page')] ?? '/tts']);
    } finally {
        $previousUser ? Auth::setUser($previousUser) : Auth::forgetUser();
    }
});
