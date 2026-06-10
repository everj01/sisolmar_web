<?php

use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\DjController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UbicacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\CapacitacionController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ReporteAvancesController;
use App\Http\Controllers\Api\MailController;
use App\Http\Controllers\BiometricoController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/get-personal', [FileController::class, 'getPersonal']);
Route::get('/get-personal-total', [FileController::class, 'getPersonalTotal']);

Route::get('/get-personal-total-prueba', [FileController::class, 'getPersonalTotalPrueba']);

Route::get('/get-personal-legajos', [FileController::class, 'getPersonalLegajos']);

Route::get('/get-coincidencias', [FileController::class, 'getCoincidencias']);
Route::get('/get-documentos/{personalId}', [FileController::class, 'getDocumentosXPersonal']);
Route::get('/get-legajos', [FileController::class, 'getLegajos']);
Route::get('/get-clientes', [FileController::class, 'getClientes']);
Route::get('/get-clientes-legajos', [FileController::class, 'getClientesLegajos']);
Route::get('/get-cargos', [FileController::class, 'getCargosXCliente']);
Route::get('/cargo-counters', [FileController::class, 'getCargoCounters']);
Route::get('/get-folios', [FileController::class, 'getFolios']);
Route::get('/get-folios-personas', [FileController::class, 'getFoliosXPersonas']);
Route::get('/get-folios-persona_uno', [FileController::class, 'getFoliosXPersona_uno']);
Route::get('/get-folios/{codCliente}/{codCargo}', [FileController::class, 'getFoliosXLegajo']);
Route::get('/get-folios-cliente-cargo', [FileController::class, 'getFoliosClienteCargo']);

Route::get('/obtener-personal-reporte', [CapacitacionController::class, 'obtenerPersonalParaReporte']);

Route::get('/get-view-documents/{codPersonal}/{codFolio}', [FileController::class, 'getViewDocumentsPer']);


Route::get('/get-folios-comercial/{codCliente}/{codCargo}', [FileController::class, 'getFoliosXLegajo_comercial']);

Route::post('/change-password-user', [LoginController::class, 'updatePasswordUser']);



//GUADAR DATOS
Route::post('/save_folio_persona', [FileController::class, 'saveFolioPersona']);
//Route::post('/save_folio_persona', [FileController::class, 'saveFolioPersona2']);
Route::post('/save_folio', [FileController::class, 'saveFolio']);
Route::post('/disabled_folio', [FileController::class, 'disabledFolio']);
Route::post('/activar_folio', [FileController::class, 'activarFolio']);

Route::post('/save_legajo', [FileController::class, 'saveLegajo']);

Route::get('/get-areas', [FileController::class, 'getAreas']);
Route::get('/get-posicion', [FileController::class, 'getPosicion']);
Route::get('/get-grupo', [FileController::class, 'getGrupo']);
Route::get('/get-grupo/{codigo}', [FileController::class, 'getGrupoId']);
Route::post('/update_cargo', [FileController::class, 'updateCargo']);

Route::post('/delete-cargo', [FileController::class, 'eliminarCargo']);
Route::post('/activar-cargo', [FileController::class, 'activarCargo']);
Route::get('/get-cargo', [FileController::class, 'getCargos']);
Route::get('/get-cargo/{codigo}', [FileController::class, 'getCargosXCodigo']);

Route::post('/enviar-memos-varios', [CapacitacionController::class, 'enviarMemos']);
Route::post('/enviar-memo-personal', [CapacitacionController::class, 'enviarMemo']);
Route::post('/delete-notif', [NotificacionController::class, 'deleteNotificacion']);
Route::post('/save-solicitud', [FileController::class, 'saveSolicitud']);

// Rutas para notificaciones de matrículas
Route::get('/notificaciones-matriculas', [NotificacionController::class, 'obtenerNotificacionesMatriculas']);
Route::post('/notificaciones-matriculas/{codigo}/leer', [NotificacionController::class, 'marcarNotificacionLeida']);
Route::post('/notificaciones-matriculas/leer-todas', [NotificacionController::class, 'marcarTodasLeidas']);

//CAPACITACION
// Route::get('/get-cursos', [CapacitacionController::class, 'index']);
// Route::get('/get-cursos/{op}', [CapacitacionController::class, 'index']);
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
Route::get('/obtener-capacitacion-sistemas', [CapacitacionController::class, 'obtenerSistemas']);

// Rutas de consulta de capacitación
Route::get('/get-matriculas-curso/{cursoId}', [CapacitacionController::class, 'getMatriculasPorCurso']);
Route::get('/get-matriculas-migra-personal/{cursoId}', [CapacitacionController::class, 'getMatriculasMigraPersonal']);
Route::get('/capacitacion/exportar-excel-matriculas/{cursoId}', [CapacitacionController::class, 'exportMatriculasExcel']);
Route::get('/get-historial-capacitaciones/{personalId}', [CapacitacionController::class, 'getHistorialCapacitaciones']);
Route::get('/buscar-personal-capacitacion', [CapacitacionController::class, 'buscarPersonalCapacitacion']);
Route::get('/listar-jefaturas', [CapacitacionController::class, 'listarJefaturas']);
Route::get('/get-sucursales', [CapacitacionController::class, 'getSucursales']);
Route::get('/obtener-personal-por-sucursal/{sucursalId}', [CapacitacionController::class, 'getPersonalPorSucursal']);
Route::get('/get-cursos-seguimiento', [CapacitacionController::class, 'getCursosSeguimiento']);
Route::get('/obtener-detalle-curso/{course_id}', [CapacitacionController::class, 'obtenerDetalleCurso']);
Route::get('/get-estudiantes-curso', [CapacitacionController::class, 'obtenerEstudiantesCurso']);
Route::get('/obtener-cursos-alumno', [CapacitacionController::class, 'obtenerEstadoCursosAlumno']);
Route::post('/comparar-memos', [CapacitacionController::class, 'compararMemos']);

Route::post('/cursos/analizar-plantilla', [CapacitacionController::class, 'analizarPlantilla']);

Route::get('/ubicacion/departamentos', [UbicacionController::class, 'departamentos']);
Route::get('/ubicacion/provincias/{departamento_id}', [UbicacionController::class, 'provincias']);
Route::get('/ubicacion/distritos/{provincia_id}', [UbicacionController::class, 'distritos']);

Route::get('/get-postulantes', [FileController::class, 'getPostulantes']);
Route::get('/get-personal-dj', [FileController::class, 'getListaDJ']);
Route::get('/get-personal-dj-migracion', [FileController::class, 'getListaDJMigracion']);
Route::post('/save-matricula', [CapacitacionController::class, 'saveMatricula']);

Route::post('/cursos/aplazar-curso', [CapacitacionController::class, 'aplazarCurso']);
Route::get('/cursos/obtener-prog-actual/{courseId}', [CapacitacionController::class, 'obtenerProgActual']);
Route::post('/cursos/programacion-manual', [CapacitacionController::class, 'storeProgramacionManual'])->middleware('web');
Route::get('/obtener-cursos', [CapacitacionController::class, 'obtenerCursos']);
Route::get('/get-cursos-por-area-fechas', [CapacitacionController::class, 'getCursosPorAreaFechas']);
Route::get('/get-areas-encargadas', [CapacitacionController::class, 'getAreasEncargadas']);
Route::get('/obtener-areas-por-sistema/{sistemaId}', [CapacitacionController::class, 'getAreasPorSistema']);
Route::get('/obtener-areas', [CapacitacionController::class, 'obtenerAreas']);
Route::get('/get-empresas', [CapacitacionController::class, 'getEmpresasList']);
Route::get('/get-clientes-pac', [CapacitacionController::class, 'getClientesForPAC']);
Route::get('/capacitacion/combos-apertura', [CapacitacionController::class, 'getCombosApertura']);
Route::post('/capacitacion/procesar-examen-word', [CapacitacionController::class, 'procesarExamenWord']);
Route::post('/capacitacion/guardar-examen-word', [CapacitacionController::class, 'guardarExamenWord']);
Route::post('/capacitacion/validar-excel-matricula', [CapacitacionController::class, 'validarExcelMatricula']);

Route::post('/capacitacion/confirmar-matricula-masiva', [CapacitacionController::class, 'confirmarMatriculaMasiva']);
Route::post('/capacitacion/desmatricular-usuario', [CapacitacionController::class, 'desmatricularUsuario']);

Route::post('/capacitacion/registrar-reporte', [CapacitacionController::class, 'saveReporteCapacitacion']);
Route::get('/capacitacion/listar-reportes', [CapacitacionController::class, 'listarReportesCapacitaciones']);

Route::get('/obtener-memos-resumen/{nivelMemo}', [CapacitacionController::class, 'obtenerMemosResumen']);
Route::get('/obtener-memos-enviados', [CapacitacionController::class, 'obtenerMemosEnviados']);
Route::get('/obtener-detalle-memo/{memoId}', [CapacitacionController::class, 'obtenerDetalleMemo']);
Route::post('/obtener-memos-personal', [CapacitacionController::class, 'obtenerMemosPersonal']);
Route::get('/obtener-info-memo/{nroDoc}', [CapacitacionController::class, 'obtenerInfoMemo']);
Route::get('/obtener-personal', [CapacitacionController::class, 'obtenerPersonal']);
Route::get('/obtener-personal-todas-empresas', [CapacitacionController::class, 'obtenerPersonalTodasEmpresas']);
Route::post('/obtener-personal-record', [CapacitacionController::class, 'obtenerPersonalParaRecord']);
Route::post('/obtener-reporte-general', [CapacitacionController::class, 'obtenerReporteGeneral']);

Route::get('/capacitacion/descargar-reporte/{id}/{tipo}', [CapacitacionController::class, 'descargarReporte']);
Route::put('/capacitacion/actualizar-reporte/{id}', [CapacitacionController::class, 'actualizarReporte']);
Route::patch('/capacitacion/actualizar-estado-reporte/{id}', [CapacitacionController::class, 'actualizarEstadoReporte']);
Route::post('/capacitacion/descargar-reportes-zip', [CapacitacionController::class, 'descargarReportesZip']);
Route::delete('/capacitacion/eliminar-reporte/{id}', [CapacitacionController::class, 'eliminarReporte']);

Route::post('/mail/enviar-recordatorios', [MailController::class, 'enviarRecordatorioCursos']);
Route::post('/mail/enviar-recordatorio', [MailController::class, 'enviarRecordatorioCurso']);

// Route::get('/dj/get-backup-data', [DjController::class, 'getBackupData']);
Route::post('/save-declaracion-jurada', [DjController::class, 'saveDeclaracionJurada']);

// NOTIFICACIONES
Route::get('/notificaciones/folios-por-vencer', [NotificacionController::class, 'foliosPorVencer'])->name('notificaciones.foliosPorVencer');

// REPORTE FOLIOS PENDIENTES POR SUCURSAL
Route::get('/reporte/folios-pendientes-sucursal', [ReporteController::class, 'foliosPendientesPorSucursal']);

// SUCURSALES POR CLIENTE
Route::get('/sucursales-por-cliente', [ConsultaController::class, 'getSucursalesXCliente']);

Route::get('/get-folios-vigentes', [ConsultaController::class, 'getFoliosVigentes']);
Route::get('/get-folios-pendientes', [ConsultaController::class, 'getFoliosPendientes']);
Route::get('/folios/proximos-vencer', [ConsultaController::class, 'getFoliosProximosVencer']);
Route::get('/reporte-personal-sin-migracion', [DjController::class, 'reportePersonalSinMigracion']);
Route::get('/reporte-personal-sin-migracion-v2', [DjController::class, 'reportePersonalSinMigracionV2']);

Route::get('rrhh/reporte-avances', [ReporteAvancesController::class, 'index']);

Route::prefix('dj')->middleware('throttle:dj_api')->group(function () {
    Route::get('/get-personal-data', [DjController::class, 'getPersonalData']);
    Route::get('/get-catalogs', [DjController::class, 'getCatalogs']);
    Route::get('/get-ubicacion', [DjController::class, 'getUbicacion']);
    Route::post('/save-dj-completo', [DjController::class, 'saveDjCompleto']);
    Route::get('/get-backup-data', [DjController::class, 'getBackupData']);
    Route::get('/proxy-foto', [DjController::class, 'proxyFoto']);

    Route::post('/update-check-pdf',  [DjController::class, 'updateCheckPdf']);
    Route::post('/reset-check-pdf',   [DjController::class, 'resetCheckPdf']);
    Route::get('/get-check-pdf',      [DjController::class, 'getCheckPdf']);
    Route::post('/reporte-avance-dj', [DjController::class, 'saveReporteAvanceDj']);


    Route::post('/save-nueva-dj', [DjController::class, 'saveNuevaDj']);
});

// REPORTE FOLIOS POR VENCER CON FILTROS
Route::get('/reporte/folios-por-vencer', [ReporteController::class, 'foliosPorVencer']);

// GUARDAR DJ DE PERSONA
Route::post('/save-dj-folio', [FileController::class, 'saveDjFolio']);

Route::post('/reporte-avance-dj', [DjController::class, 'saveReporteAvanceDj']);
Route::get('/get-biometrico/{codigo}', [BiometricoController::class, 'show']);