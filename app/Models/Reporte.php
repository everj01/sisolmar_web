<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Reporte extends Model
{
    use HasFactory;

    public static function getFoliosPendientesXSucursal($sucursal = 0)
    {
        try {
            return DB::select(
                'EXEC SW_REPORTE_FOLIOS_PENDIENTES_X_SUCURSAL ?',
                [$sucursal]
            );
        } catch (\Exception $e) {
            throw new \Exception('DB Error: '.$e->getMessage());
        }
    }

    public static function getFoliosPendientesRegistro($sucursal = '0', $cliente = '', $parametros = '')
    {
        return DB::select(
            'EXEC SW_REPORTE_FOLIOS_PENDIENTES_REGISTRO ?, ?, ?',
            [$sucursal, $cliente, $parametros]
        );
    }

    public static function getFoliosPorVencer($sucursal = 0, $diasAlerta = 30)
    {
        return DB::select(
            'EXEC SW_REPORTE_FOLIOS_POR_VENCER ?, ?',
            [$sucursal, $diasAlerta]
        );
    }

    public static function getFoliosPorVencerXCliente($cliente, $diasAlerta = 30)
    {
        return DB::select(
            'EXEC SW_REPORTE_FOLIOS_POR_VENCER_X_CLIENTE ?, ?',
            [$cliente, $diasAlerta]
        );
    }

    public static function getTiposPersonal()
    {
        return DB::select('SELECT TIPE_CODIGO, TIPE_DESCRIPCION FROM si_solm.dbo.ADMI_TIPO_PERSONAL WITH (NOLOCK) ORDER BY TIPE_DESCRIPCION');
    }

    public static function getCategoriasCarnet()
    {
        return DB::select('SELECT CATE_CODIGO, CATE_DESCRIPCION FROM si_solm.dbo.REDO_CARNET_CATEGORIA WITH (NOLOCK) ORDER BY CATE_CODIGO');
    }

    public static function getCarnet($sucursal, $tipoPers, $vigencia, $estado, $categoria)
    {
        return DB::select(
            'EXEC si_solm.dbo.USP_Redo_CarnetReporte ?, ?, ?, ?, ?',
            [$sucursal, $tipoPers, $vigencia, $estado, $categoria]
        );
    }

    public static function getClasesBrevete()
    {
        return DB::select(
            'SELECT codigo, nombre FROM dbo.sw_brevete_clase WHERE habilitado = 1 ORDER BY nombre'
        );
    }

    public static function getCategoriasBrevete($codClase = null)
    {
        if ($codClase) {
            return DB::select(
                'SELECT codigo, nombre FROM dbo.sw_brevete_categoria WHERE habilitado = 1 AND cod_clase = ? ORDER BY nombre',
                [(int) $codClase]
            );
        }

        return DB::select(
            'SELECT codigo, nombre FROM dbo.sw_brevete_categoria WHERE habilitado = 1 ORDER BY nombre'
        );
    }

    public static function getVigenciaDni(string $sucursal, string $tipo1, string $tipo2, string $vigente = 'NO')
    {
        $condVig = $vigente === 'SI'
            ? 'P.PERS_FECHCADUCADNI >= GETDATE()'
            : '(P.PERS_FECHCADUCADNI < GETDATE() OR P.PERS_FECHCADUCADNI IS NULL)';

        $sql = "
              SELECT F.CODIGO, F.PERSONAL, P.NRO_DOCU_IDEN,
                  CASE WHEN F.COD_TIPO = '01' THEN 'OPER 4°' ELSE 'OPER 5°' END AS TIPO,
                  F.CLIENTE, F.CARGO,
                  CONVERT(VARCHAR(10), P.PERS_FECHCADUCADNI, 103) AS PERS_FECHCADUCADNI,
                  (SELECT CASE WHEN si_solm.dbo.F_VER_ESCANEO_DNI(F.CODIGO) = 0 THEN 'NO'
                               WHEN si_solm.dbo.F_VER_ESCANEO_DNI(F.CODIGO) = 1 THEN 'SI'
                               ELSE 'CADUCADO' END) AS ESCANEO,
                  (SELECT SUCU_ABREVIATURA FROM si_solm.dbo.SISO_SUCURSAL (NOLOCK) WHERE SUCU_CODIGO = ?) AS SUCURSAL,
                  P.EMPR_CODIGO AS EMPRESA,
                  CONVERT(VARCHAR(10), P.FECH_INGRE, 103) AS INGRESO_SOLMAR,
                  CONVERT(VARCHAR(10), P.FECH_INGRE_PLANILLA, 103) AS INGRESO_PLAN,
                  (SELECT DESC_TIPO_DOCU FROM si_solm.dbo.TIPO_DOCUMENTO WHERE P.CODI_TIPO_DOCU = CODI_TIPO_DOCU) AS TIPO_DOCU
              FROM si_solm.dbo.UF_LISTAR_PERSONAL_LEGAJOS(?, '00000') F,
                   si_solm.dbo.PERSONAL P (NOLOCK)
              WHERE F.CODIGO = P.CODI_PERS
                AND P.PERS_VIGENCIA = 'SI'
                AND P.EMPR_CODIGO = '01'
                AND P.PERS_TIPOTRAB IN (?, ?)
                AND {$condVig}

              UNION

              SELECT P.CODI_PERS,
                  P.APEL_1 + ' ' + P.APEL_2 + ' ' + P.NOMB_1 + ' ' + ISNULL(P.NOMB_2, ''),
                  P.NRO_DOCU_IDEN,
                  CASE WHEN P.PERS_TIPOTRAB = '02' THEN 'ADMI 4°' ELSE 'ADMI 5°' END,
                  (SELECT RAZON_SOCIAL FROM si_solm.dbo.EMPRESA (NOLOCK) WHERE EMPR_CODIGO = P.EMPR_CODIGO),
                  (SELECT DESC_CARGO FROM si_solm.dbo.CARGOS (NOLOCK) WHERE CODI_CARG = P.CODI_CARG),
                  CONVERT(VARCHAR(10), P.PERS_FECHCADUCADNI, 103),
                  (SELECT CASE WHEN si_solm.dbo.F_VER_ESCANEO_DNI(P.CODI_PERS) = 0 THEN 'NO'
                               WHEN si_solm.dbo.F_VER_ESCANEO_DNI(P.CODI_PERS) = 1 THEN 'SI'
                               ELSE 'CADUCADO' END),
                  (SELECT SUCU_ABREVIATURA FROM si_solm.dbo.SISO_SUCURSAL (NOLOCK) WHERE SUCU_CODIGO = ?),
                  P.EMPR_CODIGO,
                  CONVERT(VARCHAR(10), P.FECH_INGRE, 103),
                  CONVERT(VARCHAR(10), P.FECH_INGRE_PLANILLA, 103),
                  (SELECT DESC_TIPO_DOCU FROM si_solm.dbo.TIPO_DOCUMENTO WHERE P.CODI_TIPO_DOCU = CODI_TIPO_DOCU)
              FROM si_solm.dbo.PERSONAL P (NOLOCK)
              WHERE P.PERS_VIGENCIA = 'SI'
                AND P.SUCU_CODIGO = ?
                AND P.EMPR_CODIGO = '01'
                AND P.PERS_TIPOTRAB IN (?, ?)
                AND P.PERS_TIPOTRAB NOT IN ('01', '03', '06')
                AND {$condVig}

              ORDER BY TIPO DESC, PERSONAL
          ";

        return DB::select($sql, [
            $sucursal, $sucursal, $tipo1, $tipo2,
            $sucursal, $sucursal, $tipo1, $tipo2,
        ]);
    }

    public static function getVigenciaBrevete(string $sucursal, string $tipo1, string $tipo2, string $clase, string $categoria, string $vigente = 'NO')
    {
        $condVig = $vigente === 'SI'
            ? '(P.FECH_REVAL_BREVETE >= GETDATE() AND P.FECH_REVAL_BREVETE IS NOT NULL)'
            : 'P.FECH_REVAL_BREVETE < GETDATE()';

        $sql = "
              SELECT F.CODIGO, F.PERSONAL, P.NRO_DOCU_IDEN,
                  CASE WHEN F.COD_TIPO = '01' THEN 'OPER 4°' ELSE 'OPER 5°' END AS TIPO,
                  F.CLIENTE, F.CARGO,
                  CONVERT(VARCHAR(10), P.PERS_FECHCADUCADNI, 103) AS PERS_FECHCADUCADNI,
                  (SELECT CASE WHEN si_solm.dbo.F_VER_ESCANEO_DNI(F.CODIGO) = 0 THEN 'NO'
                               WHEN si_solm.dbo.F_VER_ESCANEO_DNI(F.CODIGO) = 1 THEN 'SI'
                               ELSE 'CADUCADO' END) AS ESCANEO,
                  (SELECT SUCU_ABREVIATURA FROM si_solm.dbo.SISO_SUCURSAL (NOLOCK) WHERE SUCU_CODIGO = ?) AS SUCURSAL,
                  P.EMPR_CODIGO AS EMPRESA,
                  CONVERT(VARCHAR(10), P.FECH_INGRE, 103) AS INGRESO_SOLMAR,
                  CONVERT(VARCHAR(10), P.FECH_INGRE_PLANILLA, 103) AS INGRESO_PLAN,
                  (SELECT DESC_TIPO_DOCU FROM si_solm.dbo.TIPO_DOCUMENTO WHERE P.CODI_TIPO_DOCU = CODI_TIPO_DOCU) AS TIPO_DOCU,
                  P.PERS_BREVETE, P.CLASE_BREVETE, P.CATEGORIA_BREVETE,
                  CONVERT(VARCHAR(10), P.FECH_EXP_BREVETE, 103) AS FECH_EXP_BREVETE,
                  CONVERT(VARCHAR(10), P.FECH_REVAL_BREVETE, 103) AS FECH_REVAL_BREVETE,
                  P.RESTRICCION_BREVETE
              FROM si_solm.dbo.UF_LISTAR_PERSONAL_LEGAJOS(?, '00000') F,
                   si_solm.dbo.PERSONAL P (NOLOCK)
              WHERE F.CODIGO = P.CODI_PERS
                AND P.PERS_VIGENCIA = 'SI'
                AND P.EMPR_CODIGO = '01'
                AND (P.CLASE_BREVETE IN (?) OR (? = 'T'))
                AND (P.CATEGORIA_BREVETE IN (?) OR (? = 'T'))
                AND P.PERS_TIPOTRAB IN (?, ?)
                AND {$condVig}

              UNION

              SELECT P.CODI_PERS,
                  P.APEL_1 + ' ' + P.APEL_2 + ' ' + P.NOMB_1 + ' ' + ISNULL(P.NOMB_2, ''),
                  P.NRO_DOCU_IDEN,
                  CASE WHEN P.PERS_TIPOTRAB = '02' THEN 'ADMI 4°' ELSE 'ADMI 5°' END,
                  (SELECT RAZON_SOCIAL FROM si_solm.dbo.EMPRESA (NOLOCK) WHERE EMPR_CODIGO = P.EMPR_CODIGO),
                  (SELECT DESC_CARGO FROM si_solm.dbo.CARGOS (NOLOCK) WHERE CODI_CARG = P.CODI_CARG),
                  CONVERT(VARCHAR(10), P.PERS_FECHCADUCADNI, 103),
                  (SELECT CASE WHEN si_solm.dbo.F_VER_ESCANEO_DNI(P.CODI_PERS) = 0 THEN 'NO'
                               WHEN si_solm.dbo.F_VER_ESCANEO_DNI(P.CODI_PERS) = 1 THEN 'SI'
                               ELSE 'CADUCADO' END),
                  (SELECT SUCU_ABREVIATURA FROM si_solm.dbo.SISO_SUCURSAL (NOLOCK) WHERE SUCU_CODIGO = ?),
                  P.EMPR_CODIGO,
                  CONVERT(VARCHAR(10), P.FECH_INGRE, 103),
                  CONVERT(VARCHAR(10), P.FECH_INGRE_PLANILLA, 103),
                  (SELECT DESC_TIPO_DOCU FROM si_solm.dbo.TIPO_DOCUMENTO WHERE P.CODI_TIPO_DOCU = CODI_TIPO_DOCU),
                  P.PERS_BREVETE, P.CLASE_BREVETE, P.CATEGORIA_BREVETE,
                  CONVERT(VARCHAR(10), P.FECH_EXP_BREVETE, 103),
                  CONVERT(VARCHAR(10), P.FECH_REVAL_BREVETE, 103),
                  P.RESTRICCION_BREVETE
              FROM si_solm.dbo.PERSONAL P (NOLOCK)
              WHERE P.PERS_VIGENCIA = 'SI'
                AND P.SUCU_CODIGO = ?
                AND P.EMPR_CODIGO = '01'
                AND (P.CLASE_BREVETE IN (?) OR (? = 'T'))
                AND (P.CATEGORIA_BREVETE IN (?) OR (? = 'T'))
                AND P.PERS_TIPOTRAB IN (?, ?)
                AND P.PERS_TIPOTRAB NOT IN ('01', '03', '06')
                AND {$condVig}

              ORDER BY TIPO DESC, PERSONAL
          ";

        return DB::select($sql, [
            $sucursal, $sucursal, $clase, $clase, $categoria, $categoria, $tipo1, $tipo2,
            $sucursal, $sucursal, $clase, $clase, $categoria, $categoria, $tipo1, $tipo2,
        ]);
    }

    public static function getCertificados()
    {
        return DB::select(
            'SELECT REQU_CODIGO, REQU_DESCRIPCION FROM si_solm.dbo.REDO_REQUISITOS
            WHERE TIRE_CODIGO = 3 OR REQU_CODIGO IN (7)
            ORDER BY REQU_DESCRIPCION'
        );
    }

    public static function getCertificadosReporte($sucursal, $tipoPers, $vigencia, $estado, $requisito, $fechaVenc)
    {
        return DB::select(
            'EXEC si_solm.dbo.USP_Redo_CertificadosReporte ?, ?, ?, ?, ?, ?, ?',
            [$sucursal, $tipoPers, $vigencia, $estado, (int) $requisito, $fechaVenc, '01']
        );
    }
}
