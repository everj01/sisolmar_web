import Swal from "sweetalert2";
import axios from "axios";
import DataTable from "vanilla-datatables";

let cursosData = [];

const archivoInput = document.getElementById("archivoInput");
const btnSeleccionar = document.getElementById("btnSeleccionar");
const listaArchivos = document.getElementById("listaArchivos");
const btnAnalizar = document.getElementById("btnAnalizar");
const resumenPlantilla = document.getElementById("resumenPlantilla");

let archivoSeleccionado = null;

if (btnSeleccionar) {
    btnSeleccionar.addEventListener("click", () => {
        if (archivoInput) archivoInput.click();
    });
}


if (archivoInput) {
    archivoInput.addEventListener("change", (e) => {
        const archivo = e.target.files[0]; // Solo el primero

        if (!archivo) return;

        // Validar peso (1MB máx)
        if (archivo.size > 1024 * 1024) {
            alert(`El archivo "${archivo.name}" supera 1 MB y fue omitido.`);
            archivoInput.value = "";
            return;
        }

        // Validar extensión
        const ext = archivo.name.split('.').pop().toLowerCase();
        if (!["mbz"].includes(ext)) {
            alert(`Solo se permiten archivos .mbz`);
            archivoInput.value = "";
            archivoSeleccionado = null;
            btnAnalizar.disabled = true;
            return;
        }

        archivoSeleccionado = archivo;

        actualizarLista();
        if (btnAnalizar) btnAnalizar.disabled = false;
        archivoInput.value = "";
    });
}

if (btnAnalizar) {
    btnAnalizar.addEventListener("click", async () => {
        if (!archivoSeleccionado) return;

        const formData = new FormData();
        formData.append("plantilla", archivoSeleccionado);

        try {
            btnAnalizar.disabled = true;
            btnAnalizar.textContent = "Analizando...";

            // FIX: No especificar Content-Type manualmente
            // Axios lo configura automáticamente para FormData con el boundary correcto
            const res = await axios.post(
                `${VITE_URL_APP}/api/cursos/analizar-plantilla`,
                formData
            );

            const data = res.data;
            console.log("Respuesta backend:", data);

            if (!data.success) {
                resumenPlantilla.innerHTML = `<p class="text-red-600">${data.message}</p>`;
                return;
            }

            // construir HTML agradable
            let actividadesHtml = "";
            for (const [tipo, cantidad] of Object.entries(data.activityStats)) {
                actividadesHtml += `
              <div class="flex justify-between border-b py-1">
                  <span class="capitalize">${tipo}</span>
                  <span class="font-semibold">${cantidad}</span>
              </div>
          `;
            }

            resumenPlantilla.innerHTML = `
          <div class="bg-white shadow rounded-lg p-4 space-y-4">
              <h3 class="text-lg font-bold text-gray-700">📘 Resumen de la Plantilla</h3>

              <div>
                  <p><span class="font-semibold">Nombre del curso:</span> ${data.courseName}</p>
                  <p><span class="font-semibold">Código corto:</span> ${data.courseShortname}</p>
                  <p><span class="font-semibold">Versión Moodle:</span> ${data.moodleVersion}</p>
                  <p><span class="font-semibold">Fecha backup:</span> ${new Date(data.backupDate * 1000).toLocaleString()}</p>
              </div>

              <div class="grid grid-cols-2 gap-4 text-center">
                  <div class="bg-blue-50 rounded p-3">
                      <p class="text-2xl font-bold text-blue-700">${data.totalSections}</p>
                      <p class="text-gray-600">Secciones</p>
                  </div>
                  <div class="bg-green-50 rounded p-3">
                      <p class="text-2xl font-bold text-green-700">${data.totalActivities}</p>
                      <p class="text-gray-600">Actividades</p>
                  </div>
                  <div class="bg-purple-50 rounded p-3 col-span-2">
                      <p class="text-2xl font-bold text-purple-700">${data.totalQuestions}</p>
                      <p class="text-gray-600">Preguntas</p>
                  </div>
              </div>

              <div>
                  <h4 class="text-md font-semibold text-gray-700 mb-2">📊 Actividades por tipo</h4>
                  <div class="bg-gray-50 rounded p-2">
                      ${actividadesHtml}
                  </div>
              </div>
          </div>
      `;
        } catch (err) {
            console.error("Error al analizar plantilla:", err);

            // Mostrar mensaje de error más específico
            let mensajeError = "Error al analizar plantilla";
            if (err.response?.data?.message) {
                mensajeError = err.response.data.message;
            } else if (err.response?.status === 400) {
                mensajeError = "Archivo inválido o no se pudo procesar";
            } else if (err.response?.status === 413) {
                mensajeError = "El archivo es demasiado grande";
            }

            resumenPlantilla.innerHTML = `<p class="text-red-600">${mensajeError}</p>`;
        } finally {
            btnAnalizar.disabled = false;
            btnAnalizar.textContent = "Analizar Plantilla";
        }
    });

}

// Actualiza la lista en pantalla
function actualizarLista() {
    listaArchivos.innerHTML = "";

    if (archivoSeleccionado) {
        const item = document.createElement("li");
        item.className =
            "flex items-center justify-between bg-gray-100 p-2 rounded";

        item.innerHTML = `
            <span class="text-sm text-gray-800">${archivoSeleccionado.name}</span>
            <div class="flex items-center gap-x-2">
                <button type="button" class="text-red-500 hover:text-red-700 text-xs" id="btnQuitar">
                    <i class="i-tabler-trash size-4 shrink-0" style="margin-bottom: -4px;"></i> Quitar
                </button>
            </div>
        `;

        listaArchivos.appendChild(item);

        // Quitar archivo
        const btnQuitar = document.getElementById("btnQuitar");
        if (btnQuitar) {
            btnQuitar.addEventListener("click", () => {
                archivoSeleccionado = null;
                actualizarLista();
                resumenPlantilla.innerHTML = "";
                btnAnalizar.disabled = true;
            });
        }
    }
}

document.addEventListener('DOMContentLoaded', async () => {

    await listarTipoCurso("slcTipoCurso")
    await listarTipoCurso("slcFiltroTipoCurso", true)
    // await listarAreas("slcArea") // Removed: Replaced by Alpine component
    await listarAreas("slcFiltroArea", true)
    await listarCursos()

    // DataTable will be initialized inside renderTablaCursos
})


window.listarCursos = async function (habilitado = 1, area = '', tipoCurso = '') {
    try {
        const res = await axios.get(`${VITE_URL_APP}/api/get-cursos/${habilitado}`, {
            params: { filtro_area: area, filtro_tipo: tipoCurso }
        });

        cursosData = res.data;
        renderTablaCursos(cursosData);
    } catch (err) {
        console.error("Error al obtener cursos", err);
        Swal.fire("Error", "No se pudieron cargar los cursos", "error");
    }
}


window.opcionesTipoCurso = []; // Global

async function listarTipoCurso(selectId, esFiltro = false) {
    try {
        const res = await axios.get(`${VITE_URL_APP}/api/get-capacitacion-tipo-cursos`);
        const tipoCursosData = res.data;

        // Update global state for Alpine
        window.opcionesTipoCurso = Array.isArray(tipoCursosData) ? tipoCursosData : [];
        window.dispatchEvent(new CustomEvent('tipo-curso-loaded', { detail: window.opcionesTipoCurso }));

        // Legacy DOM manipulation: Only for elements NOT controlled by Alpine loop (or filters)
        // 'slcTipoCurso' and 'slcFiltroTipoCurso' are migrated to Alpine x-for.
        if (selectId !== 'slcTipoCurso' && selectId !== 'slcFiltroTipoCurso') {
            const select = document.getElementById(selectId);
            if (select) {
                select.innerHTML = esFiltro
                    ? '<option value="">-- Todos --</option>'
                    : '<option value="">-- Seleccione --</option>';

                window.opcionesTipoCurso.forEach(curso => {
                    const option = document.createElement("option");
                    option.value = curso.codigo;
                    option.textContent = curso.descripcion;
                    select.appendChild(option);
                });
            }
        }
    } catch (err) {
        console.error("Error al obtener tipos de cursos", err);
        Swal.fire("Error", "No se pudieron cargar los tipos de cursos", "error");
    }
}

window.opcionesArea = []; // Global initialization

async function listarAreas(selectId = null, esFiltro = false) {
    try {
        const res = await axios.get(`${VITE_URL_APP}/api/get-capacitacion-areas`);
        const areasData = Array.isArray(res.data) ? res.data : [];

        // Populate global array for Alpine.js
        window.opcionesArea = areasData.map(area => ({
            codigo: area.codigo,
            descripcion: area.descripcion
        }));

        // Dispatch event for Alpine components
        window.dispatchEvent(new CustomEvent('areas-loaded', { detail: window.opcionesArea }));

        // Legacy support (optional, can be removed if specific selects are fully replaced)
        // Only try to update older selects if selectId is provided AND element exists
        if (selectId) {
            const select = document.getElementById(selectId);
            if (select) {
                select.innerHTML = esFiltro
                    ? '<option value="">-- Todas --</option>'
                    : '<option value="">-- Seleccione --</option>';

                if (areasData.length > 0) {
                    areasData.forEach(area => {
                        const option = document.createElement("option");
                        option.value = area.codigo;
                        option.textContent = area.descripcion;
                        select.appendChild(option);
                    });
                }
            }
        }

    } catch (err) {
        console.error("Error al obtener las áreas", err);
        Swal.fire("Error", "No se pudieron cargar las áreas", "error");
    }
}

async function listarCursosFiltro() {
    try {
        const res = await axios.get(`${VITE_URL_APP}/api/get-cursos/0`)
        cursosData = res.data
        renderTablaCursos(cursosData)
    } catch (err) {
        console.error("Error al obtener cursos", err)
        Swal.fire("Error", "No se pudieron cargar los cursos", "error")
    }
}

async function obtenerCursoXId(id) {
    try {
        const res = await axios.get(`${VITE_URL_APP}/api/get-curso-id/${id}`)
        return res.data;
    } catch (err) {
        console.error("Error al obtener cursos", err)
        Swal.fire("Error", "No se pudieron cargar los cursos", "error");
        return false;
    }
}

// Global variable to store DataTable instance
let cursoTable = null;

function renderTablaCursos(data) {
    // Destroy existing DataTable instance
    if (cursoTable) {
        cursoTable.destroy();
        cursoTable = null;
    }

    const tblCursos = document.getElementById('tblCursos');
    if (!tblCursos) return;

    // Get the container and completely remove the old table
    const container = tblCursos.parentElement;
    tblCursos.remove();

    // Create a completely fresh table element
    const newTable = document.createElement('table');
    newTable.id = 'tblCursos';
    newTable.className = 'table table-bordered table-hover';

    // Create thead
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr>
            <th>#</th>
            <th>CÓDIGO</th>
            <th>NOMBRE</th>
            <th>ACCIONES</th>
        </tr>
    `;
    newTable.appendChild(thead);

    // Create tbody with data
    const tbody = document.createElement('tbody');

    if (data.length > 0) {
        data.forEach((curso, index) => {
            const tr = document.createElement("tr");
            tr.style.backgroundColor = curso.habilitado == '1' ? "" : '#fff1f1';

            tr.innerHTML = `
        <td>${index + 1}</td>
         <td>${curso.codigoCurso}</td>
        <td>${curso.nombre}</td>
        <td>
            <button type="button" @click="gestionCurso('EDIT', '${curso.codigo}', '${curso.nombre.replace(/'/g, "\\'")}')"
            class="me-2 btn rounded-full bg-info/25 text-info hover:bg-info hover:text-white" title="Editar curso">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>

            <button type="button" @click="activarPanelProgramacion('${curso.codigo}', '${curso.nombre.replace(/'/g, "\\'")}', '${curso.frecuencia || ''}')"
            class="me-2 btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white" title="Ver programaciones">
                <i class="bx bx-calendar-event"></i>
            </button>

            ${curso.habilitado == '1' ?
                    `<button type="button"  @click="gestionCurso('DEL', '${curso.codigo}', '${curso.nombre.replace(/'/g, "\\'")}')"
                class="btn rounded-full bg-danger/25 text-danger hover:bg-danger hover:text-white" title="Deshabilitar curso">
                    <i class="fa-solid fa-trash-can"></i>
                </button>`
                    :
                    `<button type="button"  @click="gestionCurso('ACT', '${curso.codigo}', '${curso.nombre.replace(/'/g, "\\'")}')"
                class="me-2 btn rounded-full  bg-success/25 text-success hover:bg-success hover:text-white" title="Habilitar curso">
                    <i class='bx bx-check' ></i>
                </button>
                <button type="button"  @click="gestionCurso('PERMA_DEL', '${curso.codigo}', '${curso.nombre.replace(/'/g, "\\'")}')"
                class="btn rounded-full bg-danger/25 text-danger hover:bg-danger hover:text-white" title="Eliminar definitivamente de BD">
                    <i class="fa-solid fa-trash"></i>
                </button>`
                }
        </td>
        `;
            tbody.appendChild(tr);
        });
    } else {
        const tr = document.createElement("tr");
        tr.innerHTML = `
      <td colspan="4" class="text-center text-gray-500 py-4">
        No hay datos disponibles
      </td>`;
        tbody.appendChild(tr);
    }

    newTable.appendChild(tbody);
    container.appendChild(newTable);

    // Initialize DataTables on the completely fresh table
    cursoTable = new DataTable(newTable, {
        perPage: 10,
        perPageSelect: [10, 15, 20, 25],
        searchable: true,
        sortable: true,
        fixedHeight: false,
        labels: {
            placeholder: "Buscar...",
            perPage: "{select} por página",
            noRows: "No hay registros",
            info: "Mostrando {start} a {end} de {rows}"
        }
    });
}


window.gestionCurso = async (op, cod, nombre = '') => {
    if (op === 'EDIT') {
        const dataget = await obtenerCursoXId(cod);
        // ... (resto del código de edición sin cambios)


        if (dataget && dataget.success && dataget.curso) {
            const curso = dataget.curso;
            const mensaje = document.getElementById('txtMensajeNuevo');
            const view = document.getElementById('viewEditCreate');
            const btn = document.getElementById('btnGestion');
            const btnEdit = document.getElementById('btnGestionEditar');
            const title = document.getElementById('txtTitleFile');
            const btnDownload = document.getElementById('btnDownloadPlantilla');

            mensaje.textContent = 'Editar';
            title.textContent = 'Actualizar plantilla';

            mensaje.classList.remove('bg-primary/25', 'text-primary-800');
            mensaje.classList.add('bg-warning/25', 'text-warning-800');

            view.classList.remove('hidden');
            btnDownload.classList.remove('hidden');

            document.getElementById('codGestionEditar').value = curso.codigo;

            // Populate Alpine.js data directly
            const formElement = document.querySelector('[x-data="formCursoGestion()"]');
            if (formElement && window.Alpine) {
                const alpineData = Alpine.$data(formElement);

                // Sincronizar campos principales con Alpine
                alpineData.nombre = curso.nombre;
                alpineData.tipoCurso = curso.tipo_curso?.codigo ?? "";
                alpineData.area = curso.area ?? "";

                // Nuevos campos de periodicidad estructurada
                alpineData.activarPeriodicidad = (curso.es_periodico == 1);
                alpineData.frecuencia = curso.frecuencia ?? '';
                alpineData.proyeccionAnios = curso.proyeccion_anios ?? 1;
                alpineData.mesInicio = '';

                // Update PAC logic
                const esPacCurso = curso.tipo_curso?.descripcion?.toUpperCase().includes('PAC') || false;
                alpineData.esPAC = esPacCurso;
                alpineData.sucursalesAsignadas = curso.sucursales || [];

                // Datos de Examen (Sincronizar con Alpine)
                alpineData.limiteTiempo = curso.examen?.tiempo ?? 0;
                alpineData.nota = curso.examen?.nota_minima ?? 0;
                alpineData.intentos = curso.examen?.intentos ?? 0;
            }

            // Se eliminaron asignaciones manuales por ID para usar x-model de Alpine

            // Lógica para el botón de descarga de plantilla
            if (curso.examen && curso.examen.file_tiene == 1 && curso.examen.file_ruta) {
                btnDownload.href = `${VITE_URL_APP}/storage/${curso.examen.file_ruta}`;
                btnDownload.setAttribute('download', curso.examen.file_nombre || 'plantilla');
                // Mostrar nombre original si existe, sino 'plantilla'
                const nombreMostrar = curso.examen.file_nombre_original || curso.examen.file_nombre || 'plantilla';
                btnDownload.innerHTML = `<i class='bx bxs-cloud-download'></i>&nbsp;Descargar ${nombreMostrar}`;
                btnDownload.classList.remove('hidden');
                btnDownload.target = "_blank";
            } else {
                btnDownload.classList.add('hidden');
                btnDownload.href = '#';
            }

            btn.classList.add('hidden');
            btnEdit.classList.remove('hidden');

        } else {
            Swal.fire('Advertencia', 'No se encontró el curso', 'warning');
        }

    } else {
        const data = {
            habilitado: op === 'DEL' ? 0 : 1
        };

        const titulo = op === 'DEL' ? '¿Estás seguro?' : (op === 'PERMA_DEL' ? '¿Eliminación Definitiva?' : '¿Habilitar curso?');
        const texto = op === 'DEL' ? `¿Quieres inhabilitar el curso "${nombre}"?` : (op === 'PERMA_DEL' ? `¿Quieres ELIMINAR COMPLETAMENTE y sin retorno el curso "${nombre}" y todas sus programaciones de la Base de Datos?` : `¿Quieres habilitar el curso "${nombre}"?`);
        const icon = op === 'DEL' || op === 'PERMA_DEL' ? 'warning' : 'question';
        const color = op === 'DEL' || op === 'PERMA_DEL' ? '#d33' : '#3085d6';
        const confirmText = op === 'DEL' ? 'Sí, inhabilitar' : (op === 'PERMA_DEL' ? 'Sí, destruir curso' : 'Sí, habilitar');

        Swal.fire({
            title: titulo,
            text: texto,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: color,
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmText,
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                if (op === 'PERMA_DEL') {
                    axios.delete(`${VITE_URL_APP}/api/cursos/${cod}`)
                        .then(async (res) => {
                            if (res.status === 200 && res.data.success) {
                                Swal.fire('Éxito', res.data.message || 'Curso eliminado definitivamente', 'success')

                                // Mantener el estado de la vista previa validando el switch local "Solo eliminados"
                                const toggleEliminados = document.getElementById('chkEliminados');
                                if (toggleEliminados && toggleEliminados.checked) {
                                    await window.gestionListarCursos(0); // Forzar recarga de los deshabilitados
                                } else {
                                    await listarCursos();
                                }
                            } else {
                                Swal.fire('Error', res.data.message || 'No se pudo eliminar el curso permanentemente', 'error')
                            }
                        })
                        .catch(err => {
                            console.error(err)
                            Swal.fire('Error', err.response?.data?.message || 'Ocurrió un problema al eliminar el curso', 'error')
                        });
                } else {
                    axios.patch(`${VITE_URL_APP}/api/cursos/${cod}/habilitado`, data)
                        .then(async (res) => {
                            if (res.status === 200 && res.data.success) {
                                Swal.fire('Éxito', res.data.message || (op === 'DEL' ? 'Curso Eliminado' : 'Curso Habilitado'), 'success')
                                await listarCursos()
                            } else {
                                Swal.fire('Error', res.data.message || 'No se pudo actualizar el curso', 'error')
                            }
                        })
                        .catch(err => {
                            console.error(err)
                            Swal.fire('Error', 'Ocurrió un problema al actualizar el curso', 'error')
                        });
                }
            }
        });
    }

}

window.gestionListarCursos = (op) => {
    if (op === 1) {
        listarCursos();
    } else {
        listarCursosFiltro(0);
    }
}

window.editarFormGestionCurso = (e) => {
    if (e) e.preventDefault();

    const formElement = document.querySelector('[x-data="formCursoGestion()"]');
    if (!formElement || !window.Alpine) return;
    const alpineData = Alpine.$data(formElement);

    const data = {
        codigo: document.getElementById('codGestionEditar').value,
        nombre: document.getElementById('txtNombreCurso').value,
        tipo_curso: document.getElementById('slcTipoCurso').value,
        area: document.getElementById('slcArea').value,
        tiempo: parseInt(document.getElementById('txtLimite').value) || 0,
        nota: parseInt(document.getElementById('txtNota').value) || 0,
        intentos: parseInt(document.getElementById('txtIntentos').value) || 0,
    }

    const formData = new FormData();
    formData.append('codigo', data.codigo);
    formData.append('nombre', data.nombre);
    formData.append('tipo_curso', data.tipo_curso);
    formData.append('area', data.area);
    formData.append('tiempo', data.tiempo);
    formData.append('nota', data.nota);
    formData.append('intentos', data.intentos);

    // Nuevos campos de periodicidad edit
    formData.append('es_periodico', alpineData.activarPeriodicidad ? 1 : 0);
    if (alpineData.activarPeriodicidad) {
        formData.append('frecuencia', alpineData.frecuencia);
        formData.append('proyeccion_anios', alpineData.proyeccionAnios);

        // Si hay una nueva fecha inicio proyectamos de cero y re-generamos
        if (alpineData.frecuencia && alpineData.frecuencia !== 'PERSONALIZADO' && alpineData.mesInicio) {
            const fechas = window.generarFechasProyectadas(alpineData.frecuencia, alpineData.mesInicio, alpineData.proyeccionAnios);
            formData.append('fechas_generadas', JSON.stringify(fechas));
        }
    }
    //formData.append('archivo', archivoSeleccionado);

    // Append Sucursales (PAC) - Read from Alpine
    if (alpineData.esPAC && alpineData.sucursalesAsignadas.length > 0) {
        alpineData.sucursalesAsignadas.forEach(suc => {
            formData.append('sucursales_asignadas[]', suc);
        });
    }

    if (archivoSeleccionado) {
        formData.append('archivo', archivoSeleccionado);
    }

    console.log(formData);
    //return;

    axios.post(`${VITE_URL_APP}/api/update-curso`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
    })
        .then(async (res) => {
            if (res.status === 200 && res.data.success) {
                Swal.fire('Éxito', res.data.message || 'Curso registrado correctamente', 'success')

                await listarCursos();

                restaurarFormCurso();
            } else {
                Swal.fire('Error', res.data.message || 'No se pudo actualizar el curso', 'error')
            }
        })
        .catch(err => {
            console.error(err);

            if (err.response && err.response.status === 422) {
                const errors = err.response.data.errors || {};
                let errorMsg = '<ul class="text-left text-sm">';
                for (const [key, msgs] of Object.entries(errors)) {
                    errorMsg += `<li><b>${key}:</b> ${msgs[0]}</li>`;
                }
                errorMsg += '</ul>';

                Swal.fire({
                    title: 'Errores de Validación',
                    html: errorMsg,
                    icon: 'warning'
                });
            } else {
                Swal.fire('Error', 'Ocurrió un problema al actualizar el curso', 'error');
            }
        })
}

window.restaurarFormCurso = () => {
    const mensaje = document.getElementById('txtMensajeNuevo');
    const view = document.getElementById('viewEditCreate');
    const btn = document.getElementById('btnGestion');
    const btnEdit = document.getElementById('btnGestionEditar');
    const title = document.getElementById('txtTitleFile');
    const btnDownload = document.getElementById('btnDownloadPlantilla');

    if (btnDownload) {
        btnDownload.classList.add('hidden');
        btnDownload.href = '#';
    }

    mensaje.textContent = 'Nuevo';
    title.textContent = 'Actualizar plantilla';

    mensaje.classList.add('bg-primary/25', 'text-primary-800');
    mensaje.classList.remove('bg-warning/25', 'text-warning-800');

    view.classList.add('hidden');

    document.getElementById('codGestionEditar').value = '-1';

    // Al llamar a restaurarFormCurso (Crear curso), limpiamos el estado de Alpine
    const formElement = document.querySelector('[x-data="formCursoGestion()"]');
    if (formElement && window.Alpine) {
        const alpineData = Alpine.$data(formElement);
        if (typeof alpineData.limpiarCampos === 'function') {
            alpineData.limpiarCampos();
        }
    }

    btn.classList.remove('hidden');
    btnEdit.classList.add('hidden');
}

window.formCursoGestion = function () {
    return {
        codigo: '-1',
        nombre: '',
        tipoCurso: '',
        area: '',
        // Nueva Periodicidad estructurada
        activarPeriodicidad: false,
        frecuencia: '',
        mesInicio: '',
        proyeccionAnios: 1,
        // Removed: nombreExa, descripcion
        limiteTiempo: '',
        nota: '',
        intentos: '',
        fechaActual: new Date().toISOString().split('T')[0],

        // Lógica PAC
        esPAC: false,
        sucursalesAsignadas: [],
        sucursalesDisponibles: [],
        busquedaSucursal: '',

        get sucursalesFiltradas() {
            if (!this.busquedaSucursal) return this.sucursalesDisponibles;
            const search = this.busquedaSucursal.toLowerCase();
            return this.sucursalesDisponibles.filter(suc =>
                suc.toLowerCase().includes(search)
            );
        },

        init() {
            // Cargar sucursales dinámicamente al iniciar
            axios.get(`${VITE_URL_APP}/api/get-sucursales`)
                .then(res => {
                    if (res.data.success) {
                        // Extraer solo el nombre/abreviatura de la sucursal
                        this.sucursalesDisponibles = res.data.sucursales.map(s => s.sucursal);
                    }
                })
                .catch(err => {
                    console.error("Error al cargar sucursales para gestión", err);
                });
        },

        checkEsPAC() {
            const select = document.getElementById('slcTipoCurso');
            if (select && select.selectedIndex >= 0) {
                const text = select.options[select.selectedIndex].text;
                this.esPAC = text.includes('PAC');
                if (!this.esPAC) {
                    this.sucursalesAsignadas = [];
                }
            }
        },

        limpiarCampos() {
            this.codigo = '-1';
            this.nombre = '';
            this.tipoCurso = '';
            this.area = '';
            this.activarPeriodicidad = false;
            this.frecuencia = '';
            this.mesInicio = '';
            this.proyeccionAnios = 1;
            this.limiteTiempo = '0';
            this.nota = '0';
            this.intentos = '0';
            this.esPAC = false;
            this.sucursalesAsignadas = [];
            this.busquedaSucursal = '';

            // Forzar limpieza de inputs de archivos y estados visuales
            const archivoInput = document.getElementById('archivoInput');
            if (archivoInput) archivoInput.value = '';

            archivoSeleccionado = null;
            if (resumenPlantilla) resumenPlantilla.innerHTML = "";
            if (btnAnalizar) btnAnalizar.disabled = true;
        },

        registrar(e) {
            e?.preventDefault();

            // Validación PAC
            if (this.esPAC && this.sucursalesAsignadas.length === 0) {
                Swal.fire('Atención', 'Debe asignar al menos una sucursal para cursos PAC', 'warning');
                return;
            }

            const camposObligatorios = [
                'nombre', 'limiteTiempo', 'nota', 'intentos'
            ];

            const vacio = camposObligatorios.some(campo => !this[campo]);

            if (vacio) {
                Swal.fire('Atención', 'Completar los campos obligatorios', 'warning')
                return
            }

            if (!archivoSeleccionado) {
                Swal.fire('Atención', 'Debe importar la plantilla', 'warning')
                return
            }

            const formData = new FormData();
            formData.append('nombre', this.nombre);
            formData.append('tipo_curso', this.tipoCurso);
            formData.append('area', this.area);

            formData.append('es_periodico', this.activarPeriodicidad ? 1 : 0);
            if (this.activarPeriodicidad) {
                formData.append('frecuencia', this.frecuencia);
                formData.append('proyeccion_anios', this.proyeccionAnios);

                // Generar fechas estructuradas
                if (this.frecuencia && this.frecuencia !== 'PERSONALIZADO' && this.mesInicio) {
                    const fechas = window.generarFechasProyectadas(this.frecuencia, this.mesInicio, this.proyeccionAnios);
                    formData.append('fechas_generadas', JSON.stringify(fechas));
                }
            }

            // Removed: nombre_exa, descripcion
            formData.append('tiempo', this.limiteTiempo);
            formData.append('nota', this.nota);
            formData.append('intentos', this.intentos);
            formData.append('archivo', archivoSeleccionado);

            // Append Sucursales (PAC)
            if (this.esPAC && this.sucursalesAsignadas.length > 0) {
                this.sucursalesAsignadas.forEach(suc => {
                    formData.append('sucursales_asignadas[]', suc);
                });
            }

            axios.post(`${VITE_URL_APP}/api/save-cursos`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
                .then(async (res) => {
                    if (res.status === 200 && res.data.success) {
                        Swal.fire('Éxito', res.data.message || 'Curso registrado correctamente', 'success')

                        const valoresPorDefecto = {
                            codigo: "-1",
                            nombre: "",
                            tipoCurso: "",
                            area: "",
                            periodicidad: 0,
                            activarPeriodicidad: false,
                            limiteTiempo: 0,
                            nota: 0,
                            intentos: 0
                        };

                        // Reset Alpine Data
                        Object.assign(this, valoresPorDefecto);
                        this.limpiarCampos();

                        await listarCursos();
                        restaurarFormCurso();

                    } else {
                        Swal.fire('Error', res.data.message || 'No se pudo registrar el curso', 'error')
                    }
                })
                .catch(err => {
                    console.error(err);

                    if (err.response && err.response.status === 422) {
                        const errors = err.response.data.errors || {};
                        let errorMsg = '<ul class="text-left text-sm">';
                        for (const [key, msgs] of Object.entries(errors)) {
                            errorMsg += `<li><b>${key}:</b> ${msgs[0]}</li>`;
                        }
                        errorMsg += '</ul>';

                        Swal.fire({
                            title: 'Errores de Validación',
                            html: errorMsg,
                            icon: 'warning'
                        });
                    } else {
                        Swal.fire('Error', 'Ocurrió un problema al registrar el curso', 'error');
                    }
                })

        }
    }
}

// Helper Generator Func
window.generarFechasProyectadas = function (frecuencia, mesInicioStr, anios) {
    if (!mesInicioStr || !frecuencia || frecuencia === 'PERSONALIZADO') return [];

    let [year, month] = mesInicioStr.split('-');
    let startDate = new Date(parseInt(year), parseInt(month) - 1, 1);

    let multiplosMeses = 1;
    if (frecuencia === 'BIMESTRAL') multiplosMeses = 2;
    if (frecuencia === 'TRIMESTRAL') multiplosMeses = 3;
    if (frecuencia === 'CUATRIMESTRAL') multiplosMeses = 4;
    if (frecuencia === 'SEMESTRAL') multiplosMeses = 6;
    if (frecuencia === 'ANUAL') multiplosMeses = 12;

    let ciclosPorAnio = 12 / multiplosMeses;
    let totalCiclos = Math.floor(ciclosPorAnio * parseInt(anios || 1));
    let arrayFechas = [];

    for (let i = 0; i < totalCiclos; i++) {
        let currentDate = new Date(startDate.getTime());
        currentDate.setMonth(currentDate.getMonth() + (i * multiplosMeses));

        let targetYear = currentDate.getFullYear();
        let targetMonth = (currentDate.getMonth() + 1).toString().padStart(2, '0');
        let targetLastDay = new Date(targetYear, currentDate.getMonth() + 1, 0).getDate();

        arrayFechas.push({
            inicio: `${targetYear}-${targetMonth}-01`,
            final: `${targetYear}-${targetMonth}-${targetLastDay}`,
            periodo: `${targetYear}-${targetMonth}`
        });
    }
    return arrayFechas;
}


// --- Lógica del Panel de Programación ---

window.activarPanelProgramacion = function (codigoCurso, nombreCurso, frecuenciaStr) {
    let mostrarBtn = true;
    if (frecuenciaStr && frecuenciaStr !== 'PERSONALIZADO' && frecuenciaStr !== 'null' && frecuenciaStr !== 'undefined') {
        mostrarBtn = false; // Bloquea si es generado estructurado
    }

    window.dispatchEvent(new CustomEvent('cambiar-panel', {
        detail: {
            panel: 'programacion',
            titulo: nombreCurso,
            mostrarBtn: mostrarBtn
        }
    }));
    // Wait a bit for Alpine transition or just setData
    setTimeout(() => {
        // Find input hidden in Programacion form and set val
        const inputCod = document.getElementById('codigoCursoInput');
        if (inputCod) {
            inputCod.value = codigoCurso;
            inputCod.dispatchEvent(new Event('input')); // Notify Alpine
        }
        // Also update Alpine data directly if possible?
        // Better: Dispatch event to the form component
        window.dispatchEvent(new CustomEvent('set-curso-programacion', {
            detail: { codigo: codigoCurso, nombre: nombreCurso }
        }));
    }, 100);

    // Cargar historial de programaciones
    listarProgramaciones(codigoCurso);
}

window.listarProgramaciones = async function (codigoCurso) {
    try {
        const tableBody = document.querySelector('#tblProgramacion tbody');
        if (!tableBody) return;

        tableBody.innerHTML = '<tr><td colspan="4" class="text-center">Cargando...</td></tr>';

        // Endpoint corregido
        const res = await axios.get(`${VITE_URL_APP}/api/get-curso-programacion/${codigoCurso}`);

        let html = '';
        // La respuesta del backend es { success: true, programaciones: [...] }
        const programaciones = res.data.programaciones || [];

        if (res.data.success && programaciones.length > 0) {

            // Helper para formatear fecha (YYYY-MM-DD -> DD/MM/YYYY)
            // Helper para formatear fecha (YYYY-MM-DD -> DD/MM/YYYY)
            const formatDate = (dateString) => {
                if (!dateString) return '';
                try {
                    // split por T o espacio para quitar hora, luego split por -
                    const partes = dateString.split(/T| /)[0].split('-');
                    if (partes.length === 3) return `${partes[2]}/${partes[1]}/${partes[0]}`;
                    return dateString;
                } catch (e) { return dateString; }
            };

            programaciones.forEach((prog, i) => {
                const fInicio = formatDate(prog.fecha_inicio);
                const fFin = formatDate(prog.fecha_final);

                // Validar si la fecha ha pasado
                let esPasada = false;
                if (prog.fecha_final) {
                    const fechaFinal = new Date(prog.fecha_final);
                    const hoy = new Date();
                    fechaFinal.setHours(23, 59, 59, 999);
                    esPasada = fechaFinal < hoy;
                }

                const trClass = esPasada ? 'bg-gray-100/70' : '';
                const textCol = esPasada ? 'text-gray-400' : '';
                const badge = esPasada ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-200 text-gray-600">Finalizada</span>' : '';

                html += `
                    <tr class="${trClass}">
                        <td class="${textCol}">${i + 1}</td>
                        <td class="${textCol}">${prog.codigo}</td>
                        <td class="text-sm font-medium ${textCol}">${fInicio} - ${fFin} ${badge}</td>
                        <td>
                            <div class="flex justify-center gap-2">
                                <button class="btn btn-sm rounded-full bg-info/25 text-info hover:bg-info hover:text-white transition-colors duration-200" 
                                    onclick="editarProgramacion('${prog.codigo}', '${prog.tipo}', '${prog.periodo}', '${prog.fecha_inicio}', '${prog.fecha_final}')"
                                    title="Editar">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="btn btn-sm rounded-full bg-danger/25 text-danger hover:bg-danger hover:text-white transition-colors duration-200" 
                                    onclick="eliminarProgramacion('${prog.codigo}', '${codigoCurso}')"
                                    title="Eliminar">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="4" class="text-center text-gray-500">No hay programaciones registradas</td></tr>';
        }
        tableBody.innerHTML = html;

    } catch (err) {
        console.error("Error listar programaciones", err);
        const tableBody = document.querySelector('#tblProgramacion tbody');
        if (tableBody) tableBody.innerHTML = '<tr><td colspan="4" class="text-center text-red-500">Error al cargar datos</td></tr>';
    }
}

window.eliminarProgramacion = function (idProg, codigoCurso) {
    Swal.fire({
        title: '¿Eliminar programación?',
        text: "No podrás revertir esto",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar'
    }).then((result) => {
        if (result.isConfirmed) {
            axios.patch(`${VITE_URL_APP}/api/programaciones/${idProg}/habilitado`, { habilitado: 0 })
                .then(res => {
                    if (res.data.success) {
                        Swal.fire('Eliminado', res.data.message || 'La programación ha sido eliminada.', 'success');
                        listarProgramaciones(codigoCurso);
                    } else {
                        Swal.fire('Error', res.data.message || 'No se pudo eliminar', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Ocurrió un problema al eliminar', 'error');
                });
        }
    })
}


// Formulario Programacion Alpine
// Helper para editar
window.editarProgramacion = function (codigo, tipo, periodo, fechaInicio, fechaFinal) {
    const event = new CustomEvent('edit-programacion', {
        detail: { codigo, tipo, periodo, fechaInicio, fechaFinal }
    });
    window.dispatchEvent(event);
    abrirModalRegistro();
}

// Formulario Programacion Alpine
window.formProgramacionGestion = function () {
    return {
        codigo: '',
        codigoCurso: '',
        nombreCurso: '',
        tipo: 'REGULAR',
        periodo: '',
        fechaInicio: '',
        fechaFinal: '',
        isEdit: false,

        init() {
            // Helper interno para parsear fecha YYYY-MM-DD desde ISO o datetime SQL
            const parseDateISO = (dateStr) => {
                if (!dateStr) return '';
                return dateStr.split(/T| /)[0];
            };

            window.addEventListener('set-curso-programacion', (e) => {
                this.isEdit = false;
                this.codigo = '';
                this.codigoCurso = e.detail.codigo;
                this.nombreCurso = e.detail.nombre;

                // Inicializar con mes actual
                this.periodo = new Date().toISOString().slice(0, 7);
                this.tipo = 'REGULAR';
                this.actualizarFechasPorPeriodo();
            });

            // Evento para EDITAR programación
            window.addEventListener('edit-programacion', (e) => {
                this.isEdit = true;
                this.codigo = e.detail.codigo;
                // Asumimos que estamos en el contexto del mismo curso
                this.codigoCurso = document.getElementById('codigoCursoInput') ? document.getElementById('codigoCursoInput').value : '';
                this.nombreCurso = document.getElementById('nombreCurso') ? document.getElementById('nombreCurso').value : '';

                this.tipo = e.detail.tipo;

                if (this.tipo === 'REGULAR') {
                    this.periodo = e.detail.periodo;
                    this.actualizarFechasPorPeriodo();
                } else {
                    this.periodo = '';
                    // Usar helper robusto para fecha
                    this.fechaInicio = parseDateISO(e.detail.fechaInicio);
                    this.fechaFinal = parseDateISO(e.detail.fechaFinal);
                }
            });

            // Watcher para periodo
            this.$watch('periodo', (val) => {
                if (val && this.tipo === 'REGULAR') {
                    this.actualizarFechasPorPeriodo();
                }
            });

            // Watcher para tipo
            this.$watch('tipo', (val) => {
                if (val === 'REGULAR') {
                    if (!this.periodo) {
                        this.periodo = new Date().toISOString().slice(0, 7);
                    }
                    this.actualizarFechasPorPeriodo();
                }
                // Si cambia a EXTEMPORANEO, mantenemos las fechas actuales (si existen) 
                // para que el usuario no empiece de cero.
            });
        },

        actualizarFechasPorPeriodo() {
            if (!this.periodo) return;

            const [year, month] = this.periodo.split('-');
            if (!year || !month) return;

            const y = parseInt(year);
            const m = parseInt(month);

            // Primer día
            this.fechaInicio = `${year}-${month}-01`;

            // Último día
            const lastDayDate = new Date(y, m, 0);
            const lastDay = lastDayDate.getDate();

            this.fechaFinal = `${year}-${month}-${lastDay}`;
        },

        submit() {
            // Validación de fechas obligatorias para TODOS los tipos
            if (!this.fechaInicio || !this.fechaFinal) {
                Swal.fire('Error', 'Debe indicar fecha de inicio y fin', 'warning');
                return;
            }

            if (this.fechaInicio > this.fechaFinal) {
                Swal.fire('Error', 'La fecha de inicio no puede ser mayor a la fecha final', 'warning');
                return;
            }

            const formData = {
                cod_cursos: this.codigoCurso,
                tipo: this.tipo,
                fecha_inicio: this.fechaInicio,
                fecha_final: this.fechaFinal,
                habilitado: 1
            };

            let promise;
            if (this.isEdit && this.codigo) {
                formData.codigo = this.codigo;
                // La ruta es definida como POST en api.php
                promise = axios.post(`${VITE_URL_APP}/api/update-programacion`, formData);
            } else {
                promise = axios.post(`${VITE_URL_APP}/api/save-programacion`, formData);
            }

            promise
                .then(res => {
                    if (res.data.success) {
                        Swal.fire('Éxito', res.data.message || 'Operación exitosa', 'success');

                        // Cerrar modal
                        const closeBtn = document.getElementById('btn-modal-docs-close');
                        if (closeBtn) closeBtn.click();

                        listarProgramaciones(this.codigoCurso);
                    } else {
                        Swal.fire('Error', res.data.message || 'No se pudo guardar', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    const msg = err.response?.data?.message || 'Error al guardar programación';
                    Swal.fire('Error', msg, 'error');
                });
        }
    }
}

// Helper para abrir modal (usado en el onclick del boton html)
window.abrirModalRegistro = function () {
    const modal = document.querySelector('#modal-registro');
    if (modal) {
        // Logic to open modal (Preline UI or similar)
        // If using HSOverlay global
        if (typeof HSOverlay !== 'undefined') {
            HSOverlay.open(modal);
        } else {
            // Fallback simplistic
            modal.classList.remove('hidden');
            modal.classList.add('open'); // check css
        }
    }
}
