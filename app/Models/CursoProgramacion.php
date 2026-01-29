<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CursoProgramacion extends Model
{
    use HasFactory;

    protected $table = 'sw_cursos_programacion';

    protected $primaryKey = 'codigo';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'codigo_programacion',
        'cod_cursos',
        'fecha_inicio',
        'fecha_final',
        'creado_por',
        'fecha_creacion',
        'modificado_por',
        'fecha_modificacion',
        'habilitado'
    ];

    public $timestamps = false;

    public function curso()
    {
        return $this->belongsTo(Cursos::class, 'cod_cursos', 'codigo');
    }
}
