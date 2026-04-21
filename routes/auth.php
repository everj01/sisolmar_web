<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::post('/validate', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login.validar');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');


Route::middleware(['auth'])->group(function () {
    Route::get('/home', fn()=>view('index'))->name('home');
});

?>
