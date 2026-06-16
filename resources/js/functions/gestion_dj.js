// ============================================================
// gestion_dj.js — Lógica principal del módulo Gestión DJ
// ============================================================
// PDF separado en: ./dj_pdf.js
// ============================================================
import axios from 'axios';
import Swal from 'sweetalert2';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Tagify from '@yaireo/tagify';
import '@yaireo/tagify/dist/tagify.css';

import { generarDeclaracionJuradaPDF, generarReporteFaltantesPDF } from './dj_pdf.js';

const API_URL = `${VITE_URL_APP}/api`;
let registroSeleccionado = null;

const categoriasSe = {
    'A': [
        { val: 'A-I', text: 'A-I: Particulares' },
        { val: 'A-IIa', text: 'A-IIa: Taxi / Ambulancia' },
        { val: 'A-IIb', text: 'A-IIb: Microbús / Pickup' },
        { val: 'A-IIIa', text: 'A-IIIa: Ómnibus' },
        { val: 'A-IIIb', text: 'A-IIIb: Camiones' },
        { val: 'A-IIIc', text: 'A-IIIc: Todos los anteriores' }
    ],
    'B': [
        { val: 'B-IIa', text: 'B-IIa: Bicimotos' },
        { val: 'B-IIb', text: 'B-IIb: Motocicletas' },
        { val: 'B-IIc', text: 'B-IIc: Mototaxis' }
    ]
};

const PAUSA_ENTRE_REGISTROS = 800;

async function esperarConBackoff(intento, baseMs = 1000) {
    const espera = baseMs * Math.pow(2, intento); // 1s, 2s, 4s, 8s...
    const jitter = Math.random() * 300;           // evita que todo reintente al mismo tiempo
    await new Promise(r => setTimeout(r, espera + jitter));
}

// ③ Wrapper para obtener datos con retry ante 429
async function obtenerDatosConRetry(codiPers, source = 'migracion', maxReintentos = 4) {
    for (let intento = 0; intento <= maxReintentos; intento++) {
        try {
            const response = await axios.get(`${API_URL}/dj/get-personal-data`, {
                params: { codi_pers: codiPers, source }
            });
            return response.data;
        } catch (err) {
            const status = err?.response?.status;

            if (status === 429 && intento < maxReintentos) {
                // Leer el header Retry-After si el servidor lo manda
                const retryAfter = err.response?.headers?.['retry-after'];
                const esperaMs = retryAfter
                    ? parseInt(retryAfter) * 1000
                    : null;

                console.warn(`[429] ${codiPers} — reintento ${intento + 1}/${maxReintentos}`);

                if (esperaMs) {
                    await new Promise(r => setTimeout(r, esperaMs + 200));
                } else {
                    await esperarConBackoff(intento);
                }
                continue;
            }

            // Si no es 429 o se agotaron reintentos, propagar el error
            throw err;
        }
    }
}


function actualizarCategorias() {
    const claseSel = document.getElementById('clase_brevete').value;
    const catSelect = document.getElementById('tipo_vehiculo');

    // Limpiar opciones previas
    catSelect.innerHTML = '<option value="">-- Seleccione Categoría --</option>';

    if (claseSel && categoriasSe[claseSel]) {
        categoriasSe[claseSel].forEach(item => {
            let opt = document.createElement('option');
            opt.value = item.val;
            opt.textContent = item.text;
            catSelect.appendChild(opt);
        });
    }
}


function marcarDJGeneradosBatch(items) {
    const data = getDJGenerados();

    items.forEach(({ codPersonal, fechaCambio }) => {
        data[codPersonal] = {
            fechaMarcado: new Date().toISOString(),
            fechaCambio: fechaCambio || null,
        };
    });

    localStorage.setItem(DJ_STORAGE_KEY, JSON.stringify(data));
}

// ============================================================
// DOCUMENT READY
// ============================================================
document.addEventListener('DOMContentLoaded', function () {

    document.getElementById('clase_brevete').addEventListener('change', actualizarCategorias);

    // ── Referencias DOM ──────────────────────────────────────
    const modalDjGestion = document.getElementById('modalDjGestion');
    const form = document.getElementById('formDatos');
    const buscarPersonalInput = document.getElementById("buscarPersonal");
    const btnNuevaDJ = document.getElementById('btnNuevaDJ');
    const cerrarModalBtn = document.getElementById('cerrarModal');
    const btnPrevisualizar = document.getElementById("btnPrevisualizar");
    const pageSizeSelect = document.getElementById("page-size");
    const pageSizeMigradoSelect = document.getElementById("page-size-migrado");

    // Pestañas
    const tabBtnPendiente = document.getElementById('tabBtnPendiente');
    const tabBtnMigrado = document.getElementById('tabBtnMigrado');
    const panelPendiente = document.getElementById('panelPendiente');
    const panelMigrado = document.getElementById('panelMigrado');

    // Familia
    const container = document.getElementById('familyContainer');
    const addBtn = document.getElementById('addFamilyMember');

    // Foto
    const inputFoto = document.getElementById("inputFoto");
    const preview = document.getElementById("previewFoto");
    const placeholder = document.getElementById("placeholderFoto");
    const btnSubir = document.getElementById("btnSubirFoto");
    const btnEliminar = document.getElementById("btnEliminarFoto");

    // SUCAMEC
    const cursoSucamec = document.getElementById("curso_sucamec");
    const institucionContainer = document.getElementById("institucion_container");
    const institucionInput = document.getElementById("institucion_laboral");

    // Ubigeos
    const departamentoSelect = document.getElementById("departamento_actual");
    const provinciaSelect = document.getElementById("provincia_actual");
    const distritoSelect = document.getElementById("distrito_actual");

    const departamentoSelectDni = document.getElementById("departamento_dni");
    const provinciaSelectDni = document.getElementById("provincia_dni");
    const distritoSelectDni = document.getElementById("distrito_dni");

    const departamentoSelectNac = document.getElementById("departamento_nac");
    const provinciaSelectNac = document.getElementById("provincia_nac");
    const distritoSelectNac = document.getElementById("distrito_nac");

    // Campos validación PDF
    const nombreDJtxt = document.getElementById("nombres_apellidos");
    const dniDJtxt = document.getElementById("dni");

    // Tagify licencia
    const inputLicencia = document.getElementById("licencia_arma");
    // const tagifyLicencia = inputLicencia ? new Tagify(inputLicencia, { maxTags: 2 }) : null;

    const API_BASE = `${VITE_URL_APP}/api/ubicacion`;

    // ============================================================
    // TABLAS TABULATOR
    // ============================================================

    // ── Tabla 1: Pendientes/Listos ───────────────────────────
    const tblPersonas = new Tabulator("#tblPersonas", {
        height: "100%",
        layout: "fitColumns",
        responsiveLayout: "collapse",
        pagination: true,
        paginationSize: 20,
        rowHeader: { formatter: "responsiveCollapse", width: 30, minWidth: 30, hozAlign: "center", resizable: false, headerSort: false },
        locale: "es",
        langs: {
            "es": {
                pagination: { first: "Primero", first_title: "Primera Página", last: "Final", last_title: "Última Página", prev: "<", prev_title: "Página Anterior", next: ">", next_title: "Página Siguiente", all: "Todo" },
                headerFilters: { default: "Filtrar..." },
                ajax: { loading: "Cargando datos...", error: "Error al cargar datos" },
                data: { empty: "No hay datos disponibles" }
            }
        },
        columns: [
            { title: "N°", formatter: "rownum", hozAlign: "center", width: 60 },
            {
                title: "Apellidos", field: "apellidos", hozAlign: "left", widthGrow: 2,
                formatter: cell => { const d = cell.getData(); return `${d.apellido1 ?? ''} ${d.apellido2 ?? ''}`.trim(); }
            },
            {
                title: "Nombres", field: "nombres", hozAlign: "left", widthGrow: 2,
                formatter: cell => { const d = cell.getData(); return `${d.nombres ?? ''} `.trim(); }
            },

            { title: "DNI", field: "dni", hozAlign: "center", widthGrow: 2 },
            { title: "Sucursal", field: "sucursal", hozAlign: "center", widthGrow: 2 },
            {
                title: "Tipo", field: "tipoPer", hozAlign: "center", widthGrow: 2,
                formatter: cell => {
                    const val = cell.getValue() ?? '';
                    let color = '';
                    if (val.includes('OPERATIVO')) {
                        color = 'border-blue-300 bg-blue-100 text-blue-800';
                    } else if (val.includes('ADMINISTRATIVO')) {
                        color = 'border-purple-300 bg-purple-100 text-purple-800';
                    } else if (val.includes('ESPECIAL')) {
                        color = 'border-orange-300 bg-orange-100 text-orange-800';
                    } else {
                        color = 'border-gray-300 bg-gray-100 text-gray-800';
                    }
                    return val ? `<span class="inline-flex items-center rounded-full border ${color} px-3 py-1 text-sm font-medium whitespace-nowrap">${capitalizeWords(val)}</span>` : '';
                }
            },
            {
                title: "Ultimo Cambio", field: "cambio", hozAlign: "center", widthGrow: 3,
                formatter: cell => {
                    const d = cell.getData();
                    if (d.cambio != null) {
                        return `<div class="flex items-center justify-center gap-3 text-sm text-gray-700">
                            <span class="flex items-center gap-1"><i class='bx bx-calendar'></i> <span>${formatearFechaHora(d.cambio).fecha}</span></span>
                            <span class="flex items-center gap-1"><i class='bx bx-time-five'></i> <span>${formatearFechaHora(d.cambio).hora}</span></span>
                        </div>`.trim();
                    }
                    return `${d.cambio ?? 'Sin cambios'}`.trim();
                }
            },
            {
                title: "Acciones", field: "acciones", hozAlign: "center", headerSort: false, widthGrow: 2,
                formatter: cell => {
                    const d = cell.getData();
                    const btnDJ = `<button type="button" class="btn rounded-full form-btn bg-success/25 text-success hover:bg-success hover:text-white">DJ</button>`;
                    const btnPDF = `<button type="button" class="btn rounded-full form-btn bg-info/25 text-info hover:bg-info hover:text-white ms-1" title="previsualizar"><i class='bx bxs-file-pdf'></i></button>`;
                    return d.estado === 'pendiente' ? btnDJ : btnDJ /*+ btnPDF*/;
                },
                cellClick: (e, cell) => {
                    const btn = e.target.closest('.form-btn');
                    if (!btn) return;
                    registroSeleccionado = cell.getRow().getData();
                    registroSeleccionado._sinSplit = true;

                    const codiPers = registroSeleccionado.codPersonal || registroSeleccionado.CODI_PERS || registroSeleccionado.id;

                    // Limpiar caché solo de esta persona
                    personalDataCache.delete(`${codiPers}_pendiente`);
                    personalDataCache.delete(`${codiPers}_migracion`);

                    //btnNuevaDJ?.click();
                    abrirFormularioDJ(codiPers, 'pendiente');
                }
            },
        ],
    });

    // ── Tabla 2: Migración ───────────────────────────────────
    const tblPersonasMigrado = new Tabulator("#tblPersonasMigrado", {
        height: "100%",
        layout: "fitColumns",
        responsiveLayout: "collapse",
        pagination: true,
        paginationSize: 20,
        rowHeader: { formatter: "responsiveCollapse", width: 30, minWidth: 30, hozAlign: "center", resizable: false, headerSort: false },
        locale: "es",
        langs: {
            "es": {
                pagination: { first: "Primero", first_title: "Primera Página", last: "Final", last_title: "Última Página", prev: "<", prev_title: "Página Anterior", next: ">", next_title: "Página Siguiente", all: "Todo" },
                headerFilters: { default: "Filtrar..." },
                ajax: { loading: "Cargando datos...", error: "Error al cargar datos" },
                data: { empty: "No hay datos disponibles" }
            }
        },
        columns: [
            { title: "N°", formatter: "rownum", hozAlign: "center", width: 60 },
            {
                title: "Apellidos", field: "apellidos", hozAlign: "left", widthGrow: 2,
                formatter: cell => { const d = cell.getData(); return `${d.apellido1 ?? ''} ${d.apellido2 ?? ''}`.trim(); }
            },
            {
                title: "Nombres", field: "nombres", hozAlign: "left", widthGrow: 2,
                formatter: cell => { const d = cell.getData(); return `${d.nombres ?? ''} `.trim(); }
            },
            { title: "DNI", field: "dni", hozAlign: "center", widthGrow: 2 },
            { title: "Sucursal", field: "sucursal", hozAlign: "center", widthGrow: 2 },
            {
                title: "Tipo", field: "tipoPer", hozAlign: "center", widthGrow: 2,
                formatter: cell => {
                    const val = cell.getValue() ?? '';
                    const color = val === 'OPERATIVO' ? 'border-blue-300 bg-blue-100 text-blue-800' : 'border-purple-300 bg-purple-100 text-purple-800';
                    return val ? `<span class="inline-flex items-center rounded-full border ${color} px-3 py-1 text-sm font-medium">${capitalizeWords(val)}</span>` : '';
                }
            },
            {
                title: "Migrado", field: "migrado", hozAlign: "center", widthGrow: 2,
                formatter: cell => {
                    const d = cell.getData();
                    const color = d.migrado === 'Migrado' ? 'border-success bg-success text-white' : 'border-dark-100 bg-dark-100 text-yellow-800';
                    return `<span class="inline-flex items-center rounded-full border ${color} px-3 py-1 text-sm font-medium">${capitalizeWords(d.migrado ?? '')}</span>`.trim();
                }
            },
            {
                title: "Ultimo Cambio", field: "cambio", hozAlign: "center", widthGrow: 3,
                formatter: cell => {
                    const d = cell.getData();
                    if (d.cambio != null) {
                        return `<div class="flex items-center justify-center gap-3 text-sm text-gray-700">
                            <span class="flex items-center gap-1"><i class='bx bx-calendar'></i> <span>${formatearFechaHora(d.cambio).fecha}</span></span>
                            <span class="flex items-center gap-1"><i class='bx bx-time-five'></i> <span>${formatearFechaHora(d.cambio).hora}</span></span>
                        </div>`.trim();
                    }
                    return `${d.cambio ?? 'Sin cambios'}`.trim();
                }
            },
            // ── NUEVA COLUMNA ──────────────────────────────────────
            {
                title: "PDF", field: "pdf_generado", hozAlign: "center", widthGrow: 1,
                headerSort: false,
                formatter: cell => {
                    const d = cell.getData();
                    const cod = d.codPersonal || d.CODI_PERS || d.id;
                    const gen = estaGenerado(cod, d.cambio);
                    const titulo = gen ? 'Generado — click para resetear' : 'Pendiente';
                    const color = gen
                        ? 'color:#16a34a;font-size:18px;cursor:pointer;'
                        : 'color:#d1d5db;font-size:18px;cursor:default;';
                    return `<span title="${titulo}" style="${color}" data-pdf-cod="${cod}" data-pdf-cambio="${d.cambio || ''}">
                                ${gen ? '✅' : '○'}
                            </span>`;
                },
                cellClick: (e, cell) => {
                    const span = e.target.closest('[data-pdf-cod]');
                    if (!span) return;
                    const cod = span.getAttribute('data-pdf-cod');
                    if (!estaGenerado(cod, span.getAttribute('data-pdf-cambio'))) return;

                    Swal.fire({
                        icon: 'question',
                        title: '¿Resetear marca?',
                        text: 'Se marcará este registro como pendiente de generar PDF.',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, resetear',
                        cancelButtonText: 'Cancelar',
                    }).then(r => {
                        if (!r.isConfirmed) return;
                        desmarcarDJGenerado(cod);
                        cell.getTable().updateOrAddData([{ ...cell.getData() }]);
                        // Forzar re-render de la fila
                        cell.getRow().reformat();
                    });
                }
            },
            // ── FIN NUEVA COLUMNA ──────────────────────────────────
            {
                title: "Acciones", field: "acciones", hozAlign: "center", headerSort: false, widthGrow: 2,
                formatter: cell => {
                    const d = cell.getData();
                    const disabled = d.migrado === 'Migrado' ? 'disabled' : '';
                    return `<button ${disabled} type="button" class="btn rounded-full form-btn-migrado bg-success/25 text-success hover:bg-success hover:text-white" data-hs-overlay="#modalDjGestion">DJ</button>`;
                },
                cellClick: (e, cell) => {
                    const btn = e.target.closest('.form-btn-migrado');
                    if (!btn) return;
                    const rowData = cell.getRow().getData();
                    const codiPers = rowData.codPersonal || rowData.CODI_PERS || rowData.id;
                    personalDataCache.delete(`${codiPers}_pendiente`);
                    personalDataCache.delete(`${codiPers}_migracion`);
                    abrirFormularioDJ(codiPers);
                }
            },
        ],
    });

    // ── Tabla coincidencias ──────────────────────────────────
    const tblPersonasCN = new Tabulator("#tblPersonasCN", {
        height: "100%",
        layout: "fitDataFill",
        responsiveLayout: "collapse",
        columns: [
            { title: "Código", field: "CODI_PERS", hozAlign: "center", width: '10%' },
            { title: "Personal", field: "personal", hozAlign: "left", width: '30%' },
            { title: "Nro Documento", field: "nroDoc", hozAlign: "center", width: '15%' },
            { title: "Sucursal", field: "sucursal", hozAlign: "center", width: '18%' },
        ],
    });

    // ============================================================
    // HELPERS INTERNOS
    // ============================================================
    function getValue(id) {
        const el = document.getElementById(id);
        return el ? (el.value || '') : '';
    }

    function formatearFechaHora(fechaStr) {
        const fecha = new Date(fechaStr);
        return {
            fecha: `${String(fecha.getDate()).padStart(2, '0')}/${String(fecha.getMonth() + 1).padStart(2, '0')}/${fecha.getFullYear()}`,
            hora: `${String(fecha.getHours()).padStart(2, '0')}:${String(fecha.getMinutes()).padStart(2, '0')}`
        };
    }

    function capitalizeWords(texto) {
        return texto.toLowerCase().split(" ").map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(" ");
    }

    function limpiarPreviewFoto() {
        if (inputFoto) inputFoto.value = "";
        if (preview) { preview.src = ""; preview.classList.add("hidden"); }
        if (placeholder) placeholder.classList.remove("hidden");
        if (btnEliminar) btnEliminar.classList.add("hidden");
    }

    function actualizarInstitucionVisibility() {
        if (!cursoSucamec || !institucionContainer || !institucionInput) return;
        if (cursoSucamec.value === "SI") {
            institucionContainer.classList.remove("hidden");
        } else {
            institucionContainer.classList.add("hidden");
            institucionInput.value = "";
        }
    }

    function makeFamilyRow() {
        return `
        <div class="family-row grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border rounded-lg relative" data-familia-row>
            <div>
                <label class="text-sm font-medium inline-block mb-2">Parentesco</label>
                <select name="parentesco[]" class="form-select w-full">
                    <option value="">Seleccionar</option>
                    <option value="PADRE">Padre</option>    <option value="MADRE">Madre</option>
                    <option value="CONYUGE">Conyuge</option>  
                    <option value="HIJO">Hijo(a)</option>     
                  
                   
                </select>
            </div>
            <div>
                <label class="text-sm font-medium inline-block mb-2">Apellidos y Nombres</label>
                <input type="text" name="apellidosNombres[]" class="form-input w-full" placeholder="Apellidos y nombres completos">
            </div>
            <div class="flex gap-2 items-end">
                <div class="flex-1">
                    <label class="text-sm font-medium inline-block mb-2">Fecha Nacimiento</label>
                    <input type="date" name="fechaNacimiento[]" class="form-input w-full">
                </div>
                <button type="button" class="remove-family self-end px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200">Eliminar</button>
            </div>
        </div>`;
        // return `
        // <div class="family-row grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border rounded-lg relative" data-familia-row>
        //     <div>
        //         <label class="text-sm font-medium inline-block mb-2">Parentesco</label>
        //         <select name="parentesco[]" class="form-select w-full">
        //             <option value="">Seleccionar</option>
        //             <option value="PADRE">Padre</option>    <option value="MADRE">Madre</option>
        //             <option value="ESPOSO">Esposo</option>  <option value="ESPOSA">Esposa</option>
        //             <option value="HIJO">Hijo</option>      <option value="HIJA">Hija</option>
        //             <option value="HERMANO">Hermano</option><option value="HERMANA">Hermana</option>
        //             <option value="ABUELO">Abuelo</option>  <option value="ABUELA">Abuela</option>
        //         </select>
        //     </div>
        //     <div>
        //         <label class="text-sm font-medium inline-block mb-2">Apellidos y Nombres</label>
        //         <input type="text" name="apellidosNombres[]" class="form-input w-full" placeholder="Apellidos y nombres completos">
        //     </div>
        //     <div class="flex gap-2 items-end">
        //         <div class="flex-1">
        //             <label class="text-sm font-medium inline-block mb-2">Fecha Nacimiento</label>
        //             <input type="date" name="fechaNacimiento[]" class="form-input w-full">
        //         </div>
        //         <button type="button" class="remove-family self-end px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200">Eliminar</button>
        //     </div>
        // </div>`;
    }

    function limpiarFormulario() {
        if (form) form.reset();
        //if (tagifyLicencia) tagifyLicencia.removeAllTags();

        limpiarPreviewFoto();

        if (container) { container.innerHTML = ''; container.insertAdjacentHTML('beforeend', makeFamilyRow()); }
        if (institucionContainer) institucionContainer.classList.add("hidden");
        if (institucionInput) institucionInput.value = "";

        if (provinciaSelect) provinciaSelect.innerHTML = '<option value="">Seleccionar</option>';
        if (distritoSelect) distritoSelect.innerHTML = '<option value="">Seleccionar</option>';

        if (provinciaSelectDni) provinciaSelectDni.innerHTML = '<option value="">Seleccionar</option>';
        if (distritoSelectDni) distritoSelectDni.innerHTML = '<option value="">Seleccionar</option>';

        if (provinciaSelectNac) provinciaSelectNac.innerHTML = '<option value="">Seleccionar</option>';
        if (distritoSelectNac) distritoSelectNac.innerHTML = '<option value="">Seleccionar</option>';

        setValue('dj2026_laboral_1', '');
        setValue('dj2026_laboral_2', '');
        limpiarSplitView();

        document.querySelectorAll('[data-tipo]').forEach(el => { el.style.display = ''; });
        const badgeLimp = document.getElementById('tipoBadgeModal');
        if (badgeLimp) badgeLimp.textContent = '';
    }

    function resaltarTexto(tabla, valor) {
        tabla.getRows().forEach(row => {
            row.getElement().querySelectorAll(".tabulator-cell").forEach((cell, i, cells) => {
                const field = cell.getAttribute('tabulator-field');
                if (i === cells.length - 1 || field === 'migrado' || field === 'estado' || field === 'tipoPer' || field === 'cambio') return;
                const text = cell.textContent || '';
                if (valor && text.toLowerCase().includes(valor)) {
                    const escaped = valor.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    cell.innerHTML = text.replace(new RegExp(`(${escaped})`, "gi"), "<span class='bg-warning/25'>$1</span>");
                } else {
                    cell.innerHTML = text;
                }
            });
        });
    }

    // ============================================================
    // UBIGEOS
    // ============================================================
    async function cargarProvincias(selectProv, selectDist, departamentoId, selectedProvincia = null, selectedDistrito = null) {
        if (!selectProv || !selectDist) return;
        selectProv.innerHTML = '<option value="">Seleccionar</option>';
        selectDist.innerHTML = '<option value="">Seleccionar</option>';
        if (!departamentoId) return;
        try {
            const response = await axios.get(`${API_BASE}/provincias/${departamentoId}`);
            response.data.forEach(prov => selectProv.add(new Option(prov.provi_descripcion, prov.provi_codigo)));
            if (selectedProvincia) {
                selectProv.value = selectedProvincia;
                await cargarDistritos(selectDist, selectedProvincia, selectedDistrito);
            }
        } catch (error) { console.error("Error cargando provincias:", error); }
    }

    async function cargarDistritos(selectDist, provinciaId, selectedDistrito = null) {
        if (!selectDist) return;
        selectDist.innerHTML = '<option value="">Seleccionar</option>';
        if (!provinciaId) return;
        try {
            const response = await axios.get(`${API_BASE}/distritos/${provinciaId}`);
            response.data.forEach(dist => selectDist.add(new Option(dist.dist_descripcion, dist.dist_codigo)));
            if (selectedDistrito) selectDist.value = selectedDistrito;
        } catch (error) { console.error("Error cargando distritos:", error); }
    }

    if (departamentoSelect && departamentoSelectDni && departamentoSelectNac) {
        axios.get(`${API_BASE}/departamentos`)
            .then(response => {
                response.data.forEach(dep => {
                    departamentoSelect.add(new Option(dep.depa_descripcion, dep.depa_codigo));
                    departamentoSelectDni.add(new Option(dep.depa_descripcion, dep.depa_codigo));
                    departamentoSelectNac.add(new Option(dep.depa_descripcion, dep.depa_codigo));
                });
            })
            .catch(error => console.error("Error cargando departamentos:", error));
    }



    departamentoSelect?.addEventListener("change", async function () { await cargarProvincias(provinciaSelect, distritoSelect, this.value); });
    provinciaSelect?.addEventListener("change", async function () { await cargarDistritos(distritoSelect, this.value); });

    departamentoSelectDni?.addEventListener("change", async function () { await cargarProvincias(provinciaSelectDni, distritoSelectDni, this.value); });
    provinciaSelectDni?.addEventListener("change", async function () { await cargarDistritos(distritoSelectDni, this.value); });

    departamentoSelectNac?.addEventListener("change", async function () { await cargarProvincias(provinciaSelectNac, distritoSelectNac, this.value); });
    provinciaSelectNac?.addEventListener("change", async function () { await cargarDistritos(distritoSelectNac, this.value); });

    // ============================================================
    // CARGA DE DATOS (API)
    // ============================================================
    function getPersonal() {

        axios.get(`${VITE_URL_APP}/get-personal-dj`)
            .then(response => {
                const datosTabla = response.data;
                tblPersonas.setData(datosTabla);
                // const sucursales = [...new Map(datosTabla.filter(d => d.sucursal).map(d => [d.codSucursal, { cod: d.codSucursal, nombre: d.sucursal }])).values()];
                // const filtroSucursal = document.getElementById('filtroSucursalPEN');
                // if (filtroSucursal) {
                //     filtroSucursal.innerHTML = '<option value="">Todas</option>';
                //     sucursales.sort((a, b) => a.nombre.localeCompare(b.nombre)).forEach(s => filtroSucursal.add(new Option(s.nombre, s.cod)));
                // }
                aplicarFiltrosPEN();
            })
            .catch(error => console.error("Hubo un error:", error));
    }


    window.getPersonalSoloDJ = function () {

        axios.get(`${VITE_URL_APP}/get-personal-dj`)
            .then(response => {
                return tblPersonas.setData(response.data);
            })
            .then(() => aplicarFiltrosPEN())
            .catch(console.error);
    };

    function getPersonalMigracion() {
        axios.get(`${VITE_URL_APP}/api/get-personal-dj-migracion`)
            .then(response => {
                const datosTabla = response.data;
                tblPersonasMigrado.setData(datosTabla);
                // const sucursales = [...new Map(datosTabla.filter(d => d.sucursal).map(d => [d.codSucursal, { cod: d.codSucursal, nombre: d.sucursal }])).values()];
                // const filtroSucursal = document.getElementById('filtroSucursal');
                // if (filtroSucursal) {
                //     filtroSucursal.innerHTML = '<option value="">Todas</option>';
                //     sucursales.sort((a, b) => a.nombre.localeCompare(b.nombre)).forEach(s => filtroSucursal.add(new Option(s.nombre, s.cod)));
                // }
                aplicarFiltrosMigracion();  // ← agrega esta línea al final
                actualizarCardDesdeSP();
            })
            .catch(error => console.error("Hubo un error:", error));
    }


    window.getPersonalSoloDJMigracion = function () {

        axios.get(`${VITE_URL_APP}/api/get-personal-dj-migracion`)
            .then(response => {
                const datosTabla = response.data;
                tblPersonasMigrado.setData(datosTabla);
                // const sucursales = [...new Map(datosTabla.filter(d => d.sucursal).map(d => [d.codSucursal, { cod: d.codSucursal, nombre: d.sucursal }])).values()];
                // const filtroSucursal = document.getElementById('filtroSucursal');
                // if (filtroSucursal) {
                //     filtroSucursal.innerHTML = '<option value="">Todas</option>';
                //     sucursales.sort((a, b) => a.nombre.localeCompare(b.nombre)).forEach(s => filtroSucursal.add(new Option(s.nombre, s.cod)));
                // }
                aplicarFiltrosMigracion();  // ← agrega esta línea al final
                actualizarCardDesdeSP();
            })
            .catch(error => console.error("Hubo un error:", error));
    }


    function actualizarCardDesdeSP(sucursal = '', tipoPer = '') {
        const codSucursal = sucursal || '00';
        let codTipoPer = '00';

        if (tipoPer === 'OPERATIVO 4°') codTipoPer = '01';
        else if (tipoPer === 'OPERATIVO 5°') codTipoPer = '03';
        else if (tipoPer === 'ADMINISTRATIVO 4°') codTipoPer = '02';
        else if (tipoPer === 'ADMINISTRATIVO 5°') codTipoPer = '05';
        else if (tipoPer === 'ESPECIAL') codTipoPer = '06';

        axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion`, { params: { codSucursal, codTipoPer } })
            .then(response => {
                if (!response.data.success) return;
                const datos = response.data.data;
                const listos = datos.filter(d => d.SIP_CAMBIO === 'Ok').length;
                const total = datos.length;
                const elFilt = document.getElementById('contadorFiltrado');
                const elTot = document.getElementById('contadorTotal');
                animarContador(elFilt, parseInt(elFilt?.textContent.replace(/,/g, '')) || 0, listos);
                animarContador(elTot, parseInt(elTot?.textContent.replace(/,/g, '')) || 0, total);
            })
            .catch(err => console.error('Error card SP:', err));
    }


    function matchBusqueda(data, texto) {
        const palabras = texto.toLowerCase().split(/\s+/).filter(p => p);
        const campos = [
            (data.nombres   ?? '').toLowerCase(),
            (data.apellido1 ?? '').toLowerCase(),
            (data.apellido2 ?? '').toLowerCase(),
            (data.dni       ?? '').toLowerCase(),
        ];
        return palabras.every(palabra => campos.some(campo => campo.includes(palabra)));
    }

    // ============================================================
    // FILTROS
    // ============================================================
    function aplicarFiltrosMigracion() {
        const sucursal = document.getElementById('filtroSucursal')?.value ?? '';
        const tipoPer = document.getElementById('filtroTipoPer')?.value ?? '';
        const filtros = [];
        if (sucursal) filtros.push({ field: "codSucursal", type: "=", value: sucursal });
        if (tipoPer) filtros.push({ field: "tipoPer", type: "=", value: tipoPer });
        const texto = buscarPersonalInput?.value.toLowerCase().trim() ?? '';
        if (texto) filtros.push([{ field: "nombres", type: "like", value: texto }, { field: "dni", type: "like", value: texto }]);
        tblPersonasMigrado.setFilter(filtros);
        actualizarCardDesdeSP(sucursal, tipoPer);
    }

    function aplicarFiltrosPEN() {
        const sucursal = document.getElementById('filtroSucursalPEN')?.value ?? '';
        const tipoPer  = document.getElementById('filtroTipoPerPEN')?.value ?? '';
        const texto    = buscarPersonalInput?.value.toLowerCase().trim() ?? '';

        tblPersonas.setFilter((data) => {
            if (sucursal && data.codSucursal !== sucursal) return false;
            if (tipoPer  && data.tipoPer     !== tipoPer)  return false;
            if (texto    && !matchBusqueda(data, texto))   return false;
            return true;
        });
    }

    document.getElementById('filtroSucursal')?.addEventListener('change', aplicarFiltrosMigracion);
    document.getElementById('filtroTipoPer')?.addEventListener('change', aplicarFiltrosMigracion);
    document.getElementById('filtroSucursalPEN')?.addEventListener('change', aplicarFiltrosPEN);
    document.getElementById('filtroTipoPerPEN')?.addEventListener('change', aplicarFiltrosPEN);

    getPersonalMigracion();
    getPersonal();

    // ============================================================
    // PESTAÑAS
    // ============================================================
    let tabActiva = 'pendiente';

    function activarTab(tab) {
        tabActiva = tab;

        // Título dinámico
        const tituloEl = document.getElementById('tituloTabActiva');
        const titulos = { pendiente: 'DJ Listos', migrado: 'Migración' };
        if (tituloEl) {
            tituloEl.innerHTML = `<span class="font-medium text-primary">${titulos[tab]}</span>`;
        }

        if (tab === 'pendiente') {
            panelPendiente?.classList.remove('hidden');
            panelMigrado?.classList.add('hidden');

            tabBtnPendiente?.classList.add('bg-white', 'text-primary', 'border-gray-200', 'border-b-white');
            tabBtnPendiente?.classList.remove('bg-gray-50', 'text-gray-500', 'border-transparent');
            tabBtnMigrado?.classList.add('bg-gray-50', 'text-gray-500', 'border-transparent');
            tabBtnMigrado?.classList.remove('bg-white', 'text-primary', 'border-gray-200', 'border-b-white');

        } else {
            panelPendiente?.classList.add('hidden');
            panelMigrado?.classList.remove('hidden');

            tabBtnMigrado?.classList.add('bg-white', 'text-primary', 'border-gray-200', 'border-b-white');
            tabBtnMigrado?.classList.remove('bg-gray-50', 'text-gray-500', 'border-transparent');
            tabBtnPendiente?.classList.add('bg-gray-50', 'text-gray-500', 'border-transparent');
            tabBtnPendiente?.classList.remove('bg-white', 'text-primary', 'border-gray-200', 'border-b-white');

            tblPersonasMigrado.redraw(true);
        }
    }

    activarTab('pendiente');

    tabBtnPendiente?.addEventListener('click', () => activarTab('pendiente'));
    tabBtnMigrado?.addEventListener('click', () => activarTab('migrado'));

    // ============================================================
    // BÚSQUEDA Y RESALTADO
    // ============================================================
    buscarPersonalInput?.addEventListener("keyup", function () {
        const valor = this.value.toLowerCase().trim();
        if (tabActiva === 'pendiente') {
            aplicarFiltrosPEN();
            tblPersonas._ultimoFiltro = valor;
            setTimeout(() => resaltarTexto(tblPersonas, valor), 10);
        } else {
            tblPersonasMigrado._ultimoFiltro = valor;
            aplicarFiltrosMigracion();
            setTimeout(() => resaltarTexto(tblPersonasMigrado, valor), 10);
        }
    });

    tblPersonas.on("renderComplete", () => { if (tblPersonas._ultimoFiltro) resaltarTexto(tblPersonas, tblPersonas._ultimoFiltro); });
    tblPersonasMigrado.on("renderComplete", () => { if (tblPersonasMigrado._ultimoFiltro) resaltarTexto(tblPersonasMigrado, tblPersonasMigrado._ultimoFiltro); });

    // ============================================================
    // BOTONES MODAL
    // ============================================================
    // btnNuevaDJ?.addEventListener('click', async function () {
    //     const { value: tipoCod, isConfirmed } = await Swal.fire({
    //         title: 'Nueva Declaración Jurada',
    //         text: 'Selecciona el tipo de personal:',
    //         input: 'radio',
    //         inputOptions: {
    //             '03': 'Operativo',
    //             '05': 'Administrativo',
    //         },
    //         inputValidator: (value) => !value && 'Debes seleccionar un tipo.',
    //         showCancelButton: true,
    //         confirmButtonText: 'Continuar',
    //         cancelButtonText: 'Cancelar',
    //     });

    //     if (!isConfirmed || !tipoCod) return;

    //     // Abrir modal con catálogos cargados
    //     await abrirFormularioDJ(null);

    //     // Setear tipo DESPUÉS de que el modal esté abierto
    //     setTimeout(() => {
    //         setValue('tipo_personal', tipoCod);
    //         aplicarVisibilidadPorTipo(tipoCod);
    //     }, 150);
    // });

    btnNuevaDJ?.addEventListener('click', function () {
        if (window.NuevaDJ) window.NuevaDJ.abrir();

    });


    cerrarModalBtn?.addEventListener('click', function () { registroSeleccionado = null; });

    // Familia
    addBtn?.addEventListener('click', e => { e.preventDefault(); if (container) container.insertAdjacentHTML('beforeend', makeFamilyRow()); });
    container?.addEventListener('click', e => {
        const btn = e.target.closest('button.remove-family');
        if (!btn) return;
        e.preventDefault(); e.stopPropagation();
        btn.closest('.family-row')?.remove();
    });

    // SUCAMEC
    cursoSucamec?.addEventListener("change", () => actualizarInstitucionVisibility());

    // Foto
    btnSubir?.addEventListener("click", () => inputFoto?.click());
    inputFoto?.addEventListener("change", () => {
        const file = inputFoto.files?.[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                if (preview) { preview.src = e.target.result; preview.classList.remove("hidden"); }
                placeholder?.classList.add("hidden");
                btnEliminar?.classList.remove("hidden");
            };
            reader.readAsDataURL(file);
        }
    });
    btnEliminar?.addEventListener("click", () => limpiarPreviewFoto());

    // Page size
    pageSizeSelect?.addEventListener("change", function () { tblPersonas.setPageSize(parseInt(this.value)); });
    pageSizeMigradoSelect?.addEventListener("change", function () { tblPersonasMigrado.setPageSize(parseInt(this.value)); });

    // ============================================================
    // PREVISUALIZAR PDF
    // ============================================================
    btnPrevisualizar?.addEventListener("click", function (e) {
        e.preventDefault();
        const camposObligatorios = [{ input: nombreDJtxt, nombre: 'Nombre' }, { input: dniDJtxt, nombre: 'DNI' }];
        const campoFaltante = camposObligatorios.find(c => !c.input || !String(c.input.value ?? '').trim());
        if (campoFaltante) {
            Swal.fire({ icon: 'warning', title: 'Campos obligatorios', text: `Falta completar: ${campoFaltante.nombre}` });
            campoFaltante.input?.focus();
            return;
        }
        generarDeclaracionJuradaPDF();
    });

    // ============================================================
    // GUARDAR FORMULARIO
    // ============================================================
    if (form) {
        console.log('✅ form encontrado, registrando listener submit');

        form.addEventListener('submit', async (e) => {
            console.log('🔥 submit disparado');
            e.preventDefault();
            e.stopPropagation(); // ← AGREGAR ESTO

            console.log('🔍 form element:', form);
            console.log('🔍 form action:', form.action);

            const btnGuardar = document.getElementById('btnGuardar');
            if (btnGuardar) btnGuardar.disabled = true;

            try {
                const formData = new FormData(form);

                // Verificar que formData tiene datos
                console.log('📋 FormData entries:');
                for (let [key, val] of formData.entries()) {
                    console.log(`  ${key}:`, val);
                }

                const data = Object.fromEntries(formData.entries());
                console.log('📦 data object:', data);

                const tabActiva = document.querySelector('.tab-btn.border-b-white')?.dataset?.tab ?? 'pendiente';
                const payload = {
                    ...data,
                    source: tabActiva,
                    FAM_PARENTESCO: formData.getAll('parentesco[]'),
                    FAM_NOMBRES: formData.getAll('apellidosNombres[]'),
                    FAM_FECHA_NACI: formData.getAll('fechaNacimiento[]'),
                    dj2026_descripcion: formData.getAll('ocupacion_alterna[]')
                };

                console.log('📤 Payload completo:', payload);
                console.log('🌐 URL:', `${VITE_URL_APP}/api/dj/save-dj-completo`);

                const esNuevaDJ = !payload.cod_postulante || String(payload.cod_postulante).trim() === '';

                const url = esNuevaDJ
                    ? `${VITE_URL_APP}/api/dj/save-nueva-dj`      // ← endpoint para nueva
                    : `${VITE_URL_APP}/api/dj/save-dj-completo`;  // ← endpoint para existente

                console.log(esNuevaDJ ? '🆕 Nueva DJ' : '✏️ DJ Existente', payload);


                const response = await axios.post(url, payload);
                console.log('✅ Response:', response);

                if (response.status === 200 || response.status === 201) {
                    Swal.fire({ icon: 'success', title: '¡Éxito!', text: 'La Declaración Jurada se guardó correctamente.' });

                    const modal = document.getElementById('modalDjGestion');
                    if (modal) {
                        if (window.HSOverlay) { try { HSOverlay.close(modal); } catch (e) { } }
                        modal.classList.add('hidden');
                        modal.classList.remove('hs-overlay-open');
                        document.querySelectorAll('.hs-overlay-backdrop').forEach(el => el.remove());
                        document.body.classList.remove('overflow-hidden');
                        document.body.style.overflow = '';
                    }

                    getPersonal();
                    getPersonalMigracion();
                }
            } catch (error) {
                console.error('❌ Error completo:', error);
                console.error('❌ error.response:', error.response);
                console.error('❌ error.message:', error.message);

                let msg = 'Hubo un error al guardar los datos.';
                if (error.response?.data?.message) msg = error.response.data.message;
                else if (error.response?.data?.errors) msg = Object.values(error.response.data.errors).flat().join('<br>');

                Swal.fire({ icon: 'error', title: 'Error', html: msg });
            } finally {
                if (btnGuardar) btnGuardar.disabled = false;
            }
        });
    }

    // ============================================================
    // DESCARGA MASIVA (ZIP)
    // ============================================================
    const btnDescargarDJs = document.getElementById('btnDescargarDJs');

    btnDescargarDJs?.addEventListener('click', async function () {
        const filasVisibles = tblPersonasMigrado.getData("active");
        if (!filasVisibles || filasVisibles.length === 0) {
            Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros visibles para descargar.' });
            return;
        }

        const confirmacion = await Swal.fire({
            icon: 'question', title: 'Descarga masiva de DJ\'s',
            html: `Se generarán <b>${filasVisibles.length}</b> PDF(s) en un archivo ZIP.<br>¿Desea continuar?`,
            showCancelButton: true, confirmButtonText: 'Sí, descargar', cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;

        const zip = new JSZip();
        let generados = 0, errores = 0;

        Swal.fire({ title: 'Generando PDFs...', html: `Procesando <b>0</b> de <b>${filasVisibles.length}</b>`, allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading() });

        try { await cargarCatalogos(); } catch { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los catálogos.' }); return; }

        for (let i = 0; i < filasVisibles.length; i++) {
            const fila = filasVisibles[i];
            const codiPers = fila.codPersonal || fila.CODI_PERS || fila.id;
            Swal.update({ html: `Procesando <b>${i + 1}</b> de <b>${filasVisibles.length}</b><br><small>${fila.nombres || codiPers}</small>` });
            try {
                await cargarDatosPersonales(codiPers);
                await new Promise(resolve => setTimeout(resolve, 600));
                const resultado = await generarDeclaracionJuradaPDF(true);
                if (resultado?.blob) { zip.file(resultado.filename, resultado.blob); generados++; } else errores++;
            } catch (err) { console.error(`Error generando PDF para ${codiPers}:`, err); errores++; }
        }

        if (generados === 0) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar ningún PDF.' }); return; }

        Swal.update({ html: 'Comprimiendo archivos...' });
        try {
            const contenidoZip = await zip.generateAsync({ type: 'blob' });
            const f = new Date();
            const ts = f.getFullYear() + String(f.getMonth() + 1).padStart(2, '0') + String(f.getDate()).padStart(2, '0') + '_' + String(f.getHours()).padStart(2, '0') + String(f.getMinutes()).padStart(2, '0');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(contenidoZip);
            link.download = `DJ_Masivo_${ts}.zip`;
            document.body.appendChild(link); link.click(); document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
            Swal.fire({ icon: 'success', title: 'Descarga completada', html: `Se generaron <b>${generados}</b> PDF(s) correctamente.` + (errores > 0 ? `<br><small class="text-red-500">${errores} con error.</small>` : '') });
        } catch { Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un error al generar el archivo ZIP.' }); }
    });

    // ============================================================
    // DJ UNIFICADO (un solo PDF — Migración)
    // ============================================================
    const btnDJUnificado = document.getElementById('btnDJUnificado');
    const btnDJUnificadoMigrado = document.getElementById('btnDJUnificadoMigrado');

    btnDJUnificado?.addEventListener('click', async function () {
        const filasVisibles = tblPersonasMigrado.getData("active");
        if (!filasVisibles || filasVisibles.length === 0) {
            Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros visibles para descargar.' });
            return;
        }

        const confirmacion = await Swal.fire({
            icon: 'question', title: 'DJ Unificado',
            html: `Se generará <b>1 PDF</b> con las <b>${filasVisibles.length}</b> declaraciones juradas.<br>¿Desea continuar?`,
            showCancelButton: true, confirmButtonText: 'Sí, generar', cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;

        await _generarUnificado(filasVisibles, 'DJ_Unificado');
    });


    btnDJUnificadoMigrado?.addEventListener('click', async function () {

        const todasMigradas = tblPersonasMigrado.getData("active")
            .filter(fila => fila.migrado === 'Migrado');

        if (!todasMigradas.length) {
            Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros migrados visibles.' });
            return;
        }

        const pendientes = todasMigradas.filter(f => !estaGenerado(f.codPersonal || f.CODI_PERS || f.id, f.cambio));
        const yaGenerados = todasMigradas.filter(f => estaGenerado(f.codPersonal || f.CODI_PERS || f.id, f.cambio));

        const { value: opcion, isConfirmed } = await Swal.fire({
            title: 'DJ Unificado — Migrados',
            html: `
            <div style="display:flex;flex-direction:column;gap:10px;text-align:left;font-size:13px;padding:4px 0;">
                <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;" id="lbl-pend">
                    <input type="radio" name="djopcion" value="pendientes" ${pendientes.length ? '' : 'disabled'}
                        style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1;">
                    <div>
                        <div style="font-weight:600;color:${pendientes.length ? '#111827' : '#9ca3af'};">
                            Solo pendientes
                            <span style="margin-left:6px;background:${pendientes.length ? '#dcfce7' : '#f3f4f6'};color:${pendientes.length ? '#16a34a' : '#9ca3af'};font-size:11px;padding:1px 8px;border-radius:20px;font-weight:700;">
                                ${pendientes.length}
                            </span>
                        </div>
                        <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                            Registros sin ✅ o con cambios nuevos desde la última generación
                        </div>
                    </div>
                </label>
                <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;" id="lbl-todos">
                    <input type="radio" name="djopcion" value="todos"
                        style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1;">
                    <div>
                        <div style="font-weight:600;color:#111827;">
                            Todos los migrados
                            <span style="margin-left:6px;background:#dbeafe;color:#1e40af;font-size:11px;padding:1px 8px;border-radius:20px;font-weight:700;">
                                ${todasMigradas.length}
                            </span>
                        </div>
                        <div style="font-size:11px;color:#6b7280;margin-top:2px;">
                            Incluye los ${yaGenerados.length} ya generados anteriormente
                        </div>
                    </div>
                </label>
            </div>
        `,
            showCancelButton: true,
            confirmButtonText: 'Generar PDF',
            cancelButtonText: 'Cancelar',
            didOpen: () => {
                const radios = document.querySelectorAll('input[name="djopcion"]');
                radios.forEach(r => {
                    r.addEventListener('change', () => {
                        document.getElementById('lbl-pend').style.borderColor = r.value === 'pendientes' && r.checked ? '#6366f1' : '#e5e7eb';
                        document.getElementById('lbl-todos').style.borderColor = r.value === 'todos' && r.checked ? '#6366f1' : '#e5e7eb';
                    });
                });
                const def = pendientes.length ? 'pendientes' : 'todos';
                const defRadio = document.querySelector(`input[name="djopcion"][value="${def}"]`);
                if (defRadio) {
                    defRadio.checked = true;
                    document.getElementById(def === 'pendientes' ? 'lbl-pend' : 'lbl-todos').style.borderColor = '#6366f1';
                }
            },
            preConfirm: () => {
                const sel = document.querySelector('input[name="djopcion"]:checked');
                if (!sel) { Swal.showValidationMessage('Selecciona una opción.'); return false; }
                return sel.value;
            }
        });

        if (!isConfirmed) return;

        const filasSeleccionadas = opcion === 'pendientes' ? pendientes : todasMigradas;

        if (!filasSeleccionadas.length) {
            Swal.fire({ icon: 'info', title: 'Sin pendientes', text: 'Todos los registros ya fueron generados. Usa "Todos" para regenerar.' });
            return;
        }

        // ── NUEVO: cruzar DNIs contra tblPersonas ──────────────────
        const dnisMigrados = filasSeleccionadas.map(f => f.dni);
        const todasEnTabla1 = tblPersonas.getData();
        const filasDesdeTabla1 = todasEnTabla1.filter(f => dnisMigrados.includes(f.dni));

        if (!filasDesdeTabla1.length) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin coincidencias',
                text: 'No se encontraron los registros migrados en la tabla de listos/pendientes.'
            });
            return;
        }

        const confirmacion = await Swal.fire({
            icon: 'question',
            title: 'Confirmar generación',
            html: `Se generará <b>1 PDF</b> con <b>${filasDesdeTabla1.length}</b> declaración(es).<br>¿Desea continuar?`,
            showCancelButton: true,
            confirmButtonText: 'Sí, generar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;

        // Usar filasDesdeTabla1 y source 'pendiente' para tomar datos frescos
        const resultadoGen = await _generarUnificado(filasDesdeTabla1, 'DJ_Unificado_Migrados', 'pendiente');

        if (resultadoGen?.ok && resultadoGen.generadosOk.length) {
            // Marcar usando los codPersonal originales de la tabla de migrados (por DNI)
            const marcados = resultadoGen.generadosOk.map(fila1 => {
                const filaMig = filasSeleccionadas.find(f => f.dni === fila1.dni);
                return {
                    codPersonal: filaMig?.codPersonal || filaMig?.CODI_PERS || filaMig?.id,
                    fechaCambio: filaMig?.cambio
                };
            }).filter(x => x.codPersonal);

            marcarDJGeneradosBatch(marcados);
        }

        tblPersonasMigrado.redraw(true);
    });

    // btnDJUnificadoMigrado?.addEventListener('click', async function () {

    //     // Todas las filas migradas visibles en la tabla
    //     const todasMigradas = tblPersonasMigrado.getData("active")
    //         .filter(fila => fila.migrado === 'Migrado');

    //     if (!todasMigradas.length) {
    //         Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros migrados visibles.' });
    //         return;
    //     }

    //     // Separar pendientes vs ya generados
    //     const pendientes   = todasMigradas.filter(f => !estaGenerado(f.codPersonal || f.CODI_PERS || f.id, f.cambio));
    //     const yaGenerados  = todasMigradas.filter(f =>  estaGenerado(f.codPersonal || f.CODI_PERS || f.id, f.cambio));

    //     // Elegir qué generar
    //     const { value: opcion, isConfirmed } = await Swal.fire({
    //         title: 'DJ Unificado — Migrados',
    //         html: `
    //             <div style="display:flex;flex-direction:column;gap:10px;text-align:left;font-size:13px;padding:4px 0;">
    //                 <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;" id="lbl-pend">
    //                     <input type="radio" name="djopcion" value="pendientes" ${pendientes.length ? '' : 'disabled'}
    //                         style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1;">
    //                     <div>
    //                         <div style="font-weight:600;color:${pendientes.length ? '#111827' : '#9ca3af'};">
    //                             Solo pendientes
    //                             <span style="margin-left:6px;background:${pendientes.length ? '#dcfce7' : '#f3f4f6'};color:${pendientes.length ? '#16a34a' : '#9ca3af'};font-size:11px;padding:1px 8px;border-radius:20px;font-weight:700;">
    //                                 ${pendientes.length}
    //                             </span>
    //                         </div>
    //                         <div style="font-size:11px;color:#6b7280;margin-top:2px;">
    //                             Registros sin ✅ o con cambios nuevos desde la última generación
    //                         </div>
    //                     </div>
    //                 </label>
    //                 <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;" id="lbl-todos">
    //                     <input type="radio" name="djopcion" value="todos"
    //                         style="width:16px;height:16px;cursor:pointer;accent-color:#6366f1;">
    //                     <div>
    //                         <div style="font-weight:600;color:#111827;">
    //                             Todos los migrados
    //                             <span style="margin-left:6px;background:#dbeafe;color:#1e40af;font-size:11px;padding:1px 8px;border-radius:20px;font-weight:700;">
    //                                 ${todasMigradas.length}
    //                             </span>
    //                         </div>
    //                         <div style="font-size:11px;color:#6b7280;margin-top:2px;">
    //                             Incluye los ${yaGenerados.length} ya generados anteriormente
    //                         </div>
    //                     </div>
    //                 </label>
    //             </div>
    //         `,
    //         showCancelButton: true,
    //         confirmButtonText: 'Generar PDF',
    //         cancelButtonText: 'Cancelar',
    //         didOpen: () => {
    //             // Seleccionar por defecto "pendientes" si hay, sino "todos"
    //             const radios = document.querySelectorAll('input[name="djopcion"]');
    //             radios.forEach(r => {
    //                 r.addEventListener('change', () => {
    //                     document.getElementById('lbl-pend').style.borderColor  = r.value === 'pendientes' && r.checked ? '#6366f1' : '#e5e7eb';
    //                     document.getElementById('lbl-todos').style.borderColor = r.value === 'todos'      && r.checked ? '#6366f1' : '#e5e7eb';
    //                 });
    //             });
    //             const def = pendientes.length ? 'pendientes' : 'todos';
    //             const defRadio = document.querySelector(`input[name="djopcion"][value="${def}"]`);
    //             if (defRadio) {
    //                 defRadio.checked = true;
    //                 document.getElementById(def === 'pendientes' ? 'lbl-pend' : 'lbl-todos').style.borderColor = '#6366f1';
    //             }
    //         },
    //         preConfirm: () => {
    //             const sel = document.querySelector('input[name="djopcion"]:checked');
    //             if (!sel) { Swal.showValidationMessage('Selecciona una opción.'); return false; }
    //             return sel.value;
    //         }
    //     });

    //     if (!isConfirmed) return;

    //     const filasFinales = opcion === 'pendientes' ? pendientes : todasMigradas;

    //     if (!filasFinales.length) {
    //         Swal.fire({ icon: 'info', title: 'Sin pendientes', text: 'Todos los registros ya fueron generados. Usa "Todos" para regenerar.' });
    //         return;
    //     }

    //     const confirmacion = await Swal.fire({
    //         icon: 'question',
    //         title: 'Confirmar generación',
    //         html: `Se generará <b>1 PDF</b> con <b>${filasFinales.length}</b> declaración(es).<br>¿Desea continuar?`,
    //         showCancelButton: true,
    //         confirmButtonText: 'Sí, generar',
    //         cancelButtonText: 'Cancelar'
    //     });
    //     if (!confirmacion.isConfirmed) return;

    //     // Generar y marcar al terminar
    //     // await _generarUnificado(filasFinales, 'DJ_Unificado_Migrados');

    //     // // Marcar todos los incluidos como generados
    //     // filasFinales.forEach(fila => {
    //     //     const cod = fila.codPersonal || fila.CODI_PERS || fila.id;
    //     //     marcarDJGenerado(cod, fila.cambio);
    //     // });

    //     const resultadoGen = await _generarUnificado(filasFinales, 'DJ_Unificado_Migrados');

    //     // if (resultadoGen?.ok) {
    //     //     resultadoGen.generadosOk.forEach(fila => {
    //     //         const cod = fila.codPersonal || fila.CODI_PERS || fila.id;
    //     //         marcarDJGenerado(cod, fila.cambio);
    //     //     });
    //     // }
    //     if (resultadoGen?.ok && resultadoGen.generadosOk.length) {
    //         marcarDJGeneradosBatch(
    //             resultadoGen.generadosOk.map(fila => ({
    //                 codPersonal: fila.codPersonal || fila.CODI_PERS || fila.id,
    //                 fechaCambio: fila.cambio
    //             }))
    //         );
    //     }

    //     tblPersonasMigrado.redraw(true);

    //     // Refrescar columna PDF en la tabla
    //     tblPersonasMigrado.redraw(true);
    // });

    // ============================================================
    // DJ UNIFICADO — Pendientes
    // ============================================================
    const btnDJUnificado_PEN = document.getElementById('btnDJUnificado_PEN');

    btnDJUnificado_PEN?.addEventListener('click', async function () {
        const filasVisibles = tblPersonas.getData("active");
        if (!filasVisibles || filasVisibles.length === 0) {
            Swal.fire({ icon: 'info', title: 'Sin resultados', text: 'No hay registros visibles para descargar.' });
            return;
        }

        const confirmacion = await Swal.fire({
            icon: 'question', title: 'DJ Unificado — Pendientes',
            html: `Se generará <b>1 PDF</b> con las <b>${filasVisibles.length}</b> declaraciones.<br>¿Desea continuar?`,
            showCancelButton: true, confirmButtonText: 'Sí, generar', cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;

        await _generarUnificado(filasVisibles, 'DJ_Unificado_Pendientes', 'pendiente');
    });

    async function obtenerDatosPersonales(codiPers, source = 'migracion') {
        const response = await axios.get(`${API_URL}/dj/get-personal-data`, {
            params: { codi_pers: codiPers, source }
        });
        return response.data;
    }


    // Helper interno para unificar PDFs
    // async function _generarUnificado(filas, nombreBase, source = 'migracion') {
    //     const pdfBlobs = [];
    //     let errores = 0;

    //     Swal.fire({ 
    //         title: 'Generando DJ Unificado...', 
    //         html: `Procesando <b>0</b> de <b>${filas.length}</b>`, allowOutsideClick: false, showCloseButton: false, showConfirmButton: false,
    //         allowEscapeKey: false, didOpen: () => Swal.showLoading() });

    //     try { await cargarCatalogos(); } catch { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los catálogos.' }); return; }

    //     for (let i = 0; i < filas.length; i++) {
    //         const fila = filas[i];
    //         const codiPers = fila.codPersonal || fila.CODI_PERS || fila.id;

    //         Swal.update({
    //             html: `Procesando <b>${i + 1}</b> de <b>${filas.length}</b><br><small>${fila.nombres || codiPers}</small>`
    //         });

    //         try {
    //             const payload = await obtenerDatosPersonales(codiPers, source);
    //             await llenarFormulario(payload.data);
    //             renderFamiliares(payload.familiares);

    //             const resultado = await generarDeclaracionJuradaPDF(true);
    //             if (resultado?.blob) {
    //                 pdfBlobs.push(await resultado.blob.arrayBuffer());
    //             } else {
    //                 errores++;
    //             }
    //         } catch (err) {
    //             console.error(`Error generando PDF para ${codiPers}:`, err);
    //             errores++;
    //         }
    //         await new Promise(resolve => setTimeout(resolve, 250));
    //     }

    //     if (pdfBlobs.length === 0) { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar ningún PDF.' }); return; }

    //     Swal.update({ html: 'Unificando documentos...' });
    //     try {
    //         const { PDFDocument } = PDFLib;
    //         const mergedPdf = await PDFDocument.create();
    //         for (const buf of pdfBlobs) {
    //             const donor = await PDFDocument.load(buf);
    //             const pages = await mergedPdf.copyPages(donor, donor.getPageIndices());
    //             pages.forEach(p => mergedPdf.addPage(p));
    //         }
    //         const mergedBytes = await mergedPdf.save();
    //         const f  = new Date();
    //         const ts = f.getFullYear() + String(f.getMonth()+1).padStart(2,'0') + String(f.getDate()).padStart(2,'0') + '_' + String(f.getHours()).padStart(2,'0') + String(f.getMinutes()).padStart(2,'0');
    //         const blob = new Blob([mergedBytes], { type: 'application/pdf' });
    //         const link = document.createElement('a');
    //         link.href = URL.createObjectURL(blob);
    //         link.download = `${nombreBase}_${ts}.pdf`;
    //         document.body.appendChild(link); link.click(); document.body.removeChild(link);
    //         URL.revokeObjectURL(link.href);
    //         Swal.fire({ icon: 'success', title: 'DJ Unificado generado', html: `Se unificaron <b>${pdfBlobs.length}</b> declaraciones.` + (errores > 0 ? `<br><small class="text-red-500">${errores} con error.</small>` : '') });
    //     } catch { Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un error al unificar los documentos.' }); }
    // }
    async function _generarUnificado(filas, nombreBase, source = 'migracion') {
        const pdfBlobs = [];
        const generadosOk = [];
        const generadosError = [];

        Swal.fire({
            title: 'Generando DJ Unificado...',
            html: `Procesando <b>0</b> de <b>${filas.length}</b>`,
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => Swal.showLoading()
        });

        try {
            await cargarCatalogos();
        } catch {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los catálogos.' });
            return { ok: false, generadosOk, generadosError };
        }

        for (let i = 0; i < filas.length; i++) {
            const fila = filas[i];
            const codiPers = fila.codPersonal || fila.CODI_PERS || fila.id;

            Swal.update({
                html: `Procesando <b>${i + 1}</b> de <b>${filas.length}</b><br><small>${fila.nombres || codiPers}</small>`
            });

            try {
                //const payload = await obtenerDatosPersonales(codiPers, source);
                const payload = await obtenerDatosConRetry(codiPers, source);
                await llenarFormulario(payload.data);
                renderFamiliares(payload.familiares);

                const resultado = await generarDeclaracionJuradaPDF(true);

                if (!resultado?.blob) {
                    generadosError.push({ fila, motivo: 'No se obtuvo blob' });
                    continue;
                }

                const buffer = await resultado.blob.arrayBuffer();

                if (!buffer || buffer.byteLength === 0) {
                    generadosError.push({ fila, motivo: 'PDF vacío' });
                    continue;
                }

                pdfBlobs.push(buffer);
                generadosOk.push(fila);

            } catch (err) {
                console.error(`Error generando PDF para ${codiPers}:`, err);
                generadosError.push({ fila, motivo: err?.message || 'Error desconocido' });
            }

            // await new Promise(resolve => setTimeout(resolve, 250));
            // await new Promise(r => setTimeout(r, PAUSA_ENTRE_REGISTROS));
            const pausa = filas.length > 30
                ? PAUSA_ENTRE_REGISTROS * 1.5
                : PAUSA_ENTRE_REGISTROS;
            await new Promise(r => setTimeout(r, pausa));
        }

        if (pdfBlobs.length === 0) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar ningún PDF.' });
            return { ok: false, generadosOk, generadosError };
        }

        Swal.update({ html: 'Unificando documentos...' });

        try {
            const { PDFDocument } = PDFLib;
            const mergedPdf = await PDFDocument.create();

            for (const buf of pdfBlobs) {
                const donor = await PDFDocument.load(buf);
                const pages = await mergedPdf.copyPages(donor, donor.getPageIndices());
                pages.forEach(p => mergedPdf.addPage(p));
            }

            const mergedBytes = await mergedPdf.save();

            if (!mergedBytes || mergedBytes.length === 0) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'El PDF unificado se generó vacío.' });
                return { ok: false, generadosOk: [], generadosError: filas.map(f => ({ fila: f, motivo: 'PDF unificado vacío' })) };
            }

            const f = new Date();
            const ts = f.getFullYear()
                + String(f.getMonth() + 1).padStart(2, '0')
                + String(f.getDate()).padStart(2, '0')
                + '_'
                + String(f.getHours()).padStart(2, '0')
                + String(f.getMinutes()).padStart(2, '0');

            const blob = new Blob([mergedBytes], { type: 'application/pdf' });

            if (blob.size === 0) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'El archivo final quedó vacío.' });
                return { ok: false, generadosOk: [], generadosError: filas.map(f => ({ fila: f, motivo: 'Blob final vacío' })) };
            }

            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `${nombreBase}_${ts}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);

            Swal.fire({
                icon: generadosError.length > 0 ? 'warning' : 'success',
                title: 'Resultado de generación',
                html: `
                    <div style="text-align:left; font-size:14px; line-height:1.6;">
                        <div>
                            <span style="font-weight:600;">Generados:</span>
                            <span style="color:#15803d; font-weight:700;">${generadosOk.length} / ${filas.length}</span>
                        </div>
                        <div>
                            <span style="font-weight:600;">Fallidos:</span>
                            <span style="color:#b91c1c; font-weight:700;">${generadosError.length}</span>
                        </div>

                        ${generadosError.length > 0
                        ? `
                                <div style="margin-top:10px; font-size:12px; color:#b91c1c;">
                                    <b>Detalle de fallas:</b><br>
                                    ${generadosError.map(x => {
                            const fila = x.fila || {};
                            const nombre = fila.nombres || fila.CODI_PERS || fila.id || 'Registro';
                            return `• ${nombre}: ${x.motivo}`;
                        }).join('<br>')}
                                </div>
                                `
                        : ''
                    }
                    </div>
                `
            });

            return { ok: true, generadosOk, generadosError };

        } catch (err) {
            console.error('Error unificando documentos:', err);
            Swal.fire({ icon: 'error', title: 'Error', text: 'Hubo un error al unificar los documentos.' });
            return { ok: false, generadosOk: [], generadosError: filas.map(f => ({ fila: f, motivo: 'Fallo en unificación' })) };
        }
    }

    // ============================================================
    // RESIZER SPLIT VIEW
    // ============================================================
    (function initResizer() {
        const wrapper = document.getElementById('djSplitWrapper');
        const resizer = document.getElementById('djResizer');
        const panelBk = document.getElementById('panelBackup');
        if (!wrapper || !resizer || !panelBk) return;

        let isResizing = false, startX = 0, startW = 0;

        resizer.addEventListener('mousedown', e => {
            isResizing = true; startX = e.clientX; startW = panelBk.offsetWidth;
            resizer.classList.add('dragging');
            document.body.style.cursor = 'col-resize';
            document.body.style.userSelect = 'none';
            e.preventDefault();
        });
        document.addEventListener('mousemove', e => {
            if (!isResizing) return;
            const newW = Math.max(220, Math.min(startW + (e.clientX - startX), wrapper.offsetWidth - 280));
            panelBk.style.width = newW + 'px'; panelBk.style.flexBasis = newW + 'px';
        });
        document.addEventListener('mouseup', () => {
            if (!isResizing) return;
            isResizing = false; resizer.classList.remove('dragging');
            document.body.style.cursor = ''; document.body.style.userSelect = '';
        });
        resizer.addEventListener('touchstart', e => {
            isResizing = true; startX = e.touches[0].clientX; startW = panelBk.offsetWidth;
            e.preventDefault();
        }, { passive: false });
        document.addEventListener('touchmove', e => {
            if (!isResizing) return;
            const newW = Math.max(220, Math.min(startW + (e.touches[0].clientX - startX), wrapper.offsetWidth - 280));
            panelBk.style.width = newW + 'px'; panelBk.style.flexBasis = newW + 'px';
        });
        document.addEventListener('touchend', () => { isResizing = false; });
        resizer.addEventListener('dblclick', () => { panelBk.style.width = '38%'; panelBk.style.flexBasis = '38%'; });
    })();

}); // fin DOMContentLoaded

// ============================================================
// FUNCIONES GLOBALES (fuera del DOMContentLoaded)
// ============================================================

// ── Abrir modal DJ ──────────────────────────────────────────
async function abrirFormularioDJ(codiPers = null, source = 'migracion') {
    try {
        const modal = document.getElementById('modalDjGestion');

        if (codiPers == null) {
            Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            // En lugar de limpiarFormulario() — reset manual con lo que sí es global
            limpiarSplitView();
            setValue('cod_postulante', '');
            setValue('tipo_personal', '');

            await cargarCatalogos();

            // Cargar departamentos para los 3 ubigeos
            const depts = await getUbicacionCached({ type: 'dept' });
            populateSelect('#departamento_actual', depts);
            populateSelect('#departamento_dni', depts);
            populateSelect('#departamento_nac', depts);

            Swal.close();

            if (modal) {
                if (window.HSOverlay) HSOverlay.open(modal);
                else modal.classList.remove('hidden');
            }

            setTimeout(() => {
                const tipo = document.getElementById('tipo_personal')?.value?.trim() ?? '';
                aplicarVisibilidadPorTipo(tipo);
            }, 80);

        } else {
            // ← DJ existente: flujo normal sin cambios
            Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            await cargarCatalogos(source);
            await cargarDatosPersonales(codiPers, source);

            Swal.close();

            if (modal) {
                if (window.HSOverlay) HSOverlay.open(modal);
                else modal.classList.remove('hidden');
            }

            if (source === 'migracion') await cargarDatosBackup(codiPers);
            else limpiarSplitView();
        }

    } catch (error) {
        console.error('Error:', error);
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo cargar el formulario: ' + error.message });
    }
}

// ── Catálogos ────────────────────────────────────────────────
let catalogosCache = null;
let catalogosPromise = null;

async function cargarCatalogos(source = 'migracion') {
    if (catalogosCache) return catalogosCache;
    if (catalogosPromise) return catalogosPromise;

    catalogosPromise = axios.get(`${API_URL}/dj/get-catalogs`)
        .then(response => {
            const { grados, carreras, instituciones, sangre, estados_civiles, tipos_arma } = response.data;

            populateSelect('#selGrado', grados);
            populateSelect('#selCarrera', carreras);
            populateSelect('#selInstitucion', instituciones);
            populateSelect('#PERS_GRUP_SANGRE', sangre);
            populateSelect('#PERS_ESTADO_CIVIL', estados_civiles);
            populateSelect('#LAB_TIPO_ARMA', tipos_arma);
            // Poblar estado civil del modal original
            populateSelect('#estado_civil', response.data.estados_civiles ?? []);

            // Poblar sistema previsional del modal original
            populateSelect('#sistema_previsional', response.data.sistemas_previsionales ?? []);

            window.allCarreras = carreras;
            catalogosCache = response.data;
            return response.data;
        })
        .finally(() => {
            catalogosPromise = null;
        });

    return catalogosPromise;
}

// ── Datos personales ─────────────────────────────────────────
const personalDataCache = new Map();
const personalDataPromise = new Map();

async function cargarDatosPersonales(codiPers, source = 'migracion') {
    const key = `${codiPers}_${source}`;

    if (personalDataCache.has(key)) {
        const cached = personalDataCache.get(key);
        await llenarFormulario(cached.data);
        renderFamiliares(cached.familiares);
        return cached;
    }

    if (personalDataPromise.has(key)) {
        const pending = await personalDataPromise.get(key);
        await llenarFormulario(pending.data);
        renderFamiliares(pending.familiares);
        return pending;
    }

    const req = axios.get(`${API_URL}/dj/get-personal-data`, {
        params: { codi_pers: codiPers, source }
    }).then(response => {
        personalDataCache.set(key, response.data);
        return response.data;
    }).finally(() => {
        personalDataPromise.delete(key);
    });

    personalDataPromise.set(key, req);

    const result = await req;
    await llenarFormulario(result.data);
    renderFamiliares(result.familiares);
    return result;
}

// ── Llenar formulario ────────────────────────────────────────
async function llenarFormulario(data) {
    setValue('cod_postulante', data.CODI_PERS);

    const tipotrab = data.PERS_TIPOTRAB ? String(data.PERS_TIPOTRAB).trim() : '';
    console.log('TIPO TRAB:', tipotrab); // ← agregar esto

    setValue('tipo_personal', tipotrab);
    aplicarVisibilidadPorTipo(tipotrab);

    setValue('#nombres_apellidos', `${data.NOMB_1 || ''} ${data.NOMB_2 || ''} ${data.APEL_1 || ''} ${data.APEL_2 || ''}`);
    setValue('#nombre1', data.NOMB_1 || '');
    setValue('#nombre2', data.NOMB_2 || '');
    setValue('#apellido_paterno', data.APEL_1 || '');
    setValue('#apellido_materno', data.APEL_2 || '');
    setValue('#dni', data.NRO_DOCU_IDEN ? data.NRO_DOCU_IDEN.trim() : '');
    setValue('#caduca', formatDateForInput(data.PERS_FECHCADUCADNI) ? formatDateForInput(data.PERS_FECHCADUCADNI) : '');
    setValue('#estado_civil', data.ESCI_CODIGO ? data.ESCI_CODIGO.trim() : '');
    setValue('#sexo', data.PERS_SEXO ? data.PERS_SEXO.trim() : data.SEXO ? data.SEXO.trim() : '');
    setValue('#fecha_nacimiento', formatDateForInput(data.FECH_NACI));
    setValue('#sabe_nadar', data.PERS_SNADAR ? data.PERS_SNADAR.trim() : '');
    setValue('#ciudad_nacimiento', data.dj2026_ciudad_naci ? data.dj2026_ciudad_naci.trim() : '');

    // setValue('#departamento_nac',data.DEPA_CODIGO_NACI ? data.DEPA_CODIGO_NACI.trim() : '');
    // setValue('#provincia_nac',data.PROVI_CODIGO_NACI ? data.PROVI_CODIGO_NACI.trim() : '');
    // setValue('#distrito_nac',data.DIST_NACI ? data.DIST_NACI.trim() : '');

    setValue('#dj2026_laboral_1', data.dj2026_laboral_1 ? data.dj2026_laboral_1.trim() : '');
    setValue('#dj2026_laboral_2', data.dj2026_laboral_2 ? data.dj2026_laboral_2.trim() : '');

    setValue('#celular', data.PERS_TELEFONO ? data.PERS_TELEFONO.trim() : '');
    setValue('#correo', data.PERS_EMAIL ? data.PERS_EMAIL.trim() : '');
    setValue('#whatsapp', data.PERS_WHATSAPP ? data.PERS_WHATSAPP.trim() : '');

    setValue('#tipo_sangre', data.tipo_sangr ? data.tipo_sangr.trim() : '');
    setValue('#peso', data.peso_kilo ? data.peso_kilo.trim() : '');
    setValue('#talla', data.tall_metr ? data.tall_metr.trim() : '');

    setValue('#sistema_previsional', data.CODI_SIST_PENS ? data.CODI_SIST_PENS.trim() : '');
    setValue('#essalud', data.ESSALUD ? data.ESSALUD.trim() : '');
    setValue('#pensionista', data.PERS_PENSIONISTA ? data.PERS_PENSIONISTA.trim() : '');

    setValue('#grado_instruccion', data.PERS_GRADO_INSTRUCCION ? data.PERS_GRADO_INSTRUCCION.trim() : '');
    if (data.CARR_CODIGO == '999999') {
        setValue('#institucion', data.IEDU_CODIGO ? data.IEDU_CODIGO.trim() : '299999999');
        if (data.IEDU_CODIGO && window.allCarreras) {
            const selCarrera = document.getElementById('carrera');
            if (selCarrera) {
                selCarrera.innerHTML = '<option value="">—</option>';
                window.allCarreras
                    .filter(c => c.IEDU_CODIGO === data.IEDU_CODIGO.trim())
                    .forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.text;
                        selCarrera.appendChild(opt);
                    });
                selCarrera.value = data.CARR_CODIGO ? data.CARR_CODIGO.trim() : '';
            }
        }

    } else {
        setValue('#institucion', data.IEDU_CODIGO ? data.IEDU_CODIGO.trim() : '999999');
        if (data.IEDU_CODIGO && window.allCarreras) {
            const selCarrera = document.getElementById('carrera');
            if (selCarrera) {
                selCarrera.innerHTML = '<option value="">—</option>';
                window.allCarreras
                    .filter(c => c.IEDU_CODIGO === data.IEDU_CODIGO.trim())
                    .forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.text;
                        selCarrera.appendChild(opt);
                    });
                selCarrera.value = data.CARR_CODIGO ? data.CARR_CODIGO.trim() : '';
            }
        }
    }

    setValue('#carrera', data.CARR_CODIGO ? data.CARR_CODIGO.trim() : '999999');
    setValue('#anio_egreso', data.EGRESO_EDUCATIVO ? data.EGRESO_EDUCATIVO.trim() : '');

    setValue('#embargos', data.PERS_EMBARGO ? data.PERS_EMBARGO.trim() : '');
    setValue('#consumo_sustancias', data.PERS_SMO ? data.PERS_SMO.trim() : '');
    setValue('#cuenta_banco', data.dj2026_banco ? data.dj2026_banco.trim() : '');

    setValue('#direccion_actual', data.DIRECCION ? data.DIRECCION.trim() : '');
    setValue('#direccion_dni', data.PERS_DIREC_DNI ? data.PERS_DIREC_DNI.trim() : '');

    cargarUbicaciones('actual', data.PERS_DEPT_ACT?.trim() ?? '', data.PERS_PROV_ACT?.trim() ?? '', data.PERS_DIST_ACT?.trim() ?? '');
    cargarUbicaciones('dni', data.PERS_DPTO_DIRDNI?.trim() ?? '', data.PERS_PROV_DIRDNI?.trim() ?? '', data.PERS_DIST_DIRDNI?.trim() ?? '');
    cargarUbicaciones('nac', data.DEPA_CODIGO_NACI?.trim() ?? '', data.PROVI_CODIGO_NACI?.trim() ?? '', data.DIST_NACI?.trim() ?? '');


    setValue('#ocupacion_principal', data.dj2026_ocupacion_principal);
    setValue('#experiencia_anios', data.dj2026_experiencia_anios ? String(data.dj2026_experiencia_anios).replace(/[^0-9]/g, '') : '');
    setValue('#familiar_empresa', data.dj2026_familiar_empresa ? data.dj2026_familiar_empresa.trim() : '');
    setValue('#familiar_nombre', data.dj2026_familiar_nombre ? data.dj2026_familiar_nombre.trim() : '');
    setValue('#familiar_parentesco', data.dj2026_familiar_parentesco ? data.dj2026_familiar_parentesco.trim() : '');

    setValue('#curso_sucamec', data.PERS_CONDISCAMEC ? data.PERS_CONDISCAMEC.trim() : '');
    setValue('#sucamec_obs', data.PERS_NRODISCAMEC ? data.PERS_NRODISCAMEC.trim() : '');
    setValue('#smo', data.PERS_SMO ? data.PERS_SMO.trim() : '');
    setValue('#licencia_arma', data.PERS_NROLICENCIA ? data.PERS_NROLICENCIA.trim() : '');
    setValue('#tipo_arma', data.PERS_TIPOARMA ? data.PERS_TIPOARMA.trim() : '');
    setValue('#arma_propia', data.PERS_CONARMAS ? data.PERS_CONARMAS.trim() : '');
    setValue('#brevete', data.PERS_BREVETE ? data.PERS_BREVETE.trim() : '');
    setValue('#clase_brevete', data.CLASE_BREVETE ? data.CLASE_BREVETE.trim() : '');
    actualizarCategorias();  // ← puebla el select tipo_vehiculo según la clase
    setValue('#tipo_vehiculo', data.CATEGORIA_BREVETE ? data.CATEGORIA_BREVETE.trim() : '');
    setValue('#vehiculo_propio', data.PERS_VEHICULO_PROPIO ? data.PERS_VEHICULO_PROPIO.trim() : '');

    setValue('#empresa_anterior', data.PERS_CTRABANT ? data.PERS_CTRABANT.trim() : '');
    setValue('#cargo_anterior', data.PERS_CARGOTRABANT ? data.PERS_CARGOTRABANT.trim() : '');
    setValue('#duracion_anterior', data.PERS_DURACIONANT ? data.PERS_DURACIONANT.trim() : '');

    setValue('#contacto_emergencia', data.PERS_NOMCONTACTO ? data.PERS_NOMCONTACTO.trim() : '');
    setValue('#celular_emergencia', data.PERS_NROEMERGENCIA ? data.PERS_NROEMERGENCIA.trim() : '');
    setValue('#parentesco_emergencia', data.PERS_EMERC_FAMILIAR ? data.PERS_EMERC_FAMILIAR.trim() : '');

    if (data.FOTO_PATH) {
        const img = document.getElementById('previewFoto');
        const placeholderEl = document.getElementById('placeholderFoto');
        if (img) {
            img.src = data.FOTO_PATH + '?v=' + (Math.floor(Math.random() * 900) + 100);
            img.classList.remove('hidden');
            if (placeholderEl) placeholderEl.classList.add('hidden');
            document.getElementById('btnEliminarFoto')?.classList.remove('hidden');
        }
    }
}

// ── Familiares ───────────────────────────────────────────────
function renderFamiliares(familiares) {
    const container = document.getElementById('familyContainer');
    if (!container) return;
    container.innerHTML = '';

    const allFam = [
        ...(familiares.padres || []),
        ...(familiares.madre || []),
        ...(familiares.hijos || []),
        ...(familiares.conyugue || [])
    ];

    if (allFam.length === 0) addFamiliarRow({}, container);
    else allFam.forEach(f => addFamiliarRow(f, container));
}

function addFamiliarRow(data = {}, container = null) {
    if (!container) container = document.getElementById('familyContainer');
    if (!container) return;

    let fechaFormateada = '';
    if (data.FECH_NACI) {
        const f = String(data.FECH_NACI);
        fechaFormateada = (f.length >= 8 && !f.includes('-') && !f.includes('/'))
            ? `${f.substring(0, 4)}-${f.substring(4, 6)}-${f.substring(6, 8)}`
            : formatDateForInput(f);
    }

    const row = document.createElement('div');
    row.className = 'family-row';
    row.style.cssText = 'display:grid;grid-template-columns:1fr 2fr 1fr auto;gap:8px;align-items:end;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;padding:8px 10px;';
    row.innerHTML = `
        <div>
            <label class="dj-label">Parentesco</label>
            <select name="parentesco[]" class="dj-select">
                <option value="">—</option>
                ${['PADRE', 'MADRE', 'CONYUGE', 'HIJO']
            .map(p => `<option value="${p}" ${data.TIPO_RELA === p ? 'selected' : ''}>${p.charAt(0) + p.slice(1).toLowerCase()}</option>`).join('')}
            </select>
        </div>
        <div>
            <label class="dj-label">Apellidos y Nombres</label>
            <input type="text" name="apellidosNombres[]" class="dj-input" value="${data.Nombres || ''}" placeholder="Apellidos y nombres completos">
        </div>
        <div>
            <label class="dj-label">Fecha de Nacimiento</label>
            <input type="date" name="fechaNacimiento[]" class="dj-input" value="${fechaFormateada}">
        </div>
        <div>
            <button type="button" class="remove-family dj-btn-sm dj-btn-danger" style="margin-bottom:1px;">Eliminar</button>
        </div>`;
    // row.innerHTML = `
    // <div>
    //     <label class="dj-label">Parentesco</label>
    //     <select name="parentesco[]" class="dj-select">
    //         <option value="">—</option>
    //         ${['PADRE','MADRE','ESPOSO','ESPOSA','CONYUGE','HIJO','HIJA','HERMANO','HERMANA','ABUELO','ABUELA']
    //             .map(p => `<option value="${p}" ${data.TIPO_RELA===p?'selected':''}>${p.charAt(0)+p.slice(1).toLowerCase()}</option>`).join('')}
    //     </select>
    // </div>
    // <div>
    //     <label class="dj-label">Apellidos y Nombres</label>
    //     <input type="text" name="apellidosNombres[]" class="dj-input" value="${data.Nombres||''}" placeholder="Apellidos y nombres completos">
    // </div>
    // <div>
    //     <label class="dj-label">Fecha de Nacimiento</label>
    //     <input type="date" name="fechaNacimiento[]" class="dj-input" value="${fechaFormateada}">
    // </div>
    // <div>
    //     <button type="button" class="remove-family dj-btn-sm dj-btn-danger" style="margin-bottom:1px;">Eliminar</button>
    // </div>`;

    container.appendChild(row);
    row.querySelector('.remove-family')?.addEventListener('click', () => row.remove());
}

// ── Ubicaciones cascada ──────────────────────────────────────
const ubicacionCache = new Map();
const ubicacionPromise = new Map();

async function getUbicacionCached(params) {
    const key = JSON.stringify(params);

    if (ubicacionCache.has(key)) return ubicacionCache.get(key);
    if (ubicacionPromise.has(key)) return ubicacionPromise.get(key);

    const req = axios.get(`${API_URL}/dj/get-ubicacion`, { params })
        .then(res => {
            ubicacionCache.set(key, res.data);
            return res.data;
        })
        .finally(() => {
            ubicacionPromise.delete(key);
        });

    ubicacionPromise.set(key, req);
    return req;
}


async function cargarUbicaciones(tipo, dept, prov, dist) {
    const prefix = tipo === 'actual' ? '_actual' : tipo === 'dni' ? '_dni' : '_nac';
    if (!dept) return;

    const depts = await getUbicacionCached({ type: 'dept' });
    populateSelect(`#departamento${prefix}`, depts);
    setValue(`#departamento${prefix}`, dept);

    if (!prov) return;

    const provs = await getUbicacionCached({ type: 'prov', dept });
    populateSelect(`#provincia${prefix}`, provs);
    setValue(`#provincia${prefix}`, prov);

    if (!dist) return;

    const dists = await getUbicacionCached({ type: 'dist', prov });
    populateSelect(`#distrito${prefix}`, dists);
    setValue(`#distrito${prefix}`, dist);
}

// ── Helpers de DOM ───────────────────────────────────────────
function populateSelect(selector, data) {
    const sel = document.querySelector(selector);
    if (!sel) return;
    sel.innerHTML = '<option value="">Seleccionar...</option>';
    data.forEach(item => { const opt = document.createElement('option'); opt.value = item.id; opt.textContent = item.text; sel.appendChild(opt); });
}

function setValue(selector, value) {
    const id = selector.startsWith('#') ? selector : `#${selector}`;
    const el = document.querySelector(id);
    if (el) el.value = value || '';
}

function formatDateForInput(dateValue) {
    if (!dateValue) return '';

    // Si tiene T, extraer solo la parte YYYY-MM-DD directamente sin parsear
    if (typeof dateValue === 'string' && dateValue.includes('T')) {
        return dateValue.split('T')[0];
    }

    if (typeof dateValue === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(dateValue)) return dateValue;
    if (typeof dateValue === 'string' && dateValue.includes(' ')) return dateValue.split(' ')[0];
    if (typeof dateValue === 'string' && /^\d{2}[-/]\d{2}[-/]\d{4}$/.test(dateValue)) {
        const [dia, mes, anio] = dateValue.split(/[-/]/);
        return `${anio}-${mes}-${dia}`;
    }
    if (dateValue instanceof Date) {
        return `${dateValue.getFullYear()}-${String(dateValue.getMonth() + 1).padStart(2, '0')}-${String(dateValue.getDate()).padStart(2, '0')}`;
    }
    return '';
}

function animarContador(el, desde, hasta, duracion = 400) {
    if (!el) return;
    const inicio = performance.now();
    const diff = hasta - desde;
    function tick(ahora) {
        const t = Math.min((ahora - inicio) / duracion, 1);
        el.textContent = Math.round(desde + diff * (1 - Math.pow(1 - t, 3))).toLocaleString();
        if (t < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
}

// ============================================================
// SPLIT VIEW — Backup, diferencias, interactividad
// ============================================================
const CAMPO_MAP = {
    'apellido_paterno': 'APEL_1', 'apellido_materno': 'APEL_2',
    'nombre1': 'NOMB_1', 'nombre2': 'NOMB_2',
    'dni': 'NRO_DOCU_IDEN', 'caduca': 'PERS_FECHCADUCADNI',
    'estado_civil': 'ESCI_DESCRIPCION', 'sexo': 'PERS_SEXO',
    'fecha_nacimiento': 'FECH_NACI', 'sabe_nadar': 'PERS_SNADAR',
    'celular': 'PERS_TELEFONO', 'correo': 'PERS_EMAIL',
    'tipo_sangre': 'tipo_sangr', 'peso': 'peso_kilo',
    'talla': 'tall_metr', 'sistema_previsional': 'DESC_SIST_PENS',
    'essalud': 'ESSALUD', 'pensionista': 'PERS_PENSIONISTA',
    'grado_instruccion': 'NIED_ABREVIADO', 'anio_egreso': 'EGRESO_EDUCATIVO',
    'embargos': 'PERS_EMBARGO', 'consumo_sustancias': 'PERS_SMO',
    'direccion_actual': 'DIRECCION', 'direccion_dni': 'PERS_DIREC_DNI',
    'contacto_emergencia': 'PERS_NOMCONTACTO', 'celular_emergencia': 'PERS_NROEMERGENCIA',
    'parentesco_emergencia': 'PERS_EMERC_FAMILIAR', 'ocupacion_principal': 'PERS_PROFESION',
    'curso_sucamec': 'PERS_CONDISCAMEC', 'licencia_arma': 'PERS_NROLICENCIA',
    'tipo_arma': 'PERS_TIPOARMA', 'arma_propia': 'PERS_CONARMAS',
    'brevete': 'PERS_BREVETE', 'clase_brevete': 'CLASE_BREVETE',
    'empresa_anterior': 'PERS_CTRABANT', 'cargo_anterior': 'PERS_CARGOTRABANT',
    'smo': 'PERS_CONSMO',
};

const FECHA_FIELDS_BK = ['FECH_NACI', 'PERS_FECHCADUCADNI', 'FECH_INGRE', 'FECH_CESE'];

let _backupData = null;

async function cargarDatosBackup(codiPers) {
    const wrapper = document.getElementById('djSplitWrapper');
    const panelBk = document.getElementById('panelBackup');
    const badgeSplit = document.getElementById('splitModeBadge');
    const contDiffs = document.getElementById('contadorDiffs');
    if (!wrapper) return;

    try {
        const response = await axios.get(`${API_URL}/dj/get-backup-data`, { params: { codi_pers: codiPers } });

        if (!response.data.success) {
            wrapper.classList.add('no-backup');
            if (panelBk) panelBk.style.display = 'none';
            if (badgeSplit) badgeSplit.style.display = 'none';
            _backupData = null;
            return;
        }

        _backupData = response.data.data;
        wrapper.classList.remove('no-backup');
        if (panelBk) panelBk.style.display = 'block';
        if (badgeSplit) badgeSplit.style.display = 'flex';

        // Badge fecha mod
        const badge = document.getElementById('bkFechaModBadge');
        if (badge && _backupData.USUA_FECHA_MOD) {
            const f = new Date(_backupData.USUA_FECHA_MOD);
            if (!isNaN(f)) badge.textContent = `Últ. mod: ${String(f.getDate()).padStart(2, '0')}/${String(f.getMonth() + 1).padStart(2, '0')}/${f.getFullYear()}`;
        }

        // Reset visibilidad secciones tipo (fuera del forEach)
        // document.querySelectorAll('.bk-tipo-section').forEach(el => { el.style.display = ''; });

        const tipoActual = document.getElementById('tipo_personal')?.value?.trim() ?? '';
        aplicarVisibilidadBackup(tipoActual); // ← aplica DESPUÉS de que el backup ya está renderizado

        activarInteractividad();

        // Llenar campos backup
        wrapper.querySelectorAll('.bk-val[data-field]').forEach(el => {
            const field = el.getAttribute('data-field');
            let val = _backupData[field] ?? '';
            // if (FECHA_FIELDS_BK.includes(field) && val) {
            //     const d = new Date(val);
            //     if (!isNaN(d)) val = `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`;
            // }
            if (FECHA_FIELDS_BK.includes(field) && val) {
                const valStr = String(val);
                // Extraer YYYY-MM-DD sin crear Date (evita timezone)
                const match = valStr.match(/^(\d{4})-(\d{2})-(\d{2})/);
                if (match) {
                    val = `${match[3]}/${match[2]}/${match[1]}`; // DD/MM/YYYY
                }
            }
            el.textContent = val ? String(val).toUpperCase().trim() : '—';
        });

        if (window.allCarreras) {
            // Institución
            const bkInstEl = wrapper.querySelector('.bk-val[data-field="IEDU_CODIGO"]');
            if (bkInstEl && _backupData.IEDU_CODIGO) {
                const carreraMatch = window.allCarreras.find(c => c.IEDU_CODIGO === _backupData.IEDU_CODIGO?.trim());
                // La descripción de institución no está en allCarreras directamente,
                // pero sí podemos buscar en el select
                const selInst = document.getElementById('institucion');
                const optInst = selInst?.querySelector(`option[value="${_backupData.IEDU_CODIGO?.trim()}"]`);
                if (optInst) bkInstEl.textContent = optInst.textContent;
            }

            // Carrera
            const bkCarrEl = wrapper.querySelector('.bk-val[data-field="CARR_CODIGO"]');
            if (bkCarrEl && _backupData.CARR_CODIGO) {
                const carrera = window.allCarreras.find(c => c.id === _backupData.CARR_CODIGO?.trim());
                if (carrera) bkCarrEl.textContent = carrera.text;
            }
        }

        // Familiares backup
        const tbody = document.getElementById('bodyBackupFamiliares');
        if (tbody) {
            const familiares = response.data.familiares ?? [];
            tbody.innerHTML = familiares.length === 0
                ? `<tr><td colspan="3" style="padding:6px 8px;color:#9ca3af;font-style:italic;font-size:11px;">Sin familiares registrados</td></tr>`
                : familiares.map(f => `<tr><td>${f.TIPO_RELA ?? '—'}</td><td>${f.Nombres ? f.Nombres.toUpperCase().trim() : '—'}</td><td>${f.FECH_NACI ?? '—'}</td></tr>`).join('');
        }

        // Diferencias (con delay para que el form esté lleno)
        setTimeout(() => {
            const diffs = marcarDiferencias();
            if (contDiffs) {
                if (diffs > 0) { contDiffs.style.display = 'inline-block'; contDiffs.textContent = `${diffs} campo${diffs > 1 ? 's' : ''} diferente${diffs > 1 ? 's' : ''}`; }
                else contDiffs.style.display = 'none';
            }
        }, 300);

        activarInteractividad();

    } catch (err) {
        console.warn('Sin backup DJ:', err);
        wrapper.classList.add('no-backup');
        if (panelBk) panelBk.style.display = 'none';
        if (badgeSplit) badgeSplit.style.display = 'none';
        _backupData = null;
    }
}

function marcarDiferencias() {
    if (!_backupData) return 0;
    let totalDiffs = 0;

    document.querySelectorAll('[data-compare]').forEach(el => el.classList.remove('has-diff'));
    document.querySelectorAll('.bk-field').forEach(el => el.classList.remove('is-diff'));

    Object.entries(CAMPO_MAP).forEach(([formId, bkField]) => {
        const inputEl = document.getElementById(formId);
        if (!inputEl) return;

        let valForm = String(inputEl.value ?? '').trim();
        let valBk = String(_backupData[bkField] ?? '').trim();

        if (FECHA_FIELDS_BK.includes(bkField) && valBk) {
            valBk = valBk.replace('T', ' ').split(' ')[0];
        }

        let normForm = valForm.toUpperCase();
        let normBk = valBk.toUpperCase();

        console.log('a ', normForm);
        console.log('b ', normBk);
        console.log('c ', formId);

        if (formId === 'estado_civil') {
            const estadosCiviles = {
                '2007000001': 'SOLTERO',
                '2007000002': 'CASADO',
                '2007000003': 'DIVORCIADO',
                '2007000004': 'VIUDO',
                '2007000008': 'CONVIVIENTE'
            };

            normForm = (estadosCiviles[valForm] || '').toUpperCase();
        }

        if (formId === 'sistema_previsional') {
            const sistemasPensiones = {
                '01': 'SISTEMA NACIONAL DE PENSIONES',
                '02': 'AFP INTEGRA',
                '03': 'PROFUTURO AFP',
                '04': 'AFP HORIZONTE',
                '05': 'AFP UNION VIDA',
                '06': 'CAJA DE BENEFICIO DEL PESCADOR',
                '07': 'NO APORTACION',
                '10': 'AFP PRIMA',
                '11': 'AFP EL ROBLE',
                '27': 'AFP HABITAT'
            };

            normForm = (sistemasPensiones[valForm] || '').toUpperCase();
        }

        if (formId === 'grado_instruccion') {
            const gradosInstruccion = {
                '01': 'SIN EDUCACIÓN FORMAL',
                '02': 'ESPECIAL INCOMPLETA',
                '03': 'ESPECIAL COMPLETA',
                '04': 'PRIMARIA INCOMPLETA',
                '05': 'PRIMARIA COMPLETA',
                '06': 'SECUNDARIA INCOMPLETA',
                '07': 'SECUNDARIA COMPLETA',
                '08': 'TÉCNICA INCOMPLETA',
                '09': 'TÉCNICA COMPLETA',
                '10': 'SUPERIOR INCOMPLETA (INSTIT. SUPER)',
                '11': 'SUPERIOR COMPLETA (INSTIT SUPER)',
                '12': 'UNIVERSITARIA INCOMPLETA',
                '13': 'UNIVERSITARIA COMPLETA',
                '14': 'GRADO DE BACHILLER',
                '15': 'TITULADO',
                '16': 'ESTUD. MAESTRÍA INCOMPLETA',
                '17': 'ESTUD. MAESTRÍA COMPLETA',
                '18': 'GRADO DE MAESTRÍA',
                '19': 'ESTUD. DOCTORADO INCOMPLETO',
                '20': 'ESTUD. DOCTORADO COMPLETO',
                '21': 'GRADO DE DOCTOR'
            };

            normForm = (gradosInstruccion[valForm] || '').toUpperCase();
        }


        if (normForm && normBk && normForm !== normBk) {
            totalDiffs++;
            inputEl.classList.add('has-diff');
            document.querySelector(`.bk-field[data-bk="${formId}"]`)?.classList.add('is-diff');
        }
    });

    return totalDiffs;
}

function activarInteractividad() {
    const panelForm = document.getElementById('panelForm');
    if (!panelForm) return;

    if (panelForm._splitFocusIn) panelForm.removeEventListener('focusin', panelForm._splitFocusIn);
    if (panelForm._splitFocusOut) panelForm.removeEventListener('focusout', panelForm._splitFocusOut);

    panelForm._splitFocusIn = e => {
        const compareId = e.target.getAttribute('data-compare') || e.target.id;
        if (!compareId) return;
        document.querySelectorAll('.bk-field.is-active').forEach(el => el.classList.remove('is-active'));
        const bkEl = document.querySelector(`.bk-field[data-bk="${compareId}"]`);
        if (bkEl) {
            bkEl.classList.add('is-active');
            const panelBk = document.getElementById('panelBackup');
            if (panelBk) panelBk.scrollTop += (bkEl.getBoundingClientRect().top - panelBk.getBoundingClientRect().top) - 80;
        }
    };
    panelForm._splitFocusOut = () => {
        setTimeout(() => {
            if (!panelForm.contains(document.activeElement))
                document.querySelectorAll('.bk-field.is-active').forEach(el => el.classList.remove('is-active'));
        }, 150);
    };

    panelForm.addEventListener('focusin', panelForm._splitFocusIn);
    panelForm.addEventListener('focusout', panelForm._splitFocusOut);
}

function aplicarVisibilidadBackup(tipoCod) {
    const esOperativo = tipoCod == '03' || tipoCod == '01';
    const esAdministrativo = tipoCod == '05' || tipoCod == '02';

    document.querySelectorAll('.bk-tipo-section[data-bk-tipo="operativo"]')
        .forEach(el => { el.style.display = esAdministrativo ? 'none' : ''; });
    document.querySelectorAll('.bk-tipo-section[data-bk-tipo="administrativo"]')
        .forEach(el => { el.style.display = esOperativo ? 'none' : ''; });
}

// ── La función original queda sin los querySelectorAll del backup ──
function aplicarVisibilidadPorTipo(tipoCod) {
    const esOperativo = tipoCod == '03' || tipoCod == '01';
    const esAdministrativo = tipoCod == '05' || tipoCod == '02';
    const esEspecial = tipoCod == '06';

    // Solo afecta el panel del formulario (derecho)
    document.querySelectorAll('[data-tipo="operativo"]')
        .forEach(el => { el.style.display = esAdministrativo ? 'none' : ''; });
    document.querySelectorAll('[data-tipo="administrativo"]')
        .forEach(el => { el.style.display = esOperativo ? 'none' : ''; });

    let badge = document.getElementById('tipoBadgeModal');
    if (!badge) {
        badge = document.createElement('span');
        badge.id = 'tipoBadgeModal';
        badge.style.cssText = 'font-size:10px;font-weight:700;padding:2px 10px;border-radius:20px;margin-left:8px;letter-spacing:.04em;text-transform:uppercase;display:inline-block;';
        const headerTitle = document.querySelector('#modalDjGestion [style*="font-size:13px"]');
        if (headerTitle) headerTitle.parentNode.insertBefore(badge, headerTitle.nextSibling);
    }

    if (esOperativo) { badge.textContent = 'Operativo — RH 01'; badge.style.background = '#dbeafe'; badge.style.color = '#1e40af'; }
    else if (esAdministrativo) { badge.textContent = 'Administrativo — RH 02'; badge.style.background = '#d1fae5'; badge.style.color = '#065f46'; }
    else { badge.textContent = ''; }
}

function limpiarSplitView() {
    _backupData = null;
    const wrapper = document.getElementById('djSplitWrapper');
    const panelBk = document.getElementById('panelBackup');
    const badgeSplit = document.getElementById('splitModeBadge');
    const contDiffs = document.getElementById('contadorDiffs');

    if (wrapper) wrapper.classList.add('no-backup');
    if (panelBk) panelBk.style.display = 'none';
    if (badgeSplit) badgeSplit.style.display = 'none';
    if (contDiffs) contDiffs.style.display = 'none';

    document.querySelectorAll('.bk-val').forEach(el => el.textContent = '—');
    document.querySelectorAll('.bk-field').forEach(el => el.classList.remove('is-diff', 'is-active'));
    document.querySelectorAll('[data-compare]').forEach(el => el.classList.remove('has-diff'));
    document.querySelectorAll('.bk-tipo-section').forEach(el => { el.style.display = ''; });

    const tbody = document.getElementById('bodyBackupFamiliares');
    if (tbody) tbody.innerHTML = `<tr><td colspan="3" style="padding:6px;color:#9ca3af;font-style:italic;">Cargando...</td></tr>`;

    const badge = document.getElementById('bkFechaModBadge');
    if (badge) badge.textContent = '';
}

// ============================================================
// BOTONES REPORTES
// ============================================================
document.getElementById('btnReporteFaltantes')?.addEventListener('click', async function () {
    const sucursal = document.getElementById('filtroSucursal')?.value ?? '00';
    const tipoPer = document.getElementById('filtroTipoPer')?.value ?? '00';

    // Nuevo mapeo para los tipos de personal V2
    let codTipoPer = '00';
    if (tipoPer === 'OPERATIVO 4°') codTipoPer = '01';
    else if (tipoPer === 'OPERATIVO 5°') codTipoPer = '03';
    else if (tipoPer === 'ADMINISTRATIVO 4°') codTipoPer = '02';
    else if (tipoPer === 'ADMINISTRATIVO 5°') codTipoPer = '05';
    else if (tipoPer === 'ESPECIAL') codTipoPer = '06';

    const codSucursal = sucursal || '00';

    Swal.fire({ title: 'Generando reporte...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const response = await axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion-v2`, { params: { codSucursal, codTipoPer, tipo: 1 } });

        if (!response.data.success || !response.data.data.length) {
            Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros.' });
            return;
        }

        const todosLosDatos = response.data.data;
        const soloFaltantes = todosLosDatos.filter(d => d.SIP_CAMBIO === 'Falta');
        Swal.close();

        if (!soloFaltantes.length) {
            Swal.fire({ icon: 'info', title: 'Sin faltantes', text: 'Todo el personal está actualizado.' });
            return;
        }

        generarReporteFaltantesPDF(soloFaltantes, todosLosDatos);
    } catch {
        Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener los datos.' });
    }
});

document.getElementById('btnReporteActualizacion')?.addEventListener('click', async function () {
    const sucursal = document.getElementById('filtroSucursal')?.value ?? '00';
    const tipoPer = document.getElementById('filtroTipoPer')?.value ?? '00';
    const codTipoPer = tipoPer === 'OPERATIVO' ? '03' : tipoPer === 'ADMINISTRATIVO' ? '05' : '00';
    const codSucursal = sucursal || '00';

    Swal.fire({ title: 'Generando reporte...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    try {
        const response = await axios.get(`${VITE_URL_APP}/api/reporte-personal-sin-migracion-v2`, { params: { codSucursal, codTipoPer, tipo: null } });
        if (!response.data.success || !response.data.data.length) { Swal.fire({ icon: 'info', title: 'Sin datos', text: 'No hay registros.' }); return; }
        const todosLosDatos = response.data.data;
        const soloActualizados = todosLosDatos.filter(d => d.SIP_CAMBIO === 'Ok');
        Swal.close();
        if (!soloActualizados.length) { Swal.fire({ icon: 'info', title: 'Sin actualizados', text: 'No hay personal actualizado aún.' }); return; }
        generarReporteFaltantesPDF(soloActualizados, todosLosDatos);
    } catch { Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener los datos.' }); }
});

// Toggle colapsable DJ Anterior
document.getElementById('headerDJAnterior')?.addEventListener('click', function () {
    const cuerpo = document.getElementById('cuerpoDJAnterior');
    const icono = document.getElementById('iconoDJAnterior');
    if (!cuerpo) return;
    const abierto = cuerpo.style.display !== 'none';
    cuerpo.style.display = abierto ? 'none' : 'block';
    if (icono) icono.textContent = abierto ? '▼' : '▲';
});

// ── Filtrar carreras según institución seleccionada ──
document.getElementById('institucion')?.addEventListener('change', function () {
    const ieduCodigo = this.value;
    const selCarrera = document.getElementById('carrera');
    if (!selCarrera) return;

    selCarrera.innerHTML = '<option value="">—</option>';

    if (!ieduCodigo || !window.allCarreras) return;

    const carrerasFiltradas = window.allCarreras.filter(c => c.IEDU_CODIGO === ieduCodigo);

    carrerasFiltradas.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.text;
        selCarrera.appendChild(opt);
    });
});


// ============================================================
// DJ GENERADOS — localStorage helpers
// ============================================================
const DJ_STORAGE_KEY = 'dj_generados';

function getDJGenerados() {
    try {
        return JSON.parse(localStorage.getItem(DJ_STORAGE_KEY) || '{}');
    } catch { return {}; }
}

function marcarDJGenerado(codPersonal, fechaCambio) {
    const data = getDJGenerados();
    data[codPersonal] = {
        fechaMarcado: new Date().toISOString(),
        fechaCambio: fechaCambio || null,
    };
    localStorage.setItem(DJ_STORAGE_KEY, JSON.stringify(data));
}

function desmarcarDJGenerado(codPersonal) {
    const data = getDJGenerados();
    delete data[codPersonal];
    localStorage.setItem(DJ_STORAGE_KEY, JSON.stringify(data));
}

function estaGenerado(codPersonal, fechaCambioActual) {
    const data = getDJGenerados();
    const reg = data[codPersonal];
    if (!reg) return false;

    // Si el registro tuvo cambios DESPUÉS de que se marcó → ya no vale
    if (fechaCambioActual && reg.fechaCambio) {
        const cambio = new Date(fechaCambioActual);
        const marcado = new Date(reg.fechaMarcado);
        if (cambio > marcado) return false;
    }
    return true;
}

function limpiarDJGenerados() {
    localStorage.removeItem(DJ_STORAGE_KEY);
    /* tblPersonasMigrado.redraw(true);
     Swal.fire({ icon: 'success', title: 'Listo', text: 'Todas las marcas fueron eliminadas.', timer: 1800, showConfirmButton: false });*/
}


document.getElementById('btnResetearDJs')?.addEventListener('click', async function () {

    const generados = getDJGenerados();
    const totalMarcados = Object.keys(generados).length;

    if (totalMarcados === 0) {
        Swal.fire({ icon: 'info', title: 'Sin marcas', text: 'No hay registros marcados como generados.' });
        return;
    }

    const { isConfirmed } = await Swal.fire({
        icon: 'warning',
        title: 'Resetear todas las marcas',
        html: `Se eliminarán las marcas ✅ de <b>${totalMarcados}</b> registro(s).<br>
               <span style="font-size:12px;color:#6b7280;">Todos volverán a aparecer como pendientes.</span>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, resetear todo',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
    });

    if (!isConfirmed) return;

    limpiarDJGenerados();
    tblPersonasMigrado.redraw(true);

    Swal.fire({ icon: 'success', title: 'Listo', text: 'Todas las marcas fueron eliminadas.', timer: 1800, showConfirmButton: false });
});

// ============================================================
// EXTRAER FIRMA Y HUELLA — Extracción desde PDF DJ
// ============================================================
(function initExtractorFirmaHuella() {

    let _pdfDoc = null;
    let _lastPageCvs = null;

    const el = id => document.getElementById(id);

    // ── Conversión mm → px ────────────────────────────────────
    // A4 = 210mm × 297mm. El canvas renderizado tiene canvas.width = 210 * mmX
    const mmX = cvs => cvs.width / 210;   // px por mm horizontal
    const mmY = cvs => cvs.height / 297;   // px por mm vertical

    // ── Detectar borde horizontal en el canvas por píxeles ───────
    // Escanea filas horizontales buscando líneas oscuras (bordes del PDF).
    // Devuelve la Y en px del ÚLTIMO borde encontrado en [fromMm, toMm].
    // Funciona con cualquier escala de renderizado.
    function findHorizontalBorder(cvs, fromMm, toMm) {
        const scY = cvs.height / 297;
        const scX = cvs.width / 210;
        const yS = Math.round(fromMm * scY);
        const yE = Math.round(toMm * scY);
        // Muestrear solo la franja interior del box (interior a los márgenes)
        const xL = Math.round(12 * scX);
        const xR = Math.round(198 * scX);
        const sw = xR - xL;

        const { data } = cvs.getContext('2d').getImageData(xL, yS, sw, yE - yS);
        let last = -1, inBdr = false;

        for (let r = 0; r < yE - yS; r++) {
            let dark = 0;
            for (let c = 0; c < sw; c++) {
                const p = (r * sw + c) * 4;
                if ((data[p] + data[p + 1] + data[p + 2]) / 3 < 80) dark++;
            }
            if (dark / sw > 0.14 && !inBdr) { last = yS + r; inBdr = true; }
            else if (dark / sw <= 0.07) { inBdr = false; }
        }
        return last >= 0 ? last : null;
    }

    // ── Calcular región de recorte exacta ─────────────────────
    // Usa detección de píxeles para encontrar el borde superior de la
    // celda firma/huella — funciona igual para PDFs de 1 o 2 páginas.
    function getCropRegion(cvs) {
        const scX = mmX(cvs);
        const scY = mmY(cvs);
        const n = _pdfDoc?.numPages ?? 1;

        let firmaTopPx;
        if (n >= 2) {
            // Pág 2: titulo(5.5mm) + conformidad(17-28mm) → firma entre 22mm y 52mm
            firmaTopPx = findHorizontalBorder(cvs, 22, 52) ?? Math.round(32 * scY);
        } else {
            // Pág 1: firma al final — zona entre 205mm y 258mm
            firmaTopPx = findHorizontalBorder(cvs, 205, 258) ?? Math.round(236 * scY);
        }

        // Y de recorte: saltar el grosor del borde de la celda
        // La celda mide 45mm; excluir últimos 9mm de etiquetas → 36mm de contenido
        const inYTop = 2.0 * scY;   // clearance del borde (~0.3mm dibujado en PDF)
        const inYBot = 0.5 * scY;
        const yTop = firmaTopPx + inYTop;
        const yBottom = firmaTopPx + (45 - 9) * scY - inYBot;

        // X fijos (marginLeft=10, firmaW=114mm, huellaW=76mm), inset 3mm c/lado
        const firmaX = Math.round((10 + 3) * scX);   // → 108mm de ancho neto
        const firmaW = Math.round((114 - 6) * scX);
        const huellaX = Math.round((124 + 3) * scX);   // → 70mm de ancho neto
        const huellaW = Math.round((76 - 6) * scX);

        return {
            yTop: Math.max(0, Math.round(yTop)),
            yBottom: Math.min(cvs.height, Math.round(yBottom)),
            firmaX,
            firmaW,
            huellaX,
            huellaW,
        };
    }

    // ── Reiniciar modal ───────────────────────────────────────
    function resetModal() {
        el('fhStep1')?.classList.remove('hidden');
        el('fhStep2')?.classList.add('hidden');
        el('fhStep3')?.classList.add('hidden');
        el('fhFileInfo')?.classList.add('hidden');
        const inp = el('fhInputPdf');
        if (inp) inp.value = '';
        const cont = el('fhPagesContainer');
        if (cont) cont.innerHTML = '';
        _pdfDoc = null;
        _lastPageCvs = null;
    }

    // ── Abrir / cerrar modal ──────────────────────────────────
    function abrirModal() {
        resetModal();
        const modal = document.getElementById('modalExtFirmaHuella');
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        const modal = document.getElementById('modalExtFirmaHuella');
        if (!modal) return;
        modal.classList.add('hidden');
        document.body.style.overflow = '';
        resetModal();
    }

    // ── Cargar y validar PDF ──────────────────────────────────
    async function cargarPDF(file) {
        if (!file || file.type !== 'application/pdf') {
            Swal.fire({ icon: 'warning', title: 'Archivo inválido', text: 'Selecciona un archivo PDF.' });
            return;
        }
        if (!window.pdfjsLib) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'PDF.js no está disponible. Recarga la página.' });
            return;
        }
        try {
            const buf = await file.arrayBuffer();
            const pdfDoc = await pdfjsLib.getDocument({ data: buf }).promise;

            if (pdfDoc.numPages > 2) {
                Swal.fire({
                    icon: 'warning', title: 'Más de 2 páginas',
                    text: `El PDF tiene ${pdfDoc.numPages} páginas. Solo se procesará la última (donde está la firma).`,
                });
            }

            _pdfDoc = pdfDoc;
            const pages = Math.min(pdfDoc.numPages, 2);
            el('fhFileName').textContent = file.name;
            el('fhPageCount').textContent = `${pages} página${pages > 1 ? 's' : ''}`;
            el('fhFileInfo')?.classList.remove('hidden');
        } catch (err) {
            console.error('Error cargando PDF:', err);
            Swal.fire({ icon: 'error', title: 'Error al leer el PDF', text: err.message });
        }
    }

    // ── Renderizar páginas del PDF ────────────────────────────
    async function previsualizarPDF() {
        if (!_pdfDoc) return;

        const container = el('fhPagesContainer');
        container.innerHTML = `
            <div class="flex flex-col items-center justify-center py-10 text-gray-400">
                <i class='bx bx-loader-alt bx-spin text-3xl'></i>
                <p class="text-sm mt-2">Renderizando documento...</p>
            </div>`;

        el('fhStep2')?.classList.remove('hidden');
        el('fhStep3')?.classList.add('hidden');

        await new Promise(r => setTimeout(r, 60));
        container.innerHTML = '';

        const numPages = Math.min(_pdfDoc.numPages, 2);
        const scale = 1.4;

        for (let i = 1; i <= numPages; i++) {
            const page = await _pdfDoc.getPage(i);
            const viewport = page.getViewport({ scale });

            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'display:flex;flex-direction:column;align-items:center;';

            const label = document.createElement('div');
            label.style.cssText = 'font-size:11px;color:#9ca3af;margin-bottom:4px;';
            label.textContent = `Página ${i} de ${numPages}`;
            wrapper.appendChild(label);

            const canvasBox = document.createElement('div');
            canvasBox.style.cssText = 'position:relative;display:inline-block;max-width:100%;';

            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.style.cssText = 'display:block;max-width:100%;border:1px solid #d1d5db;border-radius:4px;box-shadow:0 1px 4px rgba(0,0,0,.08);';

            await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            canvasBox.appendChild(canvas);

            // Overlay solo en la última página
            if (i === numPages) {
                _lastPageCvs = canvas;
                const r = getCropRegion(canvas);
                const cW = canvas.width;
                const cH = canvas.height;

                // Zona firma (amarillo)
                const mkZone = (x, w, label) => {
                    const z = document.createElement('div');
                    z.style.cssText = `
                        position:absolute;
                        left:${x / cW * 100}%;
                        width:${w / cW * 100}%;
                        top:${r.yTop / cH * 100}%;
                        height:${(r.yBottom - r.yTop) / cH * 100}%;
                        border:2px dashed #f59e0b;
                        border-radius:2px;
                        background:rgba(245,158,11,.07);
                        pointer-events:none;
                        box-sizing:border-box;
                    `;
                    const b = document.createElement('div');
                    b.style.cssText = 'position:absolute;bottom:2px;left:50%;transform:translateX(-50%);font-size:9px;color:#d97706;white-space:nowrap;font-weight:700;';
                    b.textContent = label;
                    z.appendChild(b);
                    return z;
                };

                canvasBox.appendChild(mkZone(r.firmaX, r.firmaW, '✏ Firma'));
                canvasBox.appendChild(mkZone(r.huellaX, r.huellaW, '● Huella'));
            }

            wrapper.appendChild(canvasBox);
            container.appendChild(wrapper);
        }
    }

    // ── Extraer firma y huella del canvas ────────────────────
    function extraerImagenes() {
        if (!_lastPageCvs) {
            Swal.fire({ icon: 'warning', title: 'Sin previsualización', text: 'Presiona "Previsualizar PDF" primero.' });
            return;
        }

        const src = _lastPageCvs;
        const r = getCropRegion(src);
        const cH = r.yBottom - r.yTop;

        // Firma
        const cvF = el('fhCanvasFirma');
        cvF.width = r.firmaW;
        cvF.height = cH;
        cvF.getContext('2d').drawImage(src, r.firmaX, r.yTop, r.firmaW, cH, 0, 0, r.firmaW, cH);

        // Huella
        const cvH = el('fhCanvasHuella');
        cvH.width = r.huellaW;
        cvH.height = cH;
        cvH.getContext('2d').drawImage(src, r.huellaX, r.yTop, r.huellaW, cH, 0, 0, r.huellaW, cH);

        el('fhStep2')?.classList.add('hidden');
        el('fhStep3')?.classList.remove('hidden');
    }

    // ── Descargar canvas como PNG ─────────────────────────────
    function descargarCanvas(canvas, nombre) {
        if (!canvas || canvas.width === 0) return;
        const a = document.createElement('a');
        a.href = canvas.toDataURL('image/png');
        a.download = nombre + '.png';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    // ── Event listeners ───────────────────────────────────────
    const inputPdf = el('fhInputPdf');
    const dropZone = el('fhDropZone');

    inputPdf?.addEventListener('change', e => {
        const f = e.target.files?.[0];
        if (f) cargarPDF(f);
    });

    dropZone?.addEventListener('dragover', e => { e.preventDefault(); e.stopPropagation(); dropZone.classList.add('fh-drag-over'); });
    dropZone?.addEventListener('dragleave', () => dropZone.classList.remove('fh-drag-over'));
    dropZone?.addEventListener('drop', e => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove('fh-drag-over');
        const f = e.dataTransfer.files?.[0];
        if (f) cargarPDF(f);
    });

    el('fhBtnCambiar')?.addEventListener('click', e => {
        e.stopPropagation();
        el('fhFileInfo')?.classList.add('hidden');
        el('fhStep2')?.classList.add('hidden');
        el('fhStep3')?.classList.add('hidden');
        if (inputPdf) inputPdf.value = '';
        _pdfDoc = null;
        _lastPageCvs = null;
    });

    el('fhBtnPreview')?.addEventListener('click', previsualizarPDF);
    el('fhBtnExtract')?.addEventListener('click', extraerImagenes);
    el('fhBtnReintentar')?.addEventListener('click', () => {
        el('fhStep2')?.classList.remove('hidden');
        el('fhStep3')?.classList.add('hidden');
    });

    el('fhBtnDownloadFirma')?.addEventListener('click', () => descargarCanvas(el('fhCanvasFirma'), 'firma_registrada'));
    el('fhBtnDownloadHuella')?.addEventListener('click', () => descargarCanvas(el('fhCanvasHuella'), 'huella_registrada'));

    el('btnExtFirmaHuella')?.addEventListener('click', abrirModal);
    el('btnCerrarFHModal')?.addEventListener('click', cerrarModal);
    document.getElementById('modalExtFirmaHuella')?.addEventListener('click', function (e) {
        if (e.target === this) cerrarModal();
    });

})();