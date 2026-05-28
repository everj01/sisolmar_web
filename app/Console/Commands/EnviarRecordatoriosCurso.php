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
        $registros = DB::connection('mysql_grupoihb')
            ->select('CALL SP_OBTENER_RECORDATORIOS_PENDIENTES(?)', [date('Y')]);

        if (empty($registros)) {
            $this->info('Sin pendientes.');
            return self::SUCCESS;
        }

        $porUsuario = collect($registros)->groupBy('user_id');

        $totalUsuariosSP = $porUsuario->count();

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

                $puedeEnviar = !$isDryRun || $usuario->username === '76067492';

                if ($puedeEnviar) {

                    $mailable = $cursos->count() === 1
                        ? new RecordatorioCursoMail(
                            $usuario,
                            $cursos->first(),
                        )
                        : new RecordatorioCursosPendientesMail(
                            $usuario,
                            $cursos->values()->all(),
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
                ['Correos enviados', $totalEnviados],
                ['Errores', $totalErrores],
            ]
        );

        if ($isDryRun) {
            $this->warn('Dry-run activo');
        }

        return self::SUCCESS;
    }
}
