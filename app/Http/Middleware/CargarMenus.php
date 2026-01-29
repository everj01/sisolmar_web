<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CargarMenus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $usuario = Auth::user();

            // // Aquí puedes poner tu lógica de permisos
            // $menus_per = \DB::table('sw_submenu')
            // ->join('sw_roles_permisos', 'sw_submenu.codigo', '=', 'sw_roles_permisos.codSubmenu')
            // ->where('sw_roles_permisos.codRol', $usuario->tipo_rol)
            // ->select('sw_submenu.*')
            // ->get();
            // // Compartir con todas las vistas
            // View::share('menus_per', $menus_per);

            dd('Middleware se está ejecutando'); 
        }

        return $next($request);
    }
}
