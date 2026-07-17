import axios from 'axios';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import { jsPDF } from "jspdf";

const seleccionados = new Map(); // CODI_PERS -> rowData - para guardar el personal selecionado
let currentAbortController = null;

axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

new TomSelect('#cargos');
new TomSelect('#clientes');

getPersonal();
getFolios();

// Tabla de Personas
const tblPersonas = new Tabulator("#tblPersonas", {
    height: "380px",
    layout: "fitColumns",
    responsiveLayout: "collapse",
    pagination: true,
    paginationSize: 10,
    rowHeader: { formatter: "responsiveCollapse", width: 30, minWidth: 30, hozAlign: "center", resizable: false, headerSort: false },
    locale: "es",
    langs: {
        "es": {
            "pagination": {
                "first": "Primero",
                "first_title": "Primera Página",
                "last": "Último",
                "last_title": "Última Página",
                "prev": "Anterior",
                "prev_title": "Página Anterior",
                "next": "Siguiente",
                "next_title": "Página Siguiente",
                "all": "Todo"
            },
            "data": {
                "empty": "No hay datos disponibles"
            }
        }
    },
    columns: [
        { title: "Código", field: "CODI_PERS", hozAlign: "center", widthGrow: 1 },
        { title: "Personal", field: "personal", hozAlign: "left", widthGrow: 3 },
        { title: "Nro DOC", field: "nroDoc", hozAlign: "center", widthGrow: 1.5 },
        { title: "Sucursal", field: "sucursal", hozAlign: "center", widthGrow: 1.2 },
        {
            title: "Tipo", field: "TIPOTRAB", hozAlign: "center", widthGrow: 1.5, headerSort: false,
            formatter: function (cell) {
                const val = cell.getValue();
                if (!val) return '';
                
                // Usamos includes para atrapar tanto 4° como 5°
                if (val.includes('OPERATIVO')) {
                    return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 whitespace-nowrap">${val}</span>`;
                }
                if (val.includes('ADMINISTRATIVO')) {
                    return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700 whitespace-nowrap">${val}</span>`;
                }
                if (val === 'ESPECIALES') {
                    return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-yellow-100 text-yellow-700 whitespace-nowrap">${val}</span>`;
                }
                return val;
            }
        },
        {
            title: "", field: "select", hozAlign: "center", width: 40, headerSort: false,
            formatter: function (cell, formatterParams, onRendered) {
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.classList.add("form-checkbox", "rounded", "text-dark");
                checkbox.checked = cell.getValue() || false;
                checkbox.addEventListener("change", function () {
                    cell.setValue(checkbox.checked);
                    const data = cell.getRow().getData();
                    if (checkbox.checked) {
                        seleccionados.set(data.CODI_PERS, data);
                    } else {
                        seleccionados.delete(data.CODI_PERS);
                    }
                    actualizarContador();
                });
                return checkbox;
            },
        }
    ],
    rowFormatter: function (row) {
        const data = row.getData();
        if (data.PERS_VIGENCIA && data.PERS_VIGENCIA.toString().trim().toUpperCase() === 'NO') {
            row.getElement().style.setProperty("background-color", "#fef2f2", "important");
        } else {
            row.getElement().style.removeProperty("background-color");
        }
    },
});

// Reaplica rowFormatter tras paginar / ordenar / filtrar
function reformatPersonas() { tblPersonas.getRows("active").forEach(r => r.reformat()); }
tblPersonas.on("dataLoaded", reformatPersonas);
tblPersonas.on("pageLoaded", reformatPersonas);
tblPersonas.on("dataSorted", () => { tblPersonas.setPage(1); reformatPersonas(); });
tblPersonas.on("dataFiltered", () => { tblPersonas.setPage(1); reformatPersonas(); });

// Para activar todos los checkbox del listado de personas
document.getElementById('select-all-per').addEventListener('change', function () {
    const isChecked = this.checked;
    tblPersonas.getRows("active").forEach(row => {
        const cb = row.getCell("select").getElement().querySelector('input[type="checkbox"]');
        cb.checked = isChecked;
        row.getCell("select").setValue(isChecked);
        const data = row.getData();
        isChecked ? seleccionados.set(data.CODI_PERS, data) : seleccionados.delete(data.CODI_PERS);
    });
    actualizarContador();
});

// Tabla de Folios
const tblFolios = new Tabulator("#tblFolios", {
    height: "100%",
    layout: "fitDataFill",
    responsiveLayout: "collapse",
    pagination: true,
    paginationSize: 10,
    locale: "es",
    langs: {
        "es": {
            "pagination": {
                "first": "Primero",
                "first_title": "Primera Página",
                "last": "Último",
                "last_title": "Última Página",
                "prev": "Anterior",
                "prev_title": "Página Anterior",
                "next": "Siguiente",
                "next_title": "Página Siguiente",
                "all": "Todo"
            },
            "headerFilters": {
                "default": "Filtrar...",
            },
            "ajax": {
                "loading": "Cargando datos...",
                "error": "Error al cargar datos"
            },
            "data": {
                "empty": "No hay datos disponibles"
            }
        }
    },
    columns: [
        { title: "Folios", field: "nombre", hozAlign: "left", width: '60%' },
        {
            title: "Tipo", field: "tipoFolio", hozAlign: "center", width: '32%',
            formatter: function (cell, formatterParams) {
                var tipo = cell.getValue();
                if (tipo === "FORMATO") {
                    return '<span class="text-yellow2 font-bold">FORMATO</span>'
                } else if (tipo === "DOCUMENTO") {
                    return '<span class="text-barnie font-bold">DOCUMENTO</span>'
                } else if (tipo === "CERTIFICADO") {
                    return '<span class="text-green font-bold">CERTIFICADO</span>'
                }
                return tipo;
            }
        },
        {
            title: "", field: "select", hozAlign: "center", width: '5%', headerSort: false,
            formatter: function (cell, formatterParams, onRendered) {
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.classList.add("form-checkbox", "rounded", "text-dark");
                checkbox.checked = cell.getValue() || false; // Establece si está seleccionado según el valor de la celda
                checkbox.addEventListener("change", function () {
                    cell.setValue(checkbox.checked);

                    setTimeout(() => {
                        // Mostrar todos los registros (limpiar filtro) y el input
                        tblFolios.clearFilter();
                        document.getElementById('buscarFol').value = "";

                        // Reordenar: seleccionados primero
                        const allData = tblFolios.getData();

                        const selected = allData.filter(row => row.select === true);
                        const unselected = allData.filter(row => row.select !== true);

                        const sortedData = selected.concat(unselected);

                        tblFolios.replaceData(sortedData);
                    }, 300);
                });
                return checkbox;
            },
        }
    ]
});


// Tabla de Legajos
const tblLegajos = new Tabulator("#tblLegajos", {
    height: "100%",
    layout: "fitColumns",
    responsiveLayout: "collapse",
    columns: [
        { title: "Documento", field: "folio", hozAlign: "left", widthGrow: 1 },
        {
            title: "", field: "select", hozAlign: "center", width: 40, headerSort: false,
            formatter: function (cell) {
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.classList.add("form-checkbox", "rounded", "text-dark");
                checkbox.checked = cell.getValue() !== false;
                checkbox.addEventListener("change", function () {
                    cell.setValue(checkbox.checked);
                });
                return checkbox;
            }
        }
    ]
});


document.getElementById('select-all-fol').addEventListener('change', function () {
    const isChecked = this.checked;
    const rows = tblFolios.getRows();
    rows.forEach(row => {
        const rowCheckbox = row.getCell("select").getElement().querySelector('input[type="checkbox"]');
        rowCheckbox.checked = isChecked;
        row.getCell("select").setValue(isChecked);
    });
});

document.getElementById('select-all-leg').addEventListener('change', function () {
    const isChecked = this.checked;
    tblLegajos.getRows().forEach(row => {
        const cb = row.getCell("select").getElement().querySelector('input[type="checkbox"]');
        if (cb) cb.checked = isChecked;
        row.getCell("select").setValue(isChecked);
    });
});

document.getElementById('btnLeg2').classList.add("hidden");
document.getElementById('btnLeg1').classList.add("hidden");
document.getElementById('btnLeg3').classList.add("hidden");

// Obtener todos los enlaces dentro de las cards
const links = document.querySelectorAll('.card a');


function getPersonasSeleccionadas() {
    return [...seleccionados.values()];
}

function getFoliosSeleccionados() {
    return tblFolios.getRows()
        .filter(row => row.getCell("select").getValue())
        .map(row => row.getData());
}

function actualizarContador() {
    const count = seleccionados.size;
    document.getElementById('cntSeleccionados').textContent = count;
    document.getElementById('btnVerSeleccionados').classList.toggle('hidden', count === 0);

    const radioSep = document.getElementById('radioSeparado');
    const labelSep = document.getElementById('labelSeparado');
    if (count > 1) {
        radioSep.disabled = false;
        labelSep.style.opacity = '1';
        labelSep.style.cursor = 'pointer';
    } else {
        radioSep.disabled = true;
        radioSep.checked = false;
        labelSep.style.opacity = '0.4';
        labelSep.style.cursor = 'not-allowed';
        document.querySelector('input[name="modoGenerar"][value="unico"]').checked = true;
    }
}


function getTipoBadge(tipo) {
    if (!tipo) return '';
    if (tipo.includes('OPERATIVO'))
        return `<span class="text-[10px] px-1.5 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold whitespace-nowrap">${tipo}</span>`;
    if (tipo.includes('ADMINISTRATIVO'))
        return `<span class="text-[10px] px-1.5 py-0.5 rounded-full bg-purple-100 text-purple-700 font-bold whitespace-nowrap">${tipo}</span>`;
    if (tipo === 'ESPECIALES')
        return `<span class="text-[10px] px-1.5 py-0.5 rounded-full bg-yellow-100 text-yellow-700 font-bold whitespace-nowrap">${tipo}</span>`;
    return `<span class="text-[10px] text-default-400">${tipo}</span>`;
}

function renderListaModal(filtro) {
    const f = filtro.toLowerCase().trim();
    const lista = [...seleccionados.values()];
    const resultado = f ? lista.filter(p =>
        (p.personal || '').toLowerCase().includes(f) || (p.CODI_PERS || '').toLowerCase().includes(f)
    ) : lista;

    document.getElementById('listaModalSeleccionados').innerHTML = resultado.length
        ? resultado.map((p, i) => `
            <li class="py-3 flex items-center gap-3">
                <span class="w-6 h-6 rounded-full bg-default-100 text-default-500 text-xs font-bold flex items-center justify-center flex-shrink-0">${i + 1}</span>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-default-800 truncate">${p.personal || '-'}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="font-mono text-xs text-default-400">${p.CODI_PERS}</span>
                        ${getTipoBadge(p.TIPOTRAB)}
                    </div>
                </div>
                <button data-quitar="${p.CODI_PERS}" title="Quitar"
                    class="text-default-300 hover:text-red-500 hover:bg-red-50 transition-colors flex-shrink-0 p-1.5 rounded-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </li>`).join('')
        : `<li class="py-8 text-center text-default-400 text-sm">Sin resultados</li>`;
}

function quitarPersona(codiPers) {
    seleccionados.delete(codiPers);

    // Desmarcar checkbox en tblPersonas
    tblPersonas.getRows().forEach(row => {
        if (row.getData().CODI_PERS === codiPers) {
            const cb = row.getCell("select").getElement().querySelector('input[type="checkbox"]');
            if (cb) cb.checked = false;
            row.getCell("select").setValue(false);
        }
    });

    actualizarContador();
    document.getElementById('cntModalSel').textContent = seleccionados.size;
    renderListaModal(document.getElementById('buscarModalSel').value);

    if (seleccionados.size === 0) {
        document.getElementById('modalSeleccionados').classList.replace('flex', 'hidden');
    }
}

document.getElementById('listaModalSeleccionados').addEventListener('click', function (e) {
    const btn = e.target.closest('[data-quitar]');
    if (btn) quitarPersona(btn.dataset.quitar);
});

document.getElementById('btnVerSeleccionados').addEventListener('click', function () {
    document.getElementById('buscarModalSel').value = '';
    document.getElementById('cntModalSel').textContent = seleccionados.size;
    renderListaModal('');
    const modal = document.getElementById('modalSeleccionados');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => document.getElementById('buscarModalSel').focus(), 50);
});

document.getElementById('buscarModalSel').addEventListener('input', function () {
    renderListaModal(this.value);
});

document.getElementById('btnCerrarModalSel').addEventListener('click', () => {
    document.getElementById('modalSeleccionados').classList.replace('flex', 'hidden');
});

document.getElementById('modalSeleccionados').addEventListener('click', function (e) {
    if (e.target === this) this.classList.replace('flex', 'hidden');
});
document.querySelector('input[name="modoGenerar"][value="separado"]').addEventListener('change', function () {
    if (!this.checked) return;
    const cant = seleccionados.size;
    Swal.fire({
        title: '¿Generar por separado?',
        html: `Se generarán <b>${cant === 0 ? 'N' : cant}</b> PDF${cant !== 1 ? 's' : ''} por separado, uno por cada persona seleccionada.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'No, cancelar',
        confirmButtonColor: '#0ea5e9',
    }).then(result => {
        if (!result.isConfirmed) {
            document.querySelector('input[name="modoGenerar"][value="unico"]').checked = true;
        }
    });
});

links.forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault(); // Prevenir el comportamiento por defecto del enlace

        // Eliminar la clase 'active' de todas las cards
        document.querySelectorAll('.card').forEach(card => {
            card.classList.remove('active');
        });

        // Agregar la clase 'active' a la card actual
        this.closest('.card').classList.add('active');
    });
});

// Función para el MOSTRAR LAS CARDS de cada LEGAJO
document.getElementById("legajo1").addEventListener("click", function () {
    document.getElementById('personasDiv').classList.remove("hidden");
    document.getElementById('foliosDiv').classList.remove("hidden");
    document.getElementById('legajosDiv').classList.add("hidden");
    document.getElementById('btnLeg2').classList.add("hidden");
    document.getElementById('btnLeg3').classList.add("hidden");
    document.getElementById('btnLeg1').classList.remove("hidden");
    document.getElementById('modoGenerarDiv').classList.remove("hidden");
    document.getElementById('chkSinCaratulaDiv').classList.remove("hidden");
    tblPersonas.redraw();
});

document.getElementById("legajo2").addEventListener("click", function () {
    document.getElementById('personasDiv').classList.remove("hidden");
    document.getElementById('legajosDiv').classList.remove("hidden");
    document.getElementById('foliosDiv').classList.add("hidden");
    document.getElementById('btnLeg1').classList.add("hidden");
    document.getElementById('btnLeg3').classList.add("hidden");
    document.getElementById('btnLeg2').classList.remove("hidden");
    document.getElementById('modoGenerarDiv').classList.remove("hidden");
    document.getElementById('chkSinCaratulaDiv').classList.remove("hidden");
    tblPersonas.redraw();
});


// document.getElementById("legajo3").addEventListener("click", function () {
//     document.getElementById('personasDiv').classList.add("hidden");
//     document.getElementById('legajosDiv').classList.add("hidden");
//     document.getElementById('foliosDiv').classList.add("hidden");
//     document.getElementById('btnLeg1').classList.add("hidden");
//     document.getElementById('btnLeg2').classList.add("hidden");
//     document.getElementById('btnLeg3').classList.remove("hidden");

// });
// document.getElementById("legajo4").addEventListener("click", function () {
//     document.getElementById('personasDiv').classList.add("hidden");
//     document.getElementById('legajosDiv').classList.add("hidden");
//     document.getElementById('foliosDiv').classList.add("hidden");
//     document.getElementById('btnLeg1').classList.add("hidden");
//     document.getElementById('btnLeg2').classList.add("hidden");

// });

// Llenado de la tabla de legajos
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('clientes').addEventListener('change', function () {
        document.getElementById('divCargos').classList.remove("hidden");
    });

    document.getElementById('cargos').addEventListener('change', function () {
        getLegajosConFolios();
    });
});

// Carga los folios del cliente/cargo en tblLegajos con todos seleccionados por defecto
function getLegajosConFolios() {
    const cliente = document.getElementById('clientes').value;
    const cargo   = document.getElementById('cargos').value;
    if (!cliente || !cargo) return;

    axios.get(`${VITE_URL_APP}/api/get-folios-cliente-cargo`, {
        params: { cliente, cargo }
    }).then(response => {
        const data = response.data.map(f => ({ ...f, select: true }));
        tblLegajos.setData(data);
        document.getElementById('tblLegajos').classList.remove('hidden');
        document.getElementById('legajosSelectAllDiv').classList.remove('hidden');
        // Resetear checkbox "TODOS" a marcado
        const chkAll = document.getElementById('select-all-leg');
        if (chkAll) chkAll.checked = true;
    }).catch(error => {
        console.error("Error al obtener folios cliente/cargo:", error);
    });
}

// Devuelve solo los folios marcados en tblLegajos
function getFoliosSeleccionadosLegajo() {
    return tblLegajos.getRows()
        .filter(row => row.getCell("select").getValue() !== false)
        .map(row => {
            const d = row.getData();
            return { nombre: d.folio, codigo: d.codigo };
        });
}

// Función para actualizar la tabla de personas por SUCURSAL
function aplicarFiltrosPersonal() {
    const filtros = [];
    const sucursalEl = document.getElementById('sucursal');
    if (sucursalEl.value) {
        filtros.push({ field: 'sucursal', type: '=', value: sucursalEl.value });
    }
    const tipoEl = document.getElementById('tipoPerFiltro');
    const tipo = tipoEl ? tipoEl.value : 'TODOS';
    if (tipo && tipo !== 'TODOS') filtros.push({ field: 'TIPOTRAB', type: '=', value: tipo });
    const buscar = document.getElementById('buscarPer').value.toLowerCase().trim();
    if (buscar) filtros.push([
        { field: 'CODI_PERS', type: 'like', value: buscar },
        { field: 'personal', type: 'like', value: buscar },
        { field: 'nroDoc', type: 'like', value: buscar },
        { field: 'sucursal', type: 'like', value: buscar },
    ]);
    filtros.length > 0 ? tblPersonas.setFilter(filtros) : tblPersonas.clearFilter();
}

document.getElementById('sucursal').addEventListener('change', aplicarFiltrosPersonal);
document.getElementById('tipoPerFiltro').addEventListener('change', aplicarFiltrosPersonal);
document.getElementById('filtroVigenciaLeg').addEventListener('change', getPersonal);

// Función para actualizar la tabla de folios por TIPO
function filterTableByTipoFolio() {
    const tipoFolioSeleccionado = document.querySelector('input[name="tipo_folio"]:checked').value;
    tblDocs.setFilter("tipo_folio", "=", tipoFolioSeleccionado);
}

// Escuchar los cambios en los radio buttons
document.querySelectorAll('input[name="tipo_folio"]').forEach(radio => {
    radio.addEventListener('change', filterTableByTipoFolio);
});

// Función para BUSCAR
document.getElementById("buscarPer").addEventListener("keyup", aplicarFiltrosPersonal);

document.getElementById("buscarFol").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblFolios.setFilter([
        [
            { field: "nombre", type: 'like', value: valor },
            { field: "periodo", type: 'like', value: valor },
            { field: "tipoFolio", type: 'like', value: valor },
        ]
    ]);
});

// Función para el BOTON GENERAR PDF
document.getElementById("btnLeg1").addEventListener("click", async function () {
    const personas = getPersonasSeleccionadas();
    const folios = getFoliosSeleccionados();

    if (personas.length === 0) {
        Swal.fire('Atención', 'Seleccione al menos una persona.', 'warning');
        return;
    }
    if (folios.length === 0) {
        Swal.fire('Atención', 'Seleccione al menos un folio.', 'warning');
        return;
    }

    currentAbortController = new AbortController();
    const { signal } = currentAbortController;

    Swal.fire({
        title: 'Generando LEGAJO...',
        html: '<span id="swal-generar-text">Preparando solicitud...</span>',
        allowOutsideClick: false,
        showCancelButton: true,
        cancelButtonText: 'Cancelar generación',
        cancelButtonColor: '#ef4444',
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    }).then(result => {
        if (result.dismiss === Swal.DismissReason.cancel) {
            currentAbortController?.abort();
        }
    });

    try {
        if (getModoGenerar() === 'unico') {
            await getArchivosXPersonas(personas, folios, signal);
        } else {
            for (const persona of personas) {
                if (signal.aborted) break;
                try {
                    await getArchivosXPersona_uno(persona, folios, 1, signal);
                } catch (e) {
                    if (e.code === 'ERR_CANCELED' || e.name === 'CanceledError') break;
                    console.warn("Falló para persona:", persona.CODI_PERS);
                }
            }
        }
        Swal.close();
    } catch (e) {
        if (e.code === 'ERR_CANCELED' || e.name === 'CanceledError') {
            Swal.fire({ title: 'Cancelado', text: 'La generación fue cancelada.', icon: 'info', timer: 2000, showConfirmButton: false });
        } else {
            Swal.fire('Error', 'No se pudo generar el PDF. Intente nuevamente.', 'error');
            console.error("Error generando PDF:", e);
        }
    } finally {
        currentAbortController = null;
    }
});

function getModoGenerar() {
    const radio = document.querySelector('input[name="modoGenerar"]:checked');
    return radio ? radio.value : 'unico';
}


document.getElementById("btnLeg2").addEventListener("click", async function () {
    const personas = getPersonasSeleccionadas();

    if (personas.length === 0) {
        Swal.fire('Atención', 'Seleccione al menos una persona.', 'warning');
        return;
    }

    const cliente = document.getElementById('clientes').value;
    const cargo = document.getElementById('cargos').value;

    if (!cliente || !cargo) {
        Swal.fire('Atención', 'Seleccione cliente y cargo.', 'warning');
        return;
    }

    const folios = getFoliosSeleccionadosLegajo();

    if (folios.length === 0) {
        Swal.fire('Atención', 'Seleccione al menos un folio de la lista.', 'warning');
        return;
    }

    currentAbortController = new AbortController();
    const { signal } = currentAbortController;

    Swal.fire({
        title: 'Generando LEGAJO...',
        html: '<span id="swal-generar-text">Preparando solicitud...</span>',
        allowOutsideClick: false,
        showCancelButton: true,
        cancelButtonText: 'Cancelar generación',
        cancelButtonColor: '#ef4444',
        showConfirmButton: false,
        didOpen: () => Swal.showLoading()
    }).then(result => {
        if (result.dismiss === Swal.DismissReason.cancel) {
            currentAbortController?.abort();
        }
    });

    try {
        if (getModoGenerar() === 'unico') {
            await getArchivosXPersonas(personas, folios, signal);
        } else {
            for (const persona of personas) {
                if (signal.aborted) break;
                try {
                    await getArchivosXPersona_uno(persona, folios, 1, signal);
                } catch (e) {
                    if (e.code === 'ERR_CANCELED' || e.name === 'CanceledError') break;
                    console.warn("Falló para persona:", persona.CODI_PERS);
                }
            }
        }
        Swal.close();
    } catch (e) {
        if (e.code === 'ERR_CANCELED' || e.name === 'CanceledError') {
            Swal.fire({ title: 'Cancelado', text: 'La generación fue cancelada.', icon: 'info', timer: 2000, showConfirmButton: false });
        } else {
            Swal.fire('Error', 'No se pudo generar el PDF. Intente nuevamente.', 'error');
            console.error("Error generando PDF:", e);
        }
    } finally {
        currentAbortController = null;
    }
});



// Función para el BOTON GENERAR PDF 2
// document.getElementById("btnLeg2").addEventListener("click", async function() {

//     tblPersonas.getRows().forEach(function(row) {
//         if (row.getCell("select").getValue()) {

//         }
//     });

//     const cliente = document.getElementById('clientes').value;
//     const cargo = document.getElementById('cargos').value;
//     const foliosData = await getFoliosClienteCargo(cliente, cargo);

//     var selectedFolios = foliosData.map(folio => ({
//         nombre: folio.folio,
//         codigo: folio.codigo
//     }));

//     getArchivosXPersonas(selectedPersonas, selectedFolios, 2);
// });

// Función para el BOTON GENERAR PDF 3
document.getElementById("btnLeg3").addEventListener("click", async function () {
    /*var selectedPersonas = [];
    tblPersonas.getRows().forEach(function(row) {
        if (row.getCell("select").getValue()) {
            selectedPersonas.push(row.getData());
        }
    });

    const cliente = document.getElementById('clientes').value;
    const cargo = document.getElementById('cargos').value;
    const foliosData = await getFoliosClienteCargo(cliente, cargo);

    var selectedFolios = foliosData.map(folio => ({
        nombre: folio.folio,
        codigo: folio.codigo
    }));

    getArchivosXPersonas(selectedPersonas, selectedFolios, 3);*/
    //Para las pruebas del generador de PDF
    //alert("Hola");

    try {
        const response = await axios.post(`${VITE_URL_APP}/pdf_vacio`, {}, {
            responseType: 'blob'  // Muy importante para recibir archivos binarios (PDF)
        });

        // Crear URL para descargar/abrir el PDF
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));

        // Abrir el PDF en una nueva pestaña
        window.open(url);

        // Opcional: liberar el objeto URL después de usar
        setTimeout(() => window.URL.revokeObjectURL(url), 10000);

    } catch (error) {
        console.error('Error al generar PDF vacío:', error);
    }

});


// Función para generar el PDF
function generarPDF(data, signal) {
    return new Promise((resolve, reject) => {
        const el = document.getElementById('swal-generar-text');
        if (el) el.textContent = 'Generando PDF, por favor espera...';

        axios.post(`${VITE_URL_APP}/generar-pdf`, {
            resultados: data,
            sinCaratula: document.getElementById('chkSinCaratula')?.checked || false
        }, {
            responseType: 'blob',
            signal: signal,
            timeout: 600000
        })
            .then(response => {
                const blob = new Blob([response.data], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = url;
                const nombreArchivo = response.headers['x-nombre-archivo'] || 'reporte.pdf';
                link.download = nombreArchivo;
                link.click();
                resolve();
            })
            .catch(error => {
                reject(error);
            });
    });
}



// Función para generar el PDF
function generarPDF2(data) {
    axios.post(`${VITE_URL_APP}/generar-pdf2`, {
        resultados: data
    }, {
        responseType: 'blob' // Para recibir el PDF
    })
        .then(response => {
            const blob = new Blob([response.data], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'reporte.pdf';
            link.click();
        })
        .catch(error => {
            console.error("Error al generar el PDF:", error);
        });
}



//========================================== DATA CON AXIOS ==========================================//
// Función para obtener el listados de personas
function getPersonal() {
    const vigenciaEl = document.getElementById('filtroVigenciaLeg');
    const vigencia = vigenciaEl ? vigenciaEl.value : 'SI';

    axios.get(`${VITE_URL_APP}/api/get-personal-legajos-pdf`, {
        params: { vigencia }
    })
        .then(response => {
            tblPersonas.setData(response.data);
        })
        .catch(error => {
            console.error("Hubo un error:", error);
        });
}
// Función para obtener los folios
function getFolios() {
    axios.get(`${VITE_URL_APP}/api/get-folios`)
        .then(response => {
            tblFolios.setData(response.data);
        })
        .catch(error => {
            console.error("Error al obtener los datos:", error);
        });
};



async function getArchivosXPersona_uno(persona, selectedFolios, tipo, signal) {
    if (tipo == 3) return;

    const el = document.getElementById('swal-generar-text');
    if (el) el.textContent = `Obteniendo documentos de ${persona.personal || persona.CODI_PERS}...`;

    const response = await axios.post(`${VITE_URL_APP}/api/get-folios-persona_uno`, {
        codPersona: persona.CODI_PERS,
        persona: persona,
        folios: selectedFolios,
    }, { signal, timeout: 120000 });

    await generarPDF(response.data, signal);
}




async function getArchivosXPersonas(selectedPersonas, selectedFolios, signal) {
    const el = document.getElementById('swal-generar-text');
    if (el) el.textContent = 'Obteniendo documentos...';

    const response = await axios.post(`${VITE_URL_APP}/api/get-folios-personas`, {
        personas: selectedPersonas,
        folios: selectedFolios,
    }, { signal, timeout: 120000 });

    await generarPDF(response.data, signal);
}

// Función para obtener los folios por persona
function getDocsObligatorios(codigo) {
    axios.get(`${VITE_URL_APP}/api/get-documentos/${codigo}`)
        .then(response => {
            tblDocs.setData(response.data);
            // Aplicar filtro "PRINCIPAL" por defecto después de cargar los datos
            filterTableByTipoFolio();
        })
        .catch(error => {
            console.error("Error al obtener los datos:", error);
        });
}

// Función para obtener las coincidencias
async function getFoliosClienteCargo(cliente, cargo) {
    try {
        const response = await axios.get(`${VITE_URL_APP}/api/get-folios-cliente-cargo`, {
            params: {
                cliente: cliente,
                cargo: cargo
            }
        });

        // Devuelve los datos de los folios obtenidos
        return response.data;
    } catch (error) {
        console.error("Hubo un error:", error);
        return []; // Retorna un arreglo vacío en caso de error
    }
}

//================================ GUARDAR LOS DATOS POR AXIOS ================================//
// document.getElementById('formFolioPersonal').addEventListener('submit', function (event) {
//     event.preventDefault();
//     var fechaEmision = document.getElementById('fecha_emision').value;
//     var fechaCaducidad = document.getElementById('fecha_caducidad').value;
//     var codigoPer = document.getElementById('codPersonal').value;
//     var codFolio = document.getElementById('codFolio').value;

//     if (fechaEmision /*&& fechaCaducidad*/) {
//         // Enviar los datos al servidor usando Axios
//         axios.post(`${VITE_URL_APP}/api/save_folio_persona`, {
//             fecha_emision: fechaEmision,
//             fecha_caducidad: fechaCaducidad,
//             codFolio: codFolio,
//             codPersonal: codigoPer,
//         })
//             .then(function (response) {
//                 //console.log('Datos guardados:', response.data);
//                 document.getElementById('btn-modal-docs-close').click();
//                 getDocsObligatorios(codigoPer);
//                 document.getElementById('btnTraerFolios').click();
//                 limpiarModal();
//             })
//             .catch(function (error) {
//                 console.error('Error al guardar las fechas:', error);
//             });
//     }
// });