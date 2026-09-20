<?php

namespace App\Http\Controllers;

use App\Models\ExamenesModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ExamenesController extends Controller
{
    public function obtenerCursosAV(): JsonResponse
    {
        $examenesModel = new ExamenesModel();

        $cursos = collect($examenesModel->obtenerCursosAV());

        return response()->json([
            'success' => true,
            'data'    => [
                'cursos' => $cursos,
                'total'  => $cursos->count(),
            ],
        ]);
    }

    public function obtenerDatosReporteAV(Request $request): JsonResponse
    {
        try {
            $examenesModel = new ExamenesModel();

            $detalle = $examenesModel->obtenerDetallesExamenAV($request->dni, $request->quizId);

            if (!$detalle) {
                return response()->json([
                    'success' => false,
                    'message' => 'El usuario no tiene intentos registrados en este examen.',
                    'data'    => null,
                ], 404);
            }

            $preguntas = $examenesModel->obtenerPreguntasYRespuestasAV(
                $request->dni,
                $detalle->attempt_id
            );

            return response()->json([
                'success' => true,
                'message' => 'Detalle del último intento obtenido correctamente.',
                'data'    => [
                    'course_name'        => $detalle->course_name,
                    'attempt_id'         => (int) $detalle->attempt_id,
                    'attempt_date'       => $detalle->attempt_date,
                    'obtained_grade'     => $detalle->obtained_grade,
                    'attempt_number'     => (int) $detalle->attempt_number,
                    'start_time'         => $detalle->start_time,
                    'end_time'           => $detalle->end_time,
                    'duration'           => $detalle->duration,
                    'passing_percentage' => $detalle->passing_percentage,
                    'correct_answers'    => (int) $detalle->correct_answers,
                    'incorrect_answers'  => (int) $detalle->incorrect_answers,
                    'full_name'          => $detalle->full_name,
                    'questions_answers'          => $preguntas,
                ],
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al obtener el reporte del examen.',
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'data'    => null,
            ], 500);
        }
    }
}
