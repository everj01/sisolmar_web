<?php
namespace App\Http\Middleware;

  use Closure;
  use Illuminate\Http\Request;
  use Symfony\Component\HttpFoundation\Response;
  use Illuminate\Support\Facades\View;
  use Illuminate\Support\Facades\Auth;
  use App\Models\Permisos;

  class CargarMenus
  {
      public function handle(Request $request, Closure $next): Response
      {
          if (Auth::check()) {
              $permisos = Permisos::getPermissionsByRole(Auth::user()->tipo_rol);
              View::share('permisos', $permisos);
              session(['permisos' => $permisos]); // mantiene RoutingController funcionando
          }

          return $next($request);
      }
  }