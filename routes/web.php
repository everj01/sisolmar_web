 <?php

  use App\Http\Controllers\BiometricoController;
use App\Http\Controllers\CapacitacionController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\DjController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\ReporteAvancesController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\ReportePersonalController;
use App\Http\Controllers\RoutingController;
use App\Http\Controllers\UbicacionController;
use App\Http\Controllers\UsuarioController;
use App\Mail\AlertaCaducidadMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'index'])->name('login');

Route::middleware(['auth'])->group(function () {

    // ─── RUTAS WEB (vistas y acciones directas) ───────────────────────────────
    Route::get('/ver-dj/{codPersonal}', [FileController::class, 'verDjPdf'])->name('ver.dj');
    Route::post('/save-dj-folio', [FileController::class, 'saveDjFolio']);
    Route::get('/get-personal-dj', [FileController::class, 'getListaDJXusuario']);
    Route::post('/save-dj-folio-2', [FileController::class, 'saveDjFolioAux']);
    Route::get('/get-personal', [FileController::class, 'getPersonal']);
    Route::get('/get-personal-total', [FileController::class, 'getPersonalTotal']);
    Route::get('/reportes', [FileController::class, 'ViewReportes'])->name('reportes');
    Route::get('/reporte/folios-por-vencer-cliente', [ReporteController::class, 'foliosPorVencerXCliente']);
    Route::post('/capacitacion/save-matricula', [CapacitacionController::class, 'saveMatricula'])->name('capacitacion.save-matricula');
    Route::get('/usuario', [LoginController::class, 'getUsuarioSession']);
    Route::get('/get-documentos/{personalId}', [FileController::class, 'getDocumentosXPersonal']);
    Route::get('/capacitacion/consulta-matriculas', [CapacitacionController::class, 'vistaConsultaMatriculas'])->name('capacitacion.consulta-matriculas');
    Route::get('/reporte/folios-por-vencer', [ReporteController::class, 'foliosPorVencer']);
    Route::post('/pdf_vacio', [FileController::class, 'pdf_vacio']);
    Route::post('/dash-rrhh', [FileController::class, 'dashboard']);
    Route::post('/generar-pdf', [FileController::class, 'generarPDF']);
    Route::post('/generar-pdf2', [FileController::class, 'generarPDF2']);
    Route::post('/save_cargo', [FileController::class, 'saveCargo']);

    // Route::get('/debug-notificaciones', function () {
    //     $usuarioId = \Illuminate\Support\Facades\Auth::id();
    //     $user = \Illuminate\Support\Facades\Auth::user();
    //     $notifs = \Illuminate\Support\Facades\DB::table('sw_notificaciones_matriculas')
    //         ->select('codigo', 'usuario_id', 'nombre_curso', 'tipo', 'leido', 'created_at')
    //         ->orderBy('created_at', 'desc')
    //         ->get();

    //     return response()->json([
    //         'usuario_autenticado' => ['id' => $usuarioId, 'nombre' => $user->name ?? 'N/A', 'email' => $user->email ?? 'N/A'],
    //         'notificaciones_bd' => $notifs,
    //         'notificaciones_del_usuario' => \App\Models\NotificacionMatricula::where('usuario_id', $usuarioId)->get(),
    //     ]);
    // });

    // ─── RUTAS /api/* (antes en api.php) ─────────────────────────────────────
    Route::prefix('api')->group(function () {

    

        // Usuarios
        Route::get('/get-usuarios', [UsuarioController::class, 'getUsuarios']);
        Route::post('/save-usuario', [UsuarioController::class, 'store']);
        Route::post('/update-usuario', [UsuarioController::class, 'update']);
        Route::post('/toggle-usuario', [UsuarioController::class, 'toggleHabilitado']);
        Route::get('/get-sucursales-usuario/{codUsuario}', [UsuarioController::class, 'getSucursalesUsuario']);
        Route::post('/save-sucursales-usuario', [UsuarioController::class, 'saveSucursalesUsuario']);

        // Personal
        Route::get('/get-personal', [FileController::class, 'getPersonal']);
        Route::get('/get-personal-total', [FileController::class, 'getPersonalTotal']);
        Route::get('/get-personal-total-reporte', [FileController::class, 'getPersonalTotalReporte']);
        Route::get('/get-personal-total-prueba', [FileController::class, 'getPersonalTotalPrueba']);
        Route::get('/get-personal-legajos', [FileController::class, 'getPersonalLegajos']);

        // File Control
        Route::get('/get-coincidencias', [FileController::class, 'getCoincidencias']);
        Route::get('/get-documentos/{personalId}', [FileController::class, 'getDocumentosXPersonal']);
        Route::get('/get-legajos', [FileController::class, 'getLegajos']);
        Route::get('/get-clientes', [FileController::class, 'getClientes']);
        Route::get('/get-clientes-legajos', [FileController::class, 'getClientesLegajos']);
        Route::get('/get-cargos', [FileController::class, 'getCargosXCliente']);
        Route::get('/cargo-counters', [FileController::class, 'getCargoCounters']);

        Route::post('/get-folios-personas', [FileController::class, 'getFoliosXPersonas']);
        Route::post('/get-folios-persona_uno', [FileController::class, 'getFoliosXPersona_uno']);
        Route::get('/get-folios/{codCliente}/{codCargo}', [FileController::class, 'getFoliosXLegajo']);
        Route::get('/get-folios-cliente-cargo', [FileController::class, 'getFoliosClienteCargo']);
        Route::get('/get-folios', [FileController::class, 'getFolios']);
        Route::get('/get-folios-vigentes', [ConsultaController::class, 'getFoliosVigentes']);
        Route::get('/get-folios-pendientes', [ConsultaController::class, 'getFoliosPendientes']);
        Route::get('/folios/proximos-vencer', [ConsultaController::class, 'getFoliosProximosVencer']);
        Route::get('/get-folios-comercial/{codCliente}/{codCargo}', [FileController::class, 'getFoliosXLegajo_comercial']);
        Route::post('/save_folio_persona', [FileController::class, 'saveFolioPersona']);
        Route::post('/save_folio', [FileController::class, 'saveFolio']);
        Route::post('/disabled_folio', [FileController::class, 'disabledFolio']);
        Route::post('/activar_folio', [FileController::class, 'activarFolio']);
        Route::get('/check-folio-nombre', [FileController::class, 'checkNombreFolio']);
        Route::post('/save-dj-folio', [FileController::class, 'saveDjFolio']);
        Route::get('/get-view-documents/{codPersonal}/{codFolio}', [FileController::class, 'getViewDocumentsPer']);

        // Cargos
        Route::get('/get-cargo', [FileController::class, 'getCargos']);
        Route::get('/get-cargo/{codigo}', [FileController::class, 'getCargosXCodigo']);
        Route::get('/check-cargo-nombre', [FileController::class, 'checkNombreCargo']);
        Route::post('/update_cargo', [FileController::class, 'updateCargo']);
        Route::post('/delete-cargo', [FileController::class, 'eliminarCargo']);
        Route::post('/activar-cargo', [FileController::class, 'activarCargo']);
        Route::post('save_legajo', [FileController::class, 'saveLegajo']);

        // Notificaciones
        Route::get('/get-notificaciones', [FileController::class, 'getNotificaciones']);
        Route::post('/delete-notif', [NotificacionController::class, 'deleteNotificacion']);
        Route::get('/notificaciones-matriculas', [NotificacionController::class, 'obtenerNotificacionesMatriculas']);
        Route::post('/notificaciones-matriculas/{codigo}/leer', [NotificacionController::class, 'marcarNotificacionLeida']);
        Route::post('/notificaciones-matriculas/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);
        Route::get('/notificaciones/folios-por-vencer', [NotificacionController::class, 'foliosPorVencer'])->name('notificaciones.foliosPorVencer');

        // Solicitudes y otros
        Route::post('/save-solicitud', [FileController::class, 'saveSolicitud']);
        Route::get('/get-areas', [FileController::class, 'getAreas']);
        Route::get('/get-posicion', [FileController::class, 'getPosicion']);
        Route::get('/get-grupo', [FileController::class, 'getGrupo']);
        Route::get('/get-grupo/{codigo}', [FileController::class, 'getGrupoId']);

        // Login
        Route::post('/change-password-user', [LoginController::class, 'updatePasswordUser']);

        // Capacitación
        Route::get('/get-cursos/{op?}', [CapacitacionController::class, 'index']);
        Route::get('/cursos/alertas-vencimiento', [CapacitacionController::class, 'getAlertasVencimiento']);
        Route::get('/get-curso-id/{id}', [CapacitacionController::class, 'getCursoExamenXId']);
        Route::get('/get-programacion-id/{id}', [CapacitacionController::class, 'getProgramacionXId']);
        Route::get('/get-curso-programacion/{id}', [CapacitacionController::class, 'getCursoProgramacionXId']);
        Route::post('/save-cursos', [CapacitacionController::class, 'saveCurso']);
        Route::post('/update-curso', [CapacitacionController::class, 'updateCurso']);
        Route::patch('/cursos/{codigo}/habilitado', [CapacitacionController::class, 'updateCursoHab']);
        Route::delete('/cursos/{codigo}', [CapacitacionController::class, 'destroyCurso']);
        Route::post('/save-programacion', [CapacitacionController::class, 'saveProgramacion']);
        Route::post('/update-programacion', [CapacitacionController::class, 'updateProgramacion']);
        Route::patch('/programaciones/{codigo}/habilitado', [CapacitacionController::class, 'updateProgramacionHab']);
        Route::get('/get-capacitacion-tipo-cursos', [CapacitacionController::class, 'getTipoCursos']);
        Route::get('/get-capacitacion-areas', [CapacitacionController::class, 'getAreas']);
        Route::get('/get-matriculas-curso/{cursoId}', [CapacitacionController::class, 'getMatriculasPorCurso']);
        Route::get('/get-matriculas-migra-personal/{cursoId}', [CapacitacionController::class, 'getMatriculasMigraPersonal']);
        Route::get('/get-historial-capacitaciones/{personalId}', [CapacitacionController::class, 'getHistorialCapacitaciones']);
        Route::get('/buscar-personal-capacitacion', [CapacitacionController::class, 'buscarPersonalCapacitacion']);
        Route::get('/get-sucursales', [CapacitacionController::class, 'getSucursales']);
        Route::post('/cursos/analizar-plantilla', [CapacitacionController::class, 'analizarPlantilla']);
        Route::get('/capacitacion/combos-apertura', [CapacitacionController::class, 'getCombosApertura']);
        Route::post('/capacitacion/procesar-examen-ia', [CapacitacionController::class, 'procesarExamenConIA']);
        Route::post('/capacitacion/guardar-examen-ia', [CapacitacionController::class, 'guardarExamenIA']);
        Route::post('/capacitacion/validar-excel-matricula', [CapacitacionController::class, 'validarExcelMatricula']);
        Route::post('/capacitacion/confirmar-matricula-masiva', [CapacitacionController::class, 'confirmarMatriculaMasiva']);
        Route::post('/save-matricula', [CapacitacionController::class, 'saveMatricula']);
        Route::post('/cursos/programacion-manual', [CapacitacionController::class, 'storeProgramacionManual']);
        Route::get('/get-areas-encargadas', [CapacitacionController::class, 'getAreasEncargadas']);
        Route::get('/get-areas-por-sistema/{sistemaId}', [CapacitacionController::class, 'getAreasPorSistema']);
        Route::get('/get-empresas', [CapacitacionController::class, 'getEmpresasList']);
        Route::get('/get-clientes-pac', [CapacitacionController::class, 'getClientesForPAC']);

        // Ubicación
        Route::get('/ubicacion/departamentos', [UbicacionController::class, 'departamentos']);
        Route::get('/ubicacion/provincias/{departamento_id}', [UbicacionController::class, 'provincias']);
        Route::get('/ubicacion/distritos/{provincia_id}', [UbicacionController::class, 'distritos']);

        // Postulantes / DJ
        Route::get('/get-postulantes', [FileController::class, 'getPostulantes']);
        Route::get('/get-personal-dj', [FileController::class, 'getListaDJ']);
        Route::get('/get-personal-dj-migracion', [FileController::class, 'getListaDJMigracion']);
        Route::post('/save-declaracion-jurada', [DjController::class, 'saveDeclaracionJurada']);
        Route::get('/reporte-personal-sin-migracion', [DjController::class, 'reportePersonalSinMigracion']);
        Route::get('/reporte-personal-sin-migracion-v2', [DjController::class, 'reportePersonalSinMigracionV2']);
        Route::post('/save-dj-folio', [DjController::class, 'saveDeclaracionJurada']);
        Route::post('/reporte-avance-dj', [DjController::class, 'saveReporteAvanceDj']);

        // Consultas
        Route::get('/sucursales-por-cliente', [ConsultaController::class, 'getSucursalesXCliente']);
        Route::get('rrhh/reporte-avances', [ReporteAvancesController::class, 'index']);

        // Reportes
        Route::get('/reporte/folios-pendientes-sucursal', [ReporteController::class, 'foliosPendientesPorSucursal']);
        Route::get('/reporte/folios-por-vencer', [ReporteController::class, 'foliosPorVencer']);
        Route::get('/reporte/folios-pendientes-registro', [ReporteController::class, 'foliosPendientesRegistro']);
            Route::get('/reporte/estado-legajos', [FileController::class, 'getEstadoLegajos']);
        Route::get('/reporte/carnet', [ReporteController::class, 'carnet']);

        // Biométrico
        Route::get('/get-biometrico/{codigo}', [BiometricoController::class, 'show']);

        // DJ módulo (antes con prefix 'dj')
        Route::prefix('dj')->middleware('throttle:dj_api')->group(function () {
            Route::get('/get-personal-data', [DjController::class, 'getPersonalData']);
            Route::get('/get-catalogs', [DjController::class, 'getCatalogs']);
            Route::get('/get-ubicacion', [DjController::class, 'getUbicacion']);
            Route::post('/save-dj-completo', [DjController::class, 'saveDjCompleto']);
            Route::get('/get-backup-data', [DjController::class, 'getBackupData']);
            Route::get('/proxy-foto', [DjController::class, 'proxyFoto']);
            Route::post('/update-check-pdf', [DjController::class, 'updateCheckPdf']);
            Route::post('/reset-check-pdf', [DjController::class, 'resetCheckPdf']);
            Route::get('/get-check-pdf', [DjController::class, 'getCheckPdf']);
            Route::post('/reporte-avance-dj', [DjController::class, 'saveReporteAvanceDj']);
            Route::get('/validar-documento', [DjController::class, 'validarDocumentoDj']);
            Route::post('/save-nueva-dj', [DjController::class, 'saveNuevaDj']);
            Route::get('/buscar-coincidencias', [DjController::class, 'buscarCoincidencias']);
            Route::get('/reporte-personal-datos-generales', [ReportePersonalController::class, 'datosGenerales']);
            Route::get('/reporte/proxy-imagen', [ReportePersonalController::class, 'proxyImagen']);
            Route::post('/save-recontratacion', [DjController::class, 'saveRecontratacion']);
            Route::post('/upload-foto-personal', [DjController::class, 'uploadFotoPersonal']);
            Route::get('get-tipo-doc/', [DjController::class, 'getTipoDoc']);
            Route::get('get-tipo-per/', [DjController::class, 'getTipoPer']);
            Route::get('get-estado-civil/', [DjController::class, 'getEstadoCivil']);
            Route::get('get-sistema-prev/', [DjController::class, 'getSistemaPrev']);
        });

    }); // fin prefix('api')

    // ─── RUTAS DE VISTAS (catchall al final) ─────────────────────────────────
    Route::group(['prefix' => '/', 'where' => ['first' => '^(?!api|\.well-known).*']], function () {
        Route::get('', [RoutingController::class, 'index'])->name('root');
        Route::get('/home', fn () => view('index'))->name('home');
        Route::get('{first}/{second}/{third}', [RoutingController::class, 'thirdLevel'])->name('third');
        Route::get('{first}/{second}', [RoutingController::class, 'secondLevel'])->name('second');
        Route::get('{any}', [RoutingController::class, 'root'])->name('any');
    });

}); // fin middleware auth

// ─── TEST EMAILS ─────────────────────────────────────────────────────────────
Route::get('/preview-email-caducidad', function () {
    return new AlertaCaducidadMail([
        'nombre_personal' => 'Juan Pérez',
        'nombre_empresa' => 'SISOLMAR',
        'documentos' => [
            ['nombre' => 'Certificado Médico', 'tipo' => 'PRINCIPAL', 'fecha_caducidad' => '05/02/2026', 'dias_restantes' => 5],
            ['nombre' => 'Antecedentes Policiales', 'tipo' => 'ADICIONAL', 'fecha_caducidad' => '10/02/2026', 'dias_restantes' => 10],
        ],
    ]);
});

Route::get('/test-email-caducidad', function () {
    Mail::to('webmaster@gruposolmar.com.pe')->send(new AlertaCaducidadMail(['nombre_empresa' => 'SISOLMAR']));

    return 'Correo enviado (si no hubo error)';
});

Route::get('/debug-permisos', function () {
    return response()->json([
        'tipo_rol' => Auth::user()?->tipo_rol,
        'permisos' => session('permisos'),
    ]);
})->middleware('auth');

require __DIR__.'/auth.php';
