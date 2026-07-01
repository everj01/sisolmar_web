<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DjGeneradaPeriodo extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'sw_dj_generada_x_periodo';
    protected $primaryKey = 'codigo';

    const UPDATED_AT = null;
    const CREATED_AT = null;

    protected $fillable = [
        'codPeriodo',
        'codPersonal',
        'generado',
        'creadoPor',
        'fechaCreacion',
        'modificadoPor',
        'fechaModificacion',
        'habilitado',
    ];

    protected $casts = [
        'generado' => 'boolean',
        'habilitado' => 'boolean',
        'fechaCreacion' => 'datetime',
        'fechaModificacion' => 'datetime',
    ];
}
