<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CapacitacionReporteHistorial extends Model
{
    protected $table = 'sw_capacitacion_reportes_historial';

    protected $connection = 'sqlsrv';

    protected $fillable = [
        'nombre_archivo',
        'descripcion',
        'archivo_pdf',
        'archivo_excel',
        'fecha_creacion',
        'fecha_actualizacion',
        'habilitado',
    ];

    protected $casts = [
        'habilitado' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
    ];

    public $timestamps = false;

    public static function crearReporte(array $data): int
    {
        $id = DB::connection('sqlsrv')->table('sw_capacitacion_reportes_historial')->insertGetId([
            'nombre_archivo' => $data['nombre_archivo'],
            'descripcion' => $data['descripcion'] ?? '',
            'archivo_pdf' => $data['archivo_pdf'] ?? null,
            'archivo_excel' => $data['archivo_excel'] ?? null,
            'fecha_creacion' => now(),
            'fecha_actualizacion' => now(),
            'habilitado' => 1,
        ]);

        return $id;
    }

    public static function obtenerReportesHabilitados()
    {
        return DB::connection('sqlsrv')
            ->table('sw_capacitacion_reportes_historial')
            ->where('habilitado', 1)
            ->orderBy('fecha_creacion', 'desc')
            ->get();
    }
}
