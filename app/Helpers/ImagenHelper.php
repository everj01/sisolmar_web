<?php

namespace App\Helpers;
use Illuminate\Support\Str;

class ImagenHelper
{
    public static function descargarImagenesFormato(string $codPersonal): array
    {
        $localPaths = [];
        $carpeta = public_path('temp_legajos');

        if (!file_exists($carpeta)) {
            mkdir($carpeta, 0755, true);
        }

        $archivos = [
            [
                'url' => "http://190.116.178.163/Biblioteca_Grafica/HUELLAS_DIGITALES/PERSONAL/{$codPersonal}.jpg",
                'tipo' => 'huella',
            ],
            [
                'url' => "http://190.116.178.163/Biblioteca_Grafica/FIRMAS/PERSONAL/{$codPersonal}.jpg",
                'tipo' => 'firma',
            ],
        ];
        
        foreach ($archivos as $archivo) {
            $rutaRemota = $archivo['url'];
            $nombreArchivo = basename($rutaRemota);
        
            // Obtener la ruta relativa desde "Biblioteca_Grafica/"
            $relativePath = Str::after($rutaRemota, 'Biblioteca_Grafica/');
            $subcarpeta = dirname($relativePath);
        
            // Ruta local completa para guardar
            $rutaLocal = $carpeta . '/' . $relativePath;
        
            // Crear subcarpeta si no existe
            if (!file_exists($carpeta . '/' . $subcarpeta)) {
                mkdir($carpeta . '/' . $subcarpeta, 0755, true);
            }
        
            // Guardar el archivo si no existe
            if (!file_exists($rutaLocal)) {
                try {
                    $contenido = file_get_contents($rutaRemota);
                    if ($contenido !== false) {
                        file_put_contents($rutaLocal, $contenido);
                    }
                } catch (\Exception $e) {
                    echo 'Error copiando archivos: ' . $e->getMessage();
                }
            }

            $localPaths[] = [
                'ruta' => $rutaLocal,
                'tipo' => $archivo['tipo'],
                'codPersonal' => $codPersonal,
            ];
        }

        return $localPaths;
        
    }
}

