<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cursos;
use App\Models\CursoProgramacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcesarCursosPeriodicosVencidosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capacitacion:procesar-cursos-periodicos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Procesa cursos periódicos vencidos, cerrando programaciones vigentes y generando nuevas programaciones pendientes.';

    public function handle()
    {
        $this->info('Obteniendo cursos vencidos...');

        $programacionesVencidas = DB::select('EXEC SP_OBTENER_CURSOS_EXPIRADOS');

        if (empty($programacionesVencidas)) {
            $this->info('No se encontraron cursos vencidos.');
            return self::SUCCESS;
        }

        $clonados = 0;

        foreach ($programacionesVencidas as $programacion) {

            $this->info(
                "[VENCIDO DETECTADO] Curso {$programacion->codigo_curso} " .
                    "(Prog: {$programacion->codigo_programacion}) " .
                    "- Fecha clonación: {$programacion->fecha_proxima_clonacion}"
            );

            DB::beginTransaction();

            try {

                $fechaProximaClonacion = Carbon::parse(
                    $programacion->fecha_proxima_clonacion
                );

                $progModel = CursoProgramacion::where(
                    'codigo_programacion',
                    $programacion->codigo_programacion
                )->first();

                if ($progModel) {
                    $progModel->update([
                        'estado_periodo'     => 'CERRADO',
                        'fecha_modificacion' => now()->format('Y-m-d\TH:i:s.000')
                    ]);
                }

                $lastProg = CursoProgramacion::orderBy(
                    'codigo_programacion',
                    'desc'
                )->first();

                $newProgCod = $lastProg
                    ? str_pad(
                        intval($lastProg->codigo_programacion) + 1,
                        4,
                        '0',
                        STR_PAD_LEFT
                    )
                    : '1000';

                $nuevoPeriodo = $fechaProximaClonacion->format('Y-m');

                $baseNombre = preg_replace(
                    '/\s*\|\s*\d{4}-\d{2}$/',
                    '',
                    $programacion->curso_nombre
                );

                $nuevoNombre = $baseNombre . ' | ' . $nuevoPeriodo;

                Cursos::where('codigo', $programacion->id_curso)
                    ->update([
                        'nombre' => $nuevoNombre
                    ]);

                CursoProgramacion::create([
                    'codigo_programacion' => $newProgCod,
                    'cod_curso'           => $programacion->id_curso,
                    'periodo'             => $nuevoPeriodo,
                    'tipo'                => 'REGULAR',
                    'estado_periodo'      => 'PENDIENTE',

                    'fecha_inicio'        => $fechaProximaClonacion
                        ->copy()
                        ->startOfDay()
                        ->format('Y-m-d\TH:i:s.000'),

                    'fecha_final'         => $fechaProximaClonacion
                        ->copy()
                        ->endOfMonth()
                        ->endOfDay()
                        ->format('Y-m-d\TH:i:s.000'),

                    'fecha_creacion'      => now()->format('Y-m-d\TH:i:s.000'),
                    'habilitado'          => 1,
                ]);

                DB::commit();

                $clonados++;

                $this->info(
                    "   -> Programación {$newProgCod} creada correctamente."
                );

                Log::info(
                    "Curso {$programacion->codigo_curso} procesado correctamente.",
                    [
                        'programacion_anterior' => $programacion->codigo_programacion,
                        'nueva_programacion'    => $newProgCod,
                        'nuevo_periodo'         => $nuevoPeriodo
                    ]
                );
            } catch (\Exception $e) {

                DB::rollBack();

                $this->error(
                    "[ERROR] Curso {$programacion->codigo_curso}: " .
                        $e->getMessage()
                );

                Log::error(
                    'Error procesando curso periódico vencido',
                    [
                        'curso' => $programacion->codigo_curso,
                        'error' => $e->getMessage(),
                        'line'  => $e->getLine()
                    ]
                );
            }
        }

        $this->info("Proceso finalizado. Cursos procesados: {$clonados}");

        return self::SUCCESS;
    }
}