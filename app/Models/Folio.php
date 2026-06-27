<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Folio
 * 
 * PROPÓSITO: Migrar de queries crudas DB::table() a Eloquent ORM
 * VENTAJAS: Protección automática contra SQL injection, mejor mantenibilidad
 * SCOPES: habilitado() y categoria() para filtros frecuentes
 */
class Folio extends Model
{
    use HasFactory;

    protected $table = 'sw_folios';
    protected $primaryKey = 'codigo';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'cod_categoria',
        'obligatorio',
        'habilitado',
        'fecha_creacion',
        'fecha_modificacion'
    ];

    /**
     * Scope para folios habilitados
     */
    public function scopeHabilitado($query)
    {
        return $query->where('habilitado', 1);
    }

    /**
     * Scope por categoría
     */
    public function scopeCategoria($query, $categoria)
    {
        return $query->where('cod_categoria', $categoria);
    }
}
