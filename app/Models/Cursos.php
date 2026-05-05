<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cursos extends Model
{
    use HasFactory;

    protected $table = 'sw_cursos';

    protected $primaryKey = 'codigo'; // 👈 clave primaria
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'nombre',
        'codigo_curso',
        'fecha_creacion',
        'fecha_modificacion',
        'habilitado',
        'tipo_curso',
        'area_conocimiento',
        'area',
        'periodicidad',
        'es_periodico',
        'frecuencia',
        'aplica_evaluacion',
        'obligatorio_alta',
        'cod_responsable',
        'target_group',
        'codigo_moodle',
        'observaciones',
        'proyeccion_anios',
    ];

    public $timestamps = false;

    public function examen()
    {
        return $this->hasOne(ExamenCurso::class, 'cod_cursos', 'codigo');
    }

    public function programaciones()
    {
        return $this->hasMany(CursoProgramacion::class, 'cod_cursos', 'codigo');
    }

    public function tipoCurso()
    {
        return $this->belongsTo(CapacitacionTipoCurso::class, 'tipo_curso', 'codigo');
    }

    public function area()
    {
        return $this->belongsTo(CapacitacionAreas::class, 'area', 'codigo');
    }
}
