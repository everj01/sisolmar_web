<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapacitacionTipoCurso extends Model
{
    use HasFactory;

    protected $table = 'sw_capacitacion_tipo_curso';

    protected $primaryKey = 'codigo';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'codigo',
        'descripcion',
        'fechaCreacion',
        'creadoPor',
        'fechaModificacion',
        'modificado_por',
        'habilitado',
    ];

    public $timestamps = false;
}
