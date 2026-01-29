<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DerechoHabiente extends Model
{
    use HasFactory;

    protected $table = 'sw_MIGRA_DERECHO_HABIENTE';
    protected $primaryKey = 'CODI_DERE_HABI';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'CODI_PERS',
        'TIPO_RELA',
        'NOMB_1',
        'NOMB_2',
        'APEL_1',
        'APEL_2',
        'CODI_TIPO_DOCU',
        'NRO_DOCU_IDEN',
        'FECH_NACI',
        'FALLECIDO',
        'DEHA_OCUPACION',
        'DEHA_EDAD',
        'USUA_CODIGO_REG',
        'USUA_FECHA_REG',
        'USUA_CODIGO_MOD',
        'USUA_FECHA_MOD',
        'DEHA_SEXO',
        'TIPO_DOCU_ACREDITA_PATERINAD',
        'DOCU_ACREDITA_PATERINAD',
        'DEHA_SITUACION',
        'DEHA_MES_CONCEPCION',
        'DEHA_FECHA_ALTA',
        'DEHA_TIPO_BAJA',
        'DEHA_FECHA_BAJA',
        'DEHA_INCAPACIDAD',
        'DEHA_RESOL_INCAPACIDAD',
        'DEHA_VIGENCIA',
        'DEHA_telefono',
        'domicilio',
        'DEHA_DEREHABI',
        'TIDV_CODIGO',

    ];
}
