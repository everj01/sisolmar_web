<?php

namespace App\Http\Controllers;


use App\Helpers\PdfHelper;
use App\Helpers\ImagenHelper;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Http\Request;
use App\Models\NotificacionModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;


class NotificacionController extends Controller{
    public function deleteNotificacion(Request $request){
        $codigo = $request->input('codigo');
        $habilitado = 0;

        $data = [
            "habilitado" => $habilitado,
            "listo" =>  $habilitado == 0 ? 1 : 0
        ];

        $update = NotificacionModel::updateNotificacion($codigo, $data);
       
        if ($update) {
            return response()->json(['success' => true, 'message' => 'Folio guardado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'Error al guardar el folio'], 500);
        }
    }
}




