import Swal from "sweetalert2";
import axios from "axios";
import DataTable from "vanilla-datatables";
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

let cursosData = [];
let tblPersonalMatricula = null;
let cursoActual = null;
let personasSeleccionadas = new Set();

document.addEventListener('DOMContentLoaded', async () => {
  
  await listarTipoCurso("slcFiltroTipoCurso", true)
  await listarAreas("slcFiltroArea", true)
  await listarCursos()

  new DataTable(document.getElementById('tblCursos'), {
    perPage: 10,
    searchable: true,
    sortable: true,
    labels: {
      placeholder: "Buscar...",
      perPage: "{select} por página",
      noRows: "No hay datos disponibles",
      info: "Mostrando {start} a {end} de {rows}"
    }
  });


  document.querySelectorAll(".btn-matricula").forEach(btn => {
    btn.addEventListener("click", async (e) => {
        const cursoId = e.target.dataset.cursoId;
        const cursoNombre = e.target.dataset.cursoNombre || "Curso seleccionado";
        
        cursoActual = cursoId;
        
        document.getElementById("nombreCurso").textContent = cursoNombre;
        
        await cargarPersonal(cursoId);
        
        HSOverlay.open('#modal-registro');
    });
});


document.getElementById("btnGuardarMatricula").addEventListener("click", async function() {
    console.log("🔴 personasSeleccionadas antes de convertir:", personasSeleccionadas);
    console.log("🔴 Tipo:", typeof personasSeleccionadas);
    console.log("🔴 Es Set?:", personasSeleccionadas instanceof Set);
    
    const seleccionados = Array.from(personasSeleccionadas);
    
    console.log("📋 Total a matricular:", seleccionados.length);
    console.log("👥 IDs:", seleccionados);
    
    if (seleccionados.length === 0) {
        alert("Por favor, seleccione al menos una persona para matricular");
        return;
    }
    
    this.disabled = true;
    this.innerHTML = '<i class="i-tabler-loader animate-spin mr-2"></i> Procesando matrícula...';
    
    try {

        const response = await axios.post(`${VITE_URL_APP}/api/save-matricula`, {
            cursoId: cursoActual,
            personalIds: seleccionados
        });
        
        if (response.status === 200 || response.status === 201) {
            alert(`✓ ${seleccionados.length} persona(s) matriculada(s) exitosamente.`);
            personasSeleccionadas.clear();
            HSOverlay.close('#modal-registro');
        }



        //Simulación
        // setTimeout(() => {
        //     alert(`${seleccionados.length} persona(s) matriculada(s) exitosamente`);
        //     personasSeleccionadas.clear();
        //     HSOverlay.close('#modal-registro');
        //     this.disabled = false;
        //     this.innerHTML = '<i class="i-tabler-check mr-2"></i> Matricular Seleccionados';
        // }, 1000);
        
    } catch (error) {
        console.error("Error al matricular:", error);
        alert(error.response?.data?.message || "Ocurrió un error al matricular el personal");
    } finally {
        this.disabled = false;
        this.innerHTML = '<i class="i-tabler-check mr-2"></i> Matricular Seleccionados';
    }
});


})


window.listarCursos = async function(habilitado = 1, area = '', tipoCurso = '') {
  console.log('listarCursos called', { habilitado, area, tipoCurso });
  try {
    const res = await axios.get(`${VITE_URL_APP}/api/get-cursos/${habilitado}`, {
      params: { area, tipoCurso }
    });
    cursosData = res.data;
    renderTablaCursos(cursosData);
  } catch (err) {
    console.error("Error al obtener cursos", err);
    Swal.fire("Error", "No se pudieron cargar los cursos", "error");
  }
}



async function listarTipoCurso(selectId, esFiltro = false) {
  try {
    const res = await axios.get(`${VITE_URL_APP}/api/get-capacitacion-tipo-cursos`);
    const tipoCursosData = res.data;
    const select = document.getElementById(selectId);

    select.innerHTML = esFiltro 
      ? '<option value="">-- Todos --</option>' 
      : '<option value="">-- Seleccione --</option>';

    tipoCursosData.forEach(curso => {
      const option = document.createElement("option");
      option.value = curso.codigo;
      option.textContent = curso.descripcion;
      select.appendChild(option);
    });
  } catch (err) {
    console.error("Error al obtener tipos de cursos", err);
    Swal.fire("Error", "No se pudieron cargar los tipos de cursos", "error");
  }
}


async function listarAreas(selectId, esFiltro = false) {
  try {
    const res = await axios.get(`${VITE_URL_APP}/api/get-capacitacion-areas`);
    const areasData = Array.isArray(res.data) ? res.data : [];

    const select = document.getElementById(selectId);

    select.innerHTML = esFiltro
      ? '<option value="">-- Todas --</option>'
      : '<option value="">-- Seleccione --</option>';

    if (areasData.length === 0) {
      console.warn("No hay áreas disponibles");
    } else {
      areasData.forEach(area => {
        const option = document.createElement("option");
        option.value = area.codigo;
        option.textContent = area.descripcion;
        select.appendChild(option);
      });
    }
  } catch (err) {
    console.error("Error al obtener las áreas", err);
    Swal.fire("Error", "No se pudieron cargar las áreas", "error");
  }
}

function renderTablaCursos(data) {
  const tbody = document.querySelector("#tblCursos tbody")
  if (!tbody) return

  tbody.innerHTML = ""

  if(data.length > 0){
    data.forEach((curso, index) => {
        const tr = document.createElement("tr")
        tr.style.backgroundColor = curso.habilitado == '1' ? "" : '#fff1f1';

        //SE OCULTO EL CAMPO DE PERIODO
        tr.innerHTML = `
        <td>${index + 1}</td>
         <td>${curso.codigoCurso}</td>
        <td>${curso.nombre}</td>
        <td>
          ${curso.habilitado == '1'
            ? `<button type="button" 
                class="btn-matricula btn rounded-full form-btn bg-success/25 text-success hover:bg-success hover:text-white"
                data-curso-id="${curso.codigo}" data-curso-nombre="${curso.nombre}">Matricular</button>
                `
            : `<span class="text-gray-400 italic">No disponible</span>`
          }
        </td>
        `
        tbody.appendChild(tr)
    })

    inicializarBotonesMatricula();

  }else{

    const tr = document.createElement("tr")
    tr.innerHTML = `
      <td colspan="4" class="text-center text-gray-500 py-4">
        Sin registros
      </td>
    `
    tbody.appendChild(tr)
    return
  }
}


function inicializarBotonesMatricula() {
    document.querySelectorAll(".btn-matricula").forEach(btn => {
        const nuevoBtn = btn.cloneNode(true);
        btn.parentNode.replaceChild(nuevoBtn, btn);
    });
    
    document.querySelectorAll(".btn-matricula").forEach(btn => {
        btn.addEventListener("click", async (e) => {
            const cursoId = e.target.dataset.cursoId;
            const cursoNombre = e.target.dataset.cursoNombre || "Curso seleccionado";
            
            console.log("🎯 Click en matricular - Curso ID:", cursoId);
            
            cursoActual = cursoId;
            
            const nombreCursoElement = document.getElementById("nombreCurso");
            if (nombreCursoElement) {
                nombreCursoElement.textContent = cursoNombre;
            }
            
            await cargarPersonal(cursoId);
            HSOverlay.open('#modal-registro');
        });
    });
}


async function cargarPersonal(cursoId) {
    try {
        // Limpiar selecciones anteriores
        personasSeleccionadas.clear();
        
        const response = await axios.get(`${VITE_URL_APP}/api/get-personal`);
        const personal = response.data;

        if (tblPersonalMatricula) {
            tblPersonalMatricula.destroy();
        }

        tblPersonalMatricula = new Tabulator("#tblPersonalMatricula", {
            data: personal,
            height: "100%",
            layout: "fitColumns",
            responsiveLayout: "collapse",
            pagination: true,
            paginationSize: 10,
            paginationSizeSelector: [5, 10, 20, 50],
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
                        "page_size": "Registros por página"
                    }
                }
            },
            rowHeader: {
                formatter: "responsiveCollapse",
                width: 30,
                minWidth: 30,
                hozAlign: "center",
                resizable: false,
                headerSort: false
            },
            columns: [
                {
                    title: "Seleccionar",
                    field: "seleccionar",
                    width: 100,
                    hozAlign: "center",
                    headerHozAlign: "center",
                    formatter: function(cell) {
                        const data = cell.getRow().getData();
                        const codiPers = data.CODI_PERS;
                        
                        // Verificar si ya está seleccionado
                        const isChecked = personasSeleccionadas.has(codiPers) ? 'checked' : '';
                        
                        return `<input type="checkbox" 
                                    class="checkbox-personal form-checkbox h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500" 
                                    ${isChecked}
                                    data-codi-pers="${codiPers}">`;
                    },
                    headerSort: false
                },
                {
                    title: "Nombre Completo",
                    field: "personal",
                    minWidth: 200,
                    responsive: 0,
                    formatter: function(cell) {
                        return `<div class="font-medium text-gray-900">${cell.getValue()}</div>`;
                    }
                },
                {
                    title: "DNI",
                    field: "nroDoc",
                    width: 120,
                    responsive: 1,
                    hozAlign: "center",
                    headerHozAlign: "center"
                },
                {
                    title: "Sucursal",
                    field: "sucursal",
                    width: 150,
                    responsive: 2,
                    formatter: function(cell) {
                        return `<span class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded">
                                    ${cell.getValue()}
                                </span>`;
                    }
                }
            ]
        });

        // Esperar a que la tabla esté construida
        tblPersonalMatricula.on("tableBuilt", function() {
            actualizarContadores(personal);
            configurarBuscador();
        });

        configurarEventosCheckboxes();

    } catch (error) {
        console.error("Error al cargar el personal:", error);
        alert("No se pudo cargar la lista de personal. Por favor, intente nuevamente.");
    }
}

function configurarEventosCheckboxes() {
    const contenedorTabla = document.getElementById("tblPersonalMatricula");
    
    if (!contenedorTabla) return;
    
    // Remover listeners anteriores usando una bandera
    if (!contenedorTabla.dataset.listenerAgregado) {
        contenedorTabla.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('checkbox-personal')) {
                const codiPers = e.target.dataset.codiPers;
                
                console.log("Checkbox cambiado:", codiPers, "Checked:", e.target.checked);
                
                if (e.target.checked) {
                    personasSeleccionadas.add(codiPers);
                    console.log("✓ Agregado:", codiPers);
                } else {
                    personasSeleccionadas.delete(codiPers);
                    console.log("✗ Removido:", codiPers);
                }
                
                console.log("📋 Total seleccionados:", personasSeleccionadas.size);
                console.log("👥 Array:", Array.from(personasSeleccionadas));
                
                actualizarContadorSeleccionados();
            }
        });
        
        // Marcar que ya se agregó el listener
        contenedorTabla.dataset.listenerAgregado = 'true';
    }
}

function actualizarContadorSeleccionados() {
    const seleccionados = personasSeleccionadas.size;
    
    const contadorElement = document.getElementById("countSeleccionados");
    if (contadorElement) {
        contadorElement.textContent = seleccionados;
    }
    
    const mensajeElement = document.getElementById("mensajeSeleccion");
    if (mensajeElement) {
        const mensaje = seleccionados > 0 
            ? `${seleccionados} persona${seleccionados > 1 ? 's' : ''} seleccionada${seleccionados > 1 ? 's' : ''} para matricular`
            : "Seleccione el personal a matricular";
        
        mensajeElement.textContent = mensaje;
    }
}

function configurarBuscador() {
    const inputBuscar = document.getElementById("buscarPersonal");
    
    if (!inputBuscar) {
        console.error("No se encontró el input de búsqueda");
        return;
    }
    
    // Limpiar valor
    inputBuscar.value = "";
    
    // Si ya tiene listener, no agregar otro
    if (inputBuscar.dataset.listenerAgregado) {
        return;
    }
    
    inputBuscar.addEventListener("keyup", function() {
        const filtro = this.value.trim().toLowerCase();
        
        console.log("🔍 Buscando:", filtro);
        
        if (filtro === "") {
            tblPersonalMatricula.clearFilter();
        } else {
            tblPersonalMatricula.setFilter(function(data){
                const personal = (data.personal || "").toLowerCase();
                const nroDoc = (data.nroDoc || "").toLowerCase();
                const sucursal = (data.sucursal || "").toLowerCase();
                
                return personal.includes(filtro) || 
                       nroDoc.includes(filtro) || 
                       sucursal.includes(filtro);
            });
        }
    });
    
    // Marcar que ya tiene listener
    inputBuscar.dataset.listenerAgregado = 'true';
}

function actualizarContadores(personal) {
    const matriculados = personal.filter(p => p.matriculado).length;
    const disponibles = personal.filter(p => !p.matriculado).length;
    
    const elemMatriculados = document.getElementById("countMatriculados");
    const elemDisponibles = document.getElementById("countDisponibles");
    
    if (elemMatriculados) elemMatriculados.textContent = matriculados;
    if (elemDisponibles) elemDisponibles.textContent = disponibles;
    
    document.getElementById("countSeleccionados").textContent = "0";
}