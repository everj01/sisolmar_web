import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

cargarCargos();

const tblCargos = new Tabulator("#tblCargos", {
    height:"410px",
    layout:"fitData",
    responsiveLayout:"collapse",
    pagination: true,
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
    rowHeader:{formatter:"responsiveCollapse", width:30, minWidth:30, hozAlign:"center", resizable:false, headerSort:false},
    columns: [
        { title: "N°", field: "codigo", hozAlign: "center", width: '20%' },
        { title: "Nombre", field: "descripcion", hozAlign: "left", width: '50%' },
        { title: "Acciones", field: "acciones", hozAlign: "center", width: '30%', 
            formatter: function(cell) {
              var editBtn = `<button type="button" class="btn-modal btn rounded-full edit-btn bg-success/25 text-success hover:bg-success hover:text-white" data-hs-overlay="#modal-editar-cargo"><i class="fa-solid fa-pen-to-square"></i></button>`;
              var deleteBtn = `<button type="button" class="btn rounded-full delete-btn bg-danger/25 text-danger hover:bg-danger hover:text-white"><i class="fa-solid fa-trash-can"></i></button>`;
              return editBtn + " " + deleteBtn;
            },
        },
    ],
});


document.getElementById("buscarCargo").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim(); // Convertir a minúsculas

    tblCargos.setFilter([
        [
            { field: "codigo", type: 'like',  value: valor },
            { field: "descripcion", type: 'like',  value: valor },
        ]
    ]);
});

function cargarCargos(){
    axios.get('http://127.0.0.1:8000/api/get-cargo')
    .then(response => {
        const datosTabla = response.data;
        tblCargos.setData(datosTabla);
        
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}

function cargarAreas(){
    axios.get('http://127.0.0.1:8000/api/get-areas')
    .then(response => {
        console.log(response);
        const datos = response.data;  // Suponiendo que devuelve un array de objetos
        const select = document.getElementById("slcArea");

        // Limpiar opciones anteriores (excepto la primera)
        select.innerHTML = '<option value="">Seleccione una opción</option>';

        // Recorrer los datos y agregarlos como opciones
        datos.forEach(item => {
            const option = document.createElement("option");
            option.value = item.CODI_TIPO_CARG;  // Suponiendo que 'id' es el valor
            option.textContent = item.DESC_TIPO_CARG;  // Suponiendo que 'nombre' es lo que se muestra
            select.appendChild(option);
        });
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}


function cargarPosicion(){
    axios.get('http://127.0.0.1:8000/api/get-posicion')
    .then(response => {
        console.log(response);
        const datos = response.data;  // Suponiendo que devuelve un array de objetos
        const select = document.getElementById("slcPosicion");

        // Limpiar opciones anteriores (excepto la primera)
        select.innerHTML = '<option value="">Seleccione una opción</option>';

        // Recorrer los datos y agregarlos como opciones
        datos.forEach(item => {
            const option = document.createElement("option");
            option.value = item.POSI_CODIGO;  // Suponiendo que 'id' es el valor
            option.textContent = item.POSI_DESCRIPCION;  // Suponiendo que 'nombre' es lo que se muestra
            select.appendChild(option);
        });
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}



function cargarGrupo(){
    axios.get('http://127.0.0.1:8000/api/get-grupo')
    .then(response => {
        console.log(response);
        const datos = response.data;  // Suponiendo que devuelve un array de objetos
        const select = document.getElementById("slcGrupo");

        // Limpiar opciones anteriores (excepto la primera)
        select.innerHTML = '<option value="">Seleccione una opción</option>';

        // Recorrer los datos y agregarlos como opciones
        datos.forEach(item => {
            const option = document.createElement("option");
            option.value = item.TIPP_CODIGO;  // Suponiendo que 'id' es el valor
            option.textContent = item.TIPP_DESCRIPCION;  // Suponiendo que 'nombre' es lo que se muestra
            select.appendChild(option);
        });
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}
cargarGrupo();
cargarPosicion();
cargarAreas();


cargarAreas();

//================================ GUARDAR LOS DATOS POR AXIOS ================================//
document.getElementById('formSaveCargo').addEventListener('submit', function(event) {
    event.preventDefault();
    var nombre = document.getElementById('nombre').value;

    if (nombre) {
        axios.post('http://127.0.0.1:8000/api/save_cargo', {
            nombre: nombre,
        })
        .then(function(response) {
            cargarCargos();
        })
        .catch(function(error) {
            console.error('Error al guardar las fechas:', error);
        });
    }
});