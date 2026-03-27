<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MigraPersonal extends Model
{
    protected $connection = 'sqlsrv';
    protected $table = 'sw_MIGRA_PERSONAL';
    protected $primaryKey = 'CODI_PERS';
    public $incrementing = false;
    public $timestamps = false;

    // Relación con la matrícula (puede haber varias matrículas por persona)
    public function matriculas()
    {
        return $this->hasMany(Matricula::class, 'cod_personal', 'CODI_PERS');
    }
}
