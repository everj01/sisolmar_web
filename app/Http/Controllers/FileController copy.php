<?php

namespace App\Http\Controllers;

use App\Helpers\PdfHelper;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Illuminate\Http\Request;
use App\Models\FileControl;
use Illuminate\Support\Facades\Http;

class FileController extends Controller
{
    public function index()
    {
        $personal = FileControl::getPersonal();
        $cargos = FileControl::getCargos();
        $clientes = FileControl::getClientes();
        $sucursales = FileControl::getSucursales();
        
        return view('file_control.chargefile', compact('personal', 'cargos', 'clientes', 'sucursales'));
    }

    public function getPersonal(Request $request)
    {
        $personal = FileControl::getPersonal();
        return response()->json($personal);
    }

    public function getDocumentosXPersonal($codPersonal)
    {
        $docs_personal = FileControl::getDocsXPersona($codPersonal);
        return response()->json($docs_personal);
    }

    public function getFoliosXPersonas(Request $request)
    {
        $personas = $request->personas;
        $folios = $request->folios;
        $resultados = [];

        foreach ($personas as $persona) {
            foreach ($folios as $folio) {
                $datosFolioPersona = FileControl::getFoliosInfoPersona($persona['CODI_PERS'], $folio['codigo']);
                foreach ($datosFolioPersona as $dato) {
                    $resultados[] = [
                        'persona' => $persona['personal'],
                        'folio' => $folio['nombre'],
                        'ruta' => $dato->ruta_archivo ?? null,
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

    public function generarPDF(Request $request)
    {
        /*$resultados = $request->input('resultados');
        $urls = [];

        $resultadosAgrupados = [];

        foreach ($resultados as $resultado) {
            if (!empty($resultado['ruta'])) {
                $urls[] = $resultado['ruta'];
        
                $persona = $resultado['persona'];
                if (!isset($resultadosAgrupados[$persona])) {
                    $resultadosAgrupados[$persona] = [];
                }
        
                $resultadosAgrupados[$persona][] = $resultado['ruta'];
            }
        }

        print_r($urls);
        print_r($resultadosAgrupados);
       
        
        $rutasLocales = PdfHelper::descargarImagenesLegajo($urls);
        print_r($rutasLocales);

        $resultadosFinal = []; // esto es lo que vas a pasar al blade
        $indice = 0;

        foreach ($resultadosAgrupados as $persona => $imagenesUrls) {
            $imagenesLocales = [];
        
            foreach ($imagenesUrls as $url) {
                $imagenesLocales[] = $rutasLocales[$indice];
                $indice++;
            }
        
            $resultadosFinal[] = [
                'persona' => $persona,
                'imagenes' => $imagenesLocales
            ];
        }
 
        $pdf = SnappyPdf::loadView('file_control.pdf.reporte', [
            //'resultadosAgrupados' => $resultadosAgrupados,
            'imagenes' => $rutasLocales,
            'resultados' => $resultados
            //'resultadosFinal' => $resultadosFinal
        ])->setOption('enable-local-file-access', true);

        return response($pdf->inline('reporte.pdf'))
            ->header('Content-Type', 'application/pdf');*/





        $resultados = $request->input('resultados');
        print_r($resultados);
        /*foreach ($resultados as $resultado) {
            echo 'Persona: ' . $resultado['persona'] . '<br>';
            echo 'Folio: ' . $resultado['folio'] . '<br>';
            echo 'Ruta: ' . $resultado['ruta'] . '<br><br>';
        }*/
        /*
        $urls = [
            'http://190.116.178.163/Biblioteca_Grafica/DICSCAMEC1/00530.jpg',
            'http://190.116.178.163/Biblioteca_Grafica/DICSCAMEC2/00530.jpg',
        ];*/

        $urls = [];

        foreach ($resultados as $resultado) {
            if (!empty($resultado['ruta'])) {
                $urls[] = $resultado['ruta'];
            }
        }

        print_r($urls);
        //print_r($resultados);

        $rutasLocales = PdfHelper::descargarImagenesLegajo($urls);

        print_r($rutasLocales);
        
        $pdf = SnappyPdf::loadView('file_control.pdf.reporte', [
            'resultados' => $resultados,
            'imagenes' => $rutasLocales
        ])->setOption('enable-local-file-access', true);

        return response($pdf->inline('reporte.pdf'))
            ->header('Content-Type', 'application/pdf');
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

            // Opcional: añadir salto de página entre vistas
            $html .= '<div style="page-break-after: always;"></div>';
        }

        $pdf = SnappyPdf::loadHTML($html)
            ->setOption('enable-local-file-access', true);

        return response($pdf->inline('reporte_completo.pdf'))
            ->header('Content-Type', 'application/pdf');
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

    public function getLegajos(Request $request)
    {
        $cliente = $request->input('cliente');
        $cargo = $request->input('cargo');
        $codPersonal = $request->input('codigo');
        
        $legajos = FileControl::getLegajos($cliente, $cargo, $codPersonal);
        return response()->json($legajos);
    }

    public function getFoliosClienteCargo(Request $request)
    {
        $cliente = $request->input('cliente');
        $cargo = $request->input('cargo');
        
        $legajos = FileControl::getFoliosClienteCargo($cliente, $cargo);
        return response()->json($legajos);
    }

    public function getFoliosXLegajo_comercial($codCliente, $codCargo){
        $folios = FileControl::getFoliosXLegajo_comercial($codCliente, $codCargo);
        return response()->json($folios);
    }

    


    public function getCargos(Request $request)
    {
        $cargos = FileControl::getCargos();
        return response()->json($cargos);
    }

    public function getFolios(Request $request)
    {
        $folios = FileControl::getFolios();
        return response()->json($folios);
    }

    public function ViewCargo()
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
        return view('file_control.cargo',compact('todos', 'operativo', 'administrativo'));
    }

    public function ViewLegajo()
    {
        return view('file_control.legajos');
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
        $clientes = FileControl::getClientes();

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
    public function saveFolioPersona(Request $request)
    {
        // Validar los datos del formulario
        $validated = $request->validate([
            'fecha_emision' => 'required|date',
            'fecha_caducidad' => 'nullable|date',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:1024',
        ]);

        /* Para guardar el arcivo con la codificación del personal */
        $codPersonal = $request->input('codPersonal');
        $nameFile = $codPersonal.'.jpg';

        /* Averiguar el nombre de la carpeta o ruta donde debe de guardarse el archivo */
        $rutaArchivo = FileControl::getRutaFolio($request->input('codFolio'));
        
        $filePath = null;
        // Llamar al microservicio para guardar el archivo en el servidor local
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filePath = 'http://190.116.178.163/Biblioteca_Grafica/'.$rutaArchivo.'/$nameFile';
            $response = Http::withToken('457862h45hj7u5126h58d2s51s2s') // Autenticación con el token
                ->attach('archivo', file_get_contents($file), $file->getClientOriginalName()) // Adjuntar el archivo
                ->post('http://190.116.178.163/apps/api/file-control/charge-file.php', [
                    'nameFile' => $nameFile,
                    'ruta' => $rutaArchivo
                ]);

            if ($response->failed()) {
                return response()->json(['error' => 'No se pudo guardar el archivo en el servidor local'], 500);
            }
        }

        // Llamar al método saveFolioPersonal pasando los datos y el archivo
        $inserted = FileControl::saveFolioPersonal(
            $validated['fecha_emision'],
            $validated['fecha_caducidad'],
            $request->codFolio,
            $request->codPersonal,
            $filePath // Pasamos la ruta del archivo
        );

        return response()->json(['message' => 'Folios del persona guardados']);
    }

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
    public function disabledFolio(Request $request)
    {
        $codigo = $request->codigo;
        $result = FileControl::disabledFolio($codigo);

        if ($result) {
            return response()->json(['message' => 'Folio deshabilitado exitosamente'], 200);
        } else {
            return response()->json(['message' => 'Folio no encontrado o error al deshabilitar'], 404);
        }
    }
    public function saveCargo(Request $request)
    {
        $nombre = $request->input('nombre');

        $inserted = FileControl::saveCargo($nombre);

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

        if($codLegajo != '0'){
            for($i = 0; $i < count($folios); $i++){
                $folio = $folios[$i];
                $validar = FileControl::validarLegajo($folio, $codCliente, $codCargo, $codLegajo);
                if(empty($validar)){
                    $inserted = FileControl::saveLegajo($folio, $codCliente, $codCargo, $codLegajo);
                }
                
            }
        }else{
            $last = FileControl::saveLegajoMain($nombre)[0]->last_id;
            for($i = 0; $i < count($folios); $i++){
                $folio = $folios[$i];
                $validar = FileControl::validarLegajo($folio, $codCliente, $codCargo, $last);
                if(empty($validar)){
                    $inserted = FileControl::saveLegajo($folio, $codCliente, $codCargo, $last);
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

    
    public function ViewLegajo_comercial()
    {
        return view('file_control.legajos_comercial');
    }

    public function saveSolicitud(Request $request){
        $codigo = $request->input('codigo');
        $tiene = $request->input('tiene');
        $cargo = $request->input('cargo');
        $cliente = $request->input('cliente');
        $usuario = 'ADMIN';

        $inserted = FileControl::saveSolicitud($codigo, $tiene, $cargo, $cliente, $usuario);

        return response()->json(['message' => 'Solicitud creado']);
    }

}
