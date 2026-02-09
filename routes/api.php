<?php

use App\Http\Controllers\DjController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UbicacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\FileController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\CapacitacionController;
use App\Http\Controllers\LoginController;

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


Route::post('/delete-notif', [NotificacionController::class, 'deleteNotificacion']);
Route::post('/save-solicitud', [FileController::class, 'saveSolicitud']);


//CAPACITACION
// Route::get('/get-cursos', [CapacitacionController::class, 'index']);
// Route::get('/get-cursos/{op}', [CapacitacionController::class, 'index']);
Route::get('/get-cursos/{op?}', [CapacitacionController::class, 'index']);
Route::get('/get-curso-id/{id}', [CapacitacionController::class, 'getCursoExamenXId']);
Route::get('/get-programacion-id/{id}', [CapacitacionController::class, 'getProgramacionXId']);
Route::get('/get-curso-programacion/{id}', [CapacitacionController::class, 'getCursoProgramacionXId']);

Route::post('/save-cursos', [CapacitacionController::class, 'saveCurso']);
Route::post('/update-curso', [CapacitacionController::class, 'updateCurso']);

Route::patch('/cursos/{codigo}/habilitado', [CapacitacionController::class, 'updateCursoHab']);

Route::post('/save-programacion', [CapacitacionController::class, 'saveProgramacion']);
Route::post('/update-programacion', [CapacitacionController::class, 'updateProgramacion']);
Route::patch('/programaciones/{codigo}/habilitado', [CapacitacionController::class, 'updateProgramacionHab']);

Route::get('/get-capacitacion-tipo-cursos', [CapacitacionController::class, 'getTipoCursos']);
Route::get('/get-capacitacion-areas', [CapacitacionController::class, 'getAreas']);


Route::post('/cursos/analizar-plantilla', [CapacitacionController::class, 'analizarPlantilla']);


Route::get('/ubicacion/departamentos', [UbicacionController::class, 'departamentos']);
Route::get('/ubicacion/provincias/{departamento_id}', [UbicacionController::class, 'provincias']);
Route::get('/ubicacion/distritos/{provincia_id}', [UbicacionController::class, 'distritos']);


Route::get('/get-postulantes', [FileController::class, 'getPostulantes']);
Route::post('/save-matricula', [CapacitacionController::class, 'saveMatricula']);


Route::post('/save-declaracion-jurada', [DjController::class, 'saveDeclaracionJurada']);

// NOTIFICACIONES
Route::get('/notificaciones/folios-por-vencer', [NotificacionController::class, 'foliosPorVencer']);

// REPORTE FOLIOS PENDIENTES POR SUCURSAL
Route::get('/reporte/folios-pendientes-sucursal', [ReporteController::class, 'foliosPendientesPorSucursal']);





