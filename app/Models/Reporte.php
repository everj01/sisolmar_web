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
}
