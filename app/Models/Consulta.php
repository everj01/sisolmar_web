<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consulta extends Model
{
    use HasFactory;

    public static function getSucursalesPorCliente($codLegacy)
    {
        return DB::connection('sqlsrv_controlclientes')
            ->select(
                'EXEC USP_LISTAR_SUCURSALES_X_CLIENTE :cod_legacy',
                ['cod_legacy' => $codLegacy]
            );
    }

    public static function getFoliosVigentes($codCargo = null, $codCliente = null, $tipoFolio = null, $prioridad = null)
    {
        return DB::select('EXEC SW_LISTAR_FOLIOS_VIGENTES ?, ?, ?, ?',[$codCargo, $codCliente, $tipoFolio, $prioridad]);
    }

    public static function listarFoliosPendientes($dni, $codCliente, $codSucursal)
    {
        return DB::select('EXEC SW_LISTAR_FOLIOS_PENDIENTES_CONSULTAS ?, ?, ?',[$dni, $codCliente, $codSucursal]);
    }

}
