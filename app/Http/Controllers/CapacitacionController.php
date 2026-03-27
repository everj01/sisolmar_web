<?php

namespace App\Http\Controllers;

use App\Models\CapacitacionAreas;
use App\Models\CapacitacionTipoCurso;
use App\Models\CursoProgramacion;
use App\Models\Cursos;
use App\Models\ExamenCurso;
use App\Models\NotificacionMatricula;
use App\Models\Matricula;
use App\Jobs\DispatchMatriculaBatchJob;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CapacitacionController extends Controller
{
    /**
     * Group 1: Catálogo de Cursos (CRUD)
     */

    public function index(Request $request, $op = null)
    {
        $query = Cursos::query();

        if (!is_null($op)) {
            $query->where('habilitado', $op);
        }

        if ($request->filled('filtro_area')) {
            $query->where('area_conocimiento', $request->input('filtro_area'));
        }

        if ($request->filled('filtro_tipo')) {
            $query->where('tipo_curso', $request->input('filtro_tipo'));
        }

        if ($request->filled('solo_demanda')) {
            $query->porDemanda();
        }

        $cursosVigentes = CursoProgramacion::vigentes()->habilitados()->pluck('cod_cursos')->toArray();

        $cursos = $query->get()->map(function ($curso) use ($cursosVigentes) {
            return [
                'codigo' => $curso->codigo,
                'codigoCurso' => $curso->codigo_curso,
                'nombre' => $curso->nombre,
                'habilitado' => $curso->habilitado,
                'periodicidad' => $curso->periodicidad,
                'es_periodico' => $curso->es_periodico,
                'frecuencia' => $curso->frecuencia,
                'es_demanda' => $curso->es_demanda,
                'tiene_vigente' => in_array($curso->codigo, $cursosVigentes),
            ];
        });

        return response()->json($cursos);
    }

    public function getTipoCursos()
    {
        $tipoCursos = DB::connection('sqlsrv')->table('sw_capacitacion_tipo_curso')->where('habilitado', 1)->get();
        return response()->json($tipoCursos);
    }

    public function getAreas()
    {
        $areas = DB::connection('sqlsrv')->table('sw_capacitacion_areas')->where('habilitado', 1)->get();
        return response()->json($areas);
    }

    public function getSucursales()
    {
        try {
            $sucursales = DB::connection('sqlsrv')->table('sw_MIGRA_SISO_SUCURSAL')
                ->select('SUCU_CODIGO as codigo', 'SUCU_ABREVIATURA as sucursal')
                ->whereNotNull('SUCU_ABREVIATURA')
                ->orderBy('SUCU_ABREVIATURA')
                ->get();
            return response()->json(['success' => true, 'sucursales' => $sucursales]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    public function getAreasEncargadas()
    {
        try {
            $areas = DB::connection('sqlsrv')->select("SELECT AVAR_ID as codigo, AVAR_DESCRIPCION as descripcion FROM si_solm.dbo.AV_AREA WHERE AVAR_VIGENCIA = 1");
            return response()->json($areas);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    public function getEmpresasList()
    {
        try {
            $empresas = DB::connection('sqlsrv')->table('sw_MIGRA_EMPRESA')
                        ->select('EMPR_CODIGO as codigo', 'Razon_Social as descripcion')
                        ->whereIn('EMPR_CODIGO', ['01', '02', '03', '04', '05', '06'])
                        ->orderBy('EMPR_CODIGO')
                        ->get();
            return response()->json($empresas);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    public function getClientesForPAC()
    {
        try {
            // Usamos el SP o query equivalente
            $raw = DB::connection('sqlsrv')->select('EXEC SW_LISTAR_CLIENTES');
            $clientes = collect($raw)->map(function ($row) {
                return [
                    'codigo'      => $row->codigo,
                    'descripcion' => $row->abreviatura ?? $row->razon_social ?? $row->descripcion ?? '',
                ];
            })->sortBy('descripcion')->values();
            return response()->json($clientes);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    public function getCursoExamenXId($id)
    {
        $curso = Cursos::with(['examen', 'tipoCurso'])->where('codigo', $id)->firstOrFail();
        
        $curso->sucursales = DB::connection('sqlsrv')->table('sw_curso_sucursales')
            ->where('curso_codigo', $curso->codigo)
            ->pluck('sucursal');

        if ($curso->cod_responsable) {
            $resp = DB::connection('sqlsrv')->selectOne("
                SELECT LTRIM(RTRIM(APEL_1 + ' ' + ISNULL(APEL_2, '') + ' ' + NOMB_1 + ' ' + ISNULL(NOMB_2, ''))) as nombre
                FROM si_solm.dbo.PERSONAL WHERE CODI_PERS = ?
            ", [$curso->cod_responsable]);
            $curso->nombre_responsable = $resp->nombre ?? 'No encontrado';
        }

        return response()->json(['success' => true, 'curso' => $curso]);
    }

    public function getCursoProgramacionXId($id)
    {
        try {
            $programaciones = DB::connection('sqlsrv')->table('sw_cursos_programacion')
                ->where('cod_cursos', $id)
                ->where('habilitado', 1)
                ->orderBy('fecha_inicio', 'desc')
                ->get();
            return response()->json(['success' => true, 'programaciones' => $programaciones]);
        } catch (\Exception $e) {
            Log::error("getCursoProgramacionXId Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getProgramacionXId($id)
    {
        try {
            $prog = DB::connection('sqlsrv')->table('sw_cursos_programacion')
                ->where('codigo_programacion', $id)
                ->first();
            return response()->json(['success' => true, 'programacion' => $prog]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getMatriculasPorCurso($cursoId)
    {
        // Redirigir al método optimizado con MigraPersonal por consistencia
        return $this->getMatriculasMigraPersonal($cursoId);
    }

    public function saveCurso(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'tipo_curso'=> 'required|integer|exists:sw_capacitacion_tipo_curso,codigo',
            'area_conocimiento'=> 'required|exists:sw_capacitacion_areas,codigo',
            'es_periodico'=> 'required|integer|in:0,1',
            'es_demanda' => 'nullable|integer|in:0,1',
        ]);

        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        DB::beginTransaction();
        try {
            $lastCod = Cursos::orderBy('codigo_curso', 'desc')->first();
            $newCode = $lastCod ? str_pad(intval($lastCod->codigo_curso) + 1, 5, '0', STR_PAD_LEFT) : '10001';

            $cursoId = DB::connection('sqlsrv')->table('sw_cursos')->insertGetId([
                'nombre' => $request->nombre,
                'codigo_curso' => $newCode,
                'tipo_curso' => $request->tipo_curso,
                'area_conocimiento' => $request->area_conocimiento,
                'periodicidad' => $this->calcularPeriodicidad($request->input('frecuencia'), $request->input('es_periodico')),
                'es_periodico' => $request->input('es_periodico', 0),
                'frecuencia' => $request->input('frecuencia'),
                'es_demanda' => $request->input('es_demanda', 0),
                'aplica_evaluacion' => $request->input('aplica_evaluacion', 1),
                'obligatorio_alta' => $request->input('obligatorio_alta', 0),
                'cod_responsable' => $request->input('cod_responsable'),
                'target_group' => $request->input('target_group', 'TODOS'),
                'fecha_creacion' => DB::raw('GETDATE()')
            ], 'codigo');

            $this->saveAsignaciones($cursoId, $request->input('sucursales_asignadas', []));
            $this->saveExamen($cursoId, $request);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Curso registrado', 'codigo' => $cursoId]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('saveCurso: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    public function updateCurso(Request $request)
    {
        DB::beginTransaction();
        try {
            DB::connection('sqlsrv')->table('sw_cursos')->where('codigo', $request->codigo)->update([
                'nombre' => $request->nombre,
                'tipo_curso' => $request->tipo_curso,
                'area_conocimiento' => $request->area_conocimiento,
                'periodicidad' => $this->calcularPeriodicidad($request->input('frecuencia'), $request->input('es_periodico')),
                'es_periodico' => $request->input('es_periodico'),
                'frecuencia' => $request->input('frecuencia'),
                'es_demanda' => $request->input('es_demanda', 0),
                'aplica_evaluacion' => $request->input('aplica_evaluacion', 1),
                'obligatorio_alta' => $request->input('obligatorio_alta', 0),
                'cod_responsable' => $request->input('cod_responsable'),
                'target_group' => $request->input('target_group', 'TODOS'),
                'fecha_modificacion' => DB::raw('GETDATE()')
            ]);

            $this->saveAsignaciones($request->codigo, $request->input('sucursales_asignadas', []));
            $this->saveExamen($request->codigo, $request);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Actualizado']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    public function updateCursoHab($codigo)
    {
        try {
            $curso = DB::connection('sqlsrv')->table('sw_cursos')->where('codigo', $codigo)->first();
            if (!$curso) return response()->json(['success' => false, 'message' => 'Curso no encontrado'], 4404);

            $nuevoEstado = $curso->habilitado == 1 ? 0 : 1;
            
            DB::connection('sqlsrv')->table('sw_cursos')->where('codigo', $codigo)->update([
                'habilitado' => $nuevoEstado,
                'fecha_modificacion' => DB::raw('GETDATE()')
            ]);

            return response()->json([
                'success' => true, 
                'message' => $nuevoEstado == 1 ? 'Curso habilitado correctamente' : 'Curso deshabilitado correctamente',
                'habilitado' => $nuevoEstado
            ]);
        } catch (\Exception $e) {
            Log::error("updateCursoHab Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar el estado del curso'], 500);
        }
    }

    public function destroyCurso($codigo)
    {
        try {
            // Eliminación lógica seteando habilitado en 0 o similar si se prefiere. 
            // Pero el request dice "eliminación definitiva" en JS a veces.
            // Implementaremos un delete físico si se requiere, pero usualmente es mejor habilitado=0
            DB::connection('sqlsrv')->table('sw_cursos')->where('codigo', $codigo)->delete();
            return response()->json(['success' => true, 'message' => 'Curso eliminado definitivamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar el curso'], 500);
        }
    }

    /**
     * Group 2: Programaciones (Ciclos)
     */

    public function saveProgramacion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cod_cursos' => 'required|exists:sw_cursos,codigo',
            'tipo' => 'required|in:REGULAR,EXTEMPORANEO',
            'fecha_inicio' => 'required|date',
            'fecha_final' => 'required|date|after_or_equal:fecha_inicio',
        ]);

        if ($validator->fails()) return response()->json(['success' => false, 'errors' => $validator->errors()], 422);

        DB::beginTransaction();
        try {
            $curso = Cursos::findOrFail($request->cod_cursos);
            $inicio = Carbon::parse($request->fecha_inicio);
            $periodo = $inicio->format('Y-m');

            // Límite anual (Pauta 6)
            $limit = $curso->periodicidad ?: 1;
            if (CursoProgramacion::where('cod_cursos', $curso->codigo)->where('habilitado', 1)->whereYear('fecha_inicio', $inicio->year)->count() >= $limit) {
                return response()->json(['success' => false, 'message' => "Límite anual alcanzado."], 409);
            }

            // Unicidad (Pauta 8)
            if (CursoProgramacion::where('cod_cursos', $curso->codigo)->where('periodo', $periodo)->where('tipo', $request->tipo)->habilitados()->exists()) {
                return response()->json(['success' => false, 'message' => "Ya existe una programación {$request->tipo} para {$periodo}."], 409);
            }

            $lastProg = CursoProgramacion::orderBy('codigo_programacion', 'desc')->first();
            $newCode = str_pad(($lastProg ? intval($lastProg->codigo_programacion) : 1000) + 1, 4, '0', STR_PAD_LEFT);

            DB::connection('sqlsrv')->table('sw_cursos_programacion')->insert([
                'codigo_programacion' => $newCode,
                'cod_cursos' => $curso->codigo,
                'periodo' => $periodo,
                'tipo' => $request->tipo,
                'fecha_inicio' => DB::raw("CAST('" . $inicio->startOfDay()->format('Ymd H:i:s') . "' AS DATETIME)"),
                'fecha_final' => DB::raw("CAST('" . Carbon::parse($request->fecha_final)->endOfDay()->format('Ymd H:i:s') . "' AS DATETIME)"),
                'habilitado' => 1,
                'fecha_creacion' => DB::raw('GETDATE()')
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Ciclo creado', 'codigo' => $newCode]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error.'], 500);
        }
    }

    public function storeProgramacionManual(Request $request)
    {
        DB::beginTransaction();
        try {
            $codCurso = $request->input('cod_cursos') ?? $request->input('cod_curso');
            $curso = Cursos::with('tipoCurso')->findOrFail($codCurso);
            
            // Calculamos fechas para cumplir con la validación de saveProgramacion
            $inicio = Carbon::parse($request->fecha_inicio)->startOfMonth();
            $final = Carbon::parse($request->fecha_inicio)->endOfMonth();

            $respProg = $this->saveProgramacion(new Request($request->all() + [
                'cod_cursos' => $codCurso, 
                'tipo' => 'REGULAR',
                'fecha_inicio' => $inicio->format('Y-m-d'),
                'fecha_final' => $final->format('Y-m-d')
            ]));
            if ($respProg->getStatusCode() !== 200) return $respProg;
            
            $progCode = $respProg->getData()->codigo;
            $prog = CursoProgramacion::where('codigo_programacion', $progCode)->first();

            $query = DB::connection('sqlsrv')->table('si_solm.dbo.PERSONAL')->where('ESTA_ACTI', 1);
            $tipoDesc = strtoupper($curso->tipoCurso->descripcion ?? '');

            // Rule 11: Filtros inteligentes desde el Request
            $reqSucursal = $request->input('sucursal_codigo'); // SUCU_CODIGO
            $reqCliente  = $request->input('cliente_id');      // sw_clientes.codigo
            $reqArea     = $request->input('area_codigo');     // CODI_CARG

            if (str_contains($tipoDesc, 'PCU')) {
                // Si hay cliente específico filtrado en el modal
                $sucs = $reqCliente ? [$reqCliente] : DB::connection('sqlsrv')->table('sw_curso_sucursales')->where('curso_codigo', $curso->codigo)->pluck('sucursal')->toArray();
                
                $extSucs = [];
                $clientesQuery = DB::connection('sqlsrv')->table('sw_clientes')->whereIn('codigo', $sucs);
                foreach ($clientesQuery->pluck('cod_legacy') as $lc) {
                    $res = DB::connection('sqlsrv_controlclientes')->select("EXEC USP_LISTAR_SUCURSALES_X_CLIENTE ?", [$lc]);
                    foreach ($res as $r) {
                        if (isset($r->codigo_sucursal)) {
                            // Si hay sede específica filtrada en el modal
                            if ($reqSucursal && trim($r->codigo_sucursal) !== $reqSucursal) continue;
                            $extSucs[] = trim($r->codigo_sucursal);
                        }
                    }
                }
                $query->whereIn('SUCU_CODIGO', array_unique($extSucs));

            } elseif (str_contains($tipoDesc, 'PCI')) {
                // Si hay área específica filtrada en el modal
                $areas = $reqArea ? [$reqArea] : DB::connection('sqlsrv')->table('sw_curso_sucursales')->where('curso_codigo', $curso->codigo)->pluck('sucursal')->toArray();
                $query->whereIn('CODI_CARG', $areas);
                
                // Si además filtraron por sede en el modal
                if ($reqSucursal) $query->where('SUCU_CODIGO', $reqSucursal);

            } elseif (str_contains($tipoDesc, 'PCE')) {
                // Para PCE (todos), igual permitimos filtrar por sede o área si el modal lo envía
                if ($reqSucursal) $query->where('SUCU_CODIGO', $reqSucursal);
                if ($reqArea) $query->where('CODI_CARG', $reqArea);
            }

            // DNI Paste (Pauta 11)
            if ($request->filled('dnis')) {
                $dnis = is_array($request->dnis) ? $request->dnis : explode(',', $request->dnis);
                $query->orWhereIn('NRO_DOCU_IDEN', array_map('trim', $dnis));
            }

            $personales = $query->pluck('CODI_PERS')->toArray();
            
            // Lógica diferenciada (Pauta 7 y 11)
            if ($curso->es_demanda) {
                // Para cursos bajo demanda, solo matriculamos si hay DNIs específicos pegados
                if ($request->filled('dnis')) {
                    if (empty($personales)) throw new \Exception("Los DNIs ingresados no coinciden con personal activo.");
                    DispatchMatriculaBatchJob::dispatch($curso->codigo, $prog->codigo_programacion, $personales, Auth::id());
                    $msg = 'Ciclo abierto y matrícula de ' . count($personales) . ' personas específicas iniciada.';
                } else {
                    // Si no hay DNI, solo abrimos el ciclo
                    $msg = 'Ciclo bajo demanda abierto exitosamente. Ya puede matricular personal desde la pestaña de Matrículas.';
                }
            } else {
                // Para cursos periódicos (PCI, PCU, PCE), mantenemos la matrícula masiva automática
                if (empty($personales)) throw new \Exception("No hay personal que cumpla con los criterios del Plan de Capacitación.");
                DispatchMatriculaBatchJob::dispatch($curso->codigo, $prog->codigo_programacion, $personales, Auth::id());
                $msg = 'Ciclo abierto correctamente. Matrícula masiva de ' . count($personales) . ' personas iniciada en segundo plano.';
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("storeProgramacionManual Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateProgramacionHab($codigo)
    {
        try {
            $prog = DB::connection('sqlsrv')->table('sw_cursos_programacion')->where('codigo_programacion', $codigo)->first();
            if (!$prog) return response()->json(['success' => false, 'message' => 'Programación no encontrada'], 4404);

            $nuevoEstado = $prog->habilitado == 1 ? 0 : 1;
            
            DB::connection('sqlsrv')->table('sw_cursos_programacion')->where('codigo_programacion', $codigo)->update([
                'habilitado' => $nuevoEstado,
                'fecha_modificacion' => DB::raw('GETDATE()')
            ]);

            return response()->json([
                'success' => true, 
                'message' => $nuevoEstado == 1 ? 'Programación habilitada' : 'Programación deshabilitada',
                'habilitado' => $nuevoEstado
            ]);
        } catch (\Exception $e) {
            Log::error("updateProgramacionHab Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al actualizar el ciclo'], 500);
        }
    }

    /**
     * Group 3: Matrículas
     */

    public function saveMatricula(Request $request)
    {
        $personales = $request->input('personales', []);
        $cursoId = $request->input('cod_curso');
        $progId = $request->input('cod_programacion');
        $usuarioId = Auth::id();

        if (count($personales) > 500) return response()->json(['success' => false, 'message' => 'Máximo 500.'], 400);

        $curso = Cursos::find($cursoId);
        $nombreCurso = $curso ? $curso->nombre : "Curso #{$cursoId}";

        // Enviar a segundo plano (Pauta 11)
        \App\Jobs\DispatchMatriculaBatchJob::dispatch($cursoId, $progId, $personales, $usuarioId);

        // Notificar (Fase 7)
        NotificacionMatricula::crearNotificacionExitosa($usuarioId, $cursoId, $nombreCurso, count($personales));

        return response()->json(['success' => true, 'message' => 'Matriculación masiva iniciada en segundo plano.']);
    }

    public function getMatriculasMigraPersonal($cursoId)
    {
        try {
            $res = DB::connection('sqlsrv')->table('sw_matriculas as m')
                ->leftJoin('sw_cursos_programacion as prog', 'm.cod_programacion', '=', 'prog.codigo_programacion')
                // Join directo con la tabla maestra de personal usando LTRIM/RTRIM y COLLATE para evitar conflictos de intercalación
                ->leftJoin('si_solm.dbo.PERSONAL as P', DB::raw('LTRIM(RTRIM(m.cod_personal)) COLLATE DATABASE_DEFAULT'), '=', DB::raw('LTRIM(RTRIM(P.CODI_PERS)) COLLATE DATABASE_DEFAULT'))
                ->leftJoin('sw_MIGRA_SISO_SUCURSAL as S', 'P.SUCU_CODIGO', '=', 'S.SUCU_CODIGO')
                ->where('m.cod_curso', $cursoId)
                ->select([
                    'm.cod_personal',
                    'P.NRO_DOCU_IDEN as dni',
                    DB::raw("LTRIM(RTRIM(P.APEL_1 + ' ' + ISNULL(P.APEL_2, '') + ' ' + P.NOMB_1)) as nombre_completo"),
                    'S.SUCU_ABREVIATURA as sucursal',
                    'm.fecha_matricula',
                    'prog.fecha_inicio as prog_fecha_inicio',
                    'prog.fecha_final as prog_fecha_final',
                    'm.estado'
                ])
                ->get();

            // Transformar para manejar casos de personal retirado o nulos
            $res = $res->map(function($item) {
                return [
                    'cod_personal' => trim($item->cod_personal),
                    'dni' => $item->dni ?? 'N/A',
                    'nombre_completo' => $item->nombre_completo ?? 'No encontrado (Retirado)',
                    'sucursal' => $item->sucursal ?? 'N/A',
                    'fecha_matricula' => $item->fecha_matricula,
                    'prog_fecha_inicio' => $item->prog_fecha_inicio,
                    'prog_fecha_final' => $item->prog_fecha_final,
                    'estado' => $item->estado
                ];
            });

            return response()->json(['success' => true, 'matriculas' => $res, 'total' => count($res)]);
        } catch (\Exception $e) {
            Log::error("getMatriculasMigraPersonal Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function getHistorialPersonal($codPers)
    {
        try {
            $solicitudes = DB::connection('sqlsrv')->table('sw_matriculas as m')
                ->join('sw_cursos as c', 'm.cod_curso', '=', 'c.codigo')
                ->leftJoin('sw_cursos_programacion as p', 'm.cod_programacion', '=', 'p.codigo_programacion')
                ->where('m.cod_personal', trim($codPers))
                ->select([
                    'c.nombre as nombre_curso',
                    'm.estado',
                    'm.fecha_matricula',
                    'p.fecha_inicio as prog_fecha_inicio',
                    'p.fecha_final as prog_fecha_final'
                ])
                ->orderBy('m.fecha_matricula', 'desc')
                ->get();

            return response()->json(['success' => true, 'solicitudes' => $solicitudes]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener historial.'], 500);
        }
    }

    /**
     * Group 4: Catálogos
     */
    public function getCombosApertura()
    {
        return response()->json([
            'tipos' => DB::connection('sqlsrv')->table('sw_capacitacion_tipo_curso')->get(),
            'areas' => DB::connection('sqlsrv')->table('sw_capacitacion_areas')->get(),
            'empresas' => DB::connection('sqlsrv')->table('sw_MIGRA_EMPRESA')->get()
        ]);
    }


    /**
     * Group 5: Utilidades
     */
    public function getAlertasVencimiento()
    {
        $hoy = now()->startOfDay();
        $limite = $hoy->copy()->addDays(15);
        $alertas = [];

        $progs = DB::connection('sqlsrv')->table('sw_cursos_programacion as cp')
            ->join('sw_cursos as c', 'c.codigo', '=', 'cp.cod_cursos')
            ->where('cp.habilitado', 1)->where('cp.estado_periodo', 'VIGENTE')->where('c.es_periodico', 1)
            ->select('cp.*', 'c.nombre as curso_nombre', 'c.frecuencia')->get();

        foreach ($progs as $p) {
            $prox = Carbon::parse($p->fecha_inicio);
            switch (trim(strtoupper($p->frecuencia))) {
                case 'MENSUAL': $prox->addMonth(); break;
                case 'BIMESTRAL': $prox->addMonths(2); break;
                case 'ANUAL': $prox->addYear(); break;
            }
            if ($prox->between($hoy, $limite)) {
                $alertas[] = ['nombre' => $p->curso_nombre, 'fecha_proxima' => $prox->toDateString()];
            }
        }
        return response()->json(['success' => true, 'alertas' => $alertas]);
    }

    // --- Helpers ---
    private function calcularPeriodicidad($f, $esP)
    {
        if (!$esP) return 0;
        $map = ['MENSUAL' => 12, 'BIMESTRAL' => 6, 'TRIMESTRAL' => 4, 'ANUAL' => 1];
        return $map[strtoupper($f)] ?? 0;
    }

    private function saveAsignaciones($id, $asigs)
    {
        DB::connection('sqlsrv')->table('sw_curso_sucursales')->where('curso_codigo', $id)->delete();
        foreach ($asigs as $s) DB::connection('sqlsrv')->table('sw_curso_sucursales')->insert(['curso_codigo' => $id, 'sucursal' => $s, 'created_at' => DB::raw('GETDATE()'), 'updated_at' => DB::raw('GETDATE()')]);
    }

    private function saveExamen($id, $req)
    {
        $exists = DB::connection('sqlsrv')->table('sw_cursos_examen')->where('cod_cursos', $id)->first();

        if ($exists) {
            DB::connection('sqlsrv')->table('sw_cursos_examen')->where('cod_cursos', $id)->update([
                'nombre' => $req->nombre_exa ?? ("Examen de " . $req->nombre),
                'nota_minima' => $req->nota ?? 0,
                'intentos' => $req->intentos ?? 1,
                'tiempo' => $req->tiempo ?? $exists->tiempo ?? 60,
                'fecha_modificacion' => DB::raw('GETDATE()')
            ]);
        } else {
            DB::connection('sqlsrv')->table('sw_cursos_examen')->insert([
                'cod_cursos' => $id,
                'nombre' => $req->nombre_exa ?? ("Examen de " . $req->nombre),
                'descripcion' => $req->descripcion_exa ?? '',
                'nota_minima' => $req->nota ?? 0,
                'intentos' => $req->intentos ?? 1,
                'tiempo' => $req->tiempo ?? 60,
                'file_tiene' => 0,
                'file_nombre' => '',
                'file_ruta' => '',
                'file_extension' => '',
                'file_tipo' => '',
                'file_nombre_original' => '',
                'habilitado' => 1,
                'fecha_creacion' => DB::raw('GETDATE()'),
                'fecha_modificacion' => DB::raw('GETDATE()')
            ]);
        }

        if ($req->hasFile('archivo')) {
            $path = $req->file('archivo')->storeAs('plantillas/' . date('Y/F'), 'EXA_' . $id . '_' . time() . '.mbz', 'public');
            DB::connection('sqlsrv')->table('sw_cursos_examen')->where('cod_cursos', $id)->update([
                'file_tiene' => 1,
                'file_nombre' => basename($path)
            ]);
        }
    }

    public function buscarPersonalCapacitacion(Request $request)
    {
        try {
            $tipoResponsable = $request->input('tipo_responsable');
            // PERSONAL maestro requiere esquema si_solm.dbo, sw_ sucursales no (residen en default DB)
            // Usamos la tabla local sw_MIGRA_PERSONAL unida con CARGOS para obtener descripciones legibles
            // Aplicamos COLLATE DATABASE_DEFAULT para prevenir errores de intercalación entre BD local y si_solm
            $query = DB::connection('sqlsrv')->table('sw_MIGRA_PERSONAL as P')
                ->leftJoin('sw_MIGRA_SISO_SUCURSAL as S', 'P.SUCU_CODIGO', '=', 'S.SUCU_CODIGO')
                ->leftJoin('si_solm.dbo.CARGOS as C', function($join) {
                    $join->on(
                        DB::raw('LTRIM(RTRIM(P.CODI_CARG)) COLLATE DATABASE_DEFAULT'), 
                        '=', 
                        DB::raw('LTRIM(RTRIM(C.CODI_CARG)) COLLATE DATABASE_DEFAULT')
                    );
                })
                ->where('P.PERS_VIGENCIA', 'SI')
                ->select([
                    'P.CODI_PERS as codigo',
                    DB::raw("LTRIM(RTRIM(P.APEL_1 + ' ' + ISNULL(P.APEL_2, '') + ' ' + P.NOMB_1 + ' ' + ISNULL(P.NOMB_2, ''))) as nombre_completo"),
                    'P.NRO_DOCU_IDEN as dni',
                    'S.SUCU_ABREVIATURA as sucursal',
                    'C.DESC_CARGO as cargo'
                ]);

            // Mapeo de tipos de responsable (ADMINISTRATIVO_5 -> TIPOTRAB '05')
            if ($tipoResponsable === 'ADMINISTRATIVO_5') {
                $query->where('P.PERS_TIPOTRAB', '05');
            }

            $searchTerm = strtoupper(trim($request->input('q', '')));
            if ($searchTerm !== '') {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('P.APEL_1', 'LIKE', "%$searchTerm%")
                      ->orWhere('P.NOMB_1', 'LIKE', "%$searchTerm%")
                      ->orWhere('P.NRO_DOCU_IDEN', 'LIKE', "%$searchTerm%");
                });
            }

            $personal = $query->limit(50)->get();

            return response()->json([
                'success' => true,
                'personal' => $personal->map(fn($p) => [
                    'codigo' => trim($p->codigo),
                    'nombre_completo' => $p->nombre_completo,
                    'dni' => $p->dni,
                    'sucursal' => $p->sucursal ?? 'N/A',
                    'cargo' => $p->cargo ?? 'N/A'
                ])
            ]);
        } catch (\Exception $e) {
            Log::error('Error buscarPersonalCapacitacion: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al buscar personal.'], 500);
        }
    }


    public function analizarPlantilla(Request $request)
    {
        if (!$request->hasFile('plantilla')) {
            return response()->json(['success' => false, 'message' => 'No se envió el archivo.'], 400);
        }

        $file = $request->file('plantilla');
        $tempPath = storage_path('app/temp_mbz_' . uniqid());

        if (File::isDirectory($tempPath)) File::deleteDirectory($tempPath);
        File::makeDirectory($tempPath, 0777, true);

        try {
            $filePath = $tempPath . '/' . $file->getClientOriginalName();
            $file->move($tempPath, $file->getClientOriginalName());

            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (in_array($ext, ['gz', 'mbz'])) {
                try {
                    $phar = new \PharData($filePath);
                    $tarPath = preg_replace('/\.gz$/i', '', $filePath);
                    $phar->decompress(); 
                    $pharTar = new \PharData($tarPath);
                    $pharTar->extractTo($tempPath, null, true);
                    @unlink($tarPath);
                } catch (\Throwable $e) {}
            } 

            if ($ext === 'zip' || !File::isDirectory($tempPath . '/course') ) {
                $zip = new \ZipArchive;
                if ($zip->open($filePath) === true) {
                    $zip->extractTo($tempPath);
                    $zip->close();
                }
            }

            $findFile = function(string $base, string $name) {
                $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS));
                foreach ($rii as $f) {
                    if ($f->isFile() && $f->getFilename() === $name) return $f->getPathname();
                }
                return null;
            };

            $xmlFile = $findFile($tempPath, 'moodle_backup.xml');
            if (!$xmlFile) return response()->json(['success' => false, 'message' => 'El archivo no contiene moodle_backup.xml'], 400);

            $xml = simplexml_load_file($xmlFile);
            $info = $xml->information ?? null;
            if (!$info) return response()->json(['success' => false, 'message' => 'moodle_backup.xml no contiene la sección <information>.'], 400);

            $courseName    = (string) ($info->original_course_fullname ?? 'Sin nombre');
            $courseShort   = (string) ($info->original_course_shortname ?? '');
            $backupDate    = (string) ($info->backup_date ?? null);
            $moodleVersion = (string) ($info->moodle_version ?? null);

            $totalSections   = isset($info->contents->sections->section) ? count($info->contents->sections->section) : 0;
            $totalActivities = isset($info->contents->activities->activity) ? count($info->contents->activities->activity) : 0;

            $activityStats = [];
            $mapaTipos = ['quiz' => 'Examen', 'label' => 'Diapositivas', 'assign' => 'Tarea', 'forum' => 'Foro', 'resource' => 'Recurso', 'url' => 'Enlace', 'page' => 'Página', 'other' => 'Otro'];

            if (isset($info->contents->activities->activity)) {
                foreach ($info->contents->activities->activity as $act) {
                    $mod = (string) $act->modulename ?: 'other';
                    $nombre = $mapaTipos[$mod] ?? ucfirst($mod);
                    $activityStats[$nombre] = ($activityStats[$nombre] ?? 0) + 1;
                }
            }

            $totalQuestions = 0;
            $questionsFile = $findFile($tempPath, 'questions.xml');
            if ($questionsFile && file_exists($questionsFile)) {
                $questionsDoc = simplexml_load_file($questionsFile);
                foreach ($questionsDoc->question_category as $category) {
                    if (isset($category->questions->question)) {
                        foreach ($category->questions->question as $q) {
                            if (((string)$q->parent) === "0") $totalQuestions++;
                        }
                    }
                }
            }

            return response()->json([
                'success' => true, 'courseName' => $courseName, 'courseShortname' => $courseShort, 
                'backupDate' => $backupDate, 'moodleVersion' => $moodleVersion, 
                'totalSections' => (int)$totalSections, 'totalActivities' => (int)$totalActivities, 
                'activityStats' => $activityStats, 'totalQuestions' => (int)$totalQuestions
            ]);
        } catch (\Throwable $e) {
            Log::error('Error procesando plantilla: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al procesar la plantilla.'], 500);
        } finally {
            if (File::isDirectory($tempPath)) File::deleteDirectory($tempPath);
        }
    }

    /**
     * Vistas
     */
    public function vistaConsultaMatriculas()
    {
        return view('capacitacion.consulta_matriculas');
    }

    public function vistaHistorialPersonal()
    {
        return view('capacitacion.consulta_matriculas');
    }

    public function vistaHistorialCapacitaciones()
    {
        return view('capacitacion.consulta_matriculas');
    }

}
