<?php

namespace App\Console\Commands;

use App\Mail\RecordatorioCursoMail;
use App\Mail\RecordatorioCursosPendientesMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarRecordatoriosCurso extends Command
{
    protected $signature = 'app:enviar-recordatorios-curso
                            {--dry-run : Muestra cuántos correos se enviarían sin enviarlos}';

    protected $description = 'Envía correos recordatorios a matriculados que no han iniciado sus cursos';

    public function handle(): int
    {
        $isDryRun    = $this->option('dry-run');
        $totalEnviados = 0;
        $totalErrores  = 0;

        $registros = DB::connection('mysql_grupoihb')->select(
            'CALL SP_OBTENER_RECORDATORIOS_PENDIENTES()'
        );

        if (empty($registros)) {
            $this->info('¡Increíble! No hay matriculados pendientes.');
            return self::SUCCESS;
        }

        $porUsuario = collect($registros)->groupBy('user_id');

        $this->info("{$porUsuario->count()} usuario(s) con cursos pendientes.");

        $bar = $this->output->createProgressBar($porUsuario->count());
        $bar->start();

        foreach ($porUsuario as $userId => $cursos) {
            $usuario   = $cursos->first();
            $totalCursos = $cursos->count();

            try {
                if (!$isDryRun) {
                    $mailable = $totalCursos === 1
                        ? new RecordatorioCursoMail($usuario, $cursos->first())
                        : new RecordatorioCursosPendientesMail($usuario, $cursos->all());

                    Mail::to($usuario->email)->queue($mailable);
                }

                $totalEnviados++;
            } catch (\Throwable $e) {
                $totalErrores++;
                Log::error("Error encolando recordatorio para usuario {$userId} ({$usuario->email}): {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Métrica', 'Total'],
            [
                ['Usuarios procesados', $porUsuario->count()],
                ['Correos encolados',   $totalEnviados],
                ['Errores',             $totalErrores],
            ]
        );

        if ($isDryRun) {
            $this->warn('Modo dry-run: no se encoló ningún correo.');
        }

        return self::SUCCESS;
    }
}
