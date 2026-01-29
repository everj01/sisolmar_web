<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use PDO;

class UbicacionController extends Controller
{
    public function departamentos()
    {
        try {
            $data = DB::table('si_solm.DBO.ADMI_DEPARTAMENTO')
                ->select('DEPA_CODIGO as depa_codigo', 'DEPA_DESCRIPCION as depa_descripcion')
                ->orderBy('DEPA_DESCRIPCION')
                ->get();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function provincias($departamento_id)
    {
        $data = DB::table('si_solm.DBO.ADMI_PROVINCIA')
            ->where('DEPA_CODIGO', $departamento_id)
            ->select('PROVI_CODIGO as provi_codigo', 'PROVI_DESCRIPCION as provi_descripcion')
            ->orderBy('PROVI_DESCRIPCION')
            ->get();

        return response()->json($data);
    }

    public function distritos($provincia_id)
    {
        $data = DB::table('si_solm.DBO.ADMI_DISTRITO')
            ->where('PROVI_CODIGO', $provincia_id)
            ->select('DIST_CODIGO as dist_codigo', 'DIST_DESCRIPCION as dist_descripcion')
            ->orderBy('DIST_DESCRIPCION')
            ->get();

        return response()->json($data);
    }
}
