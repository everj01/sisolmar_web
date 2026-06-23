<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use PDO;

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

        $pdo = DB::connection('sqlsrv')->getPdo();

        $stmt = $pdo->prepare(
            "INSERT INTO sw_capacitacion_reportes_historial
            (nombre_archivo, descripcion, tipo_archivo, archivo_pdf, archivo_excel, fecha_creacion, fecha_actualizacion, habilitado)
         OUTPUT INSERTED.id
         VALUES (?, ?, ?, CONVERT(VARBINARY(MAX), ?), CONVERT(VARBINARY(MAX), ?), GETDATE(), GETDATE(), 1)"
        );

        $nombreArchivo = $data['nombre_archivo'];
        $descripcion = $data['descripcion'] ?? '';
        $tipoArchivo = $data['tipo_archivo'] ?? null;
        $pdfBinario = $data['archivo_pdf_binario'] ?? null;
        $excelBinario = $data['archivo_excel_binario'] ?? null;

        $stmt->bindParam(1, $nombreArchivo);
        $stmt->bindParam(2, $descripcion);
        $stmt->bindParam(3, $tipoArchivo);

        if (!empty($pdfBinario)) {
            $stmt->bindParam(4, $pdfBinario, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
        } else {
            $stmt->bindValue(4, null, PDO::PARAM_NULL);
        }

        if (!empty($excelBinario)) {
            $stmt->bindParam(5, $excelBinario, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
        } else {
            $stmt->bindValue(5, null, PDO::PARAM_NULL);
        }

        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_OBJ);

        return (int) $row->id;
    }

    public static function obtenerReportesHabilitados()
    {
        return DB::connection('sqlsrv')
            ->table('sw_capacitacion_reportes_historial')
            ->select([
                'id',
                'nombre_archivo',
                'descripcion',
                'tipo_archivo',
                'fecha_creacion',
                'fecha_actualizacion',
                'habilitado',
            ])
            ->orderBy('fecha_creacion', 'desc')
            ->get();
    }
}