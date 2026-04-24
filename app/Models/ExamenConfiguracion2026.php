<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamenConfiguracion2026 extends Model
{
    use HasFactory;

    protected $connection = 'sqlsrv';
    protected $table = 'sw_examen_configuracion_2026';

    protected $primaryKey = 'codigo';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'cod_examen',
        'cod_curso',
        'cant_preguntas_examen',
        'habilitado',
        'fecha_creacion'
    ];

    public $timestamps = false;

    public function examen()
    {
        return $this->belongsTo(ExamenCurso::class, 'cod_examen', 'codigo');
    }

    public function curso()
    {
        return $this->belongsTo(Cursos::class, 'cod_curso', 'codigo');
    }
}
