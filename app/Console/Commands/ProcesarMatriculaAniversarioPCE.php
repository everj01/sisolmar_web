<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Personal;
use App\Models\Cursos;
use App\Models\Matricula;
use App\Models\CursoProgramacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcesarMatriculaAniversarioPCE extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capacitacion:procesar-aniversario-pce';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Punto #6: Matricula al personal en el PCE en el mes exacto que ingresó (aniversario), si es un curso periódico.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso Punto #6 (Aniversario PCE)...');

        $now = Carbon::now();
        $mes = $now->month;
        $anio = $now->year;
        $periodo = $now->format('Y-m');

        // 1. PCE Periódicos
        $cursos = DB::connection('sqlsrv')->table('sw_cursos')
            ->where('tipo_curso', 5)
            ->where('habilitado', 1)
            ->where('es_periodico', 1)
            ->get();

        if ($cursos->isEmpty()) {
            $this->warn('No hay cursos PCE periódicos.');
            return;
        }

        // 2. Personal en mes de aniversario (Fuente oficial si_solm)
        $personal = DB::connection('sqlsrv')->table('si_solm.dbo.PERSONAL')
            ->where('ESTA_ACTI', 1)
            ->whereRaw('MONTH(FECH_INGRE) = ?', [$mes])
            ->get();

        if ($personal->isEmpty()) {
            $this->info("No hay personal en aniversario para el mes {$mes}.");
            return;
        }

        foreach ($cursos as $curso) {
            $prog = $this->obtenerOCrearProgramacion($curso, $periodo);
            
            foreach ($personal as $trabajador) {
                // No matricular dos veces el mismo año
                $ya = DB::connection('sqlsrv')->table('sw_matriculas')
                    ->where('cod_personal', trim($trabajador->CODI_PERS))
                    ->where('cod_curso', $curso->codigo)
                    ->whereYear('fecha_matricula', $anio)
                    ->exists();

                if ($ya) continue;

                try {
                    DB::connection('sqlsrv')->table('sw_matriculas')->insert([
                        'cod_programacion' => $prog->codigo_programacion,
                        'cod_curso'        => $curso->codigo,
                        'cod_personal'     => trim($trabajador->CODI_PERS),
                        'fecha_matricula'  => DB::raw('GETDATE()'),
                        'estado'           => 'MATRICULADO',
                        'usuario_id'       => 0,
                        'origen_matricula' => 'AUTO_ANIVERSARIO',
                        'habilitado'       => 1,
                        'fecha_creacion'   => DB::raw('GETDATE()')
                    ]);
                } catch (\Exception $e) {
                    Log::error("Error Aniversario (CP:{$trabajador->CODI_PERS}, C:{$curso->codigo}): " . $e->getMessage());
                }
            }
        }

        $this->info('Proceso aniversario completado.');
    }

    private function obtenerOCrearProgramacion($curso, $periodo)
    {
        $prog = DB::connection('sqlsrv')->table('sw_cursos_programacion')
            ->where('cod_cursos', $curso->codigo)
            ->where('periodo', $periodo)
            ->where('estado_periodo', 'VIGENTE')
            ->where('habilitado', 1)
            ->first();

        if ($prog) return $prog;

        // Crear nueva programación para el periodo
        $last = DB::connection('sqlsrv')->table('sw_cursos_programacion')
            ->orderBy('codigo_programacion', 'desc')
            ->first();
        
        $nextCode = $last ? str_pad(intval($last->codigo_programacion) + 1, 4, '0', STR_PAD_LEFT) : '1001';

        DB::connection('sqlsrv')->table('sw_cursos_programacion')->insert([
            'codigo_programacion' => $nextCode,
            'cod_cursos'    => $curso->codigo,
            'periodo'       => $periodo,
            'tipo'          => 'REGULAR',
            'estado_periodo'=> 'VIGENTE',
            'fecha_inicio'  => Carbon::now()->startOfMonth()->format('Y-m-d H:i:s'),
            'fecha_final'   => Carbon::now()->endOfMonth()->format('Y-m-d H:i:s'),
            'fecha_creacion'=> DB::raw('GETDATE()'),
            'habilitado'    => 1,
        ]);

        return DB::connection('sqlsrv')->table('sw_cursos_programacion')
            ->where('codigo_programacion', $nextCode)
            ->first();
    }
}
