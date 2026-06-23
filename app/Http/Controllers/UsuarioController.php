<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    public function index()
    {
        $roles = DB::table('sw_roles')
            ->where('habilitado', 1)
            ->where('test', 0)
            ->select('codigo', 'nombre')
            ->orderBy('nombre')
            ->get();

        return view('maestros.usuarios', compact('roles'));
    }

    public function getUsuarios()
    {
        $usuarios = DB::table('sw_usuarios as u')
            ->leftJoin('sw_roles as r', 'u.tipo_rol', '=', 'r.codigo')
            ->select(
                'u.codigo', 'u.usuario',
                'u.nombre_1', 'u.nombre_2',
                'u.apellido_1', 'u.apellido_2',
                'u.tipo_rol', 'r.nombre as nombre_rol',
                'u.habilitado', 'u.limitarSucursal', 'u.limitarTipoPer'
            )
            ->orderBy('u.apellido_1')
            ->orderBy('u.nombre_1')
            ->get();

        return response()->json($usuarios);
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string|max:50',
            'clave' => 'required|string|min:6',
            'nombre_1' => 'required|string|max:50',
            'apellido_1' => 'required|string|max:50',
            'tipo_rol' => 'required|integer',
            'limitarSucursal' => 'required',
            'limitarTipoPer' => 'required|integer|in:0,1,2,3',
        ]);

        $existe = DB::table('sw_usuarios')
            ->where('usuario', $request->usuario)
            ->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'message' => 'El nombre de usuario ya existe.',
            ], 422);
        }

        DB::table('sw_usuarios')->insert([
            'usuario' => $request->usuario,
            'clave' => Hash::make($request->clave),
            'nombre_1' => strtoupper($request->nombre_1),
            'nombre_2' => strtoupper($request->nombre_2 ?? ''),
            'apellido_1' => strtoupper($request->apellido_1),
            'apellido_2' => strtoupper($request->apellido_2 ?? ''),
            'tipo_rol' => $request->tipo_rol,
            'habilitado' => 1,
            'limitarSucursal' => $request->limitarSucursal ? 1 : 0,
            'limitarTipoPer' => $request->limitarTipoPer,
        ]);

        return response()->json(['success' => true, 'message' => 'Usuario creado correctamente.']);
    }

    public function update(Request $request)
    {
        $request->validate([
            'codigo' => 'required|integer',
            'nombre_1' => 'required|string|max:50',
            'apellido_1' => 'required|string|max:50',
            'tipo_rol' => 'required|integer',
            'limitarSucursal' => 'required',
            'limitarTipoPer' => 'required|integer|in:0,1,2,3',
        ]);

        $data = [
            'nombre_1' => strtoupper($request->nombre_1),
            'nombre_2' => strtoupper($request->nombre_2 ?? ''),
            'apellido_1' => strtoupper($request->apellido_1),
            'apellido_2' => strtoupper($request->apellido_2 ?? ''),
            'tipo_rol' => $request->tipo_rol,
            'limitarSucursal' => $request->limitarSucursal ? 1 : 0,
            'limitarTipoPer' => $request->limitarTipoPer,
        ];

        if (! empty($request->clave)) {
            $data['clave'] = Hash::make($request->clave);
        }

        DB::table('sw_usuarios')
            ->where('codigo', $request->codigo)
            ->update($data);

        return response()->json(['success' => true, 'message' => 'Usuario actualizado correctamente.']);
    }

    public function toggleHabilitado(Request $request)
    {
        $request->validate(['codigo' => 'required|integer']);

        $usuario = DB::table('sw_usuarios')
            ->where('codigo', $request->codigo)
            ->select('habilitado')
            ->first();

        if (! $usuario) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
        }

        DB::table('sw_usuarios')
            ->where('codigo', $request->codigo)
            ->update(['habilitado' => $usuario->habilitado ? 0 : 1]);

        $estado = $usuario->habilitado ? 'deshabilitado' : 'habilitado';

        return response()->json(['success' => true, 'message' => "Usuario {$estado} correctamente."]);
    }

    public function getSucursalesUsuario($codUsuario)
    {
        // Todas las sucursales disponibles (sin filtrar por usuario)
        $todas = collect(DB::select("EXEC SW_LISTAR_SUCURSALES '0'"))
            ->filter(fn ($s) => trim($s->codigo) !== '00')
            ->values();

        // Las que ya tiene asignadas con habilitado=1
        $asignadas = DB::table('sw_permisos_usuario_sucursal')
            ->where('codUsuario', $codUsuario)
            ->where('habilitado', 1)
            ->pluck('codSucursal')
            ->map(fn ($s) => trim($s))
            ->toArray();

        $resultado = $todas->map(fn ($s) => [
            'codigo' => trim($s->codigo),
            'abreviatura' => $s->abreviatura,
            'asignada' => in_array(trim($s->codigo), $asignadas),
        ]);

        return response()->json($resultado->values());
    }

    public function saveSucursalesUsuario(Request $request)
    {
        $request->validate([
            'codUsuario' => 'required|integer',
            'codigos' => 'array',
        ]);

        $codUsuario = $request->codUsuario;
        $codigos = $request->codigos ?? [];
        $usuario = Auth::user()->usuario;
        $ahora = now();

        // Deshabilitar todos los actuales
        DB::table('sw_permisos_usuario_sucursal')
            ->where('codUsuario', $codUsuario)
            ->update([
                'habilitado' => 0,
                'modificadoPor' => $usuario,
                'fechaModificacion' => $ahora,
            ]);

        // Habilitar o insertar los seleccionados
        foreach ($codigos as $cod) {
            $cod = trim($cod);

            $existe = DB::table('sw_permisos_usuario_sucursal')
                ->where('codUsuario', $codUsuario)
                ->where('codSucursal', $cod)
                ->first();

            if ($existe) {
                DB::table('sw_permisos_usuario_sucursal')
                    ->where('codigo', $existe->codigo)
                    ->update([
                        'habilitado' => 1,
                        'modificadoPor' => $usuario,
                        'fechaModificacion' => $ahora,
                    ]);
            } else {
                DB::table('sw_permisos_usuario_sucursal')->insert([
                    'codUsuario' => $codUsuario,
                    'codSucursal' => $cod,
                    'creadoPor' => $usuario,
                    'habilitado' => 1,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Permisos de sucursales actualizados.']);
    }
}
