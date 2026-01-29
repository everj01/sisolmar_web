import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_semanticui.min.css';
import Swal from 'sweetalert2';

let filtroTexto = "";
let filtroTipo = "TODOS";

const tblCliente = new Tabulator("#tblCliente", {  // Suponiendo que 'tblVisible' es la tabla visible
    height:"450px",
    layout:"fitDataFill",
    responsiveLayout: "collapse",
    paginationSize: 10,  // Cantidad de registros por página
    rowHeader:{formatter:"responsiveCollapse", width:30, minWidth:30, hozAlign:"center", resizable:false, headerSort:false},
    columns: [
        { title: "N°", field: "", hozAlign: "center", width: "15%", formatter: "rownum" },
        { title: "Cliente", field: "razon_social", hozAlign: "left", width: '72%' },
        // { title: "RUC", field: "ruc", hozAlign: "center", width: '20%' },
        { title: "", field: "acciones", hozAlign: "center", width: '10%', headerSort: false,
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
    layout:"fitDataFill",
    responsiveLayout: "collapse",
    paginationSize: 10,  // Cantidad de registros por página
    rowHeader:{formatter:"responsiveCollapse", width:30, minWidth:30, hozAlign:"center", resizable:false, headerSort:false},
    columns: [
        { title: "N°", field: "", hozAlign: "center", width: "15%", formatter: "rownum" },
        { title: "Descripción", field: "nombre", hozAlign: "left", width: '71%' },
        { title: "", field: "acciones", hozAlign: "center", width: '10%', headerSort: false,
            formatter: function(cell) {
                const cod = cell.getData().codigo;
                const nombre = cell.getData().nombre;
                return `<input class="form-radio text-primary radCargo" type="radio" name="opCargo" id="radCargo${cod}"
                value="${cod}" data-nombre="${nombre}">`;
            },
        },
    ],
});


document.querySelector("#tblCargo").classList.add("disabled-table");

const tblFolio = new Tabulator("#tblFolio", {
    height:"410px",
    layout:"fitDataFill",
    responsiveLayout: "collapse",
    paginationSize: 10,  // Cantidad de registros por página
    rowHeader:{formatter:"responsiveCollapse", width:30, minWidth:30, hozAlign:"center", resizable:false, headerSort:false},
    columns: [
        { title: "N°", field: "", hozAlign: "center", width: "10%", formatter: "rownum" },
        { title: "Folios", field: "", hozAlign: "center", width: '55%',
            formatter: function(cell) {
                const nombre = cell.getData().nombre;
                const tipo = cell.getData().notificacion;
                if(tipo == '-1'){
                    return /*html*/`${nombre}`;
                }

                return /*html*/`${nombre}&nbsp;${tipo == '0' ? /*html*/`

                    <div class="bg-success/25 text-success text-sm rounded-md p-2 inline-flex items-center" >
                         <i class="fa-solid fa-envelope"></i> Activar
                    </div>
                    `:/*html*/`

                    <div class="bg-danger/25 text-danger text-sm rounded-md p-2 inline-flex items-center" >
                        <i class="fa-solid fa-envelope"></i> Desactivar
                    </div>
                    `}`;
            }

        },
        { title: "Tipo", field: "", hozAlign: "center", width: '25%', headerSort: false,
            formatter: function(cell) {
                const obl = cell.getData().obligatorio;
                return /*html*/ `${ obl == '0' ?
                /*html*/`<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-default-100 text-default-800">
                    ADICIONAL
                </span> `
                :
                /*html*/`<span class="inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-primary-800">
                    PRINCIPAL
                </span>` }`;

            }
        },
        { title: "", field: "acciones", hozAlign: "center", width: '5%', headerSort: false,
            formatter: function(cell) {
                const rowData = cell.getData();
                const cod = rowData.codigo;
                const tiene = rowData.tiene;

                // Si aún no tiene _selected definido, usar "tiene" para inicializarlo
                if (typeof rowData._selected === 'undefined') {
                    rowData._selected = tiene != 0; // inicializa desde la data original
                }

                // Crear checkbox
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.className = "form-checkbox rounded text-primary chkFolio";
                checkbox.id = `chkFolio${cod}`;
                checkbox.value = cod;
                checkbox.checked = rowData._selected;

                // Guardar cambios al interactuar
                checkbox.addEventListener("change", function () {
                    rowData._selected = this.checked;
                });

                return checkbox;
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

//Función para los filtros de CARGO
function aplicarFiltros() {
    let filtros = [];

    // filtro por texto
    if (filtroTexto !== "") {
        filtros.push({ field: "nombre", type: "like", value: filtroTexto });
    }

    // filtro por tipo
    if (filtroTipo !== "TODOS") {
        filtros.push({ field: "tipo", type: "=", value: filtroTipo });
    }

    tblCargo.setFilter(filtros);
}

document.getElementById("buscarCargo").addEventListener("keyup", function () {
    filtroTexto = this.value.toLowerCase().trim();
    aplicarFiltros();
});

document.querySelectorAll('input[name="cargoFiltro"]').forEach(radio => {
    radio.addEventListener("change", function () {
        filtroTipo = this.value; // TODOS, OPERATIVO, ADMINISTRATIVO
        aplicarFiltros();
    });
});

document.getElementById("buscarCliente").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim(); // Convertir a minúsculas

    tblCliente.setFilter([
        [
            { field: "razon_social", type: 'like',  value: valor },
            { field: "RUC", type: 'like',  value: valor },
            { field: "abreviatura", type: 'like',  value: valor },
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
        console.log(response.data[0].codigo_legajo);

        // Buscar el primer elemento con codigo_legajo distinto de 0
        let codigoLegajoValido = 0;

        for (let i = 0; i < response.data.length; i++) {
            if (response.data[i].codigo_legajo !== '0') {
                codigoLegajoValido = response.data[i].codigo_legajo;
                break;
            }
        }

        document.getElementById('hidLegajo').value = codigoLegajoValido;
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


// 🧠 Función externa y reutilizable
function manejarCambioSeleccion(event) {
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
            document.querySelector('#txtNombre').value = `${nombreCliente} - ${nombreCargo}`;
            document.getElementById("btnRegistrar").disabled = false;
        }
    }
}

// 📌 Escucha de eventos central
document.addEventListener("change", manejarCambioSeleccion);

window.quitarNotificacion = function(valor){
    Swal.fire({
        title: "¿Desea quitar esta notificación?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si, porfavor",
        cancelButtonText: "No",
    }).then((result) => {
        if (result.isConfirmed) {

            axios.post(`${ VITE_URL_APP }/api/delete-notif`, { 
                codigo: valor
            }).then(function(response) {
                console.log('eliminado ', response);
                Swal.fire({
                    title: "Eliminado",
                    icon: "success",
                }).then(() => {
                    location.reload();
                });
            })
            .catch(function(error) {
                console.error('Error al guardar el legajo:', error);
            });
        }
    });
}

window.guardarLegajo = function () {
    const data = tblFolio.getData();

    // Filtrar solo los que están seleccionados (_selected === true)
    const selecFolios = data
        .filter(row => row._selected == true)
        .map(row => row.codigo); // Suponiendo que 'codigo' es el valor del folio


    const dataPost = {
        folios: selecFolios,
        codCliente: valorCliente,
        codCargo: valorCargo,
        codLegajo : document.getElementById('hidLegajo').value,
        nombre: document.getElementById('txtNombre').value,
    };

    console.log(dataPost);

    Swal.fire({
        title: "¿Desea guardar el legajo?",
        text: "Cliente: " + nombreCliente + ", cargo: " + nombreCargo,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si, porfavor",
        cancelButtonText: "No",
    }).then((result) => {
        if (result.isConfirmed) {

            axios.post(`${ VITE_URL_APP }/api/save_legajo`, dataPost)
            .then(function(response) {
                console.log('guardar legajo', response);
                console.log('nuevo', response);

                Swal.fire({
                    title: "Guardado",
                    icon: "success",
                }).then(() => {
                    //location.reload();
                    cargarFolios(valorCliente, valorCargo);
                });
            })
            .catch(function(error) {
                console.error('Error al guardar el legajo:', error);
            });
        }
    });

}
