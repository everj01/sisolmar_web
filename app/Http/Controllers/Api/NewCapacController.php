<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

            $vigentes = DB::table('sw_cursos_programacion')
                ->whereIn('cod_curso', $cursos->pluck('codigo')->all())
                ->where('estado_periodo', 'VIGENTE')
                ->where('habilitado', 1)
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

            $programaciones = DB::table('sw_cursos_programacion')
                ->select(
                    'codigo_programacion',
                    'periodo',
                    'fecha_inicio',
                    'fecha_final',
                    'estado_periodo',
                    'habilitado',
                )
                ->where('cod_curso', $row->codigo)
                ->where('habilitado', 1)
                ->orderBy('codigo_programacion')
                ->get()
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
                    $resp = DB::connection('sqlsrv')->selectOne(
                        "SELECT LTRIM(RTRIM(APEL_1 + ' ' + ISNULL(APEL_2, '') + ' ' + NOMB_1 + ' ' + ISNULL(NOMB_2, ''))) as nombre
                         FROM si_solm.dbo.PERSONAL
                         WHERE CODI_PERS = ?",
                        [$row->cod_responsable],
                    );
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
}
