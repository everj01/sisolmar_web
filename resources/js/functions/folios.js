import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Swal from 'sweetalert2';

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
function filterTableByTipoFolio() {
    const folioFiltroSeleccionado = document.querySelector('input[name="folioFiltro"]:checked')?.value;
    if (!folioFiltroSeleccionado) {
        tblFolios.clearFilter();
    } else if (folioFiltroSeleccionado === "TODOS") {
        tblFolios.clearFilter("prioridad");
        tblFolios.clearFilter("tipoFolio");
    } else if (["DOCUMENTO", "FORMATO", "CERTIFICADO"].includes(folioFiltroSeleccionado)) {
        tblFolios.setFilter("tipoFolio", "=", folioFiltroSeleccionado);
        //tblFolios.clearFilter("prioridad");
    } else {
        tblFolios.setFilter("prioridad", "=", folioFiltroSeleccionado);
        //tblFolios.clearFilter("tipoFolio");
    }
}

document.querySelectorAll('input[name="folioFiltro"]').forEach(radio => {
    radio.addEventListener('change', filterTableByTipoFolio);
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




