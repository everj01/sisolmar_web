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
        return DB::select(
            'EXEC SW_REPORTE_FOLIOS_PENDIENTES_X_SUCURSAL :sucursal',
            ['sucursal' => $sucursal]
        );
    }
}
