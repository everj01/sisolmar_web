<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveDeclaracionJuradaRequest;
use App\Services\DjService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DjController extends Controller
{
    public function generarPDF(Request $request)
    {
        $data = $request->all();

        $pdfService = new PdfService;
        $pdfContent = $pdfService->generarPdf($data);

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="previsualizacion_dj.pdf"');
    }

    public function saveReporteAvanceDj(Request $request)
    {
        $data = $request->validate([
            'codPersonal' => [
                'required',
                'string',
                'size:5', 
            ],
            'estFirma'  => [
                'required',
                'boolean',
            ],
            'estHuella' => [
                'required',
                'boolean',
            ],
            'observacion' => [
                'required',
                'string',
                'max:500'
            ],
        ]);

        try {
            DB::table('sw_log_migracion_huella_firma')->updateOrInsert(
                [
                    'codiPers' => $data['codPersonal'],
                ],
                [
                    'estFirma'          => $data['estFirma'],
                    'estHuella'         => $data['estHuella'],
                    'creadoPor'         => 'MIGRACION',
                    'observacion'       => $data['observacion'],
                    'fechaModificacion' => DB::raw('GETDATE()'),
                ]
            );
        }  catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al guardar el registro',
                'detail'  => $e->getMessage(), 
                'line'    => $e->getLine(),     
                'file'    => $e->getFile(),   
            ], 500);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Proceso realizado correctamente'
        ], 200);
    }

    /**
     * GUARDAR NUEVA DJ — Personal nuevo sin registro previo en sw_MIGRA_PERSONAL
     */
    public function saveNuevaDj(Request $request)
    {
        DB::beginTransaction();

        try {
            $data    = $request->all();
            $dni     = trim($request->input('dni', ''));
            $tipoPer = trim($request->input('tipo_personal', '')); // '03' o '05'

            // Validaciones básicas
            if (empty($dni)) {
                return response()->json(['success' => false, 'message' => 'El DNI es requerido.'], 400);
            }

            // Verificar que no exista ya en PERSONAL por DNI
            $existente = DB::selectOne(
                'SELECT CODI_PERS FROM si_solm.dbo.PERSONAL WHERE NRO_DOCU_IDEN = ?',
                [$dni]
            );
            if ($existente) {
                return response()->json([
                    'success' => false,
                    'message' => "Ya existe un registro con el DNI {$dni} (Cód: {$existente->CODI_PERS})."
                ], 422);
            }

            // Generar CODI_PERS incremental char(5) numérico
            $maxCod = DB::selectOne(
                "SELECT MAX(CAST(CODI_PERS AS INT)) AS max_cod 
                FROM si_solm.dbo.PERSONAL 
                WHERE ISNUMERIC(CODI_PERS) = 1 
                AND LEN(LTRIM(RTRIM(CODI_PERS))) = 5"
            );
            $siguiente = (int)($maxCod->max_cod ?? 0) + 1;
            if ($siguiente > 99999) {
                return response()->json(['success' => false, 'message' => 'Límite máximo de códigos alcanzado.'], 500);
            }
            $nuevoCod = str_pad($siguiente, 5, '0', STR_PAD_LEFT);

            // Double-check que no exista
            $codExiste = DB::selectOne('SELECT 1 FROM si_solm.dbo.PERSONAL WHERE CODI_PERS = ?', [$nuevoCod]);
            if ($codExiste) {
                return response()->json(['success' => false, 'message' => "Conflicto al generar código {$nuevoCod}."], 500);
            }

            // ── Mapeos de campos con restricciones de largo ──────────────────

            // ESTA_CIVI char(1): S=Soltero, C=Casado, D=Divorciado, V=Viudo/Conviviente
            $estadoCivilMap = [
                '2007000001' => 'S',
                '2007000002' => 'C',
                '2007000003' => 'D',
                '2007000004' => 'V',
                '2007000008' => 'V',
            ];
            $estadoCivil      = $data['estado_civil'] ?? null; // código largo → ESCI_CODIGO varchar(10) ✓
            $estadoCivilCorto = $estadoCivilMap[$estadoCivil] ?? null; // → ESTA_CIVI char(1)

            // PERS_TIPOTRAB char(2): OP=Operativo, AD=Administrativo
            $tipoTrabMap = [
                '03' => 'OP',
                '01' => 'OP',
                '05' => 'AD',
                '02' => 'AD',
            ];
            $tipotrab = $tipoTrabMap[$tipoPer] ?? 'OP';

            // SEXO char(1), PERS_SEXO char(1) — solo 1 caracter
            $sexo = strtoupper(substr($data['sexo'] ?? 'M', 0, 1));

            // ESSALUD char(2), PERS_PENSIONISTA char(2), PERS_EMBARGO varchar(2), etc — SI/NO máx 2
            $essalud     = strtoupper(substr($data['essalud']    ?? 'NO', 0, 2));
            $pensionista = strtoupper(substr($data['pensionista'] ?? 'NO', 0, 2));
            $embargo     = strtoupper(substr($data['embargos']   ?? 'NO', 0, 2));
            $snadar      = 'NO'; // no está en el formulario nuevo, default NO

            // PERS_CONDISCAMEC char(2), PERS_CONARMAS char(2), PERS_CONSMO char(2)
            $condiscamec = strtoupper(substr($data['curso_sucamec']  ?? 'NO', 0, 2));
            $conarmas    = strtoupper(substr($data['arma_propia']    ?? 'NO', 0, 2));
            $consmo      = ($data['consumo_sustancias'] ?? 'NO') !== 'NO' ? 'SI' : 'NO';
            $smo         = strtoupper(substr($data['consumo_sustancias'] ?? 'NO', 0, 2));
            $lugarsmo    = ($data['consumo_sustancias'] ?? 'NO') !== 'NO' ? substr($data['consumo_sustancias'], 0, 50) : null;

            // PERS_VEHICULO_PROPIO varchar(2), dj2026_familiar_empresa varchar(2)
            $vehiculoPropio   = strtoupper(substr($data['vehiculo_propio']   ?? 'NO', 0, 2));
            $familiarEmpresa  = strtoupper(substr($data['familiar_empresa']  ?? 'NO', 0, 2));

            // PERS_VIGENCIA char(2)
            $vigencia = 'SI';

            // CODI_TIPO_DOCU char(4)
            $codiTipoDocu = '0001';

            // CLASE_BREVETE varchar(1) — solo A o B
            $claseBrevete = !empty($data['clase_brevete']) ? strtoupper(substr($data['clase_brevete'], 0, 1)) : null;

            // CARR_CODIGO + IEDU_CODIGO — FK constraint, si carrera es 999999 ambos van null
            $carrCodigo = null;
            $ieduCodigo = null;
            if (!empty($data['carrera']) && $data['carrera'] !== '999999') {
                // Verificar que exista en SUNAT_CARRERAS
                $carreraExiste = DB::selectOne(
                    'SELECT 1 FROM si_solm.dbo.SUNAT_CARRERAS WHERE CARR_CODIGO = ? AND IEDU_CODIGO = ?',
                    [$data['carrera'], $data['institucion'] ?? null]
                );
                if ($carreraExiste) {
                    $carrCodigo = $data['carrera'];
                    $ieduCodigo = $data['institucion'] ?? null;
                }
            }

            // Fechas
            $fechaNaci   = $this->sanitizeDatetimeForPersonal($data['fecha_nacimiento'] ?? null);
            $fechaCaduca = $this->sanitizeDatetimeForPersonal($data['caduca'] ?? null);

            // Numéricos
            $peso           = is_numeric($data['peso']  ?? '')           ? $data['peso']  : null;
            $talla          = is_numeric($data['talla'] ?? '')           ? $data['talla'] : null;
            $anioEgreso     = is_numeric($data['anio_egreso'] ?? '')     ? (int)$data['anio_egreso'] : null;
            $experienciaAnios = is_numeric($data['experiencia_anios'] ?? '') ? (int)$data['experiencia_anios'] : null;

            // Laborales alternas
            $laboral1 = !empty(trim($data['dj2026_laboral_1'] ?? '')) ? strtoupper(trim($data['dj2026_laboral_1'])) : null;
            $laboral2 = !empty(trim($data['dj2026_laboral_2'] ?? '')) ? strtoupper(trim($data['dj2026_laboral_2'])) : null;
            $cantProfesion = ($laboral1 ? 1 : 0) + ($laboral2 ? 1 : 0);

            // ── INSERT EN PERSONAL ────────────────────────────────────────────
            DB::insert(
                "INSERT INTO si_solm.dbo.PERSONAL (
                    CODI_PERS, CODI_TIPO_DOCU, NRO_DOCU_IDEN,
                    NOMB_1, NOMB_2, APEL_1, APEL_2,
                    FECH_NACI, PERS_FECHCADUCADNI,
                    SEXO, PERS_SEXO,
                    ESCI_CODIGO, ESTA_CIVI,
                    PERS_EMAIL, PERS_TELEFONO, PERS_WHATSAPP,
                    DIRECCION, PERS_DIREC_DNI,
                    PERS_DEPT_ACT, PERS_PROV_ACT, PERS_DIST_ACT,
                    PERS_DPTO_DIRDNI, PERS_PROV_DIRDNI, PERS_DIST_DIRDNI,
                    tipo_sangr, peso_kilo, tall_metr,
                    CODI_SIST_PENS, ESSALUD, PERS_PENSIONISTA, PERS_EMBARGO,
                    PERS_GRADO_INSTRUCCION, CARR_CODIGO, IEDU_CODIGO, EGRESO_EDUCATIVO,
                    PERS_CONDISCAMEC, PERS_NRODISCAMEC,
                    PERS_SMO, PERS_LUGARSMO, PERS_CONSMO,
                    PERS_NROLICENCIA, PERS_CONARMAS,
                    PERS_BREVETE, CLASE_BREVETE, CATEGORIA_BREVETE, PERS_VEHICULO_PROPIO,
                    PERS_NOMCONTACTO, PERS_NROEMERGENCIA, PERS_EMERC_FAMILIAR,
                    PERS_CTRABANT, PERS_CARGOTRABANT, PERS_DURACIONANT,
                    dj2026_banco, dj2026_ciudad_naci, dj2026_ocupacion_principal,
                    dj2026_experiencia_anios, dj2026_familiar_empresa,
                    dj2026_familiar_nombre, dj2026_familiar_parentesco,
                    dj2026_laboral_1, dj2026_laboral_2, dj2026_cantprofesion,
                    PERS_TIPOTRAB, PERS_VIGENCIA,
                    PERS_SNADAR, SIP_migrado, SIP_activo, SIP_habilitado,
                    USUA_FECHA_REG, USUA_FECHA_MOD
                ) VALUES (
                    ?,?,?,
                    ?,?,?,?,
                    ?,?,
                    ?,?,
                    ?,?,
                    ?,?,?,
                    ?,?,
                    ?,?,?,
                    ?,?,?,
                    ?,?,?,
                    ?,?,?,?,
                    ?,?,?,?,
                    ?,?,
                    ?,?,?,
                    ?,?,
                    ?,?,?,?,
                    ?,?,?,
                    ?,?,?,
                    ?,?,?,
                    ?,?,
                    ?,?,
                    ?,?,?,
                    ?,?,?,
                    1,1,0,
                    GETDATE(), GETDATE()
                )",
                [
                    $nuevoCod, $codiTipoDocu, $dni,

                    strtoupper(trim($data['nombre1']          ?? '')),
                    strtoupper(trim($data['nombre2']          ?? '')),
                    strtoupper(trim($data['apellido_paterno'] ?? '')),
                    strtoupper(trim($data['apellido_materno'] ?? '')),

                    $fechaNaci, $fechaCaduca,

                    $sexo, $sexo,

                    $estadoCivil, $estadoCivilCorto,

                    $data['correo']   ?? null,
                    $data['celular']  ?? null,
                    $data['whatsapp'] ?? null,

                    $data['direccion_actual'] ?? null,
                    $data['direccion_dni']    ?? null,

                    $data['departamento_actual'] ?? null,
                    $data['provincia_actual']    ?? null,
                    $data['distrito_actual']     ?? null,
                    $data['departamento_dni']    ?? null,
                    $data['provincia_dni']       ?? null,
                    $data['distrito_dni']        ?? null,

                    $data['tipo_sangre'] ?? null,
                    $peso,
                    $talla,

                    $data['sistema_previsional'] ?? '07',
                    $essalud,
                    $pensionista,
                    $embargo,

                    $data['grado_instruccion'] ?? null,
                    $carrCodigo,
                    $ieduCodigo,
                    $anioEgreso,

                    $condiscamec,
                    $data['sucamec_obs'] ?? null,

                    $smo,
                    $lugarsmo,
                    $consmo,

                    $data['licencia_arma'] ?? null,
                    $conarmas,

                    $data['brevete']       ?? null,
                    $claseBrevete,
                    $data['tipo_vehiculo'] ?? null,
                    $vehiculoPropio,

                    $data['contacto_emergencia']   ?? null,
                    $data['celular_emergencia']    ?? null,
                    $data['parentesco_emergencia'] ?? null,

                    $data['empresa_anterior']  ?? null,
                    $data['cargo_anterior']    ?? null,
                    $data['duracion_anterior'] ?? null,

                    $data['cuenta_banco']        ?? null,
                    $data['ciudad_nacimiento']   ?? null,
                    $data['ocupacion_principal'] ?? null,

                    $experienciaAnios,
                    $familiarEmpresa,

                    $data['familiar_nombre']     ?? null,
                    $data['familiar_parentesco'] ?? null,

                    $laboral1,
                    $laboral2,
                    $cantProfesion,

                    $tipotrab,
                    $vigencia,

                    $snadar,
                ]
            );

            // ── FAMILIARES ────────────────────────────────────────────────────
            $this->saveFamiliaresTemp($nuevoCod, $data);
            $this->migrarFamiliares($nuevoCod);

            // ── TELÉFONOS ─────────────────────────────────────────────────────
            $this->saveTelefonosTemp($nuevoCod, $data);
            $this->migrarTelefonos($nuevoCod);

            DB::commit();

            Log::info('saveNuevaDj: Personal nuevo creado', ['CODI_PERS' => $nuevoCod, 'DNI' => $dni]);

            return response()->json([
                'success'   => true,
                'message'   => 'Declaración Jurada guardada correctamente.',
                'codi_pers' => $nuevoCod,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en saveNuevaDj: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function saveDeclaracionJurada(SaveDeclaracionJuradaRequest $request)
    {

        $djService = new DjService;

        $saved = $djService->guardarDeclaracionJurada($request->validated());

        return response()->json([
            'message' => 'Declaración Jurada guardada correctamente',
            'data' => $saved,
        ], 200);
    }

    private function getTelefonos(string $codiPers, string $source = 'migracion')
    {
        // 1. Definir tabla según source
        $tablaTel = ($source === 'pendiente')
            ? 'si_solm.dbo.TELEFONO'
            : 'sisolm_web.dbo.sw_MIGRA_TELEFONO';

        // 2. Obtener teléfonos desde la tabla correspondiente
        $telefonos = DB::select(
            "SELECT * FROM {$tablaTel} WHERE CODI_PERS = ?",
            [$codiPers]
        );

        $result = [
            'PERS_TELEFONO'      => null,
            'PERS_WHATSAPP'      => null,
            'PERS_NROEMERGENCIA' => null,
            'PERS_NOMCONTACTO'   => null,
            'PERS_EMERC_FAMILIAR'=> null,
        ];

        if (!empty($telefonos)) {
            foreach ($telefonos as $tel) {
                $tel = (array) $tel;

                // Emergencia
                if ($tel['TELE_EMERGENCIA'] == '1') {
                    $result['PERS_NROEMERGENCIA']  = $tel['NRO_TELE']          ?? null;
                    $result['PERS_NOMCONTACTO']    = $tel['TELE_CONTACTO']     ?? null;
                    $result['PERS_EMERC_FAMILIAR'] = $tel['VINCULO_FAMILIAR']  ?? null;
                    continue;
                }

                // WSP y personal
                if ($tel['NRO_WSP'] == 1) {
                    $result['PERS_WHATSAPP'] = $tel['NRO_TELE'] ?? null;
                    // Si solo hay un registro con NRO_WSP=1 
                    // significa que personal == whatsapp
                    if (empty($result['PERS_TELEFONO'])) {
                        $result['PERS_TELEFONO'] = $tel['NRO_TELE'] ?? null;
                    }
                } else {
                    $result['PERS_TELEFONO'] = $tel['NRO_TELE'] ?? null;
                }
            }
        }

        return $result;
    }

    public function getPersonalData(Request $request)
    {
        try {
            $codiPers = $request->get('codi_pers') ?? session('USER_Codi_Pers');
            $source = $request->get('source', 'migracion'); // ✅ NUEVO parámetro

            // ✅ Si viene de PENDIENTES → usar directamente si_solm.dbo.PERSONAL
            if ($source === 'pendiente') {
                $personalData = DB::select(
                    'SELECT * FROM si_solm.dbo.PERSONAL WHERE CODI_PERS = ?',
                    [$codiPers]
                );

                if (empty($personalData)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se encontraron datos del personal',
                    ], 404);
                }

                $data = (array) $personalData[0];
                $sourceTable = 'PERSONAL';

            } else {
                // ✅ Flujo original para MIGRACIÓN
                $djData = DB::select(
                    'SELECT * FROM si_solm.dbo.DJ2026_PERSONAL WHERE CODI_PERS = ?',
                    [$codiPers]
                );

                if (!empty($djData)) {
                    $data = (array) $djData[0];
                    $sourceTable = 'DJ2026_PERSONAL';
                } else {
                    $migraData = DB::select(
                        'SELECT * FROM sisolm_web.dbo.sw_MIGRA_PERSONAL WHERE CODI_PERS = ?',
                        [$codiPers]
                    );

                    if (empty($migraData)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No se encontraron datos del personal',
                        ], 404);
                    }

                    $data = (array) $migraData[0];
                    $sourceTable = 'sw_MIGRA_PERSONAL';
                }
            }

            // ✅ Completar campos NULL desde PERSONAL (solo si no viene ya de PERSONAL)
            if ($source !== 'pendiente') {
                $data = $this->completarCamposNull($data, $codiPers);
            }

            $telefonos = $this->getTelefonos($codiPers, $source);

            $data['PERS_TELEFONO']       = $telefonos['PERS_TELEFONO']       ?? $data['PERS_TELEFONO']       ?? null;
            $data['PERS_WHATSAPP']       = $telefonos['PERS_WHATSAPP']       ?? $data['PERS_WHATSAPP']       ?? null;
            $data['PERS_NROEMERGENCIA']  = $telefonos['PERS_NROEMERGENCIA']  ?? $data['PERS_NROEMERGENCIA']  ?? null;
            $data['PERS_NOMCONTACTO']    = $telefonos['PERS_NOMCONTACTO']    ?? $data['PERS_NOMCONTACTO']    ?? null;
            $data['PERS_EMERC_FAMILIAR'] = $telefonos['PERS_EMERC_FAMILIAR'] ?? $data['PERS_EMERC_FAMILIAR'] ?? null;


            $dni = $data['NRO_DOCU_IDEN'] ?? '';

            // Familiares — pendiente usa DERECHO_HABIENTE directamente
            $familiaresTable = ($sourceTable === 'DJ2026_PERSONAL')
                ? 'si_solm.dbo.DERECHO_HABIENTE'
                : ($source === 'pendiente'
                    ? 'si_solm.dbo.DERECHO_HABIENTE'
                    : 'sisolm_web.dbo.sw_MIGRA_DERECHO_HABIENTE');

            $familiares = DB::select(
                "SELECT *, 
                (ISNULL(APEL_1,'') + ' ' + ISNULL(APEL_2,'') + ', ' + ISNULL(NOMB_1,'') + ' ' + ISNULL(NOMB_2,'')) AS Nombres,
                CONVERT(CHAR(10), FECH_NACI, 105) AS FECH_NACI,
                FECH_NACI AS FECH_NACI2
                FROM {$familiaresTable}
                WHERE CODI_PERS = ?
                AND TIPO_RELA IN ('MADRE', 'PADRE', 'HIJO', 'CONYUGE')
                ORDER BY TIPO_RELA",
                [$codiPers]
            );

            // 5. Obtener OCUPACIONES ALTERNAS
            $ocupacionesTable = ($sourceTable === 'DJ2026_PERSONAL')
                ? 'si_solm.dbo.DJ2026_OCUPACIONES_PER'
                : 'sisolm_web.dbo.dj2026_OCUPACIONES';

            $ocupaciones = DB::select(
                "SELECT dj2026_descripcion FROM {$ocupacionesTable} WHERE PERS_DNI = ?",
                [$dni]
            );

      
     
            

            // 6. Formatear fechas para HTML inputs
            $data = $this->formatDatesForInput($data);
            

            

            // 7. Agregar foto path
            $data['FOTO_PATH'] = "http://190.116.178.163//Biblioteca_Grafica//Fotos//{$codiPers}.jpg";

            return response()->json([
                'success' => true,
                'data' => $data,
                'familiares' => $this->groupFamiliares($familiares),
                'ocupaciones_alternas' => array_map(function ($o) {
                    return (array) $o;
                }, $ocupaciones),
                'source_table' => $sourceTable,
                'is_migrado' => ($sourceTable === 'DJ2026_PERSONAL'),
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getPersonalData: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Completar campos NULL desde si_solm.dbo.PERSONAL
     */
    private function completarCamposNull(array $data, string $codiPers)
    {
        // Buscar datos en la tabla PERSONAL
        $personalData = DB::select(
            'SELECT * FROM si_solm.dbo.PERSONAL WHERE CODI_PERS = ?',
            [$codiPers]
        );

        // Si no existe en PERSONAL, retornar data sin cambios
        if (empty($personalData)) {
            return $data;
        }

        $personal = (array) $personalData[0];

        // Lista de campos que existen en ambas tablas y pueden completarse
        $camposComunes = [
            'NRO_DOCU_IDEN',
            'APEL_1',
            'APEL_2',
            'NOMB_1',
            'NOMB_2',
            'CODI_SIST_PENS',
            'FECH_INGRE',
            'FECH_NACI',
            'ESSALUD',
            'ESTA_CIVI',
            'SEXO',
            'NACIONALIDAD',
            'DIRECCION',
            'PERS_EMAIL',
            'PERS_CONBREVETE',
            'PERS_BREVETE',
            'FECH_CESE',
            'PERS_FECHCADUCADNI',
            'ESCI_CODIGO',
            'tipo_sangr',
            'peso_kilo',
            'tall_metr',
            'PERS_SNADAR',
            'PERS_CONSMO',
            'PERS_LUGARSMO',
            'PERS_CONDISCAMEC',
            'PERS_NRODISCAMEC',
            'PERS_SMO',
            'PERS_CONLICARMAS',
            'PERS_TIPOARMA',
            'PERS_CONARMAS',
            'CLASE_BREVETE',
            'PERS_TIPO_VEHICULO',
            'PERS_VEHICULO_PROPIO',
            'PERS_NOMCONTACTO',
            'PERS_NROEMERGENCIA',
            //'PERS_CONYUGE',
            'PERS_EMERC_FAMILIAR',
            'PERS_CTRABANT',
            'PERS_CARGOTRABANT',
            'PERS_DURACIONANT',
            'PERS_TELEFONO',
            'PERS_WHATSAPP',
            'PERS_EMBARGO',
            'PERS_PENSIONISTA',
            'PERS_GRADO_INSTRUCCION',
            'CARR_CODIGO',
            'IEDU_CODIGO',
            'EGRESO_EDUCATIVO',
            'PERS_DEPT_ACT',
            'PERS_PROV_ACT',
            'PERS_DIST_ACT',
            'PERS_DPTO_DIRDNI',
            'PERS_PROV_DIRDNI',
            'PERS_DIST_DIRDNI',
            'PERS_DIREC_DNI',
            'PERS_SEXO',
            'dj2026_laboral_1',
            'dj2026_laboral_2',
        ];

        // Completar campos NULL
        foreach ($camposComunes as $campo) {
            // Si el campo está NULL o vacío en $data, pero existe en $personal
            if (
                (is_null($data[$campo] ?? null) || empty($data[$campo]))
                && isset($personal[$campo])
                && !is_null($personal[$campo])
                && !empty($personal[$campo])
            ) {

                $data[$campo] = $personal[$campo];
            }
        }

        return $data;
    }

    /**
     * Obtener catálogos (grados, carreras, instituciones, etc.)
     */
    public function getCatalogs()
    {
        try {
            $grados = DB::select(
                'SELECT NIED_CODIGO AS id, NIED_ABREVIADO AS text 
                FROM si_solm.dbo.SUNAT_NIVEL_EDUCATIVO 
                ORDER BY NIED_DESCRIPCION'
            );

            $carreras = DB::select(
                'SELECT CARR_CODIGO AS id, CARR_DESCRIPCION AS text, IEDU_CODIGO 
                FROM si_solm.dbo.SUNAT_CARRERAS 
                ORDER BY CARR_DESCRIPCION'
            );

            $instituciones = DB::select(
                "SELECT IEDU_CODIGO AS id, '' AS text 
                FROM si_solm.dbo.SUNAT_IEDUCATIVA 
                ORDER BY IEDU_DESCRIPCION"
            );

            $sangre = [
                ['id' => 'O+', 'text' => 'O+'],
                ['id' => 'O-', 'text' => 'O-'],
                ['id' => 'A+', 'text' => 'A+'],
                ['id' => 'A-', 'text' => 'A-'],
                ['id' => 'B+', 'text' => 'B+'],
                ['id' => 'B-', 'text' => 'B-'],
                ['id' => 'AB+', 'text' => 'AB+'],
                ['id' => 'AB-', 'text' => 'AB-'],
            ];

            $estadosCiviles = DB::select(
                "SELECT ESCI_CODIGO AS id, ESCI_DESCRIPCION AS text 
                FROM si_solm.dbo.ADMI_ESTADO_CIVIL 
                WHERE ESCI_VIGENCIA='SI'"
            );

            $tiposArma = DB::select(
                "SELECT CODI_TIPO AS id, DESC_TIPO AS text 
                FROM si_solm.dbo.ARMA_TIPO 
                WHERE VIGENCIA_TIPO='SI' 
                ORDER BY DESC_TIPO"
            );

            return response()->json([
                'success' => true,
                'grados' => $grados,
                'carreras' => $carreras,
                'instituciones' => $instituciones,
                'sangre' => $sangre,
                'estados_civiles' => $estadosCiviles,
                'tipos_arma' => $tiposArma,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getCatalogs: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar catálogos: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener ubicaciones (departamentos, provincias, distritos)
     */
    public function getUbicacion(Request $request)
    {
        try {
            $type = $request->get('type');
            $dept = $request->get('dept', '');
            $prov = $request->get('prov', '');

            $result = [];

            switch ($type) {
                case 'dept':
                    $result = DB::select(
                        "SELECT DEPA_CODIGO AS id, DEPA_DESCRIPCION AS text 
                        FROM si_solm.dbo.ADMI_DEPARTAMENTO 
                        WHERE DEPA_VIGENCIA='SI' 
                        ORDER BY DEPA_DESCRIPCION"
                    );
                    break;

                case 'prov':
                    $result = DB::select(
                        "SELECT PROVI_CODIGO AS id, PROVI_DESCRIPCION AS text 
                        FROM si_solm.dbo.ADMI_PROVINCIA 
                        WHERE DEPA_CODIGO = ? AND PROVI_VIGENCIA='SI' 
                        ORDER BY PROVI_DESCRIPCION",
                        [$dept]
                    );
                    break;

                case 'dist':
                    $result = DB::select(
                        "SELECT DIST_CODIGO AS id, DIST_DESCRIPCION AS text 
                        FROM si_solm.dbo.ADMI_DISTRITO 
                        WHERE PROVI_CODIGO = ? AND DIST_VIGENCIA='SI' 
                        ORDER BY DIST_DESCRIPCION",
                        [$prov]
                    );
                    break;
            }

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Error en getUbicacion: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ubicación',
            ], 500);
        }
    }

    /**
     * GUARDAR Y MIGRAR - Proceso completo
     */
    public function saveDjCompleto(Request $request)
    {
        DB::beginTransaction();

        try {
            // ✅ Obtener código de personal
            $codiPers = $request->input('cod_postulante') ?? session('USER_Codi_Pers');

            if (empty($codiPers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo identificar el código de personal',
                ], 400);
            }

            // ✅ Obtener DNI
            $dni = $request->input('dni');
            if (empty($dni)) {
                $personalData = DB::select(
                    'SELECT NRO_DOCU_IDEN FROM sisolm_web.dbo.sw_MIGRA_PERSONAL WHERE CODI_PERS = ?',
                    [$codiPers]
                );
                $dni = !empty($personalData) ? $personalData[0]->NRO_DOCU_IDEN : null;
            }

            if (empty($dni)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener el DNI',
                ], 400);
            }

            $data = $request->all();

            // ✅ 1. SOLO MARCAR COMO MIGRADO en sw_MIGRA_PERSONAL (NO actualizar otros campos)
            DB::update(
                'UPDATE sisolm_web.dbo.sw_MIGRA_PERSONAL 
            SET SIP_migrado = 1, SIP_fechaModifcacion = GETDATE()
            WHERE CODI_PERS = ?',
                [$codiPers]
            );

            // ✅ 2. INSERTAR/ACTUALIZAR DIRECTAMENTE en DJ2026_PERSONAL
            $this->insertOrUpdateDJ2026Personal($codiPers, $data);

            // ✅ 2.5. SINCRONIZAR DJ2026_PERSONAL → PERSONAL (solo columnas con valor NO NULL)
            $this->syncDJ2026ToPersonal($codiPers);

            // ✅ 3. GUARDAR FAMILIARES en tablas temporales
            $this->saveFamiliaresTemp($codiPers, $data);

            // ✅ 4. COPIAR FAMILIARES a DJ2026_DERECHO_HABIENTE
            $this->migrarFamiliares($codiPers);

            // ✅ 5. GUARDAR OCUPACIONES en tabla temporal
            //$this->saveOcupacionesTemp($dni, $data);

            // ✅ 6. COPIAR OCUPACIONES a DJ2026_OCUPACIONES_PER
            //$this->migrarOcupaciones($dni);

            // ✅ 6. GUARDAR TELÉFONOS EN sw_MIGRA_TELEFONO
            $this->saveTelefonosTemp($codiPers, $data);

            // ✅ 7. MIGRAR TELÉFONOS → si_solm.dbo.TELEFONO
            $this->migrarTelefonos($codiPers);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Declaración Jurada guardada y migrada correctamente',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en saveDjCompleto: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getBackupData(Request $request)
    {
        try {
            $codiPers = $request->get('codi_pers');

            if (empty($codiPers)) {
                return response()->json(['success' => false, 'message' => 'Código requerido'], 400);
            }

           $backup = DB::select(
                'SELECT per.*, CO.ESCI_DESCRIPCION, SS.DESC_SIST_PENS, edu.NIED_ABREVIADO 
                FROM si_solm.dbo.DJ2026_BACKUP_PERSONAL as per
                LEFT JOIN si_solm.dbo.ADMI_ESTADO_CIVIL AS CO ON CO.ESCI_CODIGO = per.ESCI_CODIGO
                LEFT JOIN si_solm.dbo.SISTEMA_PENSIONES AS SS ON SS.CODI_SIST_PENS = per.CODI_SIST_PENS
                LEFT JOIN si_solm.dbo.SUNAT_NIVEL_EDUCATIVO AS EDU ON EDU.NIED_CODIGO = per.PERS_GRADO_INSTRUCCION 
                WHERE per.CODI_PERS = ?',
                [$codiPers]
            );

            if (empty($backup)) {
                $backup = DB::select(
                    'SELECT per.*, CO.ESCI_DESCRIPCION, SS.DESC_SIST_PENS, edu.NIED_ABREVIADO 
                    FROM si_solm.dbo.PERSONAL as per
                    LEFT JOIN si_solm.dbo.ADMI_ESTADO_CIVIL AS CO ON CO.ESCI_CODIGO = per.ESCI_CODIGO
                    LEFT JOIN si_solm.dbo.SISTEMA_PENSIONES AS SS ON SS.CODI_SIST_PENS = per.CODI_SIST_PENS
                    LEFT JOIN si_solm.dbo.SUNAT_NIVEL_EDUCATIVO AS EDU ON EDU.NIED_CODIGO = per.PERS_GRADO_INSTRUCCION 
                    WHERE per.CODI_PERS = ?',
                    [$codiPers]
                );
            }

            if (empty($backup)) {
                return response()->json(['success' => false, 'message' => 'Sin registro previo']);
            }

            $data = $this->formatDatesForInput((array) $backup[0]);

            // ✅ FAMILIARES DEL BACKUP
            $familiares = DB::select(
                "SELECT 
                TIPO_RELA,
                ISNULL(APEL_1,'') + ' ' + ISNULL(APEL_2,'') + ', ' + 
                ISNULL(NOMB_1,'') + ' ' + ISNULL(NOMB_2,'') AS Nombres,
                CONVERT(CHAR(10), FECH_NACI, 103) AS FECH_NACI
             FROM si_solm.dbo.DERECHO_HABIENTE 
             WHERE CODI_PERS = ?
             ORDER BY TIPO_RELA",
                [$codiPers]
            );

            return response()->json([
                'success' => true,
                'data' => $data,
                'familiares' => $familiares,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getBackupData: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ✅ FAMILIARES - Guardar en tabla temporal
    private function saveFamiliaresTemp(string $codiPers, array $data)
    {
        if (!isset($data['FAM_NOMBRES']) || !is_array($data['FAM_NOMBRES'])) {
            Log::info('No hay familiares para guardar');

            return;
        }

        // Limpiar registros previos
        DB::delete(
            'DELETE FROM sisolm_web.dbo.sw_MIGRA_DERECHO_HABIENTE WHERE CODI_PERS = ?',
            [$codiPers]
        );

        // Insertar nuevos
        foreach ($data['FAM_NOMBRES'] as $index => $nombreCompleto) {
            if (empty($nombreCompleto)) {
                continue;
            }

            $parentesco = $data['FAM_PARENTESCO'][$index] ?? '';
            $fechaNaci = $data['FAM_FECHA_NACI'][$index] ?? null;

            // Split del nombre
            $nombreCompleto = trim($nombreCompleto);

            if (strpos($nombreCompleto, ',') !== false) {
                [$apellidos, $nombres] = explode(',', $nombreCompleto, 2);
                $apellidos = trim($apellidos);
                $nombres = trim($nombres);

                $apellidosParts = preg_split('/\s+/', $apellidos, 2);
                $nombresParts = preg_split('/\s+/', $nombres, 2);

                $apel1 = $apellidosParts[0] ?? '';
                $apel2 = $apellidosParts[1] ?? '';
                $nomb1 = $nombresParts[0] ?? '';
                $nomb2 = $nombresParts[1] ?? '';
            } else {
                $nameParts = preg_split('/\s+/', $nombreCompleto);
                $apel1 = $nameParts[0] ?? '';
                $apel2 = $nameParts[1] ?? '';
                $nomb1 = $nameParts[2] ?? '';
                $nomb2 = isset($nameParts[3]) ? implode(' ', array_slice($nameParts, 3)) : '';
            }

            DB::insert(
                'INSERT INTO sisolm_web.dbo.sw_MIGRA_DERECHO_HABIENTE 
            (CODI_PERS, TIPO_RELA, APEL_1, APEL_2, NOMB_1, NOMB_2, FECH_NACI) 
            VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$codiPers, $parentesco, $apel1, $apel2, $nomb1, $nomb2, $fechaNaci]
            );
        }
    }

    // ✅ OCUPACIONES - Guardar en tabla temporal
    private function saveOcupacionesTemp(string $dni, array $data)
    {
        if (!isset($data['dj2026_descripcion']) || !is_array($data['dj2026_descripcion'])) {
            Log::info('No hay ocupaciones para guardar');

            return;
        }

        // Limpiar previas
        DB::delete(
            'DELETE FROM sisolm_web.dbo.dj2026_OCUPACIONES WHERE PERS_DNI = ?',
            [$dni]
        );

        // Insertar nuevas (máximo 2)
        $ocupaciones = array_filter($data['dj2026_descripcion']);
        $ocupaciones = array_slice($ocupaciones, 0, 2);

        foreach ($ocupaciones as $desc) {
            DB::insert(
                'INSERT INTO sisolm_web.dbo.dj2026_OCUPACIONES (PERS_DNI, dj2026_descripcion) VALUES (?, ?)',
                [$dni, strtoupper(trim($desc))]
            );
        }
    }

    private function insertOrUpdateDJ2026Personal(string $codiPers, array $data)
    {
        // ✅ 1. Obtener datos base de sw_MIGRA_PERSONAL
        $migraData = DB::select(
            'SELECT * FROM sisolm_web.dbo.sw_MIGRA_PERSONAL WHERE CODI_PERS = ?',
            [$codiPers]
        );

        if (empty($migraData)) {
            throw new \Exception("No se encontró registro en sw_MIGRA_PERSONAL para CODI_PERS: {$codiPers}");
        }

        $base = (array) $migraData[0];

        // ✅ 2. Obtener datos de si_solm.dbo.PERSONAL (TABLA ORIGINAL/MAESTRA)
        $personalOriginal = DB::select(
            'SELECT * FROM si_solm.dbo.PERSONAL WHERE CODI_PERS = ?',
            [$codiPers]
        );

        $original = !empty($personalOriginal) ? (array) $personalOriginal[0] : [];

        // ✅ 3. Definir campos por tipo
        $datetimeFields = [
            'FECH_INGRE_PLANILLA',
            'FECH_INGRE',
            'FECH_NACI',
            'FECH_CESE',
            'PERS_FECHEMISIONDNI',
            'PERS_FECHCADUCADNI',
            'VCMTO',
            'FECH_INIC_AFIL',
            'PERS_FECHAREG',
            'USUA_FECHA_REG',
            'USUA_FECHA_MOD',
            'PERS_FECHARETIRO',
            'Pers_fech_venc_lic_arm',
            'PERS_VERIF_FECHA',
            'FEC_MOD_PLAN',
            'FECH_EXP_BREVETE',
            'FECH_REVAL_BREVETE',
            'SIP_fechaCreacion',
            'SIP_fechaModifcacion',
        ];

        $bitFields = [
            'ESTA_ACTI',
            'COMI_PESC',
            'AFIL_SIND',
            'CARG_FAMI',
            'SEGU_VIDA_LEY',
            'FOTO_SI_NO',
            'GRAT',
            'VACA',
            'AFECTO_LEY',
            'PERS_FLAG',
            'apor_essa',
            'apor_sena',
            'apor_senc',
            'apor_cona',
            'MIGRADO',
            'SIP_habilitado',
            'SIP_migrado',
            'SIP_activo',
            'NO_CADUCA_DNI',
        ];

        $numericFields = [
            'SUEL_BASI',
            'SUEL_NETO',
            'MONT_PAGO_COME',
            'HORA_LABO',
            'UTIL03',
            'HORA_AUTO',
            'peso_kilo',
            'tall_metr',
            'EGRESO_EDUCATIVO',
            'dj2026_cantprofesion',
            'dj2026_experiencia_anios',
            'fotocheck',
            'horario',
            'CODI_CATE_TRAB',
        ];

        // ✅ 4. Función helper mejorada con limpieza y validación
        $getValue = function ($formKey, $baseKey = null, $originalKey = null) use ($data, $base, $original, $datetimeFields, $bitFields, $numericFields) {
            $baseKey = $baseKey ?? $formKey;
            $originalKey = $originalKey ?? $baseKey;

            $rawValue = null;

            // 1. Intentar desde el formulario
            if (isset($data[$formKey]) && $data[$formKey] !== '' && $data[$formKey] !== null) {
                $rawValue = $data[$formKey];
            }
            // 2. Intentar desde base (sw_MIGRA_PERSONAL)
            elseif (isset($base[$baseKey]) && $base[$baseKey] !== '' && $base[$baseKey] !== null) {
                $rawValue = $base[$baseKey];
            }
            // 3. Intentar desde original (si_solm.dbo.PERSONAL) ✅ AQUÍ BUSCAMOS EN LA TABLA MAESTRA
            elseif (isset($original[$originalKey]) && $original[$originalKey] !== '' && $original[$originalKey] !== null) {
                $rawValue = $original[$originalKey];
            }

            // ✅ 4. LIMPIAR Y VALIDAR SEGÚN EL TIPO DE CAMPO

            // Si es un campo DATETIME
            if (in_array($baseKey, $datetimeFields, true)) {
                if (
                    is_null($rawValue) ||
                    $rawValue === '' ||
                    $rawValue === '0000-00-00' ||
                    $rawValue === '0000-00-00 00:00:00'
                ) {
                    return null;
                }

                if ($rawValue instanceof \DateTimeInterface) {
                    return $rawValue->format('Y-m-d\TH:i:s.v');
                }

                if (is_string($rawValue)) {
                    $rawValue = trim($rawValue);

                    // YYYY-MM-DD
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawValue)) {
                        return $rawValue . 'T00:00:00.000';
                    }

                    // DD/MM/YYYY
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $rawValue)) {
                        [$d, $m, $y] = explode('/', $rawValue);

                        return "{$y}-{$m}-{$d}T00:00:00.000";
                    }

                    // YYYY-MM-DD HH:MM:SS
                    if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $rawValue)) {
                        return str_replace(' ', 'T', $rawValue) . '.000';
                    }

                    // YYYY-MM-DD HH:MM:SS.mmm
                    if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\.\d{3}$/', $rawValue)) {
                        return str_replace(' ', 'T', $rawValue);
                    }

                    // YYYY-MM-DDTHH:MM:SS
                    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $rawValue)) {
                        return $rawValue . '.000';
                    }

                    // YYYY-MM-DDTHH:MM:SS.mmm
                    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}$/', $rawValue)) {
                        return $rawValue;
                    }
                }

                return null;
            }

            // Si es un campo BIT
            if (in_array($baseKey, $bitFields)) {
                if (is_null($rawValue) || $rawValue === '') {
                    return 0;
                }
                if ($rawValue === true || $rawValue === 1 || $rawValue === '1' || strtoupper($rawValue) === 'SI') {
                    return 1;
                }

                return 0;
            }

            // Si es un campo NUMERIC
            if (in_array($baseKey, $numericFields)) {
                if (is_null($rawValue) || $rawValue === '' || !is_numeric($rawValue)) {
                    return null;
                }

                return $rawValue;
            }

            // Para cualquier otro campo (VARCHAR, CHAR, etc.)
            if (is_null($rawValue) || (is_string($rawValue) && trim($rawValue) === '')) {
                return null;
            }

            return $rawValue;
        };
        $sanitizeFinalValue = function ($field, $value) use ($datetimeFields, $bitFields, $numericFields) {

            $charLimits = [
                'PERS_ACTUALIZAR' => 300,
                'CONTROL_VERIF_TRANS' => 50,
                'PERS_OBSERVACIONES' => 300,
                'OBS_CESE' => 500,
                'MOTI_CESE' => 200,
                'OBSERVACIONES' => 200,
            ];

            if (isset($charLimits[$field]) && is_string($value) && strlen($value) > $charLimits[$field]) {
                $value = substr($value, 0, $charLimits[$field]);
            }

            if (is_string($value) && strlen($value) > 50 && $field === 'CONTROL_VERIF_TRANS') {
                $value = substr($value, 0, 50);
            }
            // Normalizar strings
            if (is_string($value)) {
                $value = trim($value);

                // Strings "vacíos" o basura común => null
                if ($value === '' || $value === '?' || strtolower($value) === 'null' || strtolower($value) === 'undefined') {
                    $value = null;
                }
            }

            // DATETIME
            if (in_array($field, $datetimeFields, true)) {
                if ($value === null) {
                    return null;
                }

                if ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d\TH:i:s.v');
                }

                if (is_string($value)) {
                    $value = trim($value);

                    // YYYY-MM-DD
                    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        return $value . 'T00:00:00.000';
                    }

                    // YYYY-MM-DD HH:MM:SS
                    if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}$/', $value)) {
                        return str_replace(' ', 'T', $value) . '.000';
                    }

                    // YYYY-MM-DD HH:MM:SS.mmm
                    if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\.\d{3}$/', $value)) {
                        return str_replace(' ', 'T', $value);
                    }

                    // YYYY-MM-DDTHH:MM:SS
                    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}$/', $value)) {
                        return $value . '.000';
                    }

                    // YYYY-MM-DDTHH:MM:SS.mmm
                    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}$/', $value)) {
                        return $value;
                    }

                    // DD/MM/YYYY
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                        [$d, $m, $y] = explode('/', $value);

                        return "{$y}-{$m}-{$d}T00:00:00.000";
                    }

                    // DD/MM/YYYY HH:MM:SS
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}\s\d{2}:\d{2}:\d{2}$/', $value)) {
                        [$date, $time] = explode(' ', $value);
                        [$d, $m, $y] = explode('/', $date);

                        return "{$y}-{$m}-{$d}T{$time}.000";
                    }
                }

                return null;
            }

            // BIT
            if (in_array($field, $bitFields, true)) {
                if ($value === null) {
                    return 0;
                }

                if (is_string($value)) {
                    $v = strtoupper(trim($value));
                    if (in_array($v, ['1', 'SI', 'SÍ', 'TRUE', 'YES'], true)) {
                        return 1;
                    }
                    if (in_array($v, ['0', 'NO', 'FALSE'], true)) {
                        return 0;
                    }
                }

                return (int) ((bool) $value);
            }

            // NUMERIC
            if (in_array($field, $numericFields, true)) {
                if ($value === null) {
                    return null;
                }

                if (is_string($value)) {
                    $value = trim($value);

                    if ($value === '') {
                        return null;
                    }

                    // Permitir coma decimal
                    $value = str_replace(',', '.', $value);
                }

                return is_numeric($value) ? $value : null;
            }

            // Cualquier otro campo
            if (is_string($value)) {
                $value = trim($value);

                return $value === '' ? null : $value;
            }

            return $value;
        };

        // ✅ 5. Construir array de datos COMPLETO (código igual que antes)
        $updates = [
            'CODI_PERS' => $codiPers,

            // Campos que vienen del formulario (con fallback a tablas)
            'NRO_DOCU_IDEN' => $getValue('dni', 'NRO_DOCU_IDEN'),
            'PERS_EMAIL' => $getValue('correo', 'PERS_EMAIL'),
            'PERS_TELEFONO' => $getValue('celular', 'PERS_TELEFONO'),
            'PERS_WHATSAPP' => $getValue('whatsapp', 'PERS_WHATSAPP'),
            'DIRECCION' => $getValue('direccion_actual', 'DIRECCION'),
            'PERS_SEXO' => $getValue('sexo', 'PERS_SEXO'),
            'SEXO' => $getValue('sexo', 'SEXO'),
            'ESCI_CODIGO' => $getValue('estado_civil', 'ESCI_CODIGO'),
            'PERS_FECHCADUCADNI' => $getValue('caduca', 'PERS_FECHCADUCADNI'),
            'FECH_NACI' => $getValue('fecha_nacimiento', 'FECH_NACI'),
            'PERS_SNADAR' => $getValue('sabe_nadar', 'PERS_SNADAR'),
            'tipo_sangr' => $getValue('tipo_sangre', 'tipo_sangr'),
            'peso_kilo' => $getValue('peso', 'peso_kilo'),
            'tall_metr' => $getValue('talla', 'tall_metr'),
            'CODI_SIST_PENS' => $getValue('sistema_previsional', 'CODI_SIST_PENS'),
            'ESSALUD' => $getValue('essalud', 'ESSALUD'),
            'PERS_PENSIONISTA' => $getValue('pensionista', 'PERS_PENSIONISTA'),
            'PERS_EMBARGO' => $getValue('embargos', 'PERS_EMBARGO'),
            'PERS_CONSMO' => $getValue('consumo_sustancias', 'PERS_CONSMO'),
            'PERS_DEPT_ACT' => $getValue('departamento_actual', 'PERS_DEPT_ACT'),
            'PERS_PROV_ACT' => $getValue('provincia_actual', 'PERS_PROV_ACT'),
            'PERS_DIST_ACT' => $getValue('distrito_actual', 'PERS_DIST_ACT'),
            'PERS_DPTO_DIRDNI' => $getValue('departamento_dni', 'PERS_DPTO_DIRDNI'),
            'PERS_PROV_DIRDNI' => $getValue('provincia_dni', 'PERS_PROV_DIRDNI'),
            'PERS_DIST_DIRDNI' => $getValue('distrito_dni', 'PERS_DIST_DIRDNI'),
            'PERS_DIREC_DNI' => $getValue('direccion_dni', 'PERS_DIREC_DNI'),
            'PERS_GRADO_INSTRUCCION' => $getValue('grado_instruccion', 'PERS_GRADO_INSTRUCCION'),
            'CARR_CODIGO' => empty($getValue('carrera', 'CARR_CODIGO')) ? null : $getValue('carrera', 'CARR_CODIGO'),

            // EN insertOrUpdateDJ2026Personal también (más abajo)

            'IEDU_CODIGO' =>
                ($getValue('carrera', 'CARR_CODIGO') == '999999') ?
                '199999999'
                :
                (empty($getValue('institucion', 'IEDU_CODIGO')) ? null : $getValue('institucion', 'IEDU_CODIGO')),


            'EGRESO_EDUCATIVO' => $getValue('anio_egreso', 'EGRESO_EDUCATIVO'),
            'PERS_CONDISCAMEC' => $getValue('curso_sucamec', 'PERS_CONDISCAMEC'),
            'PERS_NRODISCAMEC' => $getValue('sucamec_obs', 'PERS_NRODISCAMEC'),
            'PERS_SMO' => $getValue('consumo_sustancias', 'PERS_SMO'),
            'PERS_LUGARSMO' => $getValue('consumo_sustancias', 'PERS_LUGARSMO'),
            //'PERS_CONLICARMAS' => $getValue('licencia_arma', 'PERS_CONLICARMAS'),
            //'PERS_CONLICARMAS' => 'SI', // o extraer el valor del JSON
            'PERS_NROLICENCIA' => $getValue('licencia_arma', 'PERS_NROLICENCIA'),
            'PERS_TIPOARMA' => $getValue('tipo_arma', 'PERS_TIPOARMA'),
            'PERS_CONARMAS' => $getValue('arma_propia', 'PERS_CONARMAS'),
            'PERS_BREVETE' => $getValue('brevete', 'PERS_BREVETE'),
            'CLASE_BREVETE' => $getValue('clase_brevete', 'CLASE_BREVETE'),
            'PERS_TIPO_VEHICULO' => $getValue('tipo_vehiculo', 'PERS_TIPO_VEHICULO'),
            'PERS_VEHICULO_PROPIO' => $getValue('vehiculo_propio', 'PERS_VEHICULO_PROPIO'),
            'PERS_NOMCONTACTO' => $getValue('contacto_emergencia', 'PERS_NOMCONTACTO'),
            'PERS_NROEMERGENCIA' => $getValue('celular_emergencia', 'PERS_NROEMERGENCIA'),
            //'PERS_CONYUGE' => $getValue('parentesco_emergencia', 'PERS_CONYUGE'),
            'PERS_EMERC_FAMILIAR' => $getValue('parentesco_emergencia', 'PERS_EMERC_FAMILIAR'),
            'PERS_CTRABANT' => $getValue('empresa_anterior', 'PERS_CTRABANT'),
            'PERS_CARGOTRABANT' => $getValue('cargo_anterior', 'PERS_CARGOTRABANT'),
            'PERS_DURACIONANT' => $getValue('duracion_anterior', 'PERS_DURACIONANT'),
            'dj2026_banco' => $getValue('cuenta_banco', 'dj2026_banco'),
            'dj2026_ciudad_naci' => $getValue('ciudad_nacimiento', 'dj2026_ciudad_naci'),
            'dj2026_ocupacion_principal' => $getValue('ocupacion_principal', 'dj2026_ocupacion_principal'),
            'dj2026_experiencia_anios' => $getValue('experiencia_anios', 'dj2026_experiencia_anios'),
            'dj2026_familiar_empresa' => $getValue('familiar_empresa', 'dj2026_familiar_empresa'),
            'dj2026_familiar_nombre' => $getValue('familiar_nombre', 'dj2026_familiar_nombre'),
            'dj2026_familiar_parentesco' => $getValue('familiar_parentesco', 'dj2026_familiar_parentesco'),
            // 'dj2026_cantprofesion' => isset($data['ocupacion_alterna']) ? count(array_filter($data['ocupacion_alterna'])) : 0,
            'dj2026_laboral_1' => $getValue('dj2026_laboral_1', 'dj2026_laboral_1')
                ? strtoupper(trim($getValue('dj2026_laboral_1', 'dj2026_laboral_1')))
                : null,
            'dj2026_laboral_2' => $getValue('dj2026_laboral_2', 'dj2026_laboral_2')
                ? strtoupper(trim($getValue('dj2026_laboral_2', 'dj2026_laboral_2')))
                : null,
            'dj2026_cantprofesion' => ((!empty($data['dj2026_laboral_1']) ? 1 : 0) + (!empty($data['dj2026_laboral_2']) ? 1 : 0)),

            // ✅ TODOS LOS DEMÁS CAMPOS (con cascada: formulario → migra → original → null)
            'CODI_TIPO_DOCU' => $getValue('CODI_TIPO_DOCU', 'CODI_TIPO_DOCU'),
            'APEL_1' => $getValue('APEL_1', 'APEL_1'),
            'APEL_2' => $getValue('APEL_2', 'APEL_2'),
            'NOMB_1' => $getValue('NOMB_1', 'NOMB_1'),
            'NOMB_2' => $getValue('NOMB_2', 'NOMB_2'),
            'SIST_PENS_TIPOCOMI' => $getValue('SIST_PENS_TIPOCOMI', 'SIST_PENS_TIPOCOMI'),
            'CODI_CARG' => $getValue('CODI_CARG', 'CODI_CARG'),
            'CODI_AREA' => $getValue('CODI_AREA', 'CODI_AREA'),
            'CODI_MONE_BASI' => $getValue('CODI_MONE_BASI', 'CODI_MONE_BASI'),
            'SUEL_BASI' => $getValue('SUEL_BASI', 'SUEL_BASI'),
            'FECH_INGRE_PLANILLA' => $getValue('FECH_INGRE_PLANILLA', 'FECH_INGRE_PLANILLA'),
            'FECH_INGRE' => $getValue('FECH_INGRE', 'FECH_INGRE'),
            'CARN_ESSALUD' => $getValue('CARN_ESSALUD', 'CARN_ESSALUD'),
            'ESTA_CIVI' => $getValue('ESTA_CIVI', 'ESTA_CIVI'),
            'ASIG_FAMI' => $getValue('ASIG_FAMI', 'ASIG_FAMI'),
            'ESTA_ACTI' => $getValue('ESTA_ACTI', 'ESTA_ACTI'),
            'SCRT' => $getValue('SCRT', 'SCRT'),
            'COMI_PESC' => $getValue('COMI_PESC', 'COMI_PESC'),
            'NRO_CUPSS' => $getValue('NRO_CUPSS', 'NRO_CUPSS'),
            'AFIL_SIND' => $getValue('AFIL_SIND', 'AFIL_SIND'),
            'NRO_FICHA' => $getValue('NRO_FICHA', 'NRO_FICHA'),
            'TIPO_CONT' => $getValue('TIPO_CONT', 'TIPO_CONT'),
            'CODI_MONE' => $getValue('CODI_MONE', 'CODI_MONE'),
            'SUEL_NETO' => $getValue('SUEL_NETO', 'SUEL_NETO'),
            'CARG_FAMI' => $getValue('CARG_FAMI', 'CARG_FAMI'),
            'NACIONALIDAD' => $getValue('NACIONALIDAD', 'NACIONALIDAD'),
            'TIPO_SITU_LABO' => $getValue('TIPO_SITU_LABO', 'TIPO_SITU_LABO'),
            'SEGU_VIDA_LEY' => $getValue('SEGU_VIDA_LEY', 'SEGU_VIDA_LEY'),
            'PERS_CONBREVETE' => $getValue('PERS_CONBREVETE', 'PERS_CONBREVETE'),
            'FECH_CESE' => $getValue('FECH_CESE', 'FECH_CESE'),
            'CLAVE' => $getValue('CLAVE', 'CLAVE'),
            'JUBILADO' => $getValue('JUBILADO', 'JUBILADO'),
            'FORM_PAGO' => $getValue('FORM_PAGO', 'FORM_PAGO'),
            'CODI_ANTI' => $getValue('CODI_ANTI', 'CODI_ANTI'),
            'MONT_PAGO_COME' => $getValue('MONT_PAGO_COME', 'MONT_PAGO_COME'),
            'HORA_LABO' => $getValue('HORA_LABO', 'HORA_LABO'),
            'CODI_UNID_OPER' => $getValue('CODI_UNID_OPER', 'CODI_UNID_OPER'),
            'PERS_FECHEMISIONDNI' => $getValue('PERS_FECHEMISIONDNI', 'PERS_FECHEMISIONDNI'),
            'CODI_RELA' => $getValue('CODI_RELA', 'CODI_RELA'),
            'MOTI_CESE' => $getValue('MOTI_CESE', 'MOTI_CESE'),
            'PROVINCIA' => $getValue('PROVINCIA', 'PROVINCIA'),
            'DISTRITO' => $getValue('DISTRITO', 'DISTRITO'),
            'VCMTO' => $getValue('VCMTO', 'VCMTO'),
            'DEPARTAMENTO' => $getValue('DEPARTAMENTO', 'DEPARTAMENTO'),
            'OBSERVACIONES' => $getValue('OBSERVACIONES', 'OBSERVACIONES'),
            'CODI_TIPO_RIES' => $getValue('CODI_TIPO_RIES', 'CODI_TIPO_RIES'),
            'UBIGEO' => $getValue('UBIGEO', 'UBIGEO'),
            'FOTO_SI_NO' => $getValue('FOTO_SI_NO', 'FOTO_SI_NO'),
            'FECH_INIC_AFIL' => $getValue('FECH_INIC_AFIL', 'FECH_INIC_AFIL'),
            'DIST_NACI' => $getValue('DIST_NACI', 'DIST_NACI'),
            'PROV_NACI' => $getValue('PROV_NACI', 'PROV_NACI'),
            'UTIL03' => $getValue('UTIL03', 'UTIL03'),
            'CODI_AREA_GRUP' => $getValue('CODI_AREA_GRUP', 'CODI_AREA_GRUP'),
            'CODI_SUB_AREA_GRUP' => $getValue('CODI_SUB_AREA_GRUP', 'CODI_SUB_AREA_GRUP'),
            'HORA_AUTO' => $getValue('HORA_AUTO', 'HORA_AUTO'),
            'GRAT' => $getValue('GRAT', 'GRAT'),
            'VACA' => $getValue('VACA', 'VACA'),
            'codi_luga_trab' => $getValue('codi_luga_trab', 'codi_luga_trab'),
            'AFECTO_LEY' => $getValue('AFECTO_LEY', 'AFECTO_LEY'),
            'fotocheck' => $getValue('fotocheck', 'fotocheck'),
            'horario' => $getValue('horario', 'horario'),
            'CODI_CATE_TRAB' => $getValue('CODI_CATE_TRAB', 'CODI_CATE_TRAB'),
            'DEPA_CODIGO_NACI' => $getValue('DEPA_CODIGO_NACI', 'DEPA_CODIGO_NACI'),
            'PROVI_CODIGO_NACI' => $getValue('PROVI_CODIGO_NACI', 'PROVI_CODIGO_NACI'),
            'DEPA_CODIGO_DOMI' => $getValue('DEPA_CODIGO_DOMI', 'DEPA_CODIGO_DOMI'),
            'PROVI_CODIGO_DOMI' => $getValue('PROVI_CODIGO_DOMI', 'PROVI_CODIGO_DOMI'),
            'apor_essa' => $getValue('apor_essa', 'apor_essa'),
            'apor_sena' => $getValue('apor_sena', 'apor_sena'),
            'apor_senc' => $getValue('apor_senc', 'apor_senc'),
            'apor_cona' => $getValue('apor_cona', 'apor_cona'),
            'PERS_LUEXPDNI' => $getValue('PERS_LUEXPDNI', 'PERS_LUEXPDNI'),
            'PERS_NRORUC' => $getValue('PERS_NRORUC', 'PERS_NRORUC'),
            'PERS_NROLIBM' => $getValue('PERS_NROLIBM', 'PERS_NROLIBM'),
            'PERS_CONHIJOS' => $getValue('PERS_CONHIJOS', 'PERS_CONHIJOS'),
            'PERS_PROFESION' => $getValue('PERS_PROFESION', 'PERS_PROFESION'),
            'PERS_NROANTPOL' => $getValue('PERS_NROANTPOL', 'PERS_NROANTPOL'),
            'PERS_NORANTPEN' => $getValue('PERS_NORANTPEN', 'PERS_NORANTPEN'),
            'PERS_NROLICENCIA' => $getValue('PERS_NROLICENCIA', 'PERS_NROLICENCIA'),
            'PERS_SERIEARMA' => $getValue('PERS_SERIEARMA', 'PERS_SERIEARMA'),
            'PERS_ACEPTADTA' => $getValue('PERS_ACEPTADTA', 'PERS_ACEPTADTA'),
            'PERS_FECHAREG' => $getValue('PERS_FECHAREG', 'PERS_FECHAREG'),
            'PERS_VIGENCIA' => $getValue('PERS_VIGENCIA', 'PERS_VIGENCIA'),
            'USUA_CODIGO' => $getValue('USUA_CODIGO', 'USUA_CODIGO'),
            'PERS_MARCA' => $getValue('PERS_MARCA', 'PERS_MARCA'),
            'PERS_CALIBRE' => $getValue('PERS_CALIBRE', 'PERS_CALIBRE'),
            'PERS_MODELO' => $getValue('PERS_MODELO', 'PERS_MODELO'),
            'PERS_TIPOTRAB' => $getValue('PERS_TIPOTRAB', 'PERS_TIPOTRAB'),
            'PERS_CONTRATADO' => $getValue('PERS_CONTRATADO', 'PERS_CONTRATADO'),
            'EMPR_CODIGO' => $getValue('EMPR_CODIGO', 'EMPR_CODIGO'),
            'SUCU_CODIGO' => $getValue('SUCU_CODIGO', 'SUCU_CODIGO'),
            'USUA_CODIGO_REG' => $getValue('USUA_CODIGO_REG', 'USUA_CODIGO_REG'),
            'USUA_FECHA_REG' => $getValue('USUA_FECHA_REG', 'USUA_FECHA_REG'),
            'USUA_CODIGO_MOD' => $getValue('USUA_CODIGO_MOD', 'USUA_CODIGO_MOD'),
            'USUA_FECHA_MOD' => $getValue('USUA_FECHA_MOD', 'USUA_FECHA_MOD'),
            'PERS_FECHARETIRO' => $getValue('PERS_FECHARETIRO', 'PERS_FECHARETIRO'),
            'PERS_FLAG' => $getValue('PERS_FLAG', 'PERS_FLAG'),
            'cala_codigo' => $getValue('cala_codigo', 'cala_codigo'),
            'Pers_fech_venc_lic_arm' => $getValue('Pers_fech_venc_lic_arm', 'Pers_fech_venc_lic_arm'),
            'PERS_OBSERVACIONES' => $getValue('PERS_OBSERVACIONES', 'PERS_OBSERVACIONES'),
            'PERS_RESERVA' => $getValue('PERS_RESERVA', 'PERS_RESERVA'),
            'EMPRESA_ASOCIADA_5TA' => $getValue('EMPRESA_ASOCIADA_5TA', 'EMPRESA_ASOCIADA_5TA'),
            'MOCE_CODIGO' => $getValue('MOCE_CODIGO', 'MOCE_CODIGO'),
            'PERS_ACTUALIZAR' => $getValue('PERS_ACTUALIZAR', 'PERS_ACTUALIZAR'),
            'CONTROL_MIGRADO' => $getValue('CONTROL_MIGRADO', 'CONTROL_MIGRADO'),
            'PERS_PARA_FOTOCHECK' => $getValue('PERS_PARA_FOTOCHECK', 'PERS_PARA_FOTOCHECK'),
            'PERS_CON_FOTOCHECK' => $getValue('PERS_CON_FOTOCHECK', 'PERS_CON_FOTOCHECK'),
            'PERS_VERIF_RENIEC' => $getValue('PERS_VERIF_RENIEC', 'PERS_VERIF_RENIEC'),
            'PERS_VERIF_USUARIO' => $getValue('PERS_VERIF_USUARIO', 'PERS_VERIF_USUARIO'),
            'PERS_VERIF_FECHA' => $getValue('PERS_VERIF_FECHA', 'PERS_VERIF_FECHA'),
            'GRADO_INSTRUC_OBS' => $getValue('GRADO_INSTRUC_OBS', 'GRADO_INSTRUC_OBS'),
            'OBS_CESE' => $getValue('OBS_CESE', 'OBS_CESE'),
            'CONTROL_MIGRADO2' => $getValue('CONTROL_MIGRADO2', 'CONTROL_MIGRADO2'),
            'PERS_OMISO_ONPE' => $getValue('PERS_OMISO_ONPE', 'PERS_OMISO_ONPE'),
            'USUA_MOD_PLAN' => $getValue('USUA_MOD_PLAN', 'USUA_MOD_PLAN'),
            'FEC_MOD_PLAN' => $getValue('FEC_MOD_PLAN', 'FEC_MOD_PLAN'),
            'CONTROL_MIGRADO_COPE' => $getValue('CONTROL_MIGRADO_COPE', 'CONTROL_MIGRADO_COPE'),
            'CONTROL_MIGRADO_AUSTRAL' => $getValue('CONTROL_MIGRADO_AUSTRAL', 'CONTROL_MIGRADO_AUSTRAL'),
            'CONTROL_MIGRADO_CONSORCIO' => $getValue('CONTROL_MIGRADO_CONSORCIO', 'CONTROL_MIGRADO_CONSORCIO'),
            'CONTROL_MIGRADO_CODRALUX' => $getValue('CONTROL_MIGRADO_CODRALUX', 'CONTROL_MIGRADO_CODRALUX'),
            'CONTROL_VERIF_TRANS' => $getValue('CONTROL_VERIF_TRANS', 'CONTROL_VERIF_TRANS'),
            'TIZO_CODIGO' => $getValue('TIZO_CODIGO', 'TIZO_CODIGO'),
            'PERS_ZONA_DIRDNI' => $getValue('PERS_ZONA_DIRDNI', 'PERS_ZONA_DIRDNI'),
            'PERS_KM_MZ_DIRDNI' => $getValue('PERS_KM_MZ_DIRDNI', 'PERS_KM_MZ_DIRDNI'),
            'PERS_LOTE_DIRDNI' => $getValue('PERS_LOTE_DIRDNI', 'PERS_LOTE_DIRDNI'),
            'PERS_NRO_DIRDNI' => $getValue('PERS_NRO_DIRDNI', 'PERS_NRO_DIRDNI'),
            'PERS_KM_DIRDNI' => $getValue('PERS_KM_DIRDNI', 'PERS_KM_DIRDNI'),
            'PERS_MZ_DIRDNI' => $getValue('PERS_MZ_DIRDNI', 'PERS_MZ_DIRDNI'),
            'PERS_TRABAJO_ANTERIOR' => $getValue('PERS_TRABAJO_ANTERIOR', 'PERS_TRABAJO_ANTERIOR'),
            'CODI_TIPO_DOCU_ANT' => $getValue('CODI_TIPO_DOCU_ANT', 'CODI_TIPO_DOCU_ANT'),
            'CATEGORIA_BREVETE' => $getValue('tipo_vehiculo', 'CATEGORIA_BREVETE'),
            'FECH_EXP_BREVETE' => $getValue('FECH_EXP_BREVETE', 'FECH_EXP_BREVETE'),
            'FECH_REVAL_BREVETE' => $getValue('FECH_REVAL_BREVETE', 'FECH_REVAL_BREVETE'),
            'RESTRICCION_BREVETE' => $getValue('RESTRICCION_BREVETE', 'RESTRICCION_BREVETE'),
            'MIGRADO' => $getValue('MIGRADO', 'MIGRADO'),
            'SIP_fechaCreacion' => $getValue('SIP_fechaCreacion', 'SIP_fechaCreacion'),
            'SIP_habilitado' => $getValue('SIP_habilitado', 'SIP_habilitado'),
            'SIP_migrado' => 1, // ✅ Siempre 1
            'SIP_activo' => $getValue('SIP_activo', 'SIP_activo'),
            'SIP_fechaModifcacion' => $getValue('SIP_fechaModifcacion', 'SIP_fechaModifcacion'),
            'NO_CADUCA_DNI' => $getValue('NO_CADUCA_DNI', 'NO_CADUCA_DNI'),
        ];

        foreach ($updates as $field => $value) {
            $updates[$field] = $sanitizeFinalValue($field, $value);
        }

        // ✅ 6. Verificar si ya existe en DJ2026_PERSONAL
        $exists = DB::select(
            'SELECT 1 FROM si_solm.dbo.DJ2026_PERSONAL WHERE CODI_PERS = ?',
            [$codiPers]
        );

        try {
            if (!empty($exists)) {
                // ✅ UPDATE
                $updateFields = [];
                $params = [];

                foreach ($updates as $key => $value) {
                    if ($key === 'CODI_PERS') {
                        continue;
                    }
                    $updateFields[] = "[$key] = ?";
                    $params[] = $value;
                }

                $params[] = $codiPers;

                $sql = 'UPDATE si_solm.dbo.DJ2026_PERSONAL SET ' . implode(', ', $updateFields) . ' WHERE CODI_PERS = ?';

                Log::info('✅ Ejecutando UPDATE en DJ2026_PERSONAL', ['CODI_PERS' => $codiPers]);

                DB::update($sql, $params);

            } else {
                // ✅ INSERT
                $insertFields = array_keys($updates);
                $insertPlaceholders = array_fill(0, count($updates), '?');

                $sqlInsert = 'INSERT INTO si_solm.dbo.DJ2026_PERSONAL (' .
                    implode(', ', array_map(fn($f) => "[$f]", $insertFields)) .
                    ') VALUES (' . implode(', ', $insertPlaceholders) . ')';

                //              dd([
                //     'sql' => $sqlInsert,
                //     'updates' => $updates,
                //     'params' => array_values($updates),
                // ]);

                Log::info('✅ Ejecutando INSERT en DJ2026_PERSONAL', ['CODI_PERS' => $codiPers]);

                DB::insert($sqlInsert, array_values($updates));
            }

            Log::info('✅ DJ2026_PERSONAL guardado exitosamente', ['CODI_PERS' => $codiPers]);

        } catch (\Exception $e) {
            Log::error('❌ Error en INSERT/UPDATE DJ2026_PERSONAL:', [
                'error' => $e->getMessage(),
                'CODI_PERS' => $codiPers,
            ]);
            throw $e;
        }
    }

    /**
     * Sincronizar datos de DJ2026_PERSONAL hacia si_solm.dbo.PERSONAL
     * Solo actualiza columnas donde DJ2026_PERSONAL tenga valor NO NULL.
     * Si DJ2026_PERSONAL tiene NULL en una columna, NO borra lo que ya existe en PERSONAL.
     */
    private function syncDJ2026ToPersonal(string $codiPers)
    {
        // 1. Obtener el registro recién guardado en DJ2026_PERSONAL
        $djData = DB::select(
            'SELECT * FROM si_solm.dbo.DJ2026_PERSONAL WHERE CODI_PERS = ?',
            [$codiPers]
        );

        if (empty($djData)) {
            Log::warning('syncDJ2026ToPersonal: No se encontró registro en DJ2026_PERSONAL', ['CODI_PERS' => $codiPers]);

            return;
        }

        // 2. Obtener registro actual de PERSONAL
        $personalData = DB::select(
            'SELECT * FROM si_solm.dbo.PERSONAL WHERE CODI_PERS = ?',
            [$codiPers]
        );

        if (empty($personalData)) {
            Log::info('syncDJ2026ToPersonal: No existe registro en PERSONAL, no se sincroniza', ['CODI_PERS' => $codiPers]);

            return;
        }

        $djRecord = (array) $djData[0];
        $personalRecord = (array) $personalData[0];

        // 3. Campos datetime que necesitan sanitización de formato
        $datetimeFields = [
            'FECH_INGRE_PLANILLA',
            'FECH_INGRE',
            'FECH_NACI',
            'FECH_CESE',
            'PERS_FECHEMISIONDNI',
            'PERS_FECHCADUCADNI',
            'VCMTO',
            'FECH_INIC_AFIL',
            'PERS_FECHAREG',
            'USUA_FECHA_REG',
            'USUA_FECHA_MOD',
            'PERS_FECHARETIRO',
            'Pers_fech_venc_lic_arm',
            'PERS_VERIF_FECHA',
            'FEC_MOD_PLAN',
            'FECH_EXP_BREVETE',
            'FECH_REVAL_BREVETE',
        ];

        // 4. Columnas que se deben sincronizar (comunes entre DJ2026_PERSONAL y PERSONAL)
        // Excluimos CODI_PERS (es la PK) y campos exclusivos de DJ2026 que no existen en PERSONAL
        $columnsToSync = [
            'NRO_DOCU_IDEN',
            'CODI_TIPO_DOCU',
            'PERS_VEHICULO_PROPIO',
            'APEL_1',
            'APEL_2',
            'NOMB_1',
            'NOMB_2',
            'CODI_SIST_PENS',
            'SIST_PENS_TIPOCOMI',
            'CODI_CARG',
            'CODI_AREA',
            'CODI_MONE_BASI',
            'SUEL_BASI',
            'FECH_INGRE_PLANILLA',
            'FECH_INGRE',
            'ESSALUD',
            'CARN_ESSALUD',
            'ESTA_CIVI',
            'FECH_NACI',
            'ASIG_FAMI',
            'ESTA_ACTI',
            'SCRT',
            'COMI_PESC',
            'NRO_CUPSS',
            'AFIL_SIND',
            'NRO_FICHA',
            'TIPO_CONT',
            'CODI_MONE',
            'SUEL_NETO',
            'CARG_FAMI',
            'SEXO',
            'NACIONALIDAD',
            'DIRECCION',
            'PERS_EMAIL',
            'TIPO_SITU_LABO',
            'SEGU_VIDA_LEY',
            'PERS_CONBREVETE',
            'PERS_BREVETE',
            'FECH_CESE',
            'CLAVE',
            'JUBILADO',
            'FORM_PAGO',
            'CODI_ANTI',
            'MONT_PAGO_COME',
            'HORA_LABO',
            'CODI_UNID_OPER',
            'PERS_FECHEMISIONDNI',
            'PERS_FECHCADUCADNI',
            'CODI_RELA',
            'MOTI_CESE',
            'PROVINCIA',
            'DISTRITO',
            'VCMTO',
            'DEPARTAMENTO',
            'OBSERVACIONES',
            'CODI_TIPO_RIES',
            'UBIGEO',
            'FOTO_SI_NO',
            'FECH_INIC_AFIL',
            'DIST_NACI',
            'PROV_NACI',
            'UTIL03',
            'CODI_AREA_GRUP',
            'CODI_SUB_AREA_GRUP',
            'HORA_AUTO',
            'GRAT',
            'VACA',
            'codi_luga_trab',
            'AFECTO_LEY',
            'fotocheck',
            'horario',
            'CODI_CATE_TRAB',
            'ESCI_CODIGO',
            'DEPA_CODIGO_NACI',
            'PROVI_CODIGO_NACI',
            'DEPA_CODIGO_DOMI',
            'PROVI_CODIGO_DOMI',
            'apor_essa',
            'apor_sena',
            'apor_senc',
            'apor_cona',
            'tipo_sangr',
            'peso_kilo',
            'tall_metr',
            'PERS_LUEXPDNI',
            'PERS_NRORUC',
            'PERS_NROLIBM',
            //'PERS_CONYUGE',
            'PERS_EMERC_FAMILIAR',
            'PERS_CONHIJOS',
            'PERS_PROFESION',
            'PERS_NROANTPOL',
            'PERS_NORANTPEN',
            'PERS_CTRABANT',
            'PERS_CARGOTRABANT',
            'PERS_DURACIONANT',
            'PERS_SNADAR',
            'PERS_CONSMO',
            'PERS_LUGARSMO',
            'PERS_CONDISCAMEC',
            'PERS_NRODISCAMEC',
            'PERS_CONLICARMAS',
            'PERS_NROLICENCIA',
            'PERS_CONARMAS',
            'PERS_SERIEARMA',
            'PERS_TIPOARMA',
            'PERS_ACEPTADTA',
            'PERS_NROEMERGENCIA',
            'PERS_NOMCONTACTO',
            'PERS_FECHAREG',
            'PERS_VIGENCIA',
            'USUA_CODIGO',
            'PERS_TELEFONO',
            'PERS_MARCA',
            'PERS_CALIBRE',
            'PERS_MODELO',
            'PERS_TIPOTRAB',
            'PERS_CONTRATADO',
            'EMPR_CODIGO',
            'SUCU_CODIGO',
            'USUA_CODIGO_REG',
            'USUA_FECHA_REG',
            'USUA_CODIGO_MOD',
            'USUA_FECHA_MOD',
            'PERS_FECHARETIRO',
            'PERS_FLAG',
            'cala_codigo',
            'Pers_fech_venc_lic_arm',
            'PERS_OBSERVACIONES',
            'PERS_RESERVA',
            'EMPRESA_ASOCIADA_5TA',
            'MOCE_CODIGO',
            'PERS_ACTUALIZAR',
            'PERS_SEXO',
            'PERS_GRADO_INSTRUCCION',
            'PERS_DIREC_DNI',
            'CONTROL_MIGRADO',
            'PERS_PARA_FOTOCHECK',
            'PERS_CON_FOTOCHECK',
            'PERS_VERIF_RENIEC',
            'PERS_VERIF_USUARIO',
            'PERS_VERIF_FECHA',
            'PERS_DPTO_DIRDNI',
            'PERS_PROV_DIRDNI',
            'PERS_DIST_DIRDNI',
            'GRADO_INSTRUC_OBS',
            'OBS_CESE',
            'CONTROL_MIGRADO2',
            'PERS_PENSIONISTA',
            'PERS_OMISO_ONPE',
            'USUA_MOD_PLAN',
            'FEC_MOD_PLAN',
            'CONTROL_MIGRADO_COPE',
            'CONTROL_MIGRADO_AUSTRAL',
            'CONTROL_MIGRADO_CONSORCIO',
            'CONTROL_MIGRADO_CODRALUX',
            'CONTROL_VERIF_TRANS',
            'TIZO_CODIGO',
            'PERS_ZONA_DIRDNI',
            'PERS_KM_MZ_DIRDNI',
            'PERS_LOTE_DIRDNI',
            'PERS_NRO_DIRDNI',
            'PERS_KM_DIRDNI',
            'PERS_MZ_DIRDNI',
            'IEDU_CODIGO',
            'CARR_CODIGO',
            'EGRESO_EDUCATIVO',
            'PERS_TRABAJO_ANTERIOR',
            'CODI_TIPO_DOCU_ANT',
            'CLASE_BREVETE',
            'CATEGORIA_BREVETE',
            'FECH_EXP_BREVETE',
            'FECH_REVAL_BREVETE',
            'RESTRICCION_BREVETE',
            'PERS_DEPT_ACT',
            'PERS_PROV_ACT',
            'PERS_DIST_ACT',
            'PERS_EMBARGO',
            'PERS_WHATSAPP',
            'PERS_SMO',
            'dj2026_familiar_empresa',
            'dj2026_banco',
            'dj2026_cantprofesion',
            'dj2026_ciudad_naci',
            'dj2026_ocupacion_principal',
            'dj2026_experiencia_anios',
            'dj2026_familiar_nombre',
            'dj2026_familiar_parentesco',
            'dj2026_laboral_1',
            'dj2026_laboral_2',
        ];

        // 5. Construir UPDATE dinámico: solo columnas donde DJ2026 NO es NULL
        $updateFields = [];
        $params = [];

        foreach ($columnsToSync as $col) {
            // Solo actualizar si DJ2026_PERSONAL tiene valor (no importa si PERSONAL ya tiene dato)
            if (array_key_exists($col, $djRecord) && !is_null($djRecord[$col])) {
                $value = $djRecord[$col];

                // Sanitizar campos datetime para SQL Server
                if (in_array($col, $datetimeFields, true)) {
                    $value = $this->sanitizeDatetimeForPersonal($value);
                    if (is_null($value)) {
                        continue; // Fecha inválida, no actualizar esta columna
                    }
                }

                $updateFields[] = "[$col] = ?";
                $params[] = $value;
            }
        }

        if (empty($updateFields)) {
            Log::info('syncDJ2026ToPersonal: No hay columnas con valor para sincronizar', ['CODI_PERS' => $codiPers]);

            return;
        }

        // 6. Ejecutar UPDATE en PERSONAL
        $params[] = $codiPers;
        $sql = 'UPDATE si_solm.dbo.PERSONAL SET ' . implode(', ', $updateFields) . ' WHERE CODI_PERS = ?';

        try {
            DB::update($sql, $params);
            Log::info('syncDJ2026ToPersonal: PERSONAL actualizado correctamente', [
                'CODI_PERS' => $codiPers,
                'columnas_actualizadas' => count($updateFields),
            ]);
        } catch (\Exception $e) {
            Log::error('syncDJ2026ToPersonal: Error al actualizar PERSONAL', [
                'CODI_PERS' => $codiPers,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sanitizar valor datetime para que SQL Server lo acepte.
     * Usa formato YYYYMMDD HH:MM:SS.mmm (sin guiones en fecha) que es
     * SIEMPRE seguro en SQL Server independientemente del DATEFORMAT/LANGUAGE.
     */
    private function sanitizeDatetimeForPersonal(mixed $value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        // Si es un objeto DateTime, formatear directamente al formato seguro
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Ymd H:i:s.v');
        }

        if (is_string($value)) {
            $value = trim($value);

            // Descartar valores inválidos
            if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
                return null;
            }

            // Reemplazar T por espacio
            $value = str_replace('T', ' ', $value);

            // Intentar parsear con DateTime para cualquier formato de entrada
            try {
                $dt = new \DateTime($value);

                // Formato YYYYMMDD HH:MM:SS.mmm - seguro para SQL Server con cualquier DATEFORMAT
                return $dt->format('Ymd H:i:s.v');
            } catch (\Exception $e) {
                Log::warning('sanitizeDatetimeForPersonal: Fecha no parseable', ['value' => $value]);

                return null;
            }
        }

        return null;
    }

    // ============================================
    // MÉTODOS PRIVADOS AUXILIARES
    // ============================================

    private function updateMigraPersonal(string $codiPers, array $data)
    {
        // Obtener datos actuales
        $current = DB::select(
            'SELECT * FROM sisolm_web.dbo.sw_MIGRA_PERSONAL WHERE CODI_PERS = ?',
            [$codiPers]
        );

        $base = !empty($current) ? (array) $current[0] : ['CODI_PERS' => $codiPers];

        // ✅ MAPEAR DESDE LOS NOMBRES DEL FORMULARIO HTML
        $updates = array_merge($base, [
            'NRO_DOCU_IDEN' => $data['dni'] ?? $base['NRO_DOCU_IDEN'] ?? '',
            'NOMB_1' => $this->extraerNombre($data['nombres_apellidos'] ?? '', 0),
            'NOMB_2' => $this->extraerNombre($data['nombres_apellidos'] ?? '', 1),
            'APEL_1' => $this->extraerApellido($data['nombres_apellidos'] ?? '', 0),
            'APEL_2' => $this->extraerApellido($data['nombres_apellidos'] ?? '', 1),
            'PERS_EMAIL' => $data['correo'] ?? '',
            'PERS_TELEFONO' => $data['celular'] ?? '',
            'PERS_WHATSAPP' => $data['whatsapp'] ?? '',
            'DIRECCION' => $data['direccion_actual'] ?? '',
            'PERS_SEXO' => $data['sexo'] ?? 'M',
            'SEXO' => $data['sexo'] ?? 'M',
            'ESCI_CODIGO' => $data['estado_civil'] ?? '',
            'PERS_FECHCADUCADNI' => $data['caduca'] ?? null,
            'FECH_NACI' => $data['fecha_nacimiento'] ?? null,
            'PERS_SNADAR' => $data['sabe_nadar'] ?? 'NO',
            'tipo_sangr' => $data['tipo_sangre'] ?? null,
            'peso_kilo' => $data['peso'] ?? null,
            'tall_metr' => $data['talla'] ?? null,
            'CODI_SIST_PENS' => $data['sistema_previsional'] ?? '',
            'ESSALUD' => $data['essalud'] ?? 'NO',
            'PERS_PENSIONISTA' => $data['pensionista'] ?? 'NO',
            'PERS_EMBARGO' => $data['embargos'] ?? 'NO',
            'PERS_CONSMO' => $data['consumo_sustancias'] != 'NO' ? 'SI' : 'NO',
            'PERS_SMO' => $data['consumo_sustancias'] ?? 'NO',
            'PERS_DEPT_ACT' => $data['departamento_actual'] ?? null,
            'PERS_PROV_ACT' => $data['provincia_actual'] ?? null,
            'PERS_DIST_ACT' => $data['distrito_actual'] ?? null,
            'PERS_DPTO_DIRDNI' => $data['departamento_dni'] ?? null,
            'PERS_PROV_DIRDNI' => $data['provincia_dni'] ?? null,
            'PERS_DIST_DIRDNI' => $data['distrito_dni'] ?? null,
            'PERS_DIREC_DNI' => $data['direccion_dni'] ?? null,
            'PERS_GRADO_INSTRUCCION' => $data['grado_instruccion'] ?? null,
            'CARR_CODIGO' => !empty($data['carrera']) ? $data['carrera'] : null,
            'IEDU_CODIGO' => $data['institucion'] ?? null,
            'EGRESO_EDUCATIVO' => $data['anio_egreso'] ?? null,
            'PERS_CONDISCAMEC' => $data['curso_sucamec'] ?? 'NO',
            'PERS_NRODISCAMEC' => $data['sucamec_obs'] ?? null,
            //'PERS_SMO' => $data['smo'] ?? 'NO',
            'PERS_LUGARSMO' => ($data['consumo_sustancias'] ?? 'NO') !== 'NO' ? $data['consumo_sustancias'] : null,
            'PERS_NROLICENCIA' => $data['licencia_arma'] ?? null,
            'PERS_TIPOARMA' => $data['tipo_arma'] ?? null,
            'PERS_CONARMAS' => $data['arma_propia'] ?? 'NO',
            'PERS_BREVETE' => $data['brevete'] ?? null,
            'CLASE_BREVETE' => $data['clase_brevete'] ?? null,
            'PERS_TIPO_VEHICULO' => $data['tipo_vehiculo'] ?? null,
            'PERS_VEHICULO_PROPIO' => $data['vehiculo_propio'] ?? 'NO',
            'PERS_NOMCONTACTO' => $data['contacto_emergencia'] ?? '',
            'PERS_NROEMERGENCIA' => $data['celular_emergencia'] ?? '',
            //'PERS_CONYUGE' => $data['parentesco_emergencia'] ?? null,
            'PERS_EMERC_FAMILIAR' => $data['parentesco_emergencia'] ?? null,
            'PERS_CTRABANT' => $data['empresa_anterior'] ?? null,
            'PERS_CARGOTRABANT' => $data['cargo_anterior'] ?? null,
            'PERS_DURACIONANT' => $data['duracion_anterior'] ?? null,
            'dj2026_banco' => $data['cuenta_banco'] ?? null,
            'dj2026_ciudad_naci' => $data['ciudad_nacimiento'] ?? null,
            'dj2026_ocupacion_principal' => $data['ocupacion_principal'] ?? null,
            'dj2026_experiencia_anios' => $data['experiencia_anios'] ?? null,
            'dj2026_familiar_empresa' => $data['familiar_empresa'] ?? 'NO',
            'dj2026_familiar_nombre' => $data['familiar_nombre'] ?? null,
            'dj2026_familiar_parentesco' => $data['familiar_parentesco'] ?? null,
            'dj2026_cantprofesion' => isset($data['ocupacion_alterna']) ? count(array_filter($data['ocupacion_alterna'])) : 0,
        ]);

        // Construir UPDATE dinámico
        $updateFields = [];
        $params = [];

        foreach ($updates as $key => $value) {
            if ($key === 'CODI_PERS') {
                continue;
            }
            $updateFields[] = "[$key] = ?";
            $params[] = $value;
        }

        $params[] = $codiPers;

        $sql = 'UPDATE sisolm_web.dbo.sw_MIGRA_PERSONAL SET ' . implode(', ', $updateFields) . ' WHERE CODI_PERS = ?';

        $affected = DB::update($sql, $params);

        // Si no afectó ninguna fila, hacer INSERT
        if ($affected === 0) {
            $insertFields = array_keys($updates);
            $insertPlaceholders = array_fill(0, count($updates), '?');

            $sqlInsert = 'INSERT INTO sisolm_web.dbo.sw_MIGRA_PERSONAL (' .
                implode(', ', array_map(fn($f) => "[$f]", $insertFields)) .
                ') VALUES (' . implode(', ', $insertPlaceholders) . ')';

            DB::insert($sqlInsert, array_values($updates));
        }
    }

    // ✅ AGREGAR ESTAS FUNCIONES HELPER
    private function extraerNombre(string $nombreCompleto, int $index): string
    {
        $partes = preg_split('/\s+/', trim($nombreCompleto));
        // Asumiendo formato: NOMBRE1 NOMBRE2 APELLIDO1 APELLIDO2
        if (count($partes) >= 3) {
            return $partes[$index] ?? '';
        }

        return '';
    }

    private function extraerApellido(string $nombreCompleto, int $index): string
    {
        $partes = preg_split('/\s+/', trim($nombreCompleto));
        if (count($partes) >= 3) {
            $apellidoIndex = $index + 2; // Los apellidos empiezan en posición 2

            return $partes[$apellidoIndex] ?? '';
        }

        return '';
    }

    private function migrarPersonal(string $codiPers)
    {
        // Verificar si ya existe en DJ2026_PERSONAL
        $exists = DB::select(
            'SELECT 1 FROM si_solm.dbo.DJ2026_PERSONAL WHERE CODI_PERS = ?',
            [$codiPers]
        );

        if (!empty($exists)) {
            // Ya existe, hacer UPDATE
            DB::statement(
                'UPDATE si_solm.dbo.DJ2026_PERSONAL 
                SET NOMB_1 = s.NOMB_1, NOMB_2 = s.NOMB_2, APEL_1 = s.APEL_1, APEL_2 = s.APEL_2,
                    PERS_EMAIL = s.PERS_EMAIL, PERS_TELEFONO = s.PERS_TELEFONO, PERS_WHATSAPP = s.PERS_WHATSAPP,
                    DIRECCION = s.DIRECCION, PERS_SEXO = s.PERS_SEXO, SEXO = s.SEXO,
                    ESCI_CODIGO = s.ESCI_CODIGO, PERS_SNADAR = s.PERS_SNADAR,
                    tipo_sangr = s.tipo_sangr, peso_kilo = s.peso_kilo, tall_metr = s.tall_metr,
                    CODI_SIST_PENS = s.CODI_SIST_PENS, ESSALUD = s.ESSALUD, PERS_PENSIONISTA = s.PERS_PENSIONISTA,
                    PERS_EMBARGO = s.PERS_EMBARGO, PERS_CONSMO = s.PERS_CONSMO,
                    PERS_DEPT_ACT = s.PERS_DEPT_ACT, PERS_PROV_ACT = s.PERS_PROV_ACT, PERS_DIST_ACT = s.PERS_DIST_ACT,
                    PERS_DPTO_DIRDNI = s.PERS_DPTO_DIRDNI, PERS_PROV_DIRDNI = s.PERS_PROV_DIRDNI, PERS_DIST_DIRDNI = s.PERS_DIST_DIRDNI,
                    PERS_DIREC_DNI = s.PERS_DIREC_DNI, PERS_GRADO_INSTRUCCION = s.PERS_GRADO_INSTRUCCION,
                    CARR_CODIGO = s.CARR_CODIGO, IEDU_CODIGO = s.IEDU_CODIGO, EGRESO_EDUCATIVO = s.EGRESO_EDUCATIVO,
                    PERS_CONDISCAMEC = s.PERS_CONDISCAMEC, PERS_NRODISCAMEC = s.PERS_NRODISCAMEC,
                    PERS_SMO = s.PERS_SMO, PERS_LUGARSMO = s.PERS_LUGARSMO,
                    PERS_CONLICARMAS = s.PERS_CONLICARMAS, PERS_TIPOARMA = s.PERS_TIPOARMA, PERS_CONARMAS = s.PERS_CONARMAS,
                    PERS_BREVETE = s.PERS_BREVETE, CLASE_BREVETE = s.CLASE_BREVETE,
                    PERS_TIPO_VEHICULO = s.PERS_TIPO_VEHICULO, PERS_VEHICULO_PROPIO = s.PERS_VEHICULO_PROPIO,
                    PERS_NOMCONTACTO = s.PERS_NOMCONTACTO, PERS_NROEMERGENCIA = s.PERS_NROEMERGENCIA,
                    PERS_EMERC_FAMILIAR = s.PERS_EMERC_FAMILIAR, 
                 
                    
                    PERS_CTRABANT = s.PERS_CTRABANT,
                    PERS_CARGOTRABANT = s.PERS_CARGOTRABANT, PERS_DURACIONANT = s.PERS_DURACIONANT,
                    dj2026_banco = s.dj2026_banco, dj2026_ciudad_naci = s.dj2026_ciudad_naci,
                    dj2026_ocupacion_principal = s.dj2026_ocupacion_principal, dj2026_experiencia_anios = s.dj2026_experiencia_anios,
                    dj2026_familiar_empresa = s.dj2026_familiar_empresa, dj2026_familiar_nombre = s.dj2026_familiar_nombre,
                    dj2026_familiar_parentesco = s.dj2026_familiar_parentesco, dj2026_cantprofesion = s.dj2026_cantprofesion
                FROM sisolm_web.dbo.sw_MIGRA_PERSONAL s
                WHERE si_solm.dbo.DJ2026_PERSONAL.CODI_PERS = s.CODI_PERS 
                AND s.CODI_PERS = ?',
                [$codiPers]
            );
        } else {
            // No existe, hacer INSERT completo
            DB::statement(
                'INSERT INTO si_solm.dbo.DJ2026_PERSONAL
                SELECT * FROM sisolm_web.dbo.sw_MIGRA_PERSONAL WHERE CODI_PERS = ?',
                [$codiPers]
            );
        }
    }

    private function saveFamiliares(string $codiPers, array $data)
    {
        if (!isset($data['FAM_NOMBRES']) || !is_array($data['FAM_NOMBRES'])) {
            Log::info('No hay familiares para guardar');

            return;
        }

        // Limpiar registros previos
        DB::delete(
            'DELETE FROM sisolm_web.dbo.sw_MIGRA_DERECHO_HABIENTE WHERE CODI_PERS = ?',
            [$codiPers]
        );

        // Insertar nuevos
        foreach ($data['FAM_NOMBRES'] as $index => $nombreCompleto) {
            if (empty($nombreCompleto)) {
                continue;
            }

            $parentesco = $data['FAM_PARENTESCO'][$index] ?? '';
            $fechaNaci = $data['FAM_FECHA_NACI'][$index] ?? null;

            // ✅ Split mejorado del nombre completo
            // Formato esperado: "APELLIDO1 APELLIDO2, NOMBRE1 NOMBRE2"
            $nombreCompleto = trim($nombreCompleto);

            // Si tiene coma, dividir por coma
            if (strpos($nombreCompleto, ',') !== false) {
                [$apellidos, $nombres] = explode(',', $nombreCompleto, 2);
                $apellidos = trim($apellidos);
                $nombres = trim($nombres);

                $apellidosParts = preg_split('/\s+/', $apellidos, 2);
                $nombresParts = preg_split('/\s+/', $nombres, 2);

                $apel1 = $apellidosParts[0] ?? '';
                $apel2 = $apellidosParts[1] ?? '';
                $nomb1 = $nombresParts[0] ?? '';
                $nomb2 = $nombresParts[1] ?? '';
            } else {
                // Sin coma, asumir: APEL1 APEL2 NOMB1 NOMB2
                $nameParts = preg_split('/\s+/', $nombreCompleto);
                $apel1 = $nameParts[0] ?? '';
                $apel2 = $nameParts[1] ?? '';
                $nomb1 = $nameParts[2] ?? '';
                $nomb2 = isset($nameParts[3]) ? implode(' ', array_slice($nameParts, 3)) : '';
            }

            // DB::insert(
            //     "INSERT INTO sisolm_web.dbo.sw_MIGRA_DERECHO_HABIENTE
            //     (CODI_PERS, TIPO_RELA, APEL_1, APEL_2, NOMB_1, NOMB_2, FECH_NACI)
            //     VALUES (?, ?, ?, ?, ?, ?, ?)",
            //     [$codiPers, $parentesco, $apel1, $apel2, $nomb1, $nomb2, $fechaNaci]
            // );

            DB::insert(
                'INSERT INTO si_solm.dbo.DERECHO_HABIENTE 
            (CODI_PERS, TIPO_RELA, APEL_1, APEL_2, NOMB_1, NOMB_2, FECH_NACI) 
            VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$codiPers, $parentesco, $apel1, $apel2, $nomb1, $nomb2, $fechaNaci]
            );
        }
    }

    private function migrarFamiliares(string $codiPers)
    {
        // Eliminar todos los familiares existentes del trabajador en DJ2026_DERECHO_HABIENTE
        DB::delete(
            'DELETE FROM si_solm.dbo.DERECHO_HABIENTE
         WHERE CODI_PERS = ?',
            [$codiPers]
        );

        // Insertar familiares en DJ2026_DERECHO_HABIENTE excluyendo la columna identity CODI_DERE_HABI
        DB::statement(
            'INSERT INTO si_solm.dbo.DERECHO_HABIENTE
        (
        
            CODI_PERS,
            TIPO_RELA,
            NOMB_1,
            NOMB_2,
            APEL_1,
            APEL_2,
            CODI_TIPO_DOCU,
            NRO_DOCU_IDEN,
            FECH_NACI,
            FALLECIDO,
            DEHA_OCUPACION,
            DEHA_EDAD,
            USUA_CODIGO_REG,
            USUA_FECHA_REG,
            USUA_CODIGO_MOD,
            USUA_FECHA_MOD,
            DEHA_SEXO,
            
    
            DEHA_MES_CONCEPCION,
            DEHA_FECHA_ALTA,
            DEHA_TIPO_BAJA,
            DEHA_FECHA_BAJA,
            DEHA_INCAPACIDAD,
            DEHA_RESOL_INCAPACIDAD,
            DEHA_VIGENCIA,
            DEHA_telefono,
            domicilio,
            DEHA_DEREHABI,
            TIDV_CODIGO
        )
        SELECT
       
            CODI_PERS,
            TIPO_RELA,
            NOMB_1,
            NOMB_2,
            APEL_1,
            APEL_2,
            CODI_TIPO_DOCU,
            NRO_DOCU_IDEN,
            FECH_NACI,
            FALLECIDO,
            DEHA_OCUPACION,
            DEHA_EDAD,
            USUA_CODIGO_REG,
            GETDATE(),
            USUA_CODIGO_MOD,
            GETDATE(),
            DEHA_SEXO,
           
       
            DEHA_MES_CONCEPCION,
            DEHA_FECHA_ALTA,
            DEHA_TIPO_BAJA,
            DEHA_FECHA_BAJA,
            DEHA_INCAPACIDAD,
            DEHA_RESOL_INCAPACIDAD,
            DEHA_VIGENCIA,
            DEHA_telefono,
            domicilio,
            DEHA_DEREHABI,
            TIDV_CODIGO
        FROM sisolm_web.dbo.sw_MIGRA_DERECHO_HABIENTE
        WHERE CODI_PERS = ?',
            [$codiPers]
        );

        // Eliminar todos los familiares existentes del trabajador en DERECHO_HABIENTE
        DB::delete(
            'DELETE FROM si_solm.dbo.DERECHO_HABIENTE
         WHERE CODI_PERS = ?',
            [$codiPers]
        );

        // Insertar familiares en DERECHO_HABIENTE excluyendo la columna identity CODI_DERE_HABI
        DB::statement("
        INSERT INTO si_solm.dbo.DERECHO_HABIENTE
        (
            CODI_PERS,
            TIPO_RELA,
            NOMB_1,
            NOMB_2,
            APEL_1,
            APEL_2,
            CODI_TIPO_DOCU,
            NRO_DOCU_IDEN,
            FECH_NACI,
            FALLECIDO,
            DEHA_OCUPACION,
            DEHA_EDAD,
            USUA_CODIGO_REG,
            USUA_FECHA_REG,
            USUA_CODIGO_MOD,
            USUA_FECHA_MOD,
            DEHA_SEXO,
            DEHA_MES_CONCEPCION,
            DEHA_FECHA_ALTA,
            DEHA_TIPO_BAJA,
            DEHA_FECHA_BAJA,
            DEHA_INCAPACIDAD,
            DEHA_RESOL_INCAPACIDAD,
            DEHA_VIGENCIA,
            DEHA_telefono,
            domicilio,
            DEHA_DEREHABI,
            TIDV_CODIGO
        )
        SELECT
            CODI_PERS,
            TIPO_RELA,
            NOMB_1,
            NOMB_2,
            APEL_1,
            APEL_2,
            CODI_TIPO_DOCU,
            NRO_DOCU_IDEN,
            TRY_CONVERT(datetime, NULLIF(FECH_NACI, ''), 103),
            FALLECIDO,
            DEHA_OCUPACION,
            DEHA_EDAD,
            USUA_CODIGO_REG,
            GETDATE(),
            USUA_CODIGO_MOD,
            GETDATE(),
            DEHA_SEXO,
            DEHA_MES_CONCEPCION,
            TRY_CONVERT(datetime, NULLIF(DEHA_FECHA_ALTA, ''), 103),
            DEHA_TIPO_BAJA,
            TRY_CONVERT(datetime, NULLIF(DEHA_FECHA_BAJA, ''), 103),
            DEHA_INCAPACIDAD,
            DEHA_RESOL_INCAPACIDAD,
            TRY_CONVERT(datetime, NULLIF(DEHA_VIGENCIA, ''), 103),
            DEHA_telefono,
            domicilio,
            DEHA_DEREHABI,
            TIDV_CODIGO
        FROM sisolm_web.dbo.sw_MIGRA_DERECHO_HABIENTE
        WHERE CODI_PERS = ?
    ", [$codiPers]);
    }

    // private function migrarFamiliares($codiPers)
    // {
    //     // Limpiar destino
    //     DB::delete(
    //         "DELETE TOP (1) FROM si_solm.dbo.DJ2026_DERECHO_HABIENTE WHERE CODI_PERS = ?",
    //         [$codiPers]
    //     );

    //     // Copiar de migración a DJ2026
    //     DB::statement(
    //         "INSERT INTO si_solm.dbo.DJ2026_DERECHO_HABIENTE
    //         SELECT * FROM sisolm_web.dbo.sw_MIGRA_DERECHO_HABIENTE WHERE CODI_PERS = ?",
    //         [$codiPers]
    //     );

    //     // DB::delete(
    //     //     "DELETE TOP (1) FROM si_solm.dbo.DERECHO_HABIENTE WHERE CODI_PERS = ?",
    //     //     [$codiPers]
    //     // );

    //     // DB::statement(
    //     //     "INSERT INTO si_solm.dbo.DERECHO_HABIENTE
    //     //     SELECT * FROM sisolm_web.dbo.sw_MIGRA_DERECHO_HABIENTE WHERE CODI_PERS = ?",
    //     //     [$codiPers]
    //     // );
    // }

    private function saveOcupaciones(string $dni, array $data)
    {
        if (!isset($data['dj2026_descripcion']) || !is_array($data['dj2026_descripcion'])) {
            Log::info('No hay ocupaciones para guardar');

            return;
        }

        // Limpiar previas
        DB::delete(
            'DELETE FROM sisolm_web.dbo.dj2026_OCUPACIONES WHERE PERS_DNI = ?',
            [$dni]
        );

        // Insertar nuevas (máximo 2)
        $ocupaciones = array_filter($data['dj2026_descripcion']);
        $ocupaciones = array_slice($ocupaciones, 0, 2);

        foreach ($ocupaciones as $desc) {
            DB::insert(
                'INSERT INTO sisolm_web.dbo.dj2026_OCUPACIONES (PERS_DNI, dj2026_descripcion) VALUES (?, ?)',
                [$dni, strtoupper(trim($desc))]
            );
        }
    }

    private function migrarOcupaciones(string $dni)
    {
        // Limpiar destino
        DB::delete(
            'DELETE FROM si_solm.dbo.DJ2026_OCUPACIONES_PER WHERE PERS_DNI = ?',
            [$dni]
        );

        // Copiar
        DB::statement(
            'INSERT INTO si_solm.dbo.DJ2026_OCUPACIONES_PER (PERS_DNI, dj2026_descripcion)
            SELECT PERS_DNI, dj2026_descripcion FROM sisolm_web.dbo.dj2026_OCUPACIONES WHERE PERS_DNI = ?',
            [$dni]
        );
    }

    private function formatDatesForInput(array $data): array
    {
        // Formatear fechas
        $dateFields = ['FECH_NACI', 'PERS_FECHCADUCADNI', 'FECH_INGRE', 'FECH_CESE'];

        foreach ($dateFields as $field) {
            if (isset($data[$field])) {
                if ($data[$field] instanceof \DateTime) {
                    $data[$field] = $data[$field]->format('Y-m-d');
                } elseif (is_string($data[$field]) && strpos($data[$field], ' ') !== false) {
                    $data[$field] = explode(' ', $data[$field])[0];
                }
            }
        }

        // ✅ Limpiar campos numéricos (quitar texto, dejar solo números)
        $numericFields = ['dj2026_experiencia_anios', 'peso_kilo', 'tall_metr', 'EGRESO_EDUCATIVO'];

        foreach ($numericFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                // Extraer solo números (y punto decimal si existe)
                $data[$field] = preg_replace('/[^0-9.]/', '', $data[$field]);
            }
        }

        return $data;
    }

    private function groupFamiliares(array $familiares): array
    {
        $grouped = [
            'padres' => [],
            'madre' => [],
            'hijos' => [],
            'conyugue' => [],
        ];

        foreach ($familiares as $f) {
            $f = (array) $f;

            switch ($f['TIPO_RELA']) {
                case 'PADRE':
                case 'MADRE':
                case 'HERMANO':
                    $grouped['padres'][] = $f;
                    break;
                case 'HIJO':
                case 'HIJA':
                    $grouped['hijos'][] = $f;
                    break;
                case 'CONYUGE':
                case 'Conyuge':
                case 'CONVIVIENTE':
                    $grouped['conyugue'][] = $f;
                    break;
            }
        }

        return $grouped;
    }

    public function proxyFoto(Request $request)
    {
        $codiPers = $request->get('codi_pers');
        if (!$codiPers || !preg_match('/^[a-zA-Z0-9]+$/', $codiPers)) {
            return response()->json(['success' => false], 400);
        }

        $url = "http://190.116.178.163/Biblioteca_Grafica/Fotos/{$codiPers}.jpg";

        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            if ($httpCode !== 200 || empty($imageData)) {
                return response()->json(['success' => false, 'message' => 'Imagen no encontrada'], 404);
            }

            $base64 = 'data:' . ($contentType ?: 'image/jpeg') . ';base64,' . base64_encode($imageData);

            return response()->json([
                'success' => true,
                'base64' => $base64,
            ]);
        } catch (\Exception $e) {
            Log::error('Error proxy foto: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error al obtener imagen'], 500);
        }
    }

    public function reportePersonalSinMigracion(Request $request)
    {
        try {
            $codSucursal = $request->get('codSucursal', '00');
            $codTipoPer = $request->get('codTipoPer', '00');

            $data = DB::select(
                'EXEC sisolm_web.dbo.SW_REPORTE_LISTAR_PERSONAL_SIN_MIGRACION ?, ?',
                [$codSucursal, $codTipoPer]
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en reportePersonalSinMigracion: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function reportePersonalSinMigracionV2(Request $request)
    {
        try {
            $codSucursal = $request->get('codSucursal', '00');
            $codTipoPer = $request->get('codTipoPer', '00');
            $tipo = $request->get('tipo', null);

            $data = DB::select(
                'EXEC sisolm_web.dbo.SW_REPORTE_LISTAR_PERSONAL_SIN_MIGRACION ?, ?, ?',
                [$codSucursal, $codTipoPer, $tipo]
            );

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);

        } catch (\Exception $e) {
            Log::error('Error en reportePersonalSinMigracionV2: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }


    public function updateCheckPdf(Request $request)
    {
        try {
            $codigos = $request->input('codigos'); // array de CODI_PERS
            $usuario = session('usuario') ?? 'SISTEMA';

            if (empty($codigos) || !is_array($codigos)) {
                return response()->json(['success' => false, 'message' => 'Códigos requeridos'], 400);
            }

            $placeholders = implode(',', array_fill(0, count($codigos), '?'));

            DB::update(
                "UPDATE sisolm_web.dbo.sw_MIGRA_PERSONAL
             SET CHECK_PDF = 1, CHECK_PDF_FECHA = GETDATE(), CHECK_PDF_USER = ?
             WHERE CODI_PERS IN ({$placeholders})",
                array_merge([$usuario], $codigos)
            );

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error en updateCheckPdf: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Resetear todos los CHECK_PDF (o solo los visibles)
     */
    public function resetCheckPdf(Request $request)
    {
        try {
            $codigos = $request->input('codigos'); // opcional: si viene, resetea solo esos
            $usuario = session('usuario') ?? 'SISTEMA';

            if (!empty($codigos) && is_array($codigos)) {
                $placeholders = implode(',', array_fill(0, count($codigos), '?'));
                DB::update(
                    "UPDATE sisolm_web.dbo.sw_MIGRA_PERSONAL
                 SET CHECK_PDF = 0, CHECK_PDF_FECHA = NULL, CHECK_PDF_USER = NULL
                 WHERE CODI_PERS IN ({$placeholders})",
                    $codigos
                );
            } else {
                DB::update(
                    "UPDATE sisolm_web.dbo.sw_MIGRA_PERSONAL
                 SET CHECK_PDF = 0, CHECK_PDF_FECHA = NULL, CHECK_PDF_USER = NULL
                 WHERE SIP_migrado = 1"
                );
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Error en resetCheckPdf: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    private function saveTelefonosTemp(string $codiPers, array $data)
    {
        $telPersonal   = isset($data['celular'])           ? trim($data['celular'])           : '';
        $telWsp        = isset($data['whatsapp'])          ? trim($data['whatsapp'])          : '';
        $telEmergencia = isset($data['celular_emergencia']) ? trim($data['celular_emergencia']) : '';

        // $observNombre = trim(
        //     (isset($data['APEL_1']) ? $data['APEL_1'].' ' : '').
        //     (isset($data['APEL_2']) ? $data['APEL_2'].' ' : '').
        //     (isset($data['NOMB_1']) ? $data['NOMB_1'].' ' : '').
        //     (isset($data['NOMB_2']) ? $data['NOMB_2']      : '')
        // );
        $observNombre = trim(
            (isset($data['apellido_paterno']) ? $data['apellido_paterno'].' ' : '').
            (isset($data['apellido_materno']) ? $data['apellido_materno'].' ' : '').
            (isset($data['nombre1'])          ? $data['nombre1'].' '          : '').
            (isset($data['nombre2'])          ? $data['nombre2']              : '')
        );

        // 1. Borrar registros previos
        DB::delete(
            'DELETE FROM sisolm_web.dbo.sw_MIGRA_TELEFONO WHERE CODI_PERS = ?',
            [$codiPers]
        );

        $telefonos = [];

        // 2. Lógica personal vs whatsapp
        if ($telPersonal === $telWsp) {
            // Son iguales → un solo registro con NRO_WSP = 1
            if (!empty($telPersonal) && strlen($telPersonal) <= 12) {
                $telefonos[] = [
                    'NRO_TELE'         => $telPersonal,
                    'TIPO_TELE'        => 'MOVIL',
                    'TELE_EMERGENCIA'  => '0',
                    'NRO_WSP'         => 1,
                    'TELE_CONTACTO'    => null,
                    'VINCULO_FAMILIAR' => null,
                    'OBSERVACION'      => $observNombre,
                ];
            }
        } else {
            // Son diferentes → dos registros
            if (!empty($telPersonal) && strlen($telPersonal) <= 12) {
                $telefonos[] = [
                    'NRO_TELE'         => $telPersonal,
                    'TIPO_TELE'        => 'MOVIL',
                    'TELE_EMERGENCIA'  => '0',
                    'NRO_WSP'         => 0,
                    'TELE_CONTACTO'    => null,
                    'VINCULO_FAMILIAR' => null,
                    'OBSERVACION'      => $observNombre,
                ];
            }
            if (!empty($telWsp) && strlen($telWsp) <= 12) {
                $telefonos[] = [
                    'NRO_TELE'         => $telWsp,
                    'TIPO_TELE'        => 'MOVIL',
                    'TELE_EMERGENCIA'  => '0',
                    'NRO_WSP'         => 1,
                    'TELE_CONTACTO'    => null,
                    'VINCULO_FAMILIAR' => null,
                    'OBSERVACION'      => $observNombre,
                ];
            }
        }

        // Emergencia
        if (!empty($telEmergencia) && strlen($telEmergencia) <= 12) {
            $telefonos[] = [
                'NRO_TELE'         => $telEmergencia,
                'TIPO_TELE'        => 'MOVIL',
                'TELE_EMERGENCIA'  => '1',
                'NRO_WSP'         => 0,
                'TELE_CONTACTO'    => isset($data['contacto_emergencia']) ? trim($data['contacto_emergencia']) : null,
                'VINCULO_FAMILIAR' => isset($data['parentesco_emergencia']) ? trim($data['parentesco_emergencia']) : null,
                'OBSERVACION'      => isset($data['contacto_emergencia']) ? trim($data['contacto_emergencia']) : null,
            ];
        }

        // 3. Insertar
        foreach ($telefonos as $tel) {
            DB::insert(
                'INSERT INTO sisolm_web.dbo.sw_MIGRA_TELEFONO
                (CODI_PERS, NRO_TELE, TIPO_TELE, OBSERVACION,
                TELE_VIGENCIA, TELE_RESERVADO, TELE_EMERGENCIA,
                NRO_WSP, TELE_CONTACTO, VINCULO_FAMILIAR)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $codiPers,
                    substr($tel['NRO_TELE'], 0, 12),
                    $tel['TIPO_TELE'],
                    $tel['OBSERVACION'],
                    'SI',
                    '0',
                    $tel['TELE_EMERGENCIA'],
                    $tel['NRO_WSP'],
                    $tel['TELE_CONTACTO'],
                    $tel['VINCULO_FAMILIAR'],
                ]
            );
        }

        Log::info('saveTelefonosTemp: teléfonos guardados en sw_MIGRA_TELEFONO', [
            'CODI_PERS' => $codiPers,
            'total'     => count($telefonos),
        ]);
    }

    private function migrarTelefonos(string $codiPers)
    {
        // 1. Borrar teléfonos existentes en tabla original
        DB::delete(
            'DELETE FROM si_solm.dbo.TELEFONO WHERE CODI_PERS = ?',
            [$codiPers]
        );

        // 2. Insertar desde sw_MIGRA_TELEFONO a TELEFONO
        DB::statement(
            'INSERT INTO si_solm.dbo.TELEFONO
            (CODI_PERS, NRO_TELE, TIPO_TELE, OBSERVACION,
            TELE_VIGENCIA, TELE_RESERVADO, TELE_EMERGENCIA,
            NRO_WSP, TELE_CONTACTO, VINCULO_FAMILIAR)
            SELECT
                CODI_PERS,
                NRO_TELE,
                TIPO_TELE,
                OBSERVACION,
                TELE_VIGENCIA,
                TELE_RESERVADO,
                TELE_EMERGENCIA,
                NRO_WSP,
                TELE_CONTACTO,
                VINCULO_FAMILIAR
            FROM sisolm_web.dbo.sw_MIGRA_TELEFONO
            WHERE CODI_PERS = ?',
            [$codiPers]
        );

        Log::info('migrarTelefonos: teléfonos migrados a si_solm.dbo.TELEFONO', [
            'CODI_PERS' => $codiPers,
        ]);
    }

    /**
     * Obtener estado CHECK_PDF de todos los migrados
     */
    public function getCheckPdf()
    {
        try {
            $data = DB::select(
                "SELECT CODI_PERS, CHECK_PDF, CHECK_PDF_FECHA, CHECK_PDF_USER
             FROM sisolm_web.dbo.sw_MIGRA_PERSONAL
             WHERE SIP_migrado = 1"
            );

            return response()->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            Log::error('Error en getCheckPdf: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
