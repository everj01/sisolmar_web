<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CursoProgramacion;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class NewCapacController extends Controller
{
    public function obtenerCursos(): JsonResponse
    {
        try {
            $cursos = DB::table('sw_cursos as c')
                ->leftJoin('sw_capacitacion_tipo_curso as tc', 'tc.codigo', '=', 'c.tipo_curso')
                ->leftJoin('sw_capacitacion_areas as a', 'a.codigo', '=', 'c.area_conocimiento')
                ->select(
                    'c.codigo',
                    'c.codigo_curso',
                    'c.nombre',
                    'c.categoria',
                    'c.codigo_moodle',
                    'tc.descripcion as plan_nombre',
                    'a.abreviatura as sistema_nombre',
                    'c.fecha_creacion',
                    'c.habilitado',
                )
                ->orderBy('c.codigo_curso')
                ->get();

            $vigentes = CursoProgramacion::query()
                ->habilitados()
                ->vigentes()
                ->whereIn('cod_curso', $cursos->pluck('codigo')->all())
                ->pluck('cod_curso')
                ->map(fn($v) => (int) $v)
                ->flip();

            $cursos = $cursos
                ->map(function ($row) use ($vigentes) {
                    return [
                        'CURS_COD' => (string) $row->codigo_curso,
                        'CURS_NOMBRE' => $row->nombre,
                        'CURS_TIPO' => $this->mapearCategoriaCurso(
                            isset($row->categoria) ? (int) $row->categoria : null,
                            $row->codigo_moodle,
                        ),
                        'CURS_PLAN_CAPAC_NOMBRE' => $row->plan_nombre,
                        'CURS_SIST_GESTION_NOMBRE' => $row->sistema_nombre,
                        'CURS_CREADO_FECHA' => $row->fecha_creacion
                            ? Carbon::parse($row->fecha_creacion)->format('d/m/Y')
                            : null,
                        'CURS_CREADO_FECHA_ISO' => $row->fecha_creacion
                            ? Carbon::parse($row->fecha_creacion)->format('Y-m-d')
                            : null,
                        'CURS_HABILITADO' => (bool) $row->habilitado,
                        'CURS_TIENE_VIGENTE' => isset($vigentes[(int) $row->codigo]),
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'total' => $cursos->count(),
                'data' => $cursos,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en NewCapacController@obtenerCursos', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los cursos.',
            ], 500);
        }
    }

    public function obtenerCurso(string $codigoCurso): JsonResponse
    {
        try {
            $row = DB::table('sw_cursos as c')
                ->leftJoin('sw_capacitacion_tipo_curso as tc', 'tc.codigo', '=', 'c.tipo_curso')
                ->leftJoin('sw_capacitacion_areas as a', 'a.codigo', '=', 'c.area_conocimiento')
                ->leftJoin('sw_cursos_area as ar', 'ar.codigo', '=', 'c.area')
                ->select(
                    'c.codigo',
                    'c.codigo_curso',
                    'c.nombre',
                    'c.descripcion',
                    'c.categoria',
                    'c.codigo_moodle',
                    'c.tipo_curso',
                    'c.area_conocimiento',
                    'c.area',
                    'c.cod_responsable',
                    'c.es_periodico',
                    'c.frecuencia',
                    'c.dirigido_a',
                    'c.sucursal',
                    'c.habilitado',
                    'c.fecha_creacion',
                    'tc.descripcion as plan_nombre',
                    'a.abreviatura as sistema_nombre',
                    'ar.nombre as area_responsable_nombre',
                    'ar.codigo_moodle as area_codigo_moodle',
                )
                ->where('c.codigo_curso', $codigoCurso)
                ->first();

            if (!$row) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el curso.',
                ], 404);
            }

            $programaciones = CursoProgramacion::query()
                ->habilitados()
                ->delCurso($row->codigo)
                ->orderBy('codigo_programacion')
                ->get([
                    'codigo_programacion',
                    'periodo',
                    'fecha_inicio',
                    'fecha_final',
                    'estado_periodo',
                    'habilitado',
                ])
                ->map(function ($p) {
                    return [
                        'codigo_programacion' => (string) $p->codigo_programacion,
                        'periodo' => $p->periodo,
                        'fecha_inicio' => $p->fecha_inicio,
                        'fecha_final' => $p->fecha_final,
                        'estado_periodo' => $p->estado_periodo,
                        'habilitado' => (bool) $p->habilitado,
                    ];
                })
                ->values();

            $estados = $programaciones
                ->map(fn($p) => strtoupper((string) ($p['estado_periodo'] ?? '')))
                ->all();

            $sucursales = [];
            try {
                $sucursales = DB::table('sw_curso_sucursales')
                    ->where('curso_codigo', $row->codigo)
                    ->pluck('sucursal')
                    ->all();
            } catch (\Exception $e) {
                $sucursales = [];
            }

            $responsableNombre = null;
            if (!empty($row->cod_responsable)) {
                try {
                    $resp = Personal::query()
                        ->where('CODI_PERS', $row->cod_responsable)
                        ->first([
                            DB::raw(
                                "LTRIM(RTRIM(APEL_1 + ' ' + ISNULL(APEL_2, '') + ' ' + NOMB_1 + ' ' + ISNULL(NOMB_2, ''))) as nombre"
                            ),
                        ]);
                    $responsableNombre = $resp->nombre ?? null;
                } catch (\Exception $e) {
                    $responsableNombre = null;
                }
            }

            $dirigidoNombre = null;
            $dirigidoCodigo = $row->dirigido_a !== null ? (string) $row->dirigido_a : null;
            if ($dirigidoCodigo === 'OTROS' || $dirigidoCodigo === '0') {
                $dirigidoNombre = 'Otros';
            } elseif ($dirigidoCodigo !== null && is_numeric($dirigidoCodigo)) {
                try {
                    $dirigidoNombre = DB::table('sw_cursos_dirigido')
                        ->where('codigo', (int) $dirigidoCodigo)
                        ->value('nombre');
                } catch (\Exception $e) {
                    $dirigidoNombre = null;
                }
            }

            $categoria = isset($row->categoria) ? (int) $row->categoria : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'CURS_COD' => (string) $row->codigo_curso,
                    'CURS_PK' => (int) $row->codigo,
                    'CURS_NOMBRE' => $row->nombre,
                    'CURS_DESCRIPCION' => $row->descripcion,
                    'CURS_CATEGORIA' => $categoria !== null ? (string) $categoria : null,
                    'CURS_TIPO' => $this->mapearCategoriaCurso($categoria, $row->codigo_moodle),
                    'CURS_PLAN_COD' => $row->tipo_curso !== null ? (string) $row->tipo_curso : null,
                    'CURS_PLAN_CAPAC_NOMBRE' => $row->plan_nombre,
                    'CURS_SIST_GESTION_COD' => $row->area_conocimiento !== null ? (string) $row->area_conocimiento : null,
                    'CURS_SIST_GESTION_NOMBRE' => $row->sistema_nombre,
                    'CURS_AREA_RESPONSABLE' => $row->area !== null ? (string) $row->area : null,
                    'CURS_AREA_RESPONSABLE_NOMBRE' => $row->area_responsable_nombre,
                    'CURS_COD_MOODLE_AREA' => $row->area_codigo_moodle !== null ? (string) $row->area_codigo_moodle : null,
                    'CURS_COD_RESPONSABLE' => $row->cod_responsable !== null ? (string) $row->cod_responsable : null,
                    'CURS_RESPONSABLE_NOMBRE' => $responsableNombre,
                    'CURS_ES_PERIODICO' => (bool) $row->es_periodico,
                    'CURS_FRECUENCIA' => $row->frecuencia,
                    'CURS_DIRIGIDO_A' => $row->dirigido_a !== null ? (string) $row->dirigido_a : null,
                    'CURS_DIRIGIDO_NOMBRE' => $dirigidoNombre,
                    'CURS_SUCURSAL' => $row->sucursal,
                    'CURS_SUCURSALES' => $sucursales,
                    'CURS_HABILITADO' => (bool) $row->habilitado,
                    'CURS_CREADO_FECHA' => $row->fecha_creacion
                        ? Carbon::parse($row->fecha_creacion)->format('d/m/Y')
                        : null,
                    'CURS_CREADO_FECHA_ISO' => $row->fecha_creacion
                        ? Carbon::parse($row->fecha_creacion)->format('Y-m-d')
                        : null,
                    'CURS_TIENE_VIGENTE' => in_array('VIGENTE', $estados, true),
                    'CURS_TIENE_PENDIENTE' => in_array('PENDIENTE', $estados, true),
                    'CURS_PROGRAMACIONES' => $programaciones,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error en NewCapacController@obtenerCurso', [
                'codigo_curso' => $codigoCurso,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el curso.',
            ], 500);
        }
    }

    private function mapearCategoriaCurso(?int $categoria, $courseId): string
    {
        return match ($categoria) {
            1 => 'INDUCCIÓN',
            2 => 'CHARLA',
            3 => 'CAPACITACIÓN',
            4 => 'ENTRENAMIENTO',
            5 => 'SIMULACROS DE EMERGENCIA',
            default => in_array($courseId, [77, 120]) ? 'INDUCCIÓN' : 'CAPACITACIÓN',
        };
    }

    /**
     * Devuelve el siguiente codigo_curso (max + 1, padded a 5).
     * Usado por el modal nativo "Ingresar directo BD".
     */
    public function ultimoCodigoCurso(): JsonResponse
    {
        try {
            $last = DB::table('sw_cursos')->orderBy('codigo_curso', 'desc')->value('codigo_curso');
            $next = $last ? str_pad(((int) $last) + 1, 5, '0', STR_PAD_LEFT) : '10001';

            return response()->json(['success' => true, 'ultimo' => (string) $last, 'siguiente' => (string) $next]);
        } catch (\Exception $e) {
            Log::error('Error en NewCapacController@ultimoCodigoCurso', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'No se pudo obtener el último código.'], 500);
        }
    }

    /**
     * Catálogo "dirigido a" para el modal nativo (misma fuente que la vista).
     */
    public function dirigidos(): JsonResponse
    {
        try {
            $rows = DB::table('sw_cursos_dirigido as d')
                ->select('codigo', 'nombre as texto')
                ->where('habilitado', 1)
                ->orderBy('codigo')
                ->get();

            return response()->json(['success' => true, 'data' => $rows]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'No se pudo cargar dirigidos.'], 500);
        }
    }

    /**
     * Inserción directa a sw_cursos (modal nativo "Ingresar directo BD").
     * Inserta solo columnas existentes en la tabla para tolerar
     * variaciones de nombre (creado_por, codEmpresa, es_demanda, etc.).
     */
    public function guardarCursoDirecto(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'nombre' => 'required|string|max:100',
                'codigo_curso' => 'nullable|string|max:20',
                'creado_por' => 'nullable|string|max:50',
                'fecha_creacion' => 'nullable|date',
                'modificado_por' => 'nullable|string|max:50',
                'fecha_modificacion' => 'nullable|date',
                'habilitado' => 'nullable|boolean',
                'es_periodico' => 'nullable|boolean',
                'aplica_evaluacion' => 'nullable|boolean',
                'obligatorio_alta' => 'nullable|boolean',
                'es_demanda' => 'nullable|boolean',
                'cod_responsable' => 'nullable|string|max:20',
                'codigo_moodle' => 'nullable|string|max:50',
                'descripcion' => 'nullable|string',
                'dirigido_a' => 'nullable|string|max:20',
                'cod_cliente' => 'nullable|string|max:50',
                'sucursal' => 'nullable|string|max:10',
                'tipo_curso' => 'nullable|integer',
                'area_conocimiento' => 'nullable|integer',
                'area' => 'nullable|integer',
                'categoria' => 'nullable|integer|in:1,2,3,4,5',
            ]);

            $columnas = [];
            try {
                $columnas = Schema::getColumnListing('sw_cursos');
            } catch (\Exception $e) {
                $columnas = [];
            }
            $existe = fn(string $col) => empty($columnas) || in_array($col, $columnas, true);

            // codigo_curso: si no viene, generar max + 1; si viene, respetar (evita colisión).
            $codigoCurso = trim((string) $request->input('codigo_curso', ''));
            if ($codigoCurso === '') {
                $last = DB::table('sw_cursos')->orderBy('codigo_curso', 'desc')->value('codigo_curso');
                $codigoCurso = $last ? str_pad(((int) $last) + 1, 5, '0', STR_PAD_LEFT) : '10001';
            }
            if (DB::table('sw_cursos')->where('codigo_curso', $codigoCurso)->exists()) {
                return response()->json(['success' => false, 'message' => "El codigo_curso {$codigoCurso} ya existe."], 422);
            }

            $bool = fn($v) => ((int) $v) === 1 ? 1 : 0;
            $fechaODefault = fn($v) => $v
                ? Carbon::parse($v)->format('Y-m-d\TH:i:s')
                : Carbon::now()->format('Y-m-d\TH:i:s');
            $intOrNull = fn($v) => ($v === null || $v === '' || $v === '__NULL__') ? null : (int) $v;
            $strOrNull = fn($v) => ($v === null || $v === '' || $v === '__NULL__') ? null : (string) $v;

            $dirigido = $strOrNull($request->input('dirigido_a'));
            if ($dirigido === 'OTROS') {
                $dirigido = '0';
            }

            $data = [
                'codigo_curso' => $codigoCurso,
                'nombre' => trim((string) $request->input('nombre')),
                'fecha_creacion' => $fechaODefault($request->input('fecha_creacion')),
                'fecha_modificacion' => $fechaODefault($request->input('fecha_modificacion')),
                'habilitado' => $request->has('habilitado') ? $bool($request->input('habilitado')) : 1,
                'periodicidad' => null, // siempre NULL por requerimiento
                'es_periodico' => $request->has('es_periodico') ? $bool($request->input('es_periodico')) : 0,
                'frecuencia' => null, // siempre NULL por requerimiento
                'aplica_evaluacion' => $request->has('aplica_evaluacion') ? $bool($request->input('aplica_evaluacion')) : 0,
                'obligatorio_alta' => $request->has('obligatorio_alta') ? $bool($request->input('obligatorio_alta')) : 0,
                'cod_responsable' => $strOrNull($request->input('cod_responsable')),
                'codigo_moodle' => $strOrNull($request->input('codigo_moodle')),
                'descripcion' => $strOrNull($request->input('descripcion')),
                'dirigido_a' => $dirigido,
                'cod_cliente' => $strOrNull($request->input('cod_cliente')),
                'sucursal' => $strOrNull($request->input('sucursal')),
                'target_group' => 'TODOS', // siempre TODOS
                'tipo_curso' => $intOrNull($request->input('tipo_curso')),
                'area_conocimiento' => $intOrNull($request->input('area_conocimiento')),
                'area' => $intOrNull($request->input('area')),
                'categoria' => $intOrNull($request->input('categoria')),
            ];

            // Columnas opcionales (solo si existen en la tabla real).
            $opcionales = [
                'creado_por' => $strOrNull($request->input('creado_por')),
                'modificado_por' => $strOrNull($request->input('modificado_por')),
                'es_demanda' => $request->has('es_demanda') ? $bool($request->input('es_demanda')) : 0,
                'codEmpresa' => '01', // siempre 01
                'cod_empresa' => '01', // variante snake_case por compatibilidad
            ];
            foreach ($opcionales as $col => $val) {
                if ($existe($col)) {
                    $data[$col] = $val;
                }
            }

            // Filtrar a columnas existentes (si se pudo leer el schema).
            if (!empty($columnas)) {
                $data = array_intersect_key($data, array_flip($columnas));
            }

            $pk = DB::table('sw_cursos')->insertGetId($data, 'codigo');

            return response()->json([
                'success' => true,
                'message' => 'Curso registrado directo en BD.',
                'codigo' => $pk,
                'codigo_curso' => $codigoCurso,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error en NewCapacController@guardarCursoDirecto', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }
}
