<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

/**
 * ReporteAvancesController
 * ------------------------
 * Ejecuta el SP SW_REPORTE_LISTAR_AVANCE_DJ y devuelve los datos
 * formateados al frontend para el reporte de avances DJ-2026.
 *
 * El SP acepta:
 *   @sucursal     CHAR(2)      '00' = todas  | código de 2 chars
 *   @tipoPersonal VARCHAR(10)  '00' = todos  | 'OPER' | 'ADMIN'
 *
 * Ruta en api.php o web.php:
 *   Route::get('rrhh/reporte-avances', [ReporteAvancesController::class, 'index']);
 */
class ReporteAvancesController extends Controller
{
    // -------------------------------------------------------------------------
    // CONSTANTES
    // -------------------------------------------------------------------------

    /** Valor que el SP interpreta como "sin filtro / traer todos" */
    private const FILTRO_TODOS = '00';

    /**
     * Valores válidos para @tipoPersonal en el SP.
     * El SP filtra internamente por PERS_TIPOTRAB según estos valores.
     * '00' → todos | 'OPER' → operativos | 'ADMIN' → administrativos
     */
    private const TIPOS_VALIDOS = ['OPER', 'ADMIN'];

    // -------------------------------------------------------------------------
    // MÉTODO PRINCIPAL
    // -------------------------------------------------------------------------

    /**
     * Recibe los filtros del modal, llama al SP y retorna JSON formateado.
     *
     * Query params opcionales:
     *   sucursal : código 2 chars (ej. '01') — vacío = todas
     *   tipo     : 'OPER' | 'ADMIN'          — vacío = todos
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Resolver parámetros dentro del try para capturar cualquier error
            $sucursal = $this->resolverSucursal($request->input('sucursal'));
            $tipo     = $this->resolverTipo($request->input('tipo'));

            Log::info('[ReporteAvances] Llamando SP', [
                'sucursal'     => $sucursal,
                'tipoPersonal' => $tipo,
            ]);

            $registros = $this->ejecutarSP($sucursal, $tipo);

            Log::info('[ReporteAvances] SP respondió', [
                'total' => count($registros),
            ]);

            return response()->json(
                $this->formatearRegistros($registros)
            );

        } catch (Throwable $e) {
            Log::error('[ReporteAvances] Error', [
                'mensaje' => $e->getMessage(),
                'linea'   => $e->getLine(),
                'archivo' => $e->getFile(),
            ]);

            // En desarrollo muestra el error real; en producción, mensaje genérico
            $detalle = app()->environment('production')
                ? 'Error interno al generar el reporte.'
                : $e->getMessage();

            return response()->json(['message' => $detalle], 500);
        }
    }

    // -------------------------------------------------------------------------
    // RESOLUCIÓN DE PARÁMETROS
    // -------------------------------------------------------------------------

    /**
     * Devuelve el código de sucursal para el SP.
     * El SP recibe CHAR(2): '00' = todas, o el código exacto de la sucursal.
     * Si el frontend envía vacío o nulo → '00'.
     *
     * @param  string|null  $sucursal
     * @return string
     */
    private function resolverSucursal(?string $sucursal): string
    {
        $valor = trim((string) $sucursal);
        return ($valor !== '') ? $valor : self::FILTRO_TODOS;
    }

    /**
     * Devuelve el tipo de personal para el SP.
     * El SP ya acepta 'OPER' y 'ADMIN' directamente en @tipoPersonal.
     * Si llega vacío, '00', o un valor no reconocido → '00' (todos).
     *
     * @param  string|null  $tipo
     * @return string
     */
    private function resolverTipo(?string $tipo): string
    {
        $valor = strtoupper(trim((string) $tipo));

        if (in_array($valor, self::TIPOS_VALIDOS, true)) {
            return $valor; // 'OPER' o 'ADMIN' directo al SP
        }

        return self::FILTRO_TODOS; // '00' → sin filtro
    }

    // -------------------------------------------------------------------------
    // EJECUCIÓN DEL SP
    // -------------------------------------------------------------------------

    /**
     * Llama al SP con binding posicional.
     *
     * Se usa el nombre completo sisolm_web.dbo.SP porque el SP hace
     * referencias cross-database (si_solm.dbo.*) y Laravel puede estar
     * apuntando a otra BD como default.
     *
     * Si Laravel ya tiene sisolm_web como BD default en database.php,
     * puedes cambiar a solo: 'EXEC SW_REPORTE_LISTAR_AVANCE_DJ ?, ?'
     *
     * @param  string  $sucursal
     * @param  string  $tipo
     * @return array
     */
    private function ejecutarSP(string $sucursal, string $tipo): array
    {
        return DB::select(
            'EXEC sisolm_web.dbo.SW_REPORTE_LISTAR_AVANCE_DJ ?, ?',
            [$sucursal, $tipo]
        );
    }

    // -------------------------------------------------------------------------
    // FORMATEO DE RESPUESTA
    // -------------------------------------------------------------------------

    /**
     * Transforma cada fila del SP al contrato de campos del frontend.
     *
     * Columna SP (alias)    Tipo SP     Campo JSON frontend
     * -------------------   --------    --------------------
     * cod                   CHAR        cod
     * nombreCompleto        VARCHAR     nombres
     * doc                   VARCHAR     doc
     * sucursal              VARCHAR     sucursal
     * tipo                  VARCHAR     tipo  → normalizado a OPER/ADMIN/ESPECIAL
     * djSubido              CHAR(2)     dj_subido             (bool)
     * firmaAct              CHAR(2)     firma_actualizada     (bool)
     * huellaAct             CHAR(2)     huella_actualizada    (bool)
     * fechaAct              DATETIME    ultima_actualizacion  (Y-m-d)
     *
     * @param  array  $registros
     * @return array
     */
    private function formatearRegistros(array $registros): array
    {
        return array_map(function ($fila) {
            return [
                'cod'                  => trim($fila->cod              ?? ''),
                'nombres'              => trim($fila->nombreCompleto   ?? ''),
                'doc'                  => trim($fila->doc              ?? ''),
                'sucursal'             => trim($fila->sucursal         ?? ''),
                'tipo'                 => $this->normalizarTipo($fila->tipo ?? ''),
                'dj_subido'            => $this->esSI($fila->djSubido  ?? ''),
                'firma_actualizada'    => $this->esSI($fila->firmaAct  ?? ''),
                'huella_actualizada'   => $this->esSI($fila->huellaAct ?? ''),
                'ultima_actualizacion' => $this->formatearFecha($fila->fechaAct ?? null),
            ];
        }, $registros);
    }

    /**
     * Convierte el valor de tipo del SP ('OPERATIVO'/'ADMINISTRATIVO')
     * al código corto que usa el frontend ('OPER'/'ADMIN'/'ESPECIAL').
     *
     * @param  string  $tipo
     * @return string
     */
    private function normalizarTipo(string $tipo): string
    {
        return match (strtoupper(trim($tipo))) {
            'OPERATIVO'      => 'OPER',
            'ADMINISTRATIVO' => 'ADMIN',
            default          => 'ESPECIAL',
        };
    }

    /**
     * Evalúa si un campo SI/NO del SP equivale a verdadero.
     *
     * @param  string  $valor
     * @return bool
     */
    private function esSI(string $valor): bool
    {
        return strtoupper(trim($valor)) === 'SI';
    }

    /**
     * Convierte la fecha del SP (DATETIME) a formato Y-m-d para el frontend.
     * Retorna null si el valor está vacío o no es parseable.
     *
     * @param  string|null  $fecha
     * @return string|null
     */
    private function formatearFecha(?string $fecha): ?string
    {
        if (empty($fecha)) {
            return null;
        }

        try {
            return Carbon::parse($fecha)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }
}