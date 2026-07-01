<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Cargo
 * 
 * CREADO PARA: Eliminar dependencia de DB::table('sw_cargos')
 * INCLUYE: Scopes (habilitado, operativo, administrativo) para queries comunes
 * MEJORA: Código más limpio y seguro con prepared statements automáticos
 */
class Cargo extends Model
{
    use HasFactory;

    protected $table = 'sw_cargos';
    protected $primaryKey = 'codigo';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'cod_tipo',
        'habilitado',
        'fecha_creacion',
        'fecha_modificacion'
    ];

    /**
     * Scope para cargos habilitados
     */
    public function scopeHabilitado($query)
    {
        return $query->where('habilitado', 1);
    }

    /**
     * Scope para cargos operativos
     */
    public function scopeOperativo($query)
    {
        return $query->where('cod_tipo', 1);
    }

    /**
     * Scope para cargos administrativos
     */
    public function scopeAdministrativo($query)
    {
        return $query->where('cod_tipo', 2);
    }
}
