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
        $this->info('Iniciando detección de traslados (Pauta 9: Cliente y Área)...');

        // 1. Detección PCU (Por Cliente) - Cursos Tipo 6
        $cursosPCU = DB::connection('sqlsrv')->table('sw_cursos')
            ->where('tipo_curso', 6)
            ->where('habilitado', 1)
            ->get();
        foreach ($cursosPCU as $c) $this->procesarCriterio($c, 'PCU');

        // 2. Detección PCI (Por Área) - Cursos Tipo 7
        $cursosPCI = DB::connection('sqlsrv')->table('sw_cursos')
            ->where('tipo_curso', 7)
            ->where('habilitado', 1)
            ->get();
        foreach ($cursosPCI as $c) $this->procesarCriterio($c, 'PCI');

        $this->info('Proceso de traslados finalizado.');
    }

    private function procesarCriterio($curso, $target)
    {
        $prog = DB::connection('sqlsrv')->table('sw_cursos_programacion')
            ->where('cod_cursos', $curso->codigo)
            ->where('estado_periodo', 'VIGENTE')
            ->where('habilitado', 1)
            ->first();
        
        if (!$prog) return;

        // IDs asignados al curso (clientes o áreas) en Solmar Web
        $asignados = DB::connection('sqlsrv')->table('sw_curso_sucursales')
            ->where('curso_codigo', $curso->codigo)
            ->pluck('sucursal')
            ->toArray();
        
        if (empty($asignados)) return;

        // Intentar obtener personal de si_solm
        $personalIds = [];

        if ($target == 'PCI') {
            // El mapeo de PCI suele ser por CODI_CARG o CODI_AREA. 
            // En Solmar, PCI se mapea a CARGOS/ÁREAS específicos.
            $personalIds = DB::connection('sqlsrv')->table('si_solm.dbo.PERSONAL')
                ->where('ESTA_ACTI', 1)
                ->whereIn('CODI_CARG', $asignados) // Asumiendo mapeo por cargo en PCI
                ->pluck('CODI_PERS')
                ->map(fn($id) => trim($id))
                ->toArray();
        } else { // PCU (Por Cliente)
            // Obtener códigos de sede Solmar (SUCU_CODIGO) para los clientes asignados
            $legacyCodes = DB::connection('sqlsrv')->table('sw_clientes')
                ->whereIn('codigo', $asignados)
                ->pluck('cod_legacy')
                ->filter()
                ->toArray();
            
            $sucuCodigos = [];
            foreach ($legacyCodes as $lc) {
                try {
                    $ext = DB::connection('sqlsrv_controlclientes')->select('EXEC USP_LISTAR_SUCURSALES_X_CLIENTE :cod', ['cod' => $lc]);
                    foreach ($ext as $s) if (isset($s->codigo_sucursal)) $sucuCodigos[] = trim($s->codigo_sucursal);
                } catch (\Exception $e) {
                    Log::warning("Error obteniendo sucursales traslados: " . $e->getMessage());
                }
            }
            
            if (empty($sucuCodigos)) return;

            $personalIds = DB::connection('sqlsrv')->table('si_solm.dbo.PERSONAL')
                ->where('ESTA_ACTI', 1)
                ->whereIn('SUCU_CODIGO', array_unique($sucuCodigos))
                ->pluck('CODI_PERS')
                ->map(fn($id) => trim($id))
                ->toArray();
        }

        if (empty($personalIds)) return;

        // Filtrar los que NO están matriculados en esta programación específica
        $yaMatriculados = DB::connection('sqlsrv')->table('sw_matriculas')
            ->where('cod_programacion', $prog->codigo_programacion)
            ->pluck('cod_personal')
            ->map(fn($id) => trim($id))
            ->toArray();
        
        $pendientes = array_diff($personalIds, $yaMatriculados);

        foreach ($pendientes as $codPers) {
            try {
                // Pauta DB: Usar GETDATE() y Query Builder
                DB::connection('sqlsrv')->table('sw_matriculas')->insert([
                    'cod_programacion' => $prog->codigo_programacion,
                    'cod_curso'        => $curso->codigo,
                    'cod_personal'     => $codPers,
                    'fecha_matricula'  => DB::raw('GETDATE()'),
                    'estado'           => 'MATRICULADO',
                    'usuario_id'       => 0,
                    'origen_matricula' => "AUTO_TRASLADO_{$target}",
                    'habilitado'       => 1,
                    'fecha_creacion'   => DB::raw('GETDATE()')
                ]);
            } catch (\Exception $e) {
                Log::error("Error Traslado {$target} (CP:{$codPers}, C:{$curso->codigo}): " . $e->getMessage());
            }
        }
    }
}
