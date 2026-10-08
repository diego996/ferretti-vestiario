<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Middleware\EnsureUserIsActive;
use App\Livewire\Auth\Login;
use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::post('/logout', LogoutController::class)->name('logout');
});
