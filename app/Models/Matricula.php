<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Matricula
 * 
 * CREADO PARA: Reemplazar queries DB::table('sw_matriculas') inseguros
 * BENEFICIOS: Previene SQL injection, type safety, código más mantenible
 * AUTOR: Migración de seguridad 15/01/2026
 */
class Matricula extends Model
{
    use HasFactory;

    protected $table = 'sw_matriculas';
    protected $primaryKey = 'codigo';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'cod_curso',
        'cod_programacion',
        'cod_personal',
        'usuario_id',
        'fecha_matricula',
        'estado',
        'tipo_matricula',
        'origen_matricula',
    ];

    /**
     * Relación con la programación
     */
    public function programacion()
    {
        return $this->belongsTo(CursoProgramacion::class, 'cod_programacion', 'codigo_programacion');
    }

    /**
     * Constantes de estado
     */
    const ESTADO_MATRICULADO = 'MATRICULADO';
    const ESTADO_EN_PROGRESO = 'EN_PROGRESO';
    const ESTADO_COMPLETADO = 'COMPLETADO';
    const ESTADO_APROBADO = 'APROBADO';
    const ESTADO_REPROBADO = 'REPROBADO';
    const ESTADO_CANCELADO = 'CANCELADO';

    /**
     * Relación con el curso
     */
    public function curso()
    {
        return $this->belongsTo(Cursos::class, 'cod_curso', 'codigo');
    }

    /**
     * Relación con el personal
     */
    public function personal()
    {
        return $this->belongsTo(Personal::class, 'cod_personal', 'CODI_PERS');
    }
}
