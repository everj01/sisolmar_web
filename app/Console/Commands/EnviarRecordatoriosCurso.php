<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioCursoMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatoriosCurso extends Command
{
    protected $signature = 'app:enviar-recordatorios-curso';

    protected $description = 'Envía correos recordatorios a matriculados que no han iniciado, por cada curso con pendientes';

    public function handle()
    {
        $totalEnviados = 0;
        $totalErrores = 0;

        $this->info('Obteniendo cursos con matriculados sin iniciar...');

        $cursos = DB::connection('mysql_grupoihb')->select(
            'CALL SP_OBTENER_CURSOS_CON_NO_INICIADOS()'
        );

        if (empty($cursos)) {
            $this->info('No se encontraron cursos con matriculados sin iniciar.');
            return;
        }

        $this->info("Se encontraron " . count($cursos) . " curso(s) con pendientes.");

        foreach ($cursos as $curso) {
            $courseId = $curso->course_id;
            $courseName = $curso->course_name ?? "ID {$courseId}";

            $this->info("Procesando curso: {$courseName} (ID: {$courseId})...");

            $usuarios = DB::connection('mysql_grupoihb')->select(
                'CALL SP_OBTENER_MATRICULADOS_SIN_INICIAR(?)',
                [$courseId]
            );

            if (empty($usuarios)) {
                $this->info("  Sin usuarios pendientes para este curso.");
                continue;
            }

            $cursoEnviados = 0;
            $cursoErrores = 0;

            foreach ($usuarios as $usuario) {
                try {
                    Mail::to($usuario->email)
                        ->queue(
                            new RecordatorioCursoMail($usuario)
                        );

                    $cursoEnviados++;
                } catch (\Exception $e) {
                    $cursoErrores++;
                    Log::error("Error enviando recordatorio a {$usuario->email}: " . $e->getMessage());
                }
            }

            $totalEnviados += $cursoEnviados;
            $totalErrores += $cursoErrores;

            $this->info("  {$courseName}: {$cursoEnviados} encolados, {$cursoErrores} errores");
        }

        $this->info("Proceso completado. Enviados: {$totalEnviados}, Errores: {$totalErrores}");
    }
}
