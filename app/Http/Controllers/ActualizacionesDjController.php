<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ActualizacionesDjController extends Controller
{
    public function index(Request $request)
    {
        return view('file_control.gestionar_actualizaciones_dj');
    }

    /**
     * Paso 1: Obtener el personal filtrado por la fecha de corte
     */
    public function getPersonalPorCorte(Request $request)
    {
        $fechaCorte = $request->input('fecha_corte');
        // Usamos el usuario logueado por si el SP necesita filtrar sus sucursales permitidas
        $usuario = Auth::user() ? Auth::user()->usuario : '0'; 

        try {
            // Ejecutamos el NUEVO SP pasando los parámetros en orden (Usuario, Empresa, Vigencia, FechaCorte)
            $resultadosSp = DB::select(
                'EXEC sisolm_web.dbo.SW_LISTAR_PERSONAL_CORTE_DJ @usuario = ?, @codEmpresa = ?, @vigencia = ?, @fechaCorte = ?', 
                [$usuario, '01', 'SI', $fechaCorte]
            );

            // Mapeamos las columnas que devuelve el SP para que el JS las reciba exactamente como las espera
            $personal = array_map(function($emp) {
                return [
                    'codigo'    => $emp->codPersonal,
                    'nombres'   => trim($emp->nombres . ' ' . $emp->NOMB_2),
                    'apellidos' => trim($emp->apellido1 . ' ' . $emp->apellido2),
                    'cargo'     => $emp->cargo,
                    'tipo'      => $emp->tipoPer,  // NUEVO
                    'dni'       => $emp->dni,      // NUEVO
                    'sucursal'  => $emp->sucursal  // NUEVO
                ];
            }, $resultadosSp);

            return response()->json([
                'success' => true,
                'data' => $personal
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener personal del SP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Paso 2: Guardar el Periodo y su relación con el Personal (Maestro-Detalle)
     */
    public function storePeriodo(Request $request)
    {
        // Obtenemos el usuario logueado para la auditoría (si no hay, ponemos SISTEMA)
        $usuario = Auth::user() ? Auth::user()->usuario : 'SISTEMA';

        // Iniciamos la transacción (Si algo falla, no se guarda nada a medias)
        DB::beginTransaction();

        try {
            // 1. Guardar en la tabla maestra (sw_dj_periodos) y obtener el ID generado
            $idPeriodo = DB::table('sw_dj_periodos')->insertGetId([
                'nombre_periodo' => $request->input('nombre_periodo'),
                'fecha_inicio'   => $request->input('fecha_inicio'),
                'fecha_fin'      => $request->input('fecha_fin'),
                'fecha_corte'    => $request->input('fecha_corte'),
                'anio'           => $request->input('anio_periodo'),
                'cod_empresa'    => $request->input('cod_empresa', '01'),
                'creado_por'     => $usuario,
                'fecha_creacion' => now(), // Helper de Laravel para la fecha y hora actual
                'habilitado'     => 1
            ]);

            // 2. Preparar la tabla relacional (sw_dj_periodo_personal)
            $codigosPersonal = $request->input('personal', []); // Recibimos el array de códigos [ 'EMP01', 'EMP02' ]
            $dataRelacion = [];

            foreach ($codigosPersonal as $codigo) {
                $dataRelacion[] = [
                    'id_periodo'      => $idPeriodo,
                    'codigo_personal' => $codigo,
                    'creado_por'      => $usuario,
                    'fecha_creacion'  => now(),
                    'habilitado'      => 1
                ];
            }

            // 3. Insertar todo el personal por lotes (mucho más rápido que un insert por cada empleado)
            if (count($dataRelacion) > 0) {
                DB::table('sw_dj_periodo_personal')->insert($dataRelacion);
            }

            // Confirmamos la transacción
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Periodo y personal guardados exitosamente.'
            ]);

        } catch (\Exception $e) {
            // Si hay un error (ej. faltan campos o caída de red), deshacemos los cambios
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el periodo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPeriodos()
    {
        try {
            $periodos = DB::table('sw_dj_periodos')
                ->where('habilitado', 1)
                ->orderBy('fecha_creacion', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $periodos
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener periodos: ' . $e->getMessage()
            ], 500);
        }
    }

}