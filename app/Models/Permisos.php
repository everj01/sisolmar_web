<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Permisos extends Model
{
    public static function getPermissionsByRole($rolId): array
    {
        $menuArbol = [];

        $menus = DB::select("
            SELECT m.codigo, m.descripcion, m.icono, m.modulo, m.orden, m.tiene_submenu
            FROM sw_menus m
            INNER JOIN sw_roles_permisos p ON m.codigo = p.codMenu
            WHERE p.codRol = ? AND p.habilitado = 1 AND m.habilitado = 1
            ORDER BY m.orden ASC
        ", [$rolId]);

        foreach ($menus as $menu) {
            $menuArbol[$menu->codigo] = [
                'id'            => $menu->codigo,
                'nombre'        => $menu->descripcion,
                'modulo'        => $menu->modulo,
                'icono'         => $menu->icono,
                'orden'         => $menu->orden,
                'tiene_submenu' => $menu->tiene_submenu,
                'submenus'      => []
            ];
        }

        // 2. Obtener SUBMENÚS
        $submenus = DB::select("
            SELECT DISTINCT sm.codigo, sm.codMenu, sm.descripcion, sm.vista, sm.orden
            FROM sw_submenus sm
            INNER JOIN sw_roles_permisos p ON sm.codigo = p.codSubmenu AND sm.codMenu = p.codMenu
            WHERE p.codRol = ? AND p.habilitado = 1 AND sm.habilitado = 1
            ORDER BY sm.codMenu ASC, sm.orden ASC
        ", [$rolId]);

        foreach ($submenus as $submenu) {
            if (isset($menuArbol[$submenu->codMenu]) && $menuArbol[$submenu->codMenu]['tiene_submenu']) {
                $menuArbol[$submenu->codMenu]['submenus'][] = [
                    'id'      => $submenu->codigo,
                    'id_menu' => $submenu->codMenu,
                    'nombre'  => $submenu->descripcion,
                    'vista'   => $submenu->vista,
                    'orden'   => $submenu->orden
                ];
            }
        }

        // Retornar como array plano (sin claves de menú como índices)
        return array_values($menuArbol);
    }
}
