<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Models\Matricula;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DispatchMatriculaBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $cursoCodigo;
    protected $programacionCodigo;
    protected $personalIds;
    protected $usuarioId;

    /**
     * Create a new job instance.
     */
    public function __construct($cursoCodigo, $programacionCodigo, array $personalIds, $usuarioId)
    {
        $this->cursoCodigo = $cursoCodigo;
        $this->programacionCodigo = $programacionCodigo;
        $this->personalIds = $personalIds;
        $this->usuarioId = $usuarioId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::info("Iniciando matrícula masiva para Curso: {$this->cursoCodigo}, Programación: {$this->programacionCodigo}. Total: " . count($this->personalIds));

        $timestamp = Carbon::now()->format('Y-m-d\TH:i:s.000');
        $lotes = array_chunk($this->personalIds, 100);

        foreach ($lotes as $lote) {
            $data = [];
            foreach ($lote as $personalId) {
                // El orden debe coincidir exactamente con la tabla sw_matriculas para evitar errores en SQL Server (posicional)
                // Columnas: cod_curso, cod_personal, usuario_id, fecha_matricula, estado, tipo_matricula, origen_matricula, created_at, updated_at, cod_programacion
                $data[] = [
                    'cod_curso'        => (int)$this->cursoCodigo,
                    'cod_personal'     => (string)$personalId,
                    'usuario_id'       => (int)$this->usuarioId,
                    'fecha_matricula'  => $timestamp,
                    'estado'           => 'MATRICULADO',
                    'tipo_matricula'   => 'MANUAL',
                    'origen_matricula' => 'MANUAL_MASIVA',
                    'created_at'       => $timestamp,
                    'updated_at'       => $timestamp,
                    'cod_programacion' => (string)$this->programacionCodigo,
                ];
            }

            if (!empty($data)) {
                try {
                    DB::table('sw_matriculas')->insert($data);
                } catch (\Exception $e) {
                    Log::error("Error en insert masivo sw_matriculas: " . $e->getMessage());
                    throw $e;
                }
            }
        }

        Log::info("Matrícula masiva completada para Curso: {$this->cursoCodigo}");
    }
}
