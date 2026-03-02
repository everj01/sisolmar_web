<?php

namespace App\Http\Controllers;

use App\Mail\MatriculaNotificacion;
use App\Models\Areas;
use App\Models\CapacitacionAreas;
use App\Models\CapacitacionTipoCurso;
use App\Models\Cargo;
use App\Models\CursoProgramacion;
use App\Models\Cursos;
use App\Models\FileControl;
use App\Models\TipoCurso;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ExamenCurso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Mail;
use Psy\Readline\Hoa\Console;
use App\Jobs\DispatchMatriculaBatchJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use App\Models\Personal;
use App\Models\Matricula;
use Illuminate\Support\Str;

class CapacitacionController extends Controller
{


    public function index(Request $request, $op = null)
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

        $cursos = $query->get()->map(function ($curso) {
            return [
                'codigo' => $curso->codigo,
                'codigoCurso' => $curso->codigo_curso,
                'nombre' => $curso->nombre,
                'habilitado' => $curso->habilitado,
                'periodicidad' => $curso->periodicidad,
                'es_periodico' => $curso->es_periodico,
                'frecuencia' => $curso->frecuencia,
                'proyeccion_anios' => $curso->proyeccion_anios,
            ];
        });

        return response()->json($cursos);
    }


    // ...existing code...

    public function getCursoExamenXId($id)
    {
        $curso = Cursos::with(['examen', 'tipoCurso'])->where('codigo', $id)->firstOrFail();

        // Obtener sucursales asignadas
        $sucursales = DB::table('sw_curso_sucursales')
            ->where('curso_codigo', $curso->codigo)
            ->pluck('sucursal');
            
        $curso->sucursales = $sucursales;

        return response()->json([
            'success' => true,
            'curso' => $curso
        ]);
    }

    public function updateCurso(Request $request){

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'tipo_curso'=> 'required|integer|exists:sw_capacitacion_tipo_curso,codigo',
            'area'=> 'required|string|max:255',
            'es_periodico'=> 'required|integer|in:0,1',
            'frecuencia'=> 'nullable|string',
            'proyeccion_anios'=> 'nullable|integer',
            'fechas_generadas'=> 'nullable|string',
            'nombre_exa' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'tiempo' => 'required|integer',
            'nota' => 'required|integer',
            'intentos' => 'required|integer',
            'archivo' => 'nullable|file|max:51200', // hasta 50 MB
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
                    case 'MENSUAL': $periodicidadVal = 12; break;
                    case 'BIMESTRAL': $periodicidadVal = 6; break;
                    case 'TRIMESTRAL': $periodicidadVal = 4; break;
                    case 'CUATRIMESTRAL': $periodicidadVal = 3; break;
                    case 'SEMESTRAL': $periodicidadVal = 2; break;
                    case 'ANUAL': $periodicidadVal = 1; break;
                    default: $periodicidadVal = 0; break;
                }
            }

            $curso->update([
                'nombre' => $request->nombre,
                'tipo_curso' => $request->tipo_curso,
                'area' => $request->area,
                'periodicidad' => $periodicidadVal,
                'es_periodico' => $request->input('es_periodico'),
                'frecuencia' => $request->input('frecuencia'),
                'proyeccion_anios' => $request->input('proyeccion_anios'),
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
                                'fecha_creacion'=> date('Y-m-d\TH:i:s.000'),
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

            $examen = ExamenCurso::where('cod_cursos', $request->codigo)->firstOrFail();

            // Auto-generar nombre si no viene (usuario eliminó campo)
            $nombreExamen = $request->nombre_exa ?? ("Examen de " . $request->nombre);

            $examen->update([
                'nombre' => $nombreExamen,
                'descripcion' =>  $request->descripcion,
                'tiempo' => $request->tiempo,
                'nota_minima' => $request->nota,
                'intentos' => $request->intentos,
                'fecha_modificacion' => date('Y-m-d\TH:i:s.000')
            ]);

            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');

                if ($examen->file_ruta && Storage::disk('public')->exists($examen->file_ruta)) {
                    Storage::disk('public')->delete($examen->file_ruta);
                }

                if ($archivo->getClientOriginalExtension() !== 'mbz') {
                    return back()->withErrors(['archivo' => 'El archivo debe ser .mbz']);
                }

                $tienePlantilla = true;

                $anio = date('Y');
                $mes = ucfirst(\Carbon\Carbon::now()->translatedFormat('F'));

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

    public function updateCursoHab(Request $request, $codigo)
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

    public function destroyCurso($codigo)
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

    public function saveCurso(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'tipo_curso'=> 'required|integer|exists:sw_capacitacion_tipo_curso,codigo',
                'area'=> 'required|exists:sw_capacitacion_areas,codigo',
                'es_periodico'=> 'required|integer|in:0,1',
                'frecuencia'=> 'nullable|string',
                'proyeccion_anios'=> 'nullable|integer',
                'fechas_generadas'=> 'nullable|string',
                'nombre_exa' => 'nullable|string',
                'descripcion' => 'nullable|string',
                'tiempo' => 'required|integer',
                'nota' => 'required|integer',
                'intentos' => 'required|integer',
                'archivo' => 'nullable|file|max:51200',
                'sucursales_asignadas' => 'nullable|array', // Nuevo campo
                'sucursales_asignadas.*' => 'string'
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
                    case 'MENSUAL': $periodicidadVal = 12; break;
                    case 'BIMESTRAL': $periodicidadVal = 6; break;
                    case 'TRIMESTRAL': $periodicidadVal = 4; break;
                    case 'CUATRIMESTRAL': $periodicidadVal = 3; break;
                    case 'SEMESTRAL': $periodicidadVal = 2; break;
                    case 'ANUAL': $periodicidadVal = 1; break;
                    default: $periodicidadVal = 0; break;
                }
            }

            $curso = Cursos::create([
                'nombre' => $request->nombre,
                'codigo_curso' => $newCode,
                'tipo_curso' => $request->tipo_curso,
                'area' => $request->area,
                'periodicidad' => $periodicidadVal,
                'es_periodico' => $request->input('es_periodico', 0),
                'frecuencia' => $request->input('frecuencia'),
                'proyeccion_anios' => $request->input('proyeccion_anios'),
                'fecha_creacion' => date('Y-m-d\TH:i:s.000')
            ]);

            if (!$curso || !$curso->codigo) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar el curso en la base de datos.'
                ], 500);
            }

            // GUARDAR SUCURSALES ASIGNADAS (Lógica PAC)
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
            $baseNombre = '';

            if ($request->hasFile('archivo')) {
                $archivo = $request->file('archivo');

                if ($archivo->getClientOriginalExtension() !== 'mbz') {
                    return back()->withErrors(['archivo' => 'El archivo debe ser .mbz']);
                }

                $tienePlantilla = true;

                $anio = date('Y');
                $mes = ucfirst(\Carbon\Carbon::now()->translatedFormat('F'));

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

            // Auto-generar nombre si no viene (usuario eliminó campo)
            $nombreExamen = $request->nombre_exa ?? ("Examen de " . $request->nombre);

            $examen = ExamenCurso::create([
                'cod_cursos' => $curso->codigo,
                'nombre' => $nombreExamen,
                'descripcion' => $request->descripcion,
                'tiempo' => $request->tiempo,
                'nota_minima' => $request->nota,
                'file_tiene' => $tienePlantilla ? 1 : 0,
                'file_nombre' => $nombreArchivoFinal,
                'file_ruta' => $rutaArchivo,
                'file_extension' => $extensionArchivo,
                'file_tipo' => $tipoArchivo,
                'file_nombre_original' => $nombreArchivoOriginal,
                'intentos' => $request->intentos,
                'fecha_creacion' => date('Y-m-d\TH:i:s.000')
            ]);

            if (!$examen) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar el examen en la base de datos.'
                ], 500);
            }

            // AUTOGENERAR PROGRAMACIONES SI EXISTEN (FASE 3)
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
                            'fecha_creacion'=> date('Y-m-d\TH:i:s.000'),
                            'habilitado'    => 1,
                        ]);
                    }
                }
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

    public function saveProgramacion(Request $request)
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
                'fecha_creacion'=> now()->format('Y-m-d\TH:i:s.000'),
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

    public function updateProgramacion(Request $request)
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

    public function updateProgramacionHab(Request $request, $codigo)
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

    public function getProgramacionXId($id)
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

    public function getCursoProgramacionXId($id)
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
                'fecha_inicio_texto' => \Carbon\Carbon::parse($prog->fecha_inicio)->format('d/m/Y'),
                'fecha_final_texto' => \Carbon\Carbon::parse($prog->fecha_final)->format('d/m/Y'),
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

    public function getAreas()
    {
        $areas = CapacitacionAreas::where('habilitado', 1)->get();
        return response()->json($areas);
    }

    public function getTipoCursos()
    {
        $tipoCursos = CapacitacionTipoCurso::where('habilitado', 1)->get();
        return response()->json($tipoCursos);
    }


    public function analizarPlantilla(Request $request)
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

            if ($ext === 'zip' || !File::isDirectory($tempPath . '/course') ) {
                $zip = new \ZipArchive;
                if ($zip->open($filePath) === true) {
                    $zip->extractTo($tempPath);
                    $zip->close();
                }
            }

            $findFile = function(string $base, string $name) {
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

    public function saveMatricula(Request $request)
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
    public function getMatriculasPorCurso($cursoId)
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
    public function getHistorialCapacitaciones($personalId)
    {
        try {
            // Optimización: Consultar directamente las matrículas sin pasar por el modelo Personal
            // Esto evita errores de conversión de tipos en IDs alfanuméricos (ej. 'P0056')
            $historial = \DB::table('sw_matriculas as m')
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
    public function buscarPersonalCapacitacion(Request $request)
    {
        try {
            // 1. Obtener TODO el personal usando el SP optimizado (Rápido: ~20k registros)
            $rawPersonal = FileControl::getPersonal();

            // 2. Cargar conteos de matrículas (Optimizado: una sola query para todos)
            $matriculasCounts = Matricula::select('cod_personal', \DB::raw('count(*) as total'))
                ->groupBy('cod_personal')
                ->pluck('total', 'cod_personal')
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
     * Obtener lista de sucursales
     * GET /api/get-sucursales
     */
    public function getSucursales()
    {
        try {
            // Consultar directamente la tabla sw_MIGRA_SISO_SUCURSAL usando Query Builder
            $sucursales = DB::table('sw_MIGRA_SISO_SUCURSAL')
                ->select('SUCU_ABREVIATURA as sucursal')
                ->whereNotNull('SUCU_ABREVIATURA')
                ->distinct()
                ->orderBy('SUCU_ABREVIATURA')
                ->get();
            
            return response()->json([
                'success' => true,
                'sucursales' => $sucursales
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cargar sucursales: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar sucursales',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vista de consulta de matrículas
     */
    public function vistaConsultaMatriculas()
    {
        return view('capacitacion.consulta_matriculas');
    }

    /**
     * Vista de historial de capacitaciones
     */
    public function vistaHistorialCapacitaciones()
    {
        return view('capacitacion.historial_capacitaciones');
    }

    /**
     * Listar matrículas de un curso usando MigraPersonal y sw_matriculas (JOIN robusto con logging de errores)
     * Devuelve datos personales y fecha de matrícula
     */
    public function getMatriculasMigraPersonal($cursoId)
    {
        try {
            $result = \DB::table('sw_MIGRA_PERSONAL as p')
                ->join('sw_matriculas as m', function($join) use ($cursoId) {
                    $join->on(\DB::raw("RTRIM(LTRIM(p.CODI_PERS)) COLLATE Modern_Spanish_CI_AS"), '=', \DB::raw("RTRIM(LTRIM(m.cod_personal)) COLLATE Modern_Spanish_CI_AS"))
                         ->where('m.cod_curso', '=', $cursoId);
                })
                ->leftJoin('sw_cursos_programacion as prog', 'm.cod_programacion', '=', 'prog.codigo')
                ->select([
                    'p.CODI_PERS as cod_personal',
                    'p.NRO_DOCU_IDEN as dni',
                    \DB::raw(
                        "LTRIM(RTRIM(ISNULL(p.APEL_1, ''))) + ' ' + LTRIM(RTRIM(ISNULL(p.APEL_2, ''))) + ' ' + LTRIM(RTRIM(ISNULL(p.NOMB_1, ''))) + ' ' + LTRIM(RTRIM(ISNULL(p.NOMB_2, ''))) as nombre_completo"
                    ),
                    'p.PERS_EMAIL as correo',
                    'p.CODI_CARG as cargo',
                    'm.fecha_matricula',
                    'm.estado',
                    'prog.fecha_inicio as prog_fecha_inicio',
                    'prog.fecha_final as prog_fecha_final',
                ])
                ->orderBy('nombre_completo')
                ->get();

            // Agregar sucursal a cada registro
            $matriculas = $result->map(function($item) {
                $item->sucursal = \App\Models\FileControl::getSucursalXPersona($item->cod_personal);
                return $item;
            });

            return response()->json([
                'success' => true,
                'matriculas' => $matriculas,
                'total' => $matriculas->count()
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en getMatriculasMigraPersonal: ' . $e->getMessage(), ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

}
