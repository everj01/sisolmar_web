<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Permisos; // o como se llame tu modelo

class RefreshPermissions
{
    public function handle($request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $permisos = Permisos::getPermissionsByRole($user->tipo_rol);
            session(['permisos' => $permisos]);
        }

        return $next($request);
    }
}
