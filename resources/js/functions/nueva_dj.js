import Swal from 'sweetalert2';
(function () {
    'use strict';

    const $ = id => document.getElementById(id);

    const docInput = $('ndj_nro_documento');
    const tipoDocInput = $('ndj_tipo_documento');
    const btnGuardar = $('ndj_btnGuardar');

    let dniValido = false;
    let coincidenciasValidadas = false;
    let hayCoincidencias = false;


    const nombre1 = $('ndj_nombre1');
    const nombre2 = $('ndj_nombre2');
    const apellido1 = $('ndj_apellido_paterno');
    const apellido2 = $('ndj_apellido_materno');
    let coincidenciasDiv = document.createElement('div');
    coincidenciasDiv.style.fontSize = '12px';
    coincidenciasDiv.style.marginTop = '4px';
    apellido2.parentNode.appendChild(coincidenciasDiv);

    let docErrorMsg = document.createElement('div');
    docErrorMsg.style.color = '#ef4444';
    docErrorMsg.style.fontSize = '12px';
    docErrorMsg.style.marginTop = '2px';
    docInput.parentNode.appendChild(docErrorMsg);

    const alertTipoPersonal = $('ndj_alert_tipo_personal');
    const tipoPersonalSelect = $('ndj_sel_tipo_personal');

    // ── Categorías brevete ───────────────────────────────────
    const NDJ_CAT = {
        'A': [
            { val: 'A-I', text: 'A-I: Particulares' },
            { val: 'A-IIa', text: 'A-IIa: Taxi / Ambulancia' },
            { val: 'A-IIb', text: 'A-IIb: Microbús / Pickup' },
            { val: 'A-IIIa', text: 'A-IIIa: Ómnibus' },
            { val: 'A-IIIb', text: 'A-IIIb: Camiones' },
            { val: 'A-IIIc', text: 'A-IIIc: Todos los anteriores' },
        ],
        'B': [
            { val: 'B-IIa', text: 'B-IIa: Bicimotos' },
            { val: 'B-IIb', text: 'B-IIb: Motocicletas' },
            { val: 'B-IIc', text: 'B-IIc: Mototaxis' },
        ],
    };



    // ── Caché local de educación ─────────────────────────────
    // grados e instituciones son independientes entre sí.
    // carreras se filtra por institución (IEDU_CODIGO === institucion.id)
    let ndj_allGrados = [];
    let ndj_allInstituciones = [];
    let ndj_allCarreras = [];
    let ndj_catalogoListo = false;

    let ndj_abriendo = false;

    // ── Caché ubigeos ─────────────────────────────────────────
    const ndj_ubigeoCache = new Map();

    // ============================================================
    // ABRIR
    // ============================================================
    async function ndj_abrir() {
        if (ndj_abriendo) return;
        ndj_abriendo = true;

        const modal = $('modalNuevaDJ');
        if (!modal) { ndj_abriendo = false; return; }

        // Limpia el modal antes de abrir
        ndj_reset();

        // Solo carga catálogos si no están listos
        const necesitaCarga = !ndj_catalogoListo;
        if (necesitaCarga && typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Preparando formulario...',
                html: '<span style="font-size:13px;color:#6b7280;">Cargando catálogos y ubigeos</span>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading(),
            });
        }

        try {
            if (necesitaCarga) {
                await Promise.all([
                    ndj_cargarTipoDoc(),
                    ndj_cargarTipoPer(),
                    ndj_cargarEstadoCivil(),
                    ndj_cargarSistemaPrev(),
                    ndj_cargarDepartamentos(),
                    ndj_cargarEducacion(),
                ]);
            }
        } catch (e) {
            console.error('[NuevaDJ] Error al cargar catálogos:', e);
        } finally {
            if (necesitaCarga && typeof Swal !== 'undefined') {
                Swal.close();
                await new Promise(r => setTimeout(r, 80));
            }
            ndj_abriendo = false;
        }

        if (window.HSOverlay) HSOverlay.open(modal);
        else modal.classList.remove('hidden');
    }

    function actualizarEstadoGuardar() {
        if (dniValido && (coincidenciasValidadas || !hayCoincidencias)) {
            btnGuardar.disabled = false;
            btnGuardar.style.display = 'block';
        } else {
            btnGuardar.disabled = true;
            btnGuardar.style.display = 'none';
        }
    }

    async function buscarCoincidencias() {
        const n1 = nombre1.value.trim();
        const n2 = nombre2.value.trim();
        const a1 = apellido1.value.trim();
        const a2 = apellido2.value.trim();

        if ((!n1 && !n2 && !a1 && !a2) || (n1.length == 0 || a1.length == 0)) {
            coincidenciasDiv.innerHTML = '';
            hayCoincidencias = false;
            coincidenciasValidadas = true;
            actualizarEstadoGuardar();
            return;
        }

        try {
            const res = await fetch(`${VITE_URL_APP}/api/dj/buscar-coincidencias?nomb1=${encodeURIComponent(n1)}&nomb2=${encodeURIComponent(n2)}&apel1=${encodeURIComponent(a1)}&apel2=${encodeURIComponent(a2)}`);
            const data = await res.json();

            if (data.data.length === 0) {
                hayCoincidencias = false;
                coincidenciasValidadas = true;
                coincidenciasDiv.innerHTML = '';
                actualizarEstadoGuardar();
                return;
            }

            // Mostrar coincidencias y botón "Entendido"
            hayCoincidencias = true;
            coincidenciasValidadas = false;
            coincidenciasDiv.innerHTML = `
                <b>Coincidencias encontradas:</b>
                <ul style="list-style:none;padding:0;">
                    ${data.data.map((p, idx) => `
                        <li style="padding-bottom:8px;">
                            ${p.NOMB_1} ${p.NOMB_2} ${p.APEL_1} ${p.APEL_2} - ${p.NRO_DOCU_IDEN}
                            <br>
                            <span class="text-danger">${p.PERS_VIGENCIA != 'SI' ? '(NO VIGENTE)' : ''}</span>
                            ${idx < data.data.length - 1 ? '<hr style="margin:8px 0;border:0;border-top:1px solid #e5e7eb;">' : ''}
                        </li>
                    `).join('')}
                </ul>
                <button id="ndj_btnEntendido" type="button" style="margin-top:8px;padding:6px 18px;font-size:13px;font-weight:600;border-radius:6px;background:#fbbf24;color:#fff;cursor:pointer;border:none;">
                    Entendido, deseo continuar
                </button>
            `;

            document.getElementById('ndj_btnEntendido')?.addEventListener('click', function () {
                coincidenciasValidadas = true;
                coincidenciasDiv.innerHTML = '';
                actualizarEstadoGuardar();
            });

            actualizarEstadoGuardar();

        } catch (e) {
            coincidenciasDiv.innerHTML = 'Error buscando coincidencias.';
            hayCoincidencias = false;
            coincidenciasValidadas = false;
            actualizarEstadoGuardar();
        }
    }

    function ndj_bloquearCampos(bloquear) {
        // Selecciona todos los campos menos el tipo de personal
        document.querySelectorAll('#modalNuevaDJ input:not(#ndj_sel_tipo_personal), #modalNuevaDJ select:not(#ndj_sel_tipo_personal), #modalNuevaDJ textarea')
            .forEach(el => el.disabled = bloquear);
        // También puedes deshabilitar el botón guardar
        btnGuardar.disabled = bloquear;
        if (bloquear) btnGuardar.style.display = 'none';
    }

    // ============================================================
    // CERRAR
    // ============================================================
    function ndj_cerrar() {
        ndj_abriendo = false;
        const modal = $('modalNuevaDJ');
        if (!modal) return;

        // Dejar que HSOverlay maneje su propio estado
        if (window.HSOverlay) {
            try { HSOverlay.close(modal); } catch (e) { }
        }

        // Limpiar después de un tick, no inmediatamente
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('hs-overlay-open');
            document.querySelectorAll('.hs-overlay-backdrop').forEach(el => el.remove());
            document.body.classList.remove('overflow-hidden');
            document.body.style.overflow = '';
            ndj_reset();
        }, 300); // coincidir con la duración de la transición del modal (duration-500 → usa 300 mínimo)
    }

    // ============================================================
    // RESET
    // ============================================================
    function ndj_reset() {
        $('formNuevaDJ')?.reset();

        const badge = $('ndj_tipo_badge');
        if (badge) badge.style.display = 'none';

        document.querySelectorAll('#modalNuevaDJ [data-ndj-tipo]')
            .forEach(el => { el.style.display = ''; });

        $('ndj_div_familiar_interno')?.classList.add('hidden');
        $('ndj_div_sucamec_obs')?.classList.add('hidden');

        ndj_limpiarFoto();

        // Limpiar selects dependientes de ubigeo
        ['ndj_provincia_actual', 'ndj_distrito_actual',
            'ndj_provincia_dni', 'ndj_distrito_dni',
            'ndj_provincia_nac', 'ndj_distrito_nac'].forEach(id => {
                const s = $(id);
                if (s) s.innerHTML = '<option value="">—</option>';
            });

        // Familiares: una fila vacía
        const fc = $('ndj_familyContainer');
        if (fc) { fc.innerHTML = ''; fc.appendChild(ndj_crearFila()); }
    }

    // ============================================================
    // HELPER — puebla un select desde una URL
    // Soporta respuesta directa [] o { data: [] }
    // ============================================================
    async function ndj_fetchSelect(selectId, url, valorKey, textoKey, placeholder = '— Seleccionar —') {
        const sel = $(selectId);
        if (!sel) return;
        sel.innerHTML = `<option value="">Cargando...</option>`;
        sel.disabled = true;
        try {
            const res = await fetch(url);
            const json = await res.json();
            const items = Array.isArray(json) ? json : (json.data ?? []);
            sel.innerHTML = `<option value="">${placeholder}</option>`;
            items.forEach(item => {
                const o = document.createElement('option');
                o.value = item[valorKey]; o.textContent = item[textoKey];
                sel.appendChild(o);
            });
        } catch (err) {
            console.error(`[NuevaDJ] Error cargando ${selectId}:`, err);
            sel.innerHTML = `<option value="">Error al cargar</option>`;
        } finally {
            sel.disabled = false;
        }
    }

    // ============================================================
    // CATÁLOGOS DESDE API
    // ============================================================

    function ndj_cargarTipoDoc() {
        return ndj_fetchSelect('ndj_tipo_documento',
            `${VITE_URL_APP}/api/dj/get-tipo-doc/`, 'codigo', 'nombre');
    }


    function alertaTipoPersonal() {
        Swal.fire({
            icon: 'warning',
            title: 'Seleccione el tipo de personal',
            text: 'Debe seleccionar un tipo de personal antes de continuar.',
            confirmButtonText: 'Entendido'
        });
    }

    function bindAlertaCamposBloqueados() {
        document.querySelectorAll('#modalNuevaDJ input:not(#ndj_sel_tipo_personal), #modalNuevaDJ select:not(#ndj_sel_tipo_personal), #modalNuevaDJ textarea')
            .forEach(el => {
                el.addEventListener('focus', function handler(e) {
                    if (el.disabled) {
                        alertaTipoPersonal();

                        el.blur();
                    }
                });
            });
    }

    function ndj_cargarTipoPer() {
        return ndj_fetchSelect('ndj_sel_tipo_personal',
            `${VITE_URL_APP}/api/dj/get-tipo-per/`, 'codigo', 'nombre');
    }

    // Endpoint sugerido: GET /api/dj/get-estado-civil/
    // Controlador: DjController@getEstadoCivil
    // Estructura: { data: [{ codigo, nombre }] }
    function ndj_cargarEstadoCivil() {
        return ndj_fetchSelect('ndj_estado_civil',
            `${VITE_URL_APP}/api/dj/get-estado-civil/`, 'codigo', 'nombre');
    }

    // Endpoint sugerido: GET /api/dj/get-sistema-prev/
    // Controlador: DjController@getSistemaPrev
    // Estructura: { data: [{ codigo, nombre }] }
    function ndj_cargarSistemaPrev() {
        return ndj_fetchSelect('ndj_sistema_previsional',
            `${VITE_URL_APP}/api/dj/get-sistema-prev/`, 'codigo', 'nombre');
    }

    // ============================================================
    // EDUCACIÓN
    // Estructura de /api/dj/get-catalogs:
    //   grados:       [{ id, text }]                 — independiente
    //   instituciones:[{ id, text }]                 — independiente
    //   carreras:     [{ id, text, IEDU_CODIGO }]    — se filtra por institución
    //
    // Flujo:
    //   Grado      → campo independiente (no filtra nada)
    //   Institución → filtra Carrera (IEDU_CODIGO === institucion.id)
    // ============================================================
    async function ndj_cargarEducacion() {
        if (ndj_catalogoListo) {
            // Si ya cargó, solo repoblar los selects con los datos en caché
            ndj_poblarGrados();
            ndj_poblarInstituciones();
            ndj_poblarCarreras(''); // todas, sin filtro
            return;
        }
        try {
            const res = await fetch(`${VITE_URL_APP}/api/dj/get-catalogs`);
            const data = await res.json();

            ndj_allGrados = data.grados || [];
            ndj_allInstituciones = data.instituciones || [];
            ndj_allCarreras = data.carreras || [];

            ndj_poblarGrados();
            ndj_poblarInstituciones();
            ndj_poblarCarreras(''); // todas sin filtro al inicio

            ndj_catalogoListo = true;

        } catch (e) {
            console.error('[NuevaDJ] Error cargando catálogos educación:', e);
        }
    }

    // Pobla el select de grado
    function ndj_poblarGrados() {
        const sel = $('ndj_grado_instruccion');
        if (!sel) return;
        sel.innerHTML = '<option value="" disabled selected>—</option>';
        ndj_allGrados.forEach(g => {
            const o = document.createElement('option');
            o.value = g.id; o.textContent = g.text;
            sel.appendChild(o);
        });
    }

    // Pobla el select de institución (lista completa, independiente del grado)
    function ndj_poblarInstituciones() {
        const sel = $('ndj_institucion');
        if (!sel) return;
        sel.innerHTML = '<option value="" disabled selected>—</option>';
        console.log(ndj_allInstituciones);
        ndj_allInstituciones.forEach(i => {
            const o = document.createElement('option');
            o.value = i.id; o.textContent = i.text;
            sel.appendChild(o);
        });
    }

    // Pobla el select de carrera filtrando por IEDU_CODIGO === ieduCodigo
    // Si ieduCodigo está vacío, muestra todas
    function ndj_poblarCarreras(ieduCodigo) {
        const sel = $('ndj_carrera');
        if (!sel) return;
        sel.innerHTML = '<option value="999999">NO ESPECIFICA</option>';
        const lista = ieduCodigo
            ? ndj_allCarreras.filter(c => c.IEDU_CODIGO === ieduCodigo)
            : ndj_allCarreras;
        lista.forEach(c => {
            const o = document.createElement('option');
            o.value = c.id; o.textContent = c.text;
            sel.appendChild(o);
        });
    }

    // ============================================================
    // UBIGEOS
    // Mismos endpoints que usa gestion_dj.js:
    //   GET /api/ubicacion/departamentos  → [{ depa_codigo, depa_descripcion }]
    //   GET /api/ubicacion/provincias/:d  → [{ provi_codigo, provi_descripcion }]
    //   GET /api/ubicacion/distritos/:p   → [{ dist_codigo, dist_descripcion }]
    // ============================================================
    const NDJ_UBI = `${VITE_URL_APP}/api/ubicacion`;

    async function ndj_fetchUbi(url) {
        if (ndj_ubigeoCache.has(url)) return ndj_ubigeoCache.get(url);
        const res = await fetch(url);
        const data = await res.json();
        ndj_ubigeoCache.set(url, data);
        return data;
    }

    function ndj_poblarUbiSelect(id, items, valKey, txtKey, ph = '—') {
        const sel = $(id);
        if (!sel) return;
        sel.innerHTML = `<option value="">${ph}</option>`;
        items.forEach(item => {
            const o = document.createElement('option');
            o.value = item[valKey]; o.textContent = item[txtKey];
            sel.appendChild(o);
        });
    }

    async function ndj_cargarDepartamentos() {
        try {
            const data = await ndj_fetchUbi(`${NDJ_UBI}/departamentos`);
            ['ndj_departamento_actual', 'ndj_departamento_dni', 'ndj_departamento_nac']
                .forEach(id => ndj_poblarUbiSelect(id, data, 'depa_codigo', 'depa_descripcion', '— Departamento —'));
        } catch (e) { console.error('[NuevaDJ] Error departamentos:', e); }
        // Ya es async, el return implícito es una promesa resuelta ✓
    }

    async function ndj_cargarProvincias(deptCod, provId, distId) {
        const sp = $(provId), sd = $(distId);
        if (sp) sp.innerHTML = '<option value="">— Provincia —</option>';
        if (sd) sd.innerHTML = '<option value="">— Distrito —</option>';
        if (!deptCod) return;
        try {
            const data = await ndj_fetchUbi(`${NDJ_UBI}/provincias/${deptCod}`);
            ndj_poblarUbiSelect(provId, data, 'provi_codigo', 'provi_descripcion', '— Provincia —');
        } catch (e) { console.error('[NuevaDJ] Error provincias:', e); }
    }

    async function ndj_cargarDistritos(provCod, distId) {
        const sd = $(distId);
        if (sd) sd.innerHTML = '<option value="">— Distrito —</option>';
        if (!provCod) return;
        try {
            const data = await ndj_fetchUbi(`${NDJ_UBI}/distritos/${provCod}`);
            ndj_poblarUbiSelect(distId, data, 'dist_codigo', 'dist_descripcion', '— Distrito —');
        } catch (e) { console.error('[NuevaDJ] Error distritos:', e); }
    }

    // ============================================================
    // VISIBILIDAD POR TIPO DE PERSONAL
    // ============================================================
    function ndj_aplicarTipo(tipoCod) {
        const el = $('ndj_tipo_personal');
        if (el) el.value = tipoCod;

        const esOpe = ['01', '03'].includes(tipoCod);
        const esAdm = ['02', '05', '06'].includes(tipoCod);

        document.querySelectorAll('#modalNuevaDJ [data-ndj-tipo="operativo"]')
            .forEach(s => { s.style.display = esAdm ? 'none' : ''; });
        document.querySelectorAll('#modalNuevaDJ [data-ndj-tipo="administrativo"]')
            .forEach(s => { s.style.display = esOpe ? 'none' : ''; });

        const badge = $('ndj_tipo_badge');
        if (!badge) return;
        const cfg = {
            '01': { texto: 'Operativo 4°', bg: '#dbeafe', color: '#1e40af' },
            '03': { texto: 'Operativo 5°', bg: '#dbeafe', color: '#1e40af' },
            '02': { texto: 'Administrativo 4°', bg: '#d1fae5', color: '#065f46' },
            '05': { texto: 'Administrativo 5°', bg: '#d1fae5', color: '#065f46' },
            '06': { texto: 'Especial', bg: '#fef3c7', color: '#92400e' },
        };
        const c = cfg[tipoCod];
        if (c) {
            badge.textContent = c.texto; badge.style.display = 'inline-block';
            badge.style.background = c.bg; badge.style.color = c.color;
        } else {
            badge.style.display = 'none';
        }
    }

    // ============================================================
    // FOTO
    // ============================================================
    function ndj_limpiarFoto() {
        const inp = $('ndj_inputFoto'), prv = $('ndj_previewFoto'), btn = $('ndj_btnEliminarFoto');
        if (inp) inp.value = '';
        if (prv) { prv.src = ''; prv.classList.add('hidden'); }
        if (btn) btn.classList.add('hidden');
    }

    // ============================================================
    // FILA FAMILIAR
    // ============================================================
    function ndj_crearFila() {
        const div = document.createElement('div');
        div.className = 'ndj-family-row';
        div.style.cssText = 'display:grid;grid-template-columns:1fr 2fr 1fr auto;gap:8px;align-items:end;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:8px 10px;';
        div.innerHTML = `
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
            </div>`;
        div.querySelector('.ndj-remove-family').addEventListener('click', () => div.remove());
        return div;
    }


    async function validarDocumento() {
        const tipo = tipoDocInput.value;
        const numero = docInput.value.trim();

        // Validación básica de formato (puedes personalizar según tus reglas)
        if (!tipo || !numero) {
            docErrorMsg.textContent = '';
            btnGuardar.disabled = true;
            btnGuardar.style.display = 'none';
            return;
        }

        // Ejemplo: para DNI (0034) debe tener 8 dígitos
        if (tipo === '0034' && !/^\d{8}$/.test(numero)) {
            docErrorMsg.textContent = 'El DNI debe tener 8 dígitos numéricos.';
            btnGuardar.disabled = true;
            btnGuardar.style.display = 'none';
            return;
        }

        // Aquí puedes agregar más validaciones según el tipo

        // Validación de existencia vía API
        try {
            // Cambia la URL por la de tu endpoint real
            const res = await fetch(`${VITE_URL_APP}/api/dj/validar-documento?tipo=${tipo}&numero=${numero}`);
            const data = await res.json();
            const dataper = data.data;

            if (dataper.length !== 0 && dataper) {
                const p = dataper[0];
                const vigenciaTxt = p.PERS_VIGENCIA === 'SI'
                    ? '<span style="color:#16a34a;font-weight:600;">(VIGENTE)</span>'
                    : '<span style="color:#dc2626;font-weight:600;">(NO VIGENTE)</span>';
                docErrorMsg.innerHTML = `
                    <span style="font-weight:600;">El documento ya está registrado:</span><br>
                    <span style="color:#1d4ed8;">${p.NOMB_1} ${p.NOMB_2} ${p.APEL_1} ${p.APEL_2}</span>
                    <span style="color:#64748b;">(${p.NRO_DOCU_IDEN})</span> ${vigenciaTxt}
                `;
                btnGuardar.disabled = true;
                btnGuardar.style.display = 'none';
                dniValido = false;
            } else {
                docErrorMsg.textContent = '';
                btnGuardar.disabled = false;
                btnGuardar.style.display = 'block';
                dniValido = true;
            }

            if (dataper.length !== 0 && dataper) {
                dniValido = false;
            } else {
                dniValido = true;
            }
            actualizarEstadoGuardar();
            
        } catch (e) {
            docErrorMsg.textContent = 'Error validando documento.';
            btnGuardar.disabled = true;
            btnGuardar.style.display = 'none';
        }
    }

    // ============================================================
    // BIND DE EVENTOS
    // ============================================================
    document.addEventListener('DOMContentLoaded', function () {

        ndj_bloquearCampos(true);
        //bindAlertaCamposBloqueados();

        if (alertTipoPersonal) alertTipoPersonal.style.display = 'block';

        tipoPersonalSelect?.addEventListener('change', function () {
            if (this.value && alertTipoPersonal) {
                alertTipoPersonal.style.display = 'none';
            } else if (alertTipoPersonal) {
                alertTipoPersonal.style.display = 'block';
            }
        });

        $('ndj_sel_tipo_personal')?.addEventListener('change', function () {
            if (this.value) {
                ndj_bloquearCampos(false);
            } else {
                ndj_bloquearCampos(true);
            }
        });

        // Cerrar
        $('ndj_btnCerrar')?.addEventListener('click', ndj_cerrar);
        $('ndj_btnCerrarX')?.addEventListener('click', ndj_cerrar);
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !$('modalNuevaDJ')?.classList.contains('hidden'))
                ndj_cerrar();
        });

        // ── Tipo de documento ────────────────────────────────
        $('ndj_tipo_documento')?.addEventListener('change', function () {
            const inp = $('ndj_nro_documento');
            if (!inp) return;
            inp.value = '';
            const map = {
                '0034': { max: 8, ph: 'Ej: 12345678', mode: 'numeric' },
                '0035': { max: 12, ph: 'Ej: 000123456', mode: 'text' },
                '0037': { max: 12, ph: 'Ej: A1234567', mode: 'text' },
                '0038': { max: 12, ph: 'Ingrese doc. provisional', mode: 'text' },
                '0215': { max: 12, ph: 'Ingrese cédula', mode: 'text' },
                '0216': { max: 12, ph: 'Ingrese carnet temporal', mode: 'text' },
            };
            const c = map[this.value] ?? { max: 20, ph: 'Ingrese el número', mode: 'text' };
            inp.maxLength = c.max; inp.placeholder = c.ph; inp.inputMode = c.mode;
        });

        // Solo dígitos para DNI
        $('ndj_nro_documento')?.addEventListener('input', function () {
            if ($('ndj_tipo_documento')?.value === '0034')
                this.value = this.value.replace(/\D/g, '');
            else
                this.value = this.value.toUpperCase();
        });

        // ── Tipo de personal ─────────────────────────────────
        $('ndj_sel_tipo_personal')?.addEventListener('change', function () {
            ndj_aplicarTipo(this.value);
        });

        // ── Educación: cascada Institución → Carrera ─────────
        // El grado NO filtra institución (son independientes según la estructura del API)
        // La institución SÍ filtra carrera por IEDU_CODIGO
        $('ndj_institucion')?.addEventListener('change', function () {
            ndj_poblarCarreras(this.value);
        });

        // ── Ubigeos: Dirección Actual ────────────────────────
        $('ndj_departamento_actual')?.addEventListener('change', function () {
            ndj_cargarProvincias(this.value, 'ndj_provincia_actual', 'ndj_distrito_actual');
        });
        $('ndj_provincia_actual')?.addEventListener('change', function () {
            ndj_cargarDistritos(this.value, 'ndj_distrito_actual');
        });

        // ── Ubigeos: Dirección DNI ───────────────────────────
        $('ndj_departamento_dni')?.addEventListener('change', function () {
            ndj_cargarProvincias(this.value, 'ndj_provincia_dni', 'ndj_distrito_dni');
        });
        $('ndj_provincia_dni')?.addEventListener('change', function () {
            ndj_cargarDistritos(this.value, 'ndj_distrito_dni');
        });

        // ── Ubigeos: Ciudad Nacimiento ───────────────────────
        $('ndj_departamento_nac')?.addEventListener('change', function () {
            ndj_cargarProvincias(this.value, 'ndj_provincia_nac', 'ndj_distrito_nac');
        });
        $('ndj_provincia_nac')?.addEventListener('change', function () {
            ndj_cargarDistritos(this.value, 'ndj_distrito_nac');
        });

        // ── Familiar en empresa ──────────────────────────────
        $('ndj_familiar_empresa')?.addEventListener('change', function () {
            $('ndj_div_familiar_interno')?.classList.toggle('hidden', this.value !== 'SI');
        });

        // ── SUCAMEC ──────────────────────────────────────────
        $('ndj_curso_sucamec')?.addEventListener('change', function () {
            $('ndj_div_sucamec_obs')?.classList.toggle('hidden', this.value !== 'SI');
        });

        // ── Clase brevete → tipo vehículo ────────────────────
        $('ndj_clase_brevete')?.addEventListener('change', function () {
            const sel = $('ndj_tipo_vehiculo');
            if (!sel) return;
            sel.innerHTML = '<option value="">-- Seleccione --</option>';
            (NDJ_CAT[this.value] || []).forEach(item => {
                const o = document.createElement('option');
                o.value = item.val; o.textContent = item.text;
                sel.appendChild(o);
            });
        });

        // ── Foto ─────────────────────────────────────────────
        $('ndj_btnSubirFoto')?.addEventListener('click', () => $('ndj_inputFoto')?.click());
        $('ndj_inputFoto')?.addEventListener('change', function () {
            const file = this.files?.[0];
            if (!file) return;
            const r = new FileReader();
            r.onload = e => {
                const prev = $('ndj_previewFoto');
                if (prev) { prev.src = e.target.result; prev.classList.remove('hidden'); }
                $('ndj_btnEliminarFoto')?.classList.remove('hidden');
            };
            r.readAsDataURL(file);
        });
        $('ndj_btnEliminarFoto')?.addEventListener('click', ndj_limpiarFoto);

        // ── Familiares ───────────────────────────────────────
        $('ndj_addFamilyMember')?.addEventListener('click', () => {
            $('ndj_familyContainer')?.appendChild(ndj_crearFila());
        });
        $('ndj_familyContainer')?.addEventListener('click', e => {
            const btn = e.target.closest('.ndj-remove-family');
            if (btn) btn.closest('.ndj-family-row')?.remove();
        });

        // ── Guardar ──────────────────────────────────────────
        $('ndj_btnGuardar')?.addEventListener('click', function () {
            // const fd = new FormData($('formNuevaDJ'));
            // const payload = Object.fromEntries(fd.entries());
            // payload.ndj_parentesco       = fd.getAll('ndj_parentesco[]');
            // payload.ndj_apellidosNombres = fd.getAll('ndj_apellidosNombres[]');
            // payload.ndj_fechaNacimiento  = fd.getAll('ndj_fechaNacimiento[]');
            // fetch(`${VITE_URL_APP}/api/dj/save-nueva-dj`, {
            //     method: 'POST',
            //     headers: {
            //         'Content-Type': 'application/json',
            //         'X-CSRF-TOKEN': document.querySelector('[name=_token]').value
            //     },
            //     body: JSON.stringify(payload)
            // }).then(r => r.json()).then(() => { ndj_cerrar(); }).catch(console.error);
            console.log('[NuevaDJ] Implementar guardado aquí');
        });

        // Escuchar cambios en tipo y número de documento
        tipoDocInput?.addEventListener('change', validarDocumento);
        docInput?.addEventListener('input', validarDocumento);

        // ── API pública ───────────────────────────────────────
        window.NuevaDJ = { abrir: ndj_abrir, cerrar: ndj_cerrar };

        [nombre1, nombre2, apellido1, apellido2].forEach(input => {
            input?.addEventListener('input', buscarCoincidencias);
        });
    });





})();