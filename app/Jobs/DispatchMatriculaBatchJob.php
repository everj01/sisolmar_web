<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\NotificacionMatricula;

class DispatchMatriculaBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cursoId;
    protected $progId;
    protected $personales;
    protected $usuarioId;

    /**
     * Create a new job instance.
     */
    public function __construct($cursoId, $progId, $personales, $usuarioId)
    {
        $this->cursoId = $cursoId;
        $this->progId = $progId;
        $this->personales = $personales;
        $this->usuarioId = $usuarioId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("Iniciando Job de Matrícula Masiva para Curso ID: {$this->cursoId}");

        $curso = DB::connection('sqlsrv')->table('sw_cursos')->where('codigo', $this->cursoId)->first();
        if (!$curso) {
            Log::error("Job Fallido: Curso {$this->cursoId} no encontrado.");
            return;
        }

        $enviados = 0;
        $fallidos = 0;

        foreach ($this->personales as $codPers) {
            try {
                // Verificar si ya existe matrícula activa en este curso/programación
                $existe = DB::connection('sqlsrv')->table('sw_matriculas')
                    ->where('cod_curso', $this->cursoId)
                    ->where('cod_programacion', $this->progId)
                    ->where('cod_personal', trim($codPers))
                    ->exists();

                if ($existe) {
                    $enviados++; // Contamos como procesado
                    continue;
                }

                // Insertar matrícula (Pauta DB: Query Builder + GETDATE)
                DB::connection('sqlsrv')->table('sw_matriculas')->insert([
                    'cod_programacion' => $this->progId,
                    'cod_curso'        => $this->cursoId,
                    'cod_personal'     => trim($codPers),
                    'fecha_matricula'  => DB::raw('GETDATE()'),
                    'estado'           => 'MATRICULADO',
                    'usuario_id'       => $this->usuarioId,
                    'origen_matricula' => 'WEB_BATCH',
                    'fecha_creacion'   => DB::raw('GETDATE()')
                ]);

                $enviados++;
            } catch (\Exception $e) {
                $fallidos++;
                Log::error("Error en Matrícula Batch (CP:{$codPers}, C:{$this->cursoId}): " . $e->getMessage());
            }
        }

        // Crear notificación final para el usuario
        if ($fallidos == 0) {
            NotificacionMatricula::crearNotificacionExitosa(
                $this->usuarioId, 
                $this->cursoId, 
                $curso->nombre, 
                count($this->personales)
            );
        } else {
            NotificacionMatricula::crearNotificacionMultiplesFallos(
                $this->usuarioId, 
                $this->cursoId, 
                $curso->nombre, 
                count($this->personales), 
                $enviados, 
                $fallidos
            );
        }

        Log::info("Job de Matrícula Masiva finalizado: {$enviados} exitosos, {$fallidos} fallidos.");
    }
}
