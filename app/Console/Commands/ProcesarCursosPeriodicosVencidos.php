<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcesarCursosPeriodicosVencidos extends Command
{
    protected $signature = 'capacitacion:procesar-cursos-periodicos';

    protected $description = 'Procesa cursos periódicos vencidos, cerrando programaciones vigentes y generando nuevas programaciones pendientes.';

    public function handle()
    {
        $cursos = DB::select('EXEC SP_OBTENER_CURSOS_EXPIRADOS');

        foreach ($cursos as $curso) {
            DB::beginTransaction();

            try {
                $moodleCourseId = $curso->codigo_moodle ?: null;

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

                DB::table('sw_matriculas')
                    ->where('cod_programacion', $curso->codigo_programacion)
                    ->where('estado', 'MATRICULADO')
                    ->update([
                        'estado' => 'FINALIZADO'
                    ]);

                $programacionActual = DB::table('sw_cursos_programacion')
                    ->where('codigo_programacion', $curso->codigo_programacion)
                    ->whereIn('estado_periodo', ['VIGENTE', 'PENDIENTE'])
                    ->first();

                DB::table('sw_cursos_programacion')
                    ->where('codigo_programacion', $curso->codigo_programacion)
                    ->update([
                        'estado_periodo' => 'CERRADO',
                        'fecha_modificacion' => now(),
                    ]);

                $dias = Carbon::parse($programacionActual->fecha_inicio)
                    ->diffInDays(Carbon::parse($programacionActual->fecha_final));
                $nuevaFechaInicio = Carbon::parse($programacionActual->fecha_final)->addDay();
                $nuevaFechaFin = Carbon::parse($nuevaFechaInicio)->addDays($dias);
                $lastProg = DB::table('sw_cursos_programacion')
                    ->orderBy('codigo_programacion', 'desc')
                    ->first();
                $newProgCod = $lastProg
                    ? str_pad(((int) $lastProg->codigo_programacion) + 1, 4, '0', STR_PAD_LEFT)
                    : '1000';

                DB::table('sw_cursos_programacion')->insert([
                    'codigo_programacion' => $newProgCod,
                    'cod_curso' => $programacionActual->cod_curso,
                    'estado_periodo' => 'PENDIENTE',
                    'fecha_inicio' => $nuevaFechaInicio,
                    'fecha_final' => $nuevaFechaFin,
                    'fecha_creacion' => now(),
                ]);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error(
                    'Error procesando curso expirado',
                    [
                        'curso' => $curso->nombre,
                        'error' => $e->getMessage(),
                        'line'  => $e->getLine()
                    ]
                );
            }
        }

        return self::SUCCESS;
    }
}
