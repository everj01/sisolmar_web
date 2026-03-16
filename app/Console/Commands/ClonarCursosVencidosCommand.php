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
        $this->info("Iniciando revisión de cursos periódicos para clonación...");
        Log::info("CRON capacitacion:clonar-vencidos iniciado");

        $hoy = \Carbon\Carbon::now()->startOfDay();
        $this->info("Fecha actual de referencia (hoy): " . $hoy->format('Y-m-d'));

        // 1. Obtener todas las programaciones vigentes VIGENTE de cursos que son periódicos usando JOIN para mayor precisión
        $programacionesVigentes = DB::table('sw_cursos_programacion as cp')
            ->join('sw_cursos as c', 'c.codigo', '=', 'cp.cod_cursos')
            ->select('cp.*', 'c.codigo_curso', 'c.codigo as id_curso', 'c.nombre as curso_nombre', 'c.frecuencia', 'c.es_periodico', 'c.tipo_curso')
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
                    // Cerrar el periodo anterior usando Eloquent para disparar eventos
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

                    // ✅ FIX: La nueva programación siempre dura el mes calendario completo.
                    // Usamos fechaProximaClonacion (no $hoy) para el mes correcto,
                    // así aunque el cron corra tarde (ej. día 3) el período siempre empieza el día 1.
                    $nuevaFechaInicio = $fechaProximaClonacion->copy()->startOfMonth()->startOfDay();
                    $nuevaFechaFinal  = $fechaProximaClonacion->copy()->endOfMonth()->endOfDay();
                    $nuevoPeriodo     = $nuevaFechaInicio->format('Y-m');

                    // Crear la nueva programación VIGENTE
                    $nuevaProgramacion = CursoProgramacion::create([
                        'codigo_programacion' => $newProgCod,
                        'cod_cursos'    => $programacion->id_curso,
                        'periodo'       => $nuevoPeriodo,
                        'tipo'          => 'REGULAR',
                        'estado_periodo'=> 'VIGENTE',
                        'fecha_inicio'  => $nuevaFechaInicio->format('Y-m-d\TH:i:s.000'),
                        'fecha_final'   => $nuevaFechaFinal->format('Y-m-d\TH:i:s.000'),
                        'fecha_creacion'=> now()->format('Y-m-d\TH:i:s.000'),
                        'habilitado'    => 1,
                    ]);

                    // 4. Lógica de Matriculación: Extraer sucursales asignadas
                    $sucursalesAsignadas = DB::table('sw_curso_sucursales')
                        ->where('curso_codigo', $programacion->id_curso)
                        ->pluck('sucursal')
                        ->toArray();

                    $personalQuery = Personal::where('ESTA_ACTI', 1);

                    if (!empty($sucursalesAsignadas)) {
                        if ($programacion->tipo_curso == 7) { // PCI: Filtrar por ÁREA
                            // Normalizar a 2 dígitos con relleno de ceros (ej: '1' -> '01')
                            $areasNormalizadas = array_map(function($a) {
                                return str_pad($a, 2, '0', STR_PAD_LEFT);
                            }, $sucursalesAsignadas);
                            $personalQuery->whereIn('CODI_AREA', $areasNormalizadas);
                        } else if ($programacion->tipo_curso == 6) { // PCU: Filtrar por CLIENTE (Cross-DB mapping)
                            // En PCU, $sucursalesAsignadas contiene ID de sw_clientes.codigo
                            $legacyCodes = DB::table('sw_clientes')
                                ->whereIn('codigo', $sucursalesAsignadas)
                                ->pluck('cod_legacy')
                                ->filter()
                                ->toArray();

                            $sucursalesFinales = [];
                            foreach ($legacyCodes as $legacy) {
                                $resSucursales = DB::connection('sqlsrv_controlclientes')
                                    ->select('EXEC USP_LISTAR_SUCURSALES_X_CLIENTE :cod_legacy', ['cod_legacy' => $legacy]);
                                
                                foreach ($resSucursales as $rs) {
                                    if (isset($rs->codigo_sucursal)) {
                                        $sucursalesFinales[] = $rs->codigo_sucursal;
                                    }
                                }
                            }
                            
                            if (!empty($sucursalesFinales)) {
                                $personalQuery->whereIn('SUCU_CODIGO', array_unique($sucursalesFinales));
                            } else {
                                $personalQuery->whereRaw('1 = 0');
                            }
                        } else if ($programacion->tipo_curso == 5) { // PCE / Plan Estándar
                            // PCE es GLOBAL: No filtramos por sucursal
                        } else { // Otros (PAC / etc): Filtrar por SUCURSAL DIRECTA
                            $personalQuery->whereIn('SUCU_CODIGO', $sucursalesAsignadas);
                        }
                    }

                    $personalActivo = $personalQuery->get();
                    $insertData = [];
                    $fechaMatricula = now()->format('Y-m-d\TH:i:s.000');

                    foreach ($personalActivo as $persona) {
                        $insertData[] = [
                            'cod_curso' => $programacion->id_curso,
                            'cod_programacion' => $newProgCod,
                            'cod_personal' => $persona->CODI_PERS,
                            'usuario_id' => 0,
                            'fecha_matricula' => $fechaMatricula,
                            'estado' => Matricula::ESTADO_MATRICULADO ?? 'MATRICULADO',
                            'tipo_matricula' => 'AUTOMATICA',
                            'origen_matricula' => 'CRON'
                        ];
                    }

                    $chunks = array_chunk($insertData, 200);
                    foreach ($chunks as $chunk) {
                        DB::table('sw_matriculas')->insert($chunk);
                    }
                    
                    DB::commit();
                    $clonados++;
                    $this->info("      -> Creada Prog: {$newProgCod}. Matriculados: " . count($insertData));
                    Log::info("Curso {$programacion->codigo_curso} clonado a {$newProgCod}. Alumnos: " . count($insertData));

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
