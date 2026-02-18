<?php

use App\Mail\AlertaCaducidadMail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\CapacitacionController;


Route::get('/preview-email-caducidad', function () {
    return new AlertaCaducidadMail([
        'nombre_personal' => 'Juan Pérez',
        'nombre_empresa'  => 'SISOLMAR',
        'documentos' => [
            [
                'nombre' => 'Certificado Médico',
                'tipo' => 'PRINCIPAL',
                'fecha_caducidad' => '05/02/2026',
                'dias_restantes' => 5
            ],
            [
                'nombre' => 'Antecedentes Policiales',
                'tipo' => 'ADICIONAL',
                'fecha_caducidad' => '10/02/2026',
                'dias_restantes' => 10
            ],
        ]
    ]);
});

Route::get('/test-email-caducidad', function () {

    Mail::to('webmaster@gruposolmar.com.pe')
        ->send(new AlertaCaducidadMail([
            'nombre_empresa'  => 'SISOLMAR'
        ]));

    return 'Correo enviado (si no hubo error)';
});



Route::middleware(['auth'])->group(function () {
    Route::get('/login',[LoginController::class, 'index'])->name('login');
    Route::post('/login/validar', [LoginController::class, 'validar']);

    Route::post('/capacitacion/save-matricula', [CapacitacionController::class, 'saveMatricula'])->name('capacitacion.save-matricula');
    
    // Vistas de consulta de capacitación
    Route::get('/capacitacion/consulta-matriculas', [CapacitacionController::class, 'vistaConsultaMatriculas'])->name('capacitacion.consulta-matriculas');
    Route::get('/capacitacion/historial-capacitaciones', [CapacitacionController::class, 'vistaHistorialCapacitaciones'])->name('capacitacion.historial-capacitaciones');

    Route::group(['prefix' => '/', 'where' => ['first' => '^(?!api|\.well-known).*']], function () {
        Route::get('', [RoutingController::class, 'index'])->name('root');
        Route::get('/home', fn()=>view('index'))->name('home');
        Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
        Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
        Route::get('{any}', [RoutingController::class, 'root'])->name('any');
    });

    Route::get('/debug-notificaciones', function() {
        $usuarioId = \Illuminate\Support\Facades\Auth::id();
        $user = \Illuminate\Support\Facades\Auth::user();
        
        $notifs = \Illuminate\Support\Facades\DB::table('sw_notificaciones_matriculas')
            ->select('codigo', 'usuario_id', 'nombre_curso', 'tipo', 'leido', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'usuario_autenticado' => [
                'id' => $usuarioId,
                'nombre' => $user->name ?? 'N/A',
                'email' => $user->email ?? 'N/A'
            ],
            'notificaciones_bd' => $notifs,
            'notificaciones_del_usuario' => \App\Models\NotificacionMatricula::where('usuario_id', $usuarioId)->get()
        ]);
    });


    Route::post('/pdf_vacio', [FileController::class, 'pdf_vacio']);
    Route::post('/dash-rrhh', [FileController::class, 'dashboard']);
    Route::post('/logout', [LoginController::class, 'logout']);
    Route::post('/generar-pdf', [FileController::class, 'generarPDF']);
    Route::post('/generar-pdf2', [FileController::class, 'generarPDF2']);
    Route::post('/save_cargo', [FileController::class, 'saveCargo']);
    Route::post('/capacitacion/save-matricula', [CapacitacionController::class, 'saveMatricula'])->name('capacitacion.save-matricula');
});

require __DIR__ . '/auth.php';




