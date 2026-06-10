<?php

namespace App\Jobs;

use App\Events\MatriculaMasivaProgreso;
use App\Events\MatriculaMasivaFinalizada;
use App\Models\Matricula;
use App\Models\Personal;
use App\Models\Cursos;
use App\Models\CursoProgramacion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MatriculaMasivaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const MODO_ESTANDAR       = 'estandar';
    private const MODO_POR_TIPO_CURSO = 'por_tipo_curso';

    protected string $modo;
    protected int    $cursoCodigo;
    protected string $programacionCodigo;
    protected array  $personalIds;
    protected int    $usuarioId;

    private function __construct(
        string $modo,
        int    $cursoCodigo,
        string $programacionCodigo,
        int    $usuarioId,
        array  $personalIds = []
    ) {
        $this->modo               = $modo;
        $this->cursoCodigo        = $cursoCodigo;
        $this->programacionCodigo = $programacionCodigo;
        $this->usuarioId          = $usuarioId;
        $this->personalIds        = $personalIds;
    }

    public static function estandar(
        int    $cursoCodigo,
        string $programacionCodigo,
        array  $personalIds,
        int    $usuarioId
    ): static {
        return new static(
            self::MODO_ESTANDAR,
            $cursoCodigo,
            $programacionCodigo,
            $usuarioId,
            $personalIds
        );
    }

    public static function porTipoCurso(
        int    $cursoCodigo,
        string $programacionCodigo,
        int    $usuarioId
    ): static {
        return new static(
            self::MODO_POR_TIPO_CURSO,
            $cursoCodigo,
            $programacionCodigo,
            $usuarioId
        );
    }

    public function handle(): void
    {
        $curso = Cursos::find($this->cursoCodigo);
        $prog  = CursoProgramacion::where('codigo', $this->programacionCodigo)
            ->orWhere('codigo_programacion', $this->programacionCodigo)
            ->first();

        if (!$curso || !$prog) {
            Log::error("MatriculaMasivaJob: no se encontró el curso o la programación.", [
                'curso_id'        => $this->cursoCodigo,
                'programacion_id' => $this->programacionCodigo,
                'modo'            => $this->modo,
            ]);
            return;
        }

        $jobId = $this->cursoCodigo . '_' . $this->programacionCodigo;

        $resumen = match ($this->modo) {
            self::MODO_ESTANDAR =>
            $this->ejecutarEstandar($curso, $prog),

            self::MODO_POR_TIPO_CURSO =>
            $this->ejecutarPorTipoCurso($curso, $prog),
        };

        event(new MatriculaMasivaFinalizada(
            usuarioId: $this->usuarioId,
            jobId: $jobId,
            curso: $curso->nombre,
            total: $resumen['total'],
            enviados: $resumen['enviados'],
            fallidos: $resumen['fallidos'],
        ));
    }

    private function ejecutarEstandar(Cursos $curso, CursoProgramacion $prog): array
    {
        if (empty($this->personalIds)) {
            Log::warning("MatriculaMasivaJob [estandar]: lista de personal vacía.", [
                'curso_id' => $this->cursoCodigo,
            ]);
            return [
                'total' => 0,
                'enviados' => 0,
                'fallidos' => 0,
            ];
        }

        return $this->procesarLote(
            $this->personalIds,
            $curso,
            $prog
        );
    }

    private function ejecutarPorTipoCurso(Cursos $curso, CursoProgramacion $prog): array
    {
        $tipoCurso = strtoupper(trim($curso->tipoCurso?->descripcion ?? ''));

        $personalIds = match ($tipoCurso) {
            'PCA'   => $this->resolverPersonalPCA($curso),
            'PCE'   => $this->resolverPersonalPCE($curso),
            default => $this->resolverPersonalPorDefecto($curso),
        };

        if (empty($personalIds)) {
            Log::warning("MatriculaMasivaJob [por_tipo_curso]: no se obtuvo personal para tipo '{$tipoCurso}'.", [
                'curso_id' => $this->cursoCodigo,
            ]);
            return [
                'total' => 0,
                'enviados' => 0,
                'fallidos' => 0,
            ];
        }

        return $this->procesarLote(
            $personalIds,
            $curso,
            $prog
        );
    }

    private function resolverPersonalPCA(Cursos $curso): array
    {
        $codCliente = trim((string) $curso->cod_cliente);

        if (empty($codCliente)) {
            Log::error("MatriculaMasivaJob PCA: el curso {$this->cursoCodigo} no tiene cod_cliente asignado.");
            return [];
        }

        if (trim((string) $curso->dirigido_a) === '0') {
            Log::info("MatriculaMasivaJob PCA: dirigido_a es OTROS, se omite auto-matriculación (curso {$this->cursoCodigo}).");
            return [];
        }

        try {
            $rows = DB::select(
                "EXEC [dbo].[SP_OBTENER_PERSONAL_ACTIVO_X_CLIENTE] @CodCliente = ?",
                [$codCliente]
            );

            $ids = array_map(fn($row) => $row->codigo, $rows);

            $codResponsable = str_pad(trim((string) $curso->cod_responsable), 5, '0', STR_PAD_LEFT);
            if (!empty(trim((string) $curso->cod_responsable))) {
                $ids = array_values(array_filter($ids, fn($id) => $id !== $codResponsable));
            }

            Log::info("MatriculaMasivaJob PCA: {$codCliente} retornó " . count($ids) . " personas (curso {$this->cursoCodigo}).");

            return $ids;
        } catch (\Exception $e) {
            Log::error("MatriculaMasivaJob PCA: error ejecutando SP. " . $e->getMessage(), [
                'cod_cliente' => $codCliente,
                'curso_id'    => $this->cursoCodigo,
            ]);
            return [];
        }
    }

    private function resolverPersonalPCE(Cursos $curso): array
    {
        $dirigido = trim((string) $curso->dirigido_a);

        if (empty($dirigido)) {
            Log::warning("MatriculaMasivaJob PCE: el curso {$this->cursoCodigo} no tiene dirigido_a.", [
                'curso_id' => $this->cursoCodigo,
            ]);
            return [];
        }

        $dirigidoToTipos = [
            '1' => [null],          // todos -> llama al SP sin filtrar
            '2' => ['02', '05'],    // administrativo
            '3' => ['01', '03'],    // operativo
        ];

        $tipos = $dirigidoToTipos[$dirigido] ?? null;

        if ($tipos === null) {
            Log::warning("MatriculaMasivaJob PCE: dirigido_a '{$dirigido}' no reconocido.", [
                'curso_id' => $this->cursoCodigo,
            ]);
            return [];
        }

        try {
            $allIds = [];

            foreach ($tipos as $tipoTrab) {
                $rows = DB::select(
                    "EXEC [dbo].[SP_OBTENER_PERSONAL_ACTIVO_SOLMAR] @TIPOTRAB = ?",
                    [$tipoTrab]
                );

                foreach ($rows as $row) {
                    $allIds[] = $row->codigo;
                }
            }

            $allIds = array_unique(array_values($allIds));

            $codResponsable = str_pad(trim((string) $curso->cod_responsable), 5, '0', STR_PAD_LEFT);
            if (!empty(trim((string) $curso->cod_responsable))) {
                $allIds = array_values(array_filter($allIds, fn($id) => $id !== $codResponsable));
            }

            Log::info("MatriculaMasivaJob PCE: dirigido_a={$dirigido} retornó " . count($allIds) . " personas (curso {$this->cursoCodigo}).");

            return $allIds;
        } catch (\Exception $e) {
            Log::error("MatriculaMasivaJob PCE: error ejecutando SP. " . $e->getMessage(), [
                'dirigido_a' => $dirigido,
                'curso_id'   => $this->cursoCodigo,
            ]);
            return [];
        }
    }

    private function resolverPersonalPorDefecto(Cursos $curso): array
    {
        Log::warning("MatriculaMasivaJob: tipo_curso '{$curso->tipo_curso}' no tiene resolver implementado.", [
            'curso_id' => $this->cursoCodigo,
        ]);
        return [];
    }

    private function procesarLote(array $personalIds, Cursos $curso, CursoProgramacion $prog): array
    {
        $enviados         = 0;
        $fallidos         = 0;
        $procesados       = 0;
        $totalPersonas    = count($personalIds);
        $matriculados     = [];

        $jobId            = $this->cursoCodigo . '_' . $this->programacionCodigo;
        $intervalo        = max(1, (int) round($totalPersonas * 0.01));
        $ultimoPorcentaje = -1;

        try {
            foreach (array_chunk($personalIds, 100) as $chunk) {
                foreach ($chunk as $codPersonal) {
                    $codPersonal = str_pad(trim((string) $codPersonal), 5, '0', STR_PAD_LEFT);
                    if (empty($codPersonal) || $codPersonal === '00000') continue;

                    $antes = $enviados;
                    $this->procesarPersona($codPersonal, $curso, $prog, $enviados, $fallidos);
                    $procesados++;

                    if ($enviados > $antes) {
                        $matriculados[] = $codPersonal;
                    }

                    $debeEmitir = $procesados <= 5
                        || $procesados % $intervalo === 0
                        || $procesados === $totalPersonas;

                    if ($debeEmitir) {
                        $porcentaje = round(($procesados / $totalPersonas) * 100);

                        if ($porcentaje !== $ultimoPorcentaje) {
                            $ultimoPorcentaje = $porcentaje;

                            event(new MatriculaMasivaProgreso(
                                usuarioId: $this->usuarioId,
                                jobId: $jobId,
                                curso: $curso->nombre,
                                procesados: $procesados,
                                total: $totalPersonas,
                                porcentaje: $porcentaje,
                            ));
                        }
                    }
                }
            }

            // foreach (array_chunk($matriculados, 50) as $chunkCorreos) {
            //     EnviarCorreosBienvenidaJob::dispatch(
            //         personalIds: $chunkCorreos,
            //         nombreCurso: $curso->nombre,
            //         fechaInicio: Carbon::parse($prog->fecha_inicio)->format('d/m/Y'),
            //         fechaFin: Carbon::parse($prog->fecha_fin)->format('d/m/Y'),
            //     )->onQueue('emails');
            // }

            Log::info("MatriculaMasivaJob [{$this->modo}] finalizado. Éxitos: {$enviados}, Fallos: {$fallidos}", [
                'curso_id' => $this->cursoCodigo,
            ]);

            return [
                'total'    => $totalPersonas,
                'enviados' => $enviados,
                'fallidos' => $fallidos,
            ];
        } catch (\Exception $e) {
            Log::error("MatriculaMasivaJob [{$this->modo}] error crítico: " . $e->getMessage(), [
                'curso_id' => $this->cursoCodigo,
                'trace'    => $e->getTraceAsString(),
            ]);

            return [
                'total'    => $totalPersonas,
                'enviados' => $enviados,
                'fallidos' => $fallidos,
            ];
        }
    }

    private function procesarPersona(
        string           $codPersonal,
        Cursos           $curso,
        CursoProgramacion $prog,
        int              &$enviados,
        int              &$fallidos
    ): void {
        try {
            $codResponsable = str_pad(trim((string) $curso->cod_responsable), 5, '0', STR_PAD_LEFT);
            if (!empty(trim((string) $curso->cod_responsable)) && $codPersonal === $codResponsable) {
                $this->asignarResponsableEnMoodle($curso, $prog);
                $enviados++;
                return;
            }

            $existe = Matricula::where('cod_curso', $this->cursoCodigo)
                ->where('cod_programacion', $prog->codigo_programacion)
                ->where('cod_personal', $codPersonal)
                ->exists();

            if (!$existe) {
                Matricula::create([
                    'cod_curso'        => $this->cursoCodigo,
                    'cod_programacion' => $prog->codigo_programacion,
                    'cod_personal'     => $codPersonal,
                    'usuario_id'       => $this->usuarioId,
                    'fecha_matricula'  => DB::raw("CONVERT(datetime, '" . now()->format('Y-m-d H:i:s') . "', 120)"),
                    'estado'           => Matricula::ESTADO_MATRICULADO,
                    'tipo_matricula'   => 'MASIVA',
                    'origen_matricula' => 'SISTEMA',
                ]);
            }

            $personal = Personal::where('CODI_PERS', $codPersonal)->first();

            if (!$personal) {
                Log::warning("MatriculaMasivaJob: no se encontró Personal para ID: {$codPersonal}");
                $fallidos++;
                return;
            }

            $dni = trim($personal->NRO_DOCU_IDEN);

            if (empty($dni)) {
                Log::warning("MatriculaMasivaJob: Personal {$codPersonal} no tiene DNI registrado.");
                $fallidos++;
                return;
            }

            $moodleUserId = $this->resolverUsuarioMoodle($personal, $dni);

            if ($moodleUserId <= 0) {
                Log::error("MatriculaMasivaJob: error al crear/obtener usuario Moodle para DNI {$dni}. Code: {$moodleUserId}");
                $fallidos++;
                return;
            }

            $this->matricularEnMoodle($moodleUserId, $curso, $prog);
            $enviados++;
        } catch (\Exception $e) {
            $fallidos++;
            Log::error("MatriculaMasivaJob: error procesando persona {$codPersonal}: " . $e->getMessage());
        }
    }

    private function resolverUsuarioMoodle(Personal $personal, string $dni): int
    {
        $moodleUser = DB::connection('mysql_grupoihb')->table('mdl_user')
            ->where('username', $dni)
            ->orWhere('idnumber', $dni)
            ->first();

        if ($moodleUser) {
            return $moodleUser->id;
        }

        $firstname = trim(($personal->NOMB_1 ?? '') . ' ' . ($personal->NOMB_2 ?? ''));
        $lastname  = trim(($personal->APEL_1 ?? '') . ' ' . ($personal->APEL_2 ?? ''));
        $email     = !empty(trim($personal->PERS_EMAIL ?? '')) && filter_var(trim($personal->PERS_EMAIL), FILTER_VALIDATE_EMAIL)
            ? trim($personal->PERS_EMAIL)
            : "{$dni}@sisolmar.com";

        $res = DB::connection('mysql_grupoihb')->select(
            "SELECT F_USER_crear(?, ?, ?, ?, ?, ?, ?, ?, ?, ?) AS user_id",
            [$dni, 'Gpo$olSEE_1@', $firstname, $lastname, $email, '', '', '', '', '']
        );

        return $res[0]->user_id;
    }

    private function matricularEnMoodle(int $moodleUserId, Cursos $curso, CursoProgramacion $prog): void
    {
        $moodleCourseRef = $curso->codigo_moodle ?: $curso->codigo_curso;

        $courseMoodle = DB::connection('mysql_grupoihb')->table('mdl_course')
            ->where('id', $moodleCourseRef)
            ->orWhere('idnumber', $moodleCourseRef)
            ->first(['id', 'idnumber', 'category']);

        $moodleCourseIdNumber = (string) ($courseMoodle ? $courseMoodle->idnumber : $moodleCourseRef);

        if ($courseMoodle) {
            $this->garantizarContextoMoodle($courseMoodle);
        }

        $fInic = Carbon::parse($prog->fecha_inicio)->format('Y-m-d H:i:s');
        $fFin  = Carbon::parse($prog->fecha_final)->format('Y-m-d H:i:s');

        DB::connection('mysql_grupoihb')->select(
            "SELECT F_Matricular_R(?, ?, ?, ?, ?) AS result",
            [$moodleUserId, $moodleCourseIdNumber, $fInic, $fFin, 5]
        );
    }

    private function garantizarContextoMoodle(object $courseMoodle): void
    {
        $context = DB::connection('mysql_grupoihb')->table('mdl_context')
            ->where('contextlevel', 50)
            ->where('instanceid', $courseMoodle->id)
            ->first();

        if ($context) return;

        $categoryContext = DB::connection('mysql_grupoihb')->table('mdl_context')
            ->where('contextlevel', 40)
            ->where('instanceid', $courseMoodle->category)
            ->first();

        $contextId = DB::connection('mysql_grupoihb')->table('mdl_context')->insertGetId([
            'contextlevel' => 50,
            'instanceid'   => $courseMoodle->id,
            'path'         => '',
            'depth'        => 0,
            'locked'       => 0,
        ]);

        $path  = ($categoryContext ? $categoryContext->path : '/1') . '/' . $contextId;
        $depth = ($categoryContext ? $categoryContext->depth : 1) + 1;

        DB::connection('mysql_grupoihb')->table('mdl_context')
            ->where('id', $contextId)
            ->update(['path' => $path, 'depth' => $depth]);

        Log::info("MatriculaMasivaJob: contexto Moodle generado para curso ID: {$courseMoodle->id}");
    }

    private function asignarResponsableEnMoodle(Cursos $curso, CursoProgramacion $prog): void
    {
        $codResponsable = str_pad(trim((string) $curso->cod_responsable), 5, '0', STR_PAD_LEFT);

        if (empty(trim((string) $curso->cod_responsable)) || $codResponsable === '00000') {
            return;
        }

        $personal = Personal::where('CODI_PERS', $codResponsable)->first();

        if (!$personal) {
            Log::warning("MatriculaMasivaJob: no se encontró Personal para responsable {$codResponsable}.");
            return;
        }

        $dni = trim($personal->NRO_DOCU_IDEN);

        if (empty($dni)) {
            Log::warning("MatriculaMasivaJob: responsable {$codResponsable} no tiene DNI registrado.");
            return;
        }

        $moodleUserId = $this->resolverUsuarioMoodle($personal, $dni);

        if ($moodleUserId <= 0) {
            Log::error("MatriculaMasivaJob: error al crear/obtener usuario Moodle para responsable {$codResponsable}.");
            return;
        }

        $moodleCourseRef = $curso->codigo_moodle ?: $curso->codigo_curso;

        $courseMoodle = DB::connection('mysql_grupoihb')->table('mdl_course')
            ->where('id', $moodleCourseRef)
            ->orWhere('idnumber', $moodleCourseRef)
            ->first(['id', 'idnumber', 'category']);

        if (!$courseMoodle) {
            Log::warning("MatriculaMasivaJob: no se encontró curso Moodle ({$moodleCourseRef}) para asignar responsable.");
            return;
        }

        $this->garantizarContextoMoodle($courseMoodle);

        $context = DB::connection('mysql_grupoihb')->table('mdl_context')
            ->where('contextlevel', 50)
            ->where('instanceid', $courseMoodle->id)
            ->first();

        if (!$context) {
            Log::error("MatriculaMasivaJob: no hay contexto Moodle para curso ID: {$courseMoodle->id}.");
            return;
        }

        DB::connection('mysql_grupoihb')->table('mdl_role_assignments')
            ->where('roleid', 5)
            ->where('contextid', $context->id)
            ->where('userid', $moodleUserId)
            ->delete();

        $existeRol = DB::connection('mysql_grupoihb')->table('mdl_role_assignments')
            ->where('roleid', 3)
            ->where('contextid', $context->id)
            ->where('userid', $moodleUserId)
            ->exists();

        if (!$existeRol) {
            DB::connection('mysql_grupoihb')->table('mdl_role_assignments')->insert([
                'roleid'       => 3,
                'contextid'    => $context->id,
                'userid'       => $moodleUserId,
                'timemodified' => now()->timestamp,
                'modifierid'   => 0,
                'component'    => '',
                'itemid'       => 0,
                'sortorder'    => 0,
            ]);

            Log::info("MatriculaMasivaJob: responsable {$codResponsable} asignado como editingteacher en Moodle (course id: {$courseMoodle->id}).");
        }
    }
}
