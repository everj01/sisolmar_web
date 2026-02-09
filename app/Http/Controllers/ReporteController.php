<?php

namespace App\Http\Controllers;

use App\Models\FileControl;
use App\Models\Reporte;
use Illuminate\Http\Request;

class ReporteController extends Controller
{

    public function index(){
        $sucursales = FileControl::getSucursales();

        return view('file_control.reportes', compact('sucursales'));
    }

    public function foliosPendientesPorSucursal(Request $request)
    {
        $sucursal = $request->get('sucursal', 0);

        if ($sucursal == '00') {
            $sucursal = 0;
        }

        $rows = Reporte::getFoliosPendientesXSucursal($sucursal);

        $reporte = [];

        foreach ($rows as $row) {

            $nombreSucursal = $row->sucursal;
            $codPersonal    = $row->codPersonal;

            if (!isset($reporte[$nombreSucursal])) {
                $reporte[$nombreSucursal] = [
                    'sucursal' => $nombreSucursal,
                    'personal' => []
                ];
            }

            if (!isset($reporte[$nombreSucursal]['personal'][$codPersonal])) {
                $reporte[$nombreSucursal]['personal'][$codPersonal] = [
                    'codPersonal' => $codPersonal,
                    'personal'    => $row->personal,
                    'documentos'  => []
                ];
            }

            $reporte[$nombreSucursal]['personal'][$codPersonal]['documentos'][] = [
                'documento'       => $row->documento,
                'tipo_folio'      => $row->tipo_folio,
                'fecha_emision'   => $row->fecha_emision,
                'fecha_caducidad' => $row->fecha_caducidad,
            ];
        }

        foreach ($reporte as &$sucursal) {
            $sucursal['personal'] = array_values($sucursal['personal']);
        }

        return response()->json(array_values($reporte));
    }

}
