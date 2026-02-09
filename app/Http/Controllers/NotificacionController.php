<?php

namespace App\Http\Controllers;


use App\Helpers\PdfHelper;
use App\Helpers\ImagenHelper;
use App\Mail\AlertaCaducidadMail;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Http\Request;
use App\Models\NotificacionModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Mail;
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

    // METODO NOTIFICACIONES
    public function foliosPorVencer()
    {
        $dias = 10;

        $rows = NotificacionModel::foliosPorVencer($dias);

        $notificaciones = [];

        foreach ($rows as $row) {
            $cod = $row->codPersonal;

            if (!isset($notificaciones[$cod])) {
                $notificaciones[$cod] = [
                    'codPersonal' => $cod,
                    'personal'    => $row->personal,
                    'documentos'  => []
                ];
            }

            $notificaciones[$cod]['documentos'][] = [
                'folio'          => $row->documento,
                'fecha'          => $row->fecha_caducidad,
                'dias_restantes' => $row->dias_restantes,
            ];
        }

        return response()->json(array_values($notificaciones));
    }

    // CORREO
    public function enviarAlertasCaducidad()
    {
        $dias = 10;

        $rows = NotificacionModel::foliosPorVencer($dias);

        if (empty($rows)) {
            return response()->json([
                'message' => 'No hay documentos por vencer'
            ]);
        }

        $personas = [];

        foreach ($rows as $row) {

            $cod = $row->codPersonal;

            if (!isset($personas[$cod])) {
                $personas[$cod] = [
                    'codPersonal' => $cod,
                    'nombre'      => $row->personal,
                    'email'       => $row->email ?? null,
                    'documentos'  => []
                ];
            }

            $personas[$cod]['documentos'][] = [
                'nombre'          => $row->documento,
                'fecha_caducidad' => date('d/m/Y', strtotime($row->fecha_caducidad)),
                'dias_restantes'  => $row->dias_restantes,
            ];
        }

        foreach ($personas as $persona) {

            if (empty($persona['email'])) {
                continue;
            }

            Mail::to($persona['email'])
                ->send(new AlertaCaducidadMail([
                    'nombre_personal' => $persona['nombre'],
                    'nombre_empresa'  => 'SISOLMAR',
                    'documentos'      => $persona['documentos']
                ]));
        }

        return response()->json([
            'message' => 'Correos enviados correctamente',
            'total'   => count($personas)
        ]);
    }
}




