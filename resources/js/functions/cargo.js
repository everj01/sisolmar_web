import axios from 'axios';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import Swal from 'sweetalert2';
import { buildElAttrs } from '@fullcalendar/core/internal';

document.addEventListener("DOMContentLoaded", () => {
    cargarCargos();
    cargarCounters();
});

let modoEdicion = false;
let datosOriginales = null;

function limpiarForm() {
    document.getElementById("txtMensajeNuevo").innerText = "Nuevo registro";
    document.getElementById("txtMensajeNuevo").className = "inline-flex items-center gap-1.5 py-1.5 px-3 rounded-full text-xs font-medium bg-primary/25 text-primary-800";
    document.getElementById("btnRegistrarCargo").innerHTML = 'Guardar <i class="fa-solid fa-floppy-disk"></i>';
    document.getElementById('soloEdicion').classList.remove("flex");
    document.getElementById('soloEdicion').classList.add("hidden");
    document.getElementById('codigoEditar').value = '0';

    // Limpiar inputs y notificar a Alpine para que resetee sus bindings
    ['nombre', 'txtDescripcion', 'txtAbreviatura'].forEach(id => {
        const el = document.getElementById(id);
        el.value = '';
        el.dispatchEvent(new Event('input'));
    });

    // Resetear selects
    document.getElementById('slcArea').value = '';
    document.getElementById('slcPosicion').value = '';

    // Restaurar slcGrupo con su placeholder y ocultarlo
    document.getElementById('slcGrupo').innerHTML = '<option value="" disabled selected>-Seleccionar-</option>';
    document.getElementById('divSubservicios').classList.add('hidden');

    // Volver a Operativo y disparar el change para mostrar el div de servicio
    document.getElementById('opOperativo').checked = true;
    document.getElementById('opOperativo').dispatchEvent(new Event('change'));

    modoEdicion = false;
    datosOriginales = null;

    document.getElementById('avisoNombreRepetido').classList.add('hidden');
    document.getElementById('btnRegistrarCargo').disabled = false;
    document.getElementById('btnRegistrarCargo').classList.remove('opacity-50', 'cursor-not-allowed');
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
    rowHeader: { formatter: "responsiveCollapse", width: 30, minWidth: 30, hozAlign: "center", resizable: false, headerSort: false },
    columns: [
        { title: "Nombre", field: "nombre", hozAlign: "left", width: '40%' },
          { title: "Tipo", field: "tipo", hozAlign: "center", width: '18%',
              formatter: function(cell) {
                  const val = cell.getValue();
                  if (!val) return '';
                  const cls = val === 'OPERATIVO'
                      ? 'bg-info/20 text-info'
                      : 'bg-primary/20 text-primary';
                  return `<span class="px-2 py-0.5 rounded-full text-xs font-medium ${cls}">${val}</span>`;
              }
          },
          { title: "Área", field: "area", hozAlign: "center", width: '22%' },
          {
            title: "Acciones", field: "acciones", hozAlign: "center", width: '20%', headerSort: false,
            formatter: function (cell, formatterParams, onRendered) {
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
            cellClick: function (e, cell) {
                if (e.target.classList.contains('btn-editar-cargo')) {
                    modoEdicion = true;
                    const codigo = cell.getRow().getData().codigo;

                    axios.get(`${VITE_URL_APP}/api/get-cargo/${codigo}`)
                        .then(response => {

                            const data = response.data[0];
                            datosOriginales = { ...data };
                            if (data.cod_tipo == '1') {
                                document.getElementById('opOperativo').checked = true;
                            } else {
                                document.getElementById('opAdmins').checked = true;
                            }
                            //Disparar el evento para mostrar/ocultar combos
                            document.querySelector('input[name="rdTipoCargo"]:checked')
                                .dispatchEvent(new Event('change'));

                            document.getElementById('slcArea').value = data.cod_area;
                            document.getElementById('nombre').value = data.nombre;
                            document.getElementById('txtDescripcion').value = data.descripcion;
                            document.getElementById('txtAbreviatura').value = data.abreviatura;
                             document.getElementById('slcPosicion').value = data.cod_posicion ?? '';
                            document.getElementById('codigoEditar').value = data.codigo;

                            // Subservicio: cargar opciones si tiene servicio, limpiar si no
                            if (data.cod_tipo == '1' && data.cod_posicion) {
                                listarSubServicios(data.cod_posicion).then(() => {
                                    if (data.cod_grupo) {
                                        document.getElementById('slcGrupo').value = data.cod_grupo;
                                    } else {
                                        document.getElementById('slcGrupo').innerHTML = '<option value="" disabled selected>-Seleccionar-</option>';
                                    }
                                });
                            } else {
                                document.getElementById('slcGrupo').innerHTML = '<option value="" disabled selected>-Seleccionar-</option>';
                                document.getElementById('divSubservicios').classList.add('hidden');
                            }
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

                } else if (e.target.classList.contains('btn-eliminar-cargo')) {
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
                            axios.post(`${VITE_URL_APP}/api/delete-cargo`, {
                                codigo: codigo,
                            }).then(function (response) {
                                if (response.status == '200') {
                                    cargarCargos();
                                } else {
                                    console.log('problema al actualizar');
                                }
                            })
                                .catch(function (error) {
                                    console.error('Error al actualizar:', error);
                                });
                        }
                    });





                } else if (e.target.classList.contains('btn-activar-cargo')) {
                    const codigo = cell.getRow().getData().codigo;
                    axios.post(`${VITE_URL_APP}/api/activar-cargo`, {
                        codigo: codigo,
                    }).then(function (response) {
                        if (response.status == '200') {
                            cargarCargos();
                        } else {
                            console.log('problema al actualizar');
                        }
                    })
                        .catch(function (error) {
                            console.error('Error al actualizar:', error);
                        });

                }
            },

        },
    ],
    rowFormatter: function (row) {
        let data = row.getData();

        if (data.habilitado != "1") {
            row.getElement().style.backgroundColor = "#ffe9e9";
        }
    }
});

document.getElementById("page-size").addEventListener("change", function () {
    const size = parseInt(this.value);
    tblCargos.setPageSize(size);
});

// window.aplicarFiltroEliminarCargo = (op) => {
//     if (op === 0) { tblCargos.setFilter("habilitado", "=", "0"); } else {
//         tblCargos.clearFilter();
//     }

// }

// window.aplicarFiltroSoloActivos = (op) => {
//     if (op === 1) { tblCargos.setFilter("habilitado", "=", "1"); } else {
//         tblCargos.clearFilter();
//     }

// }


  function aplicarTodosFiltrosCargo() {
      const filtros = [];

      const tipo = document.querySelector('input[name="cargoFiltro"]:checked')?.value;
      if (tipo && tipo !== 'TODOS') {
          filtros.push({ field: 'tipo', type: '=', value: tipo });
      }

      if (document.getElementById('chkEliminados').checked) {
          filtros.push({ field: 'habilitado', type: '=', value: '1' });
      }

      const area = document.getElementById('filtroArea').value;
      if (area) {
          filtros.push({ field: 'area', type: '=', value: area });
      }

      const buscar = document.getElementById('buscarCargo').value.toLowerCase().trim();
      if (buscar) {
          filtros.push([
              { field: 'nombre',      type: 'like', value: buscar },
              { field: 'area',        type: 'like', value: buscar },
              { field: 'descripcion', type: 'like', value: buscar },
          ]);
      }

      if (filtros.length > 0) {
          tblCargos.setFilter(filtros);
      } else {
          tblCargos.clearFilter();
      }
  }

  window.aplicarFiltroSoloActivos = () => aplicarTodosFiltrosCargo();
  window.aplicarFiltroEliminarCargo = () => aplicarTodosFiltrosCargo();

// Función para CANCELAR
document.getElementById("cancelButton").addEventListener("click", function () {
    limpiarForm();
});

document.querySelector('.clean-btn').addEventListener('click', limpiarForm);

// Verificar nombre duplicado mientras escribe
let checkNombreTimeout = null;
document.getElementById('nombre').addEventListener('input', function () {
    clearTimeout(checkNombreTimeout);
    const aviso = document.getElementById('avisoNombreRepetido');
    const valor = this.value.trim();

    if (!valor) {
        aviso.classList.add('hidden');
        return;
    }

    checkNombreTimeout = setTimeout(() => {
        const excluir = document.getElementById('codigoEditar').value;
        const params = new URLSearchParams({ nombre: valor });
        if (excluir && excluir !== '0') params.append('excluir', excluir);

        axios.get(`${VITE_URL_APP}/api/check-cargo-nombre?` + params.toString())
            .then(response => {
                const btn = document.getElementById('btnRegistrarCargo');
                if (response.data.existe) {
                    aviso.classList.remove('hidden');
                    btn.disabled = true;
                    btn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    aviso.classList.add('hidden');
                    btn.disabled = false;
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            })
            .catch(() => {
                aviso.classList.add('hidden');
                const btn = document.getElementById('btnRegistrarCargo');
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            });
    }, 500);
});

// Función para BUSCAR
document.getElementById("buscarCargo").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim(); // Convertir a minúsculas

    tblCargos.setFilter([
        [
            { field: "area", type: 'like', value: valor },
            { field: "descripcion", type: 'like', value: valor },
            { field: "nombre", type: 'like', value: valor },
        ]
    ]);
});

// Función para actualizar la tabla con los filtros
function filterTableByTipoCargo() {
      aplicarTodosFiltrosCargo();
  }

  document.querySelectorAll('input[name="cargoFiltro"]').forEach(radio => {
      radio.addEventListener('change', filterTableByTipoCargo);
  });

  document.getElementById('filtroArea').addEventListener('change', aplicarTodosFiltrosCargo);

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
          tblCargos.setData(response.data);
      })
      .catch(error => {
          console.error("Hubo un error:", error);
      });
  }

function cargarCounters() {
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

 function cargarAreas() {
      axios.get(`${VITE_URL_APP}/api/get-areas`)
          .then(response => {
              const datos = response.data;
              const selectForm   = document.getElementById("slcArea");
              const selectFiltro = document.getElementById("filtroArea");

              datos.forEach(item => {
                  const optForm = document.createElement("option");
                  optForm.value = item.CODI_TIPO_CARG;
                  optForm.textContent = item.DESC_TIPO_CARG;
                  selectForm.appendChild(optForm);

                  const optFiltro = document.createElement("option");
                  optFiltro.value = item.DESC_TIPO_CARG;
                  optFiltro.textContent = item.DESC_TIPO_CARG;
                  selectFiltro.appendChild(optFiltro);
              });
          })
          .catch(error => {
              console.error("Hubo un error:", error);
          });
  }


document.getElementById('slcPosicion').addEventListener('change', (e) => {
    document.getElementById('divSubservicios').classList.remove("hidden");
    listarSubServicios(e.target.value);
});

//listarSubServicios(1);
function listarSubServicios(codigo) {
    return axios.get(`${VITE_URL_APP}/api/get-grupo/` + codigo)
        .then(response => {
            const datos = response.data;
            const select = document.getElementById("slcGrupo");
            select.innerHTML = '';
            datos.forEach(item => {
                const option = document.createElement("option");
                option.value = item.TIPP_CODIGO;
                option.textContent = item.TIPP_DESCRIPCION;
                select.appendChild(option);
            });
        })
        .catch(error => {
            console.error("Hubo un error:", error);
        });
}

function cargarPosicion() {
    axios.get(`${VITE_URL_APP}/api/get-posicion`)
        .then(response => {
            console.log('posicion', response);
            const datos = response.data;  // Suponiendo que devuelve un array de objetos
            const select = document.getElementById("slcPosicion");

            // Limpiar opciones anteriores (excepto la primera)
            //select.innerHTML = '<option value="">Seleccione una opción</option>';

            // Recorrer los datos y agregarlos como opciones
            datos.forEach(item => {
                if (item.POSI_DESCRIPCION != '<TODOS>') {
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
document.getElementById('formSaveCargo').addEventListener('submit', function (event) {
    event.preventDefault();
    const rdTipoCargo = document.querySelector('input[name="rdTipoCargo"]:checked').value;
    const slcArea = document.getElementById('slcArea').value;
    const nombre = document.getElementById('nombre').value;
    const txtDescripcion = document.getElementById('txtDescripcion').value;
    const txtAbreviatura = document.getElementById('txtAbreviatura').value;
    const slcPosicion = document.getElementById('slcPosicion').value;
    const slcGrupo = document.getElementById('slcGrupo').value;
    const tipoRegistro = document.getElementById('codigoEditar').value;

    if (!nombre) {
        Swal.fire({ title: 'El nombre es obligatorio', icon: 'warning' });
        return;
    }

    if (tipoRegistro == '0') {
        axios.post(`${VITE_URL_APP}/save_cargo`, {
            tipoCargo: rdTipoCargo,
            codArea: slcArea,
            nombre: nombre,
            descripcion: txtDescripcion,
            abreviatura: txtAbreviatura,
            codPosicion: slcPosicion,
            codGrupo: slcGrupo
        })
            .then(function (response) {
                Swal.fire({ title: 'Registro exitoso', icon: 'success', timer: 2000, showConfirmButton: false });
                limpiarForm();
                cargarCounters();
                cargarCargos();
            })
            .catch(function (error) {
                Swal.fire({ title: 'Error al registrar', icon: 'error' });
                console.error(error);
            });

    } else {
        axios.post(`${VITE_URL_APP}/api/update_cargo`, {
            tipoCargo: rdTipoCargo,
            codArea: slcArea,
            nombre: nombre,
            descripcion: txtDescripcion,
            abreviatura: txtAbreviatura,
            codPosicion: slcPosicion,
            codGrupo: slcGrupo,
            codigo: tipoRegistro,
        })
            .then(function (response) {
                Swal.fire({ title: 'Cargo modificado', icon: 'success', timer: 2000, showConfirmButton: false });
                limpiarForm();
                cargarCounters();
                cargarCargos();
            })
            .catch(function (error) {
                Swal.fire({ title: 'Error al actualizar', icon: 'error' });
                console.error(error);
            });
    }
});




function activarCargo(codigo) {
    axios.post(`${VITE_URL_APP}/api/activar-cargo`, {
        codigo: codigo,
    }).then(function (response) {
        if (response.status == '200') {
            cargarCargos();
        } else {
            console.log('problema al actualizar');
        }
    })
        .catch(function (error) {
            console.error('Error al actualizar:', error);
        });
}