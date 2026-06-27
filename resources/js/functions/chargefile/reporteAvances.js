/**
 * reporteAvances.js
 * -----------------
 * Módulo principal del reporte de avances de documentos.
 * Gestiona el modal de filtros, los datos (prueba o API) y
 * delega la generación a reporteAvancesPDF.js / reporteAvancesExcel.js.
 *
 * Dependencias externas (cargar antes en el HTML o via import map):
 *   - jsPDF          : window.jspdf.jsPDF
 *   - jsPDF-AutoTable: se auto-registra en jsPDF
 *   - SheetJS (xlsx) : window.XLSX
 */

import { generarPDF }   from './reporteAvancesPDF.js';
import { generarExcel } from './reporteAvancesExcel.js';

// ---------------------------------------------------------------------------
// CONFIGURACIÓN DE FUENTE DE DATOS
// ---------------------------------------------------------------------------

/**
 * Cambia este valor a false para volver a los datos de prueba.
 */
const USAR_API = true;

/**
 * Endpoint del controlador ReporteAvancesController@index.
 * Acepta query params: ?sucursal=XX&tipo=OPER|ADMIN
 * Vacíos o ausentes = todos.
 */
const API_URL = `${ VITE_URL_APP }/api/rrhh/reporte-avances`;

// ---------------------------------------------------------------------------
// DATOS DE PRUEBA
// ---------------------------------------------------------------------------

/** @type {Array<Object>} Registros simulados del reporte */
const DATOS_PRUEBA = [
    { cod: 'P001', nombres: 'García López, Juan',        doc: '45123678', sucursal: 'LIMA',    tipo: 'OPER',  dj_subido: true,  firma_actualizada: true,  huella_actualizada: false, ultima_actualizacion: '2025-04-10' },
    { cod: 'P002', nombres: 'Martínez Ríos, Ana',        doc: '72345610', sucursal: 'LIMA',    tipo: 'ADMIN', dj_subido: true,  firma_actualizada: true,  huella_actualizada: true,  ultima_actualizacion: '2025-04-15' },
    { cod: 'P003', nombres: 'Torres Vega, Carlos',       doc: '60987321', sucursal: 'ICA',     tipo: 'OPER',  dj_subido: false, firma_actualizada: false, huella_actualizada: false, ultima_actualizacion: '2025-03-28' },
    { cod: 'P004', nombres: 'Quispe Mamani, Rosa',       doc: '48761234', sucursal: 'ICA',     tipo: 'OPER',  dj_subido: true,  firma_actualizada: false, huella_actualizada: true,  ultima_actualizacion: '2025-04-02' },
    { cod: 'P005', nombres: 'Flores Huanca, Pedro',      doc: '55432190', sucursal: 'TRUJILLO',tipo: 'ADMIN', dj_subido: true,  firma_actualizada: true,  huella_actualizada: true,  ultima_actualizacion: '2025-04-18' },
    { cod: 'P006', nombres: 'Ramos Chávez, Luisa',       doc: '41235678', sucursal: 'TRUJILLO',tipo: 'OPER',  dj_subido: false, firma_actualizada: true,  huella_actualizada: false, ultima_actualizacion: '2025-04-05' },
    { cod: 'P007', nombres: 'Díaz Paredes, Miguel',      doc: '69874512', sucursal: 'PIURA',   tipo: 'OPER',  dj_subido: true,  firma_actualizada: true,  huella_actualizada: true,  ultima_actualizacion: '2025-04-20' },
    { cod: 'P008', nombres: 'Sánchez Rojas, Patricia',   doc: '78123456', sucursal: 'PIURA',   tipo: 'ADMIN', dj_subido: false, firma_actualizada: false, huella_actualizada: false, ultima_actualizacion: '2025-03-15' },
    { cod: 'P009', nombres: 'Mendoza Cárdenas, Luis',    doc: '52347891', sucursal: 'LIMA',    tipo: 'OPER',  dj_subido: true,  firma_actualizada: false, huella_actualizada: true,  ultima_actualizacion: '2025-04-08' },
    { cod: 'P010', nombres: 'Vargas Espinoza, Carmen',   doc: '63219874', sucursal: 'CUSCO',   tipo: 'ADMIN', dj_subido: true,  firma_actualizada: true,  huella_actualizada: false, ultima_actualizacion: '2025-04-12' },
];

// ---------------------------------------------------------------------------
// OBTENCIÓN DE DATOS
// ---------------------------------------------------------------------------

/**
 * Obtiene los datos del reporte según los filtros seleccionados.
 * Si USAR_API es true, consulta el endpoint; de lo contrario filtra los datos de prueba.
 *
 * @param {string} sucursal  - Código de sucursal seleccionada ('' = todas)
 * @param {string} tipo      - 'OPER', 'ADMIN' o '' (todos)
 * @returns {Promise<Array>} - Listado de registros filtrados
 */
async function obtenerDatos(sucursal, tipo) {
    if (USAR_API) {
        const params = new URLSearchParams();

        // Solo enviar el filtro si tiene un valor real.
        // '00' es el código interno de "todos" — no se manda al servidor.
        if (sucursal && sucursal !== '00') params.append('sucursal', sucursal);
        if (tipo     && tipo     !== '00') params.append('tipo',     tipo);

        const respuesta = await fetch(`${API_URL}?${params.toString()}`);

        if (!respuesta.ok) {
            // Intentar leer el mensaje de error que devuelve el controlador
            let mensajeServidor = 'Error al obtener datos del servidor';
            try {
                const cuerpo = await respuesta.json();
                if (cuerpo?.message) mensajeServidor = cuerpo.message;
            } catch (_) { /* si no es JSON, usar el mensaje por defecto */ }

            throw new Error(mensajeServidor);
        }

        return respuesta.json();
    }

    // Filtrado local sobre datos de prueba
    // '00' en tipo equivale a "todos", se ignora como filtro
    return DATOS_PRUEBA.filter(r => {
        const matchSucursal = !sucursal || sucursal === '00' || r.sucursal === sucursal;
        const matchTipo     = !tipo     || tipo     === '00' || r.tipo     === tipo;
        return matchSucursal && matchTipo;
    });
}

// ---------------------------------------------------------------------------
// ESTADO DEL MODAL
// ---------------------------------------------------------------------------

/** Opciones de sucursal cargadas dinámicamente desde el select del sistema */
let sucursalesDisponibles = [];

// ---------------------------------------------------------------------------
// INICIALIZACIÓN
// ---------------------------------------------------------------------------

/**
 * Inicializa el módulo: carga sucursales, construye el modal e inyecta listeners.
 * Debe llamarse una vez que el DOM esté listo.
 */
function init() {
    cargarSucursales();
    inyectarModal();
    registrarEventos();
}

// ---------------------------------------------------------------------------
// CARGA DE SUCURSALES
// ---------------------------------------------------------------------------

/**
 * Lee las opciones del select #sucursal (ya renderizado por Blade)
 * para reutilizarlas en el modal del reporte.
 */
function cargarSucursales() {
    const selectSistema = document.getElementById('sucursal');
    if (!selectSistema) return;

    sucursalesDisponibles = Array.from(selectSistema.options)
        .filter(o => o.value && !o.disabled) // excluir placeholders disabled
        .map(o => ({ value: o.value, label: o.text }));
}

// ---------------------------------------------------------------------------
// INYECCIÓN DEL MODAL EN EL DOM
// ---------------------------------------------------------------------------

/**
 * Crea e inserta el modal de filtros del reporte en el body.
 * Usa el mismo patrón hs-overlay que el resto del sistema.
 */
function inyectarModal() {
    // Evitar duplicados si init() se llama más de una vez
    if (document.getElementById('modal-reporte-avances')) return;

    const opcionesSucursal = sucursalesDisponibles
        .map(s => `<option value="${s.value}">${s.label}</option>`)
        .join('');

    const html = `
    <!-- Trigger oculto para abrir el modal desde JS -->
    <button type="button"
            class="hidden"
            id="btn-abrir-modal-reporte"
            data-hs-overlay="#modal-reporte-avances">
    </button>

    <!-- Modal: Filtros del reporte de avances -->
    <div id="modal-reporte-avances"
         class="hs-overlay hidden fixed inset-0 z-70 overflow-y-auto
                transition-all duration-500 pointer-events-none">

        <div class="hs-overlay-open:translate-y-0 hs-overlay-open:opacity-100
                    translate-y-10 opacity-0 ease-in-out transition-all duration-500
                    sm:max-w-lg w-full my-8 sm:mx-auto flex flex-col bg-white
                    shadow-sm rounded-lg pointer-events-auto border border-gray-200">

            <!-- Cabecera -->
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-200">
                <h3 class="text-base font-medium text-gray-900">Reporte de Avances</h3>
                <button type="button"
                        class="text-gray-500 hover:text-gray-700 transition-colors"
                        data-hs-overlay="#modal-reporte-avances">
                    <i class="i-tabler-x text-lg"></i>
                </button>
            </div>

            <!-- Cuerpo: filtros -->
            <div class="px-5 py-5 space-y-5">

                <!-- Filtro: Sucursal -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Sucursal
                    </label>
                    <select id="rpt-sucursal" class="form-select w-full">
                      
                        ${opcionesSucursal}
                    </select>
                </div>

                <!-- Filtro: Tipo de personal -->
                <div>
                    <span class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Personal
                    </span>
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio"
                                   class="form-radio text-primary"
                                   name="rpt-tipo"
                                   value=""
                                   checked>
                            <span class="text-sm">Todos</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio"
                                   class="form-radio text-primary"
                                   name="rpt-tipo"
                                   value="OPER">
                            <span class="text-sm">Operativo</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio"
                                   class="form-radio text-primary"
                                   name="rpt-tipo"
                                   value="ADMIN">
                            <span class="text-sm">Administrativo</span>
                        </label>
                    </div>
                </div>

                <!-- Mensaje de error (oculto por defecto) -->
                <p id="rpt-error"
                   class="hidden text-sm text-red-600 bg-red-50 border border-red-200
                          rounded-lg px-3 py-2">
                </p>

            </div>

            <!-- Pie: botones de generación -->
            <div class="flex items-center justify-end gap-2 px-5 py-3 border-t border-gray-200">

                <!-- Spinner (oculto mientras no carga) -->
                <span id="rpt-spinner"
                      class="hidden text-sm text-gray-500 flex items-center gap-1">
                    <svg class="animate-spin h-4 w-4 text-gray-500"
                         xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Generando...
                </span>

                <!-- Botón PDF -->
                <button type="button"
                        id="btn-rpt-pdf"
                        class="btn bg-red-600 text-white hover:bg-red-700 transition-colors">
                    <i class="i-tabler-file-type-pdf me-1"></i> PDF
                </button>

                <!-- Botón Excel -->
                <button type="button"
                        id="btn-rpt-excel"
                        class="btn bg-green-600 text-white hover:bg-green-700 transition-colors">
                    <i class="i-tabler-file-spreadsheet me-1"></i> Excel
                </button>

                <!-- Cerrar -->
                <button type="button"
                        class="btn bg-gray-100 text-gray-700 hover:bg-gray-200"
                        data-hs-overlay="#modal-reporte-avances">
                    <i class="i-tabler-x me-1"></i> Cerrar
                </button>
            </div>

        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', html);
}

// ---------------------------------------------------------------------------
// EVENTOS
// ---------------------------------------------------------------------------

/**
 * Registra los eventos del botón principal y de los botones de generación.
 */
function registrarEventos() {
    // Abrir modal al hacer clic en "Reporte de avances"
    document.addEventListener('click', e => {
        if (e.target.closest('[data-accion="abrir-reporte-avances"]')) {
            document.getElementById('btn-abrir-modal-reporte')?.click();
        }
    });

    // Generar PDF
    document.addEventListener('click', async e => {
        if (!e.target.closest('#btn-rpt-pdf')) return;
        await ejecutarGeneracion('pdf');
    });

    // Generar Excel
    document.addEventListener('click', async e => {
        if (!e.target.closest('#btn-rpt-excel')) return;
        await ejecutarGeneracion('excel');
    });
}

// ---------------------------------------------------------------------------
// LEER FILTROS ACTIVOS
// ---------------------------------------------------------------------------

/**
 * Lee los valores actuales de los filtros del modal.
 * Devuelve el código de sucursal para la API y el nombre visible
 * del option para mostrarlo en el encabezado del reporte.
 *
 * @returns {{ sucursal: string, sucursalNombre: string, tipo: string }}
 */
function leerFiltros() {
    const selectSucursal = document.getElementById('rpt-sucursal');
    const sucursal       = selectSucursal?.value || '';
    // Tomar el texto visible del option seleccionado (ej. "LIMA", "TRUJILLO").
    // Si el value está vacío significa que se eligió "Todas las sucursales".
    const sucursalNombre = (sucursal && sucursal !== '00')
        ? (selectSucursal?.selectedOptions[0]?.text || 'Todas')
        : 'Todas';
    const tipo           = document.querySelector('input[name="rpt-tipo"]:checked')?.value || '';
    return { sucursal, sucursalNombre, tipo };
}

// ---------------------------------------------------------------------------
// ORQUESTACIÓN DE LA GENERACIÓN
// ---------------------------------------------------------------------------

/**
 * Obtiene los datos con los filtros activos y delega la generación
 * al módulo correspondiente (PDF o Excel).
 *
 * @param {'pdf'|'excel'} formato
 */
async function ejecutarGeneracion(formato) {
    const errorEl   = document.getElementById('rpt-error');
    const spinnerEl = document.getElementById('rpt-spinner');
    const btnPdf    = document.getElementById('btn-rpt-pdf');
    const btnExcel  = document.getElementById('btn-rpt-excel');

    // Ocultar errores previos y mostrar spinner
    errorEl.classList.add('hidden');
    spinnerEl.classList.remove('hidden');
    btnPdf.disabled   = true;
    btnExcel.disabled = true;

    try {
        const { sucursal, sucursalNombre, tipo } = leerFiltros();
        const datos = await obtenerDatos(sucursal, tipo);

        if (datos.length === 0) {
            mostrarError('No se encontraron registros con los filtros seleccionados.');
            return;
        }

        const meta = {
            // sucursalNombre: texto visible del select (ej. "LIMA"), no el código
            sucursal: (sucursal && sucursal !== '00') ? sucursalNombre : 'Todas',
            tipo    : (tipo     && tipo     !== '00') ? tipo           : 'Todos',
            fecha   : new Date().toLocaleDateString('es-PE', {
                day: '2-digit', month: '2-digit', year: 'numeric'
            }),
        };

        if (formato === 'pdf') {
            await generarPDF(datos, meta);
        } else {
            await generarExcel(datos, meta);
        }

    } catch (err) {
        console.error('[ReporteAvances]', err);
        mostrarError('Ocurrió un error al generar el reporte. Intente nuevamente.');
    } finally {
        spinnerEl.classList.add('hidden');
        btnPdf.disabled   = false;
        btnExcel.disabled = false;
    }
}

// ---------------------------------------------------------------------------
// UTILIDADES
// ---------------------------------------------------------------------------

/**
 * Muestra un mensaje de error en el modal.
 * @param {string} mensaje
 */
function mostrarError(mensaje) {
    const errorEl = document.getElementById('rpt-error');
    errorEl.textContent = mensaje;
    errorEl.classList.remove('hidden');
}

// ---------------------------------------------------------------------------
// EXPORTACIÓN E INICIO AUTOMÁTICO
// ---------------------------------------------------------------------------

// Arranca cuando el DOM esté completamente cargado
document.addEventListener('DOMContentLoaded', init);

export { obtenerDatos, leerFiltros };