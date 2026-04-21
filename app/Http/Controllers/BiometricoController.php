<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BiometricoController extends Controller
{
    public function show($codigo)
    {
        // ── RUTAS BASE ──────────────────────────────────────────────
        $rutaHuellaAntigua = "\\\\192.168.10.2\\Biblioteca_Grafica\\HUELLAS_DIGITALES\\PERSONAL\\";
        $rutaHuellaNueva   = "\\\\192.168.10.5\\Extranet_2024\\apps\\sisolmar\\storage\\app\\huella\\";

        $rutaFirmaAntigua  = "\\\\192.168.10.2\\Biblioteca_Grafica\\FIRMAS\\PERSONAL\\";
        $rutaFirmaNueva    = "\\\\192.168.10.5\\Extranet_2024\\apps\\sisolmar\\storage\\app\\firma\\";

        // ── EXTENSIONES A BUSCAR ────────────────────────────────────
        $extensiones = ['jpg', 'jpeg', 'png', 'bmp'];

        return response()->json([
            'huella_antigua' => $this->buscarArchivo($rutaHuellaAntigua, $codigo, $extensiones, 'antigua', 'huella'),
            'huella_nueva'   => $this->buscarArchivo($rutaHuellaNueva,   $codigo, $extensiones, 'nueva',   'huella'),
            'firma_antigua'  => $this->buscarArchivo($rutaFirmaAntigua,  $codigo, $extensiones, 'antigua', 'firma'),
            'firma_nueva'    => $this->buscarArchivo($rutaFirmaNueva,    $codigo, $extensiones, 'nueva',   'firma'),
        ]);
    }

    private function buscarArchivo($ruta, $codigo, $extensiones, $tipo, $biometrico)
    {
        foreach ($extensiones as $ext) {
            $archivo = $ruta . $codigo . '.' . $ext;
            if (file_exists($archivo)) {
                // Devolver como base64 para mostrar en el modal
                $base64 = base64_encode(file_get_contents($archivo));
                return 'data:image/' . $ext . ';base64,' . $base64;
            }
        }
        return null; // No encontrado
    }
}