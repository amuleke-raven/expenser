<?php

use Illuminate\Support\Facades\Route;

Route::impersonate();

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('filament.staff.pages.dashboard');
    }

    return redirect()->route('filament.staff.auth.login');
});
