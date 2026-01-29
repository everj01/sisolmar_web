<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDeclaracionJuradaRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // ===== DATOS PERSONALES =====
            'cod_postulante'        => ['required', 'integer'],
            'nombres_apellidos'     => ['required', 'string', 'max:255'],
            'dni'                   => ['required'],
            'caduca'                => ['nullable', 'date'],
            'estado_civil'          => ['nullable', 'string', 'max:50'],
            'sexo'                  => ['nullable', 'string', 'max:50'],
            'fecha_nacimiento'      => ['nullable', 'date'],
            'sabe_nadar'            => ['nullable', 'in:SI,NO'],
            'celular'               => ['nullable', 'regex:/^[0-9]{9}$/'],
            'correo'                => ['nullable', 'email', 'max:150'],
            'whatsapp'              => ['nullable', 'regex:/^[0-9]{9}$/'],

            // ===== DATOS DE SALUD =====
            'tipo_sangre'           => ['nullable', 'string', 'max:10'],
            'peso'                  => ['nullable', 'numeric', 'between:30,250'],
            'talla'                 => ['nullable', 'numeric', 'between:1,2.5'],

            // ===== SISTEMA PREVISIONAL =====
            'sistema_previsional'   => ['nullable', 'string', 'max:100'],
            'essalud'               => ['nullable', 'string', 'max:50'],
            'pensionista'           => ['nullable', 'string', 'in:SI,NO'],

            // ===== FORMACIÓN =====
            'grado_instruccion'     => ['nullable', 'string', 'max:100'],
            'institucion'           => ['nullable', 'string', 'max:255'],
            'carrera'               => ['nullable', 'string', 'max:255'],
            'anio_egreso'           => ['nullable', 'digits:4'],

            // ===== ANTECEDENTES =====
            'embargos'              => ['nullable', 'in:SI,NO'],
            'consumo_sustancias'    => ['nullable', 'in:SI,NO'],

            // ===== DIRECCIONES =====
            'departamento_actual'   => ['nullable', 'string'],
            'provincia_actual'      => ['nullable', 'string'],
            'distrito_actual'       => ['nullable', 'string'],
            'direccion_actual'      => ['nullable', 'string', 'max:255'],

            'departamento_dni'      => ['nullable', 'string'],
            'provincia_dni'         => ['nullable', 'string'],
            'distrito_dni'          => ['nullable', 'string'],
            'direccion_dni'         => ['nullable', 'string', 'max:255'],

            // ===== CONTACTO DE EMERGENCIA =====
            'contacto_emergencia'   => ['nullable', 'string', 'max:255'],
            'celular_emergencia'    => ['nullable', 'regex:/^[0-9]{9}$/'],
            'parentesco_emergencia' => ['nullable', 'string', 'max:100'],

            // ===== LICENCIAS / ARMAS =====
            'curso_sucamec'         => ['nullable', 'in:SI,NO'],
            'institucion_laboral'   => ['nullable', 'string', 'max:250'],
            'smo'                   => ['nullable', 'string', 'max:100'],
            'licencia_arma'         => ['nullable', 'json'],
            'tipo_arma'             => ['nullable', 'string', 'max:100'],
            'arma_propia'           => ['nullable', 'in:SI,NO'],

            // ===== CONDUCCIÓN =====
            'brevete'               => ['nullable', 'string', 'max:50'],
            'clase_brevete'         => ['nullable', 'string', 'max:50'],
            'tipo_vehiculo'         => ['nullable', 'string', 'max:100'],
            'vehiculo_propio'       => ['nullable', 'in:SI,NO'],

            // ===== EXPERIENCIA LABORAL =====
            'empresa_anterior'      => ['nullable', 'string', 'max:255'],
            'cargo_anterior'        => ['nullable', 'string', 'max:255'],
            'duracion_anterior'     => ['nullable', 'string', 'max:50'],

            // ===== PROFESIÓN ALTERNATIVA =====
            'profesion_alterna'     => ['nullable', 'string', 'max:255'],

            // ===== FAMILIARES =====
            'parentesco'            => ['nullable', 'array'],
            'parentesco.*'          => ['nullable', 'string', 'max:100'],

            'apellidosNombres'      => ['nullable', 'array'],
            'apellidosNombres.*'    => ['nullable', 'string', 'max:255'],

            'fechaNacimiento'       => ['nullable', 'array'],
            'fechaNacimiento.*'     => ['nullable', 'date'],
        ];
    }
}
