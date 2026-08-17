<?php

namespace App\Services;

class FirmaService
{
    public static function toBase64(?string $rutaFirma): ?string
    {
        if (empty($rutaFirma)) {
            return null;
        }

        $rutaUNC = preg_replace('/^file:/', '', $rutaFirma);

        if (!file_exists($rutaUNC)) {
            return null;
        }

        $contenido = file_get_contents($rutaUNC);
        return 'data:image/jpeg;base64,' . base64_encode($contenido);
    }
}
