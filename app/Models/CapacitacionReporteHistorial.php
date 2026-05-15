<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;

class CapacitacionReporteHistorial
{
    public static function crearReporte(array $data): int
    {
        $existing = DB::connection('sqlsrv')
            ->table('sw_capacitacion_reportes_historial')
            ->where('nombre_archivo', $data['nombre_archivo'])
            ->where('habilitado', 1)
            ->first();

        if ($existing) {
            return (int) $existing->id;
        }

        $pdfHex = $data['archivo_pdf'] !== null
            ? 'CONVERT(VARBINARY(MAX), 0x' . bin2hex($data['archivo_pdf']) . ')'
            : 'NULL';

        $excelHex = $data['archivo_excel'] !== null
            ? 'CONVERT(VARBINARY(MAX), 0x' . bin2hex($data['archivo_excel']) . ')'
            : 'NULL';

        $result = DB::connection('sqlsrv')->select(
            "INSERT INTO sw_capacitacion_reportes_historial
                (nombre_archivo, descripcion, archivo_pdf, archivo_excel, fecha_creacion, fecha_actualizacion, habilitado)
             OUTPUT INSERTED.id
             VALUES
                (?, ?, {$pdfHex}, {$excelHex}, GETDATE(), GETDATE(), 1)",
            [
                $data['nombre_archivo'],
                $data['descripcion'] ?? '',
            ]
        );

        return (int) $result[0]->id;
    }

    public static function obtenerReportesHabilitados()
    {
        return DB::connection('sqlsrv')
            ->table('sw_capacitacion_reportes_historial')
            ->orderBy('fecha_creacion', 'desc')
            ->get();
    }
}
