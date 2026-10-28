<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Toon het bestaande inlogscherm op de startpagina.
Route::get('/', fn () => view('home'))->name('login');

// Beperk loginpogingen om automatisch raden van wachtwoorden af te remmen.
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Het dashboard kiest zelf de juiste pagina aan de hand van de rol.
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
// Deze aanvullende informatiepagina's zijn alleen toegankelijk na inloggen.
Route::view('/ontwikkeling', 'pages.ontwikkeling')->middleware('auth')->name('ontwikkeling');
Route::view('/uren', 'pages.uren')->middleware('auth')->name('uren');
Route::view('/leerdoelen', 'pages.leerdoelen')->middleware('auth')->name('leerdoelen');
Route::view('/reflecties', 'pages.reflecties')->middleware('auth')->name('reflecties');
Route::view('/vergelijken', 'pages.vergelijken')->middleware('auth')->name('vergelijken');
Route::view('/gesprekken', 'pages.gesprekken')->middleware('auth')->name('gesprekken');
// Afmelden is een POST-verzoek, zodat de sessie veilig wordt beëindigd.
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
