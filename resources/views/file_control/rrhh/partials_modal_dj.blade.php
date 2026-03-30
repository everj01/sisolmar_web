{{-- Partial: _modal_dj.blade.php --}}

<style>
#modalDjGestion .dj-input,
#modalDjGestion .dj-select,
#modalDjGestion .dj-textarea {
    width:100%; font-size:13px; padding:5px 10px;
    border:1px solid #d1d5db; border-radius:6px;
    background:#fff; color:#111827;
    transition:border-color .15s; box-sizing:border-box;
}
#modalDjGestion .dj-input:focus,
#modalDjGestion .dj-select:focus,
#modalDjGestion .dj-textarea:focus { outline:none; border-color:var(--color-primary,#6366f1); }
#modalDjGestion .dj-input::placeholder,
#modalDjGestion .dj-textarea::placeholder { color:#9ca3af; }
#modalDjGestion .dj-textarea { resize:vertical; min-height:56px; }
#modalDjGestion .dj-label {
    display:block; font-size:11px; font-weight:600;
    color:#6b7280; text-transform:uppercase; letter-spacing:.03em; margin-bottom:3px;
}
#modalDjGestion .dj-section {
    border:1px solid #e5e7eb; border-radius:8px; overflow:hidden; margin-bottom:12px;
}
#modalDjGestion .dj-section-header {
    display:flex; align-items:center; gap:6px; padding:7px 14px;
    background:#f9fafb; border-bottom:1px solid #e5e7eb;
    font-size:12px; font-weight:700; color:#374151;
    text-transform:uppercase; letter-spacing:.04em;
}
#modalDjGestion .dj-section-header svg { width:14px; height:14px; stroke:var(--color-primary,#6366f1); flex-shrink:0; }
#modalDjGestion .dj-section-body { padding:12px 14px; display:flex; flex-direction:column; gap:10px; }
#modalDjGestion .dj-divider { border:none; border-top:1px solid #f3f4f6; margin:4px 0; }
#modalDjGestion .dj-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
#modalDjGestion .dj-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; }
#modalDjGestion .dj-grid-4 { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:10px; }
@media(max-width:768px){
    #modalDjGestion .dj-grid-2,
    #modalDjGestion .dj-grid-3,
    #modalDjGestion .dj-grid-4{grid-template-columns:1fr;}
}
#modalDjGestion .dj-group { border-radius:8px; overflow:hidden; margin-bottom:16px; border:1px solid #e5e7eb; }
#modalDjGestion .dj-group-header {
    padding:8px 16px; font-size:11px; font-weight:700;
    letter-spacing:.06em; text-transform:uppercase; color:#fff;
    background:var(--color-primary,#6366f1);
}
#modalDjGestion .dj-group-body { padding:14px 16px; display:flex; flex-direction:column; gap:12px; background:#fff; }
#modalDjGestion .dj-foto-wrap {
    width:110px; height:130px; border:2px dashed #d1d5db; border-radius:6px;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    cursor:pointer; overflow:hidden; flex-shrink:0; position:relative; background:#f9fafb;
}
#modalDjGestion .dj-foto-wrap img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
#modalDjGestion .dj-table { width:100%; border-collapse:collapse; font-size:12px; }
#modalDjGestion .dj-table thead tr { border-bottom:1px solid #e5e7eb; }
#modalDjGestion .dj-table thead th { padding:5px 6px; font-size:10px; font-weight:700; color:#9ca3af; text-transform:uppercase; text-align:left; }
#modalDjGestion .dj-table tbody tr { border-bottom:1px solid #f3f4f6; }
#modalDjGestion .dj-table tbody td { padding:5px 6px; vertical-align:middle; }
#modalDjGestion .dj-subpanel { background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px; padding:10px 12px; margin-top:6px; }
#modalDjGestion .dj-btn-sm {
    font-size:11px; font-weight:600; padding:4px 12px; border-radius:20px;
    cursor:pointer; border:none; display:inline-flex; align-items:center; gap:4px;
    transition:background .15s,color .15s;
}
#modalDjGestion .dj-btn-primary { background:rgba(99,102,241,.15); color:var(--color-primary,#6366f1); }
#modalDjGestion .dj-btn-primary:hover { background:var(--color-primary,#6366f1); color:#fff; }
#modalDjGestion .dj-btn-danger { background:#fee2e2; color:#b91c1c; }
#modalDjGestion .dj-btn-danger:hover { background:#fca5a5; }
</style>

<div id="modalDjGestion"
    class="hs-overlay w-full h-full fixed top-0 left-0 z-70 transition-all duration-500 overflow-x-hidden overflow-y-auto hidden pointer-events-none">
    <div class="-translate-y-5 hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100 opacity-0 ease-in-out transition-all duration-500 sm:w-full my-8 sm:mx-auto flex flex-col bg-white shadow-sm rounded"
        style="width:92%;max-width:1100px;">
        <div class="flex flex-col border border-default-200 shadow-sm rounded-lg pointer-events-auto">

            {{-- Header --}}
            <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 18px;border-bottom:1px solid #e5e7eb;background:#fff;">
                <span style="font-size:13px;font-weight:700;color:#374151;letter-spacing:.02em;text-transform:uppercase;">Declaración Jurada</span>
                <button type="button" data-hs-overlay="#modalDjGestion"
                    style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;padding:2px 4px;"
                    onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#9ca3af'">&#x2715;</button>
            </div>

            {{-- Body --}}
            <div class="overflow-y-auto" style="padding:16px 20px;max-height:80vh;">
                <form id="formDatos" method="POST">
                @csrf
                <input type="hidden" name="cod_postulante" id="cod_postulante">

                {{-- ① DATOS PERSONALES --}}
                <div class="dj-group">
                    <div class="dj-group-header">① Datos Personales</div>
                    <div class="dj-group-body">

                        {{-- CARD: Identidad + Datos Personales (FUSIONADAS) --}}
                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A9 9 0 1118.879 6.196 9 9 0 015.121 17.804zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Identidad y Datos Personales
                            </div>
                            <div class="dj-section-body">
                                <div style="display:flex;gap:14px;align-items:flex-start;">
                                    <div style="flex:1;">
                                              <input type="hidden" id="nombres_apellidos" name="nombres_apellidos" class="dj-input" placeholder="Ingrese nombres y apellidos completos">

                                        {{-- ✅ CAMPOS SEPARADOS: NOMBRES Y APELLIDOS --}}
                                        <div class="dj-grid-2" style="margin-bottom:10px;">
                                            <div>
                                                <label class="dj-label">Primer Nombre</label>
                                                <input type="text" id="nombre1" name="nombre1" class="dj-input" placeholder="Primer nombre" style="text-transform:uppercase;">
                                            </div>
                                            <div>
                                                <label class="dj-label">Segundo Nombre</label>
                                                <input type="text" id="nombre2" name="nombre2" class="dj-input" placeholder="Segundo nombre (opcional)" style="text-transform:uppercase;">
                                            </div>
                                            <div>
                                                <label class="dj-label">Apellido Paterno</label>
                                                <input type="text" id="apellido_paterno" name="apellido_paterno" class="dj-input" placeholder="Apellido paterno" style="text-transform:uppercase;">
                                            </div>
                                            <div>
                                                <label class="dj-label">Apellido Materno</label>
                                                <input type="text" id="apellido_materno" name="apellido_materno" class="dj-input" placeholder="Apellido materno" style="text-transform:uppercase;">
                                            </div>
                                        </div>
                                        <a href="https://eldni.com/pe/buscar-datos-por-dni" target="_blank"
                                            style="display:inline-block;margin-top:6px;font-size:11px;color:var(--color-primary,#6366f1);border:1px solid var(--color-primary,#6366f1);padding:3px 10px;border-radius:5px;text-decoration:none;">
                                            Consultar DNI
                                        </a>
                                    </div>
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:6px;">
                                        <div class="dj-foto-wrap" id="placeholderFoto">
                                            <svg style="width:28px;height:28px;stroke:#d1d5db;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7a4 4 0 014-4h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7z"/></svg>
                                            <span style="font-size:10px;color:#9ca3af;margin-top:3px;">FOTO</span>
                                            <img id="previewFoto" class="hidden"/>
                                            <input type="file" id="inputFoto" accept="image/*" class="hidden"/>
                                        </div>
                                        <div style="display:flex;gap:5px;">
                                            <button type="button" id="btnSubirFoto" class="dj-btn-sm" style="background:#f3f4f6;color:#374151;border-radius:5px;">Subir foto</button>
                                            <button type="button" id="btnEliminarFoto" class="dj-btn-sm dj-btn-danger hidden">Quitar</button>
                                        </div>
                                    </div>
                                </div>
                                <hr class="dj-divider">
                                <div class="dj-grid-4">
                                    <div><label class="dj-label">DNI</label><input type="text" id="dni" name="dni" class="dj-input" placeholder="12345678"></div>
                                    <div><label class="dj-label">Caduca</label><input type="date" id="caduca" name="caduca" class="dj-input"></div>
                                    <div>
                                        <label class="dj-label">Estado Civil</label>
                                        <select id="estado_civil" name="estado_civil" class="dj-select">
                                            <option value="">—</option>
                                            <option value="2007000001">Soltero(a)</option>
                                            <option value="2007000002">Casado(a)</option>
                                            <option value="2007000003">Divorciado(a)</option>
                                            <option value="2007000004">Viudo(a)</option>
                                            <option value="2007000008">Conviviente</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="dj-label">Sexo</label>
                                        <select id="sexo" name="sexo" class="dj-select">
                                            <option value="">—</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                        </select>
                                    </div>
                                    <div><label class="dj-label">Fecha de Nacimiento</label><input type="date" id="fecha_nacimiento" name="fecha_nacimiento" class="dj-input"></div>
                                    <div><label class="dj-label">Ciudad de Nacimiento</label><input type="text" id="ciudad_nacimiento" name="ciudad_nacimiento" class="dj-input" placeholder="Lima, Arequipa…" style="text-transform:uppercase;"></div>
                                    <div>
                                        <label class="dj-label">¿Sabe nadar?</label>
                                        <select id="sabe_nadar" name="sabe_nadar" class="dj-select">
                                            <option value="">—</option>
                                            <option value="SI">Sí</option>
                                            <option value="NO">No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD: Contacto --}}
                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.372 4.115a1 1 0 01-.21.979l-2.073 2.073a11.05 11.05 0 005.293 5.293l2.073-2.073a1 1 0 01.979-.21l4.115 1.372a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                Información de Contacto
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-3">
                                    <div><label class="dj-label">Celular</label><input type="text" id="celular" name="celular" class="dj-input" placeholder="999 999 999"></div>
                                    <div><label class="dj-label">Correo electrónico</label><input type="email" id="correo" name="correo" class="dj-input" placeholder="ejemplo@correo.com"></div>
                                    <div><label class="dj-label">WhatsApp</label><input type="text" id="whatsapp" name="whatsapp" class="dj-input" placeholder="999 999 999"></div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD: Médica --}}
                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m-4-4h8m7 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Información Médica
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-3">
                                    <div>
                                        <label class="dj-label">Tipo de Sangre</label>
                                        <select id="tipo_sangre" name="tipo_sangre" class="dj-select">
                                            <option value="">—</option>
                                            <option>O+</option><option>O-</option><option>A+</option><option>A-</option>
                                            <option>B+</option><option>B-</option><option>AB+</option><option>AB-</option>
                                        </select>
                                    </div>
                                    <div><label class="dj-label">Peso (kg)</label><input type="number" id="peso" name="peso" step="0.1" class="dj-input" placeholder="70"></div>
                                    <div><label class="dj-label">Talla (m)</label><input type="number" id="talla" name="talla" step="0.01" class="dj-input" placeholder="1.75"></div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD: Previsional --}}
                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 1.343-3 3v7h6v-7c0-1.657-1.343-3-3-3zM5 13h14M5 17h14M9 21h6"/></svg>
                                Información Previsional
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-3">
                                    <div>
                                        <label class="dj-label">Sistema Previsional</label>
                                        <select id="sistema_previsional" name="sistema_previsional" class="dj-select">
                                            <option value="">—</option>
                                            <option value="01">ONP</option><option value="03">AFP</option>
                                            <option value="A1">AFP - Mixta</option><option value="A2">AFP - Flujo</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="dj-label">ESSALUD Vida</label>
                                        <select id="essalud" name="essalud" class="dj-select">
                                            <option value="">—</option><option value="SI">Sí</option><option value="NO">No</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="dj-label">Pensionista</label>
                                        <select id="pensionista" name="pensionista" class="dj-select">
                                            <option value="">—</option><option value="SI">Sí</option><option value="NO">No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD: Educación --}}
                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m0 0a9 9 0 11-9-9"/></svg>
                                Educación
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-4">
                                    <div>
                                        <label class="dj-label">Grado de Instrucción</label>
                                        <select id="grado_instruccion" name="grado_instruccion" class="dj-select">
                                            <option value="">—</option>
                                            @foreach ($grados as $grado)
                                                <option value="{{ $grado->id }}">{{ $grado->text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="dj-label">Institución</label>
                                        <select id="institucion" name="institucion" class="dj-select">
                                            <option value="">—</option>
                                            @foreach ($instituciones as $inst)
                                                <option value="{{ $inst->id }}">{{ $inst->text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="dj-label">Carrera</label>
                                        <select id="carrera" name="carrera" class="dj-select">
                                            <option value="">—</option>
                                            @foreach ($carreras as $carrera)
                                                <option value="{{ $carrera->id }}">{{ $carrera->text }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div><label class="dj-label">Año de egreso</label><input type="number" id="anio_egreso" name="anio_egreso" class="dj-input" placeholder="2020"></div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD: Información Adicional --}}
                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                                Información Adicional
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-3">
                                    <div>
                                        <label class="dj-label">Embargos financieros</label>
                                        <select id="embargos" name="embargos" class="dj-select">
                                            <option value="">—</option><option value="SI">Sí</option><option value="NO">No</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="dj-label">Consumo de sustancias ilícitas</label>
                                        <select id="consumo_sustancias" name="consumo_sustancias" class="dj-select">
                                            <option value="">—</option><option value="SI">Sí</option><option value="NO">No</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="dj-label">Cuenta de Sueldo</label>
                                        <select id="cuenta_banco" name="cuenta_banco" class="dj-select">
                                            <option value="">—</option>
                                            <option value="NO">No</option><option value="BCP">BCP</option><option value="INTERBANK">INTERBANK</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD: Direcciones (Dir.Actual + Dir.DNI FUSIONADAS) --}}
                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.657 0 3-1.343 3-3S13.657 5 12 5 9 6.343 9 8s1.343 3 3 3zm0 0c-4.418 0-8 3.582-8 8a8 8 0 0016 0c0-4.418-3.582-8-8-8z"/></svg>
                                Direcciones
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-2">
                                    <div>
                                        <p class="dj-label" style="color:#9ca3af;margin-bottom:6px;font-size:10px;">— Dirección Actual</p>
                                        <div class="dj-grid-3" style="margin-bottom:8px;">
                                            <div><label class="dj-label">Departamento</label><select id="departamento_actual" name="departamento_actual" class="dj-select"><option value="">—</option></select></div>
                                            <div><label class="dj-label">Provincia</label><select id="provincia_actual" name="provincia_actual" class="dj-select"><option value="">—</option></select></div>
                                            <div><label class="dj-label">Distrito</label><select id="distrito_actual" name="distrito_actual" class="dj-select"><option value="">—</option></select></div>
                                        </div>
                                        <label class="dj-label">Descripción</label>
                                        <textarea id="direccion_actual" name="direccion_actual" class="dj-textarea" placeholder="Dirección actual completa"></textarea>
                                    </div>
                                    <div>
                                        <p class="dj-label" style="color:#9ca3af;margin-bottom:6px;font-size:10px;">— Dirección DNI</p>
                                        <div class="dj-grid-3" style="margin-bottom:8px;">
                                            <div><label class="dj-label">Departamento</label><select id="departamento_dni" name="departamento_dni" class="dj-select"><option value="">—</option></select></div>
                                            <div><label class="dj-label">Provincia</label><select id="provincia_dni" name="provincia_dni" class="dj-select"><option value="">—</option></select></div>
                                            <div><label class="dj-label">Distrito</label><select id="distrito_dni" name="distrito_dni" class="dj-select"><option value="">—</option></select></div>
                                        </div>
                                        <label class="dj-label">Descripción</label>
                                        <textarea id="direccion_dni" name="direccion_dni" class="dj-textarea" placeholder="Dirección registrada en el DNI"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- CARD: Contacto de Emergencia --}}
                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M12 2a10 10 0 100 20 10 10 0 000-20z"/></svg>
                                Contacto de Emergencia
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-3">
                                    <div><label class="dj-label">Llamar a</label><input type="text" id="contacto_emergencia" name="contacto_emergencia" class="dj-input" placeholder="Juan Pérez García"></div>
                                    <div><label class="dj-label">Celular</label><input type="text" id="celular_emergencia" name="celular_emergencia" class="dj-input" placeholder="999 999 999"></div>
                                    <div><label class="dj-label">Parentesco</label><input type="text" id="parentesco_emergencia" name="parentesco_emergencia" class="dj-input" placeholder="Madre, Hermano…"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>{{-- /① --}}

                {{-- ② DATOS LABORALES --}}
                <div class="dj-group">
                    <div class="dj-group-header">② Datos Laborales</div>
                    <div class="dj-group-body">

                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                Información Profesional
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-3">
                                    <div><label class="dj-label">Profesión / Ocupación Principal</label><input type="text" id="ocupacion_principal" name="ocupacion_principal" class="dj-input" placeholder="Ej. Agente de Seguridad" style="text-transform:uppercase;"></div>
                                    <div><label class="dj-label">Experiencia (años)</label><input type="number" id="experiencia_anios" name="experiencia_anios" class="dj-input" placeholder="0"></div>
                                    <div>
                                        <label class="dj-label">¿Familiar en la empresa?</label>
                                        <select id="familiar_empresa" name="familiar_empresa" class="dj-select">
                                            <option value="NO">No</option><option value="SI">Sí</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="div_familiar_interno" class="dj-subpanel hidden">
                                    <div class="dj-grid-2">
                                        <div><label class="dj-label">Nombre del familiar</label><input type="text" id="familiar_nombre" name="familiar_nombre" class="dj-input" placeholder="Nombre y apellidos" style="text-transform:uppercase;"></div>
                                        <div>
                                            <label class="dj-label">Parentesco</label>
                                            <select id="familiar_parentesco" name="familiar_parentesco" class="dj-select">
                                                <option value="">—</option>
                                                <option value="MADRE">Madre</option><option value="PADRE">Padre</option>
                                                <option value="CONYUGE">Cónyuge / Pareja</option><option value="HIJO">Hijo(a)</option>
                                                <option value="HERMANO">Hermano(a)</option><option value="OTROS">Otros</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m0 0a9 9 0 11-9-9"/></svg>
                                Curso SUCAMEC y Servicio Militar
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-3">
                                    <div>
                                        <label class="dj-label">Curso SUCAMEC</label>
                                        <select id="curso_sucamec" name="curso_sucamec" class="dj-select">
                                            <option value="NO">No</option><option value="SI">Sí</option>
                                        </select>
                                        <div id="div_sucamec_obs" class="hidden" style="margin-top:6px;">
                                            <label class="dj-label">Observación</label>
                                            <input type="text" id="sucamec_obs" name="sucamec_obs" class="dj-input" placeholder="Institución o curso...">
                                        </div>
                                    </div>
                                    <div id="institucion_container" class="hidden">
                                        <label class="dj-label">Institución</label>
                                        <input type="text" id="institucion_laboral" name="institucion_laboral" class="dj-input" placeholder="Institución donde realizó el curso">
                                    </div>
                                    <div>
                                        <label class="dj-label">S.M.O.</label>
                                        <select id="smo" name="smo" class="dj-select">
                                            <option value="">—</option><option value="NO">No</option>
                                            <option value="MARINA">Marina</option><option value="EJERCITO">Ejército</option><option value="AVIACION">Aviación</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13l6-6m0 0l6 6m-6-6v12"/></svg>
                                Licencia y Tipo de Arma
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-3">
                                    <div><label class="dj-label">Licencia de Arma</label><input id="licencia_arma" name="licencia_arma" class="dj-input" placeholder="Nº de licencia..."></div>
                                    <div>
                                        <label class="dj-label">Tipo de Arma</label>
                                        <select id="tipo_arma" name="tipo_arma" class="dj-select">
                                            <option value="">—</option><option value="PISTOLA">Pistola</option>
                                            <option value="REVOLVER">Revólver</option><option value="ESCOPETA">Escopeta</option><option value="RIFLE">Rifle</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="dj-label">Arma Propia</label>
                                        <select id="arma_propia" name="arma_propia" class="dj-select">
                                            <option value="">—</option><option value="SI">Sí</option><option value="NO">No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 13l2-5h14l2 5M5 13v5h2v-2h10v2h2v-5M5 13h14"/></svg>
                                Licencia de Conducir
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-4">
                                    <div><label class="dj-label">N° Brevete</label><input type="text" id="brevete" name="brevete" class="dj-input" placeholder="Número de brevete"></div>
                                    <div><label class="dj-label">Clase</label><input type="text" id="clase_brevete" name="clase_brevete" class="dj-input" placeholder="Ej: A-I, B-IIb"></div>
                                    <div><label class="dj-label">Tipo de Vehículo</label><input type="text" id="tipo_vehiculo" name="tipo_vehiculo" class="dj-input" placeholder="Tipo de vehículo"></div>
                                    <div>
                                        <label class="dj-label">Vehículo Propio</label>
                                        <select id="vehiculo_propio" name="vehiculo_propio" class="dj-select">
                                            <option value="">—</option><option value="SI">Sí</option><option value="NO">No</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7V4h6v3m-9 4h12v9H6V11z"/></svg>
                                Experiencia Laboral
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-3">
                                    <div><label class="dj-label">Empresa Anterior</label><input type="text" id="empresa_anterior" name="empresa_anterior" class="dj-input" placeholder="Nombre de la empresa"></div>
                                    <div><label class="dj-label">Cargo</label><input type="text" id="cargo_anterior" name="cargo_anterior" class="dj-input" placeholder="Cargo"></div>
                                    <div><label class="dj-label">Duración (años)</label><input type="number" id="duracion_anterior" name="duracion_anterior" step="0.5" class="dj-input" placeholder="2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="dj-section">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                                </svg>
                                Profesión u Ocupación Alterna
                            </div>
                            <div class="dj-section-body">
                                <div class="dj-grid-2">
                                    <div>
                                        <label class="dj-label">Ocupación Alterna 1</label>
                                        <input type="text" id="dj2026_laboral_1" name="dj2026_laboral_1" 
                                            class="dj-input" placeholder="Descripción ocupación alterna 1"
                                            style="text-transform:uppercase;">
                                    </div>
                                    <div>
                                        <label class="dj-label">Ocupación Alterna 2</label>
                                        <input type="text" id="dj2026_laboral_2" name="dj2026_laboral_2" 
                                            class="dj-input" placeholder="Descripción ocupación alterna 2"
                                            style="text-transform:uppercase;">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>{{-- /② --}}

                {{-- ③ DATOS FAMILIARES --}}
                <div class="dj-group" style="margin-bottom:0;">
                    <div class="dj-group-header">③ Datos Familiares</div>
                    <div class="dj-group-body">
                        <div class="dj-section" style="margin-bottom:0;">
                            <div class="dj-section-header">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zM12 14v7m-7-7a9 9 0 0118 0v7H5v-7z"/></svg>
                                Registros Familiares
                            </div>
                            <div class="dj-section-body">
                                <div id="familyContainer" style="display:flex;flex-direction:column;gap:8px;">
                                    <div class="family-row" style="display:grid;grid-template-columns:1fr 2fr 1fr auto;gap:8px;align-items:end;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:8px 10px;">
                                        <div>
                                            <label class="dj-label">Parentesco</label>
                                            <select name="parentesco[]" class="dj-select">
                                                <option value="">—</option>
                                                <option value="PADRE">Padre</option><option value="MADRE">Madre</option>
                                                <option value="ESPOSO">Esposo</option><option value="ESPOSA">Esposa</option>
                                                <option value="HIJO">Hijo</option><option value="HIJA">Hija</option>
                                                <option value="HERMANO">Hermano</option><option value="HERMANA">Hermana</option>
                                                <option value="ABUELO">Abuelo</option><option value="ABUELA">Abuela</option>
                                            </select>
                                        </div>
                                        <div><label class="dj-label">Apellidos y Nombres</label><input type="text" name="apellidosNombres[]" class="dj-input" placeholder="Apellidos y nombres completos"></div>
                                        <div><label class="dj-label">Fecha de Nacimiento</label><input type="date" name="fechaNacimiento[]" class="dj-input"></div>
                                        <div><button type="button" class="remove-family dj-btn-sm dj-btn-danger" style="margin-bottom:1px;">Eliminar</button></div>
                                    </div>
                                </div>
                                <div style="margin-top:8px;">
                                    <button id="addFamilyMember" type="button"
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

            {{-- Footer --}}
            <div style="display:flex;justify-content:flex-end;gap:8px;padding:12px 20px;border-top:1px solid #e5e7eb;background:#fafafa;">
                <button id="cerrarModal" type="button" data-hs-overlay="#modalDjGestion"
                    style="padding:7px 18px;font-size:12px;font-weight:600;border:1px solid #d1d5db;border-radius:6px;background:#fff;color:#374151;cursor:pointer;">
                    Cancelar
                </button>
                <button id="btnPrevisualizar" type="button"
                    style="padding:7px 18px;font-size:12px;font-weight:600;border-radius:6px;background:#64748b;color:#fff;cursor:pointer;border:none;">
                    Previsualizar PDF
                </button>
                <button id="btnGuardar" type="submit" form="formDatos"  data-hs-overlay="#modalDjGestion"
                    style="padding:7px 18px;font-size:12px;font-weight:600;border-radius:6px;background:var(--color-primary,#6366f1);color:#fff;cursor:pointer;border:none;">
                    Guardar
                </button>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('familiar_empresa')?.addEventListener('change', function () {
        document.getElementById('div_familiar_interno').classList.toggle('hidden', this.value !== 'SI');
    });
    document.getElementById('curso_sucamec')?.addEventListener('change', function () {
        document.getElementById('div_sucamec_obs').classList.toggle('hidden', this.value !== 'SI');
    });
    // document.getElementById('btnAddOcupAlterna')?.addEventListener('click', function () {
    //     const tbody = document.getElementById('bodyOcupAlterna');
    //     const tr = document.createElement('tr');
    //     tr.innerHTML = `
    //         <td style="padding:4px 6px;">
    //             <input type="text" name="ocupacion_alterna[]" class="dj-input"
    //                 style="text-transform:uppercase;" placeholder="Descripción de la ocupación alterna">
    //         </td>
    //         <td style="padding:4px 6px;text-align:right;">
    //             <button type="button" class="btn-remove-ocup dj-btn-sm dj-btn-danger">Eliminar</button>
    //         </td>`;
    //     tbody.appendChild(tr);
    //     tr.querySelector('.btn-remove-ocup').addEventListener('click', () => tr.remove());
    // });
});
</script>