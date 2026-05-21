<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cursos;
use App\Models\CursoProgramacion;
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
    protected $description = 'Clona la configuración de cursos periódicos vencidos creando una programación PENDIENTE para revisión del administrador.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando revisión de cursos periódicos para clonación...");
        Log::info("CRON capacitacion:clonar-vencidos iniciado");

        $hoy = \Carbon\Carbon::now()->startOfDay();
        $this->info("Fecha actual de referencia (hoy): " . $hoy->format('Y-m-d'));

        // 1. Obtener todas las programaciones vigentes VIGENTE de cursos que son periódicos usando JOIN para mayor precisión
        $programacionesVigentes = DB::table('sw_cursos_programacion as cp')
            ->join('sw_cursos as c', 'c.codigo', '=', 'cp.cod_cursos')
            ->select('cp.*', 'c.codigo_curso', 'c.codigo as id_curso', 'c.nombre as curso_nombre', 'c.frecuencia', 'c.es_periodico')
            ->where('cp.habilitado', 1)
            ->where('cp.estado_periodo', 'VIGENTE')
            ->where('c.habilitado', 1)
            ->where('c.es_periodico', 1)
            ->get();

        $totalEncontrados = $programacionesVigentes->count();
        $this->info("-> Se encontraron {$totalEncontrados} programaciones VIGENTES de cursos periódicos.");

        $clonados = 0;
        $vencidosCount = 0;

        foreach ($programacionesVigentes as $programacion) {
            if (empty($programacion->frecuencia)) {
                $this->info("   - Curso {$programacion->codigo_curso} ignorado (No tiene frecuencia definida)");
                continue;
            }

            // 2. Calcular fecha de próxima clonación = fecha_inicio + frecuencia
            $fechaInicioProgramacion = Carbon::parse($programacion->fecha_inicio)->startOfDay();
            $fechaProximaClonacion = $fechaInicioProgramacion->copy();

            $frecuencia = trim(strtoupper($programacion->frecuencia));

            switch ($frecuencia) {
                case 'MENSUAL': $fechaProximaClonacion->addMonth(); break;
                case 'BIMESTRAL': $fechaProximaClonacion->addMonths(2); break;
                case 'TRIMESTRAL': $fechaProximaClonacion->addMonths(3); break;
                case 'CUATRIMESTRAL': $fechaProximaClonacion->addMonths(4); break;
                case 'SEMESTRAL': $fechaProximaClonacion->addMonths(6); break;
                case 'ANUAL': $fechaProximaClonacion->addYear(); break;
                default:
                    $this->info("   - Curso {$programacion->codigo_curso} ignorado (Frecuencia desconocida: {$frecuencia})");
                    continue 2;
            }

            // 3. Verificamos si ya toca clonarlo: Que la fecha proxima de clonacion sea <= hoy
            // Esto incluye fechas exactas de hoy o vencidas en el pasado (ej. hace 5 días)
            if ($fechaProximaClonacion->lessThanOrEqualTo($hoy)) {
                $vencidosCount++;
                $this->info("   [VENCIDO DETECTADO] Curso {$programacion->codigo_curso} (Prog: {$programacion->codigo_programacion}). Venció el: " . $fechaProximaClonacion->format('Y-m-d'));
                
                DB::beginTransaction();
                try {
                    // Cerrar el periodo anterior
                    $progModel = CursoProgramacion::where('codigo_programacion', $programacion->codigo_programacion)->first();
                    if ($progModel) {
                        $progModel->update([
                            'estado_periodo' => 'CERRADO',
                            'fecha_modificacion' => now()->format('Y-m-d\TH:i:s.000')
                        ]);
                    }

                    // Generar nuevo codigo de programacion
                    $lastProg = CursoProgramacion::orderBy('codigo_programacion', 'desc')->first();
                    $newProgCod = $lastProg ? str_pad(intval($lastProg->codigo_programacion) + 1, 4, '0', STR_PAD_LEFT) : '1000';

                    // Calcular nuevo período desde la fecha de próxima clonación
                    $nuevoPeriodo = $fechaProximaClonacion->format('Y-m');

                    // Actualizar el nombre del curso con el nuevo período
                    $baseNombre = preg_replace('/\s*\|\s*\d{4}-\d{2}$/', '', $programacion->curso_nombre);
                    $nuevoNombre = $baseNombre . ' | ' . $nuevoPeriodo;
                    Cursos::where('codigo', $programacion->id_curso)
                        ->update(['nombre' => $nuevoNombre]);

                    // Crear la nueva programación PENDIENTE (no se apertura ni matricula automáticamente)
                    $nuevaProgramacion = CursoProgramacion::create([
                        'codigo_programacion' => $newProgCod,
                        'cod_cursos'    => $programacion->id_curso,
                        'periodo'       => $nuevoPeriodo,
                        'tipo'          => 'REGULAR',
                        'estado_periodo'=> 'PENDIENTE',
                        'fecha_inicio'  => $fechaProximaClonacion->copy()->startOfDay()->format('Y-m-d\TH:i:s.000'),
                        'fecha_final'   => $fechaProximaClonacion->copy()->endOfMonth()->endOfDay()->format('Y-m-d\TH:i:s.000'),
                        'fecha_creacion'=> now()->format('Y-m-d\TH:i:s.000'),
                        'habilitado'    => 1,
                    ]);

                    DB::commit();
                    $clonados++;
                    $this->info("      -> Creada Prog PENDIENTE: {$newProgCod}. Curso renombrado a: {$nuevoNombre}");
                    Log::info("Curso {$programacion->codigo_curso} clonado a PENDIENTE {$newProgCod}. Nuevo nombre: {$nuevoNombre}");

                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("      [ERROR] clonando curso {$programacion->id_curso}: " . $e->getMessage());
                    Log::error("Error CRON clonar curso {$programacion->id_curso}", [
                        'msg' => $e->getMessage(),
                        'line' => $e->getLine()
                    ]);
                }
            } else {
                 $this->info("   - Curso {$programacion->codigo_curso} (Aún vigente). Próxima clonación: " . $fechaProximaClonacion->format('Y-m-d'));
            }
        }

        $this->info("Resumen:");
        $this->info("- Total cursos periódicos evaluados: {$totalEncontrados}");
        $this->info("- Total cursos vencidos detectados: {$vencidosCount}");
        $this->info("- Total cursos clonados exitosamente: {$clonados}");
        Log::info("CRON capacitacion:clonar-vencidos finalizado. Evaluados: {$totalEncontrados}, Clonados: {$clonados}");
    }
}
