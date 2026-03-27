<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cursos;
use App\Models\CursoProgramacion;
use App\Models\Matricula;
use App\Models\Personal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ClonarCursosVencidosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'capacitacion:clonar-vencidos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clona los cursos periódicos cuyo tiempo de validez ha expirado, abriendo la ventana de ejecución de 1 mes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando revisión de cursos periódicos (Pauta 5)...");

        $hoy = Carbon::now()->startOfDay();

        // Pauta DB: Usar Query Builder y conexión explícita
        $programaciones = DB::connection('sqlsrv')->table('sw_cursos_programacion as cp')
            ->join('sw_cursos as c', 'c.codigo', '=', 'cp.cod_cursos')
            ->select('cp.*', 'c.codigo as id_curso', 'c.nombre as curso_nombre', 'c.frecuencia', 'c.tipo_curso')
            ->where('cp.habilitado', 1)
            ->where('cp.estado_periodo', 'VIGENTE')
            ->where('c.habilitado', 1)
            ->where('c.es_periodico', 1)
            ->get();

        foreach ($programaciones as $p) {
            if (empty($p->frecuencia)) continue;

            $prox = Carbon::parse($p->fecha_inicio)->startOfDay();
            $f = trim(strtoupper($p->frecuencia));

            switch ($f) {
                case 'MENSUAL': $prox->addMonth(); break;
                case 'BIMESTRAL': $prox->addMonths(2); break;
                case 'TRIMESTRAL': $prox->addMonths(3); break;
                case 'CUATRIMESTRAL': $prox->addMonths(4); break;
                case 'SEMESTRAL': $prox->addMonths(6); break;
                case 'ANUAL': $prox->addYear(); break;
                default: continue 2;
            }

            // Si la fecha de la "próxima versión" ya llegó o pasó, clonamos
            if ($prox->lessThanOrEqualTo($hoy)) {
                $this->clonar($p, $prox);
            }
        }

        $this->info("Proceso de clonación finalizado.");
    }

    private function clonar($p, $prox)
    {
        DB::connection('sqlsrv')->beginTransaction();
        try {
            // 1. Cerrar programación anterior
            DB::connection('sqlsrv')->table('sw_cursos_programacion')
                ->where('codigo_programacion', $p->codigo_programacion)
                ->update([
                    'estado_periodo' => 'CERRADO', 
                    'fecha_modificacion' => DB::raw('GETDATE()')
                ]);

            // 2. Generar nueva programación
            $last = DB::connection('sqlsrv')->table('sw_cursos_programacion')
                ->orderBy('codigo_programacion', 'desc')
                ->first();
            
            $newCode = $last ? str_pad(intval($last->codigo_programacion) + 1, 4, '0', STR_PAD_LEFT) : '1001';

            DB::connection('sqlsrv')->table('sw_cursos_programacion')->insert([
                'codigo_programacion' => $newCode,
                'cod_cursos' => $p->id_curso,
                'periodo' => $prox->format('Y-m'),
                'tipo' => 'REGULAR',
                'estado_periodo' => 'VIGENTE',
                'fecha_inicio' => $prox->copy()->startOfMonth()->format('Y-m-d H:i:s'),
                'fecha_final' => $prox->copy()->endOfMonth()->format('Y-m-d H:i:s'),
                'fecha_creacion' => DB::raw('GETDATE()'),
                'habilitado' => 1
            ]);

            // 3. Matricular personal (Lógica por tipo de curso)
            $personalIds = [];
            $asignados = DB::connection('sqlsrv')->table('sw_curso_sucursales')
                ->where('curso_codigo', $p->id_curso)
                ->pluck('sucursal')
                ->toArray();

            if ($p->tipo_curso == 7) { // PCI (Por Cargo/Área)
                $personalIds = DB::connection('sqlsrv')->table('si_solm.dbo.PERSONAL')
                    ->where('ESTA_ACTI', 1)
                    ->whereIn('CODI_CARG', $asignados)
                    ->pluck('CODI_PERS')
                    ->map(fn($id) => trim($id))
                    ->toArray();
            } else if ($p->tipo_curso == 6) { // PCU (Por Cliente)
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
                    } catch (\Exception $e) {}
                }
                
                if (!empty($sucuCodigos)) {
                    $personalIds = DB::connection('sqlsrv')->table('si_solm.dbo.PERSONAL')
                        ->where('ESTA_ACTI', 1)
                        ->whereIn('SUCU_CODIGO', array_unique($sucuCodigos))
                        ->pluck('CODI_PERS')
                        ->map(fn($id) => trim($id))
                        ->toArray();
                }
            } else if ($p->tipo_curso == 5) { // PCE (General)
                // Se asume que PCE es para todo el personal activo si no hay filtros específicos
                $personalIds = DB::connection('sqlsrv')->table('si_solm.dbo.PERSONAL')
                    ->where('ESTA_ACTI', 1)
                    ->pluck('CODI_PERS')
                    ->map(fn($id) => trim($id))
                    ->toArray();
            }

            // Insertar matrículas masivamente (Query Builder)
            foreach ($personalIds as $codPers) {
                DB::connection('sqlsrv')->table('sw_matriculas')->insert([
                    'cod_curso' => $p->id_curso,
                    'cod_programacion' => $newCode,
                    'cod_personal' => $codPers,
                    'usuario_id' => 0,
                    'fecha_matricula' => DB::raw('GETDATE()'),
                    'estado' => 'MATRICULADO',
                    'origen_matricula' => 'AUTO_CLONACION',
                    'habilitado' => 1,
                    'fecha_creacion' => DB::raw('GETDATE()')
                ]);
            }

            DB::connection('sqlsrv')->commit();
            $this->info("   -> Clonado {$p->curso_nombre} a {$newCode} con " . count($personalIds) . " matriculados.");
        } catch (\Exception $e) {
            DB::connection('sqlsrv')->rollBack();
            Log::error("Error Clonación (C:{$p->id_curso}, CP:{$p->codigo_programacion}): " . $e->getMessage());
        }
    }
}
