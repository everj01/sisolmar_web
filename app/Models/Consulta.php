<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
    use HasFactory;

    public static function getSucursalesPorCliente($codLegacy)
    {
        return DB::connection("sqlsrv_controlclientes")->select(
            "EXEC USP_LISTAR_SUCURSALES_X_CLIENTE :cod_legacy",
            ["cod_legacy" => $codLegacy],
        );
    }

    public static function getFoliosVigentes(
        $codCargo = null,
        $codCliente = null,
        $tipoFolio = null,
        $prioridad = null,
    ) {
        return DB::select("EXEC SW_LISTAR_FOLIOS_VIGENTES ?, ?, ?, ?", [
            $codCargo,
            $codCliente,
            $tipoFolio,
            $prioridad,
        ]);
    }

    public static function listarFoliosPendientes(
        $dni,
        $codCliente,
        $codSucursal,
    ) {
        return DB::select(
            "EXEC SW_LISTAR_FOLIOS_PENDIENTES_CONSULTAS ?, ?, ?",
            [$dni, $codCliente, $codSucursal],
        );
    }

    public static function listarFoliosProximosVencer(
        $dni,
        $codCliente,
        $codSucursal,
        $persona,
        $servicio,
        $dias,
        $fecha_desde,
        $fecha_hasta,
    ) {
        return DB::select(
            "EXEC SW_LISTAR_FOLIOS_PROXIMOS_VENCER_CONSULTAS ?, ?, ?, ?, ?, ?, ?, ?",
            [
                $dni,
                $codCliente,
                $codSucursal,
                $persona,
                $servicio,
                $dias,
                $fecha_desde,
                $fecha_hasta,
            ],
        );
    }

    public static function obtenerAreasPorSistema($sistemaId)
    {
        return DB::select("EXEC SW_LISTAR_AREAS_POR_SISTEMA ?", [$sistemaId]);
    }

    public static function obtenerAreasEncargadas()
    {
        return DB::select(
            "SELECT AVAR_ID as codigo, AVAR_DESCRIPCION as descripcion FROM si_solm.dbo.AV_AREA WHERE AVAR_VIGENCIA = 1",
        );
    }

    public static function obtenerSucursales()
    {
        return DB::table("sw_MIGRA_SISO_SUCURSAL")
            ->select("SUCU_CODIGO as codigo", "SUCU_ABREVIATURA as sucursal")
            ->whereNotNull("SUCU_ABREVIATURA")
            ->orderBy("SUCU_ABREVIATURA")
            ->get();
    }

    public static function obtenerProgramacionesVigentes()
    {
        return DB::table("sw_cursos_programacion as cp")
            ->join("sw_cursos as c", "c.codigo", "=", "cp.cod_cursos")
            ->select(
                "cp.*",
                "c.codigo_curso",
                "c.nombre as curso_nombre",
                "c.frecuencia",
                "c.es_periodico",
            )
            ->where("cp.habilitado", 1)
            ->where("cp.estado_periodo", "VIGENTE")
            ->where("c.habilitado", 1)
            ->where("c.es_periodico", 1)
            ->get();
    }

    public static function obtenerMatriculasPorCurso(int $cursoId)
    {
        return DB::table("sw_matriculas as m")
            ->leftJoin(
                "sw_cursos_programacion as prog",
                "m.cod_programacion",
                "=",
                "prog.codigo",
            )
            ->where("m.cod_curso", "=", $cursoId)
            ->select([
                "m.cod_personal",
                "m.fecha_matricula",
                "m.estado",
                "prog.fecha_inicio as prog_fecha_inicio",
                "prog.fecha_final as prog_fecha_final",
            ])
            ->get();
    }

    public static function obtenerTipoDeCurso(int $cursoId)
    {
        return DB::table("sw_cursos as c")
            ->join(
                "sw_capacitacion_tipo_curso as tc",
                "c.tipo_curso",
                "=",
                "tc.codigo",
            )
            ->where("c.codigo", $cursoId)
            ->select("c.tipo_curso", "tc.descripcion as tipo_descripcion")
            ->first();
    }

    public static function obtenerDirigidos()
    {
        return DB::table("sw_cursos_dirigido as d")
            ->select("codigo", "opcion as texto")
            ->where("habilitado", 1)
            ->orderBy("codigo")
            ->get();
    }

    public static function obtenerPersonalPorSucursal_Reporte($codSucursal)
    {
        return DB::table("si_solm.dbo.PERSONAL")
            ->leftJoin("sw_cargos", "si_solm.dbo.PERSONAL.CODI_CARG", "=", "sw_cargos.codigo")
            ->select(
                "si_solm.dbo.PERSONAL.CODI_PERS",
                "si_solm.dbo.PERSONAL.APEL_1",
                "si_solm.dbo.PERSONAL.APEL_2",
                "si_solm.dbo.PERSONAL.NOMB_1",
                "si_solm.dbo.PERSONAL.NOMB_2",
                "si_solm.dbo.PERSONAL.NRO_DOCU_IDEN",
                "si_solm.dbo.PERSONAL.SUCU_CODIGO",
                "si_solm.dbo.PERSONAL.FECH_INGRE",
                "si_solm.dbo.PERSONAL.PERS_TIPOTRAB",
                "sw_cargos.nombre as cargo",
            )
            ->orderBy("si_solm.dbo.PERSONAL.CODI_PERS")
            ->where("si_solm.dbo.PERSONAL.PERS_VIGENCIA", "SI")
            ->where("si_solm.dbo.PERSONAL.SUCU_CODIGO", $codSucursal)
            ->get();
    }

    public static function getPersonalPorSucursal($codSucursal)
    {
        return DB::table("si_solm.dbo.PERSONAL")
            ->leftJoin("sw_cargos", "si_solm.dbo.PERSONAL.CODI_CARG", "=", "sw_cargos.codigo")
            ->select(
                "si_solm.dbo.PERSONAL.CODI_PERS as codigo",
                DB::raw("LTRIM(RTRIM(si_solm.dbo.PERSONAL.APEL_1 + ' ' + ISNULL(si_solm.dbo.PERSONAL.APEL_2, '') + ' ' + si_solm.dbo.PERSONAL.NOMB_1 + ' ' + ISNULL(si_solm.dbo.PERSONAL.NOMB_2, ''))) as nombre_completo"),
                "si_solm.dbo.PERSONAL.NRO_DOCU_IDEN as dni",
                "sw_cargos.nombre as cargo",
            )
            ->where("si_solm.dbo.PERSONAL.PERS_VIGENCIA", "SI")
            ->where("si_solm.dbo.PERSONAL.SUCU_CODIGO", $codSucursal)
            ->orderBy("si_solm.dbo.PERSONAL.APEL_1")
            ->get();
    }
}
