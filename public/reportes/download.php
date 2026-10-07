<?php
/**
 * Descarga directa de reportes - bypass Laravel para evitar espacio prepended en servidor built-in
 * Uso: /reportes/download.php?file=Reporte_Documentos_Personal_2026-10-06_161828.xlsx
 */

$file = $_GET['file'] ?? '';

// Validar nombre de archivo (seguridad básica)
if (!preg_match('/^Reporte_(Documentos_Personal|Personal_Sin_Foto_DNI)_\d{4}-\d{2}-\d{2}_\d{6}\.xlsx$/', $file)) {
    http_response_code(403);
    exit('Archivo no válido');
}

// Probar múltiples rutas posibles
$possiblePaths = [
    __DIR__ . '/../storage/app/public/reportes/' . $file,
    __DIR__ . '/../../storage/app/public/reportes/' . $file,
    dirname(__DIR__, 2) . '/storage/app/public/reportes/' . $file,
    $_SERVER['DOCUMENT_ROOT'] . '/../storage/app/public/reportes/' . $file,
];

$path = null;
foreach ($possiblePaths as $p) {
    if (file_exists($p)) {
        $path = $p;
        break;
    }
}

// Debug: log paths tried
error_log("download.php: file=$file, tried paths: " . implode(', ', $possiblePaths) . ", found: " . ($path ?? 'none'));

if (!$path) {
    http_response_code(404);
    exit('Reporte no encontrado');
}

// Limpiar buffers de salida para evitar espacio prepended
while (ob_get_level()) {
    ob_end_clean();
}

// Cabeceras
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($path));
header('Cache-Control: public');
header('Accept-Ranges: bytes');

// Enviar archivo
readfile($path);
exit;