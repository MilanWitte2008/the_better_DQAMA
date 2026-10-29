<?php

use App\Http\Controllers\ApplicationAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Toon het bestaande inlogscherm op de startpagina.
Route::get('/', fn () => view('home'))->name('login');

// Beperk loginpogingen om automatisch raden van wachtwoorden af te remmen.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10.1,1')->name('login.attempt');

// Het dashboard kiest zelf de juiste pagina aan de hand van de rol.
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
// Afmelden is een POST-verzoek, zodat de sessie veilig wordt beëindigd.
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// Iedere beheeractie controleert de rol opnieuw op de server.
Route::middleware('auth')->prefix('beheer')->name('beheer.')->group(function () {
    Route::get('/gebruikers/nieuw', [ApplicationAdminController::class, 'create'])->name('members.create');
    Route::post('/gebruikers', [ApplicationAdminController::class, 'store'])->name('members.store');
    Route::get('/gebruikers/{member}/bewerken', [ApplicationAdminController::class, 'edit'])->name('members.edit');
    Route::put('/gebruikers/{member}', [ApplicationAdminController::class, 'update'])->name('members.update');
    Route::post('/scholen', [ApplicationAdminController::class, 'storeSchool'])->name('schools.store');
});
