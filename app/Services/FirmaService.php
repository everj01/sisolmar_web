<?php

namespace App\Services;

class FirmaService
{
    public static function toUrl(?string $rutaFirma): ?string
    {
        if (empty($rutaFirma)) {
            return null;
        }

        if (str_starts_with($rutaFirma, 'http')) {
            return $rutaFirma;
        }

        $rutaNormalizada = str_replace(['\\', '/'], '/', $rutaFirma);
        $nombreArchivo = basename($rutaNormalizada);

        if ($nombreArchivo === '') {
            return null;
        }

        return 'http://190.116.178.163/Biblioteca_Grafica/FIRMAS/PERSONAL/' . $nombreArchivo;
    }
}