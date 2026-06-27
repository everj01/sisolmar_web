import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_semanticui.min.css';
import Swal from 'sweetalert2';

const tblCliente = new Tabulator("#tblCliente", {  // Suponiendo que 'tblVisible' es la tabla visible
    height:"410px",
    layout:"fitData",
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
        { title: "CLIENTE", field: "razon_social", hozAlign: "left", width: '75%' },
        // { title: "RUC", field: "ruc", hozAlign: "center", width: '20%' },
        { title: "", field: "acciones", hozAlign: "center", width: '10%', 
            formatter: function(cell) {
                const cod = cell.getData().codigo;
                const razon_social = cell.getData().razon_social;
                return `<input class="form-radio text-primary radCliente" type="radio" name="opCliente" id="radCliente${cod}"
                 value="${cod}" data-nombre="${razon_social}">`;
            },
        },
    ],
});

const tblCargo = new Tabulator("#tblCargo", {  // Suponiendo que 'tblVisible' es la tabla visible
    height:"410px",
    layout:"fitData",
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
        { title: "NOMBRE", field: "descripcion", hozAlign: "left", width: '75%' },
        { title: "", field: "acciones", hozAlign: "center", width: '10%', 
            formatter: function(cell) {
                const cod = cell.getData().codigo;
                const descripcion = cell.getData().descripcion;
                return `<input class="form-radio text-primary radCargo" type="radio" name="opCargo" id="radCargo${cod}" 
                value="${cod}" data-nombre="${descripcion}">`;
            },
        },
    ],
});


document.querySelector("#tblCargo").classList.add("disabled-table");

const tblFolio = new Tabulator("#tblFolio", {  // Suponiendo que 'tblVisible' es la tabla visible
    height:"410px",
    layout:"fitData",
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
        { title: "N°", field: "", hozAlign: "center", width: "10%", formatter: "rownum" },
        { title: "NOMBRE", field: "nombre", hozAlign: "center", width: '59%' },
        { title: "TIPO", field: "", hozAlign: "center", width: '19%',
            formatter: function(cell) {
                const obl = cell.getData().obligatorio;
                return `${ obl == '0' ? 
                `<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-default-100 text-default-800">AUXILIAR</span>
                ` 
                : 
                `<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-primary-800">PRINCIPAL</span>` }`;
              
            },
         },
        { title: "", field: "acciones", hozAlign: "center", width: '9%', 
            formatter: function(cell) {
                const cod = cell.getData().codigo;
                const tiene = cell.getData().tiene;
                return `<input type="checkbox" class="form-checkbox rounded text-primary chkFolio" id="chkFolio${cod}"  value="${cod}" ${tiene != 0 ? 'checked' : ''}>`;
              
            },
        },
    ],

    rowFormatter: function(row) {
        let data = row.getData(); // Obtener datos de la fila
        
        if (data.obligatorio != "0") {
            row.getElement().style.backgroundColor = "#f2f5ff"; // Verde claro
        } 
    }
});

document.querySelector("#tblFolio").classList.add("disabled-table");

document.getElementById("buscarCargo").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim(); // Convertir a minúsculas

    tblCargo.setFilter([
        [
            { field: "nombre", type: 'like',  value: valor },
        ]
    ]);
});


document.getElementById("buscarCliente").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim(); // Convertir a minúsculas

    tblCargo.setFilter([
        [
            { field: "cliente", type: 'like',  value: valor },
            { field: "RUC", type: 'like',  value: valor },
        ]
    ]);
});

document.getElementById("buscarFolio").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim(); // Convertir a minúsculas

    tblFolio.setFilter([
        [
            { field: "nombre", type: 'like',  value: valor }, 
        ]
    ]);
});

function cargarDatos(){
    axios.get(`${ VITE_URL_APP }/api/get-cargo`)
    .then(response => {
        console.log('cargos', response.data);
        const datosTabla = response.data;
        tblCargo.setData(datosTabla);
        
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}

function cargarClientes(){
    axios.get(`${ VITE_URL_APP }/api/get-clientes`)
    .then(response => {
        console.log(response);
        console.log('clientes', response.data);
        const datosTabla = response.data;
        tblCliente.setData(datosTabla);
        
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}



function cargarFolios(codCliente, codCargo){
    axios.get(`${ VITE_URL_APP }/api/get-folios/${codCliente}/${codCargo}`)
    .then(response => {
        console.log('folios', response.data);
        const datosTabla = response.data;
        tblFolio.setData(datosTabla);

        document.getElementById('hidLegajo').value = response.data[0].codigo_legajo;
        
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}

cargarDatos();
cargarClientes();
// TABLA DE DOCUMENTOS POR PERSONA

let valorCliente;
let valorCargo;
let nombreCliente = '';
let nombreCargo = '';
document.addEventListener("change", function (event) {
    if (event.target.matches(".radCliente") || event.target.matches(".radCargo")) {
        const esCliente = event.target.matches(".radCliente");

        if (esCliente) {
            valorCliente = event.target.value;
            nombreCliente = event.target.dataset.nombre;
            document.querySelector("#tblCargo").classList.remove("disabled-table");
        } else {
            valorCargo = event.target.value;
            nombreCargo = event.target.dataset.nombre;
        }

        if (valorCliente && valorCargo) {
            cargarFolios(valorCliente, valorCargo);
            document.querySelector("#tblFolio").classList.remove("disabled-table");
            document.querySelector('#txtNombre').value = `LEGAJO DE ${nombreCliente} PARA ${nombreCargo}`;
            document.getElementById("btnRegistrar").disabled = false;
        }
    }
});

document.getElementById('btnRegistrar').addEventListener('click', () => {
    Swal.fire({
        title: "¿Desea registrar el legajo?",
        text: "Cliente: " + nombreCliente + ", cargo: " + nombreCargo,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si, porfavor",
        cancelButtonText: "No"
        }).then((result) => {
        if (result.isConfirmed) {
            const valFolios = [...document.querySelectorAll(".chkFolio:checked")].map(checkbox => checkbox.value);
            axios.post(`${ VITE_URL_APP }/api/save_legajo`, {
                folios: valFolios,
                codCliente: valorCliente,
                codCargo: valorCargo,
                codLegajo : document.getElementById('hidLegajo').value,
                nombre: document.getElementById('txtNombre').value,
            })
            .then(function(response) {
                console.log(response);
            })
            .catch(function(error) {
                console.error('Error al guardar las fechas:', error);
            });
        }
        });
});




