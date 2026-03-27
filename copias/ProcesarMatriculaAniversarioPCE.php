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
        $this->info('Iniciando proceso de cumplimiento del Punto #6 (Aniversario PCE)...');
        Log::info('ProcesarMatriculaAniversarioPCE: Inicio');

        $mesActual = Carbon::now()->month;
        $anioActual = Carbon::now()->year;
        $periodoActual = Carbon::now()->format('Y-m');

        // 1. Filtrar cursos del Plan de Capacitación Estándar (PCE = Tipo 5)
        // Solo cursos que sean periódicos (es_periodico = 1) para que se repitan anualmente
        $cursosPCE = Cursos::where('tipo_curso', 5)
            ->where('habilitado', 1)
            ->where('es_periodico', 1)
            ->get();

        if ($cursosPCE->isEmpty()) {
            $this->warn('No hay cursos PCE periódicos habilitados para procesar.');
            return;
        }

        // 2. Identificar personal ACTIVO cuyo mes de ingreso (FECH_INGRE) coincida con el mes actual
        // Esto cumple con "en los meses correspondientes que ingresó el personal"
        $personalAniversario = Personal::where('ESTA_ACTI', 1)
            ->whereRaw('MONTH(FECH_INGRE) = ?', [$mesActual])
            ->get();

        if ($personalAniversario->isEmpty()) {
            $this->info("No hay personal en aniversario para el mes {$mesActual}.");
            return;
        }

        $this->info("Detectados " . $personalAniversario->count() . " trabajadores que deben re-capacitarse por aniversario.");

        foreach ($cursosPCE as $curso) {
            // 3. Garantizar que exista una programación vigente para el mes de aniversario
            // Si el curso es trimestral pero alguien cumple años en febrero, creamos el "hueco" para que no espere hasta abril
            $programacion = CursoProgramacion::where('cod_cursos', $curso->codigo)
                ->where('periodo', $periodoActual)
                ->where('estado_periodo', 'VIGENTE')
                ->where('habilitado', 1)
                ->first();

            if (!$programacion) {
                $this->info("Creando ciclo de aniversario para curso: {$curso->nombre}");
                $lastProg = CursoProgramacion::orderBy('codigo_programacion', 'desc')->first();
                $newCode = $lastProg ? str_pad(intval($lastProg->codigo_programacion) + 1, 4, '0', STR_PAD_LEFT) : '1001';

                $programacion = CursoProgramacion::create([
                    'codigo_programacion' => $newCode,
                    'cod_cursos'    => $curso->codigo,
                    'periodo'       => $periodoActual,
                    'tipo'          => 'REGULAR',
                    'estado_periodo'=> 'VIGENTE',
                    'fecha_inicio'  => Carbon::now()->startOfMonth()->format('Y-m-d\TH:i:s.000'),
                    'fecha_final'   => Carbon::now()->endOfMonth()->format('Y-m-d\TH:i:s.000'),
                    'fecha_creacion'=> now(),
                    'habilitado'    => 1,
                ]);
            }

            $matriculasRealizadas = 0;

            foreach ($personalAniversario as $trabajador) {
                // 4. Verificación de seguridad: No matricular dos veces el mismo año calendario
                $yaMatriculado = Matricula::where('cod_personal', $trabajador->CODI_PERS)
                    ->where('cod_curso', $curso->codigo)
                    ->whereYear('fecha_matricula', $anioActual)
                    ->exists();

                if ($yaMatriculado) continue;

                try {
                    Matricula::create([
                        'cod_programacion' => $programacion->codigo_programacion,
                        'cod_curso'        => $curso->codigo,
                        'cod_personal'     => $trabajador->CODI_PERS,
                        'fecha_matricula'  => Carbon::now()->format('Y-m-d\TH:i:s.000'),
                        'estado'           => 'MATRICULADO',
                        'usuario_id'       => 0, // Automático
                        'tipo_matricula'   => 'AUTOMATICA',
                        'origen_matricula' => 'AUTO_ANIVERSARIO',
                        'habilitado'       => 1
                    ]);
                    $matriculasRealizadas++;
                } catch (\Exception $e) {
                    Log::error("Error en Matrícula Aniversario Punto 6: " . $e->getMessage());
                }
            }
            
            $this->info("Curso '{$curso->nombre}': {$matriculasRealizadas} personas matriculadas automáticamente.");
        }

        $this->info('Inducción por aniversario completada.');
        Log::info('ProcesarMatriculaAniversarioPCE: Fin');
    }
}
