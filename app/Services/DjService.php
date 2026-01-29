<?php

namespace App\Services;

use App\Models\DerechoHabiente;
use App\Models\Personal;
use App\Models\Telefono;
use Exception;
use Illuminate\Support\Facades\DB;

class DjService
{
    public function guardarDeclaracionJurada(array $data)
    {
        DB::beginTransaction();

        try {

            $personal = Personal::create([
                'CODI_PERS'             => $data['cod_postulante'],
                'NRO_DOCU_IDEN'         => $data['dni'],
                'ESTA_CIVI'             => $data['estado_civil'] ?? null,
                'SEXO'                  => $data['sexo'] ?? null,
                'FECH_NACI'             => $data['fecha_nacimiento'] ?? null,
                'ESSALUD'               => $data['essalud'] ?? null,
                'tipo_sangr'            => $data['tipo_sangre'] ?? null,
                'peso_kilo'             => $data['peso'] ?? null,
                'tall_metr'             => $data['talla'] ?? null,
                'PERS_PROFESION'        => $data['profesion_alterna'] ?? null,
                'PERS_GRADO_INSTRUCCION'=> $data['grado_instruccion'] ?? null,
                // 'IEDU_CODIGO'           => $data['institucion'] ?? null,
                // 'CARR_CODIGO'           => $data['carrera'] ?? null,
                'EGRESO_EDUCATIVO'      => $data['anio_egreso'] ?? null,
                'PERS_SNADAR'           => $data['sabe_nadar'] ?? null,
                'PERS_CONSMO'           => $data['consumo_sustancias'] ?? null,
                'DEPARTAMENTO'          => $data['departamento_actual'] ?? null,
                'PROVINCIA'             => $data['provincia_actual'] ?? null,
                'DISTRITO'              => $data['distrito_actual'] ?? null,
                'DIRECCION'             => $data['direccion_actual'] ?? null,
                'PERS_EMAIL'            => $data['correo'] ?? null,
                'PERS_NOMCONTACTO'      => $data['contacto_emergencia'] ?? null,
                'PERS_NROEMERGENCIA'    => $data['celular_emergencia'] ?? null,
                'PERS_CONYUGE'          => $data['parentesco_emergencia'] ?? null,
                'PERS_CONLICARMAS'      => $data['licencia_arma'] ?? null,
                'PERS_TIPOARMA'         => $data['tipo_arma'] ?? null,
                'PERS_CONARMAS'         => $data['arma_propia'] ?? null,

                
                'PERS_BREVETE'          => $data['brevete'] ?? null,
                'CLASE_BREVETE'         => $data['clase_brevete'] ?? null,
                'PERS_TRABAJO_ANTERIOR' => $data['empresa_anterior'] ?? null,
                'PERS_CARGOTRABANT'     => $data['cargo_anterior'] ?? null,
                'PERS_DURACIONANT'      => $data['duracion_anterior'] ?? null,
                'PERS_CONTRATADO'       => 'SI',
                'PERS_VIGENCIA'         => 1,
                //'PERS_FECHAREG'         => now(),
            ]);


            if (!empty($data['celular'])) {
                Telefono::create([
                    'CODI_PERS'     => $personal->CODI_PERS,
                    'NRO_TELE'      => $data['celular'],
                    'TIPO_TELE'     => 'MOVIL',
                    'OBSERVACION'   => 'Teléfono principal',
                    'TELE_VIGENCIA' => 'SI',
                ]);
            }

            if (!empty($data['whatsapp'])) {
                Telefono::create([
                    'CODI_PERS'     => $personal->CODI_PERS,
                    'NRO_TELE'      => $data['whatsapp'],
                    'TIPO_TELE'     => 'MOVIL',
                    'OBSERVACION'   => 'Número de WhatsApp',
                    'TELE_VIGENCIA' => 'SI',
                ]);
            }

            if (!empty($data['celular_emergencia'])) {
                Telefono::create([
                    'CODI_PERS'       => $personal->CODI_PERS,
                    'NRO_TELE'        => $data['celular_emergencia'],
                    'TIPO_TELE'       => 'MOVIL',
                    'OBSERVACION'     => $data['contacto_emergencia'],
                    'TELE_EMERGENCIA' => 1,
                    'TELE_VIGENCIA'   => 'SI',
                ]);
            }

            
            if (!empty($data['parentesco'])) {
                foreach ($data['parentesco'] as $i => $parentesco) {
                    if (empty($parentesco) && empty($data['apellidosNombres'][$i]) && empty($data['fechaNacimiento'][$i])) {
                        continue;
                    }

                    DerechoHabiente::create([
                        'CODI_PERS'       => $personal->CODI_PERS,
                        'TIPO_RELA'       => $parentesco ?? null,
                        'NOMB_1'          => $data['apellidosNombres'][$i] ?? null,
                        'FECH_NACI'       => $data['fechaNacimiento'][$i] ?? null,
                        'DEHA_VIGENCIA'   => 1,
                    ]);
                }
            }


            DB::commit();
            return $personal;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

}