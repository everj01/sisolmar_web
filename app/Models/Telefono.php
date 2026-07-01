<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Telefono extends Model
{
    use HasFactory;

    protected $table = 'sw_MIGRA_TELEFONO';
    public $timestamps = false;

    protected $fillable = [
        'CODI_PERS',
        'NRO_TELE',
        'TIPO_TELE',
        'OBSERVACION',
        'TELE_VIGENCIA',
        'TELE_RESERVADO',
        'TELE_EMERGENCIA',
        'TELE_CONTACTO',
    ];
}
