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
        $this->info('Iniciando proceso de inducción automática...');

        // 1. Obtener cursos marcados como Obligatorio al Alta
        $cursosObligatorios = Cursos::where('obligatorio_alta', 1)
            ->where('habilitado', 1)
            ->get();

        if ($cursosObligatorios->isEmpty()) {
            $this->warn('No hay cursos marcados como "Obligatorio al Alta". Finalizando.');
            return;
        }

        $this->info('Cursos identificados: ' . $cursosObligatorios->pluck('nombre')->implode(', '));

        // 2. Buscar personal registrado en las últimas 24 horas (o que no ha sido procesado)
        // Usamos USUA_FECHA_REG para detectar inserciones recientes.
        // También podríamos usar FECH_INGRE (Fecha de ingreso a planilla).
        $hace24Horas = Carbon::now()->subDay();
        
        $nuevosTrabajadores = Personal::where('ESTA_ACTI', 1)
            ->where(function($query) use ($hace24Horas) {
                $query->where('USUA_FECHA_REG', '>=', $hace24Horas->format('Y-m-d H:i:s'))
                      ->orWhere('FECH_INGRE', '>=', $hace24Horas->format('Y-m-d'));
            })
            ->get();

        if ($nuevosTrabajadores->isEmpty()) {
            $this->info('No se detectaron nuevos trabajadores en las últimas 24 horas.');
            return;
        }

        $this->info('Trabajadores nuevos detectados: ' . $nuevosTrabajadores->count());

        $matriculasRealizadas = 0;

        foreach ($nuevosTrabajadores as $trabajador) {
            foreach ($cursosObligatorios as $curso) {
                
                // 3. Verificar si el trabajador ya está matriculado en este curso (en cualquier programa)
                $yaMatriculado = Matricula::where('cod_personal', $trabajador->CODI_PERS)
                    ->where('cod_curso', $curso->codigo)
                    ->exists();

                if ($yaMatriculado) {
                    continue;
                }

                // 4. Buscar la programación VIGENTE más reciente para este curso
                $programacionVigente = CursoProgramacion::where('cod_cursos', $curso->codigo)
                    ->where('estado_periodo', 'VIGENTE')
                    ->where('habilitado', 1)
                    ->orderBy('fecha_inicio', 'desc')
                    ->first();

                if (!$programacionVigente) {
                    $this->error("No hay una programación VIGENTE para el curso: {$curso->nombre}. Saltando matriculación de {$trabajador->CODI_PERS}");
                    continue;
                }

                // 5. Realizar la matrícula
                try {
                    Matricula::create([
                        'cod_programacion' => $programacionVigente->codigo_programacion,
                        'cod_curso'        => $curso->codigo,
                        'cod_personal'     => $trabajador->CODI_PERS,
                        'fecha_matricula'  => Carbon::now()->format('Y-m-d\TH:i:s'),
                        'estado'           => 'MATRICULADO',
                        'usuario_id'       => 0, // 0 = Sistema Automático
                        'origen_matricula' => 'AUTO_ALTA',
                        'habilitado'       => 1
                    ]);
                    
                    $this->info("Trabajador {$trabajador->CODI_PERS} matriculado en {$curso->nombre}");
                    $matriculasRealizadas++;
                } catch (\Exception $e) {
                    Log::error("Error en Inducción Automática: " . $e->getMessage());
                    $this->error("Error al matricular trabajador {$trabajador->CODI_PERS} en {$curso->nombre}");
                }
            }
        }

        $this->info("Proceso completado. Total de matrículas automáticas realizadas: {$matriculasRealizadas}");
    }
}
