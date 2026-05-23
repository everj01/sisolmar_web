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
        $totalOmitidos = 0;

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

            try {
                $cursosValidos = $cursos->filter(function ($curso) use ($userId) {
                    $memoCount = DB::table('memo_recordatorios')
                        ->where('user_id', $userId)
                        ->where('course_id', $curso->course_id)
                        ->count();

                    return $memoCount < 3;
                });

                if ($cursosValidos->isEmpty()) {
                    $totalOmitidos++;
                    $bar->advance();
                    continue;
                }

                $numeroMemo = $cursosValidos->map(function ($curso) use ($userId) {
                    return DB::table('memo_recordatorios')
                        ->where('user_id', $userId)
                        ->where('course_id', $curso->course_id)
                        ->count() + 1;
                })->max();

                if (!$isDryRun) {
                    $mailable = $cursosValidos->count() === 1
                        ? new RecordatorioCursoMail($usuario, $cursosValidos->first(), $numeroMemo)
                        : new RecordatorioCursosPendientesMail($usuario, $cursosValidos->values()->all(), $numeroMemo);

                    Mail::to($usuario->email)->queue($mailable);

                    foreach ($cursosValidos as $curso) {
                        $memoCount = DB::table('memo_recordatorios')
                            ->where('user_id', $userId)
                            ->where('course_id', $curso->course_id)
                            ->count();

                        DB::table('memo_recordatorios')->insert([
                            'user_id'     => $userId,
                            'full_name'   => $usuario->full_name,
                            'email'       => $usuario->email,
                            'course_id'   => $curso->course_id,
                            'course_name' => $curso->course_name,
                            'numero_memo' => $memoCount + 1,
                            'enviado_at'  => now(),
                        ]);
                    }
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
                ['Omitidos (límite 3)', $totalOmitidos],
                ['Errores',             $totalErrores],
            ]
        );

        if ($isDryRun) {
            $this->warn('Modo dry-run: no se encoló ningún correo.');
        }

        return self::SUCCESS;
    }
}
