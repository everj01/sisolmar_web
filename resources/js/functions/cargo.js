import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Swal from 'sweetalert2';
import { buildElAttrs } from '@fullcalendar/core/internal';

document.addEventListener("DOMContentLoaded", () => {
    cargarCargos();
    cargarCounters();
});

let modoEdicion = false;
let datosOriginales = null;

function limpiarForm(){
    document.getElementById("txtMensajeNuevo").innerText = "Nuevo registro";
    document.getElementById("txtMensajeNuevo").className = "inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-primary-800";
    document.getElementById("btnRegistrarCargo").innerHTML = 'Guardar <i class="fa-solid fa-floppy-disk"></i>';
    document.getElementById('soloEdicion').classList.remove("flex");
    document.getElementById('soloEdicion').classList.add("hidden");
    document.getElementById('codigoEditar').value = '0';
    document.getElementById("formSaveCargo").reset();

    modoEdicion = false;
    datosOriginales = null;
}

axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').content;

const tblCargos = new Tabulator("#tblCargos", {
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
    rowHeader:{formatter:"responsiveCollapse", width:30, minWidth:30, hozAlign:"center", resizable:false, headerSort:false},
    columns: [
        { title: "Nombre", field: "nombre", hozAlign: "left", width: '56%' },
        { title: "Área", field: "area", hozAlign: "center", width: '24%' },
        { title: "Acciones", field: "acciones", hozAlign: "center", width: '20%', headerSort: false,
            formatter: function(cell, formatterParams, onRendered) {
                const editBtn = `<button type="button" 
                class="btn-editar-cargo me-3 btn rounded-full edit-btn bg-info/25 text-info hover:bg-info hover:text-white">
                    <i class="fa-solid fa-pen-to-square btn-editar-cargo"></i>
                </button>`;
                
                const deleteBtn = cell.getData().habilitado == '1' ? `<button type="button" 
                class="btn-eliminar-cargo btn rounded-full delete-btn bg-danger/25 text-danger hover:bg-danger hover:text-white">
                    <i class="fa-solid fa-trash-can btn-eliminar-cargo"></i>
                </button>`
                : 
                `<button type="button" 
                class="btn-activar-cargo btn rounded-full delete-btn bg-success/25 text-success hover:bg-success hover:text-white">
                    <i class="fa-solid fa-check btn-activar-cargo"></i>
                </button>`;

                return editBtn + ' ' + deleteBtn;
            },
            cellClick: function(e, cell){
                if(e.target.classList.contains('btn-editar-cargo')){
                    modoEdicion = true;
                    const codigo = cell.getRow().getData().codigo;

                    axios.get(`${ VITE_URL_APP }/api/get-cargo/${codigo}`)
                    .then(response => {
              
                        const data = response.data[0];
                        datosOriginales = { ...data };
                        if(data.cod_tipo == '1'){
                            document.getElementById('opOperativo').checked = true;
                        }else{
                            document.getElementById('opAdmins').checked = true;
                        }
                        //Disparar el evento para mostrar/ocultar combos
                        document.querySelector('input[name="rdTipoCargo"]:checked')
                        .dispatchEvent(new Event('change'));

                        document.getElementById('slcArea').value = data.cod_area;
                        document.getElementById('nombre').value = data.nombre;
                        document.getElementById('txtDescripcion').value = data.descripcion;
                        document.getElementById('txtAbreviatura').value = data.abreviatura;
                        document.getElementById('slcPosicion').value = data.cod_posicion;
                        document.getElementById('slcGrupo').value = data.cod_grupo;

                        document.getElementById('codigoEditar').value = data.codigo;

                        document.getElementById("txtMensajeNuevo").innerText = "Editando registro";
                        document.getElementById("txtMensajeNuevo").className = "inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800";
                        document.getElementById("btnRegistrarCargo").innerHTML = 'Guardar cambios <i class="fa-solid fa-floppy-disk"></i>';
                        document.getElementById('soloEdicion').classList.remove("hidden");
                        document.getElementById('soloEdicion').classList.add("flex");
                    })
                    .catch(error => {
                        console.error("Hubo un error:", error);
                    });

                    console.log('editar ' + codigo);

                } else if (e.target.classList.contains('btn-eliminar-cargo')){
                    const nombretxt = cell.getRow().getData().nombre
                    Swal.fire({
                        title: "¿Esta seguro?",
                        text: "Usted va a desactivar el cargo \n" + nombretxt,
                        icon: "warning",
                        showCancelButton: true,
                        confirmButtonColor: "#3085d6",
                        cancelButtonColor: "#d33",
                        confirmButtonText: "Si",
                        cancelButtonText: "Cancelar"
                      }).then((result) => {
                        if (result.isConfirmed) {
                            const codigo = cell.getRow().getData().codigo;
                            axios.post(`${ VITE_URL_APP }/api/delete-cargo`, {
                                codigo: codigo,
                            }).then(function(response) {
                                if(response.status == '200'){
                                    cargarCargos();
                                }else{
                                    console.log('problema al actualizar');
                                }
                            })
                            .catch(function(error) {
                                console.error('Error al actualizar:', error);
                            });
                        }
                      });



                    

                } else if (e.target.classList.contains('btn-activar-cargo')){
                    const codigo = cell.getRow().getData().codigo;
                    axios.post(`${ VITE_URL_APP }/api/activar-cargo`, {
                        codigo: codigo,
                    }).then(function(response) {
                        if(response.status == '200'){
                            cargarCargos();
                        }else{
                            console.log('problema al actualizar');
                        }
                    })
                    .catch(function(error) {
                        console.error('Error al actualizar:', error);
                    });

                }
            },
           
        },
    ],
    rowFormatter: function(row) {
        let data = row.getData();
        
        if (data.habilitado != "1") {
            row.getElement().style.backgroundColor = "#ffe9e9"; 
        } 
    }
});

window.aplicarFiltroEliminarCargo = (op) => {
    if(op === 0) {tblCargos.setFilter("habilitado", "=", "0");}else{
        tblCargos.clearFilter();
    }
    
}

window.aplicarFiltroSoloActivos = (op) => {
    if(op === 1) {tblCargos.setFilter("habilitado", "=", "1");}else{
        tblCargos.clearFilter();
    }
    
}

// Función para CANCELAR
document.getElementById("cancelButton").addEventListener("click", function () {

    // Si NO estamos editando → es un NUEVO registro
    if (!modoEdicion) {
        limpiarForm();
        return;
    }

    // Si estamos editando → restaurar valores
    if (datosOriginales) {

        // Tipo (radio)
        if (datosOriginales.cod_tipo == '1') {
            document.getElementById('opOperativo').checked = true;
        } else {
            document.getElementById('opAdmins').checked = true;
        }

        document.getElementById('slcArea').value = datosOriginales.cod_area;
        document.getElementById('nombre').value = datosOriginales.nombre;
        document.getElementById('txtDescripcion').value = datosOriginales.descripcion;
        document.getElementById('txtAbreviatura').value = datosOriginales.abreviatura;
        document.getElementById('slcPosicion').value = datosOriginales.cod_posicion;
        document.getElementById('slcGrupo').value = datosOriginales.cod_grupo;

        document.getElementById('codigoEditar').value = datosOriginales.codigo;
    }

    // Restaurar textos y estilos
    document.getElementById("txtMensajeNuevo").innerText = "Editando registro";
    document.getElementById("txtMensajeNuevo").className =
        "inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-red-100 text-red-800";

    document.getElementById("btnRegistrarCargo").innerHTML = 
        'Guardar cambios <i class="fa-solid fa-floppy-disk"></i>';

    document.getElementById('soloEdicion').classList.remove("hidden");
    document.getElementById('soloEdicion').classList.add("flex");
});

document.querySelector('.clean-btn').addEventListener('click', limpiarForm);

// Función para BUSCAR
document.getElementById("buscarCargo").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim(); // Convertir a minúsculas

    tblCargos.setFilter([
        [
            { field: "area", type: 'like',  value: valor },
            { field: "descripcion", type: 'like',  value: valor },
            { field: "nombre", type: 'like',  value: valor },
        ]
    ]);
});

// Función para actualizar la tabla con los filtros
function filterTableByTipoCargo() {
    const cargoFiltroSeleccionado = document.querySelector('input[name="cargoFiltro"]:checked')?.value;
    if (!cargoFiltroSeleccionado) {
        tblCargos.clearFilter();
    } else if (cargoFiltroSeleccionado === "TODOS") {
        tblCargos.clearFilter("tipo");
    } else {
        tblCargos.setFilter("tipo", "=", cargoFiltroSeleccionado);
    }
}

document.querySelectorAll('input[name="cargoFiltro"]').forEach(radio => {
    radio.addEventListener('change', filterTableByTipoCargo);
});

//Si es ADMIN no SERVICIO
const opOperativo = document.getElementById('opOperativo');
const opAdmins = document.getElementById('opAdmins');
const divServicio = document.getElementById('slcPosicion').parentElement;
const divSubservicio = document.getElementById('divSubservicios');
document.querySelectorAll('input[name="rdTipoCargo"]').forEach(radio => {
    radio.addEventListener('change', () => {

        if (opAdmins.checked) {
            // Ocultar combos
            divServicio.classList.add('hidden');
            divSubservicio.classList.add('hidden');
        } else {
            // Mostrar combos
            divServicio.classList.remove('hidden');
            divSubservicio.classList.remove('hidden');
        }

    });
});
function cargarCargos(){
    axios.get(`${ VITE_URL_APP }/api/get-cargo`)
    .then(response => {
        const datosTabla = response.data;
        tblCargos.setData(datosTabla);
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}

function cargarCounters(){
    axios.get(`${VITE_URL_APP}/api/cargo-counters`)
    .then(response => {
        const counters = response.data;

        document.querySelector('label[for="radioTodos"]').innerHTML = 
            `TODOS (${counters.todos})`;

        document.querySelector('label[for="radioOper"]').innerHTML = 
            `OPERATIVO (${counters.operativo})`;

        document.querySelector('label[for="radioAdmin"]').innerHTML = 
            `ADMINISTRATIVO (${counters.administrativo})`;

    })
    .catch(error => {
        console.error("Error obteniendo contadores:", error);
    });
}

function cargarAreas(){
    axios.get(`${ VITE_URL_APP }/api/get-areas`)
    .then(response => {
        console.log(response);
        const datos = response.data;  // Suponiendo que devuelve un array de objetos
        const select = document.getElementById("slcArea");

        // Limpiar opciones anteriores (excepto la primera)
        // select.innerHTML = '<option value="">Seleccione una opción</option>';

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


document.getElementById('slcPosicion').addEventListener('change', (e)=>{
    document.getElementById('divSubservicios').classList.remove("hidden");
    listarSubServicios(e.target.value);
});
  
//listarSubServicios(1);
function listarSubServicios(codigo){
    axios.get(`${ VITE_URL_APP }/api/get-grupo/` + codigo)
    .then(response => {
        console.log(response);
        const datos = response.data;  // Suponiendo que devuelve un array de objetos
        const select = document.getElementById("slcGrupo");

        // Limpiar opciones anteriores (excepto la primera)
        select.innerHTML = '';

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


function cargarPosicion(){
    axios.get(`${ VITE_URL_APP }/api/get-posicion`)
    .then(response => {
        console.log('posicion', response);
        const datos = response.data;  // Suponiendo que devuelve un array de objetos
        const select = document.getElementById("slcPosicion");

        // Limpiar opciones anteriores (excepto la primera)
        //select.innerHTML = '<option value="">Seleccione una opción</option>';

        // Recorrer los datos y agregarlos como opciones
        datos.forEach(item => {
            if(item.POSI_DESCRIPCION != '<TODOS>'){
                const option = document.createElement("option");
                option.value = item.POSI_CODIGO;  // Suponiendo que 'id' es el valor
                option.textContent = item.POSI_DESCRIPCION;  // Suponiendo que 'nombre' es lo que se muestra
                select.appendChild(option);
            }  
        });
    })
    .catch(error => {
        console.error("Hubo un error:", error);
    });
}

cargarPosicion();
cargarAreas();

//================================ GUARDAR LOS DATOS POR AXIOS ================================//
document.getElementById('formSaveCargo').addEventListener('submit', function(event) {
    event.preventDefault();
    let rdTipoCargo = document.querySelector('input[name="rdTipoCargo"]:checked').value;
    let slcArea = document.getElementById('slcArea').value;
    let nombre = document.getElementById('nombre').value;
    let txtDescripcion = document.getElementById('txtDescripcion').value;
    let txtAbreviatura = document.getElementById('txtAbreviatura').value;
    let slcPosicion = document.getElementById('slcPosicion').value;
    let slcGrupo = document.getElementById('slcGrupo').value;

    let tipoRegistro = document.getElementById('codigoEditar').value;

    if(tipoRegistro == '0'){
        if (nombre) {
            axios.post(`${ VITE_URL_APP }/save_cargo`, {
                tipoCargo: rdTipoCargo,
                codArea: slcArea,
                nombre: nombre,
                descripcion: txtDescripcion,
                abreviatura: txtAbreviatura,
                codPosicion: slcPosicion,
                codGrupo: slcGrupo
            }, {
                withCredentials: true
            })
            .then(function(response) {
                if(response.status == '200'){
                    Swal.fire({
                        title: "Registro exitoso",
                        icon: "success"
                    });
                    cargarCounters();
                    cargarCargos();
                }else{
                    Swal.fire({
                        title: "Error al registrar",
                        icon: "info"
                      });
                }
                
            })
            .catch(function(error) {
                console.error('Error al guardar las fechas:', error);
            });
        }
    }else{
        if (nombre) {
            axios.post(`${ VITE_URL_APP }/api/update_cargo`, {
                tipoCargo: rdTipoCargo,
                codArea: slcArea,
                nombre: nombre,
                descripcion: txtDescripcion,
                abreviatura: txtAbreviatura,
                codPosicion: slcPosicion,
                codGrupo: slcGrupo,
                codigo: tipoRegistro,
            })
            .then(function(response) {
                if(response.status == '200'){
                    Swal.fire({
                        title: "Cargo modificado",
                        icon: "success"
                    });
                    cargarCargos();
                }else{
                    Swal.fire({
                    title: "Error al actualizar",
                    icon: "info"
                    });
                }
                
            })
            .catch(function(error) {
                console.error('Error al guardar las fechas:', error);
            });
        }
    }

    //console.log(rdTipoCargo, slcArea,nombre, txtDescripcion, txtAbreviatura,  slcPosicion, slcGrupo);

    
});




function activarCargo(codigo){
    axios.post(`${ VITE_URL_APP }/api/activar-cargo`, {
        codigo: codigo,
    }).then(function(response) {
        if(response.status == '200'){
            cargarCargos();
        }else{
           console.log('problema al actualizar');
        }
    })
    .catch(function(error) {
        console.error('Error al actualizar:', error);
    });
}