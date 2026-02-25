<?php

namespace App\Http\Controllers;

use App\Models\Consulta;
use Illuminate\Http\Request;

class ConsultaController extends Controller
{
    public function getSucursalesXCliente(Request $request)
    {
        $codLegacy = $request->cod_legacy;
        $sucursales = Consulta::getSucursalesPorCliente($codLegacy);
        return response()->json($sucursales);
    }

    public function getFoliosVigentes(Request $request){

        $tipoFolio = $request->tipo_folio;
        $prioridad = $request->prioridad;

        $folios = Consulta::getFoliosVigentes(tipoFolio: $tipoFolio, prioridad: $prioridad);

        return response()->json($folios);
    }


}
