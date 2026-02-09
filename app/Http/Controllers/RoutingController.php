<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\FileController;

use Illuminate\Http\Request;

class RoutingController extends Controller
{
    public function __construct()
    {
        // $this->
        // middleware('auth')->
        // except('index');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // if (Auth::user()) {
            return redirect('index');
        // } else {
        //     return redirect('login');
        // }
    }

    /**
     * Display a view based on first route param
     *
     * @return \Illuminate\Http\Response
     */
    public function root(Request $request, $first)
    {
        if ($first == "assets")
            return redirect('home');

        return view($first);
    }

    /**
     * second level route
     */
    public function secondLevel(Request $request, $first, $second)
    {
        // Controladores específicos para cada vista
        $controllers = [
            'file_control' => [
                'chargefile' => [FileController::class, 'index'],
                'cargos' => [FileController::class, 'ViewCargo'],
                'legajos' => [FileController::class, 'ViewLegajo'],
                'legajos_comercial' => [FileController::class, 'ViewLegajo_comercial'],
                'folios' => [FileController::class, 'ViewFolios'],
                'search_legajos' => [FileController::class, 'ViewBusquedaLegajo'],
                'legajos_pdf' => [FileController::class, 'ViewLegajoPdf'],
                'dashboard' => [FileController::class, 'ViewDashboard'],
                'gestion_dj' => [FileController::class, 'indexGestionDj'],
                'reportes' => [ReporteController::class, 'index'],
            ],
        ];

        if(isset($controllers[$first][$second])){
            $controllerAction = $controllers[$first][$second];

            // Instanciando y llamando al método
            $controller = app($controllerAction[0]);
            return $controller->{$controllerAction[1]}($request);
        }

        return view($first . '.' . $second);
       
    }

    /**
     * third level route
     */
    public function thirdLevel(Request $request, $first, $second, $third)
    {

        if ($first == "assets")
            return redirect('home');

        return view($first . '.' . $second . '.' . $third);
    }
}
