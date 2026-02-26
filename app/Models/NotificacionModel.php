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
            ->join('sw_MIGRA_PERSONAL as p', function ($join) {
                $join->whereRaw("
                    (TRY_CAST(fd.codPersonal AS INT) = TRY_CAST(p.CODI_PERS AS INT) AND TRY_CAST(fd.codPersonal AS INT) IS NOT NULL)
                    OR
                    (fd.codPersonal COLLATE DATABASE_DEFAULT = p.CODI_PERS COLLATE DATABASE_DEFAULT AND TRY_CAST(fd.codPersonal AS INT) IS NULL)
                ");
            })
            ->join('sw_folios as f', 'fd.codFolio', '=', 'f.codigo')
            ->select(
                'fd.codPersonal',
                DB::raw("CONCAT(p.APEL_1 COLLATE DATABASE_DEFAULT, ' ', p.APEL_2 COLLATE DATABASE_DEFAULT, ' ', p.NOMB_1 COLLATE DATABASE_DEFAULT, ' ', p.NOMB_2 COLLATE DATABASE_DEFAULT) as personal"),
                'f.nombre as documento',
                'fd.fecha_caducidad',
                DB::raw("DATEDIFF(day, GETDATE(), fd.fecha_caducidad) as dias_restantes")
            )
            ->whereNotNull('fd.fecha_caducidad')
            ->whereRaw("DATEDIFF(day, GETDATE(), fd.fecha_caducidad) BETWEEN 0 AND ?", [$dias])
            ->get();
    }
}

