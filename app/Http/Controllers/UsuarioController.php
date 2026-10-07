<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

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
        $ahora = DB::raw('GETDATE()');

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

// ========================================================================
    // MENÚS Y SUBMENÚS
    // ========================================================================

    public function getMenusUsuario($codUsuario)
    {
        $menusPadres = DB::table('sw_menus')->where('habilitado', 1)->select('codigo', 'descripcion')->orderBy('orden')->get();
        $submenus = DB::table('sw_submenus')->where('habilitado', 1)->select('codigo', 'codMenu', 'descripcion')->orderBy('orden')->get();

        // AQUÍ EL CAMBIO 1: Agregamos 'nro' al select
        $opcionesSub = DB::table('sw_submenus_opciones')->where('habilitado', 1)->select('codigo', 'codSubmenu', 'nombre', 'nro')->orderBy('nro')->get();

        $permisosActivos = DB::table('sw_usuarios_permisos')->where('codUsuario', $codUsuario)->where('habilitado', 1)->get();
        $menusAsignados = $permisosActivos->pluck('codMenu')->toArray();
        $submenusAsignados = $permisosActivos->pluck('codSubmenu')->toArray();

        $opcionesAsignadas = DB::table('sw_permisos_usuario_submenu_opcion')
            ->where('codUsuario', $codUsuario)
            ->where('habilitado', 1)
            ->pluck('codOpcione') 
            ->toArray();

        $resultado = $menusPadres->map(function ($padre) use ($submenus, $opcionesSub, $menusAsignados, $submenusAsignados, $opcionesAsignadas) {
            $hijos = $submenus->where('codMenu', $padre->codigo)->map(function ($hijo) use ($opcionesSub, $submenusAsignados, $opcionesAsignadas) {
                
                $opciones = $opcionesSub->where('codSubmenu', $hijo->codigo)->map(function ($opc) use ($opcionesAsignadas) {
                    return [
                        'codigo'   => $opc->codigo,
                        'nombre'   => $opc->nombre,
                        'nro'      => $opc->nro, // AQUÍ EL CAMBIO 2: Pasamos el 'nro' al JS
                        'asignado' => in_array($opc->codigo, $opcionesAsignadas)
                    ];
                })->values();

                return [
                    'codigo'   => $hijo->codigo,
                    'nombre'   => $hijo->descripcion, 
                    'asignado' => in_array($hijo->codigo, $submenusAsignados),
                    'opciones' => $opciones 
                ];
            })->values();

            return [
                'codigo'   => $padre->codigo,
                'nombre'   => $padre->descripcion, 
                'asignado' => in_array($padre->codigo, $menusAsignados),
                'submenus' => $hijos
            ];
        });

        return response()->json($resultado);
    }

    public function saveMenusUsuario(Request $request)
    {
        $request->validate([
            'codUsuario'   => 'required|integer',
            'menus_padres' => 'array',
            'submenus'     => 'array',
            'opciones'     => 'array', // Recibimos el tercer nivel del JS
        ]);

        $codUsuario = $request->codUsuario;
        $usuario = Auth::user()->usuario;
        $ahora = DB::raw('GETDATE()');

        DB::beginTransaction();
        try {
            // 1. Limpiar e insertar nivel 1 y 2 en sw_usuarios_permisos
            DB::table('sw_usuarios_permisos')->where('codUsuario', $codUsuario)->update([
                'habilitado' => 0, 'modificado_por' => $usuario, 'fecha_modificacion' => $ahora
            ]);

            $relacionSubmenus = DB::table('sw_submenus')->pluck('codMenu', 'codigo')->toArray();
            $combinaciones = [];
            foreach (($request->submenus ?? []) as $codSub) {
                $combinaciones[] = ['codMenu' => $relacionSubmenus[$codSub] ?? 0, 'codSubmenu' => $codSub];
            }
            foreach (($request->menus_padres ?? []) as $codPadre) {
                $combinaciones[] = ['codMenu' => $codPadre, 'codSubmenu' => 0];
            }

            foreach ($combinaciones as $item) {
                $existe = DB::table('sw_usuarios_permisos')->where('codUsuario', $codUsuario)->where('codMenu', $item['codMenu'])->where('codSubmenu', $item['codSubmenu'])->first();
                if ($existe) {
                    DB::table('sw_usuarios_permisos')->where('codigo', $existe->codigo)->update(['habilitado' => 1, 'modificado_por' => $usuario, 'fecha_modificacion' => $ahora]);
                } else {
                    DB::table('sw_usuarios_permisos')->insert(['codUsuario' => $codUsuario, 'codMenu' => $item['codMenu'], 'codSubmenu' => $item['codSubmenu'], 'habilitado' => 1, 'creado_por' => $usuario, 'fecha_creacion' => $ahora]);
                }
            }

            // 2. Limpiar e insertar Nivel 3 (sw_permisos_usuario_submenu_opcion)
            DB::table('sw_permisos_usuario_submenu_opcion')->where('codUsuario', $codUsuario)->update([
                'habilitado' => 0, 'modificado_por' => $usuario, 'fecha_modificacion' => $ahora
            ]);

            foreach (($request->opciones ?? []) as $codOpc) {
                $existeOpc = DB::table('sw_permisos_usuario_submenu_opcion')->where('codUsuario', $codUsuario)->where('codOpcione', $codOpc)->first();
                if ($existeOpc) {
                    DB::table('sw_permisos_usuario_submenu_opcion')->where('codigo', $existeOpc->codigo)->update(['habilitado' => 1, 'modificado_por' => $usuario, 'fecha_modificacion' => $ahora]);
                } else {
                    DB::table('sw_permisos_usuario_submenu_opcion')->insert(['codUsuario' => $codUsuario, 'codOpcione' => $codOpc, 'habilitado' => 1, 'creado_por' => $usuario, 'fecha_creacion' => $ahora]);
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Permisos guardados.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
