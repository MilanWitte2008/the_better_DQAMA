<?php

use App\Http\Controllers\ApplicationAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Iedereen mag de startpagina bekijken. Inloggen heeft een eigen webadres.
Route::view('/', 'home')->name('home');
Route::get('/login', fn () => auth()->check() ? redirect()->route('dashboard') : view('login'))->name('login');

// Beperk het aantal pogingen om in te loggen. Dit helpt tegen het raden van wachtwoorden.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10.1,1')->name('login.attempt');

// Je moet ingelogd zijn. De controller kiest daarna de pagina voor jouw rol.
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
// Afmelden is een POST-verzoek, zodat de sessie veilig wordt beëindigd.
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// Deze adressen beginnen met /beheer. De controller controleert ook of je beheerder bent.
Route::middleware('auth')->prefix('beheer')->name('beheer.')->group(function () {
    // GET opent het formulier. POST slaat een nieuwe gebruiker op.
    Route::get('/gebruikers/nieuw', [ApplicationAdminController::class, 'create'])->name('members.create');
    Route::post('/gebruikers', [ApplicationAdminController::class, 'store'])->name('members.store');
    Route::get('/gebruikers/{member}/bewerken', [ApplicationAdminController::class, 'edit'])->name('members.edit');
    // {member} is het nummer van de gebruiker. PUT slaat de wijzigingen op.
    Route::put('/gebruikers/{member}', [ApplicationAdminController::class, 'update'])->name('members.update');
    // DELETE vraagt om deze gebruiker te verwijderen. De controller controleert of dat mag.
    Route::delete('/gebruikers/{member}', [ApplicationAdminController::class, 'destroy'])->name('members.destroy');
    Route::post('/scholen', [ApplicationAdminController::class, 'storeSchool'])->name('schools.store');
});
