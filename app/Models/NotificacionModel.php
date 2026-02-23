<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NotificacionModel extends Model
{
    use HasFactory;

    public static function updateNotificacion($codigo, $data){

        $updated = DB::table('sw_solicitud_cargo_comercial')
        ->where('codigo', $codigo)
        ->update($data);

        return $updated;
    }

    public static function foliosPorVencer($dias)
    {
        return DB::table('sw_folios_detalles as fd')
            ->join('sw_MIGRA_PERSONAL as p', 'fd.codPersonal', '=', 'p.CODI_PERS')
            ->join('sw_folios as f', 'fd.codFolio', '=', 'f.codigo')
            ->select(
                'fd.codPersonal',
                DB::raw("CONCAT(p.APEL_1, ' ', p.APEL_2, ' ', p.NOMB_1, ' ', p.NOMB_2) as personal"),
                'f.nombre as documento',
                'fd.fecha_caducidad',
                DB::raw("DATEDIFF(day, GETDATE(), fd.fecha_caducidad) as dias_restantes")
            )
            ->whereNotNull('fd.fecha_caducidad')
            ->whereRaw("DATEDIFF(day, GETDATE(), fd.fecha_caducidad) BETWEEN 0 AND ?", [$dias])
            ->get();
    }
}

