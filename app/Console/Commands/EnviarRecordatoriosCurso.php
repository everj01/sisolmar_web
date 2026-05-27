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
    protected $signature = 'capacitacion:enviar-recordatorios-curso {--dry-run : Muestra cuántos correos se enviarían sin enviarlos}';

    protected $description = 'Envía correos recordatorios a matriculados que no han iniciado sus cursos';

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $totalEnviados        = 0;
        $totalErrores         = 0;
        $totalSinCorreo       = 0;
        $totalCorreoInvalido  = 0;
        $totalRegistradosMemo = 0;

        $registros = DB::connection('mysql_grupoihb')
            ->select('CALL SP_OBTENER_RECORDATORIOS_PENDIENTES(?)', [date('Y')]);

        if (empty($registros)) {
            $this->info('Sin pendientes.');
            return self::SUCCESS;
        }

        $porUsuario = collect($registros)->groupBy('user_id');

        $totalUsuariosSP = $porUsuario->count();

        $memosPrevios = DB::table('SW_MEMO_RECORDATORIOS')
            ->select(
                'MOODLE_USER_ID',
                'NUM_MEMO',
                'FECHA_ENVIO'
            )
            ->orderByDesc('FECHA_ENVIO')
            ->get()
            ->groupBy('MOODLE_USER_ID');

        $this->info("{$totalUsuariosSP} usuario(s) obtenidos del SP.");

        $bar = $this->output->createProgressBar($totalUsuariosSP);
        $bar->start();

        foreach ($porUsuario as $userId => $cursos) {

            $usuario = $cursos->first();

            try {

                $email = trim((string) $usuario->email);

                $emailValido = filter_var($email, FILTER_VALIDATE_EMAIL);

                if (empty($email)) {

                    $totalSinCorreo++;

                    Log::warning("Usuario {$userId} sin correo.");

                    $bar->advance();

                    continue;
                }

                if (!$emailValido) {

                    $totalCorreoInvalido++;

                    Log::warning("Usuario {$userId} con correo inválido: {$email}");

                    $bar->advance();

                    continue;
                }

                $userMemos = $memosPrevios[$userId] ?? collect();

                $ultimoMemo = optional($userMemos->first())->NUM_MEMO;

                $siguienteMemo = match ((int) $ultimoMemo) {
                    1 => 2,
                    2 => 3,
                    3 => 1,
                    default => 1,
                };

                $fechaPrimerMEMO = null;
                $fechaSegundoMEMO = null;

                if ($siguienteMemo >= 2) {

                    $primerMemo = $userMemos
                        ->where('NUM_MEMO', 1)
                        ->first();

                    $fechaPrimerMEMO = $primerMemo
                        ? date('d/m/Y', strtotime($primerMemo->FECHA_ENVIO))
                        : null;
                }

                if ($siguienteMemo >= 3) {

                    $segundoMemo = $userMemos
                        ->where('NUM_MEMO', 2)
                        ->first();

                    $fechaSegundoMEMO = $segundoMemo
                        ? date('d/m/Y', strtotime($segundoMemo->FECHA_ENVIO))
                        : null;
                }

                $memoId = DB::table('SW_MEMO_RECORDATORIOS')->insertGetId([
                    'NRO_DOCU_IDEN'   => $usuario->username,
                    'MOODLE_USER_ID'  => $userId,
                    'NOMBRE_COMPLETO' => $usuario->full_name,
                    'NUM_MEMO'        => $siguienteMemo,
                    'FECHA_ENVIO'     => now()->format('Y-m-d H:i:s'),
                ]);

                $totalRegistradosMemo++;

                $insertCursos = $cursos->map(fn($curso) => [
                    'MEMO_RECORDATORIO_ID' => $memoId,
                    'CODIGO_MOODLE'        => $curso->course_id,
                    'NOMBRE_CURSO'         => $curso->course_name,
                ])->toArray();

                DB::table('SW_MEMO_RECORDATORIOS_CURSOS')
                    ->insert($insertCursos);

                $puedeEnviar = !$isDryRun || $usuario->username === '76067492';

                if ($puedeEnviar) {

                    $mailable = $cursos->count() === 1
                        ? new RecordatorioCursoMail(
                            $usuario,
                            $cursos->first(),
                            $siguienteMemo,
                            $fechaPrimerMEMO,
                            $fechaSegundoMEMO
                        )
                        : new RecordatorioCursosPendientesMail(
                            $usuario,
                            $cursos->values()->all(),
                            $siguienteMemo,
                            $fechaPrimerMEMO,
                            $fechaSegundoMEMO
                        );

                    Mail::to($email)
                        ->queue($mailable);

                    $totalEnviados++;
                }
            } catch (\Throwable $e) {

                $totalErrores++;

                Log::error(
                    "Error usuario {$userId}: {$e->getMessage()}",
                    [
                        'trace' => $e->getTraceAsString(),
                    ]
                );
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);

        $this->table(
            ['Métrica', 'Total'],
            [
                ['Usuarios obtenidos SP', $totalUsuariosSP],
                ['Usuarios sin correo', $totalSinCorreo],
                ['Usuarios correo inválido', $totalCorreoInvalido],
                ['MEMOs registrados', $totalRegistradosMemo],
                ['MEMOs enviados', $totalEnviados],
                ['Errores', $totalErrores],
            ]
        );

        if ($isDryRun) {
            $this->warn('Dry-run activo');
        }

        return self::SUCCESS;
    }
}
