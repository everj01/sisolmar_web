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

class ProcesarInduccionAutomatica extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capacitacion:procesar-induccion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifica nuevos trabajadores y los matricula automáticamente en cursos obligatorios al alta.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando proceso de inducción automática (Pauta 8)...');

        // Detectar ingresos recientes (24h)
        $hace24Horas = Carbon::now()->subDay();
        
        // Pauta 10/11: Fuente oficial si_solm
        $nuevosTrabajadores = DB::connection('sqlsrv')->table('si_solm.dbo.PERSONAL')
            ->where('ESTA_ACTI', 1)
            ->where(function($query) use ($hace24Horas) {
                $query->where('USUA_FECHA_REG', '>=', $hace24Horas->format('Y-m-d H:i:s'))
                      ->orWhere('FECH_INGRE', '>=', $hace24Horas->format('Y-m-d'));
            })->get();

        if ($nuevosTrabajadores->isEmpty()) {
            $this->info('No se detectaron nuevos trabajadores en las últimas 24 horas.');
            return;
        }

        $this->info('Trabajadores nuevos detectados: ' . $nuevosTrabajadores->count());

        foreach ($nuevosTrabajadores as $trabajador) {
            $this->processPersonal($trabajador);
        }

        $this->info("Proceso completado.");
    }

    private function processPersonal($trabajador)
    {
        // Pauta 8: Alta en 3 planes (Inducción/Corporativo, SSOMA, etc.)
        // Buscamos cursos obligatorios al alta (obligatorio_alta = 1)
        $cursosAlta = DB::connection('sqlsrv')->table('sw_cursos')
            ->where('obligatorio_alta', 1)
            ->where('habilitado', 1)
            ->get();

        foreach ($cursosAlta as $curso) {
            $this->matricular($trabajador, $curso, 'AUTO_ALTA_GENERAL');
        }

        // PCU: Según SUCU_CODIGO (Si existe mapeo en sw_clientes)
        if ($trabajador->SUCU_CODIGO) {
            $pcu = DB::connection('sqlsrv')->table('sw_cursos as c')
                ->join('sw_capacitacion_tipo_curso as tc', 'c.tipo_curso', '=', 'tc.codigo')
                ->join('sw_curso_sucursales as cs', 'c.codigo', '=', 'cs.curso_codigo')
                ->where('tc.descripcion', 'LIKE', '%PCU%')
                ->where('cs.sucursal', trim($trabajador->SUCU_CODIGO))
                ->where('c.habilitado', 1)
                ->select('c.*')
                ->get();
            
            foreach ($pcu as $c) $this->matricular($trabajador, $c, 'AUTO_ALTA_PCU');
        }

        // PCI: Según CODI_CARG (Área)
        if ($trabajador->CODI_CARG) {
            $pci = DB::connection('sqlsrv')->table('sw_cursos as c')
                ->join('sw_capacitacion_tipo_curso as tc', 'c.tipo_curso', '=', 'tc.codigo')
                ->join('sw_curso_sucursales as cs', 'c.codigo', '=', 'cs.curso_codigo')
                ->where('tc.descripcion', 'LIKE', '%PCI%')
                ->where('cs.sucursal', trim($trabajador->CODI_CARG))
                ->where('c.habilitado', 1)
                ->select('c.*')
                ->get();
            
            foreach ($pci as $c) $this->matricular($trabajador, $c, 'AUTO_ALTA_PCI');
        }
    }

    private function matricular($trabajador, $curso, $origen)
    {
        // 1. Verificar si ya está matriculado hoy
        $ya = DB::connection('sqlsrv')->table('sw_matriculas')
            ->where('cod_personal', trim($trabajador->CODI_PERS))
            ->where('cod_curso', $curso->codigo)
            ->exists();
        
        if ($ya) return;

        // 2. Buscar programación vigente
        $prog = DB::connection('sqlsrv')->table('sw_cursos_programacion')
            ->where('cod_cursos', $curso->codigo)
            ->where('estado_periodo', 'VIGENTE')
            ->where('habilitado', 1)
            ->orderBy('fecha_inicio', 'desc')
            ->first();
        
        if (!$prog) return;

        try {
            // Pauta DB: Usar GETDATE() y Query Builder
            DB::connection('sqlsrv')->table('sw_matriculas')->insert([
                'cod_programacion' => $prog->codigo_programacion,
                'cod_curso'        => $curso->codigo,
                'cod_personal'     => trim($trabajador->CODI_PERS),
                'fecha_matricula'  => DB::raw('GETDATE()'),
                'estado'           => 'MATRICULADO',
                'usuario_id'       => 0, // 0 = Sistema
                'origen_matricula' => $origen,
                'habilitado'       => 1,
                'fecha_creacion'   => DB::raw('GETDATE()')
            ]);
            
            $this->info("Trabajador {$trabajador->CODI_PERS} matriculado en {$curso->nombre} ({$origen})");
        } catch (\Exception $e) {
            Log::error("Error Inducción (CP:{$trabajador->CODI_PERS}, C:{$curso->codigo}): " . $e->getMessage());
        }
    }
}
