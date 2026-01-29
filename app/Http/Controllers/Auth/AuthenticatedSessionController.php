<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Permisos;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('login');
    }

    public function store(Request $request)
    {
        $credentials = $request->only('username', 'password');

        $user = User::where('usuario', $credentials['username'])
            ->where('habilitado', 1)
            ->first();

        if ($user && Hash::check($credentials['password'], $user->clave)) {
            Auth::login($user); // Iniciar sesión

            $menus_per = DB::table('sw_submenus')
            ->join('sw_roles_permisos', 'sw_submenus.codigo', '=', 'sw_roles_permisos.codSubmenu')
            ->where('sw_roles_permisos.codRol', $user->tipo_rol)
            ->orderBy('sw_submenus.orden', 'asc')  
            ->select('sw_submenus.*')
            ->get();

            session()->put('nombre', $user->nombre_1);
            session()->put('apellido', $user->apellido_1);
            session()->put('usuario', $user->usuario);
            session()->put('tipo_rol', $user->tipo_rol);
            session()->put('menu', $menus_per);
            $rolId = $user->tipo_rol; // o como tengas el rol en tu tabla users
            $permisos = Permisos::getPermissionsByRole($rolId);
            session(['permisos' => $permisos]); // Guardar en sesión

            return redirect()->intended('/home');
        }

        return redirect()->route('login')->withErrors(['error' => 'Credenciales inválidas']);
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
