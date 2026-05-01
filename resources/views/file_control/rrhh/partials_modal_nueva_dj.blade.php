{{-- ============================================================
     Partial: partials_modal_nueva_dj.blade.php
     Modal NUEVA DJ — estructura 100% igual al modal original.
     Sin SVGs inline. Dos versiones: operativo / administrativo.
     JS al final del archivo, prefijo ndj_ para evitar conflictos.
     ============================================================ --}}
@include('file_control.rrhh.modal_js_styles_nuevo')

<div id="modalNuevaDJ"
    class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-x-hidden overflow-y-auto hidden pointer-events-none">
    <div class="-translate-y-5 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded"
        style="width:96%;max-width:1100px;">
        <div class="flex flex-col border border-default-200 shadow-sm rounded-lg pointer-events-auto">

            {{-- ── Header ──────────────────────────────────── --}}
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 18px;border-bottom:1px solid #e5e7eb;background:#fff;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <span style="font-size:13px;font-weight:700;color:#374151;letter-spacing:.02em;text-transform:uppercase;">
                        Nueva Declaración Jurada
                    </span>
                    <span id="ndj_tipo_badge"
                        style="display:none;font-size:10px;font-weight:700;padding:2px 10px;border-radius:20px;text-transform:uppercase;letter-spacing:.04em;">
                    </span>
                </div>
                <button type="button" id="ndj_btnCerrarX"
                    style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;padding:2px 4px;"
                    onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#9ca3af'">&#x2715;</button>
            </div>

            {{-- ── Body ────────────────────────────────────── --}}
            <div style="overflow-y:auto;height:calc(85vh - 100px);padding:14px 16px;background:#fff;">
                <form id="formNuevaDJ" autocomplete="off" novalidate>
                    @csrf
                    <input type="hidden" id="ndj_tipo_personal"  name="ndj_tipo_personal">
                    <input type="hidden" id="ndj_cod_postulante" name="ndj_cod_postulante" value="">

                    <input type="hidden" name="ndj_usuario" id="ndj_usuario" value="{{ session('usuario') }}">

                    <div id="ndj_alert_tipo_personal"
                        style="display:block;background:#fef3c7;color:#92400e;padding:10px 16px;border-radius:6px;margin-bottom:12px;font-size:14px;font-weight:500;">
                        ⚠️ Debe seleccionar el tipo de personal antes de completar el formulario.
                    </div>

                    {{-- ══════════════════════════════════════
                         ⓪ DOCUMENTO Y TIPO DE PERSONAL
                         ══════════════════════════════════════ --}}
                    <div class="dj-group">
                        <div class="dj-group-body">
                            <div class="dj-grid-4">
                                <div>
                                    <label class="dj-label">Tipo de Personal <span style="color:#ef4444">*</span></label>
                                    <select id="ndj_sel_tipo_personal" name="ndj_sel_tipo_personal" class="dj-select">
                                        <option value="">— Seleccionar —</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="dj-label">Tipo de Documento <span style="color:#ef4444">*</span></label>
                                    <select id="ndj_tipo_documento" name="ndj_tipo_documento" class="dj-select">
                                        <option value="">cargando...</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="dj-label">Número de Documento <span style="color:#ef4444">*</span></label>
                                    <input type="text" id="ndj_nro_documento" name="ndj_nro_documento"
                                        class="dj-input" placeholder="Ingrese el número"
                                        maxlength="20" style="text-transform:uppercase;">
                                </div>
                                <div>
                                    <label class="dj-label">Sucursal <span style="color:#ef4444">*</span></label>

                                    <select id="ndj_filtroSucursal" class="dj-select" name="ndj_filtroSucursal">

                                        @foreach ($sucursalesFiltradas as $sucursal)
                                            <option value="{{ $sucursal->codigo }}">
                                                {{ $sucursal->abreviatura }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>
                            </div>
                            <div style="margin-top:6px;">
                                <a href="https://eldni.com/pe/buscar-datos-por-dni" target="_blank"
                                    style="display:inline-block;font-size:11px;color:var(--color-primary,#6366f1);border:1px solid var(--color-primary,#6366f1);padding:3px 10px;border-radius:5px;text-decoration:none;">
                                    Consultar DNI
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- ══════════════════════════════════════
                         ① DATOS PERSONALES
                         ══════════════════════════════════════ --}}
                    <div class="dj-group">
                        <div class="dj-group-header">① Datos Personales</div>
                        <div class="dj-group-body">

                            {{-- Identidad --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-user-circle'></i>
                                    Identidad y Datos Personales
                                </div>
                                <div class="dj-section-body">
                                    <div style="display:flex;gap:14px;align-items:flex-start;">
                                        <div style="flex:1;">
                                            <input type="hidden" id="ndj_nombres_apellidos" name="ndj_nombres_apellidos">
                                            <div class="dj-grid-2" style="margin-bottom:10px;">
                                                <div>
                                                    <label class="dj-label">Primer Nombre</label>
                                                    <input type="text" id="ndj_nombre1" name="ndj_nombre1"
                                                        class="dj-input" placeholder="Primer nombre"
                                                        style="text-transform:uppercase;">
                                                </div>
                                                <div>
                                                    <label class="dj-label">Segundo Nombre</label>
                                                    <input type="text" id="ndj_nombre2" name="ndj_nombre2"
                                                        class="dj-input" placeholder="Segundo nombre (opcional)"
                                                        style="text-transform:uppercase;">
                                                </div>
                                                <div>
                                                    <label class="dj-label">Apellido Paterno</label>
                                                    <input type="text" id="ndj_apellido_paterno" name="ndj_apellido_paterno"
                                                        class="dj-input" placeholder="Apellido paterno"
                                                        style="text-transform:uppercase;">
                                                </div>
                                                <div>
                                                    <label class="dj-label">Apellido Materno</label>
                                                    <input type="text" id="ndj_apellido_materno" name="ndj_apellido_materno"
                                                        class="dj-input" placeholder="Apellido materno"
                                                        style="text-transform:uppercase;">
                                                </div>
                                            </div>
                                        </div>
                                        {{-- Foto --}}
                                        <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                                            <div class="dj-foto-wrap" id="ndj_placeholderFoto"
                                                onclick="document.getElementById('ndj_inputFoto').click()" style="cursor:pointer;">
                                                <i class='bx bx-user' style="font-size:28px;color:#d1d5db;"></i>
                                                <span style="font-size:10px;color:#9ca3af;margin-top:3px;">FOTO</span>
                                                <img id="ndj_previewFoto" class="hidden"
                                                    style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;" />
                                                <input type="file" id="ndj_inputFoto" accept="image/*" class="hidden" />
                                            </div>
                                            <div style="display:flex;gap:5px;">
                                                <button type="button" id="ndj_btnSubirFoto" class="dj-btn-sm"
                                                    style="background:#f3f4f6;color:#374151;border-radius:5px;">Subir foto</button>
                                                <button type="button" id="ndj_btnEliminarFoto"
                                                    class="dj-btn-sm dj-btn-danger hidden">Quitar</button>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="dj-divider">
                                    <div class="dj-grid-4">
                                        <div>
                                            <label class="dj-label">Caduca Documento</label>
                                            <input type="date" id="ndj_caduca" name="ndj_caduca" class="dj-input">
                                        </div>
                                        <div>
                                            <label class="dj-label">Estado Civil</label>
                                            <select id="ndj_estado_civil" name="ndj_estado_civil" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Sexo</label>
                                            <select id="ndj_sexo" name="ndj_sexo" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                                <option value="M">Masculino</option>
                                                <option value="F">Femenino</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Fecha de Nacimiento</label>
                                            <input type="date" id="ndj_fecha_nacimiento" name="ndj_fecha_nacimiento" class="dj-input">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Ciudad de nacimiento --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-map'></i>
                                    Ciudad de Nacimiento
                                </div>
                                <div class="dj-section-body">
                                    {{-- ✅ CORREGIDO: Agregado campo ciudad_naci --}}
                                    <div class="dj-grid-4" style="margin-bottom:8px;">
                                        <div>
                                            <label class="dj-label">Departamento</label>
                                            <select id="ndj_departamento_nac" name="ndj_departamento_nac" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Provincia</label>
                                            <select id="ndj_provincia_nac" name="ndj_provincia_nac" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Distrito</label>
                                            <select id="ndj_distrito_nac" name="ndj_distrito_nac" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Ciudad / Localidad</label>
                                            <input type="text" id="ndj_ciudad_naci" name="ndj_ciudad_naci"
                                                class="dj-input" placeholder="Ej. Trujillo"
                                                style="text-transform:uppercase;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Contacto --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-phone'></i>
                                    Información de Contacto
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-3">
                                        <div>
                                            <label class="dj-label">Celular</label>
                                            <input type="text" id="ndj_celular" name="ndj_celular"
                                                class="dj-input" placeholder="999 999 999">
                                        </div>
                                        <div>
                                            <label class="dj-label">Correo electrónico</label>
                                            <input type="email" id="ndj_correo" name="ndj_correo"
                                                class="dj-input" placeholder="ejemplo@correo.com">
                                        </div>
                                        <div>
                                            <label class="dj-label">WhatsApp</label>
                                            <input type="text" id="ndj_whatsapp" name="ndj_whatsapp"
                                                class="dj-input" placeholder="999 999 999">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Médica --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-plus-medical'></i>
                                    Información Médica
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-3">
                                        <div>
                                            <label class="dj-label">Tipo de Sangre</label>
                                            <select id="ndj_tipo_sangre" name="ndj_tipo_sangre" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                                <option value="O+">O+</option><option value="O-">O-</option>
                                                <option value="A+">A+</option><option value="A-">A-</option>
                                                <option value="B+">B+</option><option value="B-">B-</option>
                                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                                <option value="RH">RH</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Peso (kg)</label>
                                            <input type="number" id="ndj_peso" name="ndj_peso"
                                                step="0.01" class="dj-input" placeholder="70">
                                        </div>
                                        <div>
                                            <label class="dj-label">Talla (m)</label>
                                            <input type="number" id="ndj_talla" name="ndj_talla"
                                                step="0.01" class="dj-input" placeholder="1.75">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Previsional --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-building-house'></i>
                                    Información Previsional
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-3">
                                        <div>
                                            <label class="dj-label">Sistema Provisional</label>
                                            <select id="ndj_sistema_previsional" name="ndj_sistema_previsional" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">ESSALUD Vida</label>
                                            <select id="ndj_essalud" name="ndj_essalud" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                                <option value="SI">Sí</option>
                                                <option value="NO">No</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Pensionista</label>
                                            <select id="ndj_pensionista" name="ndj_pensionista" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                                <option value="SI">Sí</option>
                                                <option value="NO">No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Educación --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-book-open'></i>
                                    Educación
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-4">
                                        <div>
                                            <label class="dj-label">Grado de Instrucción</label>
                                            <select id="ndj_grado_instruccion" name="ndj_grado_instruccion" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                                @foreach ($grados as $grado)
                                                    <option value="{{ $grado->id }}">{{ $grado->text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Institución</label>
                                            <select id="ndj_institucion" name="ndj_institucion" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                                @foreach ($instituciones as $inst)
                                                    <option value="{{ $inst->id }}">{{ $inst->text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Carrera</label>
                                            <select id="ndj_carrera" name="ndj_carrera" class="dj-select">
                                                <option value="999999">NO ESPECIFICA</option>
                                                @foreach ($carreras as $carrera)
                                                    <option value="{{ $carrera->id }}">{{ $carrera->text }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Año de egreso</label>
                                            <input type="number" id="ndj_anio_egreso" name="ndj_anio_egreso"
                                                class="dj-input" placeholder="2020">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Información adicional --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-info-circle'></i>
                                    Información Adicional
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-3">
                                        <div>
                                            <label class="dj-label">Embargos financieros</label>
                                            <select id="ndj_embargos" name="ndj_embargos" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                                <option value="SI">Sí</option>
                                                <option value="NO">No</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Cuenta de Sueldo</label>
                                            <select id="ndj_cuenta_banco" name="ndj_cuenta_banco" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                                <option value="BCP">BCP</option>
                                                <option value="INTERBANK">INTERBANK</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Direcciones --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-map-pin'></i>
                                    Direcciones
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-2">
                                        <div>
                                            <p class="dj-label" style="color:#9ca3af;margin-bottom:6px;font-size:10px;">— Dirección Actual</p>
                                            <div class="dj-grid-3" style="margin-bottom:8px;">
                                                <div>
                                                    <label class="dj-label">Departamento</label>
                                                    <select id="ndj_departamento_actual" name="ndj_departamento_actual" class="dj-select">
                                                        <option value="" disabled selected>—</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="dj-label">Provincia</label>
                                                    <select id="ndj_provincia_actual" name="ndj_provincia_actual" class="dj-select">
                                                        <option value="" disabled selected>—</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="dj-label">Distrito</label>
                                                    <select id="ndj_distrito_actual" name="ndj_distrito_actual" class="dj-select">
                                                        <option value="" disabled selected>—</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <label class="dj-label">Descripción</label>
                                            <textarea id="ndj_direccion_actual" name="ndj_direccion_actual"
                                                class="dj-textarea" placeholder="Dirección actual completa"></textarea>
                                        </div>
                                        <div>
                                            <p class="dj-label" style="color:#9ca3af;margin-bottom:6px;font-size:10px;">— Dirección DNI</p>
                                            <div class="dj-grid-3" style="margin-bottom:8px;">
                                                <div>
                                                    <label class="dj-label">Departamento</label>
                                                    <select id="ndj_departamento_dni" name="ndj_departamento_dni" class="dj-select">
                                                        <option value="" disabled selected>—</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="dj-label">Provincia</label>
                                                    <select id="ndj_provincia_dni" name="ndj_provincia_dni" class="dj-select">
                                                        <option value="" disabled selected>—</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="dj-label">Distrito</label>
                                                    <select id="ndj_distrito_dni" name="ndj_distrito_dni" class="dj-select">
                                                        <option value="" disabled selected>—</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <label class="dj-label">Descripción</label>
                                            <textarea id="ndj_direccion_dni" name="ndj_direccion_dni"
                                                class="dj-textarea" placeholder="Dirección registrada en el DNI"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Emergencia --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-alarm-exclamation'></i>
                                    Contacto de Emergencia
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-3">
                                        <div>
                                            <label class="dj-label">Llamar a</label>
                                            <input type="text" id="ndj_contacto_emergencia" name="ndj_contacto_emergencia"
                                                class="dj-input" placeholder="Juan Pérez García">
                                        </div>
                                        <div>
                                            <label class="dj-label">Celular</label>
                                            <input type="text" id="ndj_celular_emergencia" name="ndj_celular_emergencia"
                                                class="dj-input" placeholder="999 999 999">
                                        </div>
                                        <div>
                                            <label class="dj-label">Parentesco</label>
                                            <select id="ndj_parentesco_emergencia" name="ndj_parentesco_emergencia" class="dj-select">
                                                <option value="">—</option>
                                                <option value="PADRE">Padre</option>
                                                <option value="MADRE">Madre</option>
                                                <option value="CONYUGE">Cónyuge</option>
                                                <option value="HIJO">Hijo(a)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>{{-- /① --}}

                    {{-- ══════════════════════════════════════
                         ② DATOS LABORALES
                         ══════════════════════════════════════ --}}
                    <div class="dj-group">
                        <div class="dj-group-header">② Datos Laborales</div>
                        <div class="dj-group-body">

                            {{-- Solo ADMINISTRATIVO --}}
                            <div class="dj-section" data-ndj-tipo="administrativo">
                                <div class="dj-section-header">
                                    <i class='bx bx-briefcase'></i>
                                    Información Profesional
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-3">
                                        <div>
                                            <label class="dj-label">Profesión / Ocupación Principal</label>
                                            <input type="text" id="ndj_ocupacion_principal" name="ndj_ocupacion_principal"
                                                class="dj-input" placeholder="Ej. Administrador"
                                                style="text-transform:uppercase;">
                                        </div>
                                        <div>
                                            <label class="dj-label">Experiencia (años)</label>
                                            <input type="number" id="ndj_experiencia_anios" name="ndj_experiencia_anios"
                                                class="dj-input" placeholder="0">
                                        </div>
                                        <div>
                                            <label class="dj-label">¿Familiar en la empresa?</label>
                                            <select id="ndj_familiar_empresa" name="ndj_familiar_empresa" class="dj-select">
                                                <option value="NO">No</option>
                                                <option value="SI">Sí</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div id="ndj_div_familiar_interno" class="dj-subpanel hidden">
                                        <div class="dj-grid-2">
                                            <div>
                                                <label class="dj-label">Nombre del familiar</label>
                                                <input type="text" id="ndj_familiar_nombre" name="ndj_familiar_nombre"
                                                    class="dj-input" placeholder="Nombre y apellidos"
                                                    style="text-transform:uppercase;">
                                            </div>
                                            <div>
                                                <label class="dj-label">Parentesco</label>
                                                <select id="ndj_familiar_parentesco" name="ndj_familiar_parentesco" class="dj-select">
                                                    <option value="" disabled selected>—</option>
                                                    <option value="PADRE">Padre</option>
                                                    <option value="MADRE">Madre</option>
                                                    <option value="CONYUGE">Cónyuge</option>
                                                    <option value="HIJO">Hijo(a)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Solo OPERATIVO --}}
                            <div class="dj-section" data-ndj-tipo="operativo">
                                <div class="dj-section-header">
                                    <i class='bx bx-shield'></i>
                                    Curso SUCAMEC
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-2">
                                        <div>
                                            <label class="dj-label">Curso SUCAMEC</label>
                                            <select id="ndj_curso_sucamec" name="ndj_curso_sucamec" class="dj-select">
                                                <option value="NO">No</option>
                                                <option value="SI">Sí</option>
                                            </select>
                                            <div id="ndj_div_sucamec_obs" class="hidden" style="margin-top:6px;">
                                                <label class="dj-label">Observación / N° Certificado</label>
                                                <input type="text" id="ndj_sucamec_obs" name="ndj_sucamec_obs"
                                                    class="dj-input" placeholder="Institución o certificado...">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- AMBOS --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-flag'></i>
                                    Servicio Militar Obligatorio
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-3">
                                        <div>
                                            <label class="dj-label">S.M.O.</label>
                                            {{-- name=ndj_consumo_sustancias para compatibilidad con backend --}}
                                            <select id="ndj_smo" name="ndj_consumo_sustancias" class="dj-select">
                                                <option value="" disabled selected>Seleccionar...</option>
                                                <option value="NO">NO</option>
                                                <option value="MG">MGP - MARINA DE GUERRA DEL PERU</option>
                                                <option value="EP">EP - EJERCITO DEL PERU</option>
                                                <option value="FA">FAP - FUERZA AEREA DEL PERU</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Solo OPERATIVO --}}
                            <div class="dj-section" data-ndj-tipo="operativo">
                                <div class="dj-section-header">
                                    <i class='bx bx-target-lock'></i>
                                    Licencia y Tipo de Arma
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-3">
                                        <div>
                                            <label class="dj-label">Licencia de Arma L4</label>
                                            <input type="text" id="ndj_licencia_arma" name="ndj_licencia_arma"
                                                class="dj-input" placeholder="Nº de licencia...">
                                        </div>
                                        <div>
                                            <label class="dj-label">Arma Propia</label>
                                            <select id="ndj_arma_propia" name="ndj_arma_propia" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                                <option value="SI">Sí</option>
                                                <option value="NO">No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- AMBOS --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-car'></i>
                                    Licencia de Conducir
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-4">
                                        <div>
                                            <label class="dj-label">N° Brevete</label>
                                            <input type="text" id="ndj_brevete" name="ndj_brevete"
                                                class="dj-input" placeholder="Número de brevete">
                                        </div>
                                        <div>
                                            <label class="dj-label">Clase</label>
                                            <select id="ndj_clase_brevete" name="ndj_clase_brevete" class="dj-select">
                                                <option value="">Seleccionar...</option>
                                                <option value="A">A</option>
                                                <option value="B">B</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Categoría</label>
                                            <select id="ndj_tipo_vehiculo" name="ndj_tipo_vehiculo" class="dj-select">
                                                <option value="">— Seleccionar clase primero —</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="dj-label">Vehículo Propio</label>
                                            <select id="ndj_vehiculo_propio" name="ndj_vehiculo_propio" class="dj-select">
                                                <option value="" disabled selected>—</option>
                                                <option value="SI">Sí</option>
                                                <option value="NO">No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- AMBOS --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-buildings'></i>
                                    Experiencia Laboral Anterior
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-3">
                                        <div>
                                            <label class="dj-label">Empresa Anterior</label>
                                            <input type="text" id="ndj_empresa_anterior" name="ndj_empresa_anterior"
                                                class="dj-input" placeholder="Nombre de la empresa">
                                        </div>
                                        <div>
                                            <label class="dj-label">Cargo</label>
                                            <input type="text" id="ndj_cargo_anterior" name="ndj_cargo_anterior"
                                                class="dj-input" placeholder="Cargo desempeñado">
                                        </div>
                                        <div>
                                            <label class="dj-label">Duración</label>
                                            <input type="text" id="ndj_duracion_anterior" name="ndj_duracion_anterior"
                                                class="dj-input" placeholder="Ej. 2 años">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- AMBOS --}}
                            <div class="dj-section">
                                <div class="dj-section-header">
                                    <i class='bx bx-list-ul'></i>
                                    Profesión u Ocupación Alterna
                                </div>
                                <div class="dj-section-body">
                                    <div class="dj-grid-2">
                                        <div>
                                            <label class="dj-label">Ocupación Alterna 1</label>
                                            <input type="text" id="ndj_laboral_1" name="ndj_dj2026_laboral_1"
                                                class="dj-input" placeholder="Ocupación alterna 1"
                                                style="text-transform:uppercase;">
                                        </div>
                                        <div>
                                            <label class="dj-label">Ocupación Alterna 2</label>
                                            <input type="text" id="ndj_laboral_2" name="ndj_dj2026_laboral_2"
                                                class="dj-input" placeholder="Ocupación alterna 2"
                                                style="text-transform:uppercase;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>{{-- /② --}}

                    {{-- ══════════════════════════════════════
                         ③ DATOS FAMILIARES
                         ══════════════════════════════════════ --}}
                    <div class="dj-group" style="margin-bottom:0;">
                        <div class="dj-group-header">③ Datos Familiares</div>
                        <div class="dj-group-body">
                            <div class="dj-section" style="margin-bottom:0;">
                                <div class="dj-section-header">
                                    <i class='bx bx-group'></i>
                                    Registros Familiares
                                </div>
                                <div class="dj-section-body">
                                    <div id="ndj_familyContainer" style="display:flex;flex-direction:column;gap:8px;">
                                        {{-- fila inicial --}}
                                        <div class="ndj-family-row"
                                            style="display:grid;grid-template-columns:1fr 2fr 1fr auto;gap:8px;align-items:end;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:8px 10px;">
                                            <div>
                                                <label class="dj-label">Parentesco</label>
                                                <select name="ndj_parentesco[]" class="dj-select">
                                                    <option value="" disabled selected>—</option>
                                                    <option value="PADRE">Padre</option>
                                                    <option value="MADRE">Madre</option>
                                                    <option value="CONYUGE">Cónyuge</option>
                                                    <option value="HIJO">Hijo(a)</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="dj-label">Apellidos y Nombres</label>
                                                <input type="text" name="ndj_apellidosNombres[]" class="dj-input"
                                                    placeholder="Apellidos y nombres completos">
                                            </div>
                                            <div>
                                                <label class="dj-label">Fecha de Nacimiento</label>
                                                <input type="date" name="ndj_fechaNacimiento[]" class="dj-input">
                                            </div>
                                            <div>
                                                <button type="button" class="ndj-remove-family dj-btn-sm dj-btn-danger"
                                                    style="margin-bottom:1px;">Eliminar</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div style="margin-top:8px;">
                                        <button id="ndj_addFamilyMember" type="button"
                                            style="width:100%;padding:6px;font-size:12px;border:1px dashed #d1d5db;border-radius:6px;background:#f9fafb;color:#6b7280;cursor:pointer;">
                                            + Agregar Familiar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>{{-- /③ --}}

                </form>
            </div>{{-- /body --}}

            {{-- ── Footer ─────────────────────────────────── --}}
            <div style="display:flex;justify-content:flex-end;gap:8px;padding:12px 20px;border-top:1px solid #e5e7eb;background:#fafafa;flex-shrink:0;">
                <button id="ndj_btnCerrar" type="button"
                    style="padding:7px 18px;font-size:12px;font-weight:600;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;cursor:pointer;">
                    Cerrar
                </button>
                @if(session('tipo_rol') != 9 && session('tipo_rol') != 8)
                <button id="ndj_btnGuardar" type="button"
                    style="padding:7px 18px;font-size:12px;font-weight:600;border-radius:6px;background:var(--color-primary,#6366f1);color:#fff;cursor:pointer;border:none;display:none;">
                    Guardar
                </button>
                @endif
            </div>

        </div>
    </div>
</div>