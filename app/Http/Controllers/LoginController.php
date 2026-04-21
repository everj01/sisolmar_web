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

    public function getUsuarioSession()
    {
        $usuarioSession = session()->all();
        if (!$usuarioSession) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        }
        return response()->json($usuarioSession, 200);
    }


    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');;
    }
}
