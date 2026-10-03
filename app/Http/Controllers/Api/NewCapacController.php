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
                ->get()
                ->map(function ($row) {
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
                        'CURS_HABILITADO' => (bool) $row->habilitado,
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
