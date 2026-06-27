import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

let codClienteSeleccionado = null;
let codCargoSeleccionado = null;

//Tabla de Coincidencias
/*const tblPersonsCN = new Tabulator("#tblPersonsCN", {
    height: "100%",
    layout:"fitDataFill",
    responsiveLayout: "collapse",
    columns: [
        {title:"Código", field:"CODI_PERS", hozAlign:"center", width: '10%'},
        {title:"Personal", field:"personal", hozAlign:"left", width: '30%'},
        {title:"Nro Doc", field:"nroDoc", hozAlign:"center", width: '15%'},
        {title:"Sucursal", field:"sucursal", hozAlign:"center", width: '18%'},
    ],
});
*/

//Tabla de Clientes
const tblCliente = new Tabulator("#tblCliente", {  // Suponiendo que 'tblVisible' es la tabla visible
    height:"410px",
    layout:"fitDataFill",
    responsiveLayout:"collapse",
    //pagination: true,
    paginationSize: 10,  // Cantidad de registros por página
    locale: "es",  // Configurar idioma a español
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
                "default": "Filtrar...", // Texto en filtros de encabezado
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
    //filterMode:"remote",
    rowHeader:{formatter:"responsiveCollapse", width:30, minWidth:30, hozAlign:"center", resizable:false, headerSort:false},
    columns: [
        { title: "N°", field: "", hozAlign: "center", width: "15%", formatter: "rownum" },
        { title: "Cliente", field: "razon_social", hozAlign: "left", width: '69%' },
        // { title: "RUC", field: "ruc", hozAlign: "center", width: '20%' },
        { title: "Elegir", field: "acciones", hozAlign: "center", width: '10%', headerSort: false,
            formatter: function(cell) {
                const cod = cell.getData().codigo;
                const razon_social = cell.getData().razon_social;
                const abreviatura = cell.getData().abreviatura;
                return `<input class="form-radio text-primary radCliente" type="radio" name="opCliente" id="radCliente${cod}"
                 value="${cod}" data-nombre="${razon_social}" data-abre="${abreviatura}">`;
            },
            cellClick: function(e, cell) {
                if (e.target.classList.contains("radCliente")) {
                    codClienteSeleccionado = e.target.value;
                }
            }
        },
    ],
});


const tblCargo = new Tabulator("#tblCargo", {  // Suponiendo que 'tblVisible' es la tabla visible
    height:"410px",
    layout:"fitDataFill",
    responsiveLayout:"collapse",
    //pagination: true,
    paginationSize: 10,  // Cantidad de registros por página
    locale: "es",  // Configurar idioma a español
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
                "default": "Filtrar...", // Texto en filtros de encabezado
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
    //filterMode:"remote",
    rowHeader:{formatter:"responsiveCollapse", width:30, minWidth:30, hozAlign:"center", resizable:false, headerSort:false},
    columns: [
        { title: "N°", field: "", hozAlign: "center", width: "15%", formatter: "rownum" },
        { title: "Cargo", field: "nombre", hozAlign: "left", width: '69%' },
        { title: "Elegir", field: "acciones", hozAlign: "center", width: '10%', headerSort: false,
            formatter: function(cell) {
                const cod = cell.getData().codigo;

                const nombre = cell.getData().nombre;
                return `<input class="form-radio text-primary radCargo" type="radio" name="opCargo" id="radCargo${cod}" 
                value="${cod}" data-nombre="${nombre}">`;
            },
            cellClick: function(e, cell) {
                if (e.target.classList.contains("radCargo")) {
                    codCargoSeleccionado = e.target.value;
                }
            }
        },
    ],
});

getPersonal('', '', 1);

document.getElementById('btnTodos').addEventListener('click', function(){
    document.querySelectorAll(".radCliente").forEach(r => r.checked = false);
    document.querySelectorAll(".radCargo").forEach(r => r.checked = false);
    document.getElementById('dataDocsLeg').classList.add('hidden');
    codClienteSeleccionado = null;
    codCargoSeleccionado = null;
    getPersonal('', '', 1);
});
        
cargarClientes();

function cargarCargos(codCliente){
     document.getElementById('dataDocsLeg').classList.add('hidden');
    axios.get(`${ VITE_URL_APP }/api/get-cargos`, {
        params: {
            cliente: codCliente,
        }
    })
    .then(response => {
        console.log('cargos', response.data);
        const datosTabla = response.data;
        tblCargo.setData(datosTabla);
       
         tblPersonas.setData([]);
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}

function cargarClientes(){
    document.getElementById('dataDocsLeg').classList.add('hidden');
    axios.get(`${ VITE_URL_APP }/api/get-clientes-legajos`)
    .then(response => {
        console.log(response);
        console.log('clientes', response.data);
        const datosTabla = response.data;
        tblCliente.setData(datosTabla);
        
        //tblPersonas.setData([]);
        
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}

// Función para actualizar la tabla con el filtro PRINCIPAL o AUXILIAR
function filterTableByTipoFolio() {
    const tipoFolioSeleccionado = document.querySelector('input[name="tipo_folio"]:checked').value;
    tblDocs.setFilter("tipo_folio", "=", tipoFolioSeleccionado);
}

// Escuchar los cambios en los radio buttons
document.querySelectorAll('input[name="tipo_folio"]').forEach(radio => {
    radio.addEventListener('change', filterTableByTipoFolio);
});

//Tabla de Documentos LEGAJOS
const tblLegajos = new Tabulator("#tblDocsLegajo", {
    height: "100%",
    layout: "fitDataFill",
    responsiveLayout: "collapse",
    columns: [
        { title: "Folio", field: "documento", hozAlign: "left", width: '40%' },
        { title: "Emision", field: "fecha_emision", hozAlign: "center", width: '20%' ,
            formatter: function(cell, formatterParams){
                var emision = cell.getValue();
                if (emision === null){
                    return '-';
                }else{
                    return emision;
                }
            }
         },
        { title: "Caducidad", field: "fecha_caducidad", hozAlign: "center", width: '20%',
            formatter: function(cell, formatterParams) {
                var vigente = cell.getRow().getData().vigente;
                var fechaCaducidad = cell.getValue();
                if (vigente == 1) {
                    return `<span class="text-vigente-800 font-bold">${fechaCaducidad}</span>`
                } else if (vigente == 0) {
                    return `<span class="text-vencido-800 font-bold">${fechaCaducidad}</span>`
                } else {
                    return '-';
                }
            }
         },
        { title: "Acciones", field: "accionesy", hozAlign: "center", width: '20%',
            formatter: function(cell, formatterParams, onRendered) {
                var filePath = cell.getRow().getData().ruta_archivo;
                var url = '/storage/' + filePath; // Concatenar el link a la ruta del archivo
                if(filePath){
                    var viewBtn = `<a href="${url}" target="_blank" class="btn rounded-full view-btn bg-info/25 text-info hover:bg-info hover:text-white"><i class="fa fa-eye view-btn"></i></a>`;
                }else{
                    var viewBtn = `<a href="${url}" target="_blank" class="pointer-events-none btn rounded-full view-btn bg-warning/25 text-warning-opa bg-gray-200 hover:bg-gray-200"><i class="fa fa-eye"></i></a>`;
                }
                //var chargeBtn = `<button type="button" class="btn rounded-full charge-btn bg-success/25 text-success hover:bg-success hover:text-white"><i class="fa-solid fa-upload charge-btn"></i></button>`;
                return /*chargeBtn+' '+*/viewBtn;
            },
            cellClick: function(e, cell) {
                if (e.target.classList.contains('charge-btn')) {
                    const documento = cell.getRow().getData().documento;
                    const periodo = cell.getRow().getData().periodo;
                    const meses = cell.getRow().getData().meses;
                    const codFolio = cell.getRow().getData().codFolio;
                    const vencimiento = cell.getRow().getData().vencimiento;

                    document.querySelector('#modal-file h3.modal-title').textContent = `Documento: ${documento}`;
                    document.querySelector('#txtPeriodo').textContent = `${periodo}`;
                    document.getElementById('codFolio').value = codFolio;
                    document.getElementById('meses').value = meses;

                    // Verificar si vencimiento es 0 y ocultar el campo de caducidad
                    if (vencimiento == 0) {
                        document.getElementById('divCaducidad').classList.add('hidden');
                        document.getElementById('fecha_caducidad').removeAttribute('required'); 
                    } else {
                        document.getElementById('divCaducidad').classList.remove('hidden');
                        document.getElementById('fecha_caducidad').setAttribute('required', 'required');
                    };

                    limpiarModal();
                    document.getElementById('btn-modal-docs').click();
                }
            },
            rowFormatter: function(row) {
                row.getElement().classList.add("hover:bg-indigo-500");  // Cambia "indigo-500" al color que desees
            }
        },
    ]
});

//Tabla de Personas
const tblPersonas = new Tabulator("#tblPersonas", {
    height:"410px",
    layout:"fitData",
    responsiveLayout:"collapse",
    pagination: false,
    paginationSize: 10,
    rowHeader:{formatter:"responsiveCollapse", width:30, minWidth:30, hozAlign:"center", resizable:false, headerSort:false},
    columns:[
        {title:"Código", field:"CODI_PERS", hozAlign:"center", width: '10%'},
        {title:"Personal", field:"personal", hozAlign:"left", width: '20%'},
        {title:"Nro Doc", field:"nroDoc", hozAlign:"center", width: '10%'},
        {title:"Sucursal", field:"sucursal", hozAlign:"center", width: '10%'},
        {title:"Cliente", field:"cliente", hozAlign:"center", width: '15%'},
        {title:"Cargo", field:"cargo", hozAlign:"center", width: '20%'},
        {title:"Acciones", field: "acciones", width: '12%', hozAlign: "center", 
            formatter: function(cell, formatterParams, onRendered) {
                var docsBtn = `<button type="button" class="btn rounded-full docs-btn bg-success/25 text-success hover:bg-success hover:text-white" title="Ver folios">
                <i class="fa-solid fa-book docs-btn"></i></button>`;

                return docsBtn;
            },
            cellClick: function(e, cell) {
                if (e.target.classList.contains('docs-btn')) {

                    const rowData = cell.getRow().getData();

                    var cliente = rowData.codCliente;
                    var cargo = rowData.codCargo;
                    var codi_pers = rowData.CODI_PERS;
                    var per = rowData.personal;

                    getLegajos (cliente,cargo,codi_pers);

                    document.querySelector('.nombrePersDocs').textContent = 'Folios de ' + per;

                    document.getElementById('dataDocsLeg').classList.remove('hidden');
                    //document.getElementById('divCoincidencias').classList.add('hidden');
                }
                //updateCardTitle(persona);
            }
        },
    ],
    rowFormatter: function(row) {
        const data = row.getData();
    
        if (data.PERS_VIGENCIA === "NO") {
            row.getElement().style.color = "red";      // Texto rojo
            // row.getElement().classList.add("miClaseRoja"); // opcional si quieres usar Tailwind
        }
    },
    
});




document.addEventListener('change', function (e) {
    if (e.target.classList.contains('radCliente')) {
        codClienteSeleccionado = e.target.value;
        cargarCargos(codClienteSeleccionado);
    }

    if (e.target.classList.contains('radCargo')) {
        const codCargo = e.target.value;
        if(codClienteSeleccionado){
            getCoincidencias(codClienteSeleccionado,codCargo);
            document.getElementById('dataDocsLeg').classList.add('hidden');
        }else{
            alert('Primero selecciona un cliente.');
        }
    }
});


document.getElementById("buscar").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblPersonas.setFilter([
        [
            { field: "CODI_PERS", type: 'like',  value: valor },
            { field: "personal", type: 'like',  value: valor },
            { field: "nroDoc", type: 'like', value: valor },
            { field: "sucursal", type: 'like', value: valor },
            { field: "cliente", type: 'like', value: valor },
        ]
    ]);
});
document.getElementById("buscarCliente").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblCliente.setFilter([
        [
            { field: "razon_social", type: 'like',  value: valor },
        ]
    ]);
});
document.getElementById("buscarCargo").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblCargo.setFilter([
        [
            { field: "nombre", type: 'like',  value: valor },
        ]
    ]);
});



 function getPersonal(cliente, cargo, tipo = 1){
    axios.get(`${ VITE_URL_APP }/api/get-personal-legajos`)
    .then(response => {
        const datosTabla = response.data;
        console.log(datosTabla, tipo);
      
        if(tipo == 0){
            tblPersonas.setData(datosTabla).then(() => {
                tblPersonas.setFilter(function(data) {
                    return (
                        data.cliente == cliente &&
                        data.cargo == cargo
                    );
                });
            });
        }
        
        if(tipo == 1){
            tblPersonas.replaceData(datosTabla).then(() => {
                tblPersonas.clearFilter(); //quitar os filtros existentes

            });

            document.getElementById('txtTextoilus').textContent = '(TODOS LOS REGISTROS)';
            document.getElementById('txtTextoilus').className = 'text-primary font-semibold text-lg'
           
        }
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}
// Función para obtener los legajos
function getLegajos(cliente, cargo, codigoPer) {
    //alert(codigoPer);
    axios.get(`${ VITE_URL_APP }/api/get-legajos`, {
        params: {
            cliente: cliente,
            cargo: cargo,
            codigo: codigoPer
        }
    })
    .then(function (response) {
        tblLegajos.setData(response.data);
        //document.getElementById('tblDocsLegajo').classList.remove('hidden');
        
        console.log(response.data);
    })
    .catch(function (error) {
        console.error("Error al obtener los legajos:", error);
    });
};

// Función para obtener las coincidencias
function getCoincidencias(cliente, cargo) {
    axios.get(`${ VITE_URL_APP }/api/get-coincidencias`, {
        params: {
            cliente: cliente,
            cargo: cargo
        }
    })
    .then(response => {
        console.log(response.data);
        console.log('aquiii');
        document.getElementById('txtTextoilus').textContent = '(APLICANDO FILTROS)';
        document.getElementById('txtTextoilus').className = 'text-info font-semibold text-lg'
 
        tblPersonas.setData(response.data);
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
};

tblCliente.on("renderComplete", function() {
    if (codClienteSeleccionado) {
        const radio = document.querySelector(`#radCliente${codClienteSeleccionado}`);
        if (radio) radio.checked = true;
    }
});

tblCargo.on("renderComplete", function() {
    if (codCargoSeleccionado) {
        const radio = document.querySelector(`#radCargo${codCargoSeleccionado}`);
        if (radio) radio.checked = true;
    }
});

window.addEventListener("sidebar-toggled", () => {
    tblCliente?.redraw(true);
    tblCargo?.redraw(true);
    tblPersonas?.redraw(true);
    tblLegajos?.redraw(true);
});
