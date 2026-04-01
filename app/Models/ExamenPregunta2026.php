<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamenPregunta2026 extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'sw_examen_preguntas_2026';

    protected $primaryKey = 'codigo';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'cod_examen',
        'tipo_pregunta',
        'texto_pregunta',
        'opciones_json',
        'respuesta_correcta',
        'estado_revision',
        'fecha_creacion'
    ];

    public $timestamps = false;

    public function examen()
    {
        return $this->belongsTo(ExamenCurso::class, 'cod_examen', 'codigo');
    }

    /**
     * El campo opciones_json se maneja como array/objeto
     */
    protected $casts = [
        'opciones_json' => 'array'
    ];
}
