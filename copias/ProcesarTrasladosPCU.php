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

class ProcesarTrasladosPCU extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capacitacion:procesar-traslados-pcu';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Punto #9: Detecta personal trasladado (cambio de sucursal/cliente) y los matricula en los cursos PCU vigentes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso de detección de traslados (Punto #9)...');
        Log::info('ProcesarTrasladosPCU: Inicio');

        // 1. Obtener cursos PCU (Plan del Usuario - Clientes)
        $cursosPCU = Cursos::where('tipo_curso', 6)
            ->where('habilitado', 1)
            ->get();

        if ($cursosPCU->isEmpty()) {
            $this->warn('No hay cursos PCU habilitados.');
            return;
        }

        foreach ($cursosPCU as $curso) {
            // 2. Buscar programación VIGENTE para este curso
            $programacionesStr = CursoProgramacion::where('cod_cursos', $curso->codigo)
                ->where('estado_periodo', 'VIGENTE')
                ->where('habilitado', 1)
                ->get();

            if ($programacionesStr->isEmpty()) continue;

            // 3. Obtener sucursales asignadas al curso
            $sucursalesIDs = DB::table('sw_curso_sucursales')
                ->where('curso_codigo', $curso->codigo)
                ->pluck('sucursal')
                ->toArray();

            if (empty($sucursalesIDs)) continue;

            // Resolver sucursales legacy (Siso) para estas sucursales de curso
            $legacyCodes = DB::table('sw_clientes')
                ->whereIn('codigo', $sucursalesIDs)
                ->pluck('cod_legacy')
                ->filter()
                ->toArray();
            
            if (empty($legacyCodes)) continue;

            $allSucuCodigos = [];
            foreach ($legacyCodes as $lc) {
                // Usamos la misma lógica del controlador para listar sucursales por cliente
                $extSucs = DB::connection('sqlsrv_controlclientes')
                    ->select('EXEC USP_LISTAR_SUCURSALES_X_CLIENTE :cod_legacy', ['cod_legacy' => $lc]);
                foreach ($extSucs as $s) {
                    if (isset($s->codigo_sucursal)) $allSucuCodigos[] = trim($s->codigo_sucursal);
                }
            }

            if (empty($allSucuCodigos)) continue;
            $allSucuCodigos = array_unique($allSucuCodigos);

            foreach ($programacionesStr as $prog) {
                // 4. Buscar trabajadores en estas sucursales que NO estén en sw_matriculas para esta programación
                // Un "Traslado" se detecta porque ahora el trabajador cumple el criterio de sucursal pero no tiene matrícula
                $personalIds = DB::table('sw_MIGRA_PERSONAL')
                    ->where('ESTA_ACTI', 1)
                    ->whereIn('SUCU_CODIGO', $allSucuCodigos)
                    ->pluck('CODI_PERS')
                    ->toArray();

                if (empty($personalIds)) continue;

                // Filtrar los que ya están matriculados
                $yaMatriculados = DB::table('sw_matriculas')
                    ->where('cod_programacion', $prog->codigo_programacion)
                    ->pluck('cod_personal')
                    ->toArray();

                $porMatricular = array_diff($personalIds, $yaMatriculados);

                if (empty($porMatricular)) continue;

                $this->info("Curso '{$curso->nombre}': Detectados " . count($porMatricular) . " posibles traslados/pendientes.");

                $count = 0;
                foreach ($porMatricular as $codPers) {
                    try {
                        DB::table('sw_matriculas')->insert([
                            'cod_programacion' => $prog->codigo_programacion,
                            'cod_curso'        => $curso->codigo,
                            'cod_personal'     => $codPers,
                            'fecha_matricula'  => Carbon::now()->format('Y-m-d\TH:i:s.000'),
                            'estado'           => 'MATRICULADO',
                            'usuario_id'       => 0, // Sistema
                            'tipo_matricula'   => 'AUTOMATICA',
                            'origen_matricula' => 'TRASLADO_DETECTION',
                            'habilitado'       => 1
                        ]);
                        $count++;
                    } catch (\Exception $e) {
                        Log::error("ProcesarTrasladosPCU: Error insertando matrícula para {$codPers}: " . $e->getMessage());
                    }
                }
                $this->info("Matriculados {$count} personas exitosamente.");
            }
        }

        $this->info('Proceso de traslados finalizado.');
        Log::info('ProcesarTrasladosPCU: Fin');
    }
}
