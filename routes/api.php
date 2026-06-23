<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/ver-dj-externo/{codPersonal}', [FileController::class, 'verDjPdfExterno'])->name('ver.djExterno');
