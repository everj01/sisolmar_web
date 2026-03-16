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
        throw new \Exception('DB Error: ' . $e->getMessage());
    }
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
}
