import axios from 'axios';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';


document.addEventListener("DOMContentLoaded", () => {
    cargarFolios();
});

let modoEdicion = false;
let datosOriginales = null;
let tablaLista = false;

//Tabla de Folios
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
                "first": "Primero", "first_title": "Primera Página",
                "last": "Último", "last_title": "Última Página",
                "prev": "Anterior", "prev_title": "Página Anterior",
                "next": "Siguiente", "next_title": "Página Siguiente",
                "all": "Todo"
            },
            "headerFilters": { "default": "Filtrar..." },
            "ajax": { "loading": "Cargando datos...", "error": "Error al cargar datos" },
            "data": { "empty": "No hay datos disponibles" }
        }
    },
    columns: [
        { title: "Folios", field: "nombre", hozAlign: "left", width: '40%' },
        {
            title: "Tipo", field: "tipoFolio", hozAlign: "center", width: '20%',
            formatter: function (cell) {
                var tipo = cell.getValue();
                if (tipo === "FORMATO") return '<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-500 text-white">FORMATO</span>';
                if (tipo === "DOCUMENTO") return '<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-purple-500 text-white">DOCUMENTO</span>';
                if (tipo === "CERTIFICADO") return '<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary text-white">CERTIFICADO</span>';
                return tipo;
            }
        },
        { title: "Vencimiento", field: "periodo", hozAlign: "center", width: '20%' },
        {
            title: "Acciones", field: "acciones", hozAlign: "center", width: '20%', headerSort: false,
            formatter: function (cell) {
                var editBtn = `<button type="button" class="btn rounded-full edit-btn bg-info/25 text-info hover:bg-info hover:text-white" title="Editar"><i class="fa-solid fa-pen-to-square edit-btn"></i></button>`;
                var deleteBtn = cell.getData().habilitado == '1'
                    ? `<button type="button" class="btn rounded-full delete-btn bg-danger/25 text-danger hover:bg-danger hover:text-white" title="Eliminar"><i class="fa-solid fa-trash-can delete-btn"></i></button>`
                    : `<button type="button" class="btn rounded-full activar-btn bg-success/25 text-success hover:bg-success hover:text-white" title="activar"><i class="fa-solid fa-check activar-btn"></i></button>`;
                return editBtn + ' ' + deleteBtn;
            },
            cellClick: function (e, cell) {
                if (e.target.classList.contains('edit-btn')) {
                    modoEdicion = true;
                    const rowData = cell.getRow().getData();
                    datosOriginales = { ...rowData };

                    document.getElementById('submitButton').innerHTML = 'Guardar cambios <i class="fa-solid fa-floppy-disk"></i>';
                    document.querySelector('#codFolio').value = rowData.codigo;
                    document.querySelector('#nombre').value = rowData.nombre;
                    document.querySelector('#tipo').value = rowData.tipo;
                    document.querySelector('#responsable').value = rowData.cod_responsable ?? '';
                    document.querySelector('#nombre').disabled = rowData.utilizado == 1;

                    var institucionDiv = document.getElementById('institucionDiv');
                    if (rowData.tipo == 3) { institucionDiv.classList.remove('hidden'); } else { institucionDiv.classList.add('hidden'); }

                    document.getElementById('radioPrin').checked = rowData.obligatorio == 1;
                    document.getElementById('radioAdi').checked = rowData.obligatorio != 1;

                    if (rowData.vencimiento == 1) {
                        document.querySelector('#switchVencimiento').checked = true;
                        document.getElementById('periodoDiv').classList.remove('hidden');
                        document.querySelector('#periodo').value = rowData.tipo_fecha;
                    } else {
                        document.querySelector('#switchVencimiento').checked = false;
                        document.getElementById('periodoDiv').classList.add('hidden');
                    }

                    var values = { ICMA: 'ICMA', AV: 'AV' };
                    institucionDiv.querySelectorAll('input[type="radio"]').forEach(radio => {
                        radio.checked = values[rowData.plataforma] ? radio.value === values[rowData.plataforma] : false;
                    });

                    document.getElementById("txtMensajeNuevo").innerText = "Editando registro";
                    document.getElementById("txtMensajeNuevo").className = "inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800";
                    document.getElementById('soloEdicion').classList.remove("hidden");
                    document.getElementById('soloEdicion').classList.add("flex");

                    // Cargar sucursales guardadas
                    sucursalTags = [];
                    if (rowData.sucursales && rowData.sucursales.length > 0) {
                        rowData.sucursales.forEach(s => {
                            sucursalTags.push({
                                descripcion: s.descripcion ?? s.codigo,
                                abreviatura: s.codigo,
                                sucu_codigo: s.sucu_codigo ?? null
                            });
                        });
                    }
                    renderSucursalTags();

                } else if (e.target.classList.contains('activar-btn')) {
                    const rowData = cell.getRow().getData();
                    Swal.fire({
                        title: `¿Está seguro de activar el folio: ${rowData.nombre}?`,
                        icon: 'warning', showCancelButton: true,
                        confirmButtonText: 'Sí, activalo', cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.post(`${VITE_URL_APP}/api/activar_folio`, { codigo: rowData.codigo, habilitado: 1 })
                                .then(() => { Swal.fire('Habilitado!', 'El folio ha sido activado.', 'success'); cargarFolios(); limpiarForm(); })
                                .catch(() => { Swal.fire('Error', 'Hubo un problema al activar el folio.', 'error'); });
                        }
                    });

                } else if (e.target.classList.contains('delete-btn')) {
                    const rowData = cell.getRow().getData();
                    Swal.fire({
                        title: `¿Está seguro de eliminar el folio: ${rowData.nombre}?`,
                        icon: 'warning', showCancelButton: true,
                        confirmButtonText: 'Sí, eliminarlo', cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            axios.post(`${VITE_URL_APP}/api/disabled_folio`, { codigo: rowData.codigo, habilitado: 0 })
                                .then(() => { Swal.fire('Eliminado!', 'El folio ha sido deshabilitado.', 'success'); cargarFolios(); limpiarForm(); })
                                .catch(() => { Swal.fire('Error', 'Hubo un problema al deshabilitar el folio.', 'error'); });
                        }
                    });
                }
            }
        },
    ],
    rowFormatter: function (row) {
        if (row.getData().habilitado != "1") row.getElement().style.backgroundColor = "#ffe9e9";
    }
});

tblFolios.on("tableBuilt", function () { tablaLista = true; });

function estadoInstitucion(activo) {
    var institucionDiv = document.getElementById('institucionDiv');
    if (activo == 1) { institucionDiv.classList.remove('hidden'); } else { institucionDiv.classList.add('hidden'); }
    institucionDiv.querySelectorAll('input[type="radio"]').forEach(radio => { radio.checked = false; });
}

window.aplicarFiltroEliminarFolio = (op) => {
    if (!tablaLista) return;
    if (op === 0) { tblFolios.setFilter("habilitado", "=", "0"); } else { tblFolios.clearFilter(); }
}

document.getElementById("page-size").addEventListener("change", function () {
    tblFolios.setPageSize(parseInt(this.value));
});

document.getElementById("cancelButton").addEventListener("click", function () {
    if (!modoEdicion) {
        limpiarForm();
        document.getElementById("tipo").value = "";
        document.querySelector('#tipo').dispatchEvent(new Event('change'));
        return;
    }

    document.querySelector('#codFolio').value = datosOriginales.codigo;
    document.querySelector('#nombre').value = datosOriginales.nombre;
    let tipoSelect = document.getElementById("tipo");
    tipoSelect.value = datosOriginales.tipo;
    tipoSelect.dispatchEvent(new Event('change'));

    const institucionDiv = document.getElementById('institucionDiv');
    if (datosOriginales.tipo == 3) { institucionDiv.classList.remove('hidden'); } else { institucionDiv.classList.add('hidden'); }

    document.getElementById('radioPrin').checked = datosOriginales.obligatorio == 1;
    document.getElementById('radioAdi').checked = datosOriginales.obligatorio != 1;

    if (datosOriginales.vencimiento == 1) {
        document.querySelector('#switchVencimiento').checked = true;
        document.getElementById('periodoDiv').classList.remove('hidden');
        document.querySelector('#periodo').value = datosOriginales.tipo_fecha;
    } else {
        document.querySelector('#switchVencimiento').checked = false;
        document.getElementById('periodoDiv').classList.add('hidden');
    }

    document.querySelectorAll('#institucionDiv input[type="radio"]').forEach(r => { r.checked = r.value === datosOriginales.plataforma; });
    document.getElementById("txtMensajeNuevo").innerText = "Editando registro";
    document.getElementById("txtMensajeNuevo").className = "inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800";
    document.getElementById('submitButton').innerHTML = 'Guardar <i class="fa-solid fa-floppy-disk"></i>';
    document.getElementById('soloEdicion').classList.add("flex");
});

document.getElementById('tipo').addEventListener('change', function () {
    if (this.value === "3") { estadoInstitucion(1); } else { document.getElementById('institucionDiv').classList.add('hidden'); }
});

document.querySelector('.clean-btn').addEventListener('click', limpiarForm);

document.getElementById("buscar").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblFolios.setFilter([[
        { field: "nombre", type: 'like', value: valor },
        { field: "prioridad", type: 'like', value: valor },
        { field: "tipoFolio", type: 'like', value: valor },
        { field: "periodo", type: 'like', value: valor },
    ]]);
});

function aplicarTodosFiltros() {
    if (!tablaLista) return;
    const folioFiltroSeleccionado = document.querySelector('input[name="folioFiltro"]:checked')?.value;
    const soloActivosChecked = document.getElementById('chkEliminados')?.checked || false;
    tblFolios.clearFilter();
    if (soloActivosChecked) tblFolios.addFilter("habilitado", "=", "1");
    if (folioFiltroSeleccionado && folioFiltroSeleccionado !== "TODOS") {
        if (["DOCUMENTO", "FORMATO", "CERTIFICADO"].includes(folioFiltroSeleccionado)) {
            tblFolios.addFilter("tipoFolio", "=", folioFiltroSeleccionado);
        } else {
            tblFolios.addFilter("prioridad", "=", folioFiltroSeleccionado);
        }
    }
}

window.aplicarFiltroSoloActivos = function (op) {
    if (!tablaLista) return;
    aplicarTodosFiltros();
}

document.querySelectorAll('input[name="folioFiltro"]').forEach(radio => {
    radio.addEventListener('change', aplicarTodosFiltros);
});

document.getElementById('switchVencimiento').addEventListener('change', function () {
    document.getElementById('periodoDiv').classList.toggle('hidden', !this.checked);
});


// ======================== TAG INPUT SUCURSALES ======================== //
let sucursalTags = [];

function agregarSucursalTag(descripcion, abreviatura, sucu_codigo) {
    if (!descripcion || !abreviatura) return;
    const desc  = descripcion.toUpperCase();
    const abrev = abreviatura.toUpperCase();
    if (!sucursalTags.find(t => t.abreviatura === abrev)) {
        sucursalTags.push({ descripcion: desc, abreviatura: abrev, sucu_codigo: sucu_codigo ?? null });
        renderSucursalTags();
    }
    document.getElementById('sucursalInput').value = '';
    ocultarSugerencias();
}

function renderSucursalTags() {
    const wrapper = document.getElementById('sucursalTagWrapper');
    const input   = document.getElementById('sucursalInput');
    wrapper.querySelectorAll('.tag').forEach(t => t.remove());
    sucursalTags.forEach((tag, i) => {
        const el = document.createElement('span');
        el.className = 'tag';
        el.innerHTML = `<span class="tag-abrev">${tag.abreviatura}</span>${tag.descripcion}<button type="button" class="tag-remove" data-i="${i}">×</button>`;
        wrapper.insertBefore(el, input);
    });
    wrapper.querySelectorAll('.tag-remove').forEach(btn => {
        btn.addEventListener('click', e => {
            sucursalTags.splice(+e.target.dataset.i, 1);
            renderSucursalTags();
        });
    });
}

let searchTimeout = null;

function buscarSucursales(q) {
    axios.get(`${VITE_URL_APP}/api/get-sucursales-folio`, { params: { q } })
        .then(res => { if (!res.data.length) { mostrarNoEncontrado(); } else { mostrarSugerencias(res.data); } })
        .catch(() => ocultarSugerencias());
}

function mostrarSugerencias(lista) {
    const box = document.getElementById('sucursalSugerencias');
    box.innerHTML = lista.map(s => `
        <div class="tag-suggestion-item"
             data-descripcion="${s.SUCU_DESCRIPCION}"
             data-abreviatura="${s.SUCU_ABREVIATURA}"
             data-sucu_codigo="${s.SUCU_CODIGO}">
            <span class="abrev">${s.SUCU_ABREVIATURA}</span>
            ${s.SUCU_DESCRIPCION}
        </div>
    `).join('');
    box.querySelectorAll('.tag-suggestion-item').forEach(el => {
        el.addEventListener('mousedown', e => {
            e.preventDefault();
            agregarSucursalTag(el.dataset.descripcion, el.dataset.abreviatura, el.dataset.sucu_codigo);
        });
    });
    box.style.display = 'block';
}

function mostrarNoEncontrado() {
    const box = document.getElementById('sucursalSugerencias');
    box.innerHTML = `<div class="tag-no-result">⚠ No se encontró ninguna sucursal</div>`;
    box.style.display = 'block';
}

function ocultarSugerencias() {
    const box = document.getElementById('sucursalSugerencias');
    if (box) box.style.display = 'none';
}

document.getElementById('sucursalInput').addEventListener('input', function () {
    const q = this.value.trim();
    clearTimeout(searchTimeout);
    if (!q) { ocultarSugerencias(); return; }
    searchTimeout = setTimeout(() => buscarSucursales(q), 250);
});

document.getElementById('sucursalInput').addEventListener('keydown', function (e) {
    const items = document.querySelectorAll('.tag-suggestion-item');
    const activeItem = document.querySelector('.tag-suggestion-item.active');

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!activeItem) { items[0]?.classList.add('active'); } else {
            const next = activeItem.nextElementSibling;
            activeItem.classList.remove('active');
            (next || items[0]).classList.add('active');
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (activeItem) {
            const prev = activeItem.previousElementSibling;
            activeItem.classList.remove('active');
            (prev || items[items.length - 1]).classList.add('active');
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activeItem) {
            agregarSucursalTag(activeItem.dataset.descripcion, activeItem.dataset.abreviatura, activeItem.dataset.sucu_codigo);
        } else {
            if (this.value.trim()) mostrarNoEncontrado();
        }
    } else if (e.key === 'Backspace' && !this.value && sucursalTags.length) {
        sucursalTags.pop();
        renderSucursalTags();
    } else if (e.key === 'Escape') {
        ocultarSugerencias();
    }
});

document.getElementById('sucursalTagWrapper')?.addEventListener('click', () => { document.getElementById('sucursalInput').focus(); });

document.addEventListener('click', e => {
    if (!e.target.closest('#sucursalTagWrapper') && !e.target.closest('#sucursalSugerencias')) ocultarSugerencias();
});
// ===================================================================== //


function limpiarForm() {
    document.getElementById("txtMensajeNuevo").innerText = "Nuevo registro";
    document.getElementById("txtMensajeNuevo").className = "inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-primary-800";
    document.getElementById('soloEdicion').classList.remove("flex");
    document.getElementById('soloEdicion').classList.add("hidden");
    document.getElementById('nombre').value = "";
    document.getElementById('tipo').value = '';
    document.getElementById('responsable').value = '';
    document.getElementById('radioPrin').checked = true;
    document.getElementById('radioAdi').checked = false;
    document.querySelector('#switchVencimiento').checked = false;
    document.getElementById('periodoDiv').classList.add('hidden');
    estadoInstitucion(0);
    document.querySelector('#codFolio').value = "";
    modoEdicion = false;
    datosOriginales = null;
    document.getElementById('submitButton').innerHTML = 'Guardar <i class="fa-solid fa-floppy-disk"></i>';
    sucursalTags = [];
    renderSucursalTags();
    document.getElementById('sucursalInput').value = '';
    ocultarSugerencias();
}


function cargarFolios() {
    axios.get(`${VITE_URL_APP}/api/get-folios`)
        .then(response => { tblFolios.setData(response.data); })
        .catch(error => { console.error("Error al obtener los datos:", error); });
}


//================================ GUARDAR LOS DATOS POR AXIOS ================================//
document.getElementById('formSaveFolio').addEventListener('submit', function (event) {
    event.preventDefault();

    var codigo      = document.getElementById('codFolio').value;
    var nombre      = document.getElementById('nombre').value;
    var tipo        = document.getElementById('tipo').value;
    var tipoFolio   = document.querySelector('input[name="tipo_folio"]:checked').value;
    var obligatorio = (tipoFolio === 'PRINCIPAL') ? 1 : 0;
    var vencimiento = document.getElementById('switchVencimiento').checked ? 1 : 0;
    var periodo     = document.getElementById('periodo').value;
    var responsable = document.getElementById('responsable').value;
    var institucion = document.querySelector('input[name="institucion"]:checked')?.value;

    // Sucursales con los 3 campos necesarios para guardar en BD
    var sucursales = sucursalTags.length > 0 ? sucursalTags.map(t => ({
        codigo:      t.abreviatura,
        descripcion: t.descripcion,
        sucu_codigo: t.sucu_codigo ?? null
    })) : null;

    if (vencimiento == 0) periodo = null;

    if (nombre && tipo && responsable) {
        axios.post(`${VITE_URL_APP}/api/save_folio`, {
            codigo, nombre, tipo, obligatorio, vencimiento,
            periodo, responsable, plataforma: institucion, sucursales,
        })
        .then(function (response) {
            cargarFolios();
            limpiarForm();
            Swal.fire({ icon: 'success', title: '¡Éxito!', text: response.data.message, timer: 2000, showConfirmButton: false });
        })
        .catch(function (error) {
            console.error('Error al guardar:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.response?.data?.message || 'Hubo un problema al guardar el folio.'
            });
        });
    } else {
        Swal.fire({
            icon: 'warning',
            title: 'Campos incompletos',
            text: 'Por favor, complete todos los campos obligatorios: Nombre, Tipo y Responsable.',
            confirmButtonColor: '#3085d6'
        });
    }
});


document.getElementById('btnGenerarPDF').addEventListener('click', function () {
    const todosLosDatos = tblFolios.getData().filter(folio => folio.habilitado == "1");

    if (todosLosDatos.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Sin datos', text: 'No hay folios vigentes para generar el reporte' });
        return;
    }

    Swal.fire({ title: 'Generando PDF...', text: 'Por favor espere', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

    setTimeout(() => {
        const doc = new jsPDF();
        const fechaActual = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });

        doc.setFillColor(41, 128, 185);
        doc.rect(0, 0, 210, 35, 'F');
        doc.setFontSize(22); doc.setFont(undefined, 'bold'); doc.setTextColor(255, 255, 255);
        doc.text('REPORTE DE FOLIOS VIGENTES', 105, 15, { align: 'center' });
        doc.setFontSize(9); doc.setFont(undefined, 'normal');
        doc.text(`Fecha de emisión: ${fechaActual}`, 105, 22, { align: 'center' });
        doc.setFontSize(10); doc.setFont(undefined, 'bold');
        doc.text(`Total de folios activos: ${todosLosDatos.length}`, 105, 28, { align: 'center' });
        doc.setTextColor(0, 0, 0);
        let yPosition = 42;

        doc.setFontSize(13); doc.setFont(undefined, 'bold');
        doc.setFillColor(52, 73, 94); doc.rect(10, yPosition, 190, 7, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('1. CLASIFICACIÓN POR PRIORIDAD', 12, yPosition + 5);
        doc.setTextColor(0, 0, 0); yPosition += 10;

        const porPrioridad = {
            'PRINCIPAL': todosLosDatos.filter(f => f.prioridad === 'PRINCIPAL'),
            'ADICIONAL': todosLosDatos.filter(f => f.prioridad === 'ADICIONAL')
        };

        Object.keys(porPrioridad).forEach((prioridad, index) => {
            const folios = porPrioridad[prioridad];
            if (folios.length > 0) {
                doc.setFontSize(10); doc.setFont(undefined, 'bold');
                doc.setFillColor(236, 240, 241); doc.rect(10, yPosition, 190, 6, 'F');
                doc.setTextColor(44, 62, 80); doc.text(`1.${index + 1} ${prioridad}`, 12, yPosition + 4);
                doc.setFont(undefined, 'normal'); doc.setTextColor(127, 140, 141);
                doc.text(`(${folios.length} ${folios.length === 1 ? 'folio' : 'folios'})`, 50, yPosition + 4);
                doc.setTextColor(0, 0, 0); yPosition += 8;

                autoTable(doc, {
                    startY: yPosition,
                    head: [['N°', 'Nombre del Folio', 'Tipo', 'Vencimiento']],
                    body: folios.map((folio, idx) => [idx + 1, folio.nombre, folio.tipoFolio, folio.periodo || 'Sin vencimiento']),
                    styles: { fontSize: 8, cellPadding: 2, lineColor: [189, 195, 199], lineWidth: 0.1 },
                    headStyles: { fillColor: [149, 165, 166], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 9, cellPadding: 3 },
                    alternateRowStyles: { fillColor: [250, 250, 250] },
                    columnStyles: { 0: { halign: 'center', cellWidth: 12 }, 1: { cellWidth: 95 }, 2: { halign: 'center', cellWidth: 35 }, 3: { halign: 'center', cellWidth: 38 } },
                    margin: { left: 10, right: 10 }, theme: 'grid'
                });

                yPosition = doc.lastAutoTable.finalY + 6;
                if (yPosition > 260) { doc.addPage(); yPosition = 20; }
            }
        });

        if (yPosition > 150) { doc.addPage(); yPosition = 20; } else { yPosition += 5; }

        doc.setFontSize(13); doc.setFont(undefined, 'bold');
        doc.setFillColor(52, 73, 94); doc.rect(10, yPosition, 190, 7, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('2. CLASIFICACIÓN POR TIPO DE FOLIO', 12, yPosition + 5);
        doc.setTextColor(0, 0, 0); yPosition += 10;

        const porTipo = {
            'DOCUMENTO': todosLosDatos.filter(f => f.tipoFolio === 'DOCUMENTO'),
            'FORMATO': todosLosDatos.filter(f => f.tipoFolio === 'FORMATO'),
            'CERTIFICADO': todosLosDatos.filter(f => f.tipoFolio === 'CERTIFICADO')
        };
        const coloresTipo = { 'DOCUMENTO': [155, 89, 182], 'FORMATO': [241, 196, 15], 'CERTIFICADO': [52, 152, 219] };

        Object.keys(porTipo).forEach((tipo, index) => {
            const folios = porTipo[tipo];
            if (folios.length > 0) {
                doc.setFontSize(10); doc.setFont(undefined, 'bold');
                doc.setFillColor(236, 240, 241); doc.rect(10, yPosition, 190, 6, 'F');
                const color = coloresTipo[tipo];
                doc.setFillColor(color[0], color[1], color[2]); doc.circle(13, yPosition + 3, 1.5, 'F');
                doc.setTextColor(44, 62, 80); doc.text(`2.${index + 1} ${tipo}`, 17, yPosition + 4);
                doc.setFont(undefined, 'normal'); doc.setTextColor(127, 140, 141);
                doc.text(`(${folios.length} ${folios.length === 1 ? 'folio' : 'folios'})`, 55, yPosition + 4);
                doc.setTextColor(0, 0, 0); yPosition += 8;

                autoTable(doc, {
                    startY: yPosition,
                    head: [['N°', 'Nombre del Folio', 'Prioridad', 'Vencimiento']],
                    body: folios.map((folio, idx) => [idx + 1, folio.nombre, folio.prioridad, folio.periodo || 'Sin vencimiento']),
                    styles: { fontSize: 8, cellPadding: 2, lineColor: [189, 195, 199], lineWidth: 0.1 },
                    headStyles: { fillColor: [149, 165, 166], textColor: [255, 255, 255], fontStyle: 'bold', halign: 'center', fontSize: 9, cellPadding: 3 },
                    alternateRowStyles: { fillColor: [250, 250, 250] },
                    columnStyles: { 0: { halign: 'center', cellWidth: 12 }, 1: { cellWidth: 95 }, 2: { halign: 'center', cellWidth: 35 }, 3: { halign: 'center', cellWidth: 38 } },
                    margin: { left: 10, right: 10 }, theme: 'grid'
                });

                yPosition = doc.lastAutoTable.finalY + 6;
                if (yPosition > 260) { doc.addPage(); yPosition = 20; }
            }
        });

        const pageCount = doc.internal.getNumberOfPages();
        const horaGeneracion = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            doc.setDrawColor(189, 195, 199); doc.setLineWidth(0.5); doc.line(10, 282, 200, 282);
            doc.setFontSize(7); doc.setTextColor(127, 140, 141); doc.setFont(undefined, 'normal');
            doc.text('Sistema de Gestión de Recursos Humanos', 10, 287);
            doc.text(`Generado el ${fechaActual} a las ${horaGeneracion}`, 105, 287, { align: 'center' });
            doc.setFont(undefined, 'bold');
            doc.text(`Página ${i} de ${pageCount}`, 200, 287, { align: 'right' });
        }

        window.open(URL.createObjectURL(doc.output('blob')), '_blank');
        Swal.close();
        Swal.fire({ icon: 'success', title: '¡PDF generado exitosamente!', text: 'El reporte se ha abierto en una nueva pestaña', timer: 2000, showConfirmButton: false });
    }, 500);
});