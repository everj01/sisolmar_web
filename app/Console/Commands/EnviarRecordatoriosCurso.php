<?php

  namespace App\Console\Commands;

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

          $totalEnviados       = 0;
          $totalErrores        = 0;
          $totalSinCorreo      = 0;
          $totalCorreoInvalido = 0;

          $usuarios = DB::connection('mysql_grupoihb')
              ->select(
                  'CALL SP_OBTENER_RECORDATORIOS_PENDIENTES(?)',
                  [date('Y')]
              );

          if (empty($usuarios)) {
              $this->info('Sin pendientes.');
              return self::SUCCESS;
          }

          $totalUsuariosSP = count($usuarios);

          $this->info("{$totalUsuariosSP} usuario(s) obtenidos del SP.");

          $bar = $this->output->createProgressBar($totalUsuariosSP);
          $bar->start();

          foreach ($usuarios as $usuario) {

              try {

                  $email = trim((string) $usuario->email);

                  if (empty($email)) {

                      $totalSinCorreo++;

                      Log::warning(
                          "Usuario {$usuario->user_id} sin correo."
                      );

                      $bar->advance();

                      continue;
                  }

                  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

                      $totalCorreoInvalido++;

                      Log::warning(
                          "Usuario {$usuario->user_id} con correo inválido: {$email}"
                      );

                      $bar->advance();

                      continue;
                  }

                  $cursosPendientes = json_decode(
                      $usuario->cursos_pendientes
                  );

                  $usuariosPrueba = [
                      '76067492',
                      // '75412099',
                  ];

                  $puedeEnviar = !$isDryRun
                      || in_array(
                          $usuario->username,
                          $usuariosPrueba,
                          true
                      );

                  if ($puedeEnviar) {

                      Mail::to($email)
                          ->queue(
                              new RecordatorioCursosPendientesMail(
                                  $usuario,
                                  $cursosPendientes
                              )
                          );

                      $totalEnviados++;
                  }
              } catch (\Throwable $e) {

                  $totalErrores++;

                  Log::error(
                      "Error usuario {$usuario->user_id}: {$e->getMessage()}",
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