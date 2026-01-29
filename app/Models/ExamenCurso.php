<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamenCurso extends Model
{
    use HasFactory;

    protected $table = 'sw_cursos_examen';

    protected $primaryKey = 'codigo';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'cod_cursos',
        'nombre',
        'descripcion',
        'tiempo',
        'nota_minima',
        'intentos',
        'file_tiene',
        'file_nombre',
        'file_ruta',
        'file_extension',
        'file_tipo',
        'file_nombre_original',
        'fecha_creacion',
        'fecha_modificacion',
        'habilitado'
    ];

    public $timestamps = false;

    public function curso()
    {
        return $this->belongsTo(Cursos::class, 'cod_cursos');
    }

}
