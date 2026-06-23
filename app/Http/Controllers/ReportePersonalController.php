<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportePersonalController extends Controller
{
    /**
     * GET /api/reporte-personal-datos-generales
     *
     * Params:
     *   codSucursal   → SISO_SUCURSAL.SUCU_CODIGO  (vacío = todas)
     *   tipoPer       → '01'|'02'|'03'|'05'|'06'|'' (PERS_TIPOTRAB)
     *   vigente       → '1' PERS_VIGENCIA='SI' | '0' PERS_VIGENCIA='NO'
     *   apPaterno     → LIKE sobre APEL_1
     *   docIdentidad  → LIKE sobre NRO_DOCU_IDEN
     *
     * Joins:
     *   SISO_SUCURSAL       → SUCU_ABREVIATURA   (nombre sucursal)
     *   ADMI_TIPO_PERSONAL  → TIPE_DESCRIPCION   (tipo trabajador)
     *   CARGOS              → DESC_CARGO         (descripción cargo)
     *   ADMI_PAIS           → PAIS_DESCRIPCION   (nacionalidad)
     *   TIPO_DOCUMENTO      → NEMO               (DNI, CE, etc.)
     *
     * Subqueries:
     *   CONTRATOS_PERSONAL  → FECH_FIN_CONT      (último por USUA_FECHA_REG)
     *   REDO_CERTIFICADO    → CERT_FECHA_CADUCA  (último por FEC_REG, REQU_CODIGO=24)
     */
    public function proxyImagen(Request $request)
    {
        $url = trim($request->get('url', ''));
    
        // Validar que la URL sea del servidor permitido
        $dominioPermitido = 'http://190.116.178.163/Biblioteca_Grafica/';
    
        if (empty($url) || !str_starts_with($url, $dominioPermitido)) {
            return response()->json(['error' => 'URL no permitida.'], 403);
        }
    
        try {
            // Usar cURL para descargar la imagen desde el servidor interno
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
    
            $contenido  = curl_exec($ch);
            $httpCode   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);
    
            // Si no existe el archivo en el servidor
            if ($httpCode === 404 || $contenido === false || empty($contenido)) {
                return response()->json(['error' => 'Archivo no encontrado.'], 404);
            }
    
            if ($httpCode !== 200) {
                return response()->json(['error' => "Error al obtener el archivo (HTTP {$httpCode})."], 502);
            }
    
            return response($contenido, 200, [
                'Content-Type'        => $contentType ?? 'image/jpeg',
                'Content-Disposition' => 'inline',
                'Cache-Control'       => 'no-store',
            ]);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    public function datosGenerales(Request $request)
    {
        $codSucursal  = trim($request->get('codSucursal',  ''));
        $tipoPer      = trim($request->get('tipoPer',      ''));
        $vigente      = $request->get('vigente', '1');
        $apPaterno    = trim($request->get('apPaterno',    ''));
        $docIdentidad = trim($request->get('docIdentidad', ''));

        // ── Subquery: último contrato por persona ─────────────
        // Trae FECH_FIN_CONT del registro con mayor USUA_FECHA_REG
        $subContrato = DB::raw("(
            SELECT TOP 1 cp.FECH_FIN_CONT
            FROM si_solm.dbo.CONTRATOS_PERSONAL cp
            WHERE cp.CODI_PERS = p.CODI_PERS
             AND ESTA_CONT = 1
            ORDER BY cp.USUA_FECHA_REG DESC
        ) as FIN_CONTRATO");

        // ── Subquery: último EMO (REQU_CODIGO=24) por persona ─
        // Trae CERT_FECHA_CADUCA del registro con mayor FEC_REG
        $subEmo = DB::raw("(
            SELECT TOP 1 rc.CERT_FECHA_CADUCA
            FROM si_solm.dbo.REDO_CERTIFICADO rc
            WHERE rc.CODI_PERS   = p.CODI_PERS
              AND rc.REQU_CODIGO = 24 AND CERT_VIGENCIA = 'SI'
            ORDER BY rc.FEC_REG DESC
        ) as CADUCA_EMO");

        $query = DB::table('si_solm.dbo.PERSONAL as p')
            ->leftJoin('si_solm.dbo.SISO_SUCURSAL as s',
                's.SUCU_CODIGO', '=', 'p.SUCU_CODIGO')
            ->leftJoin('si_solm.dbo.ADMI_TIPO_PERSONAL as tp',
                'tp.TIPE_CODIGO', '=', 'p.PERS_TIPOTRAB')
            ->leftJoin('si_solm.dbo.CARGOS as c',
                'c.CODI_CARG', '=', 'p.CODI_CARG')
            ->leftJoin('si_solm.dbo.ADMI_PAIS as pa',
                'pa.PAIS_CODIGO', '=', 'p.NACIONALIDAD')
            ->leftJoin('si_solm.dbo.TIPO_DOCUMENTO as td',
                'td.CODI_TIPO_DOCU', '=', 'p.CODI_TIPO_DOCU')
            ->select([
                'p.CODI_PERS',
                'p.APEL_1',
                'p.APEL_2',
                'p.NOMB_1',
                'p.NOMB_2',
                'p.NRO_DOCU_IDEN',
                'p.PERS_FECHCADUCADNI',
                'p.PERS_SEXO',
                'p.FECH_NACI',
                'p.FECH_INGRE',
                'p.PERS_EMAIL',
                'p.PERS_TELEFONO',
                'p.DIRECCION',
                'p.PERS_TIPOTRAB',
                'p.SUCU_CODIGO',
                DB::raw("ISNULL(s.SUCU_ABREVIATURA, p.SUCU_CODIGO)    as SUCURSAL"),
                DB::raw("ISNULL(tp.TIPE_DESCRIPCION, p.PERS_TIPOTRAB) as TIPO_DESCRIPCION"),
                DB::raw("ISNULL(c.DESC_CARGO, p.CODI_CARG)            as CARGO_DESC"),
                DB::raw("ISNULL(pa.PAIS_DESCRIPCION, p.NACIONALIDAD)  as PAIS_DESC"),
                DB::raw("ISNULL(td.NEMO, p.CODI_TIPO_DOCU)            as TIPO_DOC_NEMO"),
                $subContrato,
                $subEmo,
            ]);

        // ── Vigente ───────────────────────────────────────────
        $query->where('p.PERS_VIGENCIA', $vigente === '1' ? 'SI' : 'NO');

        // ── Sucursal ──────────────────────────────────────────
        if ($codSucursal !== '') {
            $query->where('p.SUCU_CODIGO', $codSucursal);
        }

        // ── Tipo personal ─────────────────────────────────────
        if ($tipoPer !== '') {
            $query->whereIn('p.PERS_TIPOTRAB', $this->mapTipoPerACodigos($tipoPer));
        }

        // ── Apellido Paterno ──────────────────────────────────
        if ($apPaterno !== '') {
            $query->where('p.APEL_1', 'like', '%' . $apPaterno . '%');
        }

        // ── Nro Documento ─────────────────────────────────────
        if ($docIdentidad !== '') {
            $query->where('p.NRO_DOCU_IDEN', 'like', '%' . $docIdentidad . '%');
        }

        $query->orderBy('p.APEL_1')
              ->orderBy('p.APEL_2')
              ->orderBy('p.NOMB_1');

        $personal = $query->get();

        $data = $personal->map(fn($r) => [
            'codPersonal'  => trim($r->CODI_PERS),
            'codSucursal'  => trim($r->SUCU_CODIGO      ?? ''),
            'sucursal'     => trim($r->SUCURSAL          ?? '—'),
            'nombre'       => trim(
                ($r->NOMB_1 ?? '') . ' ' .
                ($r->NOMB_2 ?? '') . ' ' .
                ($r->APEL_1 ?? '') . ' ' .
                ($r->APEL_2 ?? '')
            ),
            'nacionalidad' => trim($r->PAIS_DESC         ?? '—'),
            'tipoDoc'      => trim($r->TIPO_DOC_NEMO     ?? '—'),
            'nroDocIden'   => trim($r->NRO_DOCU_IDEN     ?? '—'),
            'cadDni'       => $this->formatFecha($r->PERS_FECHCADUCADNI),
            'sexo'         => trim($r->PERS_SEXO         ?? '—'),
            'edad'         => $this->calcularEdad($r->FECH_NACI),
            'email'        => trim($r->PERS_EMAIL        ?? '—'),
            'telefono'     => trim($r->PERS_TELEFONO     ?? '—'),
            'direccion'    => trim($r->DIRECCION         ?? '—'),
            'fechIngreso'  => $this->formatFecha($r->FECH_INGRE),
            'cargo'        => trim($r->CARGO_DESC        ?? '—'),
            'tipoPer'      => trim($r->TIPO_DESCRIPCION  ?? '—'),
            'caducaEmo'    => $this->formatFecha($r->CADUCA_EMO),
            'finContrato'  => $this->formatFecha($r->FIN_CONTRATO),
        ]);

        return response()->json([
            'success' => true,
            'total'   => $data->count(),
            'data'    => $data,
        ]);
    }

    // ── Helpers privados ──────────────────────────────────────

    private function mapTipoPerACodigos(string $valor): array
    {
        return match (strtoupper($valor)) {
            'OP', 'OPERATIVO'      => ['01', '03'],
            'AD', 'ADMINISTRATIVO' => ['02', '05'],
            default                => [$valor],
        };
    }

    private function formatFecha($valor): string
    {
        if (empty($valor)) return '—';
        $str = trim((string) $valor);
        if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $str, $m))
            return "{$m[3]}/{$m[2]}/{$m[1]}";
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $str, $m))
            return "{$m[3]}/{$m[2]}/{$m[1]}";
        return $str;
    }

    private function calcularEdad($fechNaci): string
    {
        if (empty($fechNaci)) return '—';
        try {
            $naci = new \DateTime(trim((string) $fechNaci));
            return (string) (new \DateTime())->diff($naci)->y;
        } catch (\Exception) {
            return '—';
        }
    }
}