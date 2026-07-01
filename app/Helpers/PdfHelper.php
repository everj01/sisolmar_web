<?php

namespace App\Helpers;

class PdfHelper
{
    public static function descargarImagenesLegajo(array $urls): array
    {
        $localPaths = [];
        $carpeta = public_path('temp_legajos');

        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        foreach ($urls as $url) {
            $nombreArchivo = basename($url['ruta']); // 12345.JPG
            $segmentos = explode('/', $url['ruta']);
            $nombreCarpeta = $segmentos[count($segmentos) - 2]; // DNI1, DNI2, etc.

            $subcarpeta = $carpeta . '/' . $nombreCarpeta;
            if (!file_exists($subcarpeta)) {
                mkdir($subcarpeta, 0755, true);
            }
            
            $rutaLocal = $subcarpeta . '/' . $nombreArchivo;

            if (!file_exists($rutaLocal)) {
                try {
                    $contenido = file_get_contents($url['ruta']);
                    if ($contenido !== false) {
                        file_put_contents($rutaLocal, $contenido);
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            $localPaths[] = [
                'codPersonal' => $url['codPersonal'],
                'ruta' => $rutaLocal,
                'ancho' => $url['ancho'],
                'hojas' => $url['hojas'],
                'documento' => $url['documento'],
                'es_formato' => $url['es_formato'],
            ];
        }

        return $localPaths;
    }
}

