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

    // METODO NOTIFICACIONES
    public static function foliosPorVencer(int $dias = 10): array
    {
        return DB::select(
            'EXEC SW_NOTIFICACIONES_FOLIOS_POR_VENCER :dias',
            ['dias' => $dias]
        );
    }
}
