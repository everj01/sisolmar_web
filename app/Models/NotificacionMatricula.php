<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class NotificacionMatricula extends Model
{
    use HasFactory;

    protected $table = 'sw_notificaciones_matriculas';
    protected $primaryKey = 'codigo';
    
    // Deshabilitar timestamps automáticos para evitar error SQL Server con milisegundos
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'cod_curso',
        'nombre_curso',
        'total_personas',
        'enviados',
        'fallidos',
        'tipo',
        'mensaje',
        'leido',
        'created_at',
        'updated_at'
    ];

    /**
     * Crear notificación de proceso completado exitosamente
     */
    public static function crearNotificacionExitosa($usuarioId, $cursoId, $nombreCurso, $totalPersonas)
    {
        return self::create([
            'usuario_id' => $usuarioId,
            'cod_curso' => $cursoId,
            'nombre_curso' => $nombreCurso,
            'total_personas' => $totalPersonas,
            'enviados' => $totalPersonas,
            'fallidos' => 0,
            'tipo' => 'completado',
            'mensaje' => "Se completó exitosamente la matriculación de {$totalPersonas} persona(s) al curso {$nombreCurso}. Todos los correos fueron enviados correctamente.",
            'leido' => false,
            'created_at' => DB::raw('GETDATE()'),
            'updated_at' => DB::raw('GETDATE()')
        ]);
    }

    /**
     * Crear notificación de error por conexión
     */
    public static function crearNotificacionErrorConexion($usuarioId, $cursoId, $nombreCurso, $fallidos)
    {
        return self::create([
            'usuario_id' => $usuarioId,
            'cod_curso' => $cursoId,
            'nombre_curso' => $nombreCurso,
            'total_personas' => $fallidos,
            'enviados' => 0,
            'fallidos' => $fallidos,
            'tipo' => 'error_conexion',
            'mensaje' => "Falló el envío de {$fallidos} correo(s) del curso {$nombreCurso} por problemas de conexión. Los trabajos se reintentarán automáticamente.",
            'leido' => false,
            'created_at' => DB::raw('GETDATE()'),
            'updated_at' => DB::raw('GETDATE()')
        ]);
    }

    /**
     * Crear notificación de múltiples fallos
     */
    public static function crearNotificacionMultiplesFallos($usuarioId, $cursoId, $nombreCurso, $totalPersonas, $enviados, $fallidos)
    {
        return self::create([
            'usuario_id' => $usuarioId,
            'cod_curso' => $cursoId,
            'nombre_curso' => $nombreCurso,
            'total_personas' => $totalPersonas,
            'enviados' => $enviados,
            'fallidos' => $fallidos,
            'tipo' => 'multiples_fallos',
            'mensaje' => "Proceso de matriculación completado para el curso {$nombreCurso}: {$enviados} exitosos, {$fallidos} fallidos.",
            'leido' => false,
            'created_at' => DB::raw('GETDATE()'),
            'updated_at' => DB::raw('GETDATE()')
        ]);
    }

    /**
     * Obtener notificaciones no leídas de un usuario
     */
    public static function obtenerNoLeidas($usuarioId)
    {
        return self::where('usuario_id', $usuarioId)
            ->where('leido', 0) // SQL Server usa 0 para false
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Marcar notificación como leída
     */
    public static function marcarComoLeida($codigo)
    {
        return self::where('codigo', $codigo)->update(['leido' => 1]); // SQL Server usa 1 para true
    }

    /**
     * Marcar todas las notificaciones de un usuario como leídas
     */
    public static function marcarTodasComoLeidas($usuarioId)
    {
        return self::where('usuario_id', $usuarioId)
            ->where('leido', 0) // SQL Server usa 0 para false
            ->update(['leido' => 1]); // SQL Server usa 1 para true
    }
}
