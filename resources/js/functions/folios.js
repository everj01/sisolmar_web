import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Swal from 'sweetalert2';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';


document.addEventListener("DOMContentLoaded", () => {
    cargarFolios();
});

let modoEdicion = false;
let datosOriginales = null;

//Tabla de Folios
const tblFolios = new Tabulator("#tblFolios", {
    height: "100%",
    layout:"fitDataFill",
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
        { title: "Folios", field: "nombre", hozAlign: "left", width: '40%' },
        { title: "Tipo", field: "tipoFolio", hozAlign: "center", width: '20%',
            formatter: function(cell, formatterParams) {
                var tipo = cell.getValue();
                if (tipo === "FORMATO") {
                    return '<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-yellow-500 text-white">FORMATO</span>';
                } else if (tipo === "DOCUMENTO") {
                    return '<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-purple-500 text-white">DOCUMENTO</span>';
                } else if (tipo === "CERTIFICADO") {
                    return '<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary text-white">CERTIFICADO</span>';
                }
                return tipo;
            }
        },
        { title: "Vencimiento", field: "periodo", hozAlign: "center", width: '20%' },
        { title: "Acciones", field: "acciones", hozAlign: "center", width: '20%', headerSort: false,
            formatter: function(cell, formatterParams, onRendered) {
                var editBtn = `<button type="button" class="btn rounded-full edit-btn bg-info/25 text-info hover:bg-info hover:text-white" title="Editar"><i class="fa-solid fa-pen-to-square edit-btn"></i></button>`;
                
                var deleteBtn = 
                cell.getData().habilitado == '1' ?
                `<button type="button" class="btn rounded-full delete-btn bg-danger/25 text-danger hover:bg-danger hover:text-white" title="Eliminar"><i class="fa-solid fa-trash-can delete-btn"></i></button>`
                : 
                `<button type="button" class="btn rounded-full activar-btn bg-success/25 text-success hover:bg-success hover:text-white" title="activar">
                <i class="fa-solid fa-check activar-btn"></i></button>`
                ;

                return editBtn + ' ' + deleteBtn;
            },
            cellClick: function(e, cell) {
                if (e.target.classList.contains('edit-btn')) {
                    modoEdicion = true;
                    const rowData = cell.getRow().getData();
                    datosOriginales = { ...rowData };

                    const submitButton = document.getElementById('submitButton');
                    submitButton.innerHTML = 'Guardar cambios <i class="fa-solid fa-floppy-disk"></i>';

                    document.querySelector('#codFolio').value = rowData.codigo;
                    document.querySelector('#nombre').value = rowData.nombre;
                    document.querySelector('#tipo').value = rowData.tipo;

                    //Bloquear nombre
                    if (rowData.utilizado == 1) {
                        document.querySelector('#nombre').disabled = true;
                    } else {
                        document.querySelector('#nombre').disabled = false;
                    }                    

                    var institucionDiv = document.getElementById('institucionDiv');
                    if(rowData.tipo == 3){
                        institucionDiv.classList.remove('hidden');
                    }else{
                        institucionDiv.classList.add('hidden');
                    }

                    if(rowData.obligatorio == 1){
                        document.getElementById('radioPrin').checked = true;
                    }else{
                        document.getElementById('radioAdi').checked = true;
                    }

                    if(rowData.vencimiento == 1){
                        document.querySelector('#switchVencimiento').checked = true;
                        document.getElementById('periodoDiv').classList.remove('hidden');
                        document.querySelector('#periodo').value = rowData.tipo_fecha;
                    }else{
                        document.querySelector('#switchVencimiento').checked = false;
                        document.getElementById('periodoDiv').classList.add('hidden');
                    }

                    var plataforma = rowData.plataforma;
                    var radioButtons = institucionDiv.querySelectorAll('input[type="radio"]');

                    var values = {
                        ICMA: 'ICMA',
                        AV: 'AV',
                        //OTROS: 'OTROS'
                    };

                    if (values[plataforma]) {
                        radioButtons.forEach(radio => {
                            radio.checked = radio.value === values[plataforma];
                        });
                    }else {
                        radioButtons.forEach(radio => {
                            radio.checked = false;
                        });
                    }

                    document.getElementById("txtMensajeNuevo").innerText = "Editando registro";
                    document.getElementById("txtMensajeNuevo").className = "inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800";
                    document.getElementById('soloEdicion').classList.remove("hidden");
                    document.getElementById('soloEdicion').classList.add("flex");

                }else if (e.target.classList.contains('activar-btn')) { 
                    const rowData = cell.getRow().getData();
                    document.querySelector('#codFolio').value = rowData.codigo;
                    Swal.fire({
                        title: `¿Está seguro de activar el folio: ${rowData.nombre}?`,
                        text: 'No podrás revertir esta acción',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, activalo',
                        cancelButtonText: 'Cancelar'
                      }).then((result) => {
                        if (result.isConfirmed) {
                            axios.post(`${ VITE_URL_APP }/api/activar_folio`, {
                                codigo: rowData.codigo,
                                habilitado: 1
                            })
                            .then(response => {
                                console.log(response);
                                Swal.fire('Habilitado!', 'El folio ha sido activado.', 'success');
                                cargarFolios();
                                limpiarForm();
                            })
                            .catch(error => {
                                Swal.fire('Error', 'Hubo un problema al activar el folio.', 'error');
                            });
                        }
                      });
                }else if (e.target.classList.contains('delete-btn')) {
                    const rowData = cell.getRow().getData();
                    document.querySelector('#codFolio').value = rowData.codigo;
                    Swal.fire({
                        title: `¿Está seguro de eliminar el folio: ${rowData.nombre}?`,
                        text: 'No podrás revertir esta acción',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminarlo',
                        cancelButtonText: 'Cancelar'
                      }).then((result) => {
                        if (result.isConfirmed) {
                            axios.post(`${ VITE_URL_APP }/api/disabled_folio`, {
                                codigo: rowData.codigo,
                                habilitado: 0
                            })
                            .then(response => {
                                Swal.fire('Eliminado!', 'El folio ha sido deshabilitado.', 'success');
                                cargarFolios();
                                limpiarForm();
                            })
                            .catch(error => {
                                Swal.fire('Error', 'Hubo un problema al deshabilitar el folio.', 'error');
                            });
                        }
                      });
                }
            }
        },
    ],
    rowFormatter: function(row) {
        let data = row.getData();
        
        if (data.habilitado != "1") {
            row.getElement().style.backgroundColor = "#ffe9e9"; 
        } 
    }
});

//-------- Desactivar/Activar la institucion
function estadoInstitucion(activo){
    var institucionDiv = document.getElementById('institucionDiv');
    if (activo == 1){
        institucionDiv.classList.remove('hidden');
    }else{
        institucionDiv.classList.add('hidden');
    }
    var radioButtons = institucionDiv.querySelectorAll('input[type="radio"]');
    radioButtons.forEach(radio => {
        radio.checked = false;
    });
}

window.aplicarFiltroEliminarFolio = (op) => {
    if(op === 0) {tblFolios.setFilter("habilitado", "=", "0");}else{
        tblFolios.clearFilter();
    }
    
}

window.aplicarFiltroSoloActivos = (op) => {
    if(op === 1) {tblFolios.setFilter("habilitado", "=", "1");}else{
        tblFolios.clearFilter();
    }
    
}

document.getElementById("page-size").addEventListener("change", function () {
    const size = parseInt(this.value);
    tblFolios.setPageSize(size);
});

// Función para CANCELAR
document.getElementById("cancelButton").addEventListener("click", function () {

    if (!modoEdicion) {
        // Modo NUEVO: limpiar el formulario
        limpiarForm();
        document.getElementById("tipo").value = "";
        document.querySelector('#tipo').dispatchEvent(new Event('change'));
        return;
    }

    // Modo EDICIÓN: restaurar datos originales
    document.querySelector('#codFolio').value = datosOriginales.codigo;
    document.querySelector('#nombre').value = datosOriginales.nombre;
    let tipoSelect = document.getElementById("tipo");
    tipoSelect.value = datosOriginales.tipo;
    tipoSelect.dispatchEvent(new Event('change'));
    
    if (datosOriginales.tipo == 3) {
        institucionDiv.classList.remove('hidden');
    } else {
        institucionDiv.classList.add('hidden');
    }

    if (datosOriginales.obligatorio == 1) {
        document.getElementById('radioPrin').checked = true;
    } else {
        document.getElementById('radioAdi').checked = true;
    }

    if (datosOriginales.vencimiento == 1) {
        document.querySelector('#switchVencimiento').checked = true;
        document.getElementById('periodoDiv').classList.remove('hidden');
        document.querySelector('#periodo').value = datosOriginales.tipo_fecha;
    } else {
        document.querySelector('#switchVencimiento').checked = false;
        document.getElementById('periodoDiv').classList.add('hidden');
    }

    let radios = document.querySelectorAll('#institucionDiv input[type="radio"]');
    radios.forEach(r => r.checked = (r.value === datosOriginales.plataforma));

    document.getElementById("txtMensajeNuevo").innerText = "Editando registro";
    document.getElementById("txtMensajeNuevo").className =
        "inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800";

    submitButton.innerHTML = 'Guardar <i class="fa-solid fa-floppy-disk"></i>';

    document.getElementById('soloEdicion').classList.add("flex");
});


document.getElementById('tipo').addEventListener('change', function() {
    const selectedValue = this.value;

    if (selectedValue === "3") {
        estadoInstitucion(1);
    }else{
        institucionDiv.classList.add('hidden');
    }
});

document.querySelector('.clean-btn').addEventListener('click', limpiarForm);

document.getElementById("buscar").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblFolios.setFilter([
        [
            { field: "nombre", type: 'like',  value: valor },
            { field: "prioridad", type: 'like',  value: valor },
            { field: "tipoFolio", type: 'like', value: valor },
            { field: "periodo", type: 'like', value: valor },
        ]
    ]);
});

// Función para actualizar la tabla con los filtros
// function filterTableByTipoFolio() {
//     const folioFiltroSeleccionado = document.querySelector('input[name="folioFiltro"]:checked')?.value;
//     if (!folioFiltroSeleccionado) {
//         tblFolios.clearFilter();
//     } else if (folioFiltroSeleccionado === "TODOS") {
//         tblFolios.clearFilter("prioridad");
//         tblFolios.clearFilter("tipoFolio");
//     } else if (["DOCUMENTO", "FORMATO", "CERTIFICADO"].includes(folioFiltroSeleccionado)) {
//         tblFolios.setFilter("tipoFolio", "=", folioFiltroSeleccionado);
//         //tblFolios.clearFilter("prioridad");
//     } else {
//         tblFolios.setFilter("prioridad", "=", folioFiltroSeleccionado);
//         //tblFolios.clearFilter("tipoFolio");
//     }
// }

// document.querySelectorAll('input[name="folioFiltro"]').forEach(radio => {
//     radio.addEventListener('change', filterTableByTipoFolio);
// });

// Función para actualizar la tabla con TODOS los filtros
function aplicarTodosFiltros() {
    const folioFiltroSeleccionado = document.querySelector('input[name="folioFiltro"]:checked')?.value;
    const soloActivosChecked = document.getElementById('chkEliminados')?.checked || false;
    
    // Limpiar todos los filtros primero
    tblFolios.clearFilter();
    
    // Aplicar filtro de activos si está marcado
    if (soloActivosChecked) {
        tblFolios.addFilter("habilitado", "=", "1");
    }
    
    // Aplicar filtro de tipo/prioridad según selección
    if (folioFiltroSeleccionado && folioFiltroSeleccionado !== "TODOS") {
        if (["DOCUMENTO", "FORMATO", "CERTIFICADO"].includes(folioFiltroSeleccionado)) {
            tblFolios.addFilter("tipoFolio", "=", folioFiltroSeleccionado);
        } else {
            tblFolios.addFilter("prioridad", "=", folioFiltroSeleccionado);
        }
    }
}

// Reemplazar la función anterior
window.aplicarFiltroSoloActivos = function(op) {
    aplicarTodosFiltros();
}

// Event listener para los radio buttons de filtro
document.querySelectorAll('input[name="folioFiltro"]').forEach(radio => {
    radio.addEventListener('change', aplicarTodosFiltros);
});


//Activar los periodos si hay VENCIMIENTO
document.getElementById('switchVencimiento').addEventListener('change', function() {
    document.getElementById('periodoDiv').classList.toggle('hidden', !this.checked);
});





//Función para limpia los campos del modal
function limpiarForm(){
    document.getElementById("txtMensajeNuevo").innerText = "Nuevo registro";
    document.getElementById("txtMensajeNuevo").className = "inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-primary-800";
    document.getElementById('soloEdicion').classList.remove("flex");
    document.getElementById('soloEdicion').classList.add("hidden");

    document.getElementById('nombre').value="";
    document.getElementById('tipo').value = '';
    document.getElementById('radioPrin').checked = true;
    document.getElementById('radioAdi').checked = false;
    document.querySelector('#switchVencimiento').checked = false;
    document.getElementById('periodoDiv').classList.add('hidden');

    estadoInstitucion(0);
    document.querySelector('#codFolio').value="";
    modoEdicion = false;
    datosOriginales = null;
    const submitButton = document.getElementById('submitButton');
    submitButton.innerHTML = 'Guardar <i class="fa-solid fa-floppy-disk"></i>';
}


//========================================== DATA CON AXIOS ==========================================//
// Función para obtener los folios
function cargarFolios(){
    axios.get(`${ VITE_URL_APP }/api/get-folios`)
    .then(response => {
        tblFolios.setData(response.data);
    })
    .catch(error => {
        console.error("Error al obtener los datos:", error);
    });
};


//================================ GUARDAR LOS DATOS POR AXIOS ================================//
document.getElementById('formSaveFolio').addEventListener('submit', function(event) {
    event.preventDefault();
    var codigo = document.getElementById('codFolio').value;
    var nombre = document.getElementById('nombre').value;
    var tipo = document.getElementById('tipo').value;
    var tipoFolio = document.querySelector('input[name="tipo_folio"]:checked').value;
    var obligatorio = (tipoFolio === 'PRINCIPAL') ? 1 : 0;
    var switchVencimiento = document.getElementById('switchVencimiento');
    var vencimiento = switchVencimiento.checked ? 1 : 0;
    var periodo = document.getElementById('periodo').value;
    var institucion = document.querySelector('input[name="institucion"]:checked')?.value;

    if(vencimiento == 0){
        periodo = null;
    }

    if (nombre && tipo) {
        axios.post(`${ VITE_URL_APP }/api/save_folio`, {
            codigo: codigo,
            nombre: nombre,
            tipo: tipo,
            obligatorio: obligatorio,
            vencimiento: vencimiento,
            periodo: periodo,
            plataforma: institucion,
        })
        .then(function(response) {
            cargarFolios();
            limpiarForm();
        })
        .catch(function(error) {
            console.error('Error al guardar las fechas:', error);
        });
    }
});


//----------------------------------------------------
// Función para generar el PDF desde los datos de la tabla
// Función mejorada para generar PDF profesional
document.getElementById('btnGenerarPDF').addEventListener('click', function() {
    const todosLosDatos = tblFolios.getData().filter(folio => folio.habilitado == "1");
    
    if (todosLosDatos.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin datos',
            text: 'No hay folios vigentes para generar el reporte'
        });
        return;
    }

    Swal.fire({
        title: 'Generando PDF...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    setTimeout(() => {
        const doc = new jsPDF();
        const fechaActual = new Date().toLocaleDateString('es-PE', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        // ============= ENCABEZADO PRINCIPAL =============
        // Logo o espacio para logo (opcional)
        doc.setFillColor(41, 128, 185); // Azul corporativo
        doc.rect(0, 0, 210, 35, 'F');
        
        doc.setFontSize(22);
        doc.setFont(undefined, 'bold');
        doc.setTextColor(255, 255, 255);
        doc.text('REPORTE DE FOLIOS VIGENTES', 105, 15, { align: 'center' });
        
        doc.setFontSize(9);
        doc.setFont(undefined, 'normal');
        doc.text(`Fecha de emisión: ${fechaActual}`, 105, 22, { align: 'center' });
        
        // Resumen ejecutivo
        doc.setFontSize(10);
        doc.setFont(undefined, 'bold');
        doc.text(`Total de folios activos: ${todosLosDatos.length}`, 105, 28, { align: 'center' });
        
        doc.setTextColor(0, 0, 0);
        let yPosition = 42;

        // ============= SECCIÓN 1: CLASIFICACIÓN POR PRIORIDAD =============
        doc.setFontSize(13);
        doc.setFont(undefined, 'bold');
        doc.setFillColor(52, 73, 94); // Azul oscuro
        doc.rect(10, yPosition, 190, 7, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('1. CLASIFICACIÓN POR PRIORIDAD', 12, yPosition + 5);
        doc.setTextColor(0, 0, 0);
        yPosition += 10;

        const porPrioridad = {
            'PRINCIPAL': todosLosDatos.filter(f => f.prioridad === 'PRINCIPAL'),
            'ADICIONAL': todosLosDatos.filter(f => f.prioridad === 'ADICIONAL')
        };

        Object.keys(porPrioridad).forEach((prioridad, index) => {
            const folios = porPrioridad[prioridad];
            
            if (folios.length > 0) {
                // Subtítulo más compacto
                doc.setFontSize(10);
                doc.setFont(undefined, 'bold');
                doc.setFillColor(236, 240, 241);
                doc.rect(10, yPosition, 190, 6, 'F');
                doc.setTextColor(44, 62, 80);
                doc.text(`1.${index + 1} ${prioridad}`, 12, yPosition + 4);
                doc.setFont(undefined, 'normal');
                doc.setTextColor(127, 140, 141);
                doc.text(`(${folios.length} ${folios.length === 1 ? 'folio' : 'folios'})`, 50, yPosition + 4);
                doc.setTextColor(0, 0, 0);
                yPosition += 8;

                // Tabla optimizada
                autoTable(doc, {
                    startY: yPosition,
                    head: [['N°', 'Nombre del Folio', 'Tipo', 'Vencimiento']],
                    body: folios.map((folio, idx) => [
                        idx + 1,
                        folio.nombre,
                        folio.tipoFolio,
                        folio.periodo || 'Sin vencimiento'
                    ]),
                    styles: { 
                        fontSize: 8,
                        cellPadding: 2,
                        lineColor: [189, 195, 199],
                        lineWidth: 0.1
                    },
                    headStyles: { 
                        fillColor: [149, 165, 166],
                        textColor: [255, 255, 255],
                        fontStyle: 'bold',
                        halign: 'center',
                        fontSize: 9,
                        cellPadding: 3
                    },
                    alternateRowStyles: { 
                        fillColor: [250, 250, 250]
                    },
                    columnStyles: {
                        0: { halign: 'center', cellWidth: 12 },
                        1: { cellWidth: 95 },
                        2: { halign: 'center', cellWidth: 35 },
                        3: { halign: 'center', cellWidth: 38 }
                    },
                    margin: { left: 10, right: 10 },
                    theme: 'grid'
                });

                yPosition = doc.lastAutoTable.finalY + 6;
                
                // Control de páginas
                if (yPosition > 260) {
                    doc.addPage();
                    yPosition = 20;
                }
            }
        });

        // Nueva página para la segunda clasificación
        if (yPosition > 150) {
            doc.addPage();
            yPosition = 20;
        } else {
            yPosition += 5;
        }

        // ============= SECCIÓN 2: CLASIFICACIÓN POR TIPO =============
        doc.setFontSize(13);
        doc.setFont(undefined, 'bold');
        doc.setFillColor(52, 73, 94);
        doc.rect(10, yPosition, 190, 7, 'F');
        doc.setTextColor(255, 255, 255);
        doc.text('2. CLASIFICACIÓN POR TIPO DE FOLIO', 12, yPosition + 5);
        doc.setTextColor(0, 0, 0);
        yPosition += 10;

        const porTipo = {
            'DOCUMENTO': todosLosDatos.filter(f => f.tipoFolio === 'DOCUMENTO'),
            'FORMATO': todosLosDatos.filter(f => f.tipoFolio === 'FORMATO'),
            'CERTIFICADO': todosLosDatos.filter(f => f.tipoFolio === 'CERTIFICADO')
        };

        // Colores por tipo
        const coloresTipo = {
            'DOCUMENTO': [155, 89, 182],
            'FORMATO': [241, 196, 15],
            'CERTIFICADO': [52, 152, 219]
        };

        Object.keys(porTipo).forEach((tipo, index) => {
            const folios = porTipo[tipo];
            
            if (folios.length > 0) {
                doc.setFontSize(10);
                doc.setFont(undefined, 'bold');
                doc.setFillColor(236, 240, 241);
                doc.rect(10, yPosition, 190, 6, 'F');
                
                // Indicador de color por tipo
                const color = coloresTipo[tipo];
                doc.setFillColor(color[0], color[1], color[2]);
                doc.circle(13, yPosition + 3, 1.5, 'F');
                
                doc.setTextColor(44, 62, 80);
                doc.text(`2.${index + 1} ${tipo}`, 17, yPosition + 4);
                doc.setFont(undefined, 'normal');
                doc.setTextColor(127, 140, 141);
                doc.text(`(${folios.length} ${folios.length === 1 ? 'folio' : 'folios'})`, 55, yPosition + 4);
                doc.setTextColor(0, 0, 0);
                yPosition += 8;

                autoTable(doc, {
                    startY: yPosition,
                    head: [['N°', 'Nombre del Folio', 'Prioridad', 'Vencimiento']],
                    body: folios.map((folio, idx) => [
                        idx + 1,
                        folio.nombre,
                        folio.prioridad,
                        folio.periodo || 'Sin vencimiento'
                    ]),
                    styles: { 
                        fontSize: 8,
                        cellPadding: 2,
                        lineColor: [189, 195, 199],
                        lineWidth: 0.1
                    },
                    headStyles: { 
                        fillColor: [149, 165, 166],
                        textColor: [255, 255, 255],
                        fontStyle: 'bold',
                        halign: 'center',
                        fontSize: 9,
                        cellPadding: 3
                    },
                    alternateRowStyles: { 
                        fillColor: [250, 250, 250]
                    },
                    columnStyles: {
                        0: { halign: 'center', cellWidth: 12 },
                        1: { cellWidth: 95 },
                        2: { halign: 'center', cellWidth: 35 },
                        3: { halign: 'center', cellWidth: 38 }
                    },
                    margin: { left: 10, right: 10 },
                    theme: 'grid'
                });

                yPosition = doc.lastAutoTable.finalY + 6;
                
                if (yPosition > 260) {
                    doc.addPage();
                    yPosition = 20;
                }
            }
        });

        // ============= PIE DE PÁGINA EN TODAS LAS PÁGINAS =============
        const pageCount = doc.internal.getNumberOfPages();
        const ahora = new Date();
        const horaGeneracion = ahora.toLocaleTimeString('es-PE', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });

        for (let i = 1; i <= pageCount; i++) {
            doc.setPage(i);
            
            // Línea separadora
            doc.setDrawColor(189, 195, 199);
            doc.setLineWidth(0.5);
            doc.line(10, 282, 200, 282);
            
            // Texto del pie
            doc.setFontSize(7);
            doc.setTextColor(127, 140, 141);
            doc.setFont(undefined, 'normal');
            doc.text(
                'Sistema de Gestión de Recursos Humanos',
                10,
                287
            );
            doc.text(
                `Generado el ${fechaActual} a las ${horaGeneracion}`,
                105,
                287,
                { align: 'center' }
            );
            doc.setFont(undefined, 'bold');
            doc.text(
                `Página ${i} de ${pageCount}`,
                200,
                287,
                { align: 'right' }
            );
        }

        // Abrir en nueva pestaña
        const pdfBlob = doc.output('blob');
        const pdfUrl = URL.createObjectURL(pdfBlob);
        window.open(pdfUrl, '_blank');
        
        Swal.close();
        
        Swal.fire({
            icon: 'success',
            title: '¡PDF generado exitosamente!',
            text: 'El reporte se ha abierto en una nueva pestaña',
            timer: 2000,
            showConfirmButton: false
        });
    }, 500);
});




