<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/login',[LoginController::class, 'index'])->name('login');
    Route::post('/login/validar', [LoginController::class, 'validar']);

    Route::group(['prefix' => '/', 'where' => ['first' => '^(?!api).*']], function () {
        Route::get('', [RoutingController::class, 'index'])->name('root');
        Route::get('/home', fn()=>view('index'))->name('home');
        Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
        Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
        Route::get('{any}', [RoutingController::class, 'root'])->name('any');
    });

    Route::post('/pdf_vacio', [FileController::class, 'pdf_vacio']);

    Route::get('/file_control/chargefile', [FileController::class, 'index'])->name('file_control.chargefile');
    Route::post('/dash-rrhh', [FileController::class, 'dashboard'])->name('dash-rrhh');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/generar-pdf', [FileController::class, 'generarPDF']);
    Route::post('/generar-pdf2', [FileController::class, 'generarPDF2']);
    Route::post('/save_cargo', [FileController::class, 'saveCargo']);

    Route::get('/file_control/gestion_dj', [FileController::class, 'indexGestionDj'])->name('file_control.gestiondj');
});

require __DIR__ . '/auth.php';




