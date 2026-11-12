<?php

use Illuminate\Support\Facades\Route;

Route::get('/layout-preview', function () {
    abort_unless(app()->environment('local', 'testing'), 404);

    return view('pages.layout-preview');
});
