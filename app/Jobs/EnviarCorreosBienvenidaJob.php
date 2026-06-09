<?php

namespace App\Jobs;

use App\Mail\BienvenidaMatriculaMail;
use App\Models\Personal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarCorreosBienvenidaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        private readonly array  $personalIds,
        private readonly string $nombreCurso,
        private readonly string $fechaInicio,
        private readonly string $fechaFin,
    ) {}

    public function handle(): void
    {
        foreach ($this->personalIds as $codPersonal) {
            try {
                $personal = Personal::where('CODI_PERS', $codPersonal)->first();

                if (!$personal) continue;

                $email = $this->resolverEmail($personal, $codPersonal);

                if (!$email) continue;

                $nombre = trim("{$personal->NOMB_1} {$personal->APEL_1}");

                Mail::to($email)->queue(
                    new BienvenidaMatriculaMail(
                        nombrePersonal: $nombre,
                        nombreCurso: $this->nombreCurso,
                        fechaInicio: $this->fechaInicio,
                        fechaFin: $this->fechaFin
                    )
                );
            } catch (\Exception $e) {
                Log::warning("EnviarCorreosBienvenidaJob: fallo para {$codPersonal}: " . $e->getMessage());
            }
        }
    }

    private function resolverEmail(Personal $personal, string $codPersonal): ?string
    {
        $email = trim($personal->PERS_EMAIL ?? '');

        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $email;
        }

        Log::info("EnviarCorreosBienvenidaJob: personal {$codPersonal} sin correo registrado, se omite.");
        return null;
    }
}

;