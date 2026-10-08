<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Middleware\EnsureUserIsActive;
use App\Livewire\Auth\Login;
use App\Livewire\Carichi\NuovoCarico;
use App\Livewire\Catalogo\Categorie;
use App\Livewire\Catalogo\FormIndumento;
use App\Livewire\Catalogo\Index as Catalogo;
use App\Livewire\Colleghi\Index as Colleghi;
use App\Livewire\Commesse\Index as Commesse;
use App\Livewire\Dashboard;
use App\Livewire\Magazzino\Index as Magazzino;
use App\Livewire\Movimenti\Index as Movimenti;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/magazzino', Magazzino::class)->name('magazzino.index');
    Route::get('/carichi/nuovo', NuovoCarico::class)->name('carichi.create');
    Route::get('/movimenti', Movimenti::class)->name('movimenti.index');

    Route::get('/catalogo', Catalogo::class)->name('catalogo.index');
    Route::get('/catalogo/categorie', Categorie::class)->name('catalogo.categorie');
    Route::get('/catalogo/nuovo', FormIndumento::class)->name('catalogo.create');
    Route::get('/catalogo/{indumento}/modifica', FormIndumento::class)->name('catalogo.edit');

    Route::get('/colleghi', Colleghi::class)->name('colleghi.index');
    Route::get('/commesse', Commesse::class)->name('commesse.index');

    Route::post('/logout', LogoutController::class)->name('logout');
});
