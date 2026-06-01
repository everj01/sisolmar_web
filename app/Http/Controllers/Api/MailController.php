<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\RecordatorioCursoMail;

class MailController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'template' => 'required|string',
            'data' => 'required|array'
        ]);

        $html = view(
            'emails.' . $request->template,
            $request->data
        )->render();

        Mail::html($html, function ($message) use ($request) {
            $message->to($request->to)
                ->subject($request->subject);
        });

        return response()->json([
            'success' => true,
            'message' => 'Correo enviado'
        ]);
    }

    public function sendRecordatorioCurso(int $courseId)
    {
        $enviados = 0;
        $errores = 0;

        $usuarios = DB::connection('mysql_grupoihb')->select(
            'CALL SP_OBTENER_MATRICULADOS_SIN_INICIAR(?)',
            [$courseId]
        );

        foreach ($usuarios as $usuario) {
            try {
                Mail::to($usuario->email)
                    ->queue(
                        new RecordatorioCursoMail($usuario)
                    );

                $enviados++;
            } catch (\Exception $e) {
                $errores++;
                Log::error("Error enviando recordatorio a {$usuario->email}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Los correos fueron agregados correctamente a la cola de envío.',
            'course_id' => $courseId,
            'queued_jobs' => $enviados,
            'queue_errors' => $errores,
            'total_processed' => count($usuarios),
        ], 202);
    }

    public function enviarRecordatorioCurso(Request $request)
    {
        try {
            $email = $request->email;
            $full_name = $request->full_name;
            $course_id = $request->course_id;

            $curso = DB::connection('mysql_grupoihb')->select(
                'SELECT fullname AS course_name, shortname AS course_shortname, startdate AS enrolment_start_date
         FROM mdl_course WHERE id = ?',
                [$course_id]
            );

            $curso = $curso[0];
            $usuario = (object) [
                'email' => $email,
                'full_name' => $full_name
            ];

            Mail::to($usuario->email)
                ->queue(new RecordatorioCursoMail($usuario, $curso));

            return response()->json([
                'success' => true,
                'message' => 'Recordatorio enviado correctamente',
            ], 202);
        } catch (\Exception $e) {
            Log::error("Error enviando recordatorio individual a {$email}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el recordatorio',
            ], 500);
        }
    }
}