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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Jobs\DispatchMatriculaBatchJob;
use Illuminate\Support\Facades\Auth;
use App\Models\Matricula;
use App\Models\Consulta;
use App\Models\ExamenPregunta2026;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Http;
use App\Models\Personal;
use App\Models\CapacitacionReporteHistorial;

class CapacitacionController extends Controller
{
    public function index(Request $request, ?string $op = null): JsonResponse
    {
        $query = Cursos::query();

        if (!is_null($op)) {
            $query->where("habilitado", $op);
        }

        if ($request->filled("filtro_area")) {
            $query->where("area", $request->input("filtro_area"));
        }

        if ($request->filled("filtro_tipo")) {
            $query->where("tipo_curso", $request->input("filtro_tipo"));
        }

        $cursosVigentes = DB::table("sw_cursos_programacion")
            ->where("estado_periodo", "VIGENTE")
            ->where("habilitado", 1)
            ->pluck("cod_cursos")
            ->toArray();

        $cursos = $query->get()->map(function ($curso) use ($cursosVigentes) {
            $nombreResponsable = "";
            if ($curso->cod_responsable) {
                $resp = DB::connection("sqlsrv")->selectOne(
                    "
                    SELECT LTRIM(RTRIM(APEL_1 + ' ' + ISNULL(APEL_2, '') + ' ' + NOMB_1 + ' ' + ISNULL(NOMB_2, ''))) as nombre
                    FROM si_solm.dbo.PERSONAL
                    WHERE CODI_PERS = ?
                ",
                    [$curso->cod_responsable],
                );
                $nombreResponsable = $resp->nombre ?? "";
            }
            return [
                "codigo" => $curso->codigo,
                "codigoCurso" => $curso->codigo_curso,
                "nombre" => $curso->nombre,
                "habilitado" => $curso->habilitado,
                "cod_responsable" => $curso->cod_responsable,
                "nombre_responsable" => $nombreResponsable,
                "periodicidad" => $curso->periodicidad,
                "es_periodico" => $curso->es_periodico,
                "frecuencia" => $curso->frecuencia,
                "proyeccion_anios" => $curso->proyeccion_anios,
                "tiene_vigente" => in_array($curso->codigo, $cursosVigentes),
                "codigo_moodle" => $curso->codigo_moodle,
            ];
        });

        return response()->json($cursos);
    }

    public function getCursoExamenXId(int $id): JsonResponse
    {
        $curso = Cursos::with(["examen", "tipoCurso"])
            ->where("codigo", $id)
            ->firstOrFail();

        $sucursales = DB::table("sw_curso_sucursales")
            ->where("curso_codigo", $curso->codigo)
            ->pluck("sucursal");

        $curso->sucursales = $sucursales;

        if ($curso->cod_responsable) {
            $resp = DB::connection("sqlsrv")->selectOne(
                "
                SELECT LTRIM(RTRIM(APEL_1 + ' ' + ISNULL(APEL_2, '') + ' ' + NOMB_1 + ' ' + ISNULL(NOMB_2, ''))) as nombre
                FROM si_solm.dbo.PERSONAL
                WHERE CODI_PERS = ?
            ",
                [$curso->cod_responsable],
            );

            $curso->nombre_responsable = $resp->nombre ?? "No encontrado";
        } else {
            $curso->nombre_responsable = "";
        }

        return response()->json([
            "success" => true,
            "curso" => $curso,
        ]);
    }

    public function updateCurso(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "nombre" => "required|string|max:100",
            "tipo_curso" =>
                "required|integer|exists:sw_capacitacion_tipo_curso,codigo",
            "area_conocimiento" =>
                "required|exists:sw_capacitacion_areas,codigo",
            "area_responsable" => "nullable|integer",
            "es_periodico" => "required|integer|in:0,1",
            "frecuencia" => "nullable|string",
            "proyeccion_anios" => "nullable|integer",
            "fechas_generadas" => "nullable|string",
            "nombre_exa" => "nullable|string",
            "descripcion" => "nullable|string",
            "tiempo" => "nullable|required_if:aplica_evaluacion,1|integer",
            "nota" => "nullable|required_if:aplica_evaluacion,1|integer",
            "intentos" => "nullable|required_if:aplica_evaluacion,1|integer",
            "archivo" => "nullable|file|max:51200",
            "aplica_evaluacion" => "nullable|integer|in:0,1",
            "obligatorio_alta" => "nullable|integer|in:0,1",
            "cod_responsable" => "nullable|string|max:20",
            "target_group" =>
                "nullable|string|in:TODOS,ADMINISTRATIVO,OPERATIVO",
            "cod_moodle_area" => "nullable|integer",
            "dirigido_a" => "nullable|integer",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Errores de validación.",
                    "errors" => $validator->errors(),
                ],
                422,
            );
        }

        DB::beginTransaction();

        try {
            $curso = Cursos::where("codigo", $request->codigo)->firstOrFail();
            $codigo_curso = $curso->codigo_curso;

            $periodicidadVal = 0;
            if ($request->input("es_periodico") == 1) {
                switch ($request->input("frecuencia")) {
                    case "MENSUAL":
                        $periodicidadVal = 12;
                        break;
                    case "BIMESTRAL":
                        $periodicidadVal = 6;
                        break;
                    case "TRIMESTRAL":
                        $periodicidadVal = 4;
                        break;
                    case "CUATRIMESTRAL":
                        $periodicidadVal = 3;
                        break;
                    case "SEMESTRAL":
                        $periodicidadVal = 2;
                        break;
                    case "ANUAL":
                        $periodicidadVal = 1;
                        break;
                    default:
                        $periodicidadVal = 0;
                        break;
                }
            }

            $curso->update([
                "nombre" => $request->nombre,
                "tipo_curso" => $request->tipo_curso,
                "area_conocimiento" => $request->area_conocimiento,
                "area" => $request->area_responsable,
                "periodicidad" => $periodicidadVal,
                "es_periodico" => $request->input("es_periodico"),
                "frecuencia" => $request->input("frecuencia"),
                "proyeccion_anios" => $request->input("proyeccion_anios"),
                "aplica_evaluacion" => $request->input("aplica_evaluacion", 0),
                "obligatorio_alta" => $request->input("obligatorio_alta", 0),
                "cod_responsable" => $request->input("cod_responsable"),
                "target_group" => $request->input("target_group", "TODOS"),
                "descripcion" => $request->input("descripcion"),
                "dirigido_a" => $request->input("dirigido_a"),
                "fecha_modificacion" => date("Y-m-d\TH:i:s.000"),
            ]);

            if ($request->has("fechas_generadas")) {
                $fechasArray = json_decode(
                    $request->input("fechas_generadas"),
                    true,
                );
                if (
                    json_last_error() === JSON_ERROR_NONE &&
                    is_array($fechasArray)
                ) {
                    $lastProg = CursoProgramacion::orderBy(
                        "codigo_programacion",
                        "desc",
                    )->first();
                    $newProgCod = $lastProg
                        ? intval($lastProg->codigo_programacion)
                        : 1000;

                    foreach ($fechasArray as $fechaItem) {
                        $existe = CursoProgramacion::where(
                            "cod_cursos",
                            $curso->codigo,
                        )
                            ->where("periodo", $fechaItem["periodo"])
                            ->where("tipo", "REGULAR")
                            ->exists();

                        if (!$existe) {
                            $newProgCod++;
                            CursoProgramacion::create([
                                "codigo_programacion" => str_pad(
                                    $newProgCod,
                                    4,
                                    "0",
                                    STR_PAD_LEFT,
                                ),
                                "cod_cursos" => $curso->codigo,
                                "periodo" => $fechaItem["periodo"],
                                "tipo" => "REGULAR",
                                "fecha_inicio" =>
                                    $fechaItem["inicio"] . "T00:00:00.000",
                                "fecha_final" =>
                                    $fechaItem["final"] . "T23:59:59.000",
                                "fecha_creacion" => date("Y-m-d\TH:i:s.000"),
                                "habilitado" => 1,
                            ]);
                        }
                    }
                }
            }

            DB::table("sw_curso_sucursales")
                ->where("curso_codigo", $curso->codigo)
                ->delete();

            if (
                $request->has("sucursales_asignadas") &&
                is_array($request->sucursales_asignadas)
            ) {
                $sucursales = $request->sucursales_asignadas;
                foreach ($sucursales as $sucursal) {
                    DB::table("sw_curso_sucursales")->insert([
                        "curso_codigo" => $curso->codigo,
                        "sucursal" => $sucursal,
                        "created_at" => date("Y-m-d\TH:i:s.000"),
                        "updated_at" => date("Y-m-d\TH:i:s.000"),
                    ]);
                }
            }

            DB::table("sw_cliente_curso")
                ->where("cod_curso", $curso->codigo)
                ->delete();
            if (
                $request->input("tipo_curso") == "6" &&
                $request->has("sucursales_asignadas") &&
                is_array($request->sucursales_asignadas)
            ) {
                foreach ($request->sucursales_asignadas as $cliente) {
                    DB::table("sw_cliente_curso")->insert([
                        "cod_cliente" => $cliente,
                        "cod_curso" => $curso->codigo,
                    ]);
                }
            }

            $aplicaEvaluacion = $request->input("aplica_evaluacion", 0);

            if ($aplicaEvaluacion == 1) {
                $examen = ExamenCurso::where(
                    "cod_cursos",
                    $request->codigo,
                )->first();

                $nombreExamen =
                    $request->nombre_exa ?? "Examen de " . $request->nombre;

                if ($examen) {
                    $examen->update([
                        "nombre" => $nombreExamen,
                        "descripcion" => $request->descripcion,
                        "tiempo" => (int) ($request->tiempo ?? 0),
                        "nota_minima" => (int) ($request->nota ?? 0),
                        "intentos" => (int) ($request->intentos ?? 0),
                        "cantidad_preguntas" =>
                            (int) ($request->cantidad_preguntas ?? 0),
                        "preguntas_balotario" =>
                            (int) ($request->preguntas_balotario ?? 0),
                        "fecha_modificacion" => date("Y-m-d\TH:i:s.000"),
                    ]);
                } else {
                    $examen = ExamenCurso::create([
                        "cod_cursos" => $curso->codigo,
                        "nombre" => $nombreExamen,
                        "descripcion" => $request->descripcion,
                        "tiempo" => (int) ($request->tiempo ?? 0),
                        "nota_minima" => (int) ($request->nota ?? 0),
                        "file_tiene" => 0,
                        "file_nombre" => null,
                        "file_ruta" => null,
                        "file_extension" => null,
                        "file_tipo" => null,
                        "file_nombre_original" => null,
                        "intentos" => (int) ($request->intentos ?? 0),
                        "cantidad_preguntas" =>
                            (int) ($request->cantidad_preguntas ?? 0),
                        "preguntas_balotario" =>
                            (int) ($request->preguntas_balotario ?? 0),
                        "fecha_creacion" => date("Y-m-d\TH:i:s.000"),
                    ]);
                }

                if ($request->hasFile("archivo")) {
                    $archivo = $request->file("archivo");

                    if (
                        $examen->file_ruta &&
                        Storage::disk("public")->exists($examen->file_ruta)
                    ) {
                        Storage::disk("public")->delete($examen->file_ruta);
                    }

                    if ($archivo->getClientOriginalExtension() !== "mbz") {
                        return response()->json(
                            [
                                "success" => false,
                                "message" => "El archivo debe ser .mbz",
                                "errors" => [
                                    "archivo" => ["El archivo debe ser .mbz"],
                                ],
                            ],
                            422,
                        );
                    }

                    $tienePlantilla = true;

                    $anio = date("Y");
                    $mes = ucfirst(Carbon::now()->translatedFormat("F"));

                    $tipoArchivo = $archivo->getClientMimeType();
                    $extensionArchivo = $archivo->getClientOriginalExtension();
                    $nombreArchivoOriginal = $archivo->getClientOriginalName();

                    $baseNombre = "EXA_" . $codigo_curso . "_" . date("Ymd");
                    $carpeta = "plantillas/{$anio}/{$mes}";

                    if (!Storage::disk("public")->exists($carpeta)) {
                        Storage::disk("public")->makeDirectory($carpeta);
                    }

                    $contador = 1;

                    do {
                        $nombreArchivoFinal =
                            "{$baseNombre}_{$contador}." . $extensionArchivo;
                        $rutaCompleta = storage_path(
                            "app/public/{$carpeta}/{$nombreArchivoFinal}",
                        );
                        $contador++;
                    } while (file_exists($rutaCompleta));

                    $rutaArchivo = $archivo->storeAs(
                        $carpeta,
                        $nombreArchivoFinal,
                        "public",
                    );

                    $examen->update([
                        "file_tiene" => $tienePlantilla ? 1 : 0,
                        "file_nombre" => $nombreArchivoFinal,
                        "file_ruta" => $rutaArchivo,
                        "file_extension" => $extensionArchivo,
                        "file_tipo" => $tipoArchivo,
                        "file_nombre_original" => $nombreArchivoOriginal,
                    ]);
                }
            } else {
                // Si no aplica evaluación, eliminar examen si existe
                $examen = ExamenCurso::where(
                    "cod_cursos",
                    $request->codigo,
                )->first();
                if ($examen) {
                    if (
                        $examen->file_ruta &&
                        Storage::disk("public")->exists($examen->file_ruta)
                    ) {
                        Storage::disk("public")->delete($examen->file_ruta);
                    }
                    $examen->delete();
                }
            }

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => "Curso actualizado correctamente",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            // SEGURIDAD: No exponer información técnica al frontend en producción
            // Los detalles del error se guardan en logs para debugging interno
            // Antes exponía $e->getMessage() y $e->getLine() que revelan estructura de BD y rutas
            Log::error("Error al actualizar curso", [
                "error" => $e->getMessage(),
                "line" => $e->getLine(),
                "file" => $e->getFile(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error al actualizar el curso. Por favor, contacte al administrador.",
                ],
                500,
            );
        }
    }

    public function updateCursoHab(Request $request, int $codigo): JsonResponse
    {
        $curso = Cursos::where("codigo", $codigo)->firstOrFail();

        $curso->update([
            "habilitado" => $request->input("habilitado"),
            "fecha_modificacion" => date("Y-m-d\TH:i:s.000"),
        ]);

        if ($request->input("habilitado") == 0) {
            ExamenCurso::where("cod_cursos", $curso->codigo)->update([
                "habilitado" => 0,
            ]);
        }

        return response()->json([
            "success" => true,
            "message" =>
                "Curso y exámenes relacionados actualizados correctamente",
        ]);
    }

    public function destroyCurso(int $codigo): JsonResponse
    {
        DB::beginTransaction();
        try {
            $curso = Cursos::where("codigo", $codigo)->firstOrFail();

            // 1. Delete Sucursales mappings
            DB::table("sw_curso_sucursales")
                ->where("curso_codigo", $curso->codigo)
                ->delete();

            // 2. Delete Examen & Plantilla Archive
            $examen = ExamenCurso::where("cod_cursos", $curso->codigo)->first();
            if ($examen) {
                if (
                    $examen->file_ruta &&
                    Storage::disk("public")->exists($examen->file_ruta)
                ) {
                    Storage::disk("public")->delete($examen->file_ruta);
                }
                $examen->delete();
            }

            // 3. Delete Programaciones
            CursoProgramacion::where("cod_cursos", $curso->codigo)->delete();

            // 4. Delete Asistencias / Matriculados / Notas ?
            // Esto asume que si estaba eliminado, no tiene matriculas o si las tiene se borran (normalmente dependen del codigo_programacion).
            // Lo más seguro es que si el usuario lo puede borrar es porque recién lo creó o es un error.

            // 5. Delete Curso base
            $curso->delete();

            DB::commit();

            return response()->json([
                "success" => true,
                "message" =>
                    "El curso y todos sus registros han sido ELIMINADOS permanentemente.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al destruir curso permanentemente", [
                "error" => $e->getMessage(),
                "codigo" => $codigo,
            ]);
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "No se pudo eliminar el curso permanentemente debido a registros dependientes (ej: asistencias).",
                ],
                500,
            );
        }
    }

    public function saveCurso(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                "nombre" => "required|string|max:100",
                "tipo_curso" =>
                    "required|integer|exists:sw_capacitacion_tipo_curso,codigo",
                "area_conocimiento" =>
                    "required|exists:sw_capacitacion_areas,codigo",
                "area_responsable" => "nullable|integer",
                "es_periodico" => "required|integer|in:0,1",
                "frecuencia" => "nullable|string",
                "proyeccion_anios" => "nullable|integer",
                "fechas_generadas" => "nullable|string",
                "nombre_exa" => "nullable|string",
                "descripcion" => "nullable|string",
                "tiempo" => "nullable|required_if:aplica_evaluacion,1|integer",
                "nota" => "nullable|required_if:aplica_evaluacion,1|integer",
                "intentos" =>
                    "nullable|required_if:aplica_evaluacion,1|integer",
                "archivo" => "nullable|file|max:51200",
                "sucursales_asignadas" => "nullable|array",
                "sucursales_asignadas.*" => "integer|exists:sw_clientes,codigo",
                "aplica_evaluacion" => "nullable|integer|in:0,1",
                "obligatorio_alta" => "nullable|integer|in:0,1",
                "cod_responsable" => "nullable|string|max:20",
                "target_group" =>
                    "nullable|string|in:TODOS,ADMINISTRATIVO,OPERATIVO",
                "cod_moodle_area" => "nullable|integer",
                "dirigido_a" => "nullable|integer",
                "image_portada" => "nullable|image|mimes:jpeg,jpg,png|max:1990",
                "image_afiche" => "nullable|image|mimes:jpeg,jpg,png|max:1990",
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Errores de validación.",
                        "errors" => $validator->errors(),
                    ],
                    422,
                );
            }

            DB::beginTransaction();

            $curso = Cursos::create([
                "nombre" => $request->nombre,
                "codigo_curso" => $this->generateCourseCode(),
                "tipo_curso" => $request->tipo_curso,
                "area_conocimiento" => $request->area_conocimiento,
                "area" => $request->area_responsable,
                "periodicidad" => $this->calculatePeriodicidad(
                    $request->input("frecuencia"),
                    (int) $request->input("es_periodico", 0),
                ),
                "es_periodico" => $request->input("es_periodico", 0),
                "frecuencia" => $request->input("frecuencia"),
                "proyeccion_anios" => $request->input("proyeccion_anios"),
                "aplica_evaluacion" => $request->input("aplica_evaluacion", 0),
                "obligatorio_alta" => $request->input("obligatorio_alta", 0),
                "cod_responsable" => $request->input("cod_responsable"),
                "target_group" => $request->input("target_group", "TODOS"),
                "descripcion" => $request->input("descripcion"),
                "dirigido_a" => $request->input("dirigido_a"),
                "fecha_creacion" => date("Y-m-d\TH:i:s.000"),
            ]);

            if (!$curso || !$curso->codigo) {
                DB::rollBack();
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "Error al registrar el curso en la base de datos.",
                    ],
                    500,
                );
            }

            if (
                $request->has("sucursales_asignadas") &&
                is_array($request->sucursales_asignadas)
            ) {
                $this->saveSucursales(
                    $curso->codigo,
                    $request->sucursales_asignadas,
                );
            }

            $this->saveClientesCurso($curso->codigo, $request);

            if ($request->input("aplica_evaluacion", 0) == 1) {
                $examen = $this->createExamen($curso, $request);
                if (!$examen) {
                    DB::rollBack();
                    return response()->json(
                        [
                            "success" => false,
                            "message" =>
                                "Error al registrar el examen en la base de datos.",
                        ],
                        500,
                    );
                }
            }

            if ($request->has("fechas_generadas")) {
                $this->generateProgramaciones(
                    $curso,
                    $request->input("fechas_generadas"),
                );
            }

            $ahora = Carbon::now();
            $inicioTimestamp = $ahora->copy()->startOfDay()->timestamp;
            $finTimestamp = null;

            if ($request->has("fechas_generadas")) {
                $fechasArray = json_decode(
                    $request->input("fechas_generadas"),
                    true,
                );
                if (is_array($fechasArray) && count($fechasArray) > 0) {
                    $inicioTimestamp = Carbon::parse(
                        $fechasArray[0]["inicio"],
                    )->startOfDay()->timestamp;
                    $finTimestamp = Carbon::parse(
                        $fechasArray[0]["final"],
                    )->endOfDay()->timestamp;
                }
            }

            if ($finTimestamp === null) {
                switch ($request->input("frecuencia")) {
                    case "MENSUAL":
                        $finTimestamp = $ahora->copy()->addMonth()->endOfDay()
                            ->timestamp;
                        break;
                    case "BIMESTRAL":
                        $finTimestamp = $ahora->copy()->addMonths(2)->endOfDay()
                            ->timestamp;
                        break;
                    case "TRIMESTRAL":
                        $finTimestamp = $ahora->copy()->addMonths(3)->endOfDay()
                            ->timestamp;
                        break;
                    case "CUATRIMESTRAL":
                        $finTimestamp = $ahora->copy()->addMonths(4)->endOfDay()
                            ->timestamp;
                        break;
                    case "SEMESTRAL":
                        $finTimestamp = $ahora->copy()->addMonths(6)->endOfDay()
                            ->timestamp;
                        break;
                    case "ANUAL":
                        $finTimestamp = $ahora->copy()->addYear()->endOfDay()
                            ->timestamp;
                        break;
                    default:
                        $finTimestamp = $ahora->copy()->addMonth()->endOfDay()
                            ->timestamp;
                        break;
                }
            }

            try {
                $courseId = $this->createMoodleCourse(
                    $request->input("nombre"),
                    $curso->codigo,
                    $request->input("descripcion"),
                    $request->input("cod_moodle_area"),
                    $request->input("cod_responsable"),
                    $request->input("aplica_evaluacion", 0),
                    (int) ($request->tiempo ?? 0),
                    (int) ($request->intentos ?? 0),
                    $request->filled("preguntas_word")
                        ? 0
                        : (int) ($request->cantidad_preguntas ?? 0),
                    (float) ($request->nota ?? 0.0),
                    $inicioTimestamp,
                    $finTimestamp,
                );

                if ($courseId > 0) {
                    $curso->update(["codigo_moodle" => $courseId]);

                    $this->uploadPortadaToMoodle($courseId, $request);
                    $this->uploadAficheToMoodle($courseId, $request);
                    $this->syncPreguntasWord(
                        $courseId,
                        $request->input("aplica_evaluacion", 0),
                        $request->input("preguntas_word"),
                    );
                }
            } catch (\Throwable $e) {
                Log::error("Error al crear curso en Moodle", [
                    "error" => $e->getMessage(),
                    "codigo" => $curso->codigo ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                "message" => "Curso y examen registrados correctamente.",
                "success" => true,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error("Error al registrar curso", [
                "error" => $e->getMessage(),
                "line" => $e->getLine(),
                "file" => $e->getFile(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Ocurrió un error al registrar el curso. Por favor, contacte al administrador.",
                ],
                500,
            );
        }
    }

    private function generateCourseCode(): string
    {
        $lastCod = Cursos::orderBy("codigo_curso", "desc")->first();
        if ($lastCod) {
            $lastNumber = intval($lastCod->codigo_curso);
            return str_pad($lastNumber + 1, 5, "0", STR_PAD_LEFT);
        }
        return "10001";
    }

    private function calculatePeriodicidad(
        ?string $frecuencia,
        int $esPeriodico,
    ): int {
        if ($esPeriodico != 1) {
            return 0;
        }
        switch ($frecuencia) {
            case "MENSUAL":
                return 12;
            case "BIMESTRAL":
                return 6;
            case "TRIMESTRAL":
                return 4;
            case "CUATRIMESTRAL":
                return 3;
            case "SEMESTRAL":
                return 2;
            case "ANUAL":
                return 1;
            default:
                return 0;
        }
    }

    private function saveSucursales(int $cursoCodigo, array $sucursales): void
    {
        foreach ($sucursales as $sucursal) {
            DB::table("sw_curso_sucursales")->insert([
                "curso_codigo" => $cursoCodigo,
                "sucursal" => $sucursal,
                "created_at" => date("Y-m-d\TH:i:s.000"),
                "updated_at" => date("Y-m-d\TH:i:s.000"),
            ]);
        }
    }

    private function saveClientesCurso(int $cursoCodigo, Request $request): void
    {
        if (
            $request->input("tipo_curso") == "6" &&
            $request->has("sucursales_asignadas") &&
            is_array($request->sucursales_asignadas)
        ) {
            foreach ($request->sucursales_asignadas as $cliente) {
                DB::table("sw_cliente_curso")->insert([
                    "cod_cliente" => $cliente,
                    "cod_curso" => $cursoCodigo,
                ]);
            }
        }
    }

    private function createExamen(Cursos $curso, Request $request): ?ExamenCurso
    {
        $nombreExamen = $request->nombre_exa ?? "Examen de " . $request->nombre;

        return ExamenCurso::create([
            "cod_cursos" => $curso->codigo,
            "nombre" => $nombreExamen,
            "descripcion" => $request->descripcion,
            "tiempo" => (int) ($request->tiempo ?? 0),
            "nota_minima" => (int) ($request->nota ?? 0),
            "file_tiene" => 0,
            "file_nombre" => null,
            "file_ruta" => null,
            "file_extension" => null,
            "file_tipo" => null,
            "file_nombre_original" => null,
            "intentos" => (int) ($request->intentos ?? 0),
            "cantidad_preguntas" => (int) ($request->cantidad_preguntas ?? 0),
            "preguntas_balotario" => (int) ($request->preguntas_balotario ?? 0),
            "fecha_creacion" => date("Y-m-d\TH:i:s.000"),
        ]);
    }

    private function generateProgramaciones(
        Cursos $curso,
        string $fechasGeneradas,
    ): void {
        $fechasArray = json_decode($fechasGeneradas, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($fechasArray)) {
            return;
        }

        $lastProg = CursoProgramacion::orderBy(
            "codigo_programacion",
            "desc",
        )->first();
        $newProgCod = $lastProg ? intval($lastProg->codigo_programacion) : 1000;

        foreach ($fechasArray as $fechaItem) {
            $newProgCod++;
            CursoProgramacion::create([
                "codigo_programacion" => str_pad(
                    $newProgCod,
                    4,
                    "0",
                    STR_PAD_LEFT,
                ),
                "cod_cursos" => $curso->codigo,
                "periodo" => $fechaItem["periodo"],
                "tipo" => "REGULAR",
                "fecha_inicio" => $fechaItem["inicio"] . "T00:00:00.000",
                "fecha_final" => $fechaItem["final"] . "T23:59:59.000",
                "fecha_creacion" => date("Y-m-d\TH:i:s.000"),
                "habilitado" => 1,
            ]);
        }
    }

    private function getOrCreateMoodleUser(?string $codResponsable): ?int
    {
        if (empty($codResponsable)) {
            return null;
        }

        $personal = Personal::where("CODI_PERS", $codResponsable)->first();
        if (!$personal) {
            Log::warning(
                "No se encontró personal con CODI_PERS: {$codResponsable}",
            );
            return null;
        }

        $dni = trim($personal->NRO_DOCU_IDEN);
        if (empty($dni)) {
            Log::warning("Personal {$codResponsable} no tiene DNI registrado.");
            return null;
        }

        $moodleUser = DB::connection("mysql_grupoihb")
            ->table("mdl_user")
            ->where("username", $dni)
            ->orWhere("idnumber", $dni)
            ->first();

        if ($moodleUser) {
            return (int) $moodleUser->id;
        }

        $firstname = trim(
            ($personal->NOMB_1 ?? "") . " " . ($personal->NOMB_2 ?? ""),
        );
        $lastname = trim(
            ($personal->APEL_1 ?? "") . " " . ($personal->APEL_2 ?? ""),
        );
        $email = !empty($personal->PERS_EMAIL)
            ? trim($personal->PERS_EMAIL)
            : "{$dni}@sisolmar.com";

        $resUser = DB::connection("mysql_grupoihb")->select(
            "SELECT F_USER_crear(?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS user_id",
            [
                $dni,
                'Gpo$olSEE_1@',
                $firstname,
                $lastname,
                $email,
                "",
                "",
                "",
                "",
                "",
            ],
        );

        $moodleUserId = $resUser[0]->user_id ?? 0;

        if ($moodleUserId <= 0) {
            Log::error(
                "Error al crear usuario en Moodle para DNI {$dni}. Resultado: {$moodleUserId}",
            );
            return null;
        }

        return (int) $moodleUserId;
    }

    private function createMoodleCourse(
        string $nombre,
        int $codigo,
        ?string $descripcion,
        ?int $codMoodleArea,
        ?string $codResponsable,
        int $aplicaEvaluacion,
        int $tiempo,
        int $intentos,
        int $cantPreguntas,
        float $nota,
        int $inicio,
        int $fin,
    ): ?int {
        $moodleUserId = $this->getOrCreateMoodleUser($codResponsable);

        DB::connection("mysql_grupoihb")->statement(
            "
            CALL SP_COURSE_crear_con_examen(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @resultado)",
            [
                $nombre,
                $codigo,
                $descripcion,
                $codMoodleArea,
                $moodleUserId,
                $aplicaEvaluacion,
                $tiempo,
                $intentos,
                $cantPreguntas,
                $nota,
                $inicio,
                $fin,
            ],
        );

        $res = DB::connection("mysql_grupoihb")->select(
            "SELECT @resultado AS resultado",
        );
        return $res[0]->resultado ?? null;
    }

    private function uploadFileToMoodleDraft($file): ?int
    {
        $http = app()->isProduction()
            ? Http::asForm()
            : Http::withoutVerifying()->asForm();

        $response = $http->post(
            config("services.moodle.url") . "/webservice/rest/server.php",
            [
                "wstoken" => config("services.moodle.ws_token"),
                "wsfunction" => "core_files_upload",
                "moodlewsrestformat" => "json",
                "contextlevel" => "user",
                "instanceid" => config("services.moodle.admin_id"),
                "component" => "user",
                "filearea" => "draft",
                "itemid" => 0,
                "filepath" => "/",
                "filename" => $file->getClientOriginalName(),
                "filecontent" => base64_encode(
                    file_get_contents($file->getRealPath()),
                ),
            ],
        );

        return $response->json("itemid") ?? null;
    }

    private function uploadPortadaToMoodle(
        int $courseId,
        Request $request,
    ): void {
        if (!$request->hasFile("image_portada")) {
            return;
        }

        try {
            $contextRow = DB::connection("mysql_grupoihb")->select(
                "SELECT id FROM mdl_context
                      WHERE contextlevel = 50 AND instanceid = ? LIMIT 1",
                [$courseId],
            );

            if (empty($contextRow)) {
                return;
            }

            $draftItemId = $this->uploadFileToMoodleDraft(
                $request->file("image_portada"),
            );

            if ($draftItemId) {
                $http = app()->isProduction()
                    ? Http::asForm()
                    : Http::withoutVerifying()->asForm();

                $http->post(
                    config("services.moodle.url") .
                        "/webservice/rest/server.php",
                    [
                        "wstoken" => config("services.moodle.ws_token"),
                        "wsfunction" => "core_course_update_courses",
                        "moodlewsrestformat" => "json",
                        "courses[0][id]" => $courseId,
                        "courses[0][courseformatoptions][0][name]" =>
                            "overviewfiles_filemanager",
                        "courses[0][courseformatoptions][0][value]" => $draftItemId,
                    ],
                );
            }
        } catch (\Throwable $e) {
            Log::error("Error al subir imagen de portada a Moodle", [
                "error" => $e->getMessage(),
                "courseId" => $courseId,
            ]);
        }
    }

    private function uploadAficheToMoodle(int $courseId, Request $request): void
    {
        if (!$request->hasFile("image_afiche")) {
            return;
        }

        try {
            $file = $request->file("image_afiche");
            $filename = $file->getClientOriginalName();

            $contextRow = DB::connection("mysql_grupoihb")->select(
                "SELECT id FROM mdl_context
                      WHERE contextlevel = 50 AND instanceid = ? LIMIT 1",
                [$courseId],
            );

            if (empty($contextRow)) {
                return;
            }

            $contextid = $contextRow[0]->id;

            $draftItemId = $this->uploadFileToMoodleDraft($file);

            if (!$draftItemId) {
                return;
            }

            $seccionNombre = "Afiche informativo";

            $seccionExistente = DB::connection("mysql_grupoihb")->select(
                "SELECT id, section FROM mdl_course_sections
                         WHERE course = ? AND name = ? LIMIT 1",
                [$courseId, $seccionNombre],
            );

            if (!empty($seccionExistente)) {
                $sectionId = $seccionExistente[0]->id;
                $sectionNumber = $seccionExistente[0]->section;
            } else {
                DB::connection("mysql_grupoihb")->statement(
                    "UPDATE mdl_course_sections
                                     SET section = section + 1
                                     WHERE course = ? AND section >= 1",
                    [$courseId],
                );

                $sectionNumber = 1;

                DB::connection("mysql_grupoihb")
                    ->table("mdl_course_sections")
                    ->insert([
                        "course" => $courseId,
                        "section" => $sectionNumber,
                        "name" => $seccionNombre,
                        "summary" => "",
                        "summaryformat" => 1,
                        "sequence" => "",
                        "visible" => 1,
                        "timemodified" => time(),
                    ]);
                $sectionId = DB::connection("mysql_grupoihb")
                    ->getPdo()
                    ->lastInsertId();
            }

            DB::connection("mysql_grupoihb")
                ->table("mdl_label")
                ->insert([
                    "course" => $courseId,
                    "name" => $seccionNombre,
                    "intro" => "",
                    "introformat" => 1,
                    "timemodified" => time(),
                ]);
            $labelId = DB::connection("mysql_grupoihb")
                ->getPdo()
                ->lastInsertId();

            $moduleRow = DB::connection("mysql_grupoihb")->select(
                "SELECT id FROM mdl_modules WHERE name = 'label' LIMIT 1",
            );
            $moduleId = $moduleRow[0]->id ?? null;

            if ($moduleId && $sectionId) {
                DB::connection("mysql_grupoihb")
                    ->table("mdl_course_modules")
                    ->insert([
                        "course" => $courseId,
                        "module" => $moduleId,
                        "instance" => $labelId,
                        "section" => $sectionId,
                        "added" => time(),
                        "visible" => 1,
                        "visibleold" => 1,
                        "completion" => 0,
                    ]);
                $cmId = DB::connection("mysql_grupoihb")
                    ->getPdo()
                    ->lastInsertId();

                DB::connection("mysql_grupoihb")->statement(
                    "UPDATE mdl_course_sections
                                     SET sequence = CONCAT(IF(sequence = '' OR sequence IS NULL, '', CONCAT(sequence, ',')), ?)
                                     WHERE id = ?",
                    [$cmId, $sectionId],
                );

                DB::connection("mysql_grupoihb")
                    ->table("mdl_context")
                    ->insert([
                        "contextlevel" => 70,
                        "instanceid" => $cmId,
                        "depth" => 4,
                        "path" => "",
                    ]);
                $cmContextId = DB::connection("mysql_grupoihb")
                    ->getPdo()
                    ->lastInsertId();

                DB::connection("mysql_grupoihb")->statement(
                    "UPDATE mdl_context SET path = CONCAT('/1/', ?, '/', ?) WHERE id = ?",
                    [$contextid, $cmContextId, $cmContextId],
                );

                $newPathnamehash = sha1(
                    "/{$cmContextId}/mod_label/intro/0/{$filename}",
                );

                DB::connection("mysql_grupoihb")
                    ->table("mdl_files")
                    ->where("component", "user")
                    ->where("filearea", "draft")
                    ->where("itemid", $draftItemId)
                    ->where("filename", $filename)
                    ->update([
                        "component" => "mod_label",
                        "filearea" => "intro",
                        "itemid" => 0,
                        "filepath" => "/",
                        "contextid" => $cmContextId,
                        "pathnamehash" => $newPathnamehash,
                        "timemodified" => time(),
                    ]);

                $modalId = "afiche-modal-" . $courseId;

                $htmlAfiche = "
                                            <!-- Modal afiche -->
                                            <div id='{$modalId}' style='
                                                display:none; position:fixed; z-index:99999;
                                                inset:0; background:rgba(0,0,0,0.85);
                                                align-items:center; justify-content:center;
                                            ' onclick='this.style.display=\"none\"'>
                                                <div style='position:relative; max-width:90vw; max-height:90vh;'>
                                                    <img src='@@PLUGINFILE@@/{$filename}'
                                                        alt='Afiche del curso'
                                                        style='
                                                            display:block;
                                                            max-width:90vw; max-height:90vh;
                                                            width:auto; height:auto;
                                                            object-fit:contain;
                                                            border-radius:6px;
                                                            box-shadow:0 8px 32px rgba(0,0,0,0.6);
                                                        ' />
                                                </div>
                                            </div>

                                            <script>
                                            (function () {
                                                var sectionName = '{$seccionNombre}';
                                                var modalId     = '{$modalId}';

                                                function attachTrigger() {
                                                    var headers = document.querySelectorAll(
                                                        '.sectionname, .section-title, h3.sectionname a, ' +
                                                        '[data-sectionname], .course-section-header .sectionname'
                                                    );
                                                    for (var i = 0; i < headers.length; i++) {
                                                        var el = headers[i];
                                                        if (el.textContent.trim() === sectionName && !el.dataset.afficheReady) {
                                                            el.dataset.afficheReady = '1';
                                                            el.style.cursor = 'pointer';
                                                            el.addEventListener('click', function (e) {
                                                                e.preventDefault();
                                                                e.stopPropagation();
                                                                document.getElementById(modalId).style.display = 'flex';
                                                            });
                                                        }
                                                    }
                                                }

                                                if (document.readyState === 'loading') {
                                                    document.addEventListener('DOMContentLoaded', attachTrigger);
                                                } else {
                                                    attachTrigger();
                                                }

                                                if (window.MutationObserver) {
                                                    new MutationObserver(attachTrigger).observe(document.body, { childList: true, subtree: true });
                                                }
                                            })();
                                            </script>
                                        ";

                DB::connection("mysql_grupoihb")
                    ->table("mdl_label")
                    ->where("id", $labelId)
                    ->update([
                        "intro" => $htmlAfiche,
                        "timemodified" => time(),
                    ]);
            }
        } catch (\Throwable $e) {
            Log::error("Error al subir afiche a Moodle", [
                "error" => $e->getMessage(),
                "line" => $e->getLine(),
                "courseId" => $courseId,
            ]);
        }
    }

    private function syncPreguntasWord(
        int $courseId,
        int $aplicaEvaluacion,
        ?string $preguntasWordStr,
    ): void {
        if (!$aplicaEvaluacion || !$preguntasWordStr) {
            return;
        }

        $preguntas = json_decode($preguntasWordStr, true);

        if (empty($preguntas)) {
            return;
        }

        $quizRow = DB::connection("mysql_grupoihb")->select(
            "SELECT id FROM mdl_quiz WHERE course = ? LIMIT 1",
            [$courseId],
        );

        if (!empty($quizRow)) {
            $quizId = $quizRow[0]->id;

            DB::connection("mysql_grupoihb")->statement(
                "
                CALL SP_QUIZ_agregar_preguntas_mc(?, ?, @res_preguntas)",
                [$quizId, json_encode($preguntas)],
            );
        }
    }

    public function saveProgramacion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                "cod_cursos" => "required|integer|exists:sw_cursos,codigo",
                // 'periodo'       => 'required|date_format:Y-m', // Ya no es requerido por input
                "tipo" => "required|in:REGULAR,EXTEMPORANEO",
                "fecha_inicio" => "required|date",
                "fecha_final" => "required|date|after_or_equal:fecha_inicio",
                "habilitado" => "required|integer|in:0,1",
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Errores de validación.",
                        "errors" => $validator->errors(),
                    ],
                    422,
                );
            }

            DB::beginTransaction();

            $curso = Cursos::where("codigo", $request->cod_cursos)->first();
            if (!$curso) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "El curso especificado no existe.",
                    ],
                    404,
                );
            }

            // Derivar periodo desde fecha de inicio
            $periodoCalculado = Carbon::parse($request->fecha_inicio)->format(
                "Y-m",
            );

            // Validar Límite de Periodicidad Anual
            $limit =
                $curso->periodicidad && $curso->periodicidad > 0
                    ? $curso->periodicidad
                    : 1;

            // Determinar año objetivo
            $targetYear = substr($periodoCalculado, 0, 4);

            // Contar programaciones NO eliminadas (habilitado=1) en ese AÑO
            $countProgramaciones = CursoProgramacion::where(
                "cod_cursos",
                $curso->codigo,
            )
                ->where("habilitado", 1)
                ->whereYear("fecha_inicio", $targetYear)
                ->count();

            // Si es un curso NUEVO, el count es 0. Si ya existen N, y N >= Límite, error.
            if ($countProgramaciones >= $limit) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Este curso tiene un límite anual de {$limit} programación(es) para el año {$targetYear}. Ya se han registrado {$countProgramaciones}.",
                    ],
                    409,
                );
            }

            // Lógica de Fechas con Carbon
            // Usar fechas del usuario directamente para AMBOS tipos (REGULAR y EXTEMPORANEO)
            $fechaInicio = Carbon::parse($request->fecha_inicio)
                ->startOfDay()
                ->format("Y-m-d\TH:i:s.000");
            $fechaFinal = Carbon::parse($request->fecha_final)
                ->endOfDay()
                ->format("Y-m-d\TH:i:s.000");

            // Validación de Unicidad Compuesta: Curso + Periodo + Tipo
            // Evita duplicar el mismo tipo de curso en el mismo mes
            $existe = CursoProgramacion::where("cod_cursos", $curso->codigo)
                ->where("periodo", $periodoCalculado)
                ->where("tipo", $request->tipo)
                ->where("habilitado", 1) // Ignore deleted records
                ->exists();

            if ($existe) {
                DB::rollBack();
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Ya existe una programación {$request->tipo} para este curso en el periodo {$periodoCalculado}.",
                    ],
                    409,
                );
            }

            $lastCod = CursoProgramacion::orderBy(
                "codigo_programacion",
                "desc",
            )->first();
            $newCode = $lastCod
                ? str_pad(
                    intval($lastCod->codigo_programacion) + 1,
                    4,
                    "0",
                    STR_PAD_LEFT,
                )
                : "1001";

            $programacion = CursoProgramacion::create([
                "codigo_programacion" => (string) $newCode,
                "cod_cursos" => (int) $curso->codigo,
                "periodo" => $periodoCalculado,
                "tipo" => $request->tipo,
                "fecha_inicio" => $fechaInicio,
                "fecha_final" => $fechaFinal,
                "fecha_creacion" => now()->format("Y-m-d\TH:i:s.000"),
                "habilitado" => (int) $request->habilitado,
            ]);

            if (!$programacion) {
                DB::rollBack();
                return response()->json(
                    ["success" => false, "message" => "Error al registrar."],
                    500,
                );
            }

            DB::commit();

            return response()->json([
                "message" => "Programación registrada correctamente.",
                "success" => true,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error saveProgramacion", [
                "msg" => $e->getMessage(),
                "line" => $e->getLine(),
            ]);
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error interno del servidor.",
                ],
                500,
            );
        }
    }

    public function updateProgramacion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                "codigo" =>
                    "required|integer|exists:sw_cursos_programacion,codigo",
                "cod_cursos" => "required|integer|exists:sw_cursos,codigo",
                // 'periodo'       => 'required|date_format:Y-m', // Ya no requerido
                "tipo" => "required|in:REGULAR,EXTEMPORANEO",
                "fecha_inicio" => "required|date",
                "fecha_final" => "required|date|after_or_equal:fecha_inicio",
                "habilitado" => "required|integer|in:0,1",
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Errores de validación.",
                        "errors" => $validator->errors(),
                    ],
                    422,
                );
            }

            DB::beginTransaction();

            $programacion = CursoProgramacion::where(
                "codigo",
                $request->codigo,
            )->first();
            if (!$programacion) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "La programación especificada no existe.",
                    ],
                    404,
                );
            }

            // Derivar periodo desde fecha de inicio
            $periodoCalculado = Carbon::parse($request->fecha_inicio)->format(
                "Y-m",
            );

            // Lógica de Fechas con Carbon
            // Usar fechas del usuario directamente para AMBOS tipos
            $fechaInicio = Carbon::parse($request->fecha_inicio)
                ->startOfDay()
                ->format("Y-m-d\TH:i:s.000");
            $fechaFinal = Carbon::parse($request->fecha_final)
                ->endOfDay()
                ->format("Y-m-d\TH:i:s.000");

            // Validar unicidad (excluyendo la propia programación)
            $existe = CursoProgramacion::where(
                "cod_cursos",
                $request->cod_cursos,
            )
                ->where("periodo", $periodoCalculado)
                ->where("tipo", $request->tipo)
                ->where("codigo", "!=", $programacion->codigo)
                ->where("habilitado", 1)
                ->exists();

            if ($existe) {
                DB::rollBack();
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Ya existe otra programación {$request->tipo} para este curso en el periodo {$periodoCalculado}.",
                    ],
                    409,
                );
            }

            // Actualizar campos
            $programacion->update([
                "cod_cursos" => (int) $request->cod_cursos,
                "periodo" => $periodoCalculado,
                "tipo" => $request->tipo,
                "fecha_inicio" => $fechaInicio,
                "fecha_final" => $fechaFinal,
                "habilitado" => (int) $request->habilitado,
                "fecha_modificacion" => now()->format("Y-m-d\TH:i:s.000"),
            ]);

            DB::commit();

            return response()->json([
                "message" => "Programación actualizada correctamente.",
                "success" => true,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error updateProgramacion", [
                "msg" => $e->getMessage(),
                "line" => $e->getLine(),
            ]);
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error interno del servidor.",
                ],
                500,
            );
        }
    }

    public function updateProgramacionHab(
        Request $request,
        int $codigo,
    ): JsonResponse {
        $programacion = CursoProgramacion::where(
            "codigo",
            $codigo,
        )->firstOrFail();

        if (!$programacion) {
            return response()->json(
                [
                    "message" => "No se encontró la programación",
                    "success" => false,
                ],
                404,
            );
        }

        $actualizado = $programacion->update([
            "habilitado" => $request->input("habilitado"),
            "fecha_modificacion" => date("Y-m-d\TH:i:s.000"),
        ]);

        if (!$actualizado) {
            return response()->json(
                [
                    "message" => "No se pudo actualizar la programación",
                    "success" => false,
                ],
                500,
            );
        }

        return response()->json([
            "success" => true,
            "message" => "Programación actualizada correctamente",
        ]);
    }

    public function getProgramacionXId(int $id): JsonResponse
    {
        $programacion = CursoProgramacion::where("codigo", $id)->first();

        if (!$programacion) {
            return response()->json(
                [
                    "message" => "No se encontró la programación",
                    "success" => false,
                ],
                404,
            );
        }

        return response()->json([
            "success" => true,
            "programacion" => $programacion,
        ]);
    }

    public function getCursoProgramacionXId(int $id): JsonResponse
    {
        $curso = Cursos::where("codigo", $id)->first();

        if (!$curso) {
            return response()->json(
                [
                    "message" => "No se encontró el curso",
                    "success" => false,
                ],
                404,
            );
        }

        $programaciones = CursoProgramacion::with("curso")
            ->where("cod_cursos", $curso->codigo)
            ->where("habilitado", 1)
            ->get();

        if ($programaciones->isEmpty()) {
            return response()->json([
                "message" => "No se encontraron programaciones para este curso",
                "success" => true,
            ]);
        }

        $programacionesMapped = $programaciones->map(function ($prog) {
            return [
                "codigo" => $prog->codigo,
                "codigo_programacion" => $prog->codigo_programacion,
                "cod_cursos" => $prog->cod_cursos,
                "fecha_inicio" => $prog->fecha_inicio,
                "fecha_final" => $prog->fecha_final,
                "fecha_inicio_texto" => Carbon::parse(
                    $prog->fecha_inicio,
                )->format("d/m/Y"),
                "fecha_final_texto" => Carbon::parse(
                    $prog->fecha_final,
                )->format("d/m/Y"),
                "periodo" => $prog->periodo,
                "tipo" => $prog->tipo,
                "habilitado" => $prog->habilitado,
                "curso" => $prog->curso, // Si se necesita
            ];
        });

        return response()->json([
            "success" => true,
            "programaciones" => $programacionesMapped,
        ]);
    }

    public function getAreas(): JsonResponse
    {
        $areas = CapacitacionAreas::where("habilitado", 1)->get();
        return response()->json($areas);
    }

    public function getClientesForPAC(): JsonResponse
    {
        $raw = DB::select("EXEC SW_LISTAR_CLIENTES");
        $clientes = collect($raw)
            ->map(function ($row) {
                return [
                    "codigo" => $row->codigo,
                    "descripcion" =>
                        $row->abreviatura ?? ($row->razon_social ?? ""),
                ];
            })
            ->sortBy("codigo")
            ->values();
        return response()->json($clientes);
    }

    public function getEmpresasList(): JsonResponse
    {
        $empresas = DB::table("sw_MIGRA_EMPRESA")
            ->select("EMPR_CODIGO as codigo", "Razon_Social as descripcion")
            ->whereIn("EMPR_CODIGO", ["01", "02", "03", "04", "05", "06"])
            ->orderBy("EMPR_CODIGO")
            ->get();
        return response()->json($empresas);
    }
    public function storeProgramacionManual(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                "cod_cursos" => "required|integer|exists:sw_cursos,codigo",
                "fecha_inicio" => "required|date_format:Y-m",
            ]);

            if ($validator->fails()) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Datos inválidos.",
                        "errors" => $validator->errors(),
                    ],
                    422,
                );
            }

            DB::beginTransaction();

            $curso = Cursos::findOrFail($request->cod_cursos);

            $fechaBase = Carbon::parse($request->fecha_inicio . "-01");
            $periodo = $fechaBase->format("Y-m");
            $fInicio = $fechaBase->startOfMonth()->format("Y-m-d\TH:i:s.000");
            $fFinal = $fechaBase->endOfMonth()->format("Y-m-d\TH:i:s.000");

            $lastCod = CursoProgramacion::orderBy(
                "codigo_programacion",
                "desc",
            )->first();
            $newCode = $lastCod
                ? str_pad(
                    intval($lastCod->codigo_programacion) + 1,
                    4,
                    "0",
                    STR_PAD_LEFT,
                )
                : "1001";

            CursoProgramacion::create([
                "codigo_programacion" => (string) $newCode,
                "cod_cursos" => (int) $curso->codigo,
                "periodo" => $periodo,
                "tipo" => "REGULAR",
                "fecha_inicio" => $fInicio,
                "fecha_final" => $fFinal,
                "fecha_creacion" => now()->format("Y-m-d\TH:i:s.000"),
                "habilitado" => 1,
            ]);

            DB::commit();

            return response()->json([
                "success" => true,
                "message" =>
                    "Programación creada exitosamente. Ahora puede matricular personal desde la pestaña de Matrículas.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error storeProgramacionManual", [
                "error" => $e->getMessage(),
                "line" => $e->getLine(),
            ]);
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error al procesar la apertura de ciclo: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getTipoCursos(): JsonResponse
    {
        $tipoCursos = CapacitacionTipoCurso::where("habilitado", 1)->get();
        return response()->json($tipoCursos);
    }

    public function analizarPlantilla(Request $request): JsonResponse
    {
        if (!$request->hasFile("plantilla")) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "No se envió el archivo.",
                ],
                400,
            );
        }

        $file = $request->file("plantilla");
        $tempPath = storage_path("app/temp_mbz_" . uniqid());

        if (File::isDirectory($tempPath)) {
            File::deleteDirectory($tempPath);
        }
        File::makeDirectory($tempPath, 0777, true);

        try {
            $filePath = $tempPath . "/" . $file->getClientOriginalName();
            $file->move($tempPath, $file->getClientOriginalName());

            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (in_array($ext, ["gz", "mbz"])) {
                try {
                    $phar = new \PharData($filePath);
                    $tarPath = preg_replace('/\.gz$/i', "", $filePath);
                    $phar->decompress(); // genera .tar
                    $pharTar = new \PharData($tarPath);
                    $pharTar->extractTo($tempPath, null, true);
                    @unlink($tarPath);
                } catch (\Throwable $e) {
                }
            }

            if ($ext === "zip" || !File::isDirectory($tempPath . "/course")) {
                $zip = new \ZipArchive();
                if ($zip->open($filePath) === true) {
                    $zip->extractTo($tempPath);
                    $zip->close();
                }
            }

            $findFile = function (string $base, string $name) {
                $rii = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $base,
                        \FilesystemIterator::SKIP_DOTS,
                    ),
                );
                foreach ($rii as $f) {
                    if ($f->isFile() && $f->getFilename() === $name) {
                        return $f->getPathname();
                    }
                }
                return null;
            };

            $xmlFile = $findFile($tempPath, "moodle_backup.xml");
            if (!$xmlFile) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "El archivo no contiene moodle_backup.xml",
                    ],
                    400,
                );
            }

            $xml = simplexml_load_file($xmlFile);
            $info = $xml->information ?? null;
            if (!$info) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "moodle_backup.xml no contiene la sección <information>.",
                    ],
                    400,
                );
            }

            $courseName =
                (string) ($info->original_course_fullname ?? "Sin nombre");
            $courseShort = (string) ($info->original_course_shortname ?? "");
            $backupDate = (string) ($info->backup_date ?? null);
            $moodleVersion = (string) ($info->moodle_version ?? null);

            $totalSections = 0;
            $totalActivities = 0;
            if (isset($info->contents->sections->section)) {
                $sectionsNode = $info->contents->sections->section;
                $totalSections = is_array($sectionsNode)
                    ? count($sectionsNode)
                    : count(iterator_to_array($sectionsNode));
                $totalSections = count($info->contents->sections->section);
            }

            if (isset($info->contents->activities->activity)) {
                $totalActivities = count($info->contents->activities->activity);
            }

            $activityStats = [];

            $mapaTipos = [
                "quiz" => "Examen",
                "label" => "Diapositivas",
                "assign" => "Tarea",
                "forum" => "Foro",
                "resource" => "Recurso",
                "url" => "Enlace",
                "page" => "Página",
                "other" => "Otro",
            ];

            if (isset($info->contents->activities->activity)) {
                foreach ($info->contents->activities->activity as $act) {
                    $mod = (string) $act->modulename;
                    if ($mod === "") {
                        $mod = "other";
                    }

                    $nombre = $mapaTipos[$mod] ?? ucfirst($mod);

                    if (!isset($activityStats[$nombre])) {
                        $activityStats[$nombre] = 0;
                    }
                    $activityStats[$nombre]++;
                }
            }

            $totalQuestions = 0;
            $questionsFile = $findFile($tempPath, "questions.xml");
            if ($questionsFile && file_exists($questionsFile)) {
                $questionsDoc = simplexml_load_file($questionsFile);
                foreach ($questionsDoc->question_category as $category) {
                    if (isset($category->questions->question)) {
                        foreach ($category->questions->question as $q) {
                            if (((string) $q->parent) === "0") {
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
                                if (((string) $q->parent) === "0") {
                                    $totalQuestions++;
                                }
                            }
                        }
                    }
                }
            }

            return response()->json([
                "success" => true,
                "courseName" => $courseName,
                "courseShortname" => $courseShort,
                "backupDate" => $backupDate,
                "moodleVersion" => $moodleVersion,
                "totalSections" => (int) $totalSections,
                "totalActivities" => (int) $totalActivities,
                "activityStats" => $activityStats,
                "totalQuestions" => (int) $totalQuestions,
            ]);
        } catch (\Throwable $e) {
            // Loggear detalles para debugging
            Log::error("Error procesando plantilla", [
                "error" => $e->getMessage(),
                "line" => $e->getLine(),
                "file" => $e->getFile(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error al procesar la plantilla. Verifique el archivo e inténtelo nuevamente.",
                ],
                500,
            );
        } finally {
            if (File::isDirectory($tempPath)) {
                File::deleteDirectory($tempPath);
            }
        }
    }

    public function saveMatricula(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "cursoId" => "required|integer|exists:sw_cursos,codigo",
            "programacionId" =>
                "required|integer|exists:sw_cursos_programacion,codigo",
            "personalIds" => "required|array|max:100",
            "personalIds.*" => "required|string",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Errores de validación.",
                    "errors" => $validator->errors(),
                ],
                422,
            );
        }

        $cursoId = $request->cursoId;
        $programacionId = $request->programacionId; // Capturar programacionId
        $personalIds = $request->personalIds;

        // Validación adicional del límite de 100
        if (count($personalIds) > 100) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "No puede matricular más de 100 personas por operación.",
                ],
                422,
            );
        }

        // Obtener el ID del usuario autenticado
        $usuarioId = Auth::id();

        // Ejecutamos el proceso de matriculación sincrónicamente.
        DispatchMatriculaBatchJob::dispatchSync(
            $cursoId,
            $programacionId,
            $personalIds,
            $usuarioId,
        );

        return response()->json([
            "success" => true,
            "message" => "Matriculación completada exitosamente.",
        ]);
    }

    public function getMatriculasPorCurso(int $cursoId): JsonResponse
    {
        try {
            $curso = Cursos::find($cursoId);

            if (!$curso) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Curso no encontrado",
                    ],
                    404,
                );
            }

            $matriculas = Matricula::where("cod_curso", $cursoId)
                ->with(["personal"])
                ->orderBy("fecha_matricula", "desc")
                ->get()
                ->map(function ($matricula) {
                    $personal = $matricula->personal;
                    return [
                        "codigo" => $matricula->codigo,
                        "cod_personal" => $matricula->cod_personal,
                        "nombre_completo" => $personal
                            ? trim(
                                "{$personal->ape_paterno} {$personal->ape_materno} {$personal->nombres}",
                            )
                            : "No encontrado",
                        "dni" => $personal->dni ?? "N/A",
                        "correo" => $personal->correo ?? "N/A",
                        "cargo" => $personal->cargo ?? "N/A",
                        "fecha_matricula" => $matricula->fecha_matricula,
                        "estado" => $matricula->estado ?? "MATRICULADO",
                        "tipo_matricula" =>
                            $matricula->tipo_matricula ?? "VIRTUAL",
                        "origen_matricula" =>
                            $matricula->origen_matricula ?? "INTRANET",
                    ];
                });

            return response()->json([
                "success" => true,
                "curso" => [
                    "codigo" => $curso->codigo,
                    "nombre" => $curso->nombre,
                    "codigo_curso" => $curso->codigo_curso,
                ],
                "matriculas" => $matriculas,
                "total" => $matriculas->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener matrículas del curso", [
                "curso_id" => $cursoId,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al obtener las matrículas del curso",
                ],
                500,
            );
        }
    }

    public function getHistorialCapacitaciones(string $personalId): JsonResponse
    {
        try {
            $historial = DB::table("sw_matriculas as m")
                ->join("sw_cursos as c", "m.cod_curso", "=", "c.codigo")
                ->leftJoin(
                    "sw_cursos_programacion as prog",
                    "m.cod_programacion",
                    "=",
                    "prog.codigo",
                )
                ->where("m.cod_personal", $personalId)
                ->select([
                    "c.codigo as codigo_curso",
                    "c.nombre as nombre_curso",
                    "m.tipo_matricula",
                    "m.fecha_matricula",
                    "m.estado",
                    "prog.fecha_inicio",
                    "prog.fecha_final",
                ])
                ->orderBy("m.fecha_matricula", "desc")
                ->get();

            return response()->json([
                "success" => true,
                "historial" => $historial,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener historial de capacitaciones", [
                "personal_id" => $personalId,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error al obtener el historial de capacitaciones",
                ],
                500,
            );
        }
    }

    public function buscarPersonalCapacitacion(Request $request): JsonResponse
    {
        try {
            // 1. Obtener personal de la fuente oficial (si_solm.dbo.PERSONAL)
            $tipoTrabMap = [
                "01" => "Operativo",
                "02" => "Administrativo",
                "03" => "Operativo",
                "05" => "Administrativo",
                "06" => "Especial",
            ];
            $rawPersonal = DB::connection("sqlsrv")->select("
                SELECT
                    P.CODI_PERS as codigo,
                    LTRIM(RTRIM(P.APEL_1 + ' ' + ISNULL(P.APEL_2, '') + ' ' + P.NOMB_1 + ' ' + ISNULL(P.NOMB_2, ''))) as personal,
                    P.NRO_DOCU_IDEN as nroDoc,
                    S.SUCU_ABREVIATURA as sucursal,
                    P.PERS_TIPOTRAB as TIPOTRAB,
                    P.PERS_VIGENCIA as VIGENCIA,
                    P.PERS_EMAIL as email
                FROM si_solm.dbo.PERSONAL P
                LEFT JOIN dbo.sw_MIGRA_SISO_SUCURSAL S ON P.SUCU_CODIGO = S.SUCU_CODIGO
                WHERE P.PERS_VIGENCIA = 'SI'
            ");

            // Se unifica el mapeo y el filtro de búsqueda en una sola pasada.
            $personal = [];
            $searchTerm = strtoupper(trim($request->input("q", "")));

            foreach ($rawPersonal as $p) {
                $nombre = strtoupper($p->personal);
                $dni = $p->nroDoc;
                $cod = $p->codigo;

                if ($searchTerm !== "") {
                    if (
                        strpos($nombre, $searchTerm) === false &&
                        strpos($dni, $searchTerm) === false
                    ) {
                        continue;
                    }
                }

                $personal[] = [
                    "codigo" => trim($cod),
                    "nombre_completo" => $nombre,
                    "dni" => $dni,
                    "cargo" => $p->TIPOTRAB ?? "N/A", // Usar TIPOTRAB del resultado SQL
                    "sucursal" => $p->sucursal ?? "N/A",
                ];
            }
            // -------------------------------------------------------------

            // 2. Cargar conteos de matrículas (Optimizado: una sola query para todos)
            // Se usa el JOIN con sw_cursos para que coincida exactamente con las filas del Historial (Modal)
            $matriculasCounts = DB::table("sw_matriculas as m")
                ->join("sw_cursos as c", "m.cod_curso", "=", "c.codigo")
                ->select("m.cod_personal", DB::raw("count(*) as total"))
                ->groupBy("m.cod_personal")
                ->pluck("total", "m.cod_personal")
                ->toArray();

            // --- VERIFICAR MATRÍCULA EN CURSO ACTUAL (SAFE) ---
            $matriculadosEnCurso = [];
            if ($request->filled("cursoId")) {
                try {
                    $cursoId = $request->cursoId;
                    // Obtener códigos de personal ya matriculados en este curso
                    // Se usa try-catch y query simple para evitar 500 errors si faltan columnas
                    $matriculadosEnCurso = Matricula::where(
                        "cod_curso",
                        $cursoId,
                    )
                        ->pluck("cod_personal")
                        ->map(fn($id) => (string) $id)
                        ->toArray();
                } catch (\Exception $e) {
                    Log::error(
                        "Error verificando enrollments: " . $e->getMessage(),
                    );
                    $matriculadosEnCurso = [];
                }
            }
            // ------------------------------------------

            // 3. Mapear resultados en memoria
            $personal = array_map(function ($p) use (
                $matriculasCounts,
                $matriculadosEnCurso,
                $tipoTrabMap,
            ) {
                // Estandarización de campos
                $codigo =
                    $p->CODI_PERS ?? ($p->codi_pers ?? ($p->codigo ?? ""));
                $nombre =
                    $p->personal ??
                    ($p->nombre ?? ($p->nombre_completo ?? "Desconocido"));
                $dni = $p->nroDoc ?? ($p->dni ?? ($p->NRO_DOCU_IDEN ?? ""));
                // Se lee desde cargo o TIPOTRAB, ya que SW_LISTAR_PERSONAL_X_SUCURSAL devuelve TIPOTRAB con "ADMIN"/"OPER"
                $cargo =
                    $p->cargo ?? ($p->desc_cargo ?? ($p->TIPOTRAB ?? "N/A"));
                $sucursal = $p->sucursal ?? "N/A";
                $email = $p->email ?? "";

                $tipoLabel = $tipoTrabMap[$cargo] ?? "DESCONOCIDO";

                return [
                    "codigo" => $codigo,
                    "nombre_completo" => $nombre,
                    "dni" => $dni,
                    "cargo" => $tipoLabel,
                    "tipo_label" => $tipoLabel,
                    "sucursal" => $sucursal,
                    "email" => $email,
                    "matriculado" => in_array(
                        (string) $codigo,
                        $matriculadosEnCurso,
                    ),
                    "total_capacitaciones" => $matriculasCounts[$codigo] ?? 0,
                ];
            }, $rawPersonal);

            // --- NUEVO: FILTRO INTELIGENTE PAC ---
            if ($request->filled("cursoId")) {
                $cursoId = $request->cursoId;
                // Obtener sucursales asignadas al curso
                $sucursalesPermitidas = DB::table("sw_curso_sucursales")
                    ->where("curso_codigo", $cursoId)
                    ->pluck("sucursal")
                    ->map(fn($s) => strtoupper(trim($s))) // Normalizar
                    ->toArray();

                if (!empty($sucursalesPermitidas)) {
                    $personal = array_filter($personal, function ($item) use (
                        $sucursalesPermitidas,
                    ) {
                        return in_array(
                            strtoupper(trim($item["sucursal"])),
                            $sucursalesPermitidas,
                        );
                    });
                }
            }
            // -------------------------------------

            // 4. Filtrado opcional del lado del servidor
            $termino = strtolower($request->input("q", ""));
            if (!empty($termino)) {
                $personal = array_filter($personal, function ($item) use (
                    $termino,
                ) {
                    return str_contains(
                        strtolower($item["nombre_completo"] ?? ""),
                        $termino,
                    ) || str_contains(strval($item["dni"] ?? ""), $termino);
                });
            }

            // Filtrado por sucursal
            $sucursal = $request->input("sucursal", "");
            if (!empty($sucursal)) {
                $personal = array_filter($personal, function ($item) use (
                    $sucursal,
                ) {
                    return strtoupper(trim($item["sucursal"])) ===
                        strtoupper(trim($sucursal));
                });
            }

            // Re-indexar array después de filtrar
            $personal = array_values($personal);

            return response()->json([
                "success" => true,
                "personal" => $personal,
                "total" => count($personal),
            ]);
        } catch (\Exception $e) {
            Log::error(
                "Error al buscar personal para capacitación: " .
                    $e->getMessage(),
            );
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al cargar la lista de personal",
                    "error" => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getCombosApertura(): JsonResponse
    {
        try {
            $sucursales = DB::table("sw_MIGRA_SISO_SUCURSAL")
                ->select("SUCU_CODIGO as codigo", "SUCU_ABREVIATURA as nombre")
                ->whereNotNull("SUCU_ABREVIATURA")
                ->distinct()
                ->orderBy("SUCU_ABREVIATURA")
                ->get();

            $clientes = DB::table("sw_clientes")
                ->select("codigo", "abreviatura as nombre")
                ->where("habilitado", 1)
                ->orderBy("abreviatura")
                ->get();

            $areas = DB::table("sw_MIGRA_REDO_AREA")
                ->select("AREA_CODIGO as codigo", "AREA_DESCRIPCION as nombre")
                ->orderBy("AREA_DESCRIPCION")
                ->get();

            return response()->json([
                "success" => true,
                "sucursales" => $sucursales,
                "clientes" => $clientes,
                "areas" => $areas,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ["success" => false, "message" => $e->getMessage()],
                500,
            );
        }
    }

    public function vistaConsultaMatriculas(Request $request): View
    {
        $cursoId = $request->query("curso_id");
        return view("capacitacion.consulta_matriculas", compact("cursoId"));
    }

    public function vistaGestionCursos(): View
    {
        $dirigidos = \App\Models\Consulta::obtenerDirigidos();
        return view("capacitacion.gestion_cursos", compact("dirigidos"));
    }

    public function vistaHistorialCapacitaciones(): View
    {
        return view("capacitacion.historial_capacitaciones");
    }

    public function vistaSeguimientoMatriculas(): View
    {
        return view("capacitacion.seguimiento_matriculas");
    }

    public function vistaReportesCapacitaciones(): View
    {
        return view("capacitacion.reportes_capacitaciones");
    }

    public function getMatriculasMigraPersonal(int $cursoId): JsonResponse
    {
        try {
            $matriculas = DB::table("sw_matriculas as m")
                ->leftJoin(
                    "sw_cursos_programacion as prog",
                    "m.cod_programacion",
                    "=",
                    "prog.codigo",
                )
                ->where("m.cod_curso", "=", $cursoId)
                ->select([
                    "m.cod_personal",
                    "m.fecha_matricula",
                    "m.estado",
                    "prog.fecha_inicio as prog_fecha_inicio",
                    "prog.fecha_final as prog_fecha_final",
                ])
                ->get();

            if ($matriculas->isEmpty()) {
                return response()->json([
                    "success" => true,
                    "matriculas" => [],
                    "total" => 0,
                ]);
            }

            $codigosPersonal = $matriculas
                ->pluck("cod_personal")
                ->map(
                    fn($id) => str_pad(
                        trim((string) $id),
                        5,
                        "0",
                        STR_PAD_LEFT,
                    ),
                )
                ->unique()
                ->values()
                ->toArray();

            $personalData = collect();

            $chunks = array_chunk($codigosPersonal, 2000);
            foreach ($chunks as $chunk) {
                $batch = DB::table("si_solm.dbo.PERSONAL")
                    ->whereIn("CODI_PERS", $chunk)
                    ->select([
                        "CODI_PERS as cod_personal",
                        "NRO_DOCU_IDEN as dni",
                        DB::raw(
                            "LTRIM(RTRIM(ISNULL(APEL_1, ''))) + ' ' + LTRIM(RTRIM(ISNULL(APEL_2, ''))) + ' ' + LTRIM(RTRIM(ISNULL(NOMB_1, ''))) + ' ' + LTRIM(RTRIM(ISNULL(NOMB_2, ''))) as nombre_completo",
                        ),
                        "PERS_EMAIL as correo",
                        "CODI_CARG as cargo",
                        "SUCU_CODIGO",
                        "EMPR_CODIGO",
                    ])
                    ->get()
                    ->map(function ($item) {
                        $item->cod_personal = str_pad(
                            trim($item->cod_personal),
                            5,
                            "0",
                            STR_PAD_LEFT,
                        );
                        return $item;
                    });

                $personalData = $personalData->merge($batch);
            }

            $personalData = $personalData->keyBy("cod_personal");

            $codigosSucursal = $personalData
                ->pluck("SUCU_CODIGO")
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
                        DB::table("sw_MIGRA_SISO_SUCURSAL")
                            ->whereIn("SUCU_CODIGO", $chunk)
                            ->select(
                                "SUCU_CODIGO",
                                "SUCU_ABREVIATURA",
                                "EMPR_CODIGO",
                            )
                            ->get(),
                    );
                }
                foreach ($sucRows as $suc) {
                    $sucursalesMap[$suc->SUCU_CODIGO] = $suc->SUCU_ABREVIATURA;
                    $sucursalClienteMap[$suc->SUCU_CODIGO] = $suc->EMPR_CODIGO;
                }
            }

            $curso = Consulta::obtenerTipoDeCurso($cursoId);

            $tipoDesc = $curso
                ? strtoupper($curso->tipo_descripcion ?? "")
                : "";
            $esPCU = str_contains($tipoDesc, "PCU");
            $esPCI = str_contains($tipoDesc, "PCI");
            $esPCE = str_contains($tipoDesc, "PCE");
            $sucursalClienteNameMap = [];
            if ($esPCU) {
                $assignedClients = DB::table("sw_curso_sucursales")
                    ->where("curso_codigo", $cursoId)
                    ->pluck("sucursal")
                    ->toArray();

                if (!empty($assignedClients)) {
                    $clientDetails = DB::table("sw_clientes")
                        ->whereIn("codigo", $assignedClients)
                        ->get([
                            "codigo",
                            "cod_legacy",
                            "abreviatura",
                            "razon_social",
                        ]);

                    foreach ($clientDetails as $cd) {
                        $legacyCode = $cd->cod_legacy;
                        $clientName =
                            $cd->abreviatura ??
                            ($cd->razon_social ?? (string) $cd->codigo);

                        if ($legacyCode) {
                            $externalSucursales = DB::connection(
                                "sqlsrv_controlclientes",
                            )->select(
                                "EXEC USP_LISTAR_SUCURSALES_X_CLIENTE :cod_legacy",
                                ["cod_legacy" => $legacyCode],
                            );

                            foreach ($externalSucursales as $es) {
                                if (isset($es->codigo_sucursal)) {
                                    $sucursalClienteNameMap[
                                        trim($es->codigo_sucursal)
                                    ] = $clientName;
                                }
                            }
                        }
                    }
                }
            }

            // 5c. Para PCI y PCE: cargar mapa de empresas internas (Normalizado a 2 dígitos)
            $empresasMap = [];
            if ($esPCI || $esPCE) {
                $empresas = DB::table("sw_MIGRA_EMPRESA")
                    ->select("EMPR_CODIGO", "Razon_Social")
                    ->get();
                foreach ($empresas as $e) {
                    // Normalizar a 2 dígitos (ej: "1" -> "01")
                    $code = str_pad(
                        trim($e->EMPR_CODIGO),
                        2,
                        "0",
                        STR_PAD_LEFT,
                    );
                    $empresasMap[$code] = $e->Razon_Social;
                }
            }

            // 6. Obtener IDs de Moodle por DNI para permitir desmatriculación
            $dnis = $personalData->pluck("dni")->filter()->unique()->toArray();
            $moodleUsersMap = [];
            if (!empty($dnis)) {
                $moodleUsers = DB::connection("mysql_grupoihb")
                    ->table("mdl_user")
                    ->whereIn("username", $dnis)
                    ->orWhereIn("idnumber", $dnis)
                    ->get(["id", "username", "idnumber"]);

                foreach ($moodleUsers as $mu) {
                    if ($mu->username) {
                        $moodleUsersMap[$mu->username] = $mu->id;
                    }
                    if ($mu->idnumber) {
                        $moodleUsersMap[$mu->idnumber] = $mu->id;
                    }
                }
            }

            // 7. Unir datos en memoria
            $resultado = $matriculas
                ->map(function ($m) use (
                    $personalData,
                    $sucursalesMap,
                    $sucursalClienteNameMap,
                    $esPCU,
                    $esPCI,
                    $esPCE,
                    $empresasMap,
                    $moodleUsersMap,
                ) {
                    $id = str_pad(
                        trim((string) $m->cod_personal),
                        5,
                        "0",
                        STR_PAD_LEFT,
                    );
                    $p = $personalData->get($id);

                    // Resolver cliente/empresa según tipo de curso
                    $clienteEmpresa = "-";
                    if ($esPCU && $p && isset($p->SUCU_CODIGO)) {
                        $clienteEmpresa =
                            $sucursalClienteNameMap[trim($p->SUCU_CODIGO)] ??
                            "-";
                    } elseif (
                        ($esPCI || $esPCE) &&
                        $p &&
                        isset($p->EMPR_CODIGO)
                    ) {
                        $codeNormalizer = str_pad(
                            trim($p->EMPR_CODIGO),
                            2,
                            "0",
                            STR_PAD_LEFT,
                        );
                        $clienteEmpresa =
                            $empresasMap[$codeNormalizer] ?? $p->EMPR_CODIGO;
                    }

                    $dni = $p->dni ?? null;
                    $moodleUserId = $dni ? $moodleUsersMap[$dni] ?? null : null;

                    return [
                        "cod_personal" => $id,
                        "dni" => $dni ?? "N/A",
                        "nombre_completo" =>
                            $p->nombre_completo ??
                            "Personal no encontrado (Retirado)",
                        "correo" => $p->correo ?? "N/A",
                        "cargo" => $p->cargo ?? "N/A",
                        "cliente_empresa" => $clienteEmpresa,
                        "fecha_matricula" => $m->fecha_matricula,
                        "estado" => $m->estado,
                        "prog_fecha_inicio" => $m->prog_fecha_inicio,
                        "prog_fecha_final" => $m->prog_fecha_final,
                        "sucursal" =>
                            isset($p->SUCU_CODIGO) &&
                            isset($sucursalesMap[$p->SUCU_CODIGO])
                                ? $sucursalesMap[$p->SUCU_CODIGO]
                                : "Sin sede",
                        "moodle_user_id" => $moodleUserId,
                    ];
                })
                ->sortBy("nombre_completo")
                ->values();

            return response()->json([
                "success" => true,
                "matriculas" => $resultado,
                "total" => $resultado->count(),
            ]);
        } catch (\Exception $e) {
            Log::error(
                "Error en getMatriculasMigraPersonal rediseñado: " .
                    $e->getMessage(),
            );
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error al cargar matrículas: " . $e->getMessage(),
                ],
                500,
            );
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

                $fechaInicioProgramacion = Carbon::parse(
                    $programacion->fecha_inicio,
                )->startOfDay();
                $fechaProximaClonacion = $fechaInicioProgramacion->copy();

                // Quitar espacios extra en la base de datos SQL Server e identificar tipo
                $frecuencia = trim(strtoupper($programacion->frecuencia));
                switch ($frecuencia) {
                    case "MENSUAL":
                        $fechaProximaClonacion->addMonth();
                        break;
                    case "BIMESTRAL":
                        $fechaProximaClonacion->addMonths(2);
                        break;
                    case "TRIMESTRAL":
                        $fechaProximaClonacion->addMonths(3);
                        break;
                    case "CUATRIMESTRAL":
                        $fechaProximaClonacion->addMonths(4);
                        break;
                    case "SEMESTRAL":
                        $fechaProximaClonacion->addMonths(6);
                        break;
                    case "ANUAL":
                        $fechaProximaClonacion->addYear();
                        break;
                    default:
                        continue 2; // Salta al siguiente iterador del loop
                }

                // Condición ESTRICTA: fecha_proxima_clonacion BETWEEN hoy AND (hoy + 15 días)
                // Se utiliza greaterThanOrEqualTo (>= hoy) y lessThanOrEqualTo (<= limite de 15 días)
                if (
                    $fechaProximaClonacion->greaterThanOrEqualTo($hoy) &&
                    $fechaProximaClonacion->lessThanOrEqualTo($limite)
                ) {
                    $diasRestantes = $hoy->diffInDays(
                        $fechaProximaClonacion,
                        false,
                    );
                    $alertas[] = [
                        "codigo_curso" => $programacion->codigo_curso,
                        "nombre" => $programacion->curso_nombre,
                        "fecha_inicio_actual" => $fechaInicioProgramacion->format(
                            "Y-m-d",
                        ),
                        "fecha_proxima_clonacion" => $fechaProximaClonacion->format(
                            "Y-m-d",
                        ),
                        "dias_restantes" => ceil($diasRestantes),
                    ];
                }
            }

            return response()->json([
                "success" => true,
                "alertas" => $alertas,
                "total" => count($alertas),
            ]);
        } catch (\Exception $e) {
            Log::error("Error en getAlertasVencimiento: " . $e->getMessage(), [
                "trace" => $e->getTraceAsString(),
            ]);
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al obtener alertas de cursos",
                ],
                500,
            );
        }
    }

    public function getSucursales(): JsonResponse
    {
        try {
            $sucursales = Consulta::obtenerSucursales();
            return response()->json([
                "success" => true,
                "sucursales" => $sucursales,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ["success" => false, "message" => "Error."],
                500,
            );
        }
    }

    public function getPersonalPorSucursal(string $sucursalId): JsonResponse
    {
        try {
            $personal = Consulta::getPersonalPorSucursal($sucursalId);
            return response()->json([
                "success" => true,
                "personal" => $personal,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                ["success" => false, "message" => "Error al obtener personal: " . $e->getMessage()],
                500,
            );
        }
    }

    public function getAreasEncargadas(): JsonResponse
    {
        try {
            $areas = Consulta::obtenerAreasEncargadas();
            return response()->json([
                "success" => true,
                "areas" => $areas,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error al obtener áreas encargadas: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getAreasPorSistema(int $sistemaId): JsonResponse
    {
        try {
            $areas = Consulta::obtenerAreasPorSistema($sistemaId);
            return response()->json([
                "success" => true,
                "areas" => $areas,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error al obtener áreas por sistema: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getCursosPorCategoria(int $categoryId): JsonResponse
    {
        try {
            $cursos = DB::connection("mysql_grupoihb")->select(
                "
                SELECT
                    c.id,
                    c.fullname,
                    c.startdate,
                    c.enddate,
                    c.summary,
                    GROUP_CONCAT(CONCAT(u.firstname, ' ', u.lastname) SEPARATOR ', ') AS responsible
                FROM grupoihb_see.mdl_course c
                LEFT JOIN grupoihb_see.mdl_context ctx
                    ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                LEFT JOIN grupoihb_see.mdl_role_assignments ra ON ra.contextid = ctx.id
                LEFT JOIN grupoihb_see.mdl_role r ON r.id = ra.roleid
                LEFT JOIN grupoihb_see.mdl_user u ON u.id = ra.userid
                    AND r.shortname = 'editingteacher'
                WHERE c.category = ?
                GROUP BY c.id, c.fullname, c.startdate, c.enddate, c.summary
            ",
                [$categoryId],
            );

            return response()->json([
                "success" => true,
                "cursos" => $cursos,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error al obtener cursos por categoría: " .
                        $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function procesarExamenWord(Request $request): JsonResponse
    {
        try {
            if (!$request->hasFile("archivo")) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "No se cargó ningún archivo.",
                    ],
                    400,
                );
            }

            $file = $request->file("archivo");
            $extension = strtolower($file->getClientOriginalExtension());

            if ($extension === "doc" || $extension === "dot") {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "El formato .doc es antiguo. Por favor guarda el archivo como .docx para permitir la extracción por estilos.",
                    ],
                    422,
                );
            }

            $path = $file->getRealPath();

            if (!$path || !file_exists($path)) {
                return response()->json(
                    [
                        "success" => false,
                        "message" =>
                            "El archivo no se pudo encontrar en el servidor.",
                    ],
                    400,
                );
            }

            $phpWord = IOFactory::load($path);
            $preguntas = [];
            $currentPregunta = null;

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text = "";
                    $isCorrectByStyle = false;
                    $isCorrectByColor = false;
                    $type = get_class($element);

                    // 1. Obtener Estilo del Párrafo
                    $styleName = "";
                    if (method_exists($element, "getParagraphStyle")) {
                        $pStyle = $element->getParagraphStyle();
                        if ($pStyle) {
                            $styleName = is_string($pStyle)
                                ? $pStyle
                                : (method_exists($pStyle, "getStyleName")
                                    ? $pStyle->getStyleName()
                                    : "");
                        }
                    }

                    if ($styleName === "RightAnswer") {
                        $isCorrectByStyle = true;
                    }

                    // 2. Extraer texto y detectar color manual (fallback)
                    if (method_exists($element, "getElements")) {
                        foreach ($element->getElements() as $child) {
                            if (method_exists($child, "getText")) {
                                $partText = $child->getText();
                                $text .= $partText;

                                if (method_exists($child, "getFontStyle")) {
                                    $font = $child->getFontStyle();
                                    if ($font) {
                                        $color = $font->getColor();
                                        // Detectar verdes comunes
                                        if (
                                            $color &&
                                            (strpos($color, "00B0") !== false ||
                                                in_array($color, [
                                                    "008000",
                                                    "92D050",
                                                    "00FF00",
                                                    "228B22",
                                                ]))
                                        ) {
                                            $isCorrectByColor = true;
                                        }
                                    }
                                }
                            }
                        }
                    } elseif (method_exists($element, "getText")) {
                        $text = $element->getText();
                    }

                    $text = trim($text);
                    if (empty($text)) {
                        continue;
                    }

                    // 3. IDENTIFICAR PREGUNTA
                    $esPregunta =
                        $styleName === "MultipleChoiceQ" ||
                        strpos($type, "ListItem") !== false ||
                        preg_match("/^\d+[\.\)]\s+/", $text);

                    if ($esPregunta) {
                        if ($currentPregunta) {
                            $preguntas[] = $currentPregunta;
                        }

                        // Extraer la respuesta correcta del final del texto (ej: "Pregunta... A")
                        // NOTA: Solo lo usaremos si no detectamos RightAnswer en las opciones
                        $respuestaCandidata = "A";
                        if (preg_match('/\s+([A-E])$/', $text, $matches)) {
                            $respuestaCandidata = $matches[1];
                            $text = preg_replace('/\s+[A-E]$/', "", $text);
                        }

                        $currentPregunta = [
                            "texto" => preg_replace(
                                "/^\d+[\.\)]\s+/",
                                "",
                                $text,
                            ),
                            "opciones" => [],
                            "respuesta_correcta" => $respuestaCandidata, // Valor por defecto
                            "manual_correct_found" => false, // Flag para saber si ya encontramos la rpta por color/estilo
                            "tipo" => "multiple",
                        ];
                    }
                    // 4. IDENTIFICAR OPCIÓN
                    elseif ($currentPregunta) {
                        $opcionLimpia = preg_replace(
                            "/^[o\-\*]\s+/",
                            "",
                            $text,
                        );
                        $currentPregunta["opciones"][] = $opcionLimpia;

                        // Si detectamos que es correcta por ESTILO o COLOR
                        if ($isCorrectByStyle || $isCorrectByColor) {
                            $index = count($currentPregunta["opciones"]) - 1;
                            $currentPregunta["respuesta_correcta"] = chr(
                                65 + $index,
                            );
                            $currentPregunta["manual_correct_found"] = true;
                        }
                    }
                }
            }

            if ($currentPregunta) {
                $preguntas[] = $currentPregunta;
            }

            // Limpieza final de campos internos
            foreach ($preguntas as &$p) {
                unset($p["manual_correct_found"]);
            }

            return response()->json([
                "success" => true,
                "preguntas" => $preguntas,
                "total" => count($preguntas),
                "debug_info" =>
                    "Procesado con detección de Estilos (RightAnswer)",
            ]);
        } catch (\Exception $e) {
            Log::error(
                "PHPWord Style Parser Error: " .
                    $e->getMessage() .
                    " at " .
                    $e->getLine(),
            );
            return response()->json(
                ["success" => false, "message" => $e->getMessage()],
                500,
            );
        }
    }

    public function guardarExamenWord(Request $request): JsonResponse
    {
        $request->validate([
            "cod_curso" => "required",
            "preguntas" => "required|array",
        ]);

        try {
            DB::beginTransaction();

            // Buscar el examen vinculado al curso
            $examen = ExamenCurso::where(
                "cod_cursos",
                $request->cod_curso,
            )->first();

            if (!$examen) {
                // Si no existe, lo creamos con valores por defecto
                $curso = Cursos::where("codigo", $request->cod_curso)->first();
                $examen = ExamenCurso::create([
                    "cod_cursos" => $request->cod_curso,
                    "nombre" => "Examen de " . ($curso->nombre ?? "Curso"),
                    "intentos" => 1,
                    "nota_minima" => 14,
                    "tiempo" => 20,
                    "fecha_creacion" => now(),
                ]);
            }

            // Limpiar preguntas previas (reemplazo total de la carga actual)
            ExamenPregunta2026::where("cod_examen", $examen->codigo)->delete();

            foreach ($request->preguntas as $p) {
                ExamenPregunta2026::create([
                    "cod_examen" => $examen->codigo,
                    "tipo_pregunta" => $p["tipo"] ?? "multiple",
                    "texto_pregunta" => $p["texto"],
                    "opciones_json" => $p["opciones"],
                    "respuesta_correcta" => $p["respuesta_correcta"] ?? "A",
                    "estado_revision" => "PENDIENTE",
                    "fecha_creacion" => now(),
                ]);
            }

            DB::commit();
            return response()->json([
                "success" => true,
                "message" =>
                    "Examen guardado correctamente con " .
                    count($request->preguntas) .
                    " preguntas.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error guardando examen Word: " . $e->getMessage());
            return response()->json(
                ["success" => false, "message" => $e->getMessage()],
                500,
            );
        }
    }

    public function desmatricularUsuario(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "cursoId" => "required|integer",
            "codPersonal" => "required|string",
            "moodleUserId" => "nullable|integer",
            "observacion" => "nullable|string|max:200",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Datos inválidos",
                    "errors" => $validator->errors(),
                ],
                422,
            );
        }

        try {
            $curso = Cursos::find($request->cursoId);
            if (!$curso) {
                return response()->json(
                    ["success" => false, "message" => "Curso no encontrado"],
                    404,
                );
            }

            $codPersonal = str_pad(
                trim($request->codPersonal),
                5,
                "0",
                STR_PAD_LEFT,
            );

            $deleted = DB::table("sw_matriculas")
                ->where("cod_curso", $request->cursoId)
                ->where("cod_personal", $codPersonal)
                ->delete();

            if ($deleted === 0) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "No se encontró la matrícula local",
                    ],
                    404,
                );
            }

            $moodleUserId = $request->moodleUserId;

            if (!$moodleUserId) {
                $personal = DB::table("si_solm.dbo.PERSONAL")
                    ->where("CODI_PERS", $codPersonal)
                    ->first(["NRO_DOCU_IDEN"]);
                if ($personal && $personal->NRO_DOCU_IDEN) {
                    $moodleUser = DB::connection("mysql_grupoihb")
                        ->table("mdl_user")
                        ->where("username", trim($personal->NRO_DOCU_IDEN))
                        ->orWhere("idnumber", trim($personal->NRO_DOCU_IDEN))
                        ->first(["id"]);
                    if ($moodleUser) {
                        $moodleUserId = $moodleUser->id;
                    }
                }
            }

            if ($moodleUserId) {
                $moodleCourse = DB::connection("mysql_grupoihb")
                    ->table("mdl_course")
                    ->where("id", $curso->codigo_moodle)
                    ->first(["idnumber"]);

                if (!$moodleCourse || !$moodleCourse->idnumber) {
                    return response()->json(
                        [
                            "success" => false,
                            "message" => "El curso no tiene idnumber en Moodle",
                        ],
                        404,
                    );
                }

                $courseIdNumber = $moodleCourse->idnumber;
                $observacion =
                    $request->observacion ?: "Desmatriculación desde Intranet";

                $resultado = DB::connection("mysql_grupoihb")->select(
                    "SELECT F_USER_matricula_eliminar2(?, ?, ?, ?, ?) AS result",
                    [$moodleUserId, $courseIdNumber, "00001", $observacion, 5],
                );

                $estado = $resultado[0]->result ?? null;

                Log::info(
                    "F_USER_matricula_eliminar2 result: estado={$estado}, userId={$moodleUserId}, course={$courseIdNumber}",
                );

                if ($estado === -2) {
                    return response()->json(
                        [
                            "success" => false,
                            "message" => "Curso '{$courseIdNumber}' no encontrado en Moodle",
                        ],
                        404,
                    );
                }

                if ($estado === -1) {
                    return response()->json(
                        [
                            "success" => false,
                            "message" => "Usuario Moodle ID {$moodleUserId} no encontrado o eliminado",
                        ],
                        404,
                    );
                }

                if ($estado !== 1) {
                    return response()->json(
                        [
                            "success" => false,
                            "message" => "Error inesperado en Moodle (estado: {$estado})",
                        ],
                        500,
                    );
                }
            }

            return response()->json([
                "success" => true,
                "message" =>
                    "Usuario desmatriculado correctamente" .
                    ($moodleUserId
                        ? " y sincronizado con Moodle"
                        : " (Solo local, no se encontró ID Moodle)"),
            ]);
        } catch (\Exception $e) {
            Log::error("Error en desmatricularUsuario: " . $e->getMessage());
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al desmatricular: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function getCursosSeguimiento(): JsonResponse
    {
        $cursos = DB::select("
            SELECT
                c.codigo,
                c.codigo_curso,
                c.nombre,
                c.codigo_moodle,
                c.cod_responsable,
                c.fecha_creacion,
                ISNULL(mat.total_matriculados, 0) as total_matriculados
            FROM sw_cursos c
            LEFT JOIN (
                SELECT cod_curso, COUNT(*) as total_matriculados
                FROM sw_matriculas
                GROUP BY cod_curso
            ) mat ON c.codigo = mat.cod_curso
            WHERE c.habilitado = 1
            AND c.codigo_moodle IS NOT NULL
            AND c.codigo_moodle != ''
            AND c.codigo_moodle != 0
            ORDER BY c.fecha_creacion DESC
        ");

        $result = array_map(function ($curso) {
            $nombreResponsable = "";
            if ($curso->cod_responsable) {
                $resp = DB::connection("sqlsrv")->selectOne(
                    "
                    SELECT LTRIM(RTRIM(APEL_1 + ' ' + ISNULL(APEL_2, '') + ' ' + NOMB_1 + ' ' + ISNULL(NOMB_2, ''))) as nombre
                    FROM si_solm.dbo.PERSONAL
                    WHERE CODI_PERS = ?
                ",
                    [$curso->cod_responsable],
                );
                $nombreResponsable = $resp->nombre ?? "";
            }

            return [
                "codigo" => $curso->codigo,
                "codigo_curso" => $curso->codigo_curso,
                "codigo_moodle" => $curso->codigo_moodle,
                "nombre" => $curso->nombre,
                "responsable" => $nombreResponsable,
                "total_matriculados" => (int) $curso->total_matriculados,
                "fecha_creacion" => $curso->fecha_creacion,
            ];
        }, $cursos);

        return response()->json($result);
    }

    public function getUsuariosCursoMoodle(int $moodleCourseId): JsonResponse
    {
        try {
            $sinIniciar = DB::connection("mysql_grupoihb")->select(
                "CALL grupoihb_see.SP_OBTENER_MATRICULADOS_SIN_INICIAR(?)",
                [$moodleCourseId],
            );

            $enProgreso = DB::connection("mysql_grupoihb")->select(
                "CALL grupoihb_see.SP_OBTENER_MATRICULADOS_EN_PROGRESO(?)",
                [$moodleCourseId],
            );

            $usuarios = array_merge(
                array_map(
                    fn($u) => [
                        "full_name" => $u->full_name ?? "",
                        "email" => $u->email ?? "",
                        "username" => $u->username ?? "", // ← agregar
                        "estado" => "SIN_INICIAR",
                        "enrolment_start_date" =>
                            $u->enrolment_start_date ?? null,
                    ],
                    $sinIniciar,
                ),
                array_map(
                    fn($u) => [
                        "full_name" => $u->full_name ?? "",
                        "email" => $u->email ?? "",
                        "username" => $u->username ?? "", // ← agregar
                        "estado" => "EN_PROGRESO",
                        "enrolment_start_date" =>
                            $u->enrolment_start_date ?? null,
                    ],
                    $enProgreso,
                ),
            );

            return response()->json([
                "success" => true,
                "moodle_course_id" => $moodleCourseId,
                "usuarios" => $usuarios,
                "total_sin_iniciar" => count($sinIniciar),
                "total_en_progreso" => count($enProgreso),
                "total" => count($usuarios),
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener usuarios del curso", [
                "moodle_course_id" => $moodleCourseId,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al obtener los usuarios del curso",
                ],
                500,
            );
        }
    }

    public function getCursosAlumno(string $dni): JsonResponse
    {
        try {
            $cursos = DB::connection("mysql_grupoihb")->select(
                "CALL SP_GET_CURSOS_ALUMNO_ESTADO(NULL, ?)",
                [$dni],
            );

            $resultado = array_map(
                fn($c) => [
                    "course_id" => $c->course_id ?? null,
                    "course_codigo" => $c->course_codigo ?? "",
                    "course_nombre" => $c->course_nombre ?? "",
                    "course_corto" => $c->course_corto ?? "",
                    "area" => $c->course_categoria ?? "",
                    "fecha_inicio_matricula" =>
                        $c->fecha_inicio_matricula ?? null,
                    "fecha_fin_matricula" => $c->fecha_fin_matricula ?? null,
                    "fecha_matricula" => $c->fecha_matricula ?? null,
                    "ultimo_acceso" => $c->ultimo_acceso ?? null,
                    "fecha_finalizacion" => $c->fecha_finalizacion ?? null,
                    "estado" => $c->estado,
                ],
                $cursos,
            );

            $totales = [
                "total" => count($resultado),
                "en_curso" => count(
                    array_filter(
                        $resultado,
                        fn($c) => $c["estado"] === "en_curso",
                    ),
                ),
                "sin_iniciar" => count(
                    array_filter(
                        $resultado,
                        fn($c) => $c["estado"] === "sin_iniciar",
                    ),
                ),
                "finalizados" => count(
                    array_filter(
                        $resultado,
                        fn($c) => $c["estado"] === "finalizado",
                    ),
                ),
            ];

            return response()->json([
                "success" => true,
                "dni" => $dni,
                "cursos" => $resultado,
                "totales" => $totales,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al obtener cursos del alumno", [
                "dni" => $dni,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al obtener los cursos del alumno",
                ],
                500,
            );
        }
    }

    public function obtenerPersonalParaReporte(Request $request): JsonResponse
    {
        try {
            $courseId = $request->courseId;
            $sucursalId = $request->sucursalId;

            $personal = Consulta::obtenerPersonalPorSucursal_Reporte(
                $sucursalId,
            );

            $personalUnico = collect($personal)
                ->sortByDesc("FECH_INGRE")
                ->unique("NRO_DOCU_IDEN")
                ->values();

            $matriculados = collect(
                DB::connection("mysql_grupoihb")->select(
                    "CALL grupoihb_see.SP_OBTENER_MATRICULADOS_SIN_INICIAR(?)",
                    [$courseId],
                ),
            );

            $resultado = $personalUnico
                ->filter(function (object $persona) use ($matriculados) {
                    return $matriculados->contains(
                        fn(object $matriculado) => trim(
                            $matriculado->username,
                        ) === trim($persona->NRO_DOCU_IDEN),
                    );
                })
                ->map(function (object $persona) {
                    $tipoMap = [
                        "01" => "Operativo",
                        "02" => "Administrativo",
                        "03" => "Operativo",
                        "05" => "Administrativo",
                    ];

                    return [
                        "CodigoPers" => $persona->CODI_PERS,
                        "NombreCompleto" => trim(
                            $persona->APEL_1 .
                                " " .
                                $persona->APEL_2 .
                                " " .
                                $persona->NOMB_1 .
                                " " .
                                $persona->NOMB_2,
                        ),
                        "DNI" => $persona->NRO_DOCU_IDEN,
                        "Cargo" => $persona->cargo ?? "Sin asignar",
                        "TipoTrabajador" =>
                            $tipoMap[$persona->PERS_TIPOTRAB] ??
                            $persona->PERS_TIPOTRAB,
                        "Estado" => "SIN INICIAR",
                    ];
                })
                ->values();

            return response()->json([
                "success" => true,
                "total" => $resultado->count(),
                "personal" => $resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => $e->getMessage(),
                ],
                500,
            );
        }
    }

    public function saveReporteCapacitacion(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "nombre_archivo" => "required|string|max:255",
            "descripcion" => "nullable|string|max:500",
            "archivo_pdf" => "nullable|file|mimes:pdf|max:51200",
            "archivo_excel" => "nullable|file|mimes:xlsx,xls|max:51200",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Errores de validación.",
                    "errors" => $validator->errors(),
                ],
                422,
            );
        }

        try {
            $data = [
                "nombre_archivo" => $request->nombre_archivo,
                "descripcion" => $request->input("descripcion", ""),
                "archivo_pdf" => null,
                "archivo_excel" => null,
            ];

            if ($request->hasFile("archivo_pdf")) {
                $data["archivo_pdf"] = file_get_contents(
                    $request->file("archivo_pdf")->getRealPath(),
                );
            }

            if ($request->hasFile("archivo_excel")) {
                $data["archivo_excel"] = file_get_contents(
                    $request->file("archivo_excel")->getRealPath(),
                );
            }

            $id = CapacitacionReporteHistorial::crearReporte($data);

            return response()->json([
                "success" => true,
                "message" => "Reporte de capacitación registrado correctamente.",
                "id" => $id,
            ]);
        } catch (\Exception $e) {
            Log::error("Error al registrar reporte de capacitación", [
                "error" => $e->getMessage(),
                "line" => $e->getLine(),
                "file" => $e->getFile(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error al registrar el reporte. Por favor, contacte al administrador.",
                ],
                500,
            );
        }
    }

    public function listarReportesCapacitaciones(): JsonResponse
    {
        try {
            $reportes = CapacitacionReporteHistorial::obtenerReportesHabilitados()
                ->map(function ($reporte) {
                    return [
                        "id" => $reporte->id,
                        "nombre_archivo" => $reporte->nombre_archivo,
                        "descripcion" => $reporte->descripcion,
                        "tiene_pdf" => !is_null($reporte->archivo_pdf),
                        "tiene_excel" => !is_null($reporte->archivo_excel),
                        "fecha_creacion" => $reporte->fecha_creacion,
                        "fecha_actualizacion" => $reporte->fecha_actualizacion,
                        "habilitado" => (bool) $reporte->habilitado,
                    ];
                });

            return response()->json([
                "success" => true,
                "reportes" => $reportes,
                "total" => $reportes->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error al listar reportes de capacitación", [
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" =>
                        "Error al obtener los reportes. Por favor, contacte al administrador.",
                ],
                500,
            );
        }
    }

    public function descargarReporte(int $id, string $tipo)
    {
        try {
            $reporte = DB::connection('sqlsrv')
                ->table('sw_capacitacion_reportes_historial')
                ->where('id', $id)
                ->where('habilitado', 1)
                ->first();

            if (!$reporte) {
                return response()->json([
                    "success" => false,
                    "message" => "Reporte no encontrado.",
                ], 404);
            }

            $columna = $tipo === 'pdf' ? 'archivo_pdf' : 'archivo_excel';
            $archivo = $reporte->$columna;

            if (is_null($archivo)) {
                return response()->json([
                    "success" => false,
                    "message" => "El archivo solicitado no existe.",
                ], 404);
            }

            $mimeType = $tipo === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            $extension = $tipo === 'pdf' ? '.pdf' : '.xlsx';

            $nombreBase = preg_replace('/\.(pdf|xlsx?)$/i', '', $reporte->nombre_archivo);
            $nombreDescarga = $nombreBase . $extension;

            return response($archivo)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'attachment; filename="' . $nombreDescarga . '"');
        } catch (\Exception $e) {
            Log::error("Error al descargar reporte", [
                "error" => $e->getMessage(),
                "id" => $id,
                "tipo" => $tipo,
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al descargar el archivo.",
                ],
                500,
            );
        }
    }

    public function actualizarReporte(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "nombre_archivo" => "nullable|string|max:255",
            "descripcion" => "nullable|string|max:500",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Errores de validación.",
                    "errors" => $validator->errors(),
                ],
                422,
            );
        }

        try {
            $exists = DB::connection('sqlsrv')
                ->table('sw_capacitacion_reportes_historial')
                ->where('id', $id)
                ->where('habilitado', 1)
                ->exists();

            if (!$exists) {
                return response()->json([
                    "success" => false,
                    "message" => "Reporte no encontrado.",
                ], 404);
            }

            $setClauses = [];
            $bindings = [];

            if ($request->filled("nombre_archivo")) {
                $setClauses[] = "nombre_archivo = ?";
                $bindings[] = $request->nombre_archivo;
            }
            if ($request->has("descripcion")) {
                $setClauses[] = "descripcion = ?";
                $bindings[] = $request->descripcion;
            }
            $setClauses[] = "fecha_actualizacion = GETDATE()";
            $bindings[] = $id;

            DB::connection('sqlsrv')->statement(
                "UPDATE sw_capacitacion_reportes_historial SET " . implode(', ', $setClauses) . " WHERE id = ?",
                $bindings
            );

            return response()->json([
                "success" => true,
                "message" => "Reporte actualizado correctamente.",
            ]);
        } catch (\Exception $e) {
            Log::error("Error al actualizar reporte", [
                "error" => $e->getMessage(),
                "id" => $id,
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al actualizar el reporte.",
                ],
                500,
            );
        }
    }

    public function actualizarEstadoReporte(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            "habilitado" => "required|boolean",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Errores de validación.",
                    "errors" => $validator->errors(),
                ],
                422,
            );
        }

        try {
            $exists = DB::connection('sqlsrv')
                ->table('sw_capacitacion_reportes_historial')
                ->where('id', $id)
                ->exists();

            if (!$exists) {
                return response()->json([
                    "success" => false,
                    "message" => "Reporte no encontrado.",
                ], 404);
            }

            $habilitado = $request->boolean('habilitado') ? 1 : 0;

            DB::connection('sqlsrv')->statement(
                "UPDATE sw_capacitacion_reportes_historial SET habilitado = ?, fecha_actualizacion = GETDATE() WHERE id = ?",
                [$habilitado, $id]
            );

            return response()->json([
                "success" => true,
                "message" => $habilitado
                    ? "Reporte recuperado correctamente."
                    : "Reporte eliminado correctamente.",
            ]);
        } catch (\Exception $e) {
            Log::error("Error al actualizar estado del reporte", [
                "error" => $e->getMessage(),
                "id" => $id,
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al actualizar el reporte.",
                ],
                500,
            );
        }
    }
}