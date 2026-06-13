<?php

namespace App\Http\Controllers;

use App\Mail\MemoMail;
use Illuminate\Support\Facades\Mail;
use App\Models\CapacitacionAreas;
use App\Models\CapacitacionTipoCurso;
use App\Models\CursoProgramacion;
use App\Models\Cursos;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use App\Models\ExamenCurso;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Jobs\MatriculaMasivaJob;
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
        $query = Cursos::with('tipoCurso');

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
            ->pluck("cod_curso")
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
                "tiene_vigente" => in_array($curso->codigo, $cursosVigentes),
                "codigo_moodle" => $curso->codigo_moodle,
                "cod_cliente" => $curso->cod_cliente,
                "dirigido_a" => $curso->dirigido_a,
                "tipo_curso" => $curso->tipoCurso?->descripcion,
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

        if ($curso->tipo_curso != 6) {
            unset($curso->cod_cliente);
        }

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

        $curso->tiene_vigente = DB::table("sw_cursos_programacion")
            ->where("cod_curso", $id)
            ->where("estado_periodo", "VIGENTE")
            ->where("habilitado", 1)
            ->exists();

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
            "nullable|exists:sw_capacitacion_areas,codigo",
            "area_responsable" => "required|integer",
            "es_periodico" => "required|integer|in:0,1",
            "frecuencia" => "nullable|string",
            "fechas_generadas" => "nullable|string",
            "nombre_exa" => "nullable|string",
            "descripcion" => "nullable|string",
            "tiempo" => "nullable|required_if:aplica_evaluacion,1|integer|min:5",
            "nota" => "nullable|required_if:aplica_evaluacion,1|integer|min:5",
            "intentos" => "nullable|required_if:aplica_evaluacion,1|integer|min:1",
            "cantidad_preguntas" => "nullable|required_if:aplica_evaluacion,1|integer|min:5",
            "preguntas_balotario" => "nullable|required_if:aplica_evaluacion,1|integer|min:5",
            "archivo" => "nullable|file|max:51200",
            "aplica_evaluacion" => "nullable|integer|in:0,1",
            "obligatorio_alta" => "nullable|integer|in:0,1",
            "cod_responsable" => "required|string|max:20",
            "target_group" =>
            "nullable|string|in:TODOS,ADMINISTRATIVO,OPERATIVO",
            "cod_moodle_area" => "nullable|integer",
            "dirigido_a" => "required",
        ]);

        $validator->sometimes("area_conocimiento", "required", function ($input) {
            return in_array($input->tipo_curso, [5, 7]);
        });

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
                $periodicidadVal = match ($request->input("frecuencia")) {
                    "MENSUAL" => 1,
                    "BIMESTRAL" => 2,
                    "TRIMESTRAL" => 3,
                    "CUATRIMESTRAL" => 4,
                    "SEMESTRAL" => 6,
                    "ANUAL" => 12,
                    default => 0,
                };
            }

            $curso->update([
                "nombre" => $request->nombre,
                "tipo_curso" => $request->tipo_curso,
                "area_conocimiento" => $request->area_conocimiento,
                "area" => $request->area_responsable,
                "periodicidad" => $periodicidadVal,
                "es_periodico" => $request->input("es_periodico"),
                "frecuencia" => $request->input("frecuencia"),
                "aplica_evaluacion" => $request->input("aplica_evaluacion", 0),
                "obligatorio_alta" => $request->input("obligatorio_alta", 0),
                "cod_responsable" => $request->input("cod_responsable"),
                "target_group" => $request->input("target_group", "TODOS"),
                "descripcion" => $request->input("descripcion"),
                "dirigido_a" => $request->input("dirigido_a") === "OTROS" ? 0 : $request->input("dirigido_a"),
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
                            "cod_curso",
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
                                "cod_curso" => $curso->codigo,
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

    public function actualizarCurso(Request $request, int $codigo): JsonResponse
    {
        // ? Campos que se actualizaran tanto en Moodle como en Sisolmar web
        $nombre = $request->input('nombre');
        $descripcion = $request->input('descripcion');
        $codMoodleArea = $request->input('cod_moodle_area');
        $areaResponsable = $request->input('area_responsable');
        $codResponsable = $request->input('cod_responsable');

        // ? Campos que se actualizaran solamente en Sisolmar web
        $areaConocimiento = $request->input('area_conocimiento');
        $dirigidoA = $request->input('dirigido_a');
        $tipoCurso = $request->input('tipo_curso');
        $frecuencia = $request->input('frecuencia');

        DB::beginTransaction();

        try {
            $curso = Cursos::where('codigo', $codigo)->firstOrFail();
            $codigoMoodle = $curso->codigo_moodle;

            $updateData = ['fecha_modificacion' => now()->format('Y-m-d\TH:i:s.000')];
            $moodleData = [];

            if ($nombre) {
                $updateData['nombre'] = $nombre;
                $moodleData['fullname'] = $nombre;
            }

            if ($descripcion) {
                $updateData['descripcion'] = $descripcion;
                $moodleData['summary'] = $descripcion;
            }

            if ($areaConocimiento) {
                $updateData['area_conocimiento'] = $areaConocimiento;
            }

            if ($dirigidoA) {
                $updateData['dirigido_a'] = $dirigidoA;
            }

            if ($areaResponsable) {
                $updateData['area'] = $areaResponsable;
            }

            if ($codMoodleArea) {
                $moodleData['category'] = $codMoodleArea;
            }

            if ($codResponsable) {
                $updateData['cod_responsable'] = $codResponsable;

                $personal = DB::connection('sqlsrv')->selectOne(
                    "SELECT LTRIM(RTRIM(NRO_DOCU_IDEN)) AS dni
                        FROM si_solm.dbo.PERSONAL
                        WHERE CODI_PERS = ?",
                    [$codResponsable]
                );

                DB::connection('mysql_grupoihb')->statement(
                    "CALL SP_REEMPLAZAR_RESPONSABLE(?, ?, @resultado)",
                    [
                        $codigoMoodle,
                        $personal->dni
                    ]
                );
            }

            if ($frecuencia) {
                $updateData['frecuencia'] = $frecuencia;
                $updateData['periodicidad'] = match ($frecuencia) {
                    'MENSUAL' => 1,
                    'BIMESTRAL' => 2,
                    'TRIMESTRAL' => 3,
                    'CUATRIMESTRAL' => 4,
                    'SEMESTRAL' => 6,
                    'ANUAL' => 12,
                    default => 0,
                };
            }

            if ($tipoCurso) {
                $oldTipoCurso = $curso->tipo_curso;
                $updateData['tipo_curso'] = $tipoCurso;

                if ((int) $oldTipoCurso === 5 && (int) $tipoCurso === 6) {
                    $updateData['area_conocimiento'] = null;
                    $codCliente = $request->input('cod_cliente');
                    if ($codCliente) {
                        $updateData['cod_cliente'] = $codCliente;
                    }
                } elseif ((int) $oldTipoCurso === 6 && (int) $tipoCurso === 5) {
                    $updateData['cod_cliente'] = null;
                    $areaConocimientoValue = $request->input('area_conocimiento');
                    if ($areaConocimientoValue) {
                        $updateData['area_conocimiento'] = $areaConocimientoValue;
                    }
                }
            } elseif ($request->has('cod_cliente')) {
                $updateData['cod_cliente'] = $request->input('cod_cliente');
            }

            $curso->update($updateData);

            if ($codigoMoodle && !empty($moodleData)) {
                DB::connection('mysql_grupoihb')
                    ->table('mdl_course')
                    ->where('id', $codigoMoodle)
                    ->update($moodleData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Datos actualizado(s) correctamente.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el curso.',
            ], 500);
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
            CursoProgramacion::where("cod_curso", $curso->codigo)->delete();

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
                "nullable|exists:sw_capacitacion_areas,codigo",
                "area_responsable" => "required|integer",
                "es_periodico" => "required|integer|in:0,1",
                "frecuencia" => "nullable|string",
                "fechas_generadas" => "nullable|string",
                "fecha_inicio" => "nullable|date",
                "fecha_final" => "nullable|date",
                "nombre_exa" => "nullable|string",
                "descripcion" => "nullable|string",
                "tiempo" => "nullable|required_if:aplica_evaluacion,1|integer|min:5",
                "nota" => "nullable|required_if:aplica_evaluacion,1|integer|min:5",
                "intentos" =>
                "nullable|required_if:aplica_evaluacion,1|integer|min:1",
                "cantidad_preguntas" => "nullable|required_if:aplica_evaluacion,1|integer|min:5",
                "preguntas_balotario" => "nullable|required_if:aplica_evaluacion,1|integer|min:5",
                "archivo" => "nullable|file|max:51200",
                "sucursales_asignadas" => "nullable|array",
                "sucursales_asignadas.*" => "integer|exists:sw_clientes,codigo",
                "aplica_evaluacion" => "nullable|integer|in:0,1",
                "obligatorio_alta" => "nullable|integer|in:0,1",
                "cod_responsable" => "required|string|max:20",
                "target_group" =>
                "nullable|string|in:TODOS,ADMINISTRATIVO,OPERATIVO",
                "cod_moodle_area" => "nullable|integer",
                "dirigido_a" => "required",
                "image_portada" => "nullable|image|mimes:jpeg,jpg,png|max:1990",
                "image_afiche" => "nullable|image|mimes:jpeg,jpg,png|max:1990",
            ]);

            $validator->sometimes("area_conocimiento", "required", function ($input) {
                return in_array($input->tipo_curso, [5, 7]);
            });

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

            $codClienteLegacy = null;
            if (
                $request->input("tipo_curso") == "6" &&
                $request->has("sucursales_asignadas") &&
                is_array($request->sucursales_asignadas) &&
                count($request->sucursales_asignadas) > 0
            ) {
                $clienteId = $request->sucursales_asignadas[0];
                $cliente = DB::table("sw_clientes")
                    ->select("cod_legacy")
                    ->where("codigo", $clienteId)
                    ->first();
                if ($cliente) {
                    $codClienteLegacy = (string) $cliente->cod_legacy;
                }
            }

            $curso = Cursos::create([
                "nombre" => $request->nombre,
                "codigo_curso" => $this->generateCourseCode(),
                "tipo_curso" => $request->tipo_curso,
                "cod_cliente" => $codClienteLegacy,
                "area_conocimiento" => $request->area_conocimiento,
                "area" => $request->area_responsable,
                "periodicidad" => $this->calculatePeriodicidad(
                    $request->input("frecuencia"),
                    (int) $request->input("es_periodico", 0),
                ),
                "es_periodico" => $request->input("es_periodico", 0),
                "frecuencia" => $request->input("frecuencia"),
                "aplica_evaluacion" => $request->input("aplica_evaluacion", 0),
                "obligatorio_alta" => $request->input("obligatorio_alta", 0),
                "cod_responsable" => $request->input("cod_responsable"),
                "target_group" => $request->input("target_group", "TODOS"),
                "descripcion" => $request->input("descripcion"),
                "dirigido_a" => $request->input("dirigido_a") === "OTROS" ? 0 : $request->input("dirigido_a"),
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

            if ($finTimestamp === null && $request->input("frecuencia") === "PERSONALIZADO" && $request->filled("fecha_inicio") && $request->filled("fecha_final")) {
                $inicioTimestamp = Carbon::parse($request->input("fecha_inicio"))->startOfDay()->timestamp;
                $finTimestamp = Carbon::parse($request->input("fecha_final"))->endOfDay()->timestamp;
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
                        (int) ($request->cantidad_preguntas ?? 0),
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
        return match ($frecuencia) {
            "MENSUAL" => 1,
            "BIMESTRAL" => 2,
            "TRIMESTRAL" => 3,
            "CUATRIMESTRAL" => 4,
            "SEMESTRAL" => 6,
            "ANUAL" => 12,
            default => 0,
        };
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
                "cod_curso" => $curso->codigo,
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

        $email = !empty(trim($personal->PERS_EMAIL ?? '')) && filter_var(trim($personal->PERS_EMAIL), FILTER_VALIDATE_EMAIL)
            ? trim($personal->PERS_EMAIL)
            : "{$dni}@sisolmar.com";

        DB::connection("mysql_grupoihb")->statement(
            "CALL SP_CREAR_USUARIO(?, ?, ?, ?, ?, @resultado)",
            [
                $dni,
                'Gpo$olSEE_1@',
                $firstname,
                $lastname,
                $email,
            ]
        );

        $res = DB::connection("mysql_grupoihb")->select(
            "SELECT @resultado AS user_id"
        );

        $moodleUserId = (int) ($res[0]->user_id ?? 0);

        if ($moodleUserId <= 0) {
            Log::error(
                "Error al crear usuario en Moodle para DNI {$dni}. Resultado: {$moodleUserId}"
            );

            return null;
        }

        return $moodleUserId;
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
        float $nota,
        int $inicio,
        int $fin,
    ): ?int {
        try {
            $moodleUserId = $this->getOrCreateMoodleUser($codResponsable);

            DB::connection("mysql_grupoihb")->statement(
                "CALL SP_CREAR_CURSO(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @resultado)",
                [
                    $nombre,
                    $codigo,
                    $descripcion,
                    $codMoodleArea,
                    $moodleUserId,
                    $aplicaEvaluacion,
                    $tiempo,
                    $intentos,
                    $nota,
                    $inicio,
                    $fin,
                ]
            );

            $res = DB::connection("mysql_grupoihb")->select(
                "SELECT @resultado AS resultado"
            );

            $resultado = (int) ($res[0]->resultado ?? 0);

            if ($resultado <= 0) {
                Log::warning('SP_CREAR_CURSO devolvió un código de error', [
                    'resultado' => $resultado,
                    'codigo_curso' => $codigo,
                ]);

                return null;
            }

            return $resultado;
        } catch (\Throwable $e) {
            Log::error('Error al crear curso en Moodle', [
                'error' => $e->getMessage(),
                'codigo_curso' => $codigo,
                'responsable' => $codResponsable,
            ]);

            return null;
        }
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
        int $cantidadPreguntas = 0,
    ): void {
        if (!$aplicaEvaluacion || !$preguntasWordStr) {
            return;
        }
        
        $preguntas = json_decode($preguntasWordStr, true);

        if (empty($preguntas)) {
            return;
        }

        if ($cantidadPreguntas > 0) {
            $mitad = intdiv($cantidadPreguntas, 2);

            $basicas = array_values(
                array_filter($preguntas, fn($p) => ($p['tipo'] ?? '') === 'A'),
            );
            $complementarias = array_values(
                array_filter($preguntas, fn($p) => ($p['tipo'] ?? '') === 'B'),
            );

            shuffle($basicas);
            shuffle($complementarias);

            $preguntas = array_merge(
                array_slice($basicas, 0, $mitad),
                array_slice($complementarias, 0, $mitad),
            );

            if (empty($preguntas)) {
                Log::warning(
                    "syncPreguntasWord: no se pudo seleccionar {$cantidadPreguntas} preguntas " .
                        "(básicas: " . count($basicas) . ", complementarias: " . count($complementarias) . ")",
                );
                return;
            }
        }

        $quizRow = DB::connection("mysql_grupoihb")->select(
            "SELECT id FROM mdl_quiz WHERE course = ? LIMIT 1",
            [$courseId],
        );

        if (!empty($quizRow)) {
            $quizId = $quizRow[0]->id;

            $preguntasTransformadas = $this->transformarPreguntas($preguntas);

            $jsonPreguntas = json_encode(
                $preguntasTransformadas,
                JSON_UNESCAPED_UNICODE
            );

            Log::info(
                'JSON SP_INSERTAR_PREGUNTAS_R => ' . $jsonPreguntas
            );

            DB::connection("mysql_grupoihb")->statement(
                "
                CALL SP_INSERTAR_PREGUNTAS_R(?, ?, @res_preguntas)",
                [
                    $quizId,
                    json_encode(
                        $preguntasTransformadas,
                        JSON_UNESCAPED_UNICODE
                    ),
                ],
            );

            $resultado = DB::connection("mysql_grupoihb")->selectOne(
                "SELECT @res_preguntas AS resultado"
            );

            Log::info('Resultado SP_INSERTAR_PREGUNTAS_R', [
                'quiz_id' => $quizId,
                'resultado' => $resultado->resultado ?? null,
            ]);
        }
    }

    private function transformarPreguntas(array $preguntas): array
    {
        return array_map(function ($pregunta) {

            $opciones = $pregunta['opciones'] ?? [];
            $respuesta = $pregunta['respuesta_correcta'] ?? '';

            if (
                count($opciones) === 2 &&
                $opciones[0] === 'Verdadero' &&
                $opciones[1] === 'Falso'
            ) {
                return [
                    'tipo_pregunta' => 'true_or_false',
                    'texto' => $pregunta['texto'],
                    'respuesta_correcta' => $respuesta === 'A',
                ];
            }

            $indiceRespuesta = ord($respuesta) - ord('A');

            return [
                'tipo_pregunta' => 'multiple_choice',
                'texto' => $pregunta['texto'],
                'opciones' => $opciones,
                'respuesta_correcta' => $opciones[$indiceRespuesta] ?? null,
            ];
        }, $preguntas);
    }

    public function saveProgramacion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                "cod_curso" => "required|integer|exists:sw_cursos,codigo",
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

            $curso = Cursos::where("codigo", $request->cod_curso)->first();
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
            // periodicidad = meses por ciclo (ej: 3 = trimestral), límite = 12 / meses_por_ciclo
            $limit =
                $curso->periodicidad && $curso->periodicidad > 0
                ? intdiv(12, $curso->periodicidad)
                : 1;

            // Determinar año objetivo
            $targetYear = substr($periodoCalculado, 0, 4);

            // Contar programaciones NO eliminadas (habilitado=1) en ese AÑO
            $countProgramaciones = CursoProgramacion::where(
                "cod_curso",
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
            $existe = CursoProgramacion::where("cod_curso", $curso->codigo)
                ->where("periodo", $periodoCalculado)
                ->where("tipo", $request->tipo)
                ->where("habilitado", 1)
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
                "cod_curso" => (int) $curso->codigo,
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
                "cod_curso" => "required|integer|exists:sw_cursos,codigo",
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
                "cod_curso",
                $request->cod_curso,
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
                "cod_curso" => (int) $request->cod_curso,
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
            ->where("cod_curso", $curso->codigo)
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
                "cod_curso" => $prog->cod_curso,
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
                "estado_periodo" => $prog->estado_periodo,
                "curso" => $prog->curso, // Si se necesita
            ];
        });

        return response()->json([
            "success" => true,
            "programaciones" => $programacionesMapped,
        ]);
    }

    public function obtenerSistemas(): JsonResponse
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
                    "cod_legacy" => $row->cod_legacy,
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
            DB::beginTransaction();

            $curso = DB::table('sw_cursos as c')
                ->leftJoin('sw_capacitacion_tipo_curso as tc', 'tc.codigo', '=', 'c.tipo_curso')
                ->where('c.codigo', $request->cod_curso)
                ->select('c.*', 'tc.descripcion as tipo_curso_descripcion')
                ->first();

            $fechaInicio = Carbon::parse($request->fecha_inicio);

            $fechaFin = match ($curso->frecuencia) {
                "PERSONALIZADO"  => Carbon::parse($request->fecha_final),
                "BIMESTRAL" => $fechaInicio->copy()->addMonths(2),
                "TRIMESTRAL" => $fechaInicio->copy()->addMonths(3),
                "CUATRIMESTRAL" => $fechaInicio->copy()->addMonths(4),
                "SEMESTRAL" => $fechaInicio->copy()->addMonths(6),
                "ANUAL" => $fechaInicio->copy()->addYear(),
                default => $fechaInicio->copy()->addMonth(),
            };

            $fechaFin = $fechaFin->endOfDay();

            $startTimestamp = $fechaInicio->timestamp;
            $endTimestamp = $fechaFin->timestamp;

            $programacion = DB::table('sw_cursos_programacion')
                ->where('cod_curso', $curso->codigo)
                ->where('estado_periodo', 'PENDIENTE')
                ->first();

            if ($programacion) {
                DB::table('sw_cursos_programacion')
                    ->where('codigo_programacion', $programacion->codigo_programacion)
                    ->update([
                        'periodo' => $fechaInicio->format('Y-m'),
                        'fecha_inicio' => $fechaInicio->format('Y-m-d\TH:i:s.000'),
                        'fecha_final' => $fechaFin->format('Y-m-d\TH:i:s.000'),
                        'estado_periodo' => 'VIGENTE',
                        'fecha_modificacion' => now(),
                    ]);

                $newCode = $programacion->codigo_programacion;
            } else {

                $last = DB::table('sw_cursos_programacion')
                    ->orderBy('codigo_programacion', 'desc')
                    ->first();

                $newCode = $last
                    ? str_pad(((int)$last->codigo_programacion) + 1, 4, '0', STR_PAD_LEFT)
                    : '1001';

                DB::table('sw_cursos_programacion')->insert([
                    'codigo_programacion' => $newCode,
                    'cod_curso' => $curso->codigo,
                    'periodo' => $fechaInicio->format('Y-m'),
                    'tipo' => 'REGULAR',
                    'fecha_inicio' => $fechaInicio->format('Y-m-d\TH:i:s.000'),
                    'fecha_final' => $fechaFin->format('Y-m-d\TH:i:s.000'),
                    'estado_periodo' => 'VIGENTE',
                    'fecha_creacion' => now(),
                    'habilitado' => 1,
                ]);
            }

            $this->actualizarCursoMoodle($curso, $startTimestamp, $endTimestamp);
            $this->realizarMatriculacion($curso, (string)$newCode);

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => "Programación creada correctamente.",
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                "success" => false,
                "message" => "Error al procesar la apertura de ciclo: " . $e->getMessage(),
            ], 500);
        }
    }

    private function actualizarCursoMoodle($curso, $startTimestamp, $endTimestamp): void
    {
        if (!$curso->codigo_moodle) {
            return;
        }

        try {
            $http = app()->isProduction()
                ? Http::asForm()
                : Http::withoutVerifying()->asForm();

            $http->post(
                config("services.moodle.url") . "/webservice/rest/server.php",
                [
                    "wstoken" => config("services.moodle.ws_token"),
                    "wsfunction" => "core_course_update_courses",
                    "moodlewsrestformat" => "json",
                    "courses[0][id]" => $curso->codigo_moodle,
                    "courses[0][startdate]" => $startTimestamp,
                    "courses[0][enddate]" => $endTimestamp,
                ]
            );
        } catch (\Throwable $e) {
            Log::error("Error al actualizar fechas en Moodle", [
                "error" => $e->getMessage(),
                "courseId" => $curso->codigo_moodle,
            ]);
        }
    }

    private function realizarMatriculacion($curso, string $newCode): void
    {
        try {
            $tipoDesc = strtoupper(trim($curso->tipo_curso_descripcion ?? ""));

            $debeMatricular =
                $tipoDesc === "PCE" ||
                ($tipoDesc === "PCA" && (string)$curso->dirigido_a !== "0");

            if (!$debeMatricular) {
                return;
            }

            dispatch(MatriculaMasivaJob::porTipoCurso(
                $curso->codigo,
                $newCode,
                Auth::id()
            ))->onQueue('training');
        } catch (\Throwable $e) {
            Log::error("Error en matriculación automática al crear programación", [
                "curso_id" => $curso->codigo,
                "error" => $e->getMessage(),
            ]);
        }
    }

    private function actualizarFechasMatriculasMoodle(int $moodleCourseId, int $nuevaFechaFinTimestamp): void
    {
        DB::connection('mysql_grupoihb')
            ->table('mdl_user_enrolments as ue')
            ->join('mdl_enrol as e', 'e.id', '=', 'ue.enrolid')
            ->join('mdl_role_assignments as ra', 'ra.userid', '=', 'ue.userid')
            ->join('mdl_context as ctx', function ($join) use ($moodleCourseId) {
                $join->on('ctx.id', '=', 'ra.contextid')
                    ->where('ctx.contextlevel', 50)
                    ->where('ctx.instanceid', $moodleCourseId);
            })
            ->where('e.courseid', $moodleCourseId)
            ->where('ue.status', 0)
            ->where('ra.roleid', 5)
            ->update([
                'ue.timeend'      => $nuevaFechaFinTimestamp,
                'ue.timemodified' => now()->timestamp,
            ]);
    }

    public function aplazarCurso(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $codCurso = $request->cod_curso;
            $nuevaFechaFin = Carbon::parse($request->nueva_fecha_final)->endOfDay();

            $curso = DB::table('sw_cursos')
                ->where('codigo', $codCurso)
                ->where('habilitado', 1)
                ->first();

            if (!$curso) {
                return response()->json([
                    "success" => false,
                    "message" => "Curso no encontrado o no habilitado.",
                ], 404);
            }

            $programacion = DB::table('sw_cursos_programacion')
                ->where('cod_curso', $curso->codigo)
                ->where('estado_periodo', 'VIGENTE')
                ->first();

            if (!$programacion) {
                return response()->json([
                    "success" => false,
                    "message" => "El curso no tiene una programación vigente.",
                ], 404);
            }

            if ($nuevaFechaFin->lessThanOrEqualTo(Carbon::parse($programacion->fecha_inicio))) {
                return response()->json([
                    "success" => false,
                    "message" => "La fecha final no puede ser menor o igual a la fecha de inicio.",
                ], 422);
            }

            DB::table('sw_cursos_programacion')
                ->where('codigo_programacion', $programacion->codigo_programacion)
                ->update([
                    'fecha_final' => $nuevaFechaFin->format('Y-m-d\TH:i:s.000'),
                    'fecha_modificacion' => now(),
                ]);

            $this->actualizarCursoMoodle(
                $curso,
                Carbon::parse($programacion->fecha_inicio)->timestamp,
                $nuevaFechaFin->timestamp
            );

            $this->actualizarFechasMatriculasMoodle($curso->codigo_moodle, $nuevaFechaFin->timestamp);

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => "Curso aplazado correctamente.",
            ]);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                "success" => false,
                "message" => "Error al aplazar el curso: " . $e->getMessage(),
            ], 500);
        }
    }

    public function obtenerProgActual(int $cursoId): JsonResponse
    {
        try {
            $programacion = DB::table('sw_cursos_programacion')
                ->where('cod_curso', $cursoId)
                ->orderByRaw("CASE WHEN estado_periodo = 'VIGENTE' THEN 1 ELSE 2 END")
                ->orderBy('fecha_inicio', 'desc')
                ->first();

            if (!$programacion) {
                return response()->json([
                    "success" => false,
                    "message" => "No se encontró programación para el curso.",
                ], 404);
            }

            return response()->json([
                "success" => true,
                "data" => $programacion,
            ]);
        } catch (\Exception $e) {

            return response()->json([
                "success" => false,
                "message" => "Error al obtener la programación: " . $e->getMessage(),
            ], 500);
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
        $cursoId = $request->cursoId;
        $programacionId = $request->programacionId;
        $personalIds = $request->personalIds;

        $usuarioId = Auth::id();

        dispatch(MatriculaMasivaJob::estandar($cursoId, $programacionId, $personalIds, $usuarioId))->onQueue('training');

        return response()->json([
            "success" => true,
            "message" => "La matriculación fue enviada a procesamiento.",
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
            $tipoTrabMap = [
                "01" => "Operativo",
                "02" => "Administrativo",
                "03" => "Operativo",
                "05" => "Administrativo",
                "06" => "Especial",
                "ADMIN" => "Administrativo",
                "OPER" => "Operativo",
            ];

            $codCliente = $request->input("codCliente");

            if (
                !empty($codCliente) &&
                $codCliente !== "undefined" &&
                $codCliente !== "null"
            ) {
                $rawPersonal = DB::connection("sqlsrv")->select(
                    "EXEC [dbo].[SP_OBTENER_PERSONAL_ACTIVO_X_CLIENTE] @CodCliente = ?",
                    [$codCliente],
                );
            } else {
                $rawPersonal = DB::connection("sqlsrv")->select("
                SELECT
                    P.CODI_PERS as codigo,
                    LTRIM(RTRIM(
                        P.APEL_1 + ' ' +
                        ISNULL(P.APEL_2, '') + ' ' +
                        P.NOMB_1 + ' ' +
                        ISNULL(P.NOMB_2, '')
                    )) as personal,
                    P.NRO_DOCU_IDEN as nroDoc,
                    S.SUCU_ABREVIATURA as sucursal,
                    P.PERS_TIPOTRAB as TIPOTRAB,
                    P.PERS_VIGENCIA as VIGENCIA,
                    P.PERS_EMAIL as email
                FROM si_solm.dbo.PERSONAL P
                LEFT JOIN dbo.sw_MIGRA_SISO_SUCURSAL S
                    ON P.SUCU_CODIGO = S.SUCU_CODIGO
                WHERE P.PERS_VIGENCIA = 'SI'
            ");
            }

            // -------------------------------------------------------------
            // Conteo global de capacitaciones
            // -------------------------------------------------------------

            $matriculasCounts = DB::table("sw_matriculas as m")
                ->join("sw_cursos as c", "m.cod_curso", "=", "c.codigo")
                ->select("m.cod_personal", DB::raw("COUNT(*) as total"))
                ->groupBy("m.cod_personal")
                ->pluck("total", "m.cod_personal")
                ->toArray();

            // -------------------------------------------------------------
            // VALIDAR MATRÍCULA SOLO EN LA PROGRAMACIÓN ACTUAL
            // -------------------------------------------------------------

            $matriculadosEnCurso = [];

            $cursoId = $request->input("cursoId");
            $programacionId = $request->input("programacionId");

            if (!empty($cursoId) && !empty($programacionId)) {
                try {
                    // Resolver la programación por codigo (PK) o codigo_programacion
                    $prog = CursoProgramacion::where("codigo", $programacionId)
                        ->orWhere("codigo_programacion", $programacionId)
                        ->first();

                    $codProgramacion = $prog?->codigo_programacion;

                    if ($codProgramacion) {
                        $matriculadosEnCurso = Matricula::where(
                            "cod_curso",
                            $cursoId,
                        )
                            ->where("cod_programacion", $codProgramacion)
                            ->pluck("cod_personal")
                            ->map(fn($id) => (string) trim($id))
                            ->toArray();
                    }
                } catch (\Exception $e) {
                    Log::error(
                        "Error verificando matrículas de programación: " .
                            $e->getMessage(),
                    );

                    $matriculadosEnCurso = [];
                }
            }

            // -------------------------------------------------------------
            // FILTROS REQUEST
            // -------------------------------------------------------------

            $searchTerm = strtoupper(trim($request->input("q", "")));
            $sucursalFiltro = strtoupper(
                trim($request->input("sucursal", "")),
            );

            // -------------------------------------------------------------
            // MAPEO FINAL
            // -------------------------------------------------------------

            $personal = [];

            foreach ($rawPersonal as $p) {
                $codigo = trim(
                    $p->CODI_PERS ??
                        $p->codi_pers ??
                        $p->codigo ??
                        "",
                );

                $nombre = strtoupper(
                    trim(
                        $p->personal ??
                            $p->nombre ??
                            $p->nombre_completo ??
                            "Desconocido",
                    ),
                );

                $dni = trim(
                    $p->nroDoc ??
                        $p->dni ??
                        $p->NRO_DOCU_IDEN ??
                        "",
                );

                $cargoRaw = trim(
                    $p->cargo ??
                        $p->desc_cargo ??
                        $p->TIPOTRAB ??
                        "N/A",
                );

                $sucursal = trim($p->sucursal ?? "N/A");

                $email = trim($p->email ?? "");

                $tipoLabel =
                    $tipoTrabMap[$cargoRaw] ?? $cargoRaw ?? "DESCONOCIDO";

                // -----------------------------------------
                // FILTRO BÚSQUEDA
                // -----------------------------------------

                if ($searchTerm !== "") {
                    if (
                        strpos($nombre, $searchTerm) === false &&
                        strpos($dni, $searchTerm) === false
                    ) {
                        continue;
                    }
                }

                // -----------------------------------------
                // FILTRO SUCURSAL
                // -----------------------------------------

                if (
                    !empty($sucursalFiltro) &&
                    strtoupper($sucursal) !== $sucursalFiltro
                ) {
                    continue;
                }

                // -----------------------------------------
                // RESULTADO FINAL
                // -----------------------------------------

                $personal[] = [
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
                    "total_capacitaciones" =>
                    $matriculasCounts[$codigo] ?? 0,
                    "cliente_nombre" =>
                    $p->cliente_nombre ?? null,
                ];
            }

            return response()->json([
                "success" => true,
                "personal" => array_values($personal),
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

    public function obtenerPersonal(Request $request): JsonResponse
    {
        try {
            $dni = $request->input('dni');

            $sql = "EXEC SP_OBTENER_PERSONAL_BETA";

            $params = [];

            if (!empty($dni)) {
                $sql .= " @DNI = ?";
                $params[] = $dni;
            }

            $rawPersonal = DB::connection('sqlsrv')->select($sql, $params);

            $personal = array_map(function ($p) {
                return [
                    'dni' => trim($p->NRO_DOC ?? ''),
                    'nombre_completo' => trim($p->NOMBRE_COMPLETO ?? ''),
                    'cargo' => trim($p->CARGO ?? ''),
                    'tipo_trabajador' => trim($p->TIPO_TRABAJADOR ?? ''),
                    'cliente' => $p->CLIENTE ?? null,
                    'sucursal' => trim($p->SUCURSAL ?? ''),
                    'codigo' => trim($p->CODIGO_PERSONAL ?? ''),
                    'email' => trim($p->CORREO ?? ''),
                ];
            }, $rawPersonal);

            return response()->json([
                'success' => true,
                'personal' => $personal,
                'total' => count($personal),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener personal: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el personal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function obtenerPersonalTodasEmpresas(): JsonResponse
    {
        try {
            $rawPersonal = collect(
                DB::connection('sqlsrv')->select(
                    "EXEC [dbo].[SP_OBTENER_PERSONAL_TODAS_EMPRESAS]"
                )
            )
                ->unique('NRO_DOC')
                ->all();

            $personal = array_values(array_map(function ($p) {
                $empresa = trim(
                    $p->EMPRESA
                        ?? $p->EMPRESA_NOMBRE
                        ?? $p->EMPR_CODIGO
                        ?? $p->CLIENTE
                        ?? ''
                );
                return [
                    'dni' => trim($p->NRO_DOC ?? ''),
                    'nombre_completo' => trim($p->NOMBRE_COMPLETO ?? ''),
                    'cargo' => trim($p->CARGO ?? ''),
                    'tipo_trabajador' => trim($p->TIPO_TRABAJADOR ?? ''),
                    'empresa' => $empresa,
                    'empresa_codigo' => trim(
                        $p->COD_EMPRESA
                            ?? $p->EMPR_CODIGO
                            ?? $p->EMPRESA_CODIGO
                            ?? ''
                    ),
                    'sucursal' => trim($p->SUCURSAL ?? ''),
                    'codigo' => trim($p->CODIGO_PERSONAL ?? ''),
                    'email' => trim($p->CORREO ?? ''),
                ];
            }, $rawPersonal));

            return response()->json([
                'success' => true,
                'personal' => $personal,
                'total' => count($personal),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener personal todas empresas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el personal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listarJefaturas(): JsonResponse
    {
        try {
            $raw = DB::connection("sqlsrv")->select("EXEC si_solm.dbo.SP_LISTAR_JEFATURAS_AREAS");

            $personal = array_map(function ($j) {
                $cargoRaw = trim($j->CARGO ?? '');
                $area = preg_replace('/^JEFE\s+DE\s+/i', '', $cargoRaw);
                return [
                    "codigo"          => trim($j->CODIGO ?? ''),
                    "nombre_completo" => trim($j->NOMBRE_COMPLETO ?? ''),
                    "dni"             => trim($j->DNI ?? ''),
                    "correo"          => trim($j->CORREO ?? ''),
                    "sucursal"        => trim($j->SUCURSAL ?? ''),
                    "area"            => trim($area),
                ];
            }, $raw);

            return response()->json([
                "success" => true,
                "personal" => $personal,
                "total" => count($personal),
            ]);
        } catch (\Exception $e) {
            Log::error("Error al listar jefaturas: " . $e->getMessage());
            return response()->json([
                "success" => false,
                "message" => "Error al cargar jefaturas",
                "error" => $e->getMessage(),
            ], 500);
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
        $dirigidos = Consulta::obtenerDirigidos();
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

    public function vistaPlanesCapacitacion(): View
    {
        return view("capacitacion.planes_capacitacion");
    }

    public function getMatriculasMigraPersonal(int $cursoId): JsonResponse
    {
        try {
            $matriculas = DB::table("sw_matriculas as m")
                ->leftJoin(
                    "sw_cursos_programacion as prog",
                    "m.cod_programacion",
                    "=",
                    "prog.codigo_programacion"
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
            $personal = Consulta::obtenerPersonalPorSucursal($sucursalId);
            return response()->json([
                "success" => true,
                "Personales" => $personal,
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

    public function obtenerAreas(): JsonResponse
    {
        try {
            $areas = DB::table('sw_curso_areas')
                ->select('codigo', 'nombre', 'codModdle')
                ->where('habilitado', 1)
                ->whereNotNull('codModdle')
                ->where('codModdle', '!=', '')
                ->orderBy('codigo')
                ->get()
                ->map(fn($a) => [
                    'codArea' => (string) $a->codigo,
                    'Area' => $a->nombre,
                    'codModdle' => (string) $a->codModdle,
                ]);
            return response()->json([
                'success' => true,
                'areas' => $areas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener áreas PCA: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function obtenerCursos(Request $request): JsonResponse
    {
        try {
            $areaId   = $request->areaId   ?? null;
            $systemId = $request->systemId ?? null;

            $cursosRaw = DB::connection('mysql_grupoihb')
                ->select('CALL grupoihb_see.SP_OBTENER_CURSOS(NULL)');

            $categories = collect(Consulta::obtenerAreas())->keyBy('codModdle');

            $bdLocal = collect(
                DB::select("
                SELECT codigo, nombre, codigo_moodle, area_conocimiento, area
                FROM sisolm_web.dbo.sw_cursos
            ")
            )->keyBy('codigo_moodle');

            $sistemas = collect(
                DB::select("
                SELECT codigo, descripcion
                FROM sisolm_web.dbo.sw_capacitacion_areas
                WHERE habilitado = 1
            ")
            )->keyBy('codigo');

            $cursos = collect($cursosRaw)
                ->map(function ($c) use ($categories, $bdLocal, $sistemas) {
                    $cursoLocal = $bdLocal->get($c->course_id);
                    $sistema    = $cursoLocal ? $sistemas->get($cursoLocal->area_conocimiento) : null;
                    $area       = $categories->get($c->category_id);

                    return [
                        'Id'                 => $c->course_id,
                        'LocalId'            => $c->course_idnumber ?? $cursoLocal->codigo ?? null,
                        'AreaId'             => $area->codModdle                ?? null,
                        'SistemaId'          => $cursoLocal?->area_conocimiento ?? null,
                        'Nombre'             => $c->course_name,
                        'Area'               => $area->nombre                  ?? 'Sin área',
                        'Sistema'            => $sistema?->descripcion          ?? 'Sin sistema',
                        'Responsable'        => $c->responsable                 ?? 'Sin responsable',
                        'Descripcion'        => $c->course_summary                     ?? 'Sin descripción',
                        'Total_Matriculados' => (int) ($c->total_matriculados   ?? 0),
                        'Fecha_Inicio'       => strtotime($c->startdate)        ?? null,
                        'Fecha_Fin'          => strtotime($c->enddate)          ?? null,
                        'Fecha_Creacion'     => strtotime($c->created_at)       ?? null,
                    ];
                })
                ->when($areaId,   fn($col) => $col->where('AreaId',    $areaId))
                ->when($systemId, fn($col) => $col->where('SistemaId', $systemId))
                ->values();

            return response()->json([
                'success' => true,
                'Cursos'  => $cursos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cursos: ' . $e->getMessage(),
            ], 500);
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

            $orangeColors = [
                "FF8C00",
                "FFA500",
                "F97316",
                "FF6600",
                "FF7F00",
                "FF4500",
                "ED7D31",
                "F4B183",
                "C0504D",
                "FFC000",
                "BF8F00",
            ];
            $blueColors = [
                "0000FF",
                "0066CC",
                "3B82F6",
                "1E90FF",
                "0055CC",
                "003399",
                "4169E1",
                "0000CD",
                "0070C0",
                "2E75B6",
                "00B0F0",
                "4472C4",
                "5B9BD5",
                "1F4E79",
            ];

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    $text = "";
                    $isCorrectByStyle = false;
                    $isCorrectByColor = false;
                    $questionTipo = null;
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
                                        // Detectar verdes comunes (respuesta correcta)
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
                                        // Detectar naranja (básica) y azul (complementaria)
                                        if ($color) {
                                            $colorUpper = strtoupper($color);
                                            foreach ($orangeColors as $oc) {
                                                if (strpos($colorUpper, $oc) !== false) {
                                                    $questionTipo = "A";
                                                    break;
                                                }
                                            }
                                            if ($questionTipo === null) {
                                                foreach ($blueColors as $bc) {
                                                    if (strpos($colorUpper, $bc) !== false) {
                                                        $questionTipo = "B";
                                                        break;
                                                    }
                                                }
                                            }
                                            if ($questionTipo === null) {
                                                Log::debug("procesarExamenWord: color no reconocido", [
                                                    "color" => $color,
                                                    "texto" => mb_substr($text, 0, 60),
                                                ]);
                                            }
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
                            "tipo" => $questionTipo ?? "A",
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
        $cursosHabilitados = DB::connection('sqlsrv')->select(
            "SELECT [habilitado], [codigo_moodle] FROM [sisolm_web].[dbo].[sw_cursos] WHERE [habilitado] = 1"
        );

        $cursosArray = array_column($cursosHabilitados, 'codigo_moodle');

        $moodleCursos = collect(
            DB::connection('mysql_grupoihb')->select(
                'CALL grupoihb_see.SP_OBTENER_CURSOS(?)',
                [date('Y')]
            )
        );

        $result = $moodleCursos->filter(fn($curso) => in_array($curso->course_id, $cursosArray))->map(fn($curso) => [
            'course_id'          => $curso->course_id,
            'nombre'             => $curso->course_name,
            'responsable'        => $curso->responsable,
            'total_matriculados' => (int) $curso->total_matriculados,
            'fecha_creacion'     => $curso->created_at,
        ])->values();

        return response()->json($result);
    }

    public function obtenerEstudiantesCurso(Request $request): JsonResponse
    {
        try {
            $codMoodle = $request->course_id;
            $statusId = $request->statusId; // 0: Todos | 1: Aprobados | 2: Desaprobados | 3: Sin acceder | 4: En curso

            $resultadosMoodle = DB::connection("mysql_grupoihb")->select(
                "CALL grupoihb_see.SP_OBTENER_MATRICULADOS_CON_ESTADO(?, ?)",
                [$codMoodle, $statusId],
            );

            $resultadosBdLocal = DB::connection('sqlsrv')->select(
                "EXEC SP_OBTENER_PERSONAL_BETA"
            );

            $localMap = [];
            foreach ($resultadosBdLocal as $row) {
                $localMap[$row->NRO_DOC] = $row;
            }

            $personales = array_map(function ($row) use ($localMap) {
                $local = $localMap[$row->username] ?? null;

                return [
                    'nroDoc'         => $row->username ?? null,
                    'nombreCompleto' => $row->full_name ?? null,
                    'correo'         => $row->email ?? null,
                    'nota_final'     => $row->final_grade !== null ? (float) $row->final_grade : null,
                    'ultimo_acceso'  => $row->last_access_date ?? null,
                    'tipo_trabajador' => $local->TIPO_TRABAJADOR ?? null,
                    'cargo'          => $local->CARGO     ?? null,
                    'sucursal'       => $local->SUCURSAL  ?? null,
                    'cliente'        => $local->CLIENTE   ?? 'Sin cliente',
                ];
            }, $resultadosMoodle);

            return response()->json([
                'success'    => true,
                'Total'      => count($personales),
                'Personales' => $personales,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al obtener los usuarios del curso",
                ],
                500,
            );
        }
    }

    public function obtenerEstadoCursosAlumno(Request $request): JsonResponse
    {
        try {
            $cursos = DB::connection("mysql_grupoihb")->select(
                "CALL SP_OBTENER_CURSOS_POR_USUARIO(?, ?)",
                [$request->dni, date('Y')]
            );

            $resultado = array_map(
                fn($c) => [
                    "nombre_curso" => $c->course_name,
                    "fecha_creacion_curso" => $c->course_created_date,
                    "fecha_creacion_matricula" => $c->enrolment_start_date,
                    "fecha_ultimo_acceso" => $c->last_access_date ?? null,
                    "nota_final" => $c->final_grade ?? null,
                    "estado" => $c->estado,
                ],
                $cursos,
            );

            $totales = [
                "Total" => count($resultado),
                "aprobado" => count(
                    array_filter(
                        $resultado,
                        fn($c) => $c["estado"] === "Aprobado",
                    ),
                ),
                "desaprobado" => count(
                    array_filter(
                        $resultado,
                        fn($c) => $c["estado"] === "Desaprobado",
                    ),
                ),
                "en_curso" => count(
                    array_filter(
                        $resultado,
                        fn($c) => $c["estado"] === "En curso",
                    ),
                ),
                "sin_acceder" => count(
                    array_filter(
                        $resultado,
                        fn($c) => $c["estado"] === "Sin acceder",
                    ),
                ),
            ];

            return response()->json([
                "success" => true,
                "Totales" => $totales,
                "Cursos" => $resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al obtener los cursos del alumno",
                ],
                500,
            );
        }
    }

    public function obtenerPersonalParaRecord(Request $request): JsonResponse
    {
        try {
            $usernames = $request->usernames ?? [];
            $courseIds = $request->courseIds ?? [];
            $estadoId  = $request->estadoId  ?? 0;
            $desde     = $request->desde     ?? null;
            $hasta     = $request->hasta     ?? null;

            $usernamesStr = !empty($usernames) ? implode(',', $usernames) : null;
            $courseIdsStr = !empty($courseIds) ? implode(',', $courseIds) : null;

            $personal = collect(DB::select("EXEC SP_OBTENER_PERSONAL_BETA"))
                ->unique('NRO_DOC')
                ->keyBy(fn($p) => trim($p->NRO_DOC));

            $cursos = collect(
                DB::connection('mysql_grupoihb')->select(
                    "CALL grupoihb_see.SP_OBTENER_ESTADO_CURSOS_POR_USUARIOS(?, ?, ?)",
                    [$usernamesStr, $courseIdsStr, (int) $estadoId]
                )
            )
                ->when($desde, fn($col) => $col->filter(
                    fn($c) => $c->course_timecreated >= strtotime($desde)
                ))
                ->when($hasta, fn($col) => $col->filter(
                    fn($c) => $c->course_timecreated <= strtotime($hasta . ' 23:59:59')
                ));

            $cursosPorUsuario = [];
            foreach ($cursos as $curso) {
                $usuarios = json_decode($curso->usuarios, true);
                foreach ($usuarios as $u) {
                    $cursosPorUsuario[trim($u['username'])][] = [
                        'Nombre' => $curso->course_name,
                        'Estado' => strtoupper($u['estado']),
                        'Nota_Final'  => $u['final_grade'] ?? "Sin nota",
                        'Fecha_Nota' => $u['fecha_nota']  ?? null,
                        'Fecha_Ultimo_Acceso' => $u['ultimo_acceso']  ?? null,
                    ];
                }
            }

            $personales = collect($cursosPorUsuario)
                ->map(function ($cursosList, $username) use ($personal) {
                    $persona = $personal->get($username);

                    if (!$persona) return null;

                    return [
                        'CodigoPersonal' => $persona->CODIGO_PERSONAL ?? null,
                        'NombreCompleto' => $persona->NOMBRE_COMPLETO ?? null,
                        'NroDoc'         => $persona->NRO_DOC         ?? null,
                        'Sucursal'       => $persona->SUCURSAL        ?? null,
                        'TipoTrabajador' => $persona->TIPO_TRABAJADOR ?? null,
                        'Correo'         => $persona->CORREO          ?? null,
                        'Cargo'          => $persona->CARGO           ?? null,
                        'Cliente'        => $persona->CLIENTE         ?? "Sin cliente",
                        'Cursos'         => $cursosList,
                    ];
                })
                ->filter()
                ->values();

            return response()->json([
                'success'    => true,
                'Personales' => $personales,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function obtenerPersonalParaReporte(Request $request): JsonResponse
    {
        try {
            $courseIds  = $request->courseIds  ?? null;
            $sucursalId = $request->sucursalId ?? null;
            $estadoId   = $request->estadoId   ?? 0;

            $courseIdsCsv = null;
            if (!empty($courseIds)) {
                $courseIdsCsv = implode(',', array_map('intval', (array) $courseIds));
            }

            $personal = Consulta::obtenerPersonalPorSucursal($sucursalId);

            $personalPorDni = collect($personal)
                ->unique('NRO_DOC')
                ->keyBy(fn($p) => trim($p->NRO_DOC));

            if ($courseIdsCsv) {
                $matriculados = collect(
                    DB::connection('mysql_grupoihb')->select(
                        "CALL grupoihb_see.SP_OBTENER_MATRICULADOS_CON_ESTADO(?, ?)",
                        [$courseIdsCsv, (int) $estadoId]
                    )
                );

                $cursos = $matriculados
                    ->groupBy('course_id')
                    ->map(function ($grupo) use ($personalPorDni) {
                        return [
                            'Curso'      => $grupo->first()->course_name,
                            'Personales' => $this->mapearPersonales($grupo, $personalPorDni),
                        ];
                    })
                    ->filter(fn($c) => count($c['Personales']) > 0)
                    ->values();

                return response()->json([
                    'success' => true,
                    'Cursos'  => $cursos,
                ]);
            }

            $todosCursos = collect(
                DB::connection('mysql_grupoihb')->select(
                    "CALL grupoihb_see.SP_OBTENER_ESTADO_CURSOS_POR_USUARIOS(NULL, NULL, ?)",
                    [(int) $estadoId]
                )
            );

            $cursos = $todosCursos->map(function ($curso) use ($personalPorDni) {
                $matriculados = collect(json_decode($curso->usuarios, true))
                    ->map(fn($u) => (object) $u);

                $personales = $this->mapearPersonales($matriculados, $personalPorDni, true);

                return [
                    'Curso'      => $curso->course_name,
                    'Personales' => $personales,
                ];
            })->filter(fn($c) => count($c['Personales']) > 0)->values();

            return response()->json([
                'success' => true,
                'Cursos'  => $cursos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function mapearPersonales(
        Collection $matriculados,
        Collection $personalPorDni,
        bool $desdeJson = false
    ): array {
        return $matriculados
            ->map(function ($moodle) use ($personalPorDni, $desdeJson) {
                $username = trim($moodle->username ?? '');

                if ($username === '') return null;

                $persona = $personalPorDni->get($username);

                if (!$persona) return null;

                return [
                    'CodigoPers'     => $persona->CODI_PERS,
                    'SucursalCodigo' => $persona->SUCU_CODIGO,
                    'NombreCompleto' => $persona->NOMBRE_COMPLETO,
                    'DNI'            => $persona->NRO_DOC,
                    'Cargo'          => $persona->CARGO          ?? 'Sin asignar',
                    'TipoTrabajador' => $persona->TIPO_TRABAJADOR,
                    'Nota_Final'     => $desdeJson ? null : ($moodle->final_grade ?? null),
                    'Estado'         => strtoupper($moodle->estado ?? ''),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function obtenerDetalleCurso(Request $request): JsonResponse
    {
        try {
            $codMoodle = $request->course_id;

            $cursoBdLocal = DB::selectOne(
                'SELECT * FROM sw_cursos WHERE codigo_moodle = ?',
                [$codMoodle]
            );

            $programaciones = DB::select(
                'SELECT * FROM sw_cursos_programacion WHERE cod_curso = ?',
                [$cursoBdLocal->codigo]
            );

            $programacionVigente = collect($programaciones)
                ->firstWhere('estado_periodo', 'VIGENTE');

            $programacionPendiente = collect($programaciones)
                ->firstWhere('estado_periodo', 'PENDIENTE');

            $estadisticas = collect(
                DB::connection('mysql_grupoihb')->select(
                    "CALL grupoihb_see.SP_OBTENER_MATRICULADOS_CON_ESTADO(?, ?)",
                    [$codMoodle, 0]
                )
            )->groupBy(fn($m) => strtoupper($m->estado))
                ->map(fn($grupo) => $grupo->count());

            return response()->json([
                'success' => true,
                'nombre'             => $cursoBdLocal->nombre ?? "Sin nombre",
                'codigo'             => $cursoBdLocal->codigo_curso ?? null,
                'codigo_interno'     => $cursoBdLocal->codigo ?? null,
                'codigo_moodle'      => (int) $codMoodle,
                'fecha_creacion'     => $cursoBdLocal->fecha_creacion ?? null,
                'programacion_actual'       => $programacionVigente ?? null,
                'programacion_pendiente'  => $programacionPendiente ?? null,
                'estadisticas'       => [
                    'aprobados'    => $estadisticas->get('APROBADO', 0),
                    'desaprobados' => $estadisticas->get('DESAPROBADO', 0),
                    'en_curso'     => $estadisticas->get('EN CURSO', 0),
                    'sin_acceder'  => $estadisticas->get('SIN ACCEDER', 0),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
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

    public function descargarReportesZip(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "ids" => "required|array|min:1",
            "ids.*" => "required|integer",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "success" => false,
                "message" => "Debe seleccionar al menos un reporte.",
            ], 422);
        }

        try {
            $reportes = DB::connection('sqlsrv')
                ->table('sw_capacitacion_reportes_historial')
                ->whereIn('id', $request->ids)
                ->where('habilitado', 1)
                ->get();

            if ($reportes->isEmpty()) {
                return response()->json([
                    "success" => false,
                    "message" => "No se encontraron reportes válidos.",
                ], 404);
            }

            if (!extension_loaded('zip')) {
                return response()->json([
                    "success" => false,
                    "message" => "La extensión ZIP no está disponible.",
                ], 500);
            }

            $zip = new \ZipArchive();
            $zipPath = storage_path('app/temp/reportes_' . time() . '.zip');

            $zipDir = dirname($zipPath);
            if (!is_dir($zipDir)) {
                mkdir($zipDir, 0755, true);
            }

            $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            $fileCount = 0;
            foreach ($reportes as $reporte) {
                $nombreBase = preg_replace('/\.(pdf|xlsx?)$/i', '', $reporte->nombre_archivo);

                if (!empty($reporte->archivo_pdf)) {
                    $zip->addFromString($nombreBase . '.pdf', $reporte->archivo_pdf);
                    $fileCount++;
                }

                if (!empty($reporte->archivo_excel)) {
                    $zip->addFromString($nombreBase . '.xlsx', $reporte->archivo_excel);
                    $fileCount++;
                }
            }

            $zip->close();

            if ($fileCount === 0) {
                @unlink($zipPath);
                return response()->json([
                    "success" => false,
                    "message" => "Los reportes seleccionados no contienen archivos.",
                ], 404);
            }

            return response()->download($zipPath, 'reportes_capacitaciones.zip')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error("Error al descargar reportes en ZIP", [
                "error" => $e->getMessage(),
                "ids" => $request->ids,
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al crear el archivo ZIP.",
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

    public function eliminarReporte(int $id): JsonResponse
    {
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

            DB::connection('sqlsrv')->statement(
                "DELETE FROM sw_capacitacion_reportes_historial WHERE id = ?",
                [$id]
            );

            return response()->json([
                "success" => true,
                "message" => "Reporte eliminado definitivamente.",
            ]);
        } catch (\Exception $e) {
            Log::error("Error al eliminar reporte permanentemente", [
                "error" => $e->getMessage(),
                "id" => $id,
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al eliminar el reporte.",
                ],
                500,
            );
        }
    }

    public function enviarMemo(Request $request): JsonResponse
    {
        $personal = (object) [
            'nroDoc' => $request->input('nroDoc'),
            'nombreCompleto' => $request->input('nombreCompleto'),
            'correo' => $request->input('correo'),
            'cargo'  => $request->input('cargo'),
        ];

        $anioActual = date('Y');

        $cursosUsuario = collect(
            DB::connection('mysql_grupoihb')->select(
                "CALL grupoihb_see.SP_GET_CURSOS_ALUMNO_ESTADO(NULL, ?, ?)",
                [
                    $personal->nroDoc,
                    $anioActual
                ]
            )
        );

        $this->procesarMemo(
            $personal,
            $cursosUsuario
        );

        return response()->json([
            'success' => true,
            'message' => 'Memo enviado correctamente.'
        ]);
    }

    public function enviarMemos(Request $request): JsonResponse
    {
        $personales = $request->input('personales', []);

        $anioActual = date('Y');

        $cursosPorUsuario = collect(
            DB::connection('mysql_grupoihb')->select(
                "CALL SP_GET_CURSOS_ALUMNOS_ESTADO_ALL(?)",
                [$anioActual]
            )
        )->groupBy('username');

        foreach ($personales as $item) {
            $personal = (object) [
                'nroDoc' => $item['nroDoc'] ?? null,
                'nombreCompleto' => $item['nombreCompleto'] ?? null,
                'correo' => $item['correo'] ?? null,
                'cargo'  => $item['cargo'] ?? null,
            ];

            if (
                empty($personal->nroDoc) ||
                empty($personal->correo)
            ) {
                continue;
            }

            $this->procesarMemo(
                $personal,
                collect($cursosPorUsuario[$personal->nroDoc] ?? [])
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Memos enviados correctamente.'
        ]);
    }

    private function procesarMemo(
        object $personal,
        Collection $cursosUsuario
    ): void {
        $cursosSinAcceder = $cursosUsuario
            ->where('estado', 'sin_iniciar')
            ->map(fn($curso) => [
                'course_id' => $curso->course_id,
                'course_nombre' => $curso->course_nombre,
            ])
            ->values()
            ->toArray();

        $historicoMemos = DB::table('SW_MEMO_RECORDATORIOS')
            ->where('NRO_DOCU_IDEN', $personal->nroDoc)
            ->orderBy('id')
            ->get();

        $ultimoMemo = $historicoMemos->last();

        $tipoMemo = !$ultimoMemo
            ? 1
            : match ((int) $ultimoMemo->NUM_MEMO) {
                1 => 2,
                2 => 3,
                3 => 1,
                default => 1,
            };

        Mail::to($personal->correo)->queue(new MemoMail(
            nombreCompleto: $personal->nombreCompleto,
            cargoPersonal: $personal->cargo,
            cursosSinAcceder: $cursosSinAcceder,
            tipoMemo: $tipoMemo,
            historicoMemos: $historicoMemos
        ));

        $memoId = DB::table('SW_MEMO_RECORDATORIOS')
            ->insertGetId([
                'NRO_DOCU_IDEN'   => $personal->nroDoc,
                'MOODLE_USER_ID'  => $personal->nroDoc,
                'NOMBRE_COMPLETO' => $personal->nombreCompleto,
                'NUM_MEMO'        => $tipoMemo,
                'FECHA_ENVIO'     => now(),
            ]);

        if (!empty($cursosSinAcceder)) {
            DB::table('SW_MEMO_RECORDATORIOS_CURSOS')
                ->insert(
                    collect($cursosSinAcceder)
                        ->map(fn($curso) => [
                            'MEMO_RECORDATORIO_ID' => $memoId,
                            'CODIGO_MOODLE'        => $curso['course_id'],
                            'NOMBRE_CURSO'         => $curso['course_nombre'],
                        ])
                        ->toArray()
                );
        }
    }

    public function obtenerMemosEnviados(): JsonResponse
    {
        try {
            $resultados = DB::select("EXEC SP_OBTENER_MEMOS_ENVIADOS");

            return response()->json([
                "success" => true,
                "data" => $resultados,
                "total" => count($resultados),
            ]);
        } catch (\Exception $e) {
            Log::error("Error en obtenerMemosEnviados", [
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al obtener los MEMOs enviados al personal.",
                ],
                500,
            );
        }
    }

    public function obtenerMemosResumen(int $nivelMemo): JsonResponse
    {
        try {
            $resultados = DB::select("EXEC SP_OBTENER_MEMOS_RESUMEN ?", [$nivelMemo]);

            return response()->json([
                "success" => true,
                "data" => $resultados,
                "total" => count($resultados),
            ]);
        } catch (\Exception $e) {
            Log::error("Error en obtenerMemosResumen", [
                "nivelMemo" => $nivelMemo,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al obtener el resumen de MEMOs.",
                ],
                500,
            );
        }
    }

    public function obtenerDetalleMemo(int $memoId): JsonResponse
    {
        try {
            $resultados = DB::select("EXEC SP_MEMOS_CURSOS ?", [$memoId]);

            return response()->json([
                "success" => true,
                "data" => $resultados,
                "total" => count($resultados),
            ]);
        } catch (\Exception $e) {
            Log::error("Error en obtenerDetalleMemo", [
                "memoId" => $memoId,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al obtener el detalle del MEMO.",
                ],
                500,
            );
        }
    }

    public function obtenerMemosPersonal(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                "nroDoc" => "required|string",
                "nivel" => "required|integer|min:1|max:3",
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

            $resultados = DB::select("EXEC SP_OBTENER_MEMOS_PERSONAL ?, ?", [
                $request->nroDoc,
                $request->nivel,
            ]);

            $data = array_map(function ($item) {
                $fecha = $item->FECHA_ENVIO ?? null;
                $fechaFormateada = $fecha
                    ? Carbon::parse($fecha)->format("d/m/Y | g:i:s A")
                    : null;

                return [
                    "ID" => $item->ID,
                    "NIVEL_MEMO" => $item->NIVEL_MEMO,
                    "FECHA_ENVIO" => $fechaFormateada,
                ];
            }, $resultados);

            return response()->json([
                "success" => true,
                "data" => $data,
                "total" => count($data),
            ]);
        } catch (\Exception $e) {
            Log::error("Error en obtenerMemosPersonal", [
                "nroDoc" => $request->nroDoc,
                "nivel" => $request->nivel,
                "error" => $e->getMessage(),
            ]);

            return response()->json(
                [
                    "success" => false,
                    "message" => "Error al obtener los MEMOs del personal.",
                ],
                500,
            );
        }
    }

    public function obtenerInfoMemo($nroDoc): JsonResponse
    {
        try {
            $ultimoMemo = DB::table('SW_MEMO_RECORDATORIOS')
                ->where('NRO_DOCU_IDEN', $nroDoc)
                ->orderByDesc('id')
                ->first();

            $total = DB::table('SW_MEMO_RECORDATORIOS')
                ->where('NRO_DOCU_IDEN', $nroDoc)
                ->count();

            $ultimoNumMemo = $ultimoMemo ? (int) $ultimoMemo->NUM_MEMO : null;

            $siguienteNumMemo = !$ultimoMemo
                ? 1
                : match ($ultimoNumMemo) {
                    1 => 2,
                    2 => 3,
                    3 => 1,
                    default => 1,
                };

            $textos = [1 => 'primer', 2 => 'segundo', 3 => 'tercer'];

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'ultimo_num_memo' => $ultimoNumMemo,
                    'siguiente_num_memo' => $siguienteNumMemo,
                    'siguiente_texto' => $textos[$siguienteNumMemo],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en obtenerInfoMemo', [
                'nroDoc' => $nroDoc,
                'error' => $e->getMessage(),
            ]);

            return response()->json(
                [
                    'success' => false,
                    'message' => 'Error al obtener la información del MEMO.',
                ],
                500,
            );
        }
    }

    public function compararMemos(Request $request): JsonResponse
    {
        try {
            $memoBaseNumero = (int) $request->contrMEMOs1;
            $memoComparadoNumero = (int) $request->contrMEMOs2;

            if ($memoBaseNumero >= $memoComparadoNumero) {
                return response()->json([
                    'success' => false,
                    'message' => 'El primer MEMO debe ser menor que el segundo.'
                ], 422);
            }

            $memoBase = collect(
                DB::select(
                    'EXEC SP_OBTENER_MEMOS_ENVIADOS ?',
                    [$memoBaseNumero]
                )
            );

            $memoComparado = collect(
                DB::select(
                    'EXEC SP_OBTENER_MEMOS_ENVIADOS ?',
                    [$memoComparadoNumero]
                )
            );

            $idsBase = $memoBase->pluck('NRO_DOCU_IDEN');
            $idsComparado = $memoComparado->pluck('NRO_DOCU_IDEN');

            $persisten = $memoBase
                ->filter(
                    fn($usuario) =>
                    $idsComparado->contains($usuario->NRO_DOCU_IDEN)
                )
                ->values();

            $yaNoEstan = $memoBase
                ->filter(
                    fn($usuario) =>
                    !$idsComparado->contains($usuario->NRO_DOCU_IDEN)
                )
                ->values();

            $nuevos = $memoComparado
                ->filter(
                    fn($usuario) =>
                    !$idsBase->contains($usuario->NRO_DOCU_IDEN)
                )
                ->values();

            return response()->json([
                'success' => true,

                'data' => [
                    'memo_base' => $memoBaseNumero,
                    'memo_comparado' => $memoComparadoNumero,

                    'totales' => [
                        'persisten' => $persisten->count(),
                        'ya_no_estan' => $yaNoEstan->count(),
                        'nuevos' => $nuevos->count(),
                    ],

                    'persisten' => $persisten,
                    'ya_no_estan' => $yaNoEstan,
                    'nuevos' => $nuevos,
                ]
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al comparar los MEMOs.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function obtenerReporteGeneral(Request $request): JsonResponse
    {
        try {
            $usernames = $request->usernames ?? [];
            $courseIds = $request->courseIds ?? [];
            $estado    = $request->estado    ?? '';
            $desde     = $request->desde     ?? null;
            $hasta     = $request->hasta     ?? null;
            $cliente   = $request->cliente   ?? null;

            $usernamesStr = !empty($usernames) ? implode(',', $usernames) : null;
            $courseIdsStr = !empty($courseIds) ? implode(',', $courseIds) : null;
            $estadoId     = $estado ? 0 : 0;

            $personal = collect(
                DB::connection('sqlsrv')->select("EXEC [dbo].[SP_OBTENER_PERSONAL_TODAS_EMPRESAS]")
            )
                ->unique('NRO_DOC')
                ->keyBy(fn($p) => trim($p->NRO_DOC));

            $cursos = collect(
                DB::connection('mysql_grupoihb')->select(
                    "CALL grupoihb_see.SP_OBTENER_ESTADO_CURSOS_POR_USUARIOS(?, ?, ?)",
                    [$usernamesStr, $courseIdsStr, 0]
                )
            )
                ->when($desde, fn($col) => $col->filter(
                    fn($c) => $c->course_timecreated >= strtotime($desde)
                ))
                ->when($hasta, fn($col) => $col->filter(
                    fn($c) => $c->course_timecreated <= strtotime($hasta . ' 23:59:59')
                ));

            $cursosPorUsuario = [];
            foreach ($cursos as $curso) {
                $usuarios = json_decode($curso->usuarios, true);
                foreach ($usuarios as $u) {
                    $estadoUpper = strtoupper($u['estado'] ?? '');
                    if ($estado && $estadoUpper !== strtoupper($estado)) {
                        continue;
                    }
                    $cursosPorUsuario[trim($u['username'])][] = [
                        'Nombre'              => $curso->course_name,
                        'Estado'              => $estadoUpper,
                        'Nota_Final'          => $u['final_grade'] ?? 'Sin nota',
                        'Fecha_Nota'          => $u['fecha_nota'] ?? null,
                        'Fecha_Ultimo_Acceso' => $u['ultimo_acceso'] ?? null,
                    ];
                }
            }

            $personales = collect($cursosPorUsuario)
                ->map(function ($cursosList, $username) use ($personal) {
                    $persona = $personal->get($username);
                    if (!$persona) return null;

                    $empresa = trim(
                        $persona->EMPRESA
                            ?? $persona->EMPRESA_NOMBRE
                            ?? $persona->EMPR_CODIGO
                            ?? $persona->CLIENTE
                            ?? ''
                    );

                    return [
                        'CodigoPersonal' => trim($persona->CODIGO_PERSONAL ?? ''),
                        'NombreCompleto' => trim($persona->NOMBRE_COMPLETO ?? ''),
                        'NroDoc'         => trim($persona->NRO_DOC ?? ''),
                        'Sucursal'       => trim($persona->SUCURSAL ?? ''),
                        'Empresa'        => $empresa,
                        'Cargo'          => trim($persona->CARGO ?? ''),
                        'TipoTrabajador' => trim($persona->TIPO_TRABAJADOR ?? ''),
                        'Cursos'         => $cursosList,
                    ];
                })
                ->filter()
                ->values();

            if ($cliente) {
                $clienteSel = collect(DB::select("EXEC SW_LISTAR_CLIENTES"))
                    ->firstWhere('codigo', $cliente);
                if ($clienteSel) {
                    $desc = strtolower(trim($clienteSel->abreviatura ?? $clienteSel->razon_social ?? ''));
                    $personales = $personales->filter(function ($p) use ($desc) {
                        $emp = strtolower(trim($p['Empresa'] ?? ''));
                        return $emp === $desc || str_contains($emp, $desc) || str_contains($desc, $emp);
                    })->values();
                }
            }

            return response()->json([
                'success'    => true,
                'Personales' => $personales,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
