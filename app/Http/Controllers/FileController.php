<?php

namespace App\Http\Controllers;

use App\Helpers\PdfHelper;
use App\Helpers\ImagenHelper;
use Barryvdh\Snappy\Facades\SnappyPdf;
use DB;
use Illuminate\Http\Request;
use App\Models\FileControl;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FileController extends Controller{
    public function index(){
        $personal = FileControl::getPersonal();
        //$cargos = FileControl::getCargos();
        $clientes = FileControl::getClientesLegajos();
        $sucursales = FileControl::getSucursales();

        return view('file_control.chargefile', compact('personal', 'clientes', 'sucursales'));
    }

    public function indexGestionDj()
    {
        //$personal = FileControl::getPersonal();

        return view('file_control.gestion_dj');
    }

    public function getCargosXCliente(Request $request){
        $cliente = $request->input('cliente');
        $cargos = FileControl::getCargosXCliente($cliente);
        return response()->json($cargos);
    }

    public function ViewDashboard(){
        /*$personal = FileControl::getPersonal();
        $cargos = FileControl::getCargos();
        $clientes = FileControl::getClientes();
        $sucursales = FileControl::getSucursales();*/

        return view('file_control.dashboard'/*, compact('personal', 'cargos', 'clientes', 'sucursales')*/);
    }

    public function getPersonal(Request $request){
        $personal = FileControl::getPersonal();
        return response()->json($personal);
    }

    public function getPersonalTotal(Request $request)
    {
        return FileControl::getPersonalTotal($request);
    }

    public function getDocumentosXPersonal($codPersonal){
        $docs_personal = FileControl::getDocsXPersona($codPersonal);
        return response()->json($docs_personal);
    }

    public function getFoliosXPersonas(Request $request){
        $personas = $request->personas;
        $folios = $request->folios;
        $resultados = [];
        //Averiguando la sucursal de la persona

 
        foreach ($personas as $persona) {
            $sucursal = FileControl::getSucursalXPersona($persona['CODI_PERS']);
            foreach ($folios as $folio) {
                $datosFolioPersona = FileControl::getFoliosInfoPersona($persona['CODI_PERS'], $folio['codigo']);
                foreach ($datosFolioPersona as $dato) {
                    $resultados[] = [
                        //'persona' => $persona['personal'],
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
                /*$resultados[] = [
                    'persona' => $persona['personal'],
                    'folio' => $folio['nombre'],
                    'ruta' => $datosFolioPersona[0]->ruta_archivo ?? null,
                ];*/
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

    // public function generarPDFsPorPersona(Request $request){
    //     $resultados = $request->input('resultados');

    //     // Agrupar todos los datos por persona
    //     $agrupados = [];
    //     foreach ($resultados as $item) {
    //         $cod = $item['codPersonal'];
    //         $agrupados[$cod][] = $item;
    //     }

    //     $carpetaTemporal = storage_path('app/temp_pdfs');
    //     if (!File::exists($carpetaTemporal)) {
    //         File::makeDirectory($carpetaTemporal, 0777, true, true);
    //     }

    //     $pdfsGenerados = [];

    //     foreach ($agrupados as $codPersonal => $documentosPersona) {
    //         $persona = $documentosPersona[0]['persona'] ?? 'persona_sin_nombre';

    //         // Carátula: datos únicos de persona
    //         $caratula = [
    //             'persona' => $persona,
    //             'codPersonal' => $codPersonal,
    //             'sucursal' => $documentosPersona[0]['sucursal'] ?? '',
    //             'cargo' => $documentosPersona[0]['cargo'] ?? '',
    //         ];

    //         // Separar documentos: escaneados vs formatos Blade
    //         $urls = [];
    //         $itemsFinales = [];

    //         foreach ($documentosPersona as $doc) {
    //             if (!empty($doc['ruta'])) {
    //                 $urls[] = [
    //                     'ruta' => $doc['ruta'],
    //                     'codPersonal' => $codPersonal,
    //                     'ancho' => $doc['ancho'],
    //                     'hojas' => $doc['hojas'],
    //                     'documento' => $doc['documento'],
    //                     'es_formato' => $doc['es_formato'],
    //                 ];
    //             }

    //             if ($doc['es_formato'] == 1) {
    //                 $itemsFinales[] = [
    //                     'es_formato' => 1,
    //                     'codPersonal' => $codPersonal,
    //                     'documento' => $doc['documento'],
    //                     'nombre_vista' => $this->obtenerNombreVista($doc),
    //                     'datos' => $doc,
    //                     'firma' => public_path('temp_legajos') . '/FIRMAS/PERSONAL/' . $codPersonal . '.jpg',
    //                     'huella' => public_path('temp_legajos') . '/HUELLAS_DIGITALES/PERSONAL/' . $codPersonal . '.jpg',
    //                 ];

    //                 // Descargar imágenes del formato (firma y huella)
    //                 ImagenHelper::descargarImagenesFormato($codPersonal);
    //             }
    //         }

    //         // Descargar imágenes escaneadas
    //         $rutasLocales = PdfHelper::descargarImagenesLegajo($urls);

    //         foreach ($rutasLocales as $img) {
    //             $itemsFinales[] = [
    //                 'es_formato' => 0,
    //                 'codPersonal' => $codPersonal,
    //                 'ruta' => $img['ruta'],
    //                 'documento' => $img['documento'],
    //                 'hojas' => $img['hojas'],
    //                 'ancho' => $img['ancho'],
    //             ];
    //         }

    //         // Render PDF de esta persona
    //         $pdf = SnappyPdf::loadView('file_control.pdf.reporte', [
    //             'personas' => [$caratula], // vista espera array
    //             'resultados' => $documentosPersona,
    //             'imagenes' => $rutasLocales,
    //             'items' => $itemsFinales,
    //         ])->setOption('enable-local-file-access', true);

    //         $nombreArchivo = 'PDF_' . Str::slug($persona) . '_' . $codPersonal . '.pdf';
    //         $rutaArchivo = $carpetaTemporal . '/' . $nombreArchivo;

    //         $pdf->save($rutaArchivo);
    //         $pdfsGenerados[] = $rutaArchivo;
    //     }

    //     // Comprimir todo en ZIP
    //     $zipPath = storage_path('app/documentos_por_persona.zip');
    //     $zip = new \ZipArchive();

    //     if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
    //         foreach ($pdfsGenerados as $archivo) {
    //             $zip->addFile($archivo, basename($archivo));
    //         }
    //         $zip->close();
    //     }

    //     return response()->download($zipPath)->deleteFileAfterSend(true);
    // }

    public function generarPDF_env(Request $request){
        echo "Hola";
    }

    public function pdf_vacio()
    {
        // Aquí cargas una vista muy simple (puedes crear un blade vacío o con texto simple)
        $pdf = SnappyPdf::loadView('file_control.pdf.reporte_vacio');

        // Lo envías para que se descargue (o inline si quieres verlo en navegador)
        return $pdf->inline('reporte_vacio.pdf');
    }


    public function generarPDF(Request $request){
        $resultados = $request->input('resultados');
        //dd($resultados);
        //exit;
        //Agrupar los datos para mostrar en la carátula
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

        //Para los FORMATOS
        // $formatosBlade = [
        //     'ACTA DE COMPROMISO' => 'file_control.pdf.acta_compromiso',
        //     'DECLARACION JURADA ' => 'file_control.pdf.declaracion_jurada',
        // ];

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
        };

        $rutasLocales = PdfHelper::descargarImagenesLegajo($urls);

        $itemsFinales = [];
        //Los que tienen imagen en ruta
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

        // Los que deben renderizar una vista Blade porque son formatos (es_formato == 1)
        foreach ($resultados as $resultado) {
            if ($resultado['es_formato'] == 1) {
                $itemsFinales[] = [
                    'es_formato' => $resultado['es_formato'],
                    'codPersonal' => $resultado['codPersonal'],
                    'documento' => $resultado['documento'],
                    'nombre_vista' => $this->obtenerNombreVista($resultado), // Función que defines
                    'datos' => $resultado,
                    'firma' => public_path('temp_legajos').'/FIRMAS/PERSONAL/'.$resultado['codPersonal'].'.jpg',
                    'huella' => public_path('temp_legajos').'/HUELLAS_DIGITALES/PERSONAL/'.$resultado['codPersonal'].'.jpg',
                ];
                //Hacer la copia local de la FIRMA y HUELLA DIGITAL
                $rutasLocalesFormato = ImagenHelper::descargarImagenesFormato($resultado['codPersonal']);
            }
        }

        //print_r($itemsFinales);
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
            'X-Nombre-Archivo' => $nombreNuevo . '.pdf', // <- aquí
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
        $codigo =  $request->input('valor');
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

            $html .= view($vista, [
                'resultados' => $resultados
            ])->render();

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

    public function getFolios(){
        $folios = FileControl::getFolios();
        return response()->json($folios);
    }

    public function ViewCargo()
    {
        return view('file_control.cargo');
    }

    public function getCargoCounters()
    {
        $todos = \DB::table('sw_cargos')
                    ->where('habilitado', 1)
                    ->count();

        $operativo = \DB::table('sw_cargos')
                    ->where('cod_tipo', 1)
                    ->where('habilitado', 1)
                    ->count();

        $administrativo = \DB::table('sw_cargos')
                    ->where('cod_tipo', 2)
                    ->where('habilitado', 1)
                    ->count();

        return response()->json([
            'todos' => $todos,
            'operativo' => $operativo,
            'administrativo' => $administrativo
        ]);
    }

    public function ViewLegajo()
    {
         $notif = FileControl::listarNotificaciones();
        return view('file_control.legajos', [
            'notify' => $notif
        ]);
    }

    public function ViewFolios()
    {
        $periodos = FileControl::getPeriodos();
        $todos = \DB::table('sw_folios')
                    ->where('habilitado', 1)
                    ->count();
        $principal = \DB::table('sw_folios')
                    ->where('obligatorio', 1)
                    ->where('habilitado', 1)
                    ->count();
        $adicional = \DB::table('sw_folios')
                    ->where('obligatorio', 0)
                    ->where('habilitado', 1)
                    ->count();
        $documento = \DB::table('sw_folios')
                    ->where('tipo', 1)
                    ->where('habilitado', 1)
                    ->count();
        $formato = \DB::table('sw_folios')
                    ->where('tipo', 2)
                    ->where('habilitado', 1)
                    ->count();
        $certificado = \DB::table('sw_folios')
                    ->where('tipo', 3)
                    ->where('habilitado', 1)
                    ->count();

        return view('file_control.folios', compact('periodos', 'todos', 'principal', 'adicional', 'documento', 'formato', 'certificado'));
    }

    public function ViewBusquedaLegajo()
    {
        $personal = FileControl::getPersonalLegajos();
        $cargos = FileControl::getCargos();
        //$clientes = FileControl::getClientes();
        $clientes = FileControl::getClientesLegajos();

        return view('file_control.search_legajos', compact('personal', 'cargos', 'clientes'));
    }

    public function ViewLegajoPdf()
    {
        $sucursales = FileControl::getSucursales();
        $cargos = FileControl::getCargos();
        $clientes = FileControl::getClientes();
        return view('file_control.legajos_pdf', compact('sucursales','cargos','clientes'));
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

    //GUARDAR DATOS
    public function saveFolioPersona(Request $request){
        //dd($request->file('imagenes'));


        // Validar los datos del formulario
        $validated = $request->validate([
            'fecha_emision' => 'required|date',
            'fecha_caducidad' => 'nullable|date',
            //'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024',
        ]);

        // Para guardar el archivo con la codificación del personal

        $codPersonal = $request->input('codPersonal');
        $rutas = FileControl::getRutaFolio($request->input('codFolio')); // Siempre array
        $archivos = $request->file('imagenes');

        $totalArchivos = count($archivos);
        $totalRutas = count($rutas);

        // Validación
        if ($totalRutas !== 1 && $totalRutas !== $totalArchivos) {
            return response()->json(['error' => 'La cantidad de rutas no coincide con la cantidad de archivos.'], 400);
        }

        // Recorrer archivos
        foreach ($archivos as $index => $archivo) {
            // Determinar nombre según el caso
            if ($totalRutas === $totalArchivos) {
                // Caso: un archivo por ruta → nombre sin sufijo
                $nameFile = $codPersonal . '.jpg';
                $rutaArchivo = $rutas[$index];
            } else {
                // Caso: múltiples archivos para una sola ruta → nombre con sufijo
                $nameFile = $codPersonal . '_' . ($index + 1) . '.jpg';
                $rutaArchivo = $rutas[0];
            }

            // Enviar al microservicio
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

            // Opcional: guardar localmente
            $archivo->storeAs('uploads/folios', $nameFile);
        }
        
        // Llamar al método saveFolioPersonal pasando los datos y el archivo
        $inserted = FileControl::saveFolioPersonal(
            $validated['fecha_emision'],
            $validated['fecha_caducidad'],
            $request->codFolio,
            $request->codPersonal,
            //$filePath // Pasamos la ruta del archivo
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

            // Probar primero con ruta_aux
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

            // Si no se encontró nada en ruta_aux, probar con ruta
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
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron archivos accesibles desde la red'
            ]);
        }

        return response()->json([
            'success' => true,
            'rutas' => $rutasValidas
        ]);
    }

    // Función para validar si la URL existe
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



    // public function saveFolioPersona(Request $request)
    // {
    //     $validated = $request->validate([
    //         'fecha_emision'     => 'required|date',
    //         'fecha_caducidad'   => 'nullable|date',
    //         'imagenes.*'        => 'required|file|mimes:jpg,jpeg,png|max:2048',
    //         'codPersonal'       => 'required|string',
    //         'codFolio'          => 'required|string',
    //     ]);

    //     try {
    //         $codPersonal = $validated['codPersonal'];
    //         $codFolio    = $validated['codFolio'];
    //         $archivos    = $request->file('imagenes');
    //         $total       = count($archivos);

    //         // Obtener ruta lógica relativa
    //         $subCarpeta = FileControl::getRutaFolio($codFolio); // Ejemplo: "FOLIOS/2025/F123"

    //         foreach ($archivos as $index => $archivo) {
    //             $nombreArchivo = ($total === 1)
    //                 ? "{$codPersonal}.jpg"
    //                 : "{$codPersonal}_" . ($index + 1) . ".jpg";

    //             $rutaRelativa = $subCarpeta . '/' . $nombreArchivo;

    //             if (App::environment('local')) {
    //                 // Guardado directo en ruta de red en entorno local
    //                 $rutaFinal = "\\\\192.168.10.5:86\\sisolmar\\DATA_SISOLMAR\\" . $subCarpeta . "\\" . $nombreArchivo;

    //                 if (!file_exists(dirname($rutaFinal))) {
    //                     mkdir(dirname($rutaFinal), 0777, true);
    //                 }

    //                 file_put_contents($rutaFinal, file_get_contents($archivo->getRealPath()));
    //             }

    //             if (App::environment('production')) {
    //                 // Guardado en D:\ mediante Storage
    //                 Storage::disk('disk_d')->put($rutaRelativa, file_get_contents($archivo->getRealPath()));
    //             }
    //         }

    //         // Lógica adicional de BD
    //         FileControl::saveFolioPersonal(
    //             $validated['fecha_emision'],
    //             $validated['fecha_caducidad'],
    //             $codFolio,
    //             $codPersonal
    //         );

    //         return response()->json(['message' => 'Folios del personal guardados correctamente.']);

    //     } catch (\Exception $e) {
    //         Log::error('Error al guardar folios: ' . $e->getMessage());
    //         return response()->json(['error' => 'Error interno al guardar archivos.'], 500);
    //     }
    // }

    public function saveFolioPersona3(Request $request)
{
    \Log::info('TEMP DIR: ' . sys_get_temp_dir());
    \Log::info('_FILES', $_FILES);
    \Log::info('REQUEST', $request->all());

    return response()->json(['message' => 'Revisado en log.'], 200);
}
    public function saveFolioPersona2(Request $request)
    {
        /*$fechaEmision = $request->input('fecha_emision');
        $fechaCaducidad = $request->input('fecha_caducidad');
        $codFolio = $request->input('codFolio');
        $codPersonal = $request->input('codPersonal');

        $inserted = FileControl::saveFolioPersonal($fechaEmision,$fechaCaducidad,$codFolio,$codPersonal);

        if ($inserted) {
            return response()->json(['success' => true, 'message' => 'El folio del personal, guardado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'Error al guardar el folio'], 500);
        }*/
        
        $cant = 0;
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $archivo) {
                //$nombre = time() . '_' . $archivo->getClientOriginalName();
                //$archivo->move(public_path('archivos_folio'), $nombre);
                $cant = $cant + 1;
            }
        }
    
        return response()->json("hay ".$cant." archivos");
        
    }

    //GUARDAR EN PUBLIC
    public function saveFolioPersona0(Request $request)
    {
        $request->validate([
            'fecha_emision' => 'required|date',
            'fecha_caducidad' => 'nullable|date',
            'codFolio' => 'required|integer',
            'codPersonal' => 'required|string',
            'imagenes' => 'required|array',
            'imagenes.*' => 'image|max:5120' // máximo 5MB por imagen (ajusta según tu necesidad)
        ]);

        $fechaEmision = $request->input('fecha_emision');
        $fechaCaducidad = $request->input('fecha_caducidad');
        $codFolio = $request->input('codFolio');
        $codPersonal = $request->input('codPersonal');
        $imagenes = $request->file('imagenes');

        // Obtiene las rutas de carpeta según el folio
        $subCarpetas = FileControl::getRutaFolio($codFolio); // devuelve un array de rutas

       
        if (count($subCarpetas) !== count($imagenes)) {
            return response()->json([
                'error' => 'El número de imágenes no coincide con el número de rutas esperadas para el folio.'
            ], 400);
        }

        foreach ($imagenes as $index => $imagen) {
            $rutaSubcarpeta = $subCarpetas[$index] ?? null;

            if (!$rutaSubcarpeta) {
                continue; // O lanza un error si lo prefieres
            }

            $rutaDestino = public_path('Biblioteca_Grafica/' . $rutaSubcarpeta);

            // Crea la carpeta si no existe
            if (!file_exists($rutaDestino)) {
                mkdir($rutaDestino, 0777, true);
            }

            // Guarda la imagen con el nombre del codPersonal
            $nombreArchivo = $codPersonal . '.' . $imagen->getClientOriginalExtension();

            $imagen->move($rutaDestino, $nombreArchivo);
        }

        // Aquí podrías guardar los datos en la BD si lo necesitas
        // ...

        return response()->json([
            'message' => 'Folio guardado exitosamente.',
        ]);
        
    }


    //DE EVER
    /*public function saveFolioPersona(Request $request)
    {
        $validated = $request->validate([
            'fecha_emision'     => 'required|date',
            'fecha_caducidad'   => 'nullable|date',
            'imagenes.*'        => 'required|file|mimes:jpg,jpeg,png|max:2048',
            'codPersonal'       => 'required|string',
            'codFolio'          => 'required|string',
        ]);

        try {
            $codPersonal = $validated['codPersonal'];
            $codFolio    = $validated['codFolio'];
            $archivos    = $request->file('imagenes');
            $total       = count($archivos);

            // Subcarpeta relativa dentro de public/
            $subCarpeta = FileControl::getRutaFolio($codFolio); // ejemplo: "folios/F123"
            $rutaBase = public_path($subCarpeta);

            if (!File::exists($rutaBase)) {
                File::makeDirectory($rutaBase, 0777, true);
            }

            foreach ($archivos as $index => $archivo) {
                $nombreArchivo = ($total === 1)
                    ? "{$codPersonal}.jpg"
                    : "{$codPersonal}_" . ($index + 1) . ".jpg";

                $rutaFinal = $rutaBase . '/' . $nombreArchivo;

                // Guardar archivo en public/
                file_put_contents($rutaFinal, file_get_contents($archivo->getRealPath()));
            }

            // Guardar en BD
            FileControl::saveFolioPersonal(
                $validated['fecha_emision'],
                $validated['fecha_caducidad'],
                $codFolio,
                $codPersonal
            );

            return response()->json(['message' => 'Folios guardados correctamente.']);

        } catch (\Exception $e) {
            Log::error('Error al guardar folios en public/: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno al guardar archivos.'], 500);
        }
    }
        */

    public function saveFolio(Request $request)
    {
        $codigo = $request->input('codigo');
        $nombre = $request->input('nombre');
        $tipo = $request->input('tipo');
        $obligatorio = $request->input('obligatorio');
        $vencimiento = $request->input('vencimiento');
        $tipo_fecha = $request->input('periodo');
        $plataforma = $request->input('plataforma');

        if (empty($codigo)) {
            $inserted = FileControl::saveFolio($nombre, $tipo, $obligatorio, $vencimiento, $tipo_fecha, $plataforma);
        } else {
            $inserted = FileControl::updateFolio($codigo, $nombre, $tipo, $obligatorio, $vencimiento, $tipo_fecha, $plataforma);
        }

        if ($inserted) {
            return response()->json(['success' => true, 'message' => 'Folio guardado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'Error al guardar el folio'], 500);
        }

        //return response()->json(['message' => 'Folios creados']);
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

    //-----------------

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
        //$codigo = $request->input('codigo');
        $data = FileControl::getGrupoId($codigo);
        return response()->json($data);
    }

    public function saveLegajo(Request $request){
        $folios = $request->input('folios');
        $codCliente = $request->input('codCliente');
        $codCargo = $request->input('codCargo');
        $codLegajo = $request->input('codLegajo');
        $nombre  = $request->input('nombre');

        $usuario = session('usuario');

        if($codLegajo != '0'){//MODIFICAR LEGAJO
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
        }else{//CREAR NUEVO LEGAJO
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

    /* GESTION DE CARGOS */
    public function insertarCargo(Request $request)
    {
        $tipo = $request->input('tipoCargo');
        $codArea = $request->input('codArea');
        $nombre = $request->input('nombre');
        $descripcion = $request->input('descripcion');
        $abreviatura = $request->input('abreviatura');
        $codPosicion = $request->input('codPosicion');
        $codGrupo = $request->input('codGrupo');

        $data = [
            $tipo,
            $codArea,
            $nombre,
            $descripcion,
            $abreviatura,
            $codPosicion,
            $codGrupo,
            'SISTEMA'
        ];

        $inserted = FileControl::insertarCargo($data);

        return response()->json(['message' => 'Cargo creado']);
    }
    public function updateCargo(Request $request)
    {
        $tipo = $request->input('tipoCargo');
        $codArea = $request->input('codArea');
        $nombre = $request->input('nombre');
        $descripcion = $request->input('descripcion');
        $abreviatura = $request->input('abreviatura');
        $codPosicion = $request->input('codPosicion');
        $codGrupo = $request->input('codGrupo');
        $codigo = $request->input('codigo');

        $data = [
            $codigo,
            $tipo,
            $codArea,
            $nombre,
            $descripcion,
            $abreviatura,
            $codPosicion,
            $codGrupo,
            'SISTEMA'
        ];

        $inserted = FileControl::updateCargo($data);


        if ($inserted) {
            return response()->json(['success' => true, 'message' => 'Cargo modificado correctamente']);
        } else {
            return response()->json(['success' => false, 'message' => 'Error al guardar el folio'], 500);
        }

        //return response()->json(['message' => 'Folios creados']);
    }


    public function getCargosXCodigo($codigo){
        $data = FileControl::getCargosXCodigo($codigo);
        return response()->json($data);
    }


    public function eliminarCargo(Request $request){
        $codigo = $request->input('codigo');
        $data = 0;
        $inserted = FileControl::activarCargo($codigo, $data);
        return response()->json(['message' => 'Cargo modificdo']);
    }

    public function activarCargo(Request $request){
        $codigo = $request->input('codigo');
        $data = 1;
        $inserted = FileControl::activarCargo($codigo, $data);
        return response()->json(['message' => 'Cargo modificdo']);
    }

    public function ViewLegajo_comercial(){

        return view('file_control.legajos_comercial');
    }

    public function saveSolicitud(Request $request){
        $codigo = $request->input('codigo');
        $tiene = $request->input('tiene');
        $cargo = $request->input('cargo');
        $cliente = $request->input('cliente');

        $usuario = session('usuario');

        FileControl::saveSolicitud($codigo, $tiene, $cargo, $cliente, $usuario);
        return response()->json(['message' => 'Solicitud creado']);
    }

    public function getPostulantes()
    {
        try {
            $data = DB::connection('sqlsrv_prueba1')
                ->table('dbo.postulantes as p')
                ->join('dbo.estado_postulantes as ep', 'p.estado', '=', 'ep.id')
                ->select('p.*', 'ep.nombre as estado_nombre')
                ->where('ep.nombre', 'APTO')
                ->get();

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

    }

}
