<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::post('/login/validar', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login.validar');

/*Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest');*/

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');


Route::middleware(['auth'])->group(function () {
    Route::get('/home', fn()=>view('index'))->name('home');
});

?>
