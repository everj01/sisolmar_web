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

    public static function pendientesEtapa2()
    {
        // SIN CACHÉ: Lo ejecutamos en vivo para evitar datos pegados
        try {
            $qAnual = DB::select("EXEC SW_LISTAR_PERSONAL_DJ_MIGRACION_ETAPA2_VIGENTES_DNI '0', '01', '00', '00', NULL, 'anual'");
            $qDemanda = DB::select("EXEC SW_LISTAR_PERSONAL_DJ_MIGRACION_ETAPA2_VIGENTES_DNI '0', '01', '00', '00', NULL, 'demanda'");
        } catch (\Exception $e) {
            $qAnual = DB::select("EXEC SW_LISTAR_PERSONAL_DJ_MIGRACION_ETAPA2_VIGENTES_DNI '00', '00', '00', '00', NULL, 'anual'");
            $qDemanda = DB::select("EXEC SW_LISTAR_PERSONAL_DJ_MIGRACION_ETAPA2_VIGENTES_DNI '00', '00', '00', '00', NULL, 'demanda'");
        }

        // Inyectamos las columnas para ambos
        $resAnual = array_map(function($i) { $i->tipo_actualizacion = 'anual'; return $i; }, $qAnual);
        $resDemanda = array_map(function($i) { $i->tipo_actualizacion = 'demanda'; return $i; }, $qDemanda);

        $resultados = array_merge($resAnual, $resDemanda);
        $conteos = [];

        foreach ($resultados as $item) {
            $row = array_change_key_case((array) $item, CASE_UPPER);
            $estado = strtoupper(trim($row['VERIFICADO_CAMBIO'] ?? $row['MIGRADO'] ?? ''));
            $tipoPersonal = strtoupper(trim($row['TIPOPER'] ?? $row['TIPO_PER'] ?? ''));

            if ($estado !== 'SI' && $estado !== 'VERIFICADO' && !str_contains($tipoPersonal, 'ESPECIAL')) {
                $sucursal = trim($row['SUCURSAL'] ?? 'SIN SUCURSAL');
                if (!isset($conteos[$sucursal])) {
                    $conteos[$sucursal] = 0;
                }
                $conteos[$sucursal]++;
            }
        }

        ksort($conteos);
        return $conteos;
    }

public static function obtenerDemandasNuevas()
    {
        $columnaPersonal = 'codigoPers'; 

        try {
            return DB::table('sw_dj_notificaciones_act as n')
                ->join('sw_MIGRA_PERSONAL as p', function ($join) use ($columnaPersonal) {
                    $join->whereRaw("
                        (TRY_CAST(n.$columnaPersonal AS INT) = TRY_CAST(p.CODI_PERS AS INT) AND TRY_CAST(n.$columnaPersonal AS INT) IS NOT NULL)
                        OR
                        (n.$columnaPersonal COLLATE DATABASE_DEFAULT = p.CODI_PERS COLLATE DATABASE_DEFAULT AND TRY_CAST(n.$columnaPersonal AS INT) IS NULL)
                    ");
                })
                ->select(
                    'n.id', 
                    DB::raw("CONCAT(p.APEL_1 COLLATE DATABASE_DEFAULT, ' ', p.APEL_2 COLLATE DATABASE_DEFAULT, ' ', p.NOMB_1 COLLATE DATABASE_DEFAULT, ' ', p.NOMB_2 COLLATE DATABASE_DEFAULT) as personal")
                )
                ->where('n.tipo', 'demanda') // <--- Devolvemos el filtro original aquí
                ->where('p.tipo_actualizacion', 'demanda')
                ->get();
        } catch (\Exception $e) {
            return DB::table('sw_dj_notificaciones_act as n')
                ->join('sw_MIGRA_PERSONAL as p', function ($join) use ($columnaPersonal) {
                    $join->whereRaw("
                        (TRY_CAST(n.$columnaPersonal AS INT) = TRY_CAST(p.CODI_PERS AS INT) AND TRY_CAST(n.$columnaPersonal AS INT) IS NOT NULL)
                        OR
                        (n.$columnaPersonal COLLATE DATABASE_DEFAULT = p.CODI_PERS COLLATE DATABASE_DEFAULT AND TRY_CAST(n.$columnaPersonal AS INT) IS NULL)
                    ");
                })
                ->select(
                    'n.codigo as id', 
                    DB::raw("CONCAT(p.APEL_1 COLLATE DATABASE_DEFAULT, ' ', p.APEL_2 COLLATE DATABASE_DEFAULT, ' ', p.NOMB_1 COLLATE DATABASE_DEFAULT, ' ', p.NOMB_2 COLLATE DATABASE_DEFAULT) as personal")
                )
                ->where('n.tipo', 'demanda') // <--- Y aquí también
                ->where('p.tipo_actualizacion', 'demanda')
                ->get();
        }
    }

    public static function borrarDemanda($id)
    {
        try {
            return DB::table('sw_dj_notificaciones_act')->where('id', $id)->delete();
        } catch (\Exception $e) {
            return DB::table('sw_dj_notificaciones_act')->where('codigo', $id)->delete();
        }
    }

}

