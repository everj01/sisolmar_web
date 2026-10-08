<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportePersonalController extends Controller
{
    /**
     * Roles que pueden descargar el reporte de documentos:
     *   17 → Admins RRHH   ·   11 → Super Usuario
     * (catálogo: si_solm.dbo.sw_roles)
     */
    private const ROLES_REPORTE_DOCUMENTOS = [17, 11];

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

    /**
     * GET /api/reporte/personal-documentos   (?refrescar=1&solo_faltantes=1)
     *
     * Devuelve un .xlsx con el personal vigente e indica:
     *   · Foto     → Sí / No / Sin verificar
     *   · DNI      → Completo / Solo anverso / Solo reverso / No / Sin verificar
     *   · Quién lo registró (y cuándo) y quién lo modificó (y cuándo)
     *
     * El estado de los archivos se consulta contra el servidor de imágenes
     * (Biblioteca_Grafica) y se guarda en caché de archivos, de modo que:
     *   · el primer escaneo tarda ~105 s y queda guardado 6 horas,
     *   · si la petición se corta a mitad se retoma donde quedó,
     *   · nada de esto toca la base de datos.
     */
    public function documentosPendientes(Request $request)
    {
        // El reporte expone el DNI y la trazabilidad de todo el personal vigente:
        // solo ADMINS RRHH (17) y SUPERUSUARIO (11). Mismo criterio que el @if
        // del botón en gestion_dj.blade.php, para que ambos siempre concuerden.
        if (!in_array((int) session('tipo_rol'), self::ROLES_REPORTE_DOCUMENTOS, true)) {
            abort(403, 'Este reporte es exclusivo para ADMINS RRHH y SUPERUSUARIO.');
        }

        @set_time_limit(0);

        $forzar = in_array((string) $request->query('refrescar'), ['1', 'true'], true);
        $soloFaltantes = in_array((string) $request->query('solo_faltantes'), ['1', 'true'], true);

        // ── 1. Población ──────────────────────────────────────
        // Réplica de SW_LISTAR_PERSONAL_DJ_2026_GESTION (la misma que
        // alimenta la pantalla de Gestión DJ): todos los vigentes,
        // todos los tipos de personal y todas las sucursales.
        $sql = <<<'SQL'
            SELECT
                LTRIM(RTRIM(p.CODI_PERS))  AS CODI_PERS,
                p.APEL_1, p.APEL_2, p.NOMB_1, p.NOMB_2,
                p.NRO_DOCU_IDEN,
                ISNULL(s.SUCU_ABREVIATURA, p.SUCU_CODIGO)    AS SUCURSAL,
                ISNULL(tp.TIPE_DESCRIPCION, p.PERS_TIPOTRAB) AS TIPO_DESCRIPCION,
                ISNULL(c.DESC_CARGO, p.CODI_CARG)            AS CARGO_DESC,
                p.USUA_CODIGO_REG, p.USUA_FECHA_REG,
                p.USUA_CODIGO_MOD, p.USUA_FECHA_MOD,
                reg.NOMBRE_USUARIO AS REG_NOMBRE,
                mod.NOMBRE_USUARIO AS MOD_NOMBRE
            FROM si_solm.dbo.PERSONAL p
            INNER JOIN si_solm.dbo.SISO_SUCURSAL s
                    ON s.SUCU_CODIGO = p.SUCU_CODIGO
            LEFT JOIN si_solm.dbo.ADMI_TIPO_PERSONAL tp
                    ON tp.TIPE_CODIGO = p.PERS_TIPOTRAB
            LEFT JOIN si_solm.dbo.CARGOS c
                    ON c.CODI_CARG = p.CODI_CARG
            OUTER APPLY (
                SELECT TOP 1
                       LTRIM(RTRIM(ISNULL(u.NOMB_1,  ''))) + ' ' +
                       LTRIM(RTRIM(ISNULL(u.NOMB_2,  ''))) + ' ' +
                       LTRIM(RTRIM(ISNULL(u.APEL_1,  ''))) + ' ' +
                       LTRIM(RTRIM(ISNULL(u.APEL_2,  ''))) AS NOMBRE_USUARIO
                FROM si_solm.dbo.USUARIOS u
                WHERE u.LOGIN = LTRIM(RTRIM(p.USUA_CODIGO_REG))
            ) reg
            OUTER APPLY (
                SELECT TOP 1
                       LTRIM(RTRIM(ISNULL(u.NOMB_1,  ''))) + ' ' +
                       LTRIM(RTRIM(ISNULL(u.NOMB_2,  ''))) + ' ' +
                       LTRIM(RTRIM(ISNULL(u.APEL_1,  ''))) + ' ' +
                       LTRIM(RTRIM(ISNULL(u.APEL_2,  ''))) AS NOMBRE_USUARIO
                FROM si_solm.dbo.USUARIOS u
                WHERE u.LOGIN = LTRIM(RTRIM(p.USUA_CODIGO_MOD))
            ) mod
            WHERE p.PERS_VIGENCIA = 'SI'
              AND p.EMPR_CODIGO   = '01'
            ORDER BY p.APEL_1, p.APEL_2, p.NOMB_1
            SQL;

        $personal = DB::select($sql);

        $filas   = [];
        $codigos = [];

        foreach ($personal as $r) {
            $codi = trim((string) ($r->CODI_PERS ?? ''));
            if ($codi === '') {
                continue;
            }

            $codigos[]   = $codi;
            $filas[$codi] = [
                'codi'     => $codi,
                'nombre'   => trim(implode(' ', array_filter([
                    trim((string) ($r->APEL_1 ?? '')),
                    trim((string) ($r->APEL_2 ?? '')),
                    trim((string) ($r->NOMB_1 ?? '')),
                    trim((string) ($r->NOMB_2 ?? '')),
                ], fn ($v) => $v !== ''))),
                'doc'      => trim((string) ($r->NRO_DOCU_IDEN ?? '')),
                'sucursal' => trim((string) ($r->SUCURSAL         ?? '')),
                'tipo'     => trim((string) ($r->TIPO_DESCRIPCION ?? '')),
                'cargo'    => trim((string) ($r->CARGO_DESC       ?? '')),
                'reg'      => $this->usuarioLegible($r->USUA_CODIGO_REG, $r->REG_NOMBRE),
                'freg'     => $this->formatFechaHora($r->USUA_FECHA_REG),
                'mod'      => $this->usuarioLegible($r->USUA_CODIGO_MOD, $r->MOD_NOMBRE),
                'fmod'     => $this->formatFechaHora($r->USUA_FECHA_MOD),
            ];
        }

        // ── 2. Escaneo del servidor de archivos (con caché) ───
        $estado = $this->estadoDocumentos($codigos, $forzar);

        // ── 3. Enriquecer filas + estadísticas ────────────────
        $fotoOk   = 0;
        $dniOk    = 0;
        $faltan   = 0;

        foreach ($filas as $codi => $fila) {
            $doc = $estado['datos'][$codi] ?? [
                'foto' => 'desconocido', 'anverso' => 'desconocido', 'reverso' => 'desconocido',
            ];

            $foto = $this->estadoFoto($doc['foto']);
            $dni  = $this->estadoDni($doc['anverso'], $doc['reverso']);

            $filas[$codi]['foto_txt']  = $foto;
            $filas[$codi]['dni_txt']   = $dni;
            $filas[$codi]['incompleto'] = ($foto !== 'Sí') || ($dni !== 'Completo');

            if ($foto === 'Sí') {
                $fotoOk++;
            }
            if ($dni === 'Completo') {
                $dniOk++;
            }
            if ($filas[$codi]['incompleto']) {
                $faltan++;
            }
        }

        $stats = [
            'generado' => date('d/m/Y H:i:s'),
            'vigentes' => count($filas),
            'foto_ok'  => $fotoOk,
            'dni_ok'   => $dniOk,
            'faltan'   => $faltan,
            'scan_at'  => $estado['scan_at'] ? $this->formatFechaHora($estado['scan_at']) : '—',
        ];

        if ($soloFaltantes) {
            $filas = array_values(array_filter(
                $filas,
                static fn (array $fila): bool => $fila['incompleto'] === true
            ));
        }

        // ── 4. Excel ──────────────────────────────────────────
        $nombreArchivo = ($soloFaltantes
            ? 'Reporte_Personal_Sin_Foto_DNI_'
            : 'Reporte_Documentos_Personal_') . date('Y-m-d_His') . '.xlsx';
        $rutaArchivo   = $this->generarExcelDocumentos(array_values($filas), $stats);

        // Copiar a storage/app/public/reportes para servir vía URL directa
        // (evita el problema de espacio prepended en respuestas AJAX del servidor built-in)
        $publicDir = storage_path('app/public/reportes');
        if (!is_dir($publicDir)) {
            @mkdir($publicDir, 0755, true);
        }
        $rutaPublica = $publicDir . DIRECTORY_SEPARATOR . $nombreArchivo;
        copy($rutaArchivo, $rutaPublica);
        @unlink($rutaArchivo);

        // Retornar URL para descarga directa via download.php (bypass Laravel)
        // Esto evita el problema de espacio prepended en el servidor built-in (php artisan serve)
        return response()->json([
            'url' => url('reportes/download.php?file=' . $nombreArchivo),
            'nombre' => $nombreArchivo,
        ]);
    }

    public function descargarReporte(Request $request, string $archivo)
    {
        $ruta = storage_path('app/public/reportes/' . $archivo);

        if (!file_exists($ruta)) {
            abort(404, 'Reporte no encontrado');
        }

        // Verificar que el archivo sea un reporte válido (seguridad básica)
        if (!preg_match('/^Reporte_(Documentos_Personal|Personal_Sin_Foto_DNI)_\d{4}-\d{2}-\d{2}_\d{6}\.xlsx$/', $archivo)) {
            abort(403, 'Archivo no válido');
        }

        return response()->download($ruta, $archivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Escaneo del servidor de archivos ───────────────────────

    private const DOC_CACHE_KEY = 'reporte_documentos_estado_v1';
    private const DOC_LOTE      = 100;
    private const DOC_BASE      = 'http://190.116.178.163/Biblioteca_Grafica';

    /**
     * Devuelve ['datos' => [CODI => ['foto'|'anverso'|'reverso' => si|no|desconocido]],
     *           'scan_at' => fecha, 'completo' => bool]
     *
     * Solo consulta los códigos que aún no fueron escaneados y guarda el
     * avance en cada lote, de forma que una petición interrumpida se retoma.
     */
    private function estadoDocumentos(array $codigos, bool $forzar): array
    {
        if ($forzar) {
            Cache::forget(self::DOC_CACHE_KEY);
        }

        $estado = Cache::get(self::DOC_CACHE_KEY) ?? [
            'datos'    => [],
            'scan_at'  => null,
            'completo' => false,
        ];

        $pendientes = array_values(array_diff($codigos, array_keys($estado['datos'])));

        if ($pendientes !== []) {
            foreach (array_chunk($pendientes, self::DOC_LOTE) as $lote) {
                foreach ($lote as $codi) {
                    $estado['datos'][$codi] = $this->consultarDocumentos($codi);
                }

                // Avance parcial: si la petición se corta aquí, la siguiente continúa.
                Cache::put(self::DOC_CACHE_KEY, $estado, now()->addHours(12));
            }

            $estado['completo'] = true;
            $estado['scan_at']  = now()->toDateTimeString();
            Cache::put(self::DOC_CACHE_KEY, $estado, now()->addHours(6));
        }

        return $estado;
    }

    private function consultarDocumentos(string $codi): array
    {
        return [
            'foto'    => $this->existeEnServidor('Fotos', $codi),
            'anverso' => $this->existeEnServidor('DNI1_1', $codi),
            'reverso' => $this->existeEnServidor('DNI2_1', $codi),
        ];
    }

    /**
     * 200 = sí existe · 404 = no existe · cualquier otra cosa = desconocido.
     * Un fallo de red NUNCA se reporta como "faltante".
     */
    private function existeEnServidor(string $carpeta, string $codi): string
    {
        try {
            $respuesta = Http::timeout(8)
                ->connectTimeout(5)
                ->head(self::DOC_BASE . '/' . $carpeta . '/' . rawurlencode($codi) . '.jpg');

            $codigo = $respuesta->status();

            if ($codigo === 200) {
                return 'si';
            }
            if ($codigo === 404) {
                return 'no';
            }

            return 'desconocido';
        } catch (\Throwable) {
            return 'desconocido';
        }
    }

    private function estadoFoto(string $valor): string
    {
        return match ($valor) {
            'si'          => 'Sí',
            'no'          => 'No',
            default       => 'Sin verificar',
        };
    }

    private function estadoDni(string $anverso, string $reverso): string
    {
        if ($anverso === 'si' && $reverso === 'si') return 'Completo';
        if ($anverso === 'si' && $reverso === 'no')  return 'Solo anverso';
        if ($anverso === 'no' && $reverso === 'si')  return 'Solo reverso';
        if ($anverso === 'no' && $reverso === 'no')  return 'No';

        return 'Sin verificar';
    }

    // ── Excel ─────────────────────────────────────────────────

    /**
     * Devuelve la ruta temporal del .xlsx generado.
     */
    private function generarExcelDocumentos(array $filas, array $stats): string
    {
        $columnas = [
            'N°', 'Código', 'Apellidos y Nombres', 'N° Documento', 'Sucursal',
            'Tipo', 'Cargo', 'Foto', 'DNI', 'Registrado por', 'F. Registro',
            'Modificado por', 'F. Modificación',
        ];
        $ultima = 'M';

        $ss   = new Spreadsheet();
        $hoja = $ss->getActiveSheet();
        $hoja->setTitle('Documentos');
        $hoja->setShowGridlines(false);

        // ── Anchos de columna ─────────────────────────────────
        $anchos = [6, 10, 34, 15, 16, 20, 26, 9, 15, 26, 17, 26, 17];
        foreach ($anchos as $i => $ancho) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($ancho);
        }

        // ── Logo ──────────────────────────────────────────────
        // logo_sol.png mide 500x238 (relación 2,10): se fija el tamaño
        // exacto sin redimensionado proporcional para no distorsionarlo.
        $rutaLogo = public_path('images/logo_sol.png');
        if (is_file($rutaLogo)) {
            $logo = new Drawing();
            $logo->setPath($rutaLogo);
            $logo->setResizeProportional(false);
            $logo->setCoordinates('A1');
            $logo->setWidth(130);
            $logo->setHeight(62);
            $logo->setWorksheet($hoja);
        }

        // ── Encabezado ────────────────────────────────────────
        $hoja->getRowDimension(1)->setRowHeight(64);
        $hoja->mergeCells('C1:' . $ultima . '1');
        $hoja->setCellValue('C1', 'REPORTE DE DOCUMENTOS DE PERSONAL VIGENTE');
        $hoja->getStyle('C1')->getFont()->setBold(true)->setSize(14)
              ->getColor()->setRGB('990000');
        $hoja->getStyle('C1')->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_LEFT)
              ->setVertical(Alignment::VERTICAL_CENTER);

        $hoja->mergeCells('C2:' . $ultima . '2');
        $hoja->setCellValue('C2', 'Sistema Integrado Solmar — SISOL Web');
        $hoja->getStyle('C2')->getFont()->setBold(true)->setSize(11)
              ->getColor()->setRGB('990000');
        $hoja->getStyle('C2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $hoja->mergeCells('C3:' . $ultima . '3');
        $hoja->setCellValue(
            'C3',
            'Fuente: si_solm.dbo.PERSONAL (PERS_VIGENCIA = SI · EMPR_CODIGO = 01) · ' .
            'Archivos: Biblioteca_Grafica/{Fotos, DNI1_1, DNI2_1}'
        );
        $hoja->getStyle('C3')->getFont()->setSize(9)->getColor()->setRGB('6B7280');
        $hoja->getStyle('C3')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $hoja->getRowDimension(4)->setRowHeight(6);

        // ── Bloque de estadísticas ────────────────────────────
        $bloquesStats = [
            'A' => 'B',  // Generado
            'C' => 'D',  // Vigentes
            'E' => 'F',  // Foto OK
            'G' => 'H',  // DNI completo
            'I' => 'J',  // Incompletos
            'K' => 'M',  // Últ. escaneo
        ];
        $etiquetas = ['Generado', 'Vigentes', 'Foto OK', 'DNI completo', 'Incompletos', 'Últ. escaneo de archivos'];

        $total = max($stats['vigentes'], 1);
        $valores = [
            $stats['generado'],
            number_format($stats['vigentes'], 0, ',', '.'),
            number_format($stats['foto_ok'], 0, ',', '.') . '  (' . number_format($stats['foto_ok'] / $total * 100, 1, ',', '.') . '%)',
            number_format($stats['dni_ok'],  0, ',', '.') . '  (' . number_format($stats['dni_ok'] / $total * 100, 1, ',', '.') . '%)',
            number_format($stats['faltan'],  0, ',', '.'),
            $stats['scan_at'],
        ];

        $indice = 0;
        foreach ($bloquesStats as $desde => $hasta) {
            $rango = $desde . '5:' . $hasta . '5';
            $hoja->mergeCells($rango);
            $hoja->setCellValue($desde . '5', $etiquetas[$indice]);

            $rangoValor = $desde . '6:' . $hasta . '6';
            $hoja->mergeCells($rangoValor);
            $hoja->setCellValue($desde . '6', $valores[$indice]);

            $indice++;
        }

        $hoja->getStyle('A5:' . $ultima . '6')->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('E5E7EB');
        $hoja->getStyle('A5:' . $ultima . '5')->getFont()->setBold(true)->setSize(9)
              ->getColor()->setRGB('374151');
        $hoja->getStyle('A6:' . $ultima . '6')->getFont()->setBold(true)->setSize(10)
              ->getColor()->setRGB('111827');
        $hoja->getStyle('A5:' . $ultima . '6')->getBorders()->getAllBorders()
              ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
        $hoja->getStyle('A5:' . $ultima . '6')->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER)
              ->setVertical(Alignment::VERTICAL_CENTER);

        $hoja->getRowDimension(7)->setRowHeight(6);

        // ── Cabecera de la tabla ──────────────────────────────
        $filaCabecera = 8;
        foreach ($columnas as $i => $titulo) {
            $hoja->setCellValue(Coordinate::stringFromColumnIndex($i + 1) . $filaCabecera, $titulo);
        }
        $hoja->getStyle('A' . $filaCabecera . ':' . $ultima . $filaCabecera)
              ->getFill()->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setRGB('4B5563');
        $hoja->getStyle('A' . $filaCabecera . ':' . $ultima . $filaCabecera)
              ->getFont()->setBold(true)->setSize(9)->getColor()->setRGB('FFFFFF');
        $hoja->getStyle('A' . $filaCabecera . ':' . $ultima . $filaCabecera)
              ->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER)
              ->setVertical(Alignment::VERTICAL_CENTER)
              ->setWrapText(true);
        $hoja->getStyle('A' . $filaCabecera . ':' . $ultima . $filaCabecera)
              ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
              ->getColor()->setRGB('4B5563');
        $hoja->getRowDimension($filaCabecera)->setRowHeight(30);

        // ── Filas ─────────────────────────────────────────────
        // Se vuelcan con fromArray y se pintan por rangos (en vez de celda
        // a celda): con 1.135 registros pasa de ~16 s a ~3 s.
        $filaInicial = $filaCabecera + 1;
        $matriz      = [];
        $incompletas = [];

        $num = 0;
        foreach ($filas as $d) {
            $num++;
            $f = $filaCabecera + $num;

            $matriz[] = [
                $num,
                $d['codi'],
                $d['nombre'],
                $d['doc'],
                $d['sucursal'],
                $d['tipo'],
                $d['cargo'],
                $d['foto_txt'],
                $d['dni_txt'],
                $d['reg'],
                $d['freg'],
                $d['mod'],
                $d['fmod'],
            ];

            if ($d['incompleto']) {
                $incompletas[$f] = $d;
            }
        }

        $filaFinal = $filaCabecera + count($matriz);

        if ($matriz !== []) {
            $hoja->fromArray($matriz, null, 'A' . $filaInicial, true);

            // Estilos base del bloque (una sola pasada)
            $base = $hoja->getStyle('A' . $filaInicial . ':' . $ultima . $filaFinal);
            $base->getFont()->setSize(9);
            $base->getBorders()->getAllBorders()
                 ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('D1D5DB');
            $base->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

            // Columnas cortas centradas (6 rangos en vez de 1.135 x 6)
            foreach (['A', 'D', 'H', 'I', 'K', 'M'] as $col) {
                $hoja->getStyle($col . $filaInicial . ':' . $col . $filaFinal)->getAlignment()
                     ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Estados correctos en verde; abajo se sobrescriben las excepciones
            $hoja->getStyle('H' . $filaInicial . ':' . 'H' . $filaFinal)
                 ->getFont()->setBold(true)->getColor()->setRGB('15803D');
            $hoja->getStyle('I' . $filaInicial . ':' . 'I' . $filaFinal)
                 ->getFont()->setBold(true)->getColor()->setRGB('15803D');

            // Solo las filas con documentos faltantes: relleno ámbar + color
            foreach ($incompletas as $f => $d) {
                $hoja->getStyle('A' . $f . ':' . $ultima . $f)->getFill()
                     ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FEF3C7');

                $colorFoto = $d['foto_txt'] === 'Sí' ? '15803D' : ($d['foto_txt'] === 'No' ? 'B91C1C' : 'B45309');
                $colorDni  = match ($d['dni_txt']) {
                    'Completo'                      => '15803D',
                    'Solo anverso', 'Solo reverso'   => 'B45309',
                    'No'                            => 'B91C1C',
                    default                         => 'B45309',
                };

                $hoja->getStyle('H' . $f)->getFont()->setBold(true)->getColor()->setRGB($colorFoto);
                $hoja->getStyle('I' . $f)->getFont()->setBold(true)->getColor()->setRGB($colorDni);
            }
        }

        // ── Filtros y panel congelado ─────────────────────────
        $hoja->setAutoFilter('A' . $filaCabecera . ':' . $ultima . $filaCabecera);
        $hoja->freezePane('A' . ($filaCabecera + 1));

        // sys_get_temp_dir() queda redirigido a <proyecto>/temp por AppServiceProvider;
        // nos aseguramos de que exista antes de escribir (si no, fopen da 500).
        $dir = sys_get_temp_dir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ruta = $dir . DIRECTORY_SEPARATOR
              . 'rep_documentos_' . date('Ymd_His') . '_' . uniqid() . '.xlsx';

        (new Xlsx($ss))->save($ruta);
        $ss->disconnectWorksheets();
        unset($ss);

        return $ruta;
    }

    // ── Helpers privados ──────────────────────────────────────

    private function usuarioLegible($codigo, $nombre): string
    {
        $nom = trim((string) ($nombre ?? ''));
        if ($nom !== '' && trim(preg_replace('/\s+/', ' ', $nom)) !== '') {
            return trim(preg_replace('/\s+/', ' ', $nom));
        }

        $cod = trim((string) ($codigo ?? ''));

        return $cod !== '' ? $cod : '—';
    }

    private function formatFechaHora($valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        try {
            $fecha = $valor instanceof \DateTimeInterface
                ? $valor
                : new \DateTime(trim((string) $valor));

            return $fecha->format('d/m/Y H:i');
        } catch (\Throwable) {
            return trim((string) $valor);
        }
    }

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