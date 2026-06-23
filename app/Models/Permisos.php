<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Permisos extends Model
{
    /**
     * Retorna el árbol de menús y submenús habilitados para un rol dado.
     *
     * @param  int  $rolId
     * @return array<int, array{
     *     id: string,
     *     nombre: string,
     *     modulo: string,
     *     icono: string,
     *     orden: int,
     *     tiene_submenu: bool,
     *     submenus: array
     * }>
     *
     * @throws \RuntimeException  Si falla la consulta a la base de datos.
     */
    public static function getPermissionsByRole(int $rolId): array
    {
        try {
            // 1. Obtener MENÚS habilitados para el rol
            $menus = DB::select("
                SELECT m.codigo, m.descripcion, m.icono, m.modulo, m.orden, m.tiene_submenu
                FROM sw_menus m
                INNER JOIN sw_roles_permisos p ON m.codigo = p.codMenu
                WHERE p.codRol     = ?
                  AND p.habilitado  = 1
                  AND m.habilitado  = 1
                ORDER BY m.orden ASC
            ", [$rolId]);

            // 2. Obtener SUBMENÚS habilitados para el rol
            $submenus = DB::select("
                SELECT DISTINCT sm.codigo, sm.codMenu, sm.descripcion, sm.vista, sm.orden
                FROM sw_submenus sm
                INNER JOIN sw_roles_permisos p
                        ON sm.codigo  = p.codSubmenu
                       AND sm.codMenu = p.codMenu
                WHERE p.codRol    = ?
                  AND p.habilitado = 1
                  AND sm.habilitado = 1
                ORDER BY sm.codMenu ASC, sm.orden ASC
            ", [$rolId]);

        } catch (\Exception $e) {
            Log::error('Error al obtener permisos por rol', [
                'rol_id' => $rolId,
                'error'  => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                "No se pudieron cargar los permisos para el rol #{$rolId}.",
                previous: $e
            );
        }

        // 3. Construir árbol indexado por código de menú
        $menuArbol = [];

        foreach ($menus as $menu) {
            $menuArbol[$menu->codigo] = [
                'id'            => $menu->codigo,
                'nombre'        => $menu->descripcion,
                'modulo'        => $menu->modulo,
                'icono'         => $menu->icono,
                'orden'         => $menu->orden,
                'tiene_submenu' => (bool) $menu->tiene_submenu,
                'submenus'      => [],
            ];
        }

        // 4. Anidar submenús en su menú padre
        //    Nota: si tiene_submenu = false, el submenú se ignora intencionalmente
        //    (el flag controla si el menú debe renderizarse como expandible).
        foreach ($submenus as $submenu) {
            $padre = $menuArbol[$submenu->codMenu] ?? null;

            if ($padre === null || ! $padre['tiene_submenu']) {
                continue;
            }

            $menuArbol[$submenu->codMenu]['submenus'][] = [
                'id'      => $submenu->codigo,
                'id_menu' => $submenu->codMenu,
                'nombre'  => $submenu->descripcion,
                'vista'   => $submenu->vista,
                'orden'   => $submenu->orden,
            ];
        }

        // 5. Devolver array plano (índices numéricos consecutivos)
        return array_values($menuArbol);
    }
}