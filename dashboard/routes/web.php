<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('home'))->name('login');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::view('/dashboard', 'dashboard')->middleware('auth')->name('dashboard');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
