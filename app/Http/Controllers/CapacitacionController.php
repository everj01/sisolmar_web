<?php

namespace App\Http\Controllers;

use App\Models\CapacitacionAreas;
use App\Models\CapacitacionTipoCurso;
use App\Models\CursoProgramacion;
use App\Models\Cursos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Models\ExamenCurso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Jobs\DispatchMatriculaBatchJob;
use Illuminate\Support\Facades\Auth;
use App\Models\Matricula;
use App\Models\Consulta;

class CapacitacionController extends Controller
{


    public function index(Request $request, ?string $op = null): JsonResponse
    {
        // Query base - solo seleccionamos columnas necesarias para la lista
        // El eager loading no es necesario aquí porque no devolvemos datos de relaciones
        // Para ver detalles de un curso específico (con examen/área), usar getCursoExamenXId()
        $query = Cursos::query();

        // Filtrar por habilitado
        if (!is_null($op)) {
            $query->where('habilitado', $op);
        }

        // Filtro por área
        if ($request->filled('filtro_area')) {
            $query->where('area', $request->input('filtro_area'));
        }

        // Filtro por tipo de curso
        if ($request->filled('filtro_tipo')) {
            $query->where('tipo_curso', $request->input('filtro_tipo'));
        }

        // Validar qué cursos tienen periodos vigentes para ocultar botón mágico
        $cursosVigentes = DB::table('sw_cursos_programacion')
            ->where('estado_periodo', 'VIGENTE')
            ->where('habilitado', 1)
            ->pluck('cod_cursos')
            ->toArray();

        $cursos = $query->get()->map(function ($curso) use ($cursosVigentes) {
            return [
                'codigo' => $curso->codigo,
                'codigoCurso' => $curso->codigo_curso,
                'nombre' => $curso->nombre,
                'habilitado' => $curso->habilitado,
                'periodicidad' => $curso->periodicidad,
                'es_periodico' => $curso->es_periodico,
                'frecuencia' => $curso->frecuencia,
                'proyeccion_anios' => $curso->proyeccion_anios,
                'tiene_vigente' => in_array($curso->codigo, $cursosVigentes),
                'codigo_moodle' => $curso->codigo_moodle,
            ];
        });

        return response()->json($cursos);
    }

    public function getCursoExamenXId(int $id): JsonResponse
    {
        $curso = Cursos::with(['examen', 'tipoCurso'])->where('codigo', $id)->firstOrFail();

        // Obtener sucursales asignadas
        $sucursales = DB::table('sw_curso_sucursales')
            ->where('curso_codigo', $curso->codigo)
            ->pluck('sucursal');

        $curso->sucursales = $sucursales;

        // Resolver nombre del responsable si existe (Fuente Oficial si_solm)
        if ($curso->cod_responsable) {
            $resp = DB::connection('sqlsrv')->selectOne("
                SELECT LTRIM(RTRIM(APEL_1 + ' ' + ISNULL(APEL_2, '') + ' ' + NOMB_1 + ' ' + ISNULL(NOMB_2, ''))) as nombre
                FROM si_solm.dbo.PERSONAL 
                WHERE CODI_PERS = ?
            ", [$curso->cod_responsable]);

            $curso->nombre_responsable = $resp->nombre ?? 'No encontrado';
        } else {
            $curso->nombre_responsable = '';
        }

        return response()->json([
            'success' => true,
            'curso' => $curso
        ]);
    }

    public function updateCurso(Request $request): JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'tipo_curso' => 'required|integer|exists:sw_capacitacion_tipo_curso,codigo',
            'area_conocimiento' => 'required|exists:sw_capacitacion_areas,codigo',
            'area_responsable' => 'nullable|integer',
            'es_periodico' => 'required|integer|in:0,1',
            'frecuencia' => 'nullable|string',
            'proyeccion_anios' => 'nullable|integer',
            'fechas_generadas' => 'nullable|string',
            'nombre_exa' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'tiempo' => 'nullable|required_if:aplica_evaluacion,1|integer',
            'nota' => 'nullable|required_if:aplica_evaluacion,1|integer',
            'intentos' => 'nullable|required_if:aplica_evaluacion,1|integer',
            'archivo' => 'nullable|file|max:51200',
            'aplica_evaluacion' => 'nullable|integer|in:0,1',
            'obligatorio_alta' => 'nullable|integer|in:0,1',
            'cod_responsable' => 'nullable|string|max:20',
            'target_group' => 'nullable|string|in:TODOS,ADMINISTRATIVO,OPERATIVO',
            'cod_moodle_area' => 'nullable|integer',
            'observaciones' => 'nullable|string',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $curso = Cursos::where('codigo', $request->codigo)->firstOrFail();
            $codigo_curso =  $curso->codigo_curso;

            $periodicidadVal = 0;
            if ($request->input('es_periodico') == 1) {
                switch ($request->input('frecuencia')) {
                    case 'MENSUAL':
                        $periodicidadVal = 12;
                        break;
                    case 'BIMESTRAL':
                        $periodicidadVal = 6;
                        break;
                    case 'TRIMESTRAL':
                        $periodicidadVal = 4;
                        break;
                    case 'CUATRIMESTRAL':
                        $periodicidadVal = 3;
                        break;
                    case 'SEMESTRAL':
                        $periodicidadVal = 2;
                        break;
                    case 'ANUAL':
                        $periodicidadVal = 1;
                        break;
                    default:
                        $periodicidadVal = 0;
                        break;
                }
            }

            $curso->update([
                'nombre' => $request->nombre,
                'tipo_curso' => $request->tipo_curso,
                'area_conocimiento' => $request->area_conocimiento,
                'area' => $request->area_responsable ?? $request->area_conocimiento, // Persistir área específica si existe
                'periodicidad' => $periodicidadVal,
                'es_periodico' => $request->input('es_periodico'),
                'frecuencia' => $request->input('frecuencia'),
                'proyeccion_anios' => $request->input('proyeccion_anios'),
                'aplica_evaluacion' => $request->input('aplica_evaluacion', 0),
                'obligatorio_alta' => $request->input('obligatorio_alta', 0),
                'cod_responsable' => $request->input('cod_responsable'),
                'target_group' => $request->input('target_group', 'TODOS'),
                'observaciones' => $request->input('observaciones'),
                'fecha_modificacion' => date('Y-m-d\TH:i:s.000')
            ]);

            // AUTOGENERAR PROGRAMACIONES SI VIENEN NUEVAS
            if ($request->has('fechas_generadas')) {
                $fechasArray = json_decode($request->input('fechas_generadas'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($fechasArray)) {
                    $lastProg = CursoProgramacion::orderBy('codigo_programacion', 'desc')->first();
                    $newProgCod = $lastProg ? intval($lastProg->codigo_programacion) : 1000;

                    foreach ($fechasArray as $fechaItem) {
                        $existe = CursoProgramacion::where('cod_cursos', $curso->codigo)
                            ->where('periodo', $fechaItem['periodo'])
                            ->where('tipo', 'REGULAR')
                            ->exists();

                        if (!$existe) {
                            $newProgCod++;
                            CursoProgramacion::create([
                                'codigo_programacion' => str_pad($newProgCod, 4, '0', STR_PAD_LEFT),
                                'cod_cursos'    => $curso->codigo,
                                'periodo'       => $fechaItem['periodo'],
                                'tipo'          => 'REGULAR',
                                'fecha_inicio'  => $fechaItem['inicio'] . 'T00:00:00.000',
                                'fecha_final'   => $fechaItem['final'] . 'T23:59:59.000',
                                'fecha_creacion' => date('Y-m-d\TH:i:s.000'),
                                'habilitado'    => 1,
                            ]);
                        }
                    }
                }
            }

            // ACTUALIZAR SUCURSALES (Borrar anteriores e insertar nuevas)
            DB::table('sw_curso_sucursales')->where('curso_codigo', $curso->codigo)->delete();

            if ($request->has('sucursales_asignadas') && is_array($request->sucursales_asignadas)) {
                $sucursales = $request->sucursales_asignadas;
                foreach ($sucursales as $sucursal) {
                    DB::table('sw_curso_sucursales')->insert([
                        'curso_codigo' => $curso->codigo,
                        'sucursal' => $sucursal,
                        'created_at' => date('Y-m-d\TH:i:s.000'),
                        'updated_at' => date('Y-m-d\TH:i:s.000')
                    ]);
                }
            }

            // Manejar examen según aplica_evaluacion
            $aplicaEvaluacion = $request->input('aplica_evaluacion', 0);

            if ($aplicaEvaluacion == 1) {
                // Verificar si ya existe el examen
                $examen = ExamenCurso::where('cod_cursos', $request->codigo)->first();

                // Auto-generar nombre si no viene (usuario eliminó campo)
                $nombreExamen = $request->nombre_exa ?? ("Examen de " . $request->nombre);

                if ($examen) {
                    // Actualizar examen existente
                    $examen->update([
                        'nombre' => $nombreExamen,
                        'descripcion' =>  $request->descripcion,
                        'tiempo' => (int) ($request->tiempo ?? 0),
                        'nota_minima' => (int) ($request->nota ?? 0),
                        'intentos' => (int) ($request->intentos ?? 0),
                        'cantidad_preguntas' => (int) ($request->cantidad_preguntas ?? 0),
                        'preguntas_balotario' => (int) ($request->preguntas_balotario ?? 0),
                        'fecha_modificacion' => date('Y-m-d\TH:i:s.000')
                    ]);
                } else {
                    // Crear nuevo examen
                    $examen = ExamenCurso::create([
                        'cod_cursos' => $curso->codigo,
                        'nombre' => $nombreExamen,
                        'descripcion' => $request->descripcion,
                        'tiempo' => (int) ($request->tiempo ?? 0),
                        'nota_minima' => (int) ($request->nota ?? 0),
                        'file_tiene' => 0,
                        'file_nombre' => null,
                        'file_ruta' => null,
                        'file_extension' => null,
                        'file_tipo' => null,
                        'file_nombre_original' => null,
                        'intentos' => (int) ($request->intentos ?? 0),
                        'cantidad_preguntas' => (int) ($request->cantidad_preguntas ?? 0),
                        'preguntas_balotario' => (int) ($request->preguntas_balotario ?? 0),
                        'fecha_creacion' => date('Y-m-d\TH:i:s.000')
                    ]);
                }

                if ($request->hasFile('archivo')) {
                    $archivo = $request->file('archivo');

                    if ($examen->file_ruta && Storage::disk('public')->exists($examen->file_ruta)) {
                        Storage::disk('public')->delete($examen->file_ruta);
                    }

                    if ($archivo->getClientOriginalExtension() !== 'mbz') {
                        return response()->json([
                            'success' => false,
                            'message' => 'El archivo debe ser .mbz',
                            'errors' => ['archivo' => ['El archivo debe ser .mbz']]
                        ], 422);
                    }

                    $tienePlantilla = true;

                    $anio = date('Y');
                    $mes = ucfirst(Carbon::now()->translatedFormat('F'));

                    $tipoArchivo = $archivo->getClientMimeType();
                    $extensionArchivo = $archivo->getClientOriginalExtension();
                    $nombreArchivoOriginal = $archivo->getClientOriginalName();

                    $baseNombre = 'EXA_' . $codigo_curso . '_' . date('Ymd');
                    $carpeta = "plantillas/{$anio}/{$mes}";

                    if (!Storage::disk('public')->exists($carpeta)) {
                        Storage::disk('public')->makeDirectory($carpeta);
                    }

                    $contador = 1;

                    do {
                        $nombreArchivoFinal = "{$baseNombre}_{$contador}." . $extensionArchivo;
                        $rutaCompleta = storage_path("app/public/{$carpeta}/{$nombreArchivoFinal}");
                        $contador++;
                    } while (file_exists($rutaCompleta));

                    $rutaArchivo = $archivo->storeAs($carpeta, $nombreArchivoFinal, 'public');

                    $examen->update([
                        'file_tiene' => $tienePlantilla ? 1 : 0,
                        'file_nombre' => $nombreArchivoFinal,
                        'file_ruta' => $rutaArchivo,
                        'file_extension' => $extensionArchivo,
                        'file_tipo' => $tipoArchivo,
                        'file_nombre_original' => $nombreArchivoOriginal,
                    ]);
                }
            } else {
                // Si no aplica evaluación, eliminar examen si existe
                $examen = ExamenCurso::where('cod_cursos', $request->codigo)->first();
                if ($examen) {
                    if ($examen->file_ruta && Storage::disk('public')->exists($examen->file_ruta)) {
                        Storage::disk('public')->delete($examen->file_ruta);
                    }
                    $examen->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Curso actualizado correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // SEGURIDAD: No exponer información técnica al frontend en producción
            // Los detalles del error se guardan en logs para debugging interno
            // Antes exponía $e->getMessage() y $e->getLine() que revelan estructura de BD y rutas
            Log::error('Error al actualizar curso', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el curso. Por favor, contacte al administrador.'
            ], 500);
        }
    }

    public function updateCursoHab(Request $request, int $codigo): JsonResponse
    {
        $curso = Cursos::where('codigo', $codigo)->firstOrFail();

        $curso->update([
            'habilitado' => $request->input('habilitado'),
            'fecha_modificacion' => date('Y-m-d\TH:i:s.000')
        ]);

        if ($request->input('habilitado') == 0) {
            ExamenCurso::where('cod_cursos', $curso->codigo)
                ->update(['habilitado' => 0]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Curso y exámenes relacionados actualizados correctamente'
        ]);
    }

    public function destroyCurso(int $codigo): JsonResponse
    {
        DB::beginTransaction();
        try {
            $curso = Cursos::where('codigo', $codigo)->firstOrFail();

            // 1. Delete Sucursales mappings
            DB::table('sw_curso_sucursales')->where('curso_codigo', $curso->codigo)->delete();

            // 2. Delete Examen & Plantilla Archive
            $examen = ExamenCurso::where('cod_cursos', $curso->codigo)->first();
            if ($examen) {
                if ($examen->file_ruta && Storage::disk('public')->exists($examen->file_ruta)) {
                    Storage::disk('public')->delete($examen->file_ruta);
                }
                $examen->delete();
            }

            // 3. Delete Programaciones
            CursoProgramacion::where('cod_cursos', $curso->codigo)->delete();

            // 4. Delete Asistencias / Matriculados / Notas ? 
            // Esto asume que si estaba eliminado, no tiene matriculas o si las tiene se borran (normalmente dependen del codigo_programacion).
            // Lo más seguro es que si el usuario lo puede borrar es porque recién lo creó o es un error.

            // 5. Delete Curso base
            $curso->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'El curso y todos sus registros han sido ELIMINADOS permanentemente.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al destruir curso permanentemente', [
                'error' => $e->getMessage(),
                'codigo' => $codigo
            ]);
            return response()->json([
                'success' => false,
                'message' => 'No se pudo eliminar el curso permanentemente debido a registros dependientes (ej: asistencias).'
            ], 500);
        }
    }

    public function saveCurso(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'tipo_curso' => 'required|integer|exists:sw_capacitacion_tipo_curso,codigo',
                'area_conocimiento' => 'required|exists:sw_capacitacion_areas,codigo',
                'area_responsable' => 'nullable|integer',
                'es_periodico' => 'required|integer|in:0,1',
                'frecuencia' => 'nullable|string',
                'proyeccion_anios' => 'nullable|integer',
                'fechas_generadas' => 'nullable|string',
                'nombre_exa' => 'nullable|string',
                'descripcion' => 'nullable|string',
                'tiempo' => 'nullable|required_if:aplica_evaluacion,1|integer',
                'nota' => 'nullable|required_if:aplica_evaluacion,1|integer',
                'intentos' => 'nullable|required_if:aplica_evaluacion,1|integer',
                'archivo' => 'nullable|file|max:51200',
                'sucursales_asignadas' => 'nullable|array',
                'sucursales_asignadas.*' => 'string',
                'aplica_evaluacion' => 'nullable|integer|in:0,1',
                'obligatorio_alta' => 'nullable|integer|in:0,1',
                'cod_responsable' => 'nullable|string|max:20',
                'target_group' => 'nullable|string|in:TODOS,ADMINISTRATIVO,OPERATIVO',
                'cod_moodle_area' => 'nullable|integer',
                'observaciones' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación.',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $lastCod = Cursos::orderBy('codigo_curso', 'desc')->first();
            if ($lastCod) {
                $lastNumber = intval($lastCod->codigo_curso);
                $newCode = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
            } else {
                $newCode = '10001';
            }

            $periodicidadVal = 0;
            if ($request->input('es_periodico') == 1) {
                switch ($request->input('frecuencia')) {
                    case 'MENSUAL':
                        $periodicidadVal = 12;
                        break;
                    case 'BIMESTRAL':
                        $periodicidadVal = 6;
                        break;
                    case 'TRIMESTRAL':
                        $periodicidadVal = 4;
                        break;
                    case 'CUATRIMESTRAL':
                        $periodicidadVal = 3;
                        break;
                    case 'SEMESTRAL':
                        $periodicidadVal = 2;
                        break;
                    case 'ANUAL':
                        $periodicidadVal = 1;
                        break;
                    default:
                        $periodicidadVal = 0;
                        break;
                }
            }

            $curso = Cursos::create([
                'nombre' => $request->nombre,
                'codigo_curso' => $newCode,
                'tipo_curso' => $request->tipo_curso,
                'area_conocimiento' => $request->area_conocimiento,
                'area' => $request->area_responsable ?? $request->area_conocimiento, // Persistir área específica si existe
                'periodicidad' => $periodicidadVal,
                'es_periodico' => $request->input('es_periodico', 0),
                'frecuencia' => $request->input('frecuencia'),
                'proyeccion_anios' => $request->input('proyeccion_anios'),
                'aplica_evaluacion' => $request->input('aplica_evaluacion', 0),
                'obligatorio_alta' => $request->input('obligatorio_alta', 0),
                'cod_responsable' => $request->input('cod_responsable'),
                'target_group' => $request->input('target_group', 'TODOS'),
                'observaciones' => $request->input('observaciones'),
                'fecha_creacion' => date('Y-m-d\TH:i:s.000')
            ]);

            if (!$curso || !$curso->codigo) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar el curso en la base de datos.'
                ], 500);
            }

            if ($request->has('sucursales_asignadas') && is_array($request->sucursales_asignadas)) {
                $sucursales = $request->sucursales_asignadas;
                foreach ($sucursales as $sucursal) {
                    DB::table('sw_curso_sucursales')->insert([
                        'curso_codigo' => $curso->codigo,
                        'sucursal' => $sucursal,
                        'created_at' => date('Y-m-d\TH:i:s.000'),
                        'updated_at' => date('Y-m-d\TH:i:s.000')
                    ]);
                }
            }

            $tienePlantilla = false;
            $nombreArchivoOriginal = null;
            $tipoArchivo = null;
            $extensionArchivo = null;
            $rutaArchivo = null;
            $nombreArchivoFinal = null;
            $baseNombre = '';

            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');

                if ($archivo->getClientOriginalExtension() !== 'mbz') {
                    return response()->json([
                        'success' => false,
                        'message' => 'El archivo debe ser .mbz',
                        'errors' => ['archivo' => ['El archivo debe ser .mbz']]
                    ], 422);
                }

                $tienePlantilla = true;

                $anio = date('Y');
                $mes = ucfirst(Carbon::now()->translatedFormat('F'));

                $tipoArchivo = $archivo->getClientMimeType();
                $extensionArchivo = $archivo->getClientOriginalExtension();
                $nombreArchivoOriginal = $archivo->getClientOriginalName();

                $baseNombre = 'EXA_' . $newCode . '_' . date('Ymd');
                $carpeta = "plantillas/{$anio}/{$mes}";

                if (!Storage::disk('public')->exists($carpeta)) {
                    Storage::disk('public')->makeDirectory($carpeta);
                }

                $contador = 1;

                do {
                    $nombreArchivoFinal = "{$baseNombre}_{$contador}." . $extensionArchivo;
                    $rutaCompleta = storage_path("app/public/{$carpeta}/{$nombreArchivoFinal}");
                    $contador++;
                } while (file_exists($rutaCompleta));

                $rutaArchivo = $archivo->storeAs($carpeta, $nombreArchivoFinal, 'public');
            }

            // Solo crear examen si aplica evaluación
            if ($request->input('aplica_evaluacion', 0) == 1) {
                $nombreExamen = $request->nombre_exa ?? ("Examen de " . $request->nombre);

                $examen = ExamenCurso::create([
                    'cod_cursos' => $curso->codigo,
                    'nombre' => $nombreExamen,
                    'descripcion' => $request->descripcion,
                    'tiempo' => (int) ($request->tiempo ?? 0),
                    'nota_minima' => (int) ($request->nota ?? 0),
                    'file_tiene' => $tienePlantilla ? 1 : 0,
                    'file_nombre' => $nombreArchivoFinal,
                    'file_ruta' => $rutaArchivo,
                    'file_extension' => $extensionArchivo,
                    'file_tipo' => $tipoArchivo,
                    'file_nombre_original' => $nombreArchivoOriginal,
                    'intentos' => (int) ($request->intentos ?? 0),
                    'cantidad_preguntas' => (int) ($request->cantidad_preguntas ?? 0),
                    'preguntas_balotario' => (int) ($request->preguntas_balotario ?? 0),
                    'fecha_creacion' => date('Y-m-d\TH:i:s.000')
                ]);

                if (!$examen) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al registrar el examen en la base de datos.'
                    ], 500);
                }
            }

            if ($request->has('fechas_generadas')) {
                $fechasArray = json_decode($request->input('fechas_generadas'), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($fechasArray)) {
                    $lastProg = CursoProgramacion::orderBy('codigo_programacion', 'desc')->first();
                    $newProgCod = $lastProg ? intval($lastProg->codigo_programacion) : 1000;

                    foreach ($fechasArray as $fechaItem) {
                        $newProgCod++;
                        CursoProgramacion::create([
                            'codigo_programacion' => str_pad($newProgCod, 4, '0', STR_PAD_LEFT),
                            'cod_cursos'    => $curso->codigo,
                            'periodo'       => $fechaItem['periodo'],
                            'tipo'          => 'REGULAR',
                            'fecha_inicio'  => $fechaItem['inicio'] . 'T00:00:00.000',
                            'fecha_final'   => $fechaItem['final'] . 'T23:59:59.000',
                            'fecha_creacion' => date('Y-m-d\TH:i:s.000'),
                            'habilitado'    => 1,
                        ]);
                    }
                }
            }

            try {
                $nombre = $request->input('nombre');
                $codigo = $curso->codigo;
                $codMoodleArea = $request->input('cod_moodle_area');

                $resMoodle = DB::connection('mysql_grupoihb')->select(
                    "SELECT F_COURSE_crear(?, ?, ?) AS course_id",
                    [$nombre, $codigo, $codMoodleArea]
                );

                if (!empty($resMoodle) && isset($resMoodle[0]->course_id)) {
                    $curso->update(['codigo_moodle' => $resMoodle[0]->course_id]);
                }
            } catch (\Throwable $e) {
                Log::error('Error al ejecutar F_COURSE_crear en MySQL Moodle', [
                    'error' => $e->getMessage(),
                    'newCode' => $newCode ?? null
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Curso y examen registrados correctamente.',
                'success' => true
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al registrar curso', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al registrar el curso. Por favor, contacte al administrador.'
            ], 500);
        }
    }

    public function saveProgramacion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cod_cursos'    => 'required|integer|exists:sw_cursos,codigo',
                // 'periodo'       => 'required|date_format:Y-m', // Ya no es requerido por input
                'tipo'          => 'required|in:REGULAR,EXTEMPORANEO',
                'fecha_inicio'  => 'required|date',
                'fecha_final'   => 'required|date|after_or_equal:fecha_inicio',
                'habilitado'    => 'required|integer|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación.',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $curso = Cursos::where('codigo', $request->cod_cursos)->first();
            if (!$curso) {
                return response()->json([
                    'success' => false,
                    'message' => 'El curso especificado no existe.'
                ], 404);
            }

            // Derivar periodo desde fecha de inicio
            $periodoCalculado = Carbon::parse($request->fecha_inicio)->format('Y-m');

            // Validar Límite de Periodicidad Anual
            $limit = ($curso->periodicidad && $curso->periodicidad > 0) ? $curso->periodicidad : 1;

            // Determinar año objetivo
            $targetYear = substr($periodoCalculado, 0, 4);

            // Contar programaciones NO eliminadas (habilitado=1) en ese AÑO
            $countProgramaciones = CursoProgramacion::where('cod_cursos', $curso->codigo)
                ->where('habilitado', 1)
                ->whereYear('fecha_inicio', $targetYear)
                ->count();

            // Si es un curso NUEVO, el count es 0. Si ya existen N, y N >= Límite, error.
            if ($countProgramaciones >= $limit) {
                return response()->json([
                    'success' => false,
                    'message' => "Este curso tiene un límite anual de {$limit} programación(es) para el año {$targetYear}. Ya se han registrado {$countProgramaciones}."
                ], 409);
            }

            // Lógica de Fechas con Carbon
            // Usar fechas del usuario directamente para AMBOS tipos (REGULAR y EXTEMPORANEO)
            $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay()->format('Y-m-d\TH:i:s.000');
            $fechaFinal  = Carbon::parse($request->fecha_final)->endOfDay()->format('Y-m-d\TH:i:s.000');

            // Validación de Unicidad Compuesta: Curso + Periodo + Tipo
            // Evita duplicar el mismo tipo de curso en el mismo mes
            $existe = CursoProgramacion::where('cod_cursos', $curso->codigo)
                ->where('periodo', $periodoCalculado)
                ->where('tipo', $request->tipo)
                ->where('habilitado', 1) // Ignore deleted records
                ->exists();

            if ($existe) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Ya existe una programación {$request->tipo} para este curso en el periodo {$periodoCalculado}."
                ], 409);
            }

            $lastCod = CursoProgramacion::orderBy('codigo_programacion', 'desc')->first();
            $newCode = $lastCod ? str_pad(intval($lastCod->codigo_programacion) + 1, 4, '0', STR_PAD_LEFT) : '1001';

            $programacion = CursoProgramacion::create([
                'codigo_programacion' => (string) $newCode,
                'cod_cursos'    => (int) $curso->codigo,
                'periodo'       => $periodoCalculado,
                'tipo'          => $request->tipo,
                'fecha_inicio'  => $fechaInicio,
                'fecha_final'   => $fechaFinal,
                'fecha_creacion' => now()->format('Y-m-d\TH:i:s.000'),
                'habilitado'    => (int) $request->habilitado,
            ]);

            if (!$programacion) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Error al registrar.'], 500);
            }

            DB::commit();

            return response()->json([
                'message' => 'Programación registrada correctamente.',
                'success' => true
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error saveProgramacion', ['msg' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => 'Error interno del servidor.'], 500);
        }
    }

    public function updateProgramacion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'codigo'        => 'required|integer|exists:sw_cursos_programacion,codigo',
                'cod_cursos'    => 'required|integer|exists:sw_cursos,codigo',
                // 'periodo'       => 'required|date_format:Y-m', // Ya no requerido
                'tipo'          => 'required|in:REGULAR,EXTEMPORANEO',
                'fecha_inicio'  => 'required|date',
                'fecha_final'   => 'required|date|after_or_equal:fecha_inicio',
                'habilitado'    => 'required|integer|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación.',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $programacion = CursoProgramacion::where('codigo', $request->codigo)->first();
            if (!$programacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'La programación especificada no existe.'
                ], 404);
            }

            // Derivar periodo desde fecha de inicio
            $periodoCalculado = Carbon::parse($request->fecha_inicio)->format('Y-m');

            // Lógica de Fechas con Carbon
            // Usar fechas del usuario directamente para AMBOS tipos
            $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay()->format('Y-m-d\TH:i:s.000');
            $fechaFinal  = Carbon::parse($request->fecha_final)->endOfDay()->format('Y-m-d\TH:i:s.000');

            // Validar unicidad (excluyendo la propia programación)
            $existe = CursoProgramacion::where('cod_cursos', $request->cod_cursos)
                ->where('periodo', $periodoCalculado)
                ->where('tipo', $request->tipo)
                ->where('codigo', '!=', $programacion->codigo)
                ->where('habilitado', 1)
                ->exists();

            if ($existe) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Ya existe otra programación {$request->tipo} para este curso en el periodo {$periodoCalculado}."
                ], 409);
            }

            // Actualizar campos
            $programacion->update([
                'cod_cursos'    => (int) $request->cod_cursos,
                'periodo'       => $periodoCalculado,
                'tipo'          => $request->tipo,
                'fecha_inicio'  => $fechaInicio,
                'fecha_final'   => $fechaFinal,
                'habilitado'    => (int) $request->habilitado,
                'fecha_modificacion' => now()->format('Y-m-d\TH:i:s.000'),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Programación actualizada correctamente.',
                'success' => true
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error updateProgramacion', ['msg' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['success' => false, 'message' => 'Error interno del servidor.'], 500);
        }
    }

    public function updateProgramacionHab(Request $request, int $codigo): JsonResponse
    {
        $programacion = CursoProgramacion::where('codigo', $codigo)->firstOrFail();

        if (!$programacion) {
            return response()->json([
                'message' => 'No se encontró la programación',
                'success' => false
            ], 404);
        }

        $actualizado = $programacion->update([
            'habilitado' => $request->input('habilitado'),
            'fecha_modificacion' => date('Y-m-d\TH:i:s.000')
        ]);

        if (!$actualizado) {
            return response()->json([
                'message' => 'No se pudo actualizar la programación',
                'success' => false
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Programación actualizada correctamente'
        ]);
    }

    // public function getCursoProgramacionXId($id){
    //     if (!is_null($id)) {
    //         $cursos = Cursos::get()
    //         ->map(function ($curso) {
    //             return [
    //                 'codigo' => $curso->codigo,
    //                 'codigoCurso' => $curso->codigo_curso,
    //                 'nombre' => $curso->nombre,
    //                 'habilitado' => $curso->habilitado,
    //             ];
    //         });
    //     } else{
    //         return response()->json([
    //             'message' => 'Ninguna programacion encontrad',
    //             'success' => false
    //         ]);
    //     }

    //     return response()->json($cursos);

    // }

    public function getProgramacionXId(int $id): JsonResponse
    {
        $programacion = CursoProgramacion::where('codigo', $id)->first();

        if (!$programacion) {
            return response()->json([
                'message' => 'No se encontró la programación',
                'success' => false
            ], 404);
        }

        return response()->json([
            'success' => true,
            'programacion' => $programacion
        ]);
    }

    public function getCursoProgramacionXId(int $id): JsonResponse
    {
        $curso = Cursos::where('codigo', $id)->first();

        if (!$curso) {
            return response()->json([
                'message' => 'No se encontró el curso',
                'success' => false
            ], 404);
        }

        $programaciones = CursoProgramacion::with('curso')
            ->where('cod_cursos', $curso->codigo)
            ->where('habilitado', 1)
            ->get();

        if ($programaciones->isEmpty()) {
            return response()->json([
                'message' => 'No se encontraron programaciones para este curso',
                'success' => true
            ]);
        }

        $programacionesMapped = $programaciones->map(function ($prog) {
            return [
                'codigo' => $prog->codigo,
                'codigo_programacion' => $prog->codigo_programacion,
                'cod_cursos' => $prog->cod_cursos,
                'fecha_inicio' => $prog->fecha_inicio,
                'fecha_final' => $prog->fecha_final,
                'fecha_inicio_texto' => Carbon::parse($prog->fecha_inicio)->format('d/m/Y'),
                'fecha_final_texto' => Carbon::parse($prog->fecha_final)->format('d/m/Y'),
                'periodo' => $prog->periodo,
                'tipo' => $prog->tipo,
                'habilitado' => $prog->habilitado,
                'curso' => $prog->curso // Si se necesita
            ];
        });

        return response()->json([
            'success' => true,
            'programaciones' => $programacionesMapped
        ]);
    }

    public function getAreas(): JsonResponse
    {
        $areas = CapacitacionAreas::where('habilitado', 1)->get();
        return response()->json($areas);
    }

    public function getClientesForPAC(): JsonResponse
    {
        $raw = DB::select('EXEC SW_LISTAR_CLIENTES');
        $clientes = collect($raw)->map(function ($row) {
            return [
                'codigo'      => $row->codigo,
                'descripcion' => $row->abreviatura ?? $row->razon_social ?? '',
            ];
        })->sortBy('descripcion')->values();
        return response()->json($clientes);
    }

    public function getEmpresasList(): JsonResponse
    {
        $empresas = DB::table('sw_MIGRA_EMPRESA')
            ->select('EMPR_CODIGO as codigo', 'Razon_Social as descripcion')
            ->whereIn('EMPR_CODIGO', ['01', '02', '03', '04', '05', '06'])
            ->orderBy('EMPR_CODIGO')
            ->get();
        return response()->json($empresas);
    }


    public function storeProgramacionManual(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'cod_cursos'   => 'required|integer|exists:sw_cursos,codigo',
                'fecha_inicio' => 'required|date_format:Y-m',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos.',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $curso = Cursos::findOrFail($request->cod_cursos);

            $fechaBase = Carbon::parse($request->fecha_inicio . '-01');
            $periodo = $fechaBase->format('Y-m');
            $fInicio = $fechaBase->startOfMonth()->format('Y-m-d\TH:i:s.000');
            $fFinal  = $fechaBase->endOfMonth()->format('Y-m-d\TH:i:s.000');

            $lastCod = CursoProgramacion::orderBy('codigo_programacion', 'desc')->first();
            $newCode = $lastCod ? str_pad(intval($lastCod->codigo_programacion) + 1, 4, '0', STR_PAD_LEFT) : '1001';

            CursoProgramacion::create([
                'codigo_programacion' => (string) $newCode,
                'cod_cursos'    => (int) $curso->codigo,
                'periodo'       => $periodo,
                'tipo'          => 'REGULAR',
                'fecha_inicio'  => $fInicio,
                'fecha_final'   => $fFinal,
                'fecha_creacion' => now()->format('Y-m-d\TH:i:s.000'),
                'habilitado'    => 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Programación creada exitosamente. Ahora puede matricular personal desde la pestaña de Matrículas.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error storeProgramacionManual', ['error' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la apertura de ciclo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTipoCursos(): JsonResponse
    {
        $tipoCursos = CapacitacionTipoCurso::where('habilitado', 1)->get();
        return response()->json($tipoCursos);
    }


    public function analizarPlantilla(Request $request): JsonResponse
    {
        if (!$request->hasFile('plantilla')) {
            return response()->json([
                'success' => false,
                'message' => 'No se envió el archivo.'
            ], 400);
        }

        $file = $request->file('plantilla');
        $tempPath = storage_path('app/temp_mbz_' . uniqid());

        if (File::isDirectory($tempPath)) {
            File::deleteDirectory($tempPath);
        }
        File::makeDirectory($tempPath, 0777, true);

        try {
            $filePath = $tempPath . '/' . $file->getClientOriginalName();
            $file->move($tempPath, $file->getClientOriginalName());

            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (in_array($ext, ['gz', 'mbz'])) {
                try {
                    $phar = new \PharData($filePath);
                    $tarPath = preg_replace('/\.gz$/i', '', $filePath);
                    $phar->decompress(); // genera .tar
                    $pharTar = new \PharData($tarPath);
                    $pharTar->extractTo($tempPath, null, true);
                    @unlink($tarPath);
                } catch (\Throwable $e) {
                }
            }

            if ($ext === 'zip' || !File::isDirectory($tempPath . '/course')) {
                $zip = new \ZipArchive;
                if ($zip->open($filePath) === true) {
                    $zip->extractTo($tempPath);
                    $zip->close();
                }
            }

            $findFile = function (string $base, string $name) {
                $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
                foreach ($rii as $f) {
                    if ($f->isFile() && $f->getFilename() === $name) {
                        return $f->getPathname();
                    }
                }
                return null;
            };

            $xmlFile = $findFile($tempPath, 'moodle_backup.xml');
            if (!$xmlFile) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo no contiene moodle_backup.xml'
                ], 400);
            }

            $xml = simplexml_load_file($xmlFile);
            $info = $xml->information ?? null;
            if (!$info) {
                return response()->json([
                    'success' => false,
                    'message' => 'moodle_backup.xml no contiene la sección <information>.'
                ], 400);
            }

            $courseName    = (string) ($info->original_course_fullname ?? 'Sin nombre');
            $courseShort   = (string) ($info->original_course_shortname ?? '');
            $backupDate    = (string) ($info->backup_date ?? null);
            $moodleVersion = (string) ($info->moodle_version ?? null);

            $totalSections   = 0;
            $totalActivities = 0;
            if (isset($info->contents->sections->section)) {
                $sectionsNode = $info->contents->sections->section;
                $totalSections = is_array($sectionsNode) ? count($sectionsNode) : count(iterator_to_array($sectionsNode));
                $totalSections = count($info->contents->sections->section);
            }

            if (isset($info->contents->activities->activity)) {
                $totalActivities = count($info->contents->activities->activity);
            }

            $activityStats = [];

            $mapaTipos = [
                'quiz' => 'Examen',
                'label' => 'Diapositivas',
                'assign' => 'Tarea',
                'forum' => 'Foro',
                'resource' => 'Recurso',
                'url' => 'Enlace',
                'page' => 'Página',
                'other' => 'Otro'
            ];

            if (isset($info->contents->activities->activity)) {
                foreach ($info->contents->activities->activity as $act) {
                    $mod = (string) $act->modulename;
                    if ($mod === '') $mod = 'other';

                    $nombre = $mapaTipos[$mod] ?? ucfirst($mod);

                    if (!isset($activityStats[$nombre])) {
                        $activityStats[$nombre] = 0;
                    }
                    $activityStats[$nombre]++;
                }
            }

            $totalQuestions = 0;
            $questionsFile = $findFile($tempPath, 'questions.xml');
            if ($questionsFile && file_exists($questionsFile)) {
                $questionsDoc = simplexml_load_file($questionsFile);
                foreach ($questionsDoc->question_category as $category) {
                    if (isset($category->questions->question)) {
                        foreach ($category->questions->question as $q) {
                            if (((string)$q->parent) === "0") {
                                $totalQuestions++;
                            }
                        }
                    }
                }
            } else {
                if (isset($xml->questions->question_category)) {
                    foreach ($xml->questions->question_category as $category) {
                        if (isset($category->questions->question)) {
                            foreach ($category->questions->question as $q) {
                                if (((string)$q->parent) === "0") $totalQuestions++;
                            }
                        }
                    }
                }
            }

            return response()->json([
                'success' => true,
                'courseName' => $courseName,
                'courseShortname' => $courseShort,
                'backupDate' => $backupDate,
                'moodleVersion' => $moodleVersion,
                'totalSections' => (int)$totalSections,
                'totalActivities' => (int)$totalActivities,
                'activityStats' => $activityStats,
                'totalQuestions' => (int)$totalQuestions
            ]);
        } catch (\Throwable $e) {
            // Loggear detalles para debugging
            Log::error('Error procesando plantilla', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la plantilla. Verifique el archivo e inténtelo nuevamente.'
            ], 500);
        } finally {
            if (File::isDirectory($tempPath)) {
                File::deleteDirectory($tempPath);
            }
        }
    }

    public function saveMatricula(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cursoId'      => 'required|integer|exists:sw_cursos,codigo',
            'programacionId' => 'required|integer|exists:sw_cursos_programacion,codigo',
            'personalIds'  => 'required|array|max:100',
            'personalIds.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors()
            ], 422);
        }

        $cursoId = $request->cursoId;
        $programacionId = $request->programacionId; // Capturar programacionId
        $personalIds = $request->personalIds;

        // Validación adicional del límite de 100
        if (count($personalIds) > 100) {
            return response()->json([
                'success' => false,
                'message' => 'No puede matricular más de 100 personas por operación.'
            ], 422);
        }

        // Obtener el ID del usuario autenticado
        $usuarioId = Auth::id();
        Log::info('CapacitacionController->saveMatricula: Usuario autenticado ID: ' . $usuarioId);

        // Despachamos el trabajo principal a la cola.
        // Este job se encargará de crear los trabajos individuales para cada persona.
        DispatchMatriculaBatchJob::dispatch($cursoId, $programacionId, $personalIds, $usuarioId);

        // Respondemos inmediatamente al usuario.
        // El código 202 "Accepted" es el estándar para indicar que la solicitud
        // ha sido aceptada para procesamiento, pero este aún no ha terminado.
        return response()->json([
            'success' => true,
            'message' => 'La matriculación ha sido puesta en cola. Recibirá una notificación cuando termine.'
        ], 202);
    }

    /**
     * Obtener todas las matrículas de un curso específico
     * GET /api/get-matriculas-curso/{cursoId}
     */
    public function getMatriculasPorCurso(int $cursoId): JsonResponse
    {
        try {
            $curso = Cursos::find($cursoId);

            if (!$curso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado'
                ], 404);
            }

            $matriculas = Matricula::where('cod_curso', $cursoId)
                ->with(['personal'])
                ->orderBy('fecha_matricula', 'desc')
                ->get()
                ->map(function ($matricula) {
                    $personal = $matricula->personal;
                    return [
                        'codigo' => $matricula->codigo,
                        'cod_personal' => $matricula->cod_personal,
                        'nombre_completo' => $personal
                            ? trim("{$personal->ape_paterno} {$personal->ape_materno} {$personal->nombres}")
                            : 'No encontrado',
                        'dni' => $personal->dni ?? 'N/A',
                        'correo' => $personal->correo ?? 'N/A',
                        'cargo' => $personal->cargo ?? 'N/A',
                        'fecha_matricula' => $matricula->fecha_matricula,
                        'estado' => $matricula->estado ?? 'MATRICULADO',
                        'tipo_matricula' => $matricula->tipo_matricula ?? 'VIRTUAL',
                        'origen_matricula' => $matricula->origen_matricula ?? 'INTRANET',
                    ];
                });

            return response()->json([
                'success' => true,
                'curso' => [
                    'codigo' => $curso->codigo,
                    'nombre' => $curso->nombre,
                    'codigo_curso' => $curso->codigo_curso
                ],
                'matriculas' => $matriculas,
                'total' => $matriculas->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener matrículas del curso', [
                'curso_id' => $cursoId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las matrículas del curso'
            ], 500);
        }
    }

    /**
     * Obtener historial de capacitaciones de un empleado
     * GET /api/get-historial-capacitaciones/{personalId}
     */
    public function getHistorialCapacitaciones(string $personalId): JsonResponse
    {
        try {
            // Optimización: Consultar directamente las matrículas sin pasar por el modelo Personal
            // Esto evita errores de conversión de tipos en IDs alfanuméricos (ej. 'P0056')
            $historial = DB::table('sw_matriculas as m')
                ->join('sw_cursos as c', 'm.cod_curso', '=', 'c.codigo')
                ->leftJoin('sw_cursos_programacion as prog', 'm.cod_programacion', '=', 'prog.codigo')
                ->where('m.cod_personal', $personalId)
                ->select([
                    'c.codigo as codigo_curso',
                    'c.nombre as nombre_curso',
                    'm.tipo_matricula',
                    'm.fecha_matricula',
                    'm.estado',
                    'prog.fecha_inicio',
                    'prog.fecha_final'
                ])
                ->orderBy('m.fecha_matricula', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'historial' => $historial
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener historial de capacitaciones', [
                'personal_id' => $personalId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el historial de capacitaciones'
            ], 500);
        }
    }

    /**
     * Buscar personal para consulta de historial
     * GET /api/buscar-personal-capacitacion
     */
    public function buscarPersonalCapacitacion(Request $request): JsonResponse
    {
        Log::info('buscarPersonalCapacitacion: Inicio', $request->all());
        try {
            // 1. Obtener personal ADMINISTRATIVO 5 de la fuente oficial (si_solm.dbo.PERSONAL)
            $rawPersonal = DB::connection('sqlsrv')->select("
                SELECT 
                    P.CODI_PERS as codigo,
                    LTRIM(RTRIM(P.APEL_1 + ' ' + ISNULL(P.APEL_2, '') + ' ' + P.NOMB_1 + ' ' + ISNULL(P.NOMB_2, ''))) as personal,
                    P.NRO_DOCU_IDEN as nroDoc,
                    S.SUCU_ABREVIATURA as sucursal,
                    P.PERS_TIPOTRAB as TIPOTRAB,
                    P.PERS_VIGENCIA as VIGENCIA
                FROM si_solm.dbo.PERSONAL P
                LEFT JOIN dbo.sw_MIGRA_SISO_SUCURSAL S ON P.SUCU_CODIGO = S.SUCU_CODIGO
                WHERE P.PERS_TIPOTRAB = '05' 
                  AND P.PERS_VIGENCIA = 'SI'
            ");

            // El filtro de tipo_responsable ya se aplica en el query SQL, no es necesario un filtro manual adicional.
            // Se unifica el mapeo y el filtro de búsqueda en una sola pasada.
            $personal = [];
            $searchTerm = strtoupper(trim($request->input('q', '')));

            foreach ($rawPersonal as $p) {
                $nombre = strtoupper($p->personal);
                $dni = $p->nroDoc;
                $cod = $p->codigo;

                if ($searchTerm !== '') {
                    if (strpos($nombre, $searchTerm) === false && strpos($dni, $searchTerm) === false) {
                        continue;
                    }
                }

                $personal[] = [
                    'codigo' => trim($cod),
                    'nombre_completo' => $nombre,
                    'dni' => $dni,
                    'cargo' => $p->TIPOTRAB ?? 'N/A', // Usar TIPOTRAB del resultado SQL
                    'sucursal' => $p->sucursal ?? 'N/A'
                ];
            }
            // -------------------------------------------------------------

            // 2. Cargar conteos de matrículas (Optimizado: una sola query para todos)
            // Se usa el JOIN con sw_cursos para que coincida exactamente con las filas del Historial (Modal)
            $matriculasCounts = DB::table('sw_matriculas as m')
                ->join('sw_cursos as c', 'm.cod_curso', '=', 'c.codigo')
                ->select('m.cod_personal', DB::raw('count(*) as total'))
                ->groupBy('m.cod_personal')
                ->pluck('total', 'm.cod_personal')
                ->toArray();

            // --- VERIFICAR MATRÍCULA EN CURSO ACTUAL (SAFE) ---
            $matriculadosEnCurso = [];
            if ($request->filled('cursoId')) {
                try {
                    $cursoId = $request->cursoId;
                    // Obtener códigos de personal ya matriculados en este curso
                    // Se usa try-catch y query simple para evitar 500 errors si faltan columnas
                    $matriculadosEnCurso = Matricula::where('cod_curso', $cursoId)
                        ->pluck('cod_personal')
                        ->map(fn($id) => (string)$id)
                        ->toArray();
                } catch (\Exception $e) {
                    Log::error('Error verificando enrollments: ' . $e->getMessage());
                    $matriculadosEnCurso = [];
                }
            }
            // ------------------------------------------

            // 3. Mapear resultados en memoria
            $personal = array_map(function ($p) use ($matriculasCounts, $matriculadosEnCurso) {
                // Estandarización de campos
                $codigo = $p->CODI_PERS ?? $p->codi_pers ?? $p->codigo ?? '';
                $nombre = $p->personal ?? $p->nombre ?? $p->nombre_completo ?? 'Desconocido';
                $dni = $p->nroDoc ?? $p->dni ?? $p->NRO_DOCU_IDEN ?? '';
                // Se lee desde cargo o TIPOTRAB, ya que SW_LISTAR_PERSONAL_X_SUCURSAL devuelve TIPOTRAB con "ADMIN"/"OPER"
                $cargo = $p->cargo ?? $p->desc_cargo ?? $p->TIPOTRAB ?? 'N/A';
                $sucursal = $p->sucursal ?? 'N/A';

                return [
                    'codigo' => $codigo,
                    'nombre_completo' => $nombre,
                    'dni' => $dni,
                    'cargo' => $cargo,
                    'sucursal' => $sucursal,
                    'matriculado' => in_array((string)$codigo, $matriculadosEnCurso),
                    'total_capacitaciones' => $matriculasCounts[$codigo] ?? 0
                ];
            }, $rawPersonal);

            // --- NUEVO: FILTRO INTELIGENTE PAC ---
            if ($request->filled('cursoId')) {
                $cursoId = $request->cursoId;
                // Obtener sucursales asignadas al curso
                $sucursalesPermitidas = DB::table('sw_curso_sucursales')
                    ->where('curso_codigo', $cursoId)
                    ->pluck('sucursal')
                    ->map(fn($s) => strtoupper(trim($s))) // Normalizar
                    ->toArray();

                if (!empty($sucursalesPermitidas)) {
                    $personal = array_filter($personal, function ($item) use ($sucursalesPermitidas) {
                        return in_array(strtoupper(trim($item['sucursal'])), $sucursalesPermitidas);
                    });
                }
            }
            // -------------------------------------

            // 4. Filtrado opcional del lado del servidor
            $termino = strtolower($request->input('q', ''));
            if (!empty($termino)) {
                $personal = array_filter($personal, function ($item) use ($termino) {
                    return str_contains(strtolower($item['nombre_completo'] ?? ''), $termino) ||
                        str_contains(strval($item['dni'] ?? ''), $termino);
                });
            }

            // Filtrado por sucursal
            $sucursal = $request->input('sucursal', '');
            if (!empty($sucursal)) {
                $personal = array_filter($personal, function ($item) use ($sucursal) {
                    return strtoupper(trim($item['sucursal'])) === strtoupper(trim($sucursal));
                });
            }

            // Re-indexar array después de filtrar
            $personal = array_values($personal);
            Log::info('buscarPersonalCapacitacion: Resultados', ['total' => count($personal)]);

            return response()->json([
                'success' => true,
                'personal' => $personal,
                'total' => count($personal)
            ]);
        } catch (\Exception $e) {
            Log::error('Error al buscar personal para capacitación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la lista de personal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener combos para el modal de apertura de ciclo (Sedes, Clientes, Áreas)
     * GET /api/capacitacion/combos-apertura
     */
    public function getCombosApertura(): JsonResponse
    {
        try {
            $sucursales = DB::table('sw_MIGRA_SISO_SUCURSAL')
                ->select('SUCU_CODIGO as codigo', 'SUCU_ABREVIATURA as nombre')
                ->whereNotNull('SUCU_ABREVIATURA')
                ->distinct()
                ->orderBy('SUCU_ABREVIATURA')
                ->get();

            $clientes = DB::table('sw_clientes')
                ->select('codigo', 'abreviatura as nombre')
                ->where('habilitado', 1)
                ->orderBy('abreviatura')
                ->get();

            $areas = DB::table('sw_MIGRA_REDO_AREA')
                ->select('AREA_CODIGO as codigo', 'AREA_DESCRIPCION as nombre')
                ->orderBy('AREA_DESCRIPCION')
                ->get();

            return response()->json([
                'success' => true,
                'sucursales' => $sucursales,
                'clientes' => $clientes,
                'areas' => $areas
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener lista de sucursales
                'message' => 'Error al cargar sucursales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vista de consulta de matrículas
     */
    public function vistaConsultaMatriculas(): View
    {
        return view('capacitacion.consulta_matriculas');
    }

    /**
     * Vista de historial de capacitaciones
     */
    public function vistaHistorialCapacitaciones(): View
    {
        return view('capacitacion.historial_capacitaciones');
    }

    /**
     * Listar matrículas de un curso usando MigraPersonal y sw_matriculas (JOIN robusto con logging de errores)
     * Devuelve datos personales y fecha de matrícula
     */
    public function getMatriculasMigraPersonal(int $cursoId): JsonResponse
    {
        try {
            // 1. Obtener todas las matrículas del curso (Base de la verdad: 1105 registros)
            $matriculas = DB::table('sw_matriculas as m')
                ->leftJoin('sw_cursos_programacion as prog', 'm.cod_programacion', '=', 'prog.codigo')
                ->where('m.cod_curso', '=', $cursoId)
                ->select([
                    'm.cod_personal',
                    'm.fecha_matricula',
                    'm.estado',
                    'prog.fecha_inicio as prog_fecha_inicio',
                    'prog.fecha_final as prog_fecha_final',
                ])
                ->get();

            if ($matriculas->isEmpty()) {
                return response()->json(['success' => true, 'matriculas' => [], 'total' => 0]);
            }

            $codigosPersonal = $matriculas->pluck('cod_personal')
                ->map(fn($id) => str_pad(trim((string)$id), 5, '0', STR_PAD_LEFT))
                ->unique()
                ->values()
                ->toArray();

            $personalData = collect();

            $chunks = array_chunk($codigosPersonal, 2000);
            foreach ($chunks as $chunk) {
                $batch = DB::table('si_solm.dbo.PERSONAL')
                    ->whereIn('CODI_PERS', $chunk)
                    ->select([
                        'CODI_PERS as cod_personal',
                        'NRO_DOCU_IDEN as dni',
                        DB::raw("LTRIM(RTRIM(ISNULL(APEL_1, ''))) + ' ' + LTRIM(RTRIM(ISNULL(APEL_2, ''))) + ' ' + LTRIM(RTRIM(ISNULL(NOMB_1, ''))) + ' ' + LTRIM(RTRIM(ISNULL(NOMB_2, ''))) as nombre_completo"),
                        'PERS_EMAIL as correo',
                        'CODI_CARG as cargo',
                        'SUCU_CODIGO',
                        'EMPR_CODIGO',
                    ])
                    ->get()
                    ->map(function ($item) {
                        $item->cod_personal = str_pad(trim($item->cod_personal), 5, '0', STR_PAD_LEFT);
                        return $item;
                    });

                $personalData = $personalData->merge($batch);
            }

            $personalData = $personalData->keyBy('cod_personal');

            $codigosSucursal = $personalData->pluck('SUCU_CODIGO')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $sucursalesMap = [];
            $sucursalClienteMap = [];
            if (!empty($codigosSucursal)) {
                $sucChunks = array_chunk($codigosSucursal, 2000);
                $sucRows = collect();
                foreach ($sucChunks as $chunk) {
                    $sucRows = $sucRows->merge(
                        DB::table('sw_MIGRA_SISO_SUCURSAL')
                            ->whereIn('SUCU_CODIGO', $chunk)
                            ->select('SUCU_CODIGO', 'SUCU_ABREVIATURA', 'EMPR_CODIGO')
                            ->get()
                    );
                }
                foreach ($sucRows as $suc) {
                    $sucursalesMap[$suc->SUCU_CODIGO]      = $suc->SUCU_ABREVIATURA;
                    $sucursalClienteMap[$suc->SUCU_CODIGO] = $suc->EMPR_CODIGO;
                }
            }

            $curso = Consulta::obtenerTipoDeCurso($cursoId);

            $tipoDesc = $curso ? strtoupper($curso->tipo_descripcion ?? '') : '';
            $esPCU = str_contains($tipoDesc, 'PCU');
            $esPCI = str_contains($tipoDesc, 'PCI');
            $esPCE = str_contains($tipoDesc, 'PCE');
            Log::info("[ClienteEmpresa] cursoId={$cursoId} tipoDesc={$tipoDesc} esPCU=" . ($esPCU ? 'SI' : 'NO') . " esPCI=" . ($esPCI ? 'SI' : 'NO') . " esPCE=" . ($esPCE ? 'SI' : 'NO'));

            $sucursalClienteNameMap = [];
            if ($esPCU) {
                $assignedClients = DB::table('sw_curso_sucursales')
                    ->where('curso_codigo', $cursoId)
                    ->pluck('sucursal')
                    ->toArray();

                if (!empty($assignedClients)) {
                    $clientDetails = DB::table('sw_clientes')
                        ->whereIn('codigo', $assignedClients)
                        ->get(['codigo', 'cod_legacy', 'abreviatura', 'razon_social']);

                    foreach ($clientDetails as $cd) {
                        $legacyCode = $cd->cod_legacy;
                        $clientName = $cd->abreviatura ?? $cd->razon_social ?? (string)$cd->codigo;

                        if ($legacyCode) {
                            $externalSucursales = DB::connection('sqlsrv_controlclientes')
                                ->select('EXEC USP_LISTAR_SUCURSALES_X_CLIENTE :cod_legacy', ['cod_legacy' => $legacyCode]);

                            foreach ($externalSucursales as $es) {
                                if (isset($es->codigo_sucursal)) {
                                    $sucursalClienteNameMap[trim($es->codigo_sucursal)] = $clientName;
                                }
                            }
                        }
                    }
                }
            }

            // 5c. Para PCI y PCE: cargar mapa de empresas internas (Normalizado a 2 dígitos)
            $empresasMap = [];
            if ($esPCI || $esPCE) {
                $empresas = DB::table('sw_MIGRA_EMPRESA')
                    ->select('EMPR_CODIGO', 'Razon_Social')
                    ->get();
                foreach ($empresas as $e) {
                    // Normalizar a 2 dígitos (ej: "1" -> "01")
                    $code = str_pad(trim($e->EMPR_CODIGO), 2, '0', STR_PAD_LEFT);
                    $empresasMap[$code] = $e->Razon_Social;
                }
            }

            // 6. Unir datos en memoria
            $resultado = $matriculas->map(function ($m) use ($personalData, $sucursalesMap, $sucursalClienteNameMap, $esPCU, $esPCI, $esPCE, $empresasMap) {
                $id = str_pad(trim((string)$m->cod_personal), 5, '0', STR_PAD_LEFT);
                $p = $personalData->get($id);

                // Resolver cliente/empresa según tipo de curso
                // PCU: personal.SUCU_CODIGO → sw_clientes.abreviatura
                // PCI / PCE: personal.EMPR_CODIGO → sw_MIGRA_EMPRESA.Razon_Social
                $clienteEmpresa = '-';
                if ($esPCU && $p && isset($p->SUCU_CODIGO)) {
                    $clienteEmpresa = $sucursalClienteNameMap[trim($p->SUCU_CODIGO)] ?? '-';
                } elseif (($esPCI || $esPCE) && $p && isset($p->EMPR_CODIGO)) {
                    $codeNormalizer = str_pad(trim($p->EMPR_CODIGO), 2, '0', STR_PAD_LEFT);
                    $clienteEmpresa = $empresasMap[$codeNormalizer] ?? $p->EMPR_CODIGO;
                }

                return [
                    'cod_personal' => $id,
                    'dni' => $p->dni ?? 'N/A',
                    'nombre_completo' => $p->nombre_completo ?? 'Personal no encontrado (Retirado)',
                    'correo' => $p->correo ?? 'N/A',
                    'cargo' => $p->cargo ?? 'N/A',
                    'cliente_empresa' => $clienteEmpresa,
                    'fecha_matricula' => $m->fecha_matricula,
                    'estado' => $m->estado,
                    'prog_fecha_inicio' => $m->prog_fecha_inicio,
                    'prog_fecha_final' => $m->prog_fecha_final,
                    'sucursal' => (isset($p->SUCU_CODIGO) && isset($sucursalesMap[$p->SUCU_CODIGO]))
                        ? $sucursalesMap[$p->SUCU_CODIGO]
                        : 'Sin sede'
                ];
            })->sortBy('nombre_completo')->values();

            return response()->json([
                'success' => true,
                'matriculas' => $resultado,
                'total' => $resultado->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error en getMatriculasMigraPersonal rediseñado: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar matrículas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAlertasVencimiento(): JsonResponse
    {
        try {
            $hoy = Carbon::now()->startOfDay();
            $limite = $hoy->copy()->addDays(15)->endOfDay();

            $programacionesVigentes = Consulta::obtenerProgramacionesVigentes();

            $alertas = [];

            foreach ($programacionesVigentes as $programacion) {
                if (empty($programacion->frecuencia)) {
                    continue;
                }

                $fechaInicioProgramacion = Carbon::parse($programacion->fecha_inicio)->startOfDay();
                $fechaProximaClonacion = $fechaInicioProgramacion->copy();

                // Quitar espacios extra en la base de datos SQL Server e identificar tipo
                $frecuencia = trim(strtoupper($programacion->frecuencia));
                switch ($frecuencia) {
                    case 'MENSUAL':
                        $fechaProximaClonacion->addMonth();
                        break;
                    case 'BIMESTRAL':
                        $fechaProximaClonacion->addMonths(2);
                        break;
                    case 'TRIMESTRAL':
                        $fechaProximaClonacion->addMonths(3);
                        break;
                    case 'CUATRIMESTRAL':
                        $fechaProximaClonacion->addMonths(4);
                        break;
                    case 'SEMESTRAL':
                        $fechaProximaClonacion->addMonths(6);
                        break;
                    case 'ANUAL':
                        $fechaProximaClonacion->addYear();
                        break;
                    default:
                        continue 2; // Salta al siguiente iterador del loop
                }

                // Condición ESTRICTA: fecha_proxima_clonacion BETWEEN hoy AND (hoy + 15 días)
                // Se utiliza greaterThanOrEqualTo (>= hoy) y lessThanOrEqualTo (<= limite de 15 días)
                if ($fechaProximaClonacion->greaterThanOrEqualTo($hoy) && $fechaProximaClonacion->lessThanOrEqualTo($limite)) {
                    $diasRestantes = $hoy->diffInDays($fechaProximaClonacion, false);
                    $alertas[] = [
                        'codigo_curso' => $programacion->codigo_curso,
                        'nombre' => $programacion->curso_nombre,
                        'fecha_inicio_actual' => $fechaInicioProgramacion->format('Y-m-d'),
                        'fecha_proxima_clonacion' => $fechaProximaClonacion->format('Y-m-d'),
                        'dias_restantes' => ceil($diasRestantes)
                    ];
                }
            }

            Log::info('Cursos por vencer auditar:', $alertas);

            return response()->json([
                'success' => true,
                'alertas' => $alertas,
                'total' => count($alertas)
            ]);
        } catch (\Exception $e) {
            Log::error('Error en getAlertasVencimiento: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Error al obtener alertas de cursos'], 500);
        }
    }

    public function getSucursales(): JsonResponse
    {
        try {
            $sucursales = Consulta::obtenerSucursales();
            return response()->json(['success' => true, 'sucursales' => $sucursales]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    public function getAreasEncargadas(): JsonResponse
    {
        try {
            $areas = Consulta::obtenerAreasEncargadas();
            return response()->json([
                'success' => true,
                'areas' => $areas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener áreas encargadas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAreasPorSistema(int $sistemaId): JsonResponse
    {
        try {
            $areas = Consulta::obtenerAreasPorSistema($sistemaId);
            return response()->json([
                'success' => true,
                'areas' => $areas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener áreas por sistema: ' . $e->getMessage()
            ], 500);
        }
    }
}
