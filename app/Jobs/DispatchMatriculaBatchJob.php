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
        // Rule 11: Optimización masiva (Batching)
        // Evitar timeout para procesos de gran volumen
        set_time_limit(0);

        // Asegurar usuario_id no nulo para evitar errores 23000 de SQL
        $this->usuarioId = $this->usuarioId ?? 999;

        Log::info("Iniciando Job de Matrícula Masiva para Curso ID: {$this->cursoId} (Total: " . count($this->personales) . " personas)");

        $curso = DB::connection('sqlsrv')->table('sw_cursos')->where('codigo', $this->cursoId)->first();
        if (!$curso) {
            Log::error("Job Fallido: Curso {$this->cursoId} no encontrado.");
            return;
        }

        // 1. Pre-cargar matrículas existentes para filtrar en memoria (Rápido)
        $existentes = DB::connection('sqlsrv')->table('sw_matriculas')
            ->where('cod_curso', $this->cursoId)
            ->where('cod_programacion', $this->progId)
            ->pluck('cod_personal')
            ->map(fn($p) => trim($p))
            ->toArray();
        
        $existentesMap = array_flip($existentes);

        // 2. Filtrar lista para solo procesar los nuevos
        $nuevosPersonales = array_filter($this->personales, function($codPers) use ($existentesMap) {
            return !isset($existentesMap[trim($codPers)]);
        });

        if (empty($nuevosPersonales)) {
            Log::info("No hay nuevos personales para matricular (todos ya existen).");
            NotificacionMatricula::crearNotificacionExitosa($this->usuarioId, $this->cursoId, $curso->nombre, count($this->personales));
            return;
        }

        $totalNuevos = count($nuevosPersonales);
        $enviados = count($this->personales) - $totalNuevos;
        $fallidos = 0;

        // 3. Procesar por LOTES (Chunks de 500)
        $chunks = array_chunk($nuevosPersonales, 500);
        
        foreach ($chunks as $chunk) {
            try {
                $batchData = [];
                foreach ($chunk as $codPers) {
                    $batchData[] = [
                        'cod_programacion' => $this->progId,
                        'cod_curso'        => $this->cursoId,
                        'cod_personal'     => trim($codPers),
                        'fecha_matricula'  => DB::raw('GETDATE()'),
                        'estado'           => 'MATRICULADO',
                        'usuario_id'       => $this->usuarioId,
                        'origen_matricula' => 'WEB_BATCH',
                        'created_at'       => DB::raw('GETDATE()'),
                        'updated_at'       => DB::raw('GETDATE()')
                    ];
                }

                // Inserción Masiva por lote (Muy Rápido)
                DB::connection('sqlsrv')->table('sw_matriculas')->insert($batchData);
                $enviados += count($chunk);

            } catch (\Exception $e) {
                $fallidos += count($chunk);
                Log::error("Error en Lote de Matrícula Batch: " . $e->getMessage());
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

        Log::info("Job finalizado: {$enviados} matriculados/actualizados, {$fallidos} fallidos en lotes.");
    }
}
