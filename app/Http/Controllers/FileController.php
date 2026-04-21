<?php

namespace App\Http\Controllers;

use App\Helpers\PdfHelper;
use App\Helpers\ImagenHelper;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DB;
use Illuminate\Http\Request;
use App\Models\FileControl;
use App\Models\Matricula;
use App\Models\Cargo;
use App\Models\Personal;
use App\Models\Folio;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use ZipArchive;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller{
    public function index(){
        $personal = FileControl::getPersonal();
        $clientes = FileControl::getClientesLegajos();
        $sucursales = FileControl::getSucursales();
        return view('file_control.chargefile', compact('personal', 'clientes', 'sucursales'));
    }

    public function indexGestionDj()
    {
        $grados = FileControl::getGradosInstruccion();
        $carreras = FileControl::getCarreras();
        $instituciones = FileControl::getInstituciones();
        return view('file_control.gestion_dj', compact('grados', 'carreras', 'instituciones'));
    }

    public function ViewReportes()
    {
        $clientes = FileControl::getClientes();
        $sucursales = FileControl::getSucursales();
        $cargos = FileControl::getCargos();
        return view('file_control.reportes', compact('clientes', 'sucursales', 'cargos'));
    }

    public function getCargosXCliente(Request $request){
        $cliente = $request->input('cliente');
        $cargos = FileControl::getCargosXCliente($cliente);
        return response()->json($cargos);
    }

    public function ViewDashboard(){
        return view('file_control.dashboard');
    }

    public function getPersonal(Request $request){
        try {
            $allPersonal = FileControl::getPersonal();
            $cursoId = $request->input('cursoId');
            $paginationMode = $request->input('pagination');

            if ($paginationMode === 'off') {
                if ($cursoId) {
                    if (!is_numeric($cursoId)) throw new \Exception("El ID del curso no es válido.");
                    $matriculados = Matricula::where('cod_curso', $cursoId)->pluck('cod_personal')->toArray();
                    $allPersonal = array_map(function($persona) use ($matriculados) {
                        $persona->matriculado = in_array($persona->CODI_PERS, $matriculados);
                        return $persona;
                    }, $allPersonal);
                }
                return response()->json(array_values($allPersonal));
            }

            $page = (int) $request->input('page', 1);
            $size = (int) $request->input('size', 50);
            $search = $request->input('filter', '');

            if (!empty($search)) {
                $searchLower = strtolower($search);
                $allPersonal = array_filter($allPersonal, function($persona) use ($searchLower) {
                    $nombre = strtolower($persona->personal ?? '');
                    $doc = strtolower($persona->nroDoc ?? '');
                    return str_contains($nombre, $searchLower) || str_contains($doc, $searchLower);
                });
                $allPersonal = array_values($allPersonal);
            }

            $total = count($allPersonal);
            $offset = ($page - 1) * $size;
            $personalPaginado = array_slice($allPersonal, $offset, $size);

            if ($cursoId) {
                if (!is_numeric($cursoId)) throw new \Exception("El ID del curso no es válido.");
                $matriculados = Matricula::where('cod_curso', $cursoId)->pluck('cod_personal')->toArray();
                $personalPaginado = array_map(function($persona) use ($matriculados) {
                    $persona->matriculado = in_array($persona->CODI_PERS, $matriculados);
                    return $persona;
                }, $personalPaginado);
            }

            return response()->json([
                'data' => $personalPaginado,
                'last_page' => (int) ceil($total / $size),
                'total' => $total,
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            Log::error("Error en FileController@getPersonal: " . $e->getMessage());
            return response()->json([
                'data' => [], 'last_page' => 1, 'total' => 0,
                'status' => 'error', 'error_message' => $e->getMessage()
            ]);
        }
    }

    public function getPersonalTotal(Request $request)
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 50);
        $search = $request->get('search', null);
        $tipo_per = $request->get('tipo_per', null);
        $vigencia = $request->get('vigencia', null);
        $codSucursal = $request->get('codSucursal', '0');

        $data = DB::select('EXEC SW_LISTAR_PERSONAL_X_SUCURSAL_TOTAL ?, ?, ?, ?, ?, ?', [
            $codSucursal, $page, $size, $search, $tipo_per, $vigencia
        ]);
        $total = DB::select('EXEC SW_CONTAR_PERSONAL ?, ?, ?, ?', [
            $codSucursal, $search, $tipo_per, $vigencia
        ])[0]->total;

        return response()->json([
            'data' => $data,
            'last_page' => ceil($total / $size),
            'total' => (int) $total,
        ]);
    }

    public function getPersonalTotalPrueba(Request $request)
    {
        $page = $request->get('page', 1);
        $size = $request->get('size', 50);
        $search = $request->get('search', null);
        $tipo_per = $request->get('tipo_per', null);
        $vigencia = $request->get('vigencia', null);
        $codSucursal = $request->get('codSucursal', '0');

        $data = DB::select('EXEC SW_LISTAR_PERSONAL_X_SUCURSAL_TOTAL_PRUEBA ?, ?, ?, ?, ?, ?', [
            $codSucursal, $page, $size, $search, $tipo_per, $vigencia
        ]);
        $total = DB::select('EXEC SW_CONTAR_PERSONAL ?, ?, ?, ?', [
            $codSucursal, $search, $tipo_per, $vigencia
        ])[0]->total;

        return response()->json([
            'data' => $data,
            'last_page' => ceil($total / $size),
            'total' => (int) $total,
        ]);
    }

    public function getDocumentosXPersonal($codPersonal){
        $docs_personal = FileControl::getDocsXPersona($codPersonal);
        return response()->json($docs_personal);
    }

    public function getFoliosXPersonas(Request $request){
        $personas = $request->personas;
        $folios = $request->folios;
        $resultados = [];

        foreach ($personas as $persona) {
            $sucursal = FileControl::getSucursalXPersona($persona['CODI_PERS']);
            foreach ($folios as $folio) {
                $datosFolioPersona = FileControl::getFoliosInfoPersona($persona['CODI_PERS'], $folio['codigo']);
                foreach ($datosFolioPersona as $dato) {
                    $resultados[] = [
                        'persona' => $dato->personal ?? null,
                        'nroDoc' => $dato->nroDoc ?? null,
                        'codPersonal' => $persona['CODI_PERS'],
                        'folio' => $folio['nombre'],
                        'sucursal' => $sucursal,
                        'ruta' => $dato->ruta_archivo ?? null,
                        'ancho' => $dato->ancho ?? null,
                        'hojas' => $dato->cantidad_hojas ?? null,
                        'documento' => $dato->documento ?? null,
                        'cargo' => $dato->cargo ?? null,
                        'es_formato' => $dato->es_formato ?? null,
                    ];
                }
            }
        }
        return response()->json($resultados);
    }

    public function getFoliosXPersona_uno(Request $request){
        $persona = $request->input('codPersona');
        $folios = $request->folios;
        $resultados = [];
        $sucursal = FileControl::getSucursalXPersona($persona);

        foreach ($folios as $folio) {
            $datosFolioPersona = FileControl::getFoliosInfoPersona($persona, $folio['codigo']);
            foreach ($datosFolioPersona as $dato) {
                $resultados[] = [
                    'persona' => $dato->personal ?? null,
                    'nroDoc' => $dato->nroDoc ?? null,
                    'codPersonal' => $persona,
                    'folio' => $folio['nombre'],
                    'sucursal' => $sucursal,
                    'ruta' => $dato->ruta_archivo ?? null,
                    'ancho' => $dato->ancho ?? null,
                    'hojas' => $dato->cantidad_hojas ?? null,
                    'documento' => $dato->documento ?? null,
                    'cargo' => $dato->cargo ?? null,
                    'es_formato' => $dato->es_formato ?? null,
                ];
            }
        }
        return response()->json($resultados);
    }

    public function generarPDF_env(Request $request){
        echo "Hola";
    }

    public function pdf_vacio()
    {
        $pdf = SnappyPdf::loadView('file_control.pdf.reporte_vacio');
        return $pdf->inline('reporte_vacio.pdf');
    }

    public function generarPDF(Request $request){
        $resultados = $request->input('resultados');
        $unicos = [];
        $nombreNuevo = 'Reporte';

        foreach ($resultados as $item) {
            $clave = $item['persona'] . '|' . $item['sucursal'] . '|' . $item['codPersonal'] . '|' . $item['cargo'];
            if(!isset($unicos[$clave])) {
                $unicos[$clave] = [
                    'persona' => $item['persona'],
                    'codPersonal' => $item['codPersonal'],
                    'sucursal' => $item['sucursal'],
                    'cargo' => $item['cargo'],
                ];
            }
            $nombreNuevo = $item['codPersonal'].'_'.$item['persona'] . '_' . date('Ymd_Hi');
        }

        $personasUnicas = array_values($unicos);
        $urls = [];

        foreach ($resultados as $resultado) {
            if (!empty($resultado['ruta']) && !empty($resultado['codPersonal'])) {
                $urls[] = [
                    'ruta' => $resultado['ruta'],
                    'codPersonal' => $resultado['codPersonal'],
                    'ancho' => $resultado['ancho'],
                    'hojas' => $resultado['hojas'],
                    'documento' => $resultado['documento'],
                    'es_formato' => $resultado['es_formato'],
                ];
            }
        }

        $rutasLocales = PdfHelper::descargarImagenesLegajo($urls);
        $itemsFinales = [];

        foreach ($rutasLocales as $item) {
            $itemsFinales[] = [
                'es_formato' => $item['es_formato'],
                'codPersonal' => $item['codPersonal'],
                'ruta' => $item['ruta'],
                'documento' => $item['documento'],
                'hojas' => $item['hojas'],
                'ancho' => $item['ancho'],
            ];
        }

        foreach ($resultados as $resultado) {
            if ($resultado['es_formato'] == 1) {
                $itemsFinales[] = [
                    'es_formato' => $resultado['es_formato'],
                    'codPersonal' => $resultado['codPersonal'],
                    'documento' => $resultado['documento'],
                    'nombre_vista' => $this->obtenerNombreVista($resultado),
                    'datos' => $resultado,
                    'firma' => public_path('temp_legajos').'/FIRMAS/PERSONAL/'.$resultado['codPersonal'].'.jpg',
                    'huella' => public_path('temp_legajos').'/HUELLAS_DIGITALES/PERSONAL/'.$resultado['codPersonal'].'.jpg',
                ];
                $rutasLocalesFormato = ImagenHelper::descargarImagenesFormato($resultado['codPersonal']);
            }
        }

        $pdf = SnappyPdf::loadView('file_control.pdf.reporte', [
            'personas' => $personasUnicas,
            'resultados' => $resultados,
            'imagenes' => $rutasLocales,
            'items' => $itemsFinales,
        ])->setOption('enable-local-file-access', true);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $nombreNuevo . '.pdf', [
            'Content-Type' => 'application/pdf',
            'X-Nombre-Archivo' => $nombreNuevo . '.pdf',
        ]);
    }

    public function obtenerNombreVista($resultado) {
        $mapa = [
            'ACTA DE COMPROMISO' => 'file_control.pdf.acta_compromiso',
            'DECLARACION JURADA DE CUMPLIMIENTO DE DISPOSICIONES' => 'file_control.pdf.declaracion_jurada',
            'EVALUACION DEL POSTULANTE' => 'file_control.pdf.evaluacion_postulante',
            'COMPROMISO DE CONFIABILIDAD' => 'file_control.pdf.compromiso_confidencialidad',
            'CUMPLIMIENTO DE cumpliDISPOSICION' => 'file_control.pdf.dj_complumiento_disposicion',
            'ESTUDIO DE SEGURIDAD DE TRABAJADORES' => 'file_control.pdf.dj_complumiento_disposicion',
            'VISITA DOMICILIARIA' => 'file_control.pdf.visita_domiciliaria_concluciones'
        ];
        return $mapa[$resultado['documento']] ?? null;
    }

    public function generarReporteConsulta(Request $request){
        $codigo = $request->input('valor');
        $data = FileControl::getReporteFiltro($codigo);
        return response()->json($data);
    }

    public function generarPDF2(Request $request) {
        $resultados = $request->input('resultados');
        $html = '';
        $vistas = [
            'file_control.pdf.evaluacion-postulante',
            'file_control.pdf.declaracion-jurada',
            'file_control.pdf.acta_compromiso',
            'file_control.pdf.compromiso_confidencialidad',
            'file_control.pdf.dj_cumplimiento_disposicion',
            'file_control.pdf.estudio_seguridad_empleado',
            'file_control.pdf.estudio_seguridad_empleado_2',
            'file_control.pdf.visita_domiciliaria',
            'file_control.pdf.visita_domiciliaria_concluciones',
        ];

        foreach ($vistas as $vista) {
            $html .= view($vista, ['resultados' => $resultados])->render();
            $html .= '<div style="page-break-after: always;"></div>';
        }

        $pdf = SnappyPdf::loadHTML($html)->setOption('enable-local-file-access', true);
        return response($pdf->inline('reporte_completo.pdf'))->header('Content-Type', 'application/pdf');
    }

    public function getAllCargo()
    {
        $data = FileControl::getAllCargo();
        return response()->json($data);
    }

    public function getClientes(){
        $data = FileControl::getClientes();
        return response()->json($data);
    }

    public function getClientesLegajos(){
        $data = FileControl::getClientesLegajos();
        return response()->json($data);
    }

    public function getLegajos(Request $request)
    {
        $cliente = $request->input('cliente');
        $cargo = $request->input('cargo');
        $codPersonal = $request->input('codigo');
        $legajos = FileControl::getLegajos($cliente, $cargo, $codPersonal);
        return response()->json($legajos);
    }

    public function getFoliosClienteCargo(Request $request){
        $cliente = $request->input('cliente');
        $cargo = $request->input('cargo');
        $legajos = FileControl::getFoliosClienteCargo($cliente, $cargo);
        return response()->json($legajos);
    }

    public function getFoliosXLegajo_comercial($codCliente, $codCargo){
        $folios = FileControl::getFoliosXLegajo_comercial($codCliente, $codCargo);
        return response()->json($folios);
    }

    public function getCargos(){
        $cargos = FileControl::getCargos();
        return response()->json($cargos);
    }

    public function getFolios()
    {
        $folios = DB::select("EXEC SW_LISTAR_FOLIOS");
        $encargados = DB::table('sw_folio_encargado')->get()->keyBy('cod_folio');

        $sucursales = DB::table('sw_folio_sucursal as fs')
            ->where('fs.habilitado', 1)
            ->select('fs.cod_folio', 'fs.sucu_codigo', 'fs.sucu_abreviatura', 'fs.sucu_descripcion')
            ->get()
            ->groupBy('cod_folio');

        foreach ($folios as $folio) {
            $folio->cod_responsable = $encargados->get($folio->codigo)->cod_rol ?? null;
            $folio->sucursales = isset($sucursales[$folio->codigo])
                ? $sucursales[$folio->codigo]->map(fn($s) => [
                    'codigo'      => $s->sucu_abreviatura,
                    'sucu_codigo' => $s->sucu_codigo,
                    'descripcion' => $s->sucu_descripcion
                  ])->values()->toArray()
                : [];
        }

        return response()->json($folios);
    }

    public function ViewCargo()
    {
        $todos = Cargo::habilitado()->count();
        $operativo = Cargo::operativo()->habilitado()->count();
        $administrativo = Cargo::administrativo()->habilitado()->count();
        return view('file_control.cargo', compact('todos', 'operativo', 'administrativo'));
    }

    public function ViewLegajo()
    {
        $notif = FileControl::listarNotificaciones();
        return view('file_control.legajos', ['notify' => $notif]);
    }

    public function ViewFolios()
    {
        $periodos = FileControl::getPeriodos();
        $todos = Folio::habilitado()->count();
        $principal = Folio::habilitado()->where('obligatorio', 1)->count();
        $adicional = Folio::habilitado()->where('obligatorio', 0)->count();
        $documento = Folio::habilitado()->where('tipo', 1)->count();
        $formato = Folio::habilitado()->where('tipo', 2)->count();
        $certificado = Folio::habilitado()->where('tipo', 3)->count();
        $roles = FileControl::getRoles();
        return view('file_control.folios', compact('periodos', 'todos', 'principal', 'adicional', 'documento', 'formato', 'certificado', 'roles'));
    }

    public function ViewBusquedaLegajo()
    {
        $personal = FileControl::getPersonalLegajos();
        $cargos = FileControl::getCargos();
        $clientes = FileControl::getClientesLegajos();
        return view('file_control.search_legajos', compact('personal', 'cargos', 'clientes'));
    }

    public function ViewLegajoPdf()
    {
        $sucursales = FileControl::getSucursales();
        $cargos = FileControl::getCargos();
        $clientes = FileControl::getClientes();
        return view('file_control.legajos_pdf', compact('sucursales', 'cargos', 'clientes'));
    }

    public function getPersonalLegajos(Request $request)
    {
        $personal = FileControl::getPersonalLegajos();
        return response()->json($personal);
    }

    public function getCoincidencias(Request $request)
    {
        $cliente = $request->input('cliente');
        $cargo = $request->input('cargo');
        $legajos = FileControl::getCoincidencias($cliente, $cargo);
        return response()->json($legajos);
    }

    public function saveFolioPersona(Request $request){
        $validated = $request->validate([
            'fecha_emision' => 'required|date',
            'fecha_caducidad' => 'nullable|date',
        ]);

        $codPersonal = $request->input('codPersonal');
        $rutas = FileControl::getRutaFolio($request->input('codFolio'));
        $archivos = $request->file('imagenes');
        $totalArchivos = count($archivos);
        $totalRutas = count($rutas);

        if ($totalRutas !== 1 && $totalRutas !== $totalArchivos) {
            return response()->json(['error' => 'La cantidad de rutas no coincide con la cantidad de archivos.'], 400);
        }

        foreach ($archivos as $index => $archivo) {
            if ($totalRutas === $totalArchivos) {
                $nameFile = $codPersonal . '.jpg';
                $rutaArchivo = $rutas[$index];
            } else {
                $nameFile = $codPersonal . '_' . ($index + 1) . '.jpg';
                $rutaArchivo = $rutas[0];
            }

            $response = Http::withToken('457862h45hj7u5126h58d2s51s2s')
                ->attach('archivo', file_get_contents($archivo), $archivo->getClientOriginalName())
                ->post('http://190.116.178.163/apps/api/file-control/charge-file_fin.php', [
                    'nameFile' => $nameFile,
                    'ruta' => $rutaArchivo
                ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => 'No se pudo guardar el archivo en el servidor remoto',
                    'detalle' => $response->body()
                ], 500);
            }

            $archivo->storeAs('uploads/folios', $nameFile);
        }

        $inserted = FileControl::saveFolioPersonal(
            $validated['fecha_emision'],
            $validated['fecha_caducidad'],
            $request->codFolio,
            $request->codPersonal,
        );

        return response()->json(['message' => 'Folios del persona guardados']);
    }

    public function getViewDocumentsPer($codPersonal, $codFolio)
    {
        $result = FileControl::getViewPerDocs($codPersonal, $codFolio);

        if (empty($result)) {
            return response()->json(['success' => false, 'message' => 'No se encontraron rutas'], 404);
        }

        $extensiones = ['jpg', 'jpeg', 'png', 'pdf'];
        $rutasValidas = [];

        foreach ($result as $item) {
            $rutaEncontrada = false;

            if (isset($item->ruta_aux)) {
                $rutaBase = str_replace('//', 'http://', $item->ruta_aux);
                foreach ($extensiones as $ext) {
                    $rutaConExt = $rutaBase . '.' . $ext;
                    if (self::urlExiste($rutaConExt)) {
                        $rutasValidas[] = $rutaConExt;
                        $rutaEncontrada = true;
                        break;
                    }
                }
            }

            if (!$rutaEncontrada && isset($item->ruta)) {
                $rutaBase = str_replace('//', 'http://', $item->ruta);
                foreach ($extensiones as $ext) {
                    $rutaConExt = $rutaBase . '.' . $ext;
                    if (self::urlExiste($rutaConExt)) {
                        $rutasValidas[] = $rutaConExt;
                        break;
                    }
                }
            }
        }

        if (empty($rutasValidas)) {
            return response()->json(['success' => false, 'message' => 'No se encontraron archivos accesibles desde la red']);
        }

        return response()->json(['success' => true, 'rutas' => $rutasValidas]);
    }

    private static function urlExiste($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $statusCode === 200;
    }

    public function saveFolioPersona3(Request $request)
    {
        \Log::info('TEMP DIR: ' . sys_get_temp_dir());
        \Log::info('_FILES', $_FILES);
        \Log::info('REQUEST', $request->all());
        return response()->json(['message' => 'Revisado en log.'], 200);
    }

    public function saveFolioPersona2(Request $request)
    {
        $cant = 0;
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $archivo) {
                $cant = $cant + 1;
            }
        }
        return response()->json("hay ".$cant." archivos");
    }

    public function saveFolioPersona0(Request $request)
    {
        $request->validate([
            'fecha_emision' => 'required|date',
            'fecha_caducidad' => 'nullable|date',
            'codFolio' => 'required|integer',
            'codPersonal' => 'required|string',
            'imagenes' => 'required|array',
            'imagenes.*' => 'image|max:5120'
        ]);

        $codFolio = $request->input('codFolio');
        $codPersonal = $request->input('codPersonal');
        $imagenes = $request->file('imagenes');
        $subCarpetas = FileControl::getRutaFolio($codFolio);

        if (count($subCarpetas) !== count($imagenes)) {
            return response()->json(['error' => 'El número de imágenes no coincide con el número de rutas esperadas para el folio.'], 400);
        }

        foreach ($imagenes as $index => $imagen) {
            $rutaSubcarpeta = $subCarpetas[$index] ?? null;
            if (!$rutaSubcarpeta) continue;
            $rutaDestino = public_path('Biblioteca_Grafica/' . $rutaSubcarpeta);
            if (!file_exists($rutaDestino)) mkdir($rutaDestino, 0777, true);
            $nombreArchivo = $codPersonal . '.' . $imagen->getClientOriginalExtension();
            $imagen->move($rutaDestino, $nombreArchivo);
        }

        return response()->json(['message' => 'Folio guardado exitosamente.']);
    }

    // ============================================================
    // GUARDAR FOLIO — con sucursales
    // ============================================================
    public function saveFolio(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|max:255',
            'tipo'        => 'required|integer',
            'responsable' => 'required|integer',
        ]);

        $codigo      = $request->input('codigo');
        $nombre      = $request->input('nombre');
        $tipo        = $request->input('tipo');
        $obligatorio = $request->input('obligatorio');
        $vencimiento = $request->input('vencimiento');
        $tipo_fecha  = $request->input('periodo');
        $plataforma  = $request->input('plataforma');
        $responsable = $request->input('responsable');
        $sucursales  = $request->input('sucursales', []);

        try {
            if (empty($codigo)) {
                // ── CREAR NUEVO FOLIO ──
                $inserted = FileControl::saveFolio($nombre, $tipo, $obligatorio, $vencimiento, $tipo_fecha, $plataforma);

                if ($inserted) {
                    $lastId = DB::getPdo()->lastInsertId();

                    if ($responsable) {
                        DB::table('sw_folio_encargado')->insert([
                            'cod_folio'  => $lastId,
                            'cod_rol'    => $responsable,
                            'habilitado' => 1
                        ]);
                    }

                    if (!empty($sucursales)) {
                        foreach ($sucursales as $suc) {
                            DB::table('sw_folio_sucursal')->insert([
                                'cod_folio'        => $lastId,
                                'sucu_abreviatura' => $suc['codigo']      ?? null,
                                'sucu_codigo'      => $suc['sucu_codigo'] ?? null,
                                'sucu_descripcion' => $suc['descripcion'] ?? null,
                                'habilitado'       => 1
                            ]);
                        }
                    }
                }

            } else {
                // ── EDITAR FOLIO EXISTENTE ──
                $inserted = FileControl::updateFolio($codigo, $nombre, $tipo, $obligatorio, $vencimiento, $tipo_fecha, $plataforma);

                if ($inserted && $responsable) {
                    DB::table('sw_folio_encargado')->updateOrInsert(
                        ['cod_folio' => $codigo],
                        ['cod_rol' => $responsable, 'habilitado' => 1]
                    );
                }

                if ($inserted) {
                    DB::table('sw_folio_sucursal')->where('cod_folio', $codigo)->delete();
                    if (!empty($sucursales)) {
                        foreach ($sucursales as $suc) {
                            DB::table('sw_folio_sucursal')->insert([
                                'cod_folio'        => $codigo,
                                'sucu_abreviatura' => $suc['codigo']      ?? null,
                                'sucu_codigo'      => $suc['sucu_codigo'] ?? null,
                                'sucu_descripcion' => $suc['descripcion'] ?? null,
                                'habilitado'       => 1
                            ]);
                        }
                    }
                }
            }

            if ($inserted) {
                return response()->json(['success' => true, 'message' => 'Folio guardado correctamente']);
            } else {
                return response()->json(['success' => false, 'message' => 'Error al guardar el folio'], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Error en saveFolio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function disabledFolio(Request $request){
        $codigo = $request->codigo;
        $result = FileControl::disabledFolio($codigo);
        if ($result) {
            return response()->json(['message' => 'Folio deshabilitado exitosamente'], 200);
        } else {
            return response()->json(['message' => 'Folio no encontrado o error al deshabilitar'], 404);
        }
    }

    public function activarFolio(Request $request){
        $codigo = $request->codigo;
        $result = FileControl::activarFolio($codigo);
        if ($result) {
            return response()->json(['message' => 'Folio activado exitosamente'], 200);
        } else {
            return response()->json(['message' => 'Folio no encontrado o error al activar'], 404);
        }
    }

    public function saveCargo(Request $request){
        $descripcion = $request->input('descripcion');
        $nombre = $request->input('nombre');
        $cod_tipo = $request->input('tipoCargo');
        $abreviatura = $request->input('abreviatura');
        $cod_servicio = $request->input('codPosicion');
        $cod_subservicio = $request->input('codGrupo');
        $cod_area = $request->input('codArea');
        $usuario = session('usuario');
        $inserted = FileControl::saveCargo($descripcion, $nombre, $abreviatura, $cod_servicio, $cod_subservicio, $cod_tipo, $cod_area, $usuario);
        return response()->json(['message' => 'Cargo creado']);
    }

    public function dashboard()
    {
        return view('file_control.dashboard');
    }

    public function getFoliosXLegajo($codCliente, $codCargo){
        $folios = FileControl::getFoliosXLegajo($codCliente, $codCargo);
        return response()->json($folios);
    }

    public function getAreas(){
        $areas = FileControl::getAreas();
        return response()->json($areas);
    }

    public function getPosicion(){
        $data = FileControl::getPosicion();
        return response()->json($data);
    }

    public function getGrupo(){
        $data = FileControl::getGrupo();
        return response()->json($data);
    }

    public function getGrupoId($codigo){
        $data = FileControl::getGrupoId($codigo);
        return response()->json($data);
    }

    public function saveLegajo(Request $request){
        $folios = $request->input('folios');
        $codCliente = $request->input('codCliente');
        $codCargo = $request->input('codCargo');
        $codLegajo = $request->input('codLegajo');
        $nombre = $request->input('nombre');
        $usuario = session('usuario');

        if($codLegajo != '0'){
            $codigos = FileControl::QuitarTodosLegajos($codLegajo, $usuario);
            for ($i = 0; $i < count($codigos); $i++) {
                if (!in_array($codigos[$i], $folios)) {
                    FileControl::actualizarNotificacionDes($codigos[$i], $codCliente, $codCargo);
                }
            }
            for($i = 0; $i < count($folios); $i++){
                $folio = $folios[$i];
                $validar = FileControl::validarLegajo($folio, $codCliente, $codCargo, $codLegajo);
                FileControl::actualizarNotificacion($folio, $codCliente, $codCargo);
                if(empty($validar)){
                    FileControl::saveLegajo($folio, $codCliente, $codCargo, $codLegajo, $usuario);
                }else{
                    FileControl::updateLegajo($folio, $codCliente, $codCargo, $codLegajo, $usuario);
                }
            }
        }else{
            $legajo = FileControl::saveLegajoMain($nombre);
            for($i = 0; $i < count($folios); $i++){
                $folio = $folios[$i];
                $validar = FileControl::validarLegajo($folio, $codCliente, $codCargo, $legajo);
                FileControl::actualizarNotificacion($folio, $codCliente, $codCargo);
                if(empty($validar)){
                    FileControl::saveLegajo($folio, $codCliente, $codCargo, $legajo, $usuario);
                }
            }
        }
    }

    public function insertarCargo(Request $request)
    {
        $data = [
            $request->input('tipoCargo'),
            $request->input('codArea'),
            $request->input('nombre'),
            $request->input('descripcion'),
            $request->input('abreviatura'),
            $request->input('codPosicion'),
            $request->input('codGrupo'),
            'SISTEMA'
        ];
        FileControl::insertarCargo($data);
        return response()->json(['message' => 'Cargo creado']);
    }

    public function updateCargo(Request $request)
    {
        $data = [
            $request->input('codigo'),
            $request->input('tipoCargo'),
            $request->input('codArea'),
            $request->input('nombre'),
            $request->input('descripcion'),
            $request->input('abreviatura'),
            $request->input('codPosicion'),
            $request->input('codGrupo'),
            'SISTEMA'
        ];
        $inserted = FileControl::updateCargo($data);
        if ($inserted) {
            return response()->json(['success' => true, 'message' => 'Cargo modificado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'Error al guardar el folio'], 500);
        }
    }

    public function getCargosXCodigo($codigo){
        $data = FileControl::getCargosXCodigo($codigo);
        return response()->json($data);
    }

    public function eliminarCargo(Request $request){
        FileControl::activarCargo($request->input('codigo'), 0);
        return response()->json(['message' => 'Cargo modificado']);
    }

    public function activarCargo(Request $request){
        FileControl::activarCargo($request->input('codigo'), 1);
        return response()->json(['message' => 'Cargo modificado']);
    }

    public function ViewLegajo_comercial(){
        return view('file_control.legajos_comercial');
    }

    public function saveSolicitud(Request $request){
        $usuario = session('usuario');
        FileControl::saveSolicitud($request->input('codigo'), $request->input('tiene'), $request->input('cargo'), $request->input('cliente'), $usuario);
        return response()->json(['message' => 'Solicitud creado']);
    }

    public function getPostulantes()
    {
        try {
            $postulantes = DB::connection('sqlsrv_prueba1')
                ->table('dbo.postulantes as p')
                ->join('dbo.estado_postulantes as ep', 'p.estado', '=', 'ep.id')
                ->select('p.*', 'ep.nombre as estado_nombre')
                ->where('ep.nombre', 'APTO')
                ->get();

            $ids = $postulantes->pluck('id')->toArray();
            $personalData = DB::table('sw_MIGRA_PERSONAL')->whereIn('CODI_PERS', $ids)->get()->keyBy('CODI_PERS');

            $data = $postulantes->map(function ($p) use ($personalData) {
                if (isset($personalData[$p->id])) {
                    $saved = $personalData[$p->id];
                    $p->dni = $saved->NRO_DOCU_IDEN ?? $p->dni;
                    $p->nombres = $saved->NOMB_1;
                    $p->apellido1 = $saved->APEL_1;
                    $p->apellido2 = $saved->APEL_2;
                    $p->direccion = $saved->DIRECCION ?? $p->direccion;
                    $p->correo = $saved->PERS_EMAIL ?? $p->correo;
                    $p->fecha_nacimiento = $saved->FECH_NACI ?? $p->fecha_nacimiento;
                    $p->departamento = $saved->DEPARTAMENTO;
                    $p->provincia = $saved->PROVINCIA;
                    $p->distrito = $saved->DISTRITO;
                    $p->sucamec = $saved->PERS_CONDISCAMEC;
                    $p->licencia_arma = $saved->PERS_NROLICENCIA;
                    $p->grado_instruccion = $saved->PERS_GRADO_INSTRUCCION;
                    $p->source = 'sisolm_web';
                } else {
                    $p->source = 'reclusol';
                }
                return $p;
            });

            return response()->json($data);
        } catch (\Exception $e) {
            \Log::error("Error en getPostulantes: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ============================================================
    // BUSCAR SUCURSALES PARA EL TAG INPUT
    // ============================================================
    public function getSucursales(Request $request)
    {
        try {
            $query = strtoupper($request->get('q', ''));

            $sucursales = DB::table('sisolm_web.dbo.sw_MIGRA_SISO_SUCURSAL')
                ->select('SUCU_CODIGO', 'SUCU_DESCRIPCION', 'SUCU_ABREVIATURA')
                ->where('SUCU_VIGENCIA', 'SI')
                ->where(function($q) use ($query) {
                    $q->where('SUCU_DESCRIPCION', 'like', '%' . $query . '%')
                      ->orWhere('SUCU_ABREVIATURA', 'like', '%' . $query . '%');
                })
                ->orderBy('SUCU_DESCRIPCION')
                ->limit(10)
                ->get();

            return response()->json($sucursales);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}