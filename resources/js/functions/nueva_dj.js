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

    // ── Estado de recontratación ─────────────────────────────
    let modoRecontratacion = false;
    let codiPersRecontratacion = null;

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
            { val: 'A-I',    text: 'A-I: Particulares' },
            { val: 'A-IIa',  text: 'A-IIa: Taxi / Ambulancia' },
            { val: 'A-IIb',  text: 'A-IIb: Microbús / Pickup' },
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

    let ndj_allGrados        = [];
    let ndj_allInstituciones = [];
    let ndj_allCarreras      = [];
    let ndj_catalogoListo    = false;
    let ndj_abriendo         = false;

    const ndj_ubigeoCache = new Map();

    // ============================================================
    // MODO RECONTRATACIÓN
    // ============================================================
    function activarModoRecontratacion(codiPers) {
        modoRecontratacion     = true;
        codiPersRecontratacion = codiPers;
        if (btnGuardar) {
            btnGuardar.textContent = 'Recontratar';
            btnGuardar.style.background = '#f59e0b';
        }
        const badge = $('ndj_tipo_badge');
        if (badge) {
            badge.textContent      = 'MODO RECONTRATACIÓN';
            badge.style.display    = 'inline-block';
            badge.style.background = '#fef3c7';
            badge.style.color      = '#92400e';
        }
    }

    function desactivarModoRecontratacion() {
        modoRecontratacion     = false;
        codiPersRecontratacion = null;
        if (btnGuardar) {
            btnGuardar.textContent  = 'Guardar';
            btnGuardar.style.background = 'var(--color-primary,#6366f1)';
        }
    }

    // ============================================================
    // AUTOCOMPLETAR FORMULARIO
    // ============================================================
    async function autocompletarDesdePersonal(codiPers) {
        Swal.fire({ title: 'Cargando datos...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

        try {
            if (!ndj_catalogoListo) {
                await Promise.all([
                    ndj_cargarTipoDoc(), ndj_cargarTipoPer(),
                    ndj_cargarEstadoCivil(), ndj_cargarSistemaPrev(),
                    ndj_cargarDepartamentos(), ndj_cargarEducacion(),
                ]);
            }

            const res  = await fetch(`${VITE_URL_APP}/api/dj/get-personal-data?codi_pers=${encodeURIComponent(codiPers)}&source=pendiente`);
            const json = await res.json();

            if (!json.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: json.message || 'No se pudo obtener los datos.' });
                return;
            }

            const data      = json.data      || {};
            const familiares = json.familiares || {};

            ndj_setVal('ndj_cod_postulante',       data.CODI_PERS            || '');
            ndj_setVal('ndj_nro_documento',         data.NRO_DOCU_IDEN?.trim() || '');
            ndj_setVal('ndj_nombre1',               data.NOMB_1?.trim()        || '');
            ndj_setVal('ndj_nombre2',               data.NOMB_2?.trim()        || '');
            ndj_setVal('ndj_apellido_paterno',      data.APEL_1?.trim()        || '');
            ndj_setVal('ndj_apellido_materno',      data.APEL_2?.trim()        || '');
            ndj_setVal('ndj_caduca',                ndj_fmtDate(data.PERS_FECHCADUCADNI));
            ndj_setVal('ndj_estado_civil',          data.ESCI_CODIGO?.trim()   || '');
            ndj_setVal('ndj_sexo',                  data.PERS_SEXO?.trim()     || data.SEXO?.trim() || '');
            ndj_setVal('ndj_fecha_nacimiento',      ndj_fmtDate(data.FECH_NACI));
            ndj_setVal('ndj_celular',               data.PERS_TELEFONO?.trim() || '');
            ndj_setVal('ndj_correo',                data.PERS_EMAIL?.trim()    || '');
            ndj_setVal('ndj_whatsapp',              data.PERS_WHATSAPP?.trim() || '');
            ndj_setVal('ndj_tipo_sangre',           data.tipo_sangr?.trim()    || '');
            ndj_setVal('ndj_peso',                  data.peso_kilo?.trim()     || '');
            ndj_setVal('ndj_talla',                 data.tall_metr?.trim()     || '');
            ndj_setVal('ndj_sistema_previsional',   data.CODI_SIST_PENS?.trim()|| '');
            ndj_setVal('ndj_essalud',               data.ESSALUD?.trim()       || '');
            ndj_setVal('ndj_pensionista',           data.PERS_PENSIONISTA?.trim() || '');
            ndj_setVal('ndj_grado_instruccion',     data.PERS_GRADO_INSTRUCCION?.trim() || '');
            ndj_setVal('ndj_anio_egreso',           data.EGRESO_EDUCATIVO?.trim() || '');
            ndj_setVal('ndj_embargos',              data.PERS_EMBARGO?.trim()  || '');
            ndj_setVal('ndj_cuenta_banco',          data.dj2026_banco?.trim()  || '');
            ndj_setVal('ndj_direccion_actual',      data.DIRECCION?.trim()     || '');
            ndj_setVal('ndj_direccion_dni',         data.PERS_DIREC_DNI?.trim()|| '');
            ndj_setVal('ndj_contacto_emergencia',   data.PERS_NOMCONTACTO?.trim()    || '');
            ndj_setVal('ndj_celular_emergencia',    data.PERS_NROEMERGENCIA?.trim()  || '');
            ndj_setVal('ndj_parentesco_emergencia', data.PERS_EMERC_FAMILIAR?.trim() || '');
            ndj_setVal('ndj_ocupacion_principal',   data.dj2026_ocupacion_principal?.trim() || '');
            ndj_setVal('ndj_experiencia_anios',     data.dj2026_experiencia_anios ? String(data.dj2026_experiencia_anios).replace(/[^0-9]/g,'') : '');
            ndj_setVal('ndj_familiar_empresa',      data.dj2026_familiar_empresa?.trim()    || '');
            ndj_setVal('ndj_familiar_nombre',       data.dj2026_familiar_nombre?.trim()     || '');
            ndj_setVal('ndj_familiar_parentesco',   data.dj2026_familiar_parentesco?.trim() || '');
            ndj_setVal('ndj_curso_sucamec',         data.PERS_CONDISCAMEC?.trim() || '');
            ndj_setVal('ndj_sucamec_obs',           data.PERS_NRODISCAMEC?.trim() || '');
            ndj_setVal('ndj_smo',                   data.PERS_SMO?.trim()         || '');
            ndj_setVal('ndj_licencia_arma',         data.PERS_NROLICENCIA?.trim() || '');
            ndj_setVal('ndj_arma_propia',           data.PERS_CONARMAS?.trim()    || '');
            ndj_setVal('ndj_brevete',               data.PERS_BREVETE?.trim()     || '');
            ndj_setVal('ndj_vehiculo_propio',       data.PERS_VEHICULO_PROPIO?.trim() || '');
            ndj_setVal('ndj_empresa_anterior',      data.PERS_CTRABANT?.trim()    || '');
            ndj_setVal('ndj_cargo_anterior',        data.PERS_CARGOTRABANT?.trim()|| '');
            ndj_setVal('ndj_duracion_anterior',     data.PERS_DURACIONANT?.trim() || '');
            ndj_setVal('ndj_laboral_1',             data.dj2026_laboral_1?.trim() || '');
            ndj_setVal('ndj_laboral_2',             data.dj2026_laboral_2?.trim() || '');
            ndj_setVal('ndj_filtroSucursal',        data.SUCU_CODIGO?.trim()      || '');

            const tipotrab = data.PERS_TIPOTRAB?.trim() || '';
            ndj_setVal('ndj_sel_tipo_personal', tipotrab);
            ndj_setVal('ndj_tipo_personal',     tipotrab);
            ndj_aplicarTipo(tipotrab);
            ndj_bloquearCampos(false);

            if (data.CLASE_BREVETE) {
                ndj_setVal('ndj_clase_brevete', data.CLASE_BREVETE.trim());
                const selTipo = $('ndj_tipo_vehiculo');
                if (selTipo) {
                    selTipo.innerHTML = '<option value="">-- Seleccione --</option>';
                    (NDJ_CAT[data.CLASE_BREVETE.trim()] || []).forEach(item => {
                        const o = document.createElement('option');
                        o.value = item.val; o.textContent = item.text;
                        selTipo.appendChild(o);
                    });
                    ndj_setVal('ndj_tipo_vehiculo', data.CATEGORIA_BREVETE?.trim() || '');
                }
            }

            if (data.IEDU_CODIGO) {
                ndj_setVal('ndj_institucion', data.IEDU_CODIGO.trim());
                ndj_poblarCarreras(data.IEDU_CODIGO.trim());
                await new Promise(r => setTimeout(r, 80));
                ndj_setVal('ndj_carrera', data.CARR_CODIGO?.trim() || '999999');
            }

            if (data.dj2026_familiar_empresa === 'SI') $('ndj_div_familiar_interno')?.classList.remove('hidden');
            if (data.PERS_CONDISCAMEC === 'SI')         $('ndj_div_sucamec_obs')?.classList.remove('hidden');

            await ndj_cargarUbigeosCascada('ndj_departamento_actual','ndj_provincia_actual','ndj_distrito_actual', data.PERS_DEPT_ACT?.trim(),   data.PERS_PROV_ACT?.trim(),    data.PERS_DIST_ACT?.trim());
            await ndj_cargarUbigeosCascada('ndj_departamento_dni',   'ndj_provincia_dni',   'ndj_distrito_dni',    data.PERS_DPTO_DIRDNI?.trim(), data.PERS_PROV_DIRDNI?.trim(), data.PERS_DIST_DIRDNI?.trim());
            await ndj_cargarUbigeosCascada('ndj_departamento_nac',   'ndj_provincia_nac',   'ndj_distrito_nac',    data.DEPA_CODIGO_NACI?.trim(), data.PROVI_CODIGO_NACI?.trim(),data.DIST_NACI?.trim());
            ndj_setVal('ndj_ciudad_naci', data.dj2026_ciudad_naci?.trim() || '');

            const fc = $('ndj_familyContainer');
            if (fc) {
                fc.innerHTML = '';
                const allFam = [...(familiares.padres||[]),...(familiares.madre||[]),...(familiares.hijos||[]),...(familiares.conyugue||[])];
                if (allFam.length === 0) fc.appendChild(ndj_crearFila());
                else allFam.forEach(f => fc.appendChild(ndj_crearFilaConDatos(f)));
            }

            if (data.FOTO_PATH) {
                const prv = $('ndj_previewFoto'), ph = $('ndj_placeholderFoto');
                if (prv) { prv.src = data.FOTO_PATH + '?v=' + (Math.floor(Math.random() * 900) + 100); prv.classList.remove('hidden'); }
                if (ph)  ph.classList.add('hidden');
                $('ndj_btnEliminarFoto')?.classList.remove('hidden');
            }

            Swal.close();
            activarModoRecontratacion(codiPers);
            dniValido              = true;
            coincidenciasValidadas = true;
            actualizarEstadoGuardar();

        } catch (err) {
            console.error('[NuevaDJ] Error autocompletando:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos del personal.' });
        }
    }

    // ── Ubigeo cascada ───────────────────────────────────────
    async function ndj_cargarUbigeosCascada(idDept, idProv, idDist, dept, prov, dist) {
        if (!dept) return;
        ndj_setVal(idDept, dept);
        if (!prov) return;
        await ndj_cargarProvincias(dept, idProv, idDist);
        ndj_setVal(idProv, prov);
        if (!dist) return;
        await ndj_cargarDistritos(prov, idDist);
        ndj_setVal(idDist, dist);
    }

    // ── Fila familiar con datos ──────────────────────────────
    function ndj_crearFilaConDatos(f = {}) {
        let fechaFormateada = '';
        if (f.FECH_NACI) {
            const s = String(f.FECH_NACI);
            fechaFormateada = (s.length >= 8 && !s.includes('-') && !s.includes('/'))
                ? `${s.substring(0,4)}-${s.substring(4,6)}-${s.substring(6,8)}`
                : ndj_fmtDate(s);
        }
        const div    = ndj_crearFila();
        const selPar = div.querySelector('select[name="ndj_parentesco[]"]');
        const inpNom = div.querySelector('input[name="ndj_apellidosNombres[]"]');
        const inpFec = div.querySelector('input[name="ndj_fechaNacimiento[]"]');
        if (selPar) selPar.value = f.TIPO_RELA || '';
        if (inpNom) inpNom.value = f.Nombres   || '';
        if (inpFec) inpFec.value = fechaFormateada;
        return div;
    }

    function ndj_setVal(id, value) {
        const el = document.getElementById(id);
        if (el) el.value = value || '';
    }

    function ndj_fmtDate(val) {
        if (!val) return '';
        if (typeof val === 'string' && val.includes('T')) return val.split('T')[0];
        if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val)) return val;
        if (typeof val === 'string' && val.includes(' ')) return val.split(' ')[0];
        if (typeof val === 'string' && /^\d{2}[-/]\d{2}[-/]\d{4}$/.test(val)) {
            const [d, m, y] = val.split(/[-/]/);
            return `${y}-${m}-${d}`;
        }
        return '';
    }

    // ============================================================
    // ABRIR
    // ============================================================
    async function ndj_abrir() {
        if (ndj_abriendo) return;
        ndj_abriendo = true;

        if (alertTipoPersonal) alertTipoPersonal.style.display = 'block';

        const modal = $('modalNuevaDJ');
        if (!modal) { ndj_abriendo = false; return; }

        ndj_reset();

        const necesitaCarga = !ndj_catalogoListo;
        if (necesitaCarga && typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Preparando formulario...',
                html: '<span style="font-size:13px;color:#6b7280;">Cargando catálogos y ubigeos</span>',
                allowOutsideClick: false, allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading(),
            });
        }

        try {
            if (necesitaCarga) {
                await Promise.all([
                    ndj_cargarTipoDoc(), ndj_cargarTipoPer(),
                    ndj_cargarEstadoCivil(), ndj_cargarSistemaPrev(),
                    ndj_cargarDepartamentos(), ndj_cargarEducacion(),
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
            btnGuardar.disabled      = false;
            btnGuardar.style.display = 'block';
        } else {
            btnGuardar.disabled      = true;
            btnGuardar.style.display = 'none';
        }
    }

    async function buscarCoincidencias() {
        const n1 = nombre1.value.trim(), n2 = nombre2.value.trim();
        const a1 = apellido1.value.trim(), a2 = apellido2.value.trim();

        if ((!n1 && !n2 && !a1 && !a2) || (n1.length === 0 || a1.length === 0)) {
            coincidenciasDiv.innerHTML = '';
            hayCoincidencias = false; coincidenciasValidadas = true;
            actualizarEstadoGuardar(); return;
        }

        try {
            const res  = await fetch(`${VITE_URL_APP}/api/dj/buscar-coincidencias?nomb1=${encodeURIComponent(n1)}&nomb2=${encodeURIComponent(n2)}&apel1=${encodeURIComponent(a1)}&apel2=${encodeURIComponent(a2)}`);
            const data = await res.json();

            if (data.data.length === 0) {
                hayCoincidencias = false; coincidenciasValidadas = true;
                coincidenciasDiv.innerHTML = ''; actualizarEstadoGuardar(); return;
            }

            hayCoincidencias = true; coincidenciasValidadas = false;
            coincidenciasDiv.innerHTML = `
                <b>Coincidencias encontradas:</b>
                <ul style="list-style:none;padding:0;">
                    ${data.data.map((p, idx) => `
                        <li style="padding-bottom:8px;">
                            ${p.NOMB_1} ${p.NOMB_2} ${p.APEL_1} ${p.APEL_2} - ${p.NRO_DOCU_IDEN}<br>
                            <span class="text-danger">${p.PERS_VIGENCIA != 'SI' ? '(NO VIGENTE)' : ''}</span>
                            ${idx < data.data.length - 1 ? '<hr style="margin:8px 0;border:0;border-top:1px solid #e5e7eb;">' : ''}
                        </li>`).join('')}
                </ul>
                <button id="ndj_btnEntendido" type="button" style="margin-top:8px;padding:6px 18px;font-size:13px;font-weight:600;border-radius:6px;background:#fbbf24;color:#fff;cursor:pointer;border:none;">
                    Entendido, deseo continuar
                </button>`;

            document.getElementById('ndj_btnEntendido')?.addEventListener('click', function () {
                coincidenciasValidadas = true;
                coincidenciasDiv.innerHTML = '';
                actualizarEstadoGuardar();
            });
            actualizarEstadoGuardar();

        } catch (e) {
            coincidenciasDiv.innerHTML = 'Error buscando coincidencias.';
            hayCoincidencias = false; coincidenciasValidadas = false;
            actualizarEstadoGuardar();
        }
    }

    function ndj_bloquearCampos(bloquear) {
        document.querySelectorAll('#modalNuevaDJ input:not(#ndj_sel_tipo_personal), #modalNuevaDJ select:not(#ndj_sel_tipo_personal), #modalNuevaDJ textarea')
            .forEach(el => el.disabled = bloquear);
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
        if (window.HSOverlay) { try { HSOverlay.close(modal); } catch (e) { } }
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('hs-overlay-open');
            document.querySelectorAll('.hs-overlay-backdrop').forEach(el => el.remove());
            document.body.classList.remove('overflow-hidden');
            document.body.style.overflow = '';
            ndj_reset();
        }, 300);
    }

    // ============================================================
    // RESET
    // ============================================================
    function ndj_reset() {
        $('formNuevaDJ')?.reset();
        desactivarModoRecontratacion();
        dniValido = false; coincidenciasValidadas = false; hayCoincidencias = false;
        docErrorMsg.innerHTML = ''; coincidenciasDiv.innerHTML = '';

        const badge = $('ndj_tipo_badge');
        if (badge) badge.style.display = 'none';

        document.querySelectorAll('#modalNuevaDJ [data-ndj-tipo]').forEach(el => { el.style.display = ''; });
        $('ndj_div_familiar_interno')?.classList.add('hidden');
        $('ndj_div_sucamec_obs')?.classList.add('hidden');

        ndj_limpiarFoto();

        ['ndj_provincia_actual','ndj_distrito_actual','ndj_provincia_dni','ndj_distrito_dni','ndj_provincia_nac','ndj_distrito_nac']
            .forEach(id => { const s = $(id); if (s) s.innerHTML = '<option value="">—</option>'; });

        const fc = $('ndj_familyContainer');
        if (fc) { fc.innerHTML = ''; fc.appendChild(ndj_crearFila()); }
    }

    // ============================================================
    // CATÁLOGOS
    // ============================================================
    async function ndj_fetchSelect(selectId, url, valorKey, textoKey, placeholder = '— Seleccionar —') {
        const sel = $(selectId);
        if (!sel) return;
        sel.innerHTML = `<option value="">Cargando...</option>`;
        sel.disabled  = true;
        try {
            const res   = await fetch(url);
            const json  = await res.json();
            const items = Array.isArray(json) ? json : (json.data ?? []);
            sel.innerHTML = `<option value="">${placeholder}</option>`;
            items.forEach(item => { const o = document.createElement('option'); o.value = item[valorKey]; o.textContent = item[textoKey]; sel.appendChild(o); });
        } catch (err) {
            console.error(`[NuevaDJ] Error cargando ${selectId}:`, err);
            sel.innerHTML = `<option value="">Error al cargar</option>`;
        } finally { sel.disabled = false; }
    }

    function ndj_cargarTipoDoc()    { return ndj_fetchSelect('ndj_tipo_documento',      `${VITE_URL_APP}/api/dj/get-tipo-doc/`,     'codigo','nombre'); }
    function ndj_cargarTipoPer()    { return ndj_fetchSelect('ndj_sel_tipo_personal',   `${VITE_URL_APP}/api/dj/get-tipo-per/`,     'codigo','nombre'); }
    function ndj_cargarEstadoCivil(){ return ndj_fetchSelect('ndj_estado_civil',        `${VITE_URL_APP}/api/dj/get-estado-civil/`, 'codigo','nombre'); }
    function ndj_cargarSistemaPrev(){ return ndj_fetchSelect('ndj_sistema_previsional', `${VITE_URL_APP}/api/dj/get-sistema-prev/`, 'codigo','nombre'); }

    // ============================================================
    // EDUCACIÓN
    // ============================================================
    async function ndj_cargarEducacion() {
        if (ndj_catalogoListo) { ndj_poblarGrados(); ndj_poblarInstituciones(); ndj_poblarCarreras(''); return; }
        try {
            const res  = await fetch(`${VITE_URL_APP}/api/dj/get-catalogs`);
            const data = await res.json();
            ndj_allGrados        = data.grados        || [];
            ndj_allInstituciones = data.instituciones || [];
            ndj_allCarreras      = data.carreras      || [];
            ndj_poblarGrados(); ndj_poblarInstituciones(); ndj_poblarCarreras('');
            ndj_catalogoListo = true;
        } catch (e) { console.error('[NuevaDJ] Error catálogos educación:', e); }
    }

    function ndj_poblarGrados() {
        const sel = $('ndj_grado_instruccion'); if (!sel) return;
        sel.innerHTML = '<option value="" disabled selected>—</option>';
        ndj_allGrados.forEach(g => { const o = document.createElement('option'); o.value = g.id; o.textContent = g.text; sel.appendChild(o); });
    }
    function ndj_poblarInstituciones() {
        const sel = $('ndj_institucion'); if (!sel) return;
        sel.innerHTML = '<option value="" disabled selected>—</option>';
        ndj_allInstituciones.forEach(i => { const o = document.createElement('option'); o.value = i.id; o.textContent = i.text; sel.appendChild(o); });
    }
    function ndj_poblarCarreras(ieduCodigo) {
        const sel = $('ndj_carrera'); if (!sel) return;
        sel.innerHTML = '<option value="999999">NO ESPECIFICA</option>';
        const lista = ieduCodigo ? ndj_allCarreras.filter(c => c.IEDU_CODIGO === ieduCodigo) : ndj_allCarreras;
        lista.forEach(c => { const o = document.createElement('option'); o.value = c.id; o.textContent = c.text; sel.appendChild(o); });
    }

    // ============================================================
    // UBIGEOS
    // ============================================================
    const NDJ_UBI = `${VITE_URL_APP}/api/ubicacion`;

    async function ndj_fetchUbi(url) {
        if (ndj_ubigeoCache.has(url)) return ndj_ubigeoCache.get(url);
        const res = await fetch(url); const data = await res.json();
        ndj_ubigeoCache.set(url, data); return data;
    }
    function ndj_poblarUbiSelect(id, items, valKey, txtKey, ph = '—') {
        const sel = $(id); if (!sel) return;
        sel.innerHTML = `<option value="">${ph}</option>`;
        items.forEach(item => { const o = document.createElement('option'); o.value = item[valKey]; o.textContent = item[txtKey]; sel.appendChild(o); });
    }
    async function ndj_cargarDepartamentos() {
        try {
            const data = await ndj_fetchUbi(`${NDJ_UBI}/departamentos`);
            ['ndj_departamento_actual','ndj_departamento_dni','ndj_departamento_nac']
                .forEach(id => ndj_poblarUbiSelect(id, data, 'depa_codigo', 'depa_descripcion', '— Departamento —'));
        } catch (e) { console.error('[NuevaDJ] Error departamentos:', e); }
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
    // VISIBILIDAD POR TIPO
    // ============================================================
    function ndj_aplicarTipo(tipoCod) {
        const el = $('ndj_tipo_personal');
        if (el) el.value = tipoCod;
        const esOpe = ['01','03'].includes(tipoCod);
        const esAdm = ['02','05','06'].includes(tipoCod);
        document.querySelectorAll('#modalNuevaDJ [data-ndj-tipo="operativo"]').forEach(s => { s.style.display = esAdm ? 'none' : ''; });
        document.querySelectorAll('#modalNuevaDJ [data-ndj-tipo="administrativo"]').forEach(s => { s.style.display = esOpe ? 'none' : ''; });
        if (modoRecontratacion) return;
        const badge = $('ndj_tipo_badge'); if (!badge) return;
        const cfg = {
            '01':{ texto:'Operativo 4°',     bg:'#dbeafe', color:'#1e40af' },
            '03':{ texto:'Operativo 5°',      bg:'#dbeafe', color:'#1e40af' },
            '02':{ texto:'Administrativo 4°', bg:'#d1fae5', color:'#065f46' },
            '05':{ texto:'Administrativo 5°', bg:'#d1fae5', color:'#065f46' },
            '06':{ texto:'Especial',           bg:'#fef3c7', color:'#92400e' },
        };
        const c = cfg[tipoCod];
        if (c) { badge.textContent = c.texto; badge.style.display = 'inline-block'; badge.style.background = c.bg; badge.style.color = c.color; }
        else   { badge.style.display = 'none'; }
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
    // FILAS FAMILIAR
    // ============================================================
    function ndj_crearFila() {
        const div = document.createElement('div');
        div.className  = 'ndj-family-row';
        div.style.cssText = 'display:grid;grid-template-columns:1fr 2fr 1fr auto;gap:8px;align-items:end;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:8px 10px;';
        div.innerHTML = `
            <div><label class="dj-label">Parentesco</label>
                <select name="ndj_parentesco[]" class="dj-select">
                    <option value="" disabled selected>—</option>
                    <option value="PADRE">Padre</option><option value="MADRE">Madre</option>
                    <option value="CONYUGE">Cónyuge</option><option value="HIJO">Hijo(a)</option>
                </select></div>
            <div><label class="dj-label">Apellidos y Nombres</label>
                <input type="text" name="ndj_apellidosNombres[]" class="dj-input" placeholder="Apellidos y nombres completos"></div>
            <div><label class="dj-label">Fecha de Nacimiento</label>
                <input type="date" name="ndj_fechaNacimiento[]" class="dj-input"></div>
            <div><button type="button" class="ndj-remove-family dj-btn-sm dj-btn-danger" style="margin-bottom:1px;">Eliminar</button></div>`;
        div.querySelector('.ndj-remove-family').addEventListener('click', () => div.remove());
        return div;
    }

    // ============================================================
    // VALIDAR DOCUMENTO (DNI)
    // ============================================================
    async function validarDocumento() {
        const tipo   = tipoDocInput.value;
        const numero = docInput.value.trim();

        docErrorMsg.innerHTML = '';
        modoRecontratacion    = false;
        desactivarModoRecontratacion();
        dniValido = false;
        actualizarEstadoGuardar();

        if (!tipo || !numero) return;

        if (tipo === '0034' && !/^\d{8}$/.test(numero)) {
            docErrorMsg.textContent = 'El DNI debe tener 8 dígitos numéricos.'; return;
        }

        try {
            const res     = await fetch(`${VITE_URL_APP}/api/dj/validar-documento?tipo=${tipo}&numero=${numero}`);
            const data    = await res.json();
            const dataper = data.data;

            if (!dataper || dataper.length === 0) {
                docErrorMsg.innerHTML = ''; dniValido = true; actualizarEstadoGuardar(); return;
            }

            const p = dataper[0];

            if (p.PERS_VIGENCIA === 'SI') {
                docErrorMsg.innerHTML = `
                    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:6px;padding:8px 12px;margin-top:4px;">
                        <span style="font-weight:700;color:#b91c1c;">⚠ Personal VIGENTE ya registrado:</span><br>
                        <span style="color:#1d4ed8;font-weight:600;">${p.NOMB_1} ${p.NOMB_2} ${p.APEL_1} ${p.APEL_2}</span>
                        <span style="color:#64748b;">(${p.NRO_DOCU_IDEN})</span>
                        <br><span style="font-size:11px;color:#b91c1c;">No se puede crear un nuevo registro para este personal.</span>
                    </div>`;
                dniValido = false; actualizarEstadoGuardar(); return;
            }

            const codiPers = p.codigo || p.CODI_PERS || '';
            docErrorMsg.innerHTML = `
                <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:6px;padding:8px 12px;margin-top:4px;">
                    <span style="font-weight:700;color:#92400e;">ℹ Personal NO VIGENTE encontrado:</span><br>
                    <span style="color:#1d4ed8;font-weight:600;">${p.NOMB_1} ${p.NOMB_2} ${p.APEL_1} ${p.APEL_2}</span>
                    <span style="color:#64748b;">(${p.NRO_DOCU_IDEN})</span><br>
                    <button id="ndj_btnRecontratacion" type="button" data-codi-pers="${codiPers}"
                        style="margin-top:8px;padding:6px 18px;font-size:13px;font-weight:700;border-radius:6px;
                               background:#f59e0b;color:#fff;cursor:pointer;border:none;box-shadow:0 1px 4px rgba(0,0,0,.15);">
                        🔄 Recontratación — Cargar datos
                    </button>
                </div>`;

            document.getElementById('ndj_btnRecontratacion')?.addEventListener('click', async function () {
                const cp = this.getAttribute('data-codi-pers');
                if (!cp) { Swal.fire({ icon:'error', title:'Error', text:'No se pudo obtener el código de personal.' }); return; }
                await autocompletarDesdePersonal(cp);
            });

            dniValido = false; actualizarEstadoGuardar();

        } catch (e) {
            docErrorMsg.textContent = 'Error validando documento.';
            dniValido = false; actualizarEstadoGuardar();
        }
    }

    // ============================================================
    // SUBIR FOTO AL PROXY (después de guardar)
    // ============================================================
    async function ndj_subirFoto(codiPers) {
        const fotoFile    = $('ndj_inputFoto')?.files?.[0];
        const fotoPreview = $('ndj_previewFoto');

        // Solo subir si es un archivo nuevo seleccionado por el usuario
        // (si es URL existente cargada en autocompletar, fotoFile estará vacío)
        if (!fotoFile || !codiPers) return { ok: true, sinFoto: true };

        try {
            const fdFoto = new FormData();
            fdFoto.append('foto',      fotoFile);
            fdFoto.append('codi_pers', codiPers);
            fdFoto.append('_token',    document.querySelector('[name=_token]')?.value || '');

            const res  = await fetch(`${VITE_URL_APP}/api/dj/upload-foto-personal`, {
                method: 'POST',
                body:   fdFoto,
            });
            const json = await res.json();

            return { ok: json.success, message: json.message };
        } catch (err) {
            console.warn('[NuevaDJ] Error subiendo foto:', err);
            return { ok: false, message: 'Error de conexión al subir foto.' };
        }
    }

    // ============================================================
    // GUARDAR
    // ============================================================
    async function ndj_guardar() {


        // ── Validaciones previas ──────────────────────────────
        const hoy    = new Date(); hoy.setHours(0,0,0,0);
        const manana = new Date(hoy); manana.setDate(hoy.getDate() + 1);

        const caduca = $('ndj_caduca')?.value;
        if (caduca && new Date(caduca + 'T00:00:00') < manana) {
            Swal.fire({ icon:'warning', title:'Fecha inválida', text:'La caducidad debe ser desde mañana en adelante.', confirmButtonText:'Entendido' });
            $('ndj_caduca')?.focus(); return;
        }
        const fechaNac = $('ndj_fecha_nacimiento')?.value;
        if (fechaNac && new Date(fechaNac + 'T00:00:00') >= hoy) {
            Swal.fire({ icon:'warning', title:'Fecha inválida', text:'La fecha de nacimiento debe ser anterior a hoy.', confirmButtonText:'Entendido' });
            $('ndj_fecha_nacimiento')?.focus(); return;
        }
        const celular = $('ndj_celular')?.value?.trim();
        if (celular && (!/^\d+$/.test(celular) || celular.length < 7 || celular.length > 11)) {
            Swal.fire({ icon:'warning', title:'Celular inválido', text:'El celular debe tener entre 7 y 11 dígitos.', confirmButtonText:'Entendido' });
            $('ndj_celular')?.focus(); return;
        }
        const wsp = $('ndj_whatsapp')?.value?.trim();
        if (wsp && (!/^\d+$/.test(wsp) || wsp.length < 7 || wsp.length > 11)) {
            Swal.fire({ icon:'warning', title:'WhatsApp inválido', text:'El WhatsApp debe tener entre 7 y 11 dígitos.', confirmButtonText:'Entendido' });
            $('ndj_whatsapp')?.focus(); return;
        }
        const celEmer = $('ndj_celular_emergencia')?.value?.trim();
        if (celEmer && (!/^\d+$/.test(celEmer) || celEmer.length < 7 || celEmer.length > 11)) {
            Swal.fire({ icon:'warning', title:'Celular emergencia inválido', text:'Debe tener entre 7 y 11 dígitos.', confirmButtonText:'Entendido' });
            $('ndj_celular_emergencia')?.focus(); return;
        }
        const correo = $('ndj_correo')?.value?.trim();
        if (correo && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo)) {
            Swal.fire({ icon:'warning', title:'Correo inválido', text:'Ingrese un correo válido (ejemplo@dominio.com).', confirmButtonText:'Entendido' });
            $('ndj_correo')?.focus(); return;
        }

        // Campos obligatorios
        const camposReq = [
            { id:'ndj_nro_documento',    nombre:'Número de Documento' },
            { id:'ndj_nombre1',          nombre:'Primer Nombre'       },
            { id:'ndj_apellido_paterno', nombre:'Apellido Paterno'    },
            { id:'ndj_apellido_materno', nombre:'Apellido Materno'    },
        ];
        const faltante = camposReq.find(c => !$(c.id)?.value?.trim());
        if (faltante) {
            Swal.fire({ icon:'warning', title:'Campo obligatorio', text:`El campo "${faltante.nombre}" es requerido.`, confirmButtonText:'Entendido' });
            $(faltante.id)?.focus(); return;
        }

        // Pregunta si no hay foto
        const fotoSrc  = $('ndj_previewFoto')?.src?.trim() + '?v=' + (Math.floor(Math.random() * 900) + 100);
        const tieneFoto = fotoSrc && !fotoSrc.endsWith('/') && !$('ndj_previewFoto')?.classList.contains('hidden');
        if (!tieneFoto) {
            const { isConfirmed } = await Swal.fire({
                icon: 'question', title: '¿Continuar sin foto?',
                text: 'No se ha registrado una foto. ¿Desea guardar de todas formas?',
                showCancelButton: true, confirmButtonText: 'Sí, guardar sin foto',
                cancelButtonText: 'Cancelar', confirmButtonColor: '#f59e0b',
            });
            if (!isConfirmed) return;
        }
        // ── Fin validaciones ─────────────────────────────────

        const fd      = new FormData($('formNuevaDJ'));
        const payload = Object.fromEntries(fd.entries());
        payload.ndj_parentesco       = fd.getAll('ndj_parentesco[]');
        payload.ndj_apellidosNombres = fd.getAll('ndj_apellidosNombres[]');
        payload.ndj_fechaNacimiento  = fd.getAll('ndj_fechaNacimiento[]');

        const body = {
            ...payload,
            tipo_personal:       payload.ndj_tipo_personal        || payload.ndj_sel_tipo_personal,
            cod_postulante:      payload.ndj_cod_postulante        || '',
            tipo_documento:      payload.ndj_tipo_documento        || '0034',
            dni:                 payload.ndj_nro_documento,
            nombre1:             payload.ndj_nombre1,
            nombre2:             payload.ndj_nombre2,
            apellido_paterno:    payload.ndj_apellido_paterno,
            apellido_materno:    payload.ndj_apellido_materno,
            caduca:              payload.ndj_caduca,
            estado_civil:        payload.ndj_estado_civil,
            sexo:                payload.ndj_sexo,
            fecha_nacimiento:    payload.ndj_fecha_nacimiento,
            celular:             payload.ndj_celular,
            correo:              payload.ndj_correo,
            whatsapp:            payload.ndj_whatsapp,
            tipo_sangre:         payload.ndj_tipo_sangre,
            peso:                payload.ndj_peso,
            talla:               payload.ndj_talla,
            sistema_previsional: payload.ndj_sistema_previsional,
            essalud:             payload.ndj_essalud,
            pensionista:         payload.ndj_pensionista,
            grado_instruccion:   payload.ndj_grado_instruccion,
            institucion:         payload.ndj_institucion,
            carrera:             payload.ndj_carrera,
            anio_egreso:         payload.ndj_anio_egreso,
            embargos:            payload.ndj_embargos,
            cuenta_banco:        payload.ndj_cuenta_banco,
            direccion_actual:    payload.ndj_direccion_actual,
            direccion_dni:       payload.ndj_direccion_dni,
            departamento_actual: payload.ndj_departamento_actual,
            provincia_actual:    payload.ndj_provincia_actual,
            distrito_actual:     payload.ndj_distrito_actual,
            departamento_dni:    payload.ndj_departamento_dni,
            provincia_dni:       payload.ndj_provincia_dni,
            distrito_dni:        payload.ndj_distrito_dni,
            departamento_nac:    payload.ndj_departamento_nac,
            provincia_nac:       payload.ndj_provincia_nac,
            distrito_nac:        payload.ndj_distrito_nac,
            ciudad_nacimiento:   payload.ndj_ciudad_naci           || '',
            contacto_emergencia:   payload.ndj_contacto_emergencia,
            celular_emergencia:    payload.ndj_celular_emergencia,
            parentesco_emergencia: payload.ndj_parentesco_emergencia,
            ocupacion_principal:   payload.ndj_ocupacion_principal,
            experiencia_anios:     payload.ndj_experiencia_anios,
            familiar_empresa:      payload.ndj_familiar_empresa,
            familiar_nombre:       payload.ndj_familiar_nombre,
            familiar_parentesco:   payload.ndj_familiar_parentesco,
            curso_sucamec:         payload.ndj_curso_sucamec,
            sucamec_obs:           payload.ndj_sucamec_obs,
            consumo_sustancias:    payload.ndj_consumo_sustancias,
            licencia_arma:         payload.ndj_licencia_arma,
            arma_propia:           payload.ndj_arma_propia,
            brevete:               payload.ndj_brevete,
            clase_brevete:         payload.ndj_clase_brevete,
            tipo_vehiculo:         payload.ndj_tipo_vehiculo,
            vehiculo_propio:       payload.ndj_vehiculo_propio,
            empresa_anterior:      payload.ndj_empresa_anterior,
            cargo_anterior:        payload.ndj_cargo_anterior,
            duracion_anterior:     payload.ndj_duracion_anterior,
            dj2026_laboral_1:      payload.ndj_dj2026_laboral_1,
            dj2026_laboral_2:      payload.ndj_dj2026_laboral_2,
            FAM_PARENTESCO:        payload.ndj_parentesco,
            FAM_NOMBRES:           payload.ndj_apellidosNombres,
            FAM_FECHA_NACI:        payload.ndj_fechaNacimiento,
            sucursal:              payload.ndj_filtroSucursal,
            usuario:               payload.ndj_usuario,
        };

        let url;
        if (modoRecontratacion && codiPersRecontratacion) {
            url = `${VITE_URL_APP}/api/dj/save-recontratacion`;
            body.cod_postulante = codiPersRecontratacion;
        } else {
            url = `${VITE_URL_APP}/api/dj/save-nueva-dj`;
        }

        try {
            btnGuardar.disabled = true;
            const res  = await fetch(url, {
                method:  'POST',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': document.querySelector('[name=_token]')?.value || '' },
                body:    JSON.stringify(body),
            });
            const json = await res.json();

            if (json.success) {
                // ── Subir foto si hay archivo nuevo seleccionado ──
                const codiPers = json.codi_pers || body.cod_postulante || '';
                const resFoto  = await ndj_subirFoto(codiPers);

                if (!resFoto.sinFoto && !resFoto.ok) {
                    // DJ guardado OK pero foto falló
                    Swal.fire({
                        icon:  'warning',
                        title: modoRecontratacion ? '¡Recontratación exitosa!' : '¡Guardado!',
                        html:  (json.message || 'Declaración Jurada guardada correctamente.') +
                               `<br><small style="color:#b45309;">⚠️ La foto no se pudo subir: ${resFoto.message || 'Error desconocido'}</small>`,
                    });
                } else {
                    Swal.fire({
                        icon:  'success',
                        title: modoRecontratacion ? '¡Recontratación exitosa!' : '¡Guardado!',
                        text:  json.message || 'La Declaración Jurada se guardó correctamente.',
                    });
                }

                ndj_cerrar();
                if (window.getPersonalSoloDJ)          window.getPersonalSoloDJ();
                if (window.getPersonalSoloDJMigracion) window.getPersonalSoloDJMigracion();

            } else {
                Swal.fire({ icon:'error', title:'Error', text: json.message || 'Error al guardar.' });
            }
        } catch (err) {
            console.error('[NuevaDJ] Error al guardar:', err);
            Swal.fire({ icon:'error', title:'Error', text:'Error de conexión al guardar.' });
        } finally {
            btnGuardar.disabled = false;
        }
    }

    // ============================================================
    // BIND DE EVENTOS
    // ============================================================
    document.addEventListener('DOMContentLoaded', function () {

        ndj_bloquearCampos(true);
        if (alertTipoPersonal) alertTipoPersonal.style.display = 'block';

        tipoPersonalSelect?.addEventListener('change', function () {
            if (this.value && alertTipoPersonal) alertTipoPersonal.style.display = 'none';
            else if (alertTipoPersonal)          alertTipoPersonal.style.display = 'block';
        });
        $('ndj_sel_tipo_personal')?.addEventListener('change', function () { ndj_bloquearCampos(!this.value); });

        // ── Validaciones de campos ────────────────────────────

        // Caduca: desde mañana
        $('ndj_caduca')?.addEventListener('change', function () {
            console.log('Fecha caduca AQUI');
            if (!this.value) return;
            const hoy = new Date(); hoy.setHours(0,0,0,0);
            const man = new Date(hoy); man.setDate(hoy.getDate() + 1);
            console.log(new Date(this.value + 'T00:00:00') < man);
            console.log(this.value);
            console.log(man);
            console.log(new Date(this.value + 'T00:00:00'));
            if (new Date(this.value + 'T00:00:00') < man) {
                Swal.fire({ icon:'warning', title:'Fecha inválida', text:'La fecha de caducidad debe ser desde mañana en adelante.', confirmButtonText:'Entendido' });
                this.value = '';
            }
        });

        // Fecha nacimiento: anterior a hoy
        $('ndj_fecha_nacimiento')?.addEventListener('change', function () {
            if (!this.value) return;
            const hoy = new Date(); hoy.setHours(0,0,0,0);
            if (new Date(this.value + 'T00:00:00') >= hoy) {
                Swal.fire({ icon:'warning', title:'Fecha inválida', text:'La fecha de nacimiento debe ser anterior a hoy.', confirmButtonText:'Entendido' });
                this.value = '';
            }
        });

        // Celular
        $('ndj_celular')?.addEventListener('input', function () { this.value = this.value.replace(/\D/g,'').slice(0,11); });
        $('ndj_celular')?.addEventListener('blur',  function () {
            if (this.value && this.value.length < 7) {
                Swal.fire({ icon:'warning', title:'Celular inválido', text:'Debe tener al menos 7 dígitos.', confirmButtonText:'Entendido' });
                this.value = '';
            }
        });

        // WhatsApp
        $('ndj_whatsapp')?.addEventListener('input', function () { this.value = this.value.replace(/\D/g,'').slice(0,11); });
        $('ndj_whatsapp')?.addEventListener('blur',  function () {
            if (this.value && this.value.length < 7) {
                Swal.fire({ icon:'warning', title:'WhatsApp inválido', text:'Debe tener al menos 7 dígitos.', confirmButtonText:'Entendido' });
                this.value = '';
            }
        });

        // Celular emergencia
        $('ndj_celular_emergencia')?.addEventListener('input', function () { this.value = this.value.replace(/\D/g,'').slice(0,11); });
        $('ndj_celular_emergencia')?.addEventListener('blur',  function () {
            if (this.value && this.value.length < 7) {
                Swal.fire({ icon:'warning', title:'Celular emergencia inválido', text:'Debe tener al menos 7 dígitos.', confirmButtonText:'Entendido' });
                this.value = '';
            }
        });

        // Correo
        $('ndj_correo')?.addEventListener('blur', function () {
            const val = this.value.trim();
            if (!val) return;
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                Swal.fire({ icon:'warning', title:'Correo inválido', text:'Ingrese un correo válido (ejemplo@dominio.com).', confirmButtonText:'Entendido' });
                this.value = ''; this.focus();
            }
        });

        // Nombres/apellidos: solo letras
        ['ndj_nombre1','ndj_nombre2','ndj_apellido_paterno','ndj_apellido_materno'].forEach(id => {
            $(id)?.addEventListener('input', function () {
                this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
            });
        });

        // ── Foto: solo JPG, máx 1MB ───────────────────────────
        $('ndj_btnSubirFoto')?.addEventListener('click', () => $('ndj_inputFoto')?.click());
        $('ndj_inputFoto')?.addEventListener('change', function () {
            const file = this.files?.[0];
            if (!file) return;

            // Validar tipo: solo JPG/JPEG
            if (!['image/jpeg','image/jpg'].includes(file.type)) {
                Swal.fire({ icon:'warning', title:'Formato no permitido', text:'Solo se permiten imágenes en formato JPG/JPEG.', confirmButtonText:'Entendido' });
                this.value = ''; return;
            }

            // Validar tamaño: máx 1 MB
            if (file.size > 1 * 1024 * 1024) {
                Swal.fire({ icon:'warning', title:'Archivo muy grande', text:`La foto no debe superar 1 MB. El archivo pesa ${(file.size/1024/1024).toFixed(2)} MB.`, confirmButtonText:'Entendido' });
                this.value = ''; return;
            }

            // Preview
            const r = new FileReader();
            r.onload = e => {
                const prev = $('ndj_previewFoto');
                if (prev) { prev.src = e.target.result; prev.classList.remove('hidden'); }
                $('ndj_placeholderFoto')?.querySelector('i')?.classList.add('hidden');
                $('ndj_placeholderFoto')?.querySelector('span')?.classList.add('hidden');
                $('ndj_btnEliminarFoto')?.classList.remove('hidden');
            };
            r.readAsDataURL(file);
        });
        $('ndj_btnEliminarFoto')?.addEventListener('click', ndj_limpiarFoto);

        // Cerrar
        $('ndj_btnCerrar')?.addEventListener('click',  ndj_cerrar);
        $('ndj_btnCerrarX')?.addEventListener('click', ndj_cerrar);
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && !$('modalNuevaDJ')?.classList.contains('hidden')) ndj_cerrar();
        });

        // Tipo documento
        $('ndj_tipo_documento')?.addEventListener('change', function () {
            const inp = $('ndj_nro_documento'); if (!inp) return;
            inp.value = '';
            const map = {
                '0034':{ max:8,  ph:'Ej: 12345678',           mode:'numeric' },
                '0035':{ max:12, ph:'Ej: 000123456',           mode:'text'   },
                '0037':{ max:12, ph:'Ej: A1234567',            mode:'text'   },
                '0038':{ max:12, ph:'Ingrese doc. provisional',mode:'text'   },
                '0215':{ max:12, ph:'Ingrese cédula',          mode:'text'   },
                '0216':{ max:12, ph:'Ingrese carnet temporal', mode:'text'   },
            };
            const c = map[this.value] ?? { max:20, ph:'Ingrese el número', mode:'text' };
            inp.maxLength = c.max; inp.placeholder = c.ph; inp.inputMode = c.mode;
        });

        $('ndj_nro_documento')?.addEventListener('input', function () {
            if ($('ndj_tipo_documento')?.value === '0034') this.value = this.value.replace(/\D/g,'');
            else this.value = this.value.toUpperCase();
        });

        // Tipo personal
        $('ndj_sel_tipo_personal')?.addEventListener('change', function () { ndj_aplicarTipo(this.value); });

        // Institución → carrera
        $('ndj_institucion')?.addEventListener('change', function () { ndj_poblarCarreras(this.value); });

        // Ubigeos
        $('ndj_departamento_actual')?.addEventListener('change', function () { ndj_cargarProvincias(this.value,'ndj_provincia_actual','ndj_distrito_actual'); });
        $('ndj_provincia_actual')?.addEventListener('change',    function () { ndj_cargarDistritos(this.value,'ndj_distrito_actual'); });
        $('ndj_departamento_dni')?.addEventListener('change',    function () { ndj_cargarProvincias(this.value,'ndj_provincia_dni','ndj_distrito_dni'); });
        $('ndj_provincia_dni')?.addEventListener('change',       function () { ndj_cargarDistritos(this.value,'ndj_distrito_dni'); });
        $('ndj_departamento_nac')?.addEventListener('change',    function () { ndj_cargarProvincias(this.value,'ndj_provincia_nac','ndj_distrito_nac'); });
        $('ndj_provincia_nac')?.addEventListener('change',       function () { ndj_cargarDistritos(this.value,'ndj_distrito_nac'); });

        // Familiar empresa / SUCAMEC / Clase brevete
        $('ndj_familiar_empresa')?.addEventListener('change', function () { $('ndj_div_familiar_interno')?.classList.toggle('hidden', this.value !== 'SI'); });
        $('ndj_curso_sucamec')?.addEventListener('change',    function () { $('ndj_div_sucamec_obs')?.classList.toggle('hidden', this.value !== 'SI'); });
        $('ndj_clase_brevete')?.addEventListener('change', function () {
            const sel = $('ndj_tipo_vehiculo'); if (!sel) return;
            sel.innerHTML = '<option value="">-- Seleccione --</option>';
            (NDJ_CAT[this.value] || []).forEach(item => { const o = document.createElement('option'); o.value = item.val; o.textContent = item.text; sel.appendChild(o); });
        });

        // Familiares
        $('ndj_addFamilyMember')?.addEventListener('click', () => { $('ndj_familyContainer')?.appendChild(ndj_crearFila()); });
        $('ndj_familyContainer')?.addEventListener('click', e => {
            const btn = e.target.closest('.ndj-remove-family');
            if (btn) btn.closest('.ndj-family-row')?.remove();
        });

        // DNI y coincidencias
        tipoDocInput?.addEventListener('change', validarDocumento);
        docInput?.addEventListener('input',      validarDocumento);
        [nombre1, nombre2, apellido1, apellido2].forEach(input => { input?.addEventListener('input', buscarCoincidencias); });

        // Guardar
        $('ndj_btnGuardar')?.addEventListener('click', ndj_guardar);

        // API pública
        window.NuevaDJ = { abrir: ndj_abrir, cerrar: ndj_cerrar };
    });

})();