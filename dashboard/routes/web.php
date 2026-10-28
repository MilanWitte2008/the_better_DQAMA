<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('login');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
Route::view('/ontwikkeling', 'pages.ontwikkeling')->middleware('auth')->name('ontwikkeling');
Route::view('/uren', 'pages.uren')->middleware('auth')->name('uren');
Route::view('/leerdoelen', 'pages.leerdoelen')->middleware('auth')->name('leerdoelen');
Route::view('/reflecties', 'pages.reflecties')->middleware('auth')->name('reflecties');
Route::view('/vergelijken', 'pages.vergelijken')->middleware('auth')->name('vergelijken');
Route::view('/gesprekken', 'pages.gesprekken')->middleware('auth')->name('gesprekken');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
