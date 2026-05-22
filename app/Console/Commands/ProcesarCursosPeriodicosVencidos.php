<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cursos;
use App\Models\CursoProgramacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcesarCursosPeriodicosVencidos extends Command
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
                        'fecha_modificacion' => DB::raw("CONVERT(datetime, '" . now()->format('Y-m-d H:i:s') . "', 120)"),
                    ]);

                    DB::table('sw_matriculas')
                        ->where('cod_programacion', $programacion->codigo_programacion)
                        ->whereIn('estado', ['MATRICULADO', 'PENDIENTE'])
                        ->update([
                            'estado'     => 'FINALIZADO',
                            'updated_at' => DB::raw("CONVERT(datetime, '" . now()->format('Y-m-d H:i:s') . "', 120)"),
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

                    'fecha_inicio'        => DB::raw("CONVERT(datetime, '" . $fechaProximaClonacion->copy()->startOfDay()->format('Y-m-d H:i:s') . "', 120)"),
                    'fecha_final'         => DB::raw("CONVERT(datetime, '" . $fechaProximaClonacion->copy()->addMonth()->subSecond()->format('Y-m-d H:i:s') . "', 120)"),
                    'fecha_creacion'      => DB::raw("CONVERT(datetime, '" . now()->format('Y-m-d H:i:s') . "', 120)"),
                    'habilitado'          => 1,
                ]);

                DB::commit();

                $clonados++;

                $this->info(
                    "   -> Programación {$newProgCod} creada correctamente."
                );

                $moodleCourseId = $programacion->codigo_moodle ?: null;

                if ($moodleCourseId) {
                    $courseCtxId = DB::connection('mysql_grupoihb')
                        ->table('mdl_context')
                        ->where('contextlevel', 50)
                        ->where('instanceid', $moodleCourseId)
                        ->value('id');

                    DB::connection('mysql_grupoihb')
                        ->table('mdl_user_enrolments as ue')
                        ->join('mdl_enrol as e', 'e.id', '=', 'ue.enrolid')
                        ->leftJoin('mdl_role_assignments as ra', function ($j) use ($courseCtxId) {
                            $j->on('ra.userid', '=', 'ue.userid')
                              ->where('ra.contextid', $courseCtxId)
                              ->whereIn('ra.roleid', [3, 4]);
                        })
                        ->where('e.courseid', $moodleCourseId)
                        ->where('ue.status', 0)
                        ->whereNull('ra.id')
                        ->update([
                            'ue.status'       => 1,
                            'ue.timemodified' => now()->timestamp,
                        ]);

                    $this->info("   -> Matriculados suspendidos en Moodle (profesor intacto).");
                }

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
