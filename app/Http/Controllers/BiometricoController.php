<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BiometricoController extends Controller
{
    public function show($codigo)
    {
        // ── RUTAS BASE ──────────────────────────────────────────────
        $rutaHuellaAntigua = "\\\\192.168.10.2\\Biblioteca_Grafica\\HUELLAS_DIGITALES\\PERSONAL\\";
        //$rutaHuellaNueva   = "\\\\192.168.10.5\\Extranet_2024\\apps\\sisolmar\\storage\\app\\huella\\";
        $rutaHuellaNueva   = "\\\\192.168.10.2\\Biblioteca_Grafica\\DOCUMENTOS_PERS\\DJ_2026\\huella\\";

        $rutaFirmaAntigua  = "\\\\192.168.10.2\\Biblioteca_Grafica\\FIRMAS\\PERSONAL\\";
        //$rutaFirmaNueva    = "\\\\192.168.10.5\\Extranet_2024\\apps\\sisolmar\\storage\\app\\firma\\";
        $rutaFirmaNueva    = "\\\\192.168.10.2\\Biblioteca_Grafica\\DOCUMENTOS_PERS\\DJ_2026\\firma\\";

        $rutaDNIanversoAntigua  = "\\\\192.168.10.2\\Biblioteca_Grafica\\DNI1_1\\";
        $rutaDNIreversoAntigua    = "\\\\192.168.10.2\\Biblioteca_Grafica\\DNI2_1\\";

        $rutaDNIanversoNueva  = "\\\\192.168.10.5\\Extranet_2024\\apps\\sisolmar\\storage\\app\\dni\\anverso\\";
        $rutaDNIreversoNueva    = "\\\\192.168.10.5\\Extranet_2024\\apps\\sisolmar\\storage\\app\\dni\\reverso\\";
        

        // ── EXTENSIONES A BUSCAR ────────────────────────────────────
        $extensiones = ['jpg', 'jpeg', 'png', 'bmp'];

        return response()->json([
            'huella_antigua' => $this->buscarArchivo($rutaHuellaAntigua, $codigo, $extensiones, 'antigua', 'huella'),
            'huella_nueva'   => $this->buscarArchivo($rutaHuellaNueva,   $codigo, $extensiones, 'nueva',   'huella'),
            'firma_antigua'  => $this->buscarArchivo($rutaFirmaAntigua,  $codigo, $extensiones, 'antigua', 'firma'),
            'firma_nueva'    => $this->buscarArchivo($rutaFirmaNueva,    $codigo, $extensiones, 'nueva',   'firma'),
            'dni_anverso_antigua'  => $this->buscarArchivo($rutaDNIanversoAntigua,  $codigo, $extensiones, 'antigua', 'dni'),
            'dni_reverso_antigua'    => $this->buscarArchivo($rutaDNIreversoAntigua,    $codigo, $extensiones, 'antigua',   'dni'),
            'dni_anverso_nuevo'  => $this->buscarArchivo($rutaDNIanversoNueva,  $codigo, $extensiones, 'nueva', 'dni'),
            'dni_reverso_nuevo'    => $this->buscarArchivo($rutaDNIreversoNueva,    $codigo, $extensiones, 'nueva',   'dni'),
        ]);
    }

private function buscarArchivo($ruta, $codigo, $extensiones, $tipo, $biometrico)
{


    $codigoFormateado = str_pad($codigo, 5, '0', STR_PAD_LEFT);

    foreach ($extensiones as $ext) {

        // 1. Con ceros
        $archivo = $ruta . $codigoFormateado . '.' . $ext;

        if (file_exists($archivo) && is_readable($archivo)) {
            return $this->toBase64($archivo, $ext);
        }

        // 2. Sin ceros
        $archivo = $ruta . $codigo . '.' . $ext;

        if (file_exists($archivo) && is_readable($archivo)) {
            return $this->toBase64($archivo, $ext);
        }
    }

    // 👇 DEBUG (CLAVE PARA TU CASO)
    return [
        'error' => 'NO ENCONTRADO',
        'codigo' => $codigo,
        'ruta' => $ruta,
        'intentos' => [
            $ruta . $codigoFormateado . '.jpg',
            $ruta . $codigo . '.jpg',
        ]
    ];
}

private function toBase64($archivo, $ext)
{
    $mimeMap = [
        'jpg'  => 'jpeg',
        'jpeg' => 'jpeg',
        'png'  => 'png',
        'bmp'  => 'bmp',      // algunos navegadores no soportan bmp
    ];

    $mime   = $mimeMap[strtolower($ext)] ?? 'jpeg';
    $base64 = base64_encode(file_get_contents($archivo));

    return 'data:image/' . $mime . ';base64,' . $base64;
}
}