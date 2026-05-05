<?php

namespace App\Http\Controllers;

use App\Models\Consulta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ConsultaController extends Controller
{
    public function getSucursalesXCliente(Request $request)
    {
        try {
            $codLegacy = $request->cod_legacy;
            
            if (!$codLegacy) {
                return response()->json([]);
            }

            $sucursales = Consulta::getSucursalesPorCliente($codLegacy);
            return response()->json([
                'status' => 'success',
                'data' => $sucursales
            ]);

        } catch (\Exception $e) {
            Log::error("Error en getSucursalesXCliente: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getFoliosVigentes(Request $request){

        $tipoFolio = $request->tipo_folio;
        $prioridad = $request->prioridad;

        $folios = Consulta::getFoliosVigentes(tipoFolio: $tipoFolio, prioridad: $prioridad);

        return response()->json($folios);
    }

    public function getFoliosPendientes(Request $request)
    {
        // Aumentamos recursos para manejar el universo total de la planilla
        ini_set('memory_limit', '512M');
        set_time_limit(180); // 3 minutos máximo para procesos masivos

        $dni = $request->dni;
        $codCliente = $request->cliente;
        $codSucursal = $request->sucursal;

        $folios = Consulta::listarFoliosPendientes($dni, $codCliente, $codSucursal);

        return response()->json($folios);
    }

    public function getFoliosProximosVencer(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(180);

        $dni = $request->dni;
        $codCliente = $request->cliente;
        $codSucursal = $request->sucursal;
        $persona = $request->persona;
        $servicio = $request->servicio;
        $periodo = $request->periodo;

        if ($periodo == 'custom') {
            $fecha_desde = $request->fecha_desde;
            $fecha_hasta = $request->fecha_hasta;
            $dias = 0;
        } else {
            $dias = (int) $periodo;
            $fecha_desde = null;
            $fecha_hasta = null;
        }

        $folios = Consulta::listarFoliosProximosVencer($dni, $codCliente, $codSucursal, $persona, $servicio, $dias, $fecha_desde, $fecha_hasta);

        return response()->json($folios);
    }
}
