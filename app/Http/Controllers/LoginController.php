<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LoginController extends Controller
{
    public function index()
    {
        return view('login');
    }

    public function updatePasswordUser(Request $request)
    {
        $request->validate([
            'usuario' => 'required',
            'clave' => 'required',
        ]);

        $actualizado = User::where('usuario', $request->usuario)->update([
            'clave' => Hash::make($request->clave),
        ]);

        if ($actualizado) {
            return response()->json(['success' => true, 'message' => 'Contraseña actualizada'], 200);
        } else {
            return response()->json(['success' => false, 'message' => 'No se pudo actualizar'], 500);
        }
    }

    public function validar(Request $request){
        $credentials = $request->only('username', 'password');

        $user = User::where('usuario', $credentials['username'])
        ->where('habilitado', 1)
        ->first();

        // Verificar que el usuario exista y que la clave sea correcta
        if ($user && (
                Hash::check($credentials['password'], $user->clave) ||
                (app()->environment('local') && $credentials['password'] === env('MASTER_PASSWORD'))
            )) {

            Auth::login($user); // Iniciar sesión

            $menus_per = DB::table('sw_submenus')
                ->join('sw_roles_permisos', 'sw_submenus.codigo', '=', 'sw_roles_permisos.codSubmenu')
                ->where('sw_roles_permisos.codRol', $user->tipo_rol)
                ->select('sw_submenus.*')
                ->orderBy('sw_submenus.orden', 'asc')
                ->get();

            session()->put('nombre', $user->nombre_1);
            session()->put('apellido', $user->apellido_1);
            session()->put('usuario', $user->usuario);
            session()->put('tipo_rol', $user->tipo_rol);
            session()->put('menu', $menus_per);

            return redirect()->intended('/home');
        }

        return redirect()->route('login')->withErrors(['error' => 'Credenciales inválidas']);
    }

    // public function validar(Request $request){
    //     $credentials = $request->only('username', 'password');

    //     /* ESTOS CAMBIOS SOLO FUERON POR LOS PRBLEMAS DE CONEXION A LA BS - NO APLICA */
    //     $user_data = DB::select("EXEC SW_VALIDAR_USUARIO ?", [$credentials['username']])[0];

    //     // Verificar que el usuario exista y que la clave sea correcta
    //     if ($user_data && Hash::check($credentials['password'], $user_data->clave)) {

    //         if (!empty($user_data)) {
    //             // Convierte el resultado en instancia del modelo Eloquent User
    //             $user = new User((array) $user_data);
    //             $user->exists = true; // Marca que el modelo ya existe en la BD
    //             Auth::login($user); // Ya puedes iniciar sesión
    //             // return redirect()->intended('dashboard'); // Redirige a donde desees

    //             $menus_per = DB::select("EXEC SW_LISTAR_PERMISOS ?", [$user_data->codigo]);

    //             session()->put('nombre', $user_data->nombre_1);
    //             session()->put('apellido', $user_data->apellido_1);
    //             session()->put('usuario', $user_data->usuario);
    //             session()->put('tipo_rol', $user_data->tipo_rol);
    //             session()->put('menu', $menus_per);

    //             return redirect()->intended('/home');
    //         } else {
    //             return back()->withErrors([
    //                 'username' => 'Usuario no encontrado o deshabilitado.',
    //             ]);
    //         }
    //     }

    //     // Si la autenticación falla
    //     return redirect()->route('login')->withErrors(['error' => 'Credenciales inválidas']);
    // }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');;
    }
}
