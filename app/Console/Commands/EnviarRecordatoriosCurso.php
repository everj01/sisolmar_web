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
        $isDryRun = $this->option('dry-run');

        $totalEnviados = 0;
        $totalErrores  = 0;

        $registros = DB::connection('mysql_grupoihb')
            ->select('CALL SP_OBTENER_RECORDATORIOS_PENDIENTES(?)', [date('Y')]);

        if (empty($registros)) {
            $this->info('Sin pendientes.');
            return self::SUCCESS;
        }

        $porUsuario = collect($registros)->groupBy('user_id');

        $this->info("{$porUsuario->count()} usuario(s) con pendientes.");

        $bar = $this->output->createProgressBar($porUsuario->count());
        $bar->start();

        foreach ($porUsuario as $userId => $cursos) {
            $usuario = $cursos->first();

            try {
                $ultimoMemo = DB::table('SW_MEMO_RECORDATORIOS')
                    ->where('MOODLE_USER_ID', $userId)
                    ->max('NUM_MEMO');

                $siguienteMemo = match ((int) $ultimoMemo) {
                    1 => 2,
                    2 => 3,
                    3 => 1,
                    default => 1,
                };

                $memoId = DB::table('SW_MEMO_RECORDATORIOS')->insertGetId([
                    'NRO_DOCU_IDEN'  => $usuario->username,
                    'MOODLE_USER_ID' => $userId,
                    'NOMBRE_COMPLETO' => $usuario->full_name,
                    'NUM_MEMO'       => $siguienteMemo,
                    'FECHA_ENVIO' => now()->format('Y-m-d H:i:s'),
                ]);

                $insertCursos = $cursos->map(fn($curso) => [
                    'MEMO_RECORDATORIO_ID' => $memoId,
                    'CODIGO_MOODLE'        => $curso->course_id,
                    'NOMBRE_CURSO'         => $curso->course_name,
                ])->toArray();

                DB::table('SW_MEMO_RECORDATORIOS_CURSOS')->insert($insertCursos);

                if (!$isDryRun) {
                    $mailable = $cursos->count() === 1
                        ? new RecordatorioCursoMail(
                            $usuario,
                            $cursos->first(),
                            $siguienteMemo
                        )
                        : new RecordatorioCursosPendientesMail(
                            $usuario,
                            $cursos->values()->all(),
                            $siguienteMemo
                        );

                    Mail::to($usuario->email)->queue($mailable);
                }

                $totalEnviados++;
            } catch (\Throwable $e) {
                $totalErrores++;
                Log::error("Error usuario {$userId}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Métrica', 'Total'],
            [
                ['Usuarios', $porUsuario->count()],
                ['Enviados', $totalEnviados],
                ['Errores', $totalErrores],
            ]
        );

        if ($isDryRun) {
            $this->warn('Dry-run activo');
        }

        return self::SUCCESS;
    }
}
