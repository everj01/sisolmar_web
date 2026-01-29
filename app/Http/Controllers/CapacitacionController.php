<?php

namespace App\Http\Controllers;

use App\Mail\MatriculaNotificacion;
use App\Models\Areas;
use App\Models\CapacitacionAreas;
use App\Models\CapacitacionTipoCurso;
use App\Models\CursoProgramacion;
use App\Models\FileControl;
use App\Models\TipoCurso;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Cursos;
use App\Models\ExamenCurso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Mail;
use Psy\Readline\Hoa\Console;

class CapacitacionController extends Controller
{

    public function index(Request $request, $op = null)
    {
        $query = Cursos::query();

        // Filtrar por habilitado
        if (!is_null($op)) {
            $query->where('habilitado', $op);
        }

        // Filtro por área
        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        // Filtro por tipo de curso
        if ($request->filled('tipoCurso')) {
            $query->where('tipo_curso', $request->tipoCurso);
        }

        $cursos = $query->get()->map(function ($curso) {
            return [
                'codigo' => $curso->codigo,
                'codigoCurso' => $curso->codigo_curso,
                'nombre' => $curso->nombre,
                'habilitado' => $curso->habilitado,
                'periodicidad' => $curso->periodicidad,
            ];
        });

        return response()->json($cursos);
    }


    // public function index($op = null)
    // {
    //     if (is_null($op)) {
    //         $cursos = Cursos::get()
    //         ->map(function ($curso) {
    //             return [
    //                 'codigo' => $curso->codigo,
    //                 'codigoCurso' => $curso->codigo_curso,
    //                 'nombre' => $curso->nombre,
    //                 'habilitado' => $curso->habilitado,
    //             ];
    //         });
    // } else {
    //         $cursos = Cursos::where('habilitado', $op)
    //         ->get()
    //         ->map(function ($curso) {
    //             return [
    //                 'codigo' => $curso->codigo,
    //                 'codigoCurso' => $curso->codigo_curso,
    //                 'nombre' => $curso->nombre,
    //                 'habilitado' => $curso->habilitado,
    //             ];
    //         });
    //     }
    //     return response()->json($cursos);
    // }

    public function getCursoExamenXId($id)
    {
        $curso = Cursos::with(['examen', 'tipoCurso', 'area'])->where('codigo', $id)->firstOrFail();

        return response()->json([
            'success' => true,
            'curso' => $curso
        ]);
    }

    public function updateCurso(Request $request){

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'tipo_curso'=> 'required|integer|exists:sw_capacitacion_tipo_curso,codigo',
            'area'=> 'required|integer|exists:sw_capacitacion_areas,codigo',
            'periodicidad'=> 'required|integer|max:10',
            'nombre_exa' => 'required|string',
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

            $curso->update([
                'nombre' => $request->nombre,
                'tipo_curso' => $request->tipo_curso,
                'area' => $request->area,
                'periodicidad' => $request->periodicidad,
                'fecha_modificacion' => date('Y-m-d\TH:i:s.000')
            ]);

            $examen = ExamenCurso::where('cod_cursos', $request->codigo)->firstOrFail();

            $examen->update([
                'nombre' => $request->nombre_exa,
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

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el curso.',
                'error' => $e->getMessage()
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

        ExamenCurso::where('cod_cursos', $curso->codigo)
            ->update(['habilitado' => 0]);

        return response()->json([
            'success' => true,
            'message' => 'Curso y exámenes relacionados actualizados correctamente'
        ]);
    }

    public function saveCurso(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:100',
                'tipo_curso'=> 'required|integer|exists:sw_capacitacion_tipo_curso,codigo',
                'area'=> 'required|integer|exists:sw_capacitacion_areas,codigo',
                'periodicidad'=> 'required|integer|max:10',
                'nombre_exa' => 'required|string',
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

            $lastCod = Cursos::orderBy('codigo_curso', 'desc')->first();
            if ($lastCod) {
                $lastNumber = intval($lastCod->codigo_curso);
                $newCode = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
            } else {
                $newCode = '10001';
            }

            $curso = Cursos::create([
                'nombre' => $request->nombre,
                'codigo_curso' => $newCode,
                'tipo_curso' => $request->tipo_curso,
                'area' => $request->area,
                'periodicidad' => $request->periodicidad,
                'fecha_creacion' => date('Y-m-d\TH:i:s.000')
            ]);

            if (!$curso || !$curso->codigo) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar el curso en la base de datos.'
                ], 500);
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

            $examen = ExamenCurso::create([
                'cod_cursos' => $curso->codigo,
                'nombre' => $request->nombre_exa,
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

            DB::commit();

            return response()->json([
                'message' => 'Curso y examen registrados correctamente.',
                'success' => true
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error interno.',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function saveProgramacion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cod_cursos'    => 'required|integer|exists:sw_cursos,codigo',
                'fecha_inicio'  => 'required|date',
                'fecha_final'   => 'required|date',
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


            if ($curso->periodicidad) {
                $cantidadProgramaciones = CursoProgramacion::where('cod_cursos', $curso->codigo)
                    ->where('habilitado', 1)->count();

                if ($cantidadProgramaciones >= $curso->periodicidad) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "El curso ya alcanzó el máximo de programaciones permitidas ({$curso->periodicidad})."
                    ], 409);
                }
            }

            $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay()->format('Y-m-d\TH:i:s');
            $fechaFinal  = Carbon::parse($request->fecha_final)->endOfDay()->format('Y-m-d\TH:i:s');

            $existeProgramacion = CursoProgramacion::where('cod_cursos', $curso->codigo)
                ->where(function($query) use ($fechaInicio, $fechaFinal) {
                    $query->where('fecha_inicio', '<', $fechaFinal)
                        ->where('fecha_final', '>', $fechaInicio);
                })
                ->exists();


            if ($existeProgramacion) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una programación para este curso que se solapa con el rango de fechas ingresado.'
                ], 409);
            }

            $lastCod = CursoProgramacion::orderBy('codigo_programacion', 'desc')->first();
            if ($lastCod) {
                $lastNumber = intval($lastCod->codigo_programacion);
                $newCode = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newCode = '1001';
            }

            $programacion = CursoProgramacion::create([
                'codigo_programacion' => (string) $newCode,
                'cod_cursos' => (int) $curso->codigo,
                'fecha_inicio' => $fechaInicio,
                'fecha_final'  => $fechaFinal,
                'fecha_creacion' => now()->format('Y-m-d\TH:i:s'),
                'habilitado' => (int) $request->habilitado,
            ]);

            if (!$programacion || !$programacion->codigo) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error al registrar la programación en la base de datos.'
                ], 500);
            }

            DB::commit();

            return response()->json([
                'message' => 'Programación registrada correctamente.',
                'success' => true
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error interno.',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function updateProgramacion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'codigo'        => 'required|integer|exists:sw_cursos_programacion,codigo',
                'cod_cursos'    => 'required|integer|exists:sw_cursos,codigo',
                'fecha_inicio'  => 'required|date',
                'fecha_final'   => 'required|date',
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

            // Buscar la programación existente
            $programacion = CursoProgramacion::where('codigo', $request->codigo)->first();
            if (!$programacion) {
                return response()->json([
                    'success' => false,
                    'message' => 'La programación especificada no existe.'
                ], 404);
            }

            // Verificar que el curso exista
            $curso = Cursos::where('codigo', $request->cod_cursos)->first();
            if (!$curso) {
                return response()->json([
                    'success' => false,
                    'message' => 'El curso especificado no existe.'
                ], 404);
            }

            $fechaInicio = Carbon::parse($request->fecha_inicio)->startOfDay()->format('Y-m-d\TH:i:s');
            $fechaFinal  = Carbon::parse($request->fecha_final)->endOfDay()->format('Y-m-d\TH:i:s');

            // Validar solapamiento, excluyendo la programación actual
            $existeProgramacion = CursoProgramacion::where('cod_cursos', $curso->codigo)
                ->where('codigo', '!=', $programacion->codigo)
                ->where(function($query) use ($fechaInicio, $fechaFinal) {
                    $query->where('fecha_inicio', '<', $fechaFinal)
                        ->where('fecha_final', '>', $fechaInicio);
                })
                ->exists();

            if ($existeProgramacion) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe otra programación para este curso que se solapa con el rango de fechas ingresado.'
                ], 409);
            }

            // Actualizar campos
            $programacion->update([
                'cod_cursos'    => (int) $curso->codigo,
                'fecha_inicio'  => $fechaInicio,
                'fecha_final'   => $fechaFinal,
                'habilitado'    => (int) $request->habilitado,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Programación actualizada correctamente.',
                'success' => true
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error interno.',
                'error'   => $e->getMessage(),
                'line'    => $e->getLine()
            ], 500);
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

        return response()->json([
            'success' => true,
            'programaciones' => $programaciones
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
            return response()->json([
                'success' => false,
                'message' => 'Error procesando plantilla: ' . $e->getMessage()
            ], 500);
        } finally {
            if (File::isDirectory($tempPath)) {
                File::deleteDirectory($tempPath);
            }
        }
    }

    public function saveMatricula(Request $request)
    {

        // $personalPrueba = (object)[
        //     'personal' => 'Juan Pérez García',
        //     'email' => 'juan.perez@ejemplo.com',
        //     'nroDoc' => '12345678'
        // ];
        
        // $cursoPrueba = (object)[
        //     'nombre' => 'Curso de Seguridad Industrial y Salud Ocupacional',
        //     'codigoCurso' => 'SI-2024-001'
        // ];
        
        // return new MatriculaNotificacion($personalPrueba, curso: $cursoPrueba);

        try {
            $validator = Validator::make($request->all(), [
                'cursoId'      => 'required|integer',
                'personalIds'  => 'required|array',
                'personalIds.*' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación.',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $cursoId = $request->cursoId;
            $personalIds = $request->personalIds;
            
            $curso = Cursos::find($cursoId);
            
            if (!$curso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Curso no encontrado.'
                ], 404);
            }

            $matriculados = [];
            $errores = [];
            $correosEnviados = 0;

            foreach ($personalIds as $personalId) {
                try {
                    // // Verificar si ya está matriculado
                    // $existe = Matricula::where('curso_id', $cursoId)
                    //     ->where('personal_id', $personalId)
                    //     ->exists();

                    // if ($existe) {
                    //     $errores[] = [
                    //         'personalId' => $personalId,
                    //         'mensaje' => 'Ya está matriculado en este curso'
                    //     ];
                    //     continue;
                    // }

                    // // Insertar matrícula
                    // $matricula = Matricula::create([
                    //     'curso_id' => $cursoId,
                    //     'personal_id' => $personalId,
                    //     'fecha_matricula' => now(),
                    //     'estado' => 1 // Activo
                    // ]);

                    // Obtener datos del personal
                    $personal = FileControl::getPersonalXId($personalId);

                    if ($personal && $personal->PERS_EMAIL) {

                        $email = "webmaster@gruposolmar.com.pe";
                        //$email = "gilmertiradoam.27@gmail.com";
                        Mail::to($email)->send(new MatriculaNotificacion($personal, $curso));

                        //Mail::to($personal->PERS_EMAIL)->send(new MatriculaNotificacion($personal, $curso));
                        $correosEnviados++;
                    }

                    $matriculados[] = [
                        'personalId' => $personalId,
                        'nombre' => $personal->NOMB_1 . ' ' . $personal->APEL_1 . ' ' . $personal->APEL_2
                    ];

                } catch (\Exception $e) {
                    $errores[] = [
                        'personalId' => $personalId,
                        'mensaje' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($matriculados) . ' persona(s) matriculada(s) exitosamente.',
                'data' => [
                    'matriculados' => count($matriculados),
                    'correosEnviados' => $correosEnviados,
                    'errores' => count($errores),
                    'detalleMatriculados' => $matriculados,
                    'detalleErrores' => $errores
                ]
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error interno.',
                'error' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
    }

}
