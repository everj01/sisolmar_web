import Swal from "sweetalert2";
import axios from "axios";
import DataTable from "vanilla-datatables";
import imageCompression from 'browser-image-compression';

console.log('ESTO ES UNA PREUBA PROVAOFR');

window.cursosData = [];
window.alertasCursosData = [];

// window.alertasVencimientoCursos inyectado vía Blade para evitar race conditions con Vite

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
            Swal.fire("Atención", `El archivo "${archivo.name}" supera 1 MB y fue omitido.`, "warning");
            archivoInput.value = "";
            return;
        }

        // Validar extensión
        const ext = archivo.name.split('.').pop().toLowerCase();
        if (!["mbz"].includes(ext)) {
            Swal.fire("Atención", `Solo se permiten archivos .mbz`, "warning");
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

            // --- AUTO-RELLENADO INTELIGENTE (PAUTA 7/11) ---
            const formElement = document.querySelector('[x-data="formCursoGestion()"]');
            if (formElement && window.Alpine) {
                const alpineData = Alpine.$data(formElement);
                // Sincronizar el total de preguntas detectadas
                alpineData.preguntasBalotario = data.totalQuestions || 0;

                // Si el usuario no ha puesto una cantidad de preguntas, sugerir que se tomen todas
                if (!alpineData.cantidadPreguntas || alpineData.cantidadPreguntas == 0) {
                    alpineData.cantidadPreguntas = data.totalQuestions || 0;
                }
            }
            // -----------------------------------------------

            let actividadesHtml = "";

            resumenPlantilla.innerHTML = `
          <div class="bg-white border border-gray-100 shadow-sm rounded-xl p-6 mt-6">
              <div class="flex items-center gap-2 mb-4">
                  <div class="w-5 h-6 bg-blue-500 rounded-sm"></div>
                  <h3 class="text-lg font-bold text-slate-500">Resumen de la Plantilla</h3>
              </div>

              <div class="text-sm text-slate-500 leading-relaxed mb-6">
                  <p><span class="font-bold">Nombre del curso:</span> ${data.courseName}</p>
                  <p><span class="font-bold">Código corto:</span> ${data.courseShortname}</p>
                  <p><span class="font-bold">Versión Moodle:</span> ${data.moodleVersion}</p>
                  <p><span class="font-bold">Fecha backup:</span> ${new Date(data.backupDate * 1000).toLocaleString()}</p>
              </div>

              <div class="grid grid-cols-2 gap-8 text-center mb-8">
                  <div>
                      <p class="text-3xl font-bold text-blue-600 mb-1">${data.totalSections}</p>
                      <p class="text-sm text-slate-500">Secciones</p>
                  </div>
                  <div>
                      <p class="text-3xl font-bold text-slate-500 mb-1">${data.totalActivities}</p>
                      <p class="text-sm text-slate-500">Actividades</p>
                  </div>
              </div>

              <div>
                  <div class="flex items-center gap-2 mb-3">
                      <i class="bx bx-bar-chart-alt-2 text-slate-500 text-lg"></i>
                      <h4 class="text-sm font-bold text-slate-500">Actividades por tipo</h4>
                  </div>
                  <div class="space-y-2 text-sm text-slate-500 mt-2">
                      ${Object.entries(data.activityStats).map(([tipo, cantidad]) => `
                          <div class="flex justify-between border-b border-gray-100 py-2">
                              <span class="capitalize">${tipo}</span>
                              <span class="font-bold text-slate-700">${cantidad}</span>
                          </div>
                      `).join('')}
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
            "flex items-center justify-between py-3";

        item.innerHTML = `
            <span class="text-sm font-medium text-gray-500">${archivoSeleccionado.name}</span>
            <div class="flex items-center gap-x-2">
                <button type="button" class="text-red-500 hover:text-red-600 text-sm flex items-center font-medium transition-colors" id="btnQuitar">
                    <i class="bx bx-trash mr-1 text-base"></i> Quitar
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

// document.addEventListener('DOMContentLoaded', async () => {

//     await listarTipoCurso("slcTipoCurso")
//     await listarTipoCurso("slcFiltroTipoCurso", true)
//     // await listarAreas("slcArea") // Removed: Replaced by Alpine component
//     await listarAreas("slcFiltroArea", true)
//     await listarCursos()

//     // DataTable will be initialized inside renderTablaCursos
// })

document.addEventListener('DOMContentLoaded', async () => {
    await listarTipoCurso("slcTipoCurso")
    await listarTipoCurso("slcFiltroTipoCurso", true)
    await listarAreas()
    await listarCursos()
});


window.listarCursos = async function (habilitado = 1, area = '', tipoCurso = '') {
    try {
        const res = await axios.get(`${VITE_URL_APP}/api/get-cursos/${habilitado}`, {
            params: { filtro_area: area, filtro_tipo: tipoCurso }
        });

        window.cursosData = res.data;
        window.renderTablaCursos(window.cursosData);
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
        const res = await axios.get(
            `${VITE_URL_APP}/api/obtener-capacitacion-sistemas`,
        );
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

// async function listarCursosFiltro() {
//     try {
//         const res = await axios.get(`${VITE_URL_APP}/api/get-cursos/0`)
//         window.cursosData = res.data
//         window.renderTablaCursos(window.cursosData)
//     } catch (err) {
//         console.error("Error al obtener cursos", err)
//         Swal.fire("Error", "No se pudieron cargar los cursos", "error")
//     }
// }

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
window.cursoTable = null;

window.renderTablaCursos = function (data) {
    // Destroy existing DataTable instance
    if (window.cursoTable) {
        window.cursoTable.destroy();
        window.cursoTable = null;
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

            const alertIcon = window.alertasCursosData && window.alertasCursosData.includes(String(curso.codigoCurso))
                ? '<i class="bx bxs-info-circle text-orange-500 ml-2 text-lg" title="Próxima clonación programada (≤15 días)"></i>'
                : '';

            tr.innerHTML = `
        <td>${index + 1}</td>
        <td>${curso.codigoCurso}</td>
        <td class="text-primary font-medium">
            <div class="flex items-center">
                ${curso.nombre}${alertIcon}
            </div>
        </td>
        <td>
            <div class="flex items-center gap-2">
                <button type="button" onclick="window.gestionCurso('EDIT', '${curso.codigo}', '${curso.nombre.replace(/'/g, "\\'")}')"
                class="btn btn-sm rounded bg-info/10 text-info hover:bg-info hover:text-white transition-colors" title="Editar curso">
                    <i class="bx bxs-edit text-base"></i>
                </button>


                ${curso.habilitado == '1' ?
                    `<button type="button" 
                        ${curso.tiene_vigente
                        ? 'disabled class="btn btn-sm rounded bg-gray-100/50 text-gray-400 cursor-not-allowed" title="Ya tiene un periodo VIGENTE activo"'
                        : `class="btn btn-sm rounded bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors" title="Aperturar 1er Ciclo Manual" 
                        onclick="window.dispatchEvent(new CustomEvent('open-apertura-modal', { detail: { codigo: '${curso.codigo}', nombre: '${curso.nombre.replace(/'/g, "\\'")}', tipo_curso: '${curso.tipo_curso || ''}', dirigido_a: '${curso.dirigido_a || ''}', frecuencia: '${curso.frecuencia || ''}' } }))"`}>
                        <i class="bx bx-calendar-star text-base"></i>
                    </button>
                    
                    <button type="button"  onclick="window.gestionCurso('DEL', '${curso.codigo}', '${curso.nombre.replace(/'/g, "\\'")}')"
                    class="btn btn-sm rounded bg-danger/10 text-danger hover:bg-danger hover:text-white transition-colors" title="Deshabilitar curso">
                        <i class="bx bx-trash text-base"></i>
                    </button>`
                    :
                    `<button type="button"  onclick="window.gestionCurso('ACT', '${curso.codigo}', '${curso.nombre.replace(/'/g, "\\'")}')"
                    class="btn btn-sm rounded bg-success/10 text-success hover:bg-success hover:text-white transition-colors" title="Habilitar curso">
                        <i class='bx bx-check text-base'></i>
                    </button>
                    <button type="button"  onclick="window.gestionCurso('PERMA_DEL', '${curso.codigo}', '${curso.nombre.replace(/'/g, "\\'")}')"
                    class="btn btn-sm rounded bg-danger/10 text-danger hover:bg-danger hover:text-white transition-colors" title="Eliminar definitivamente de BD">
                        <i class="bx bx-trash text-base"></i>
                    </button>`
                }

                ${curso.es_demanda == '1' ?
                    `<button type="button" onclick="window.dispatchEvent(new CustomEvent('abrir-modal-excel', { detail: { codigo: '${curso.codigo}', nombre: '${curso.nombre.replace(/'/g, "\\'")}' } }))"
                    class="btn btn-sm rounded bg-blue-500/10 text-blue-600 hover:bg-blue-500 hover:text-white transition-colors" title="Matrícula Masiva (Excel)">
                        <i class="bx bxs-file-import text-base"></i>
                    </button>` : ''
                }

                <button type="button" onclick="window.abrirModalAplazarCurso('${curso.codigo}', '${curso.nombre.replace(/'/g, "\\'")}')"
                    class="btn btn-sm rounded bg-success/10 text-success hover:bg-success hover:text-white transition-colors" title="Dar más plazo al curso">
                    <i class="bx bx-time-five text-base"></i>
                </button>
            </div>
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
    window.cursoTable = new DataTable(newTable, {
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

        if (dataget && dataget.success && dataget.curso) {
            const curso = dataget.curso;
            const mensaje = document.getElementById('txtMensajeNuevo');
            const view = document.getElementById('viewEditCreate');
            const btn = document.getElementById('btnGestion');
            const btnEdit = document.getElementById('btnGestionEditar');
            const title = document.getElementById('txtTitleFile');
            const btnDownload = document.getElementById('btnDownloadPlantilla');

            if (mensaje) {
                mensaje.textContent = 'Editar';
                mensaje.classList.remove('bg-primary/25', 'text-primary-800');
                mensaje.classList.add('bg-warning/25', 'text-warning-800');
            }

            if (title) title.textContent = 'Actualizar curso';
            if (view) view.classList.remove('hidden');
            if (btnDownload) btnDownload.classList.remove('hidden');

            document.getElementById('codGestionEditar').value = curso.codigo;

            // Populate Alpine.js data directly
            const formElement = document.querySelector('[x-data="formCursoGestion()"]');
            if (formElement && window.Alpine) {
                const alpineData = Alpine.$data(formElement);

                // Sincronizar campos principales con Alpine
                alpineData.codigo = curso.codigo;
                alpineData.nombre = curso.nombre;
                alpineData.tipoCurso = curso.tipo_curso?.codigo ?? "";

                // IMPORTANTE: Sincronizar Area de Conocimiento
                // Usar tarea asincrónica para cargar las áreas y luego asignar el área responsable
                const cargarAreaYAsignar = async () => {
                    alpineData.areaConocimiento = curso.area_conocimiento ?? "";
                    alpineData.area = curso.area_conocimiento ?? "";

                    if (alpineData.tipoCurso == '6') {
                        await alpineData.cargarAreasResponsablesPCA();
                    } else if (curso.area_conocimiento) {
                        alpineData.lastSistemaId = null;
                        await alpineData.cargarAreasResponsables(curso.area_conocimiento);
                    }

                    alpineData.areaResponsable = curso.area ?? "";
                };

                // Ejecutar la carga de áreas y continuar
                const areaPromise = cargarAreaYAsignar();

                // Esperar la carga de áreas antes de abrir el modal
                await areaPromise;

                alpineData.frecuencia = curso.frecuencia ?? "";

                alpineData.dirigido = curso.dirigido_a == 0 ? 'OTROS' : (curso.dirigido_a ?? '');

                // Responsable (NUEVO)
                alpineData.codResponsable = curso.cod_responsable ?? "";
                alpineData.nombreResponsable = curso.nombre_responsable ?? "";
                alpineData.descripcion = curso.descripcion ?? "";

                // Moodle
                alpineData.codMoodleArea = curso.cod_moodle_area ?? ""; // Si existiera en el futuro

                // METADATOS DE SISTEMA
                alpineData.sys_codigo = curso.codigo ?? "-";
                alpineData.sys_creado_por = curso.creado_por || "-";
                alpineData.sys_fecha_creacion = curso.fecha_creacion || "-";
                alpineData.sys_modificado_por = curso.modificado_por || "-";
                alpineData.sys_fecha_modificacion = curso.fecha_modificacion || curso.updated_at || "-";

                // Update PAC/PCI/PCU logic
                const esPacCurso = curso.tipo_curso?.descripcion?.toUpperCase().includes('PAC') || false;
                alpineData.esPAC = esPacCurso;
                alpineData.sucursalesAsignadas = curso.sucursales || [];

                if (alpineData.tipoCurso == '6') {
                    alpineData.clienteSeleccionado = curso.sucursales?.[0] || '';
                } else if (alpineData.tipoCurso == '7') {
                    alpineData.areasAsignadas = curso.sucursales || [];
                }

                // Datos de Examen (Sincronizar con Alpine)
                alpineData.aplicaEvaluacion = curso.aplica_evaluacion == 1;
                alpineData.obligatorioAlta = true; // Siempre true por regla de negocio
                alpineData.esDemanda = false;      // Retirado por regla de negocio

                alpineData.targetGroup = curso.target_group || 'TODOS';

                alpineData.limiteTiempo = curso.examen?.tiempo ?? 0;
                alpineData.nota = curso.examen?.nota_minima ?? 0;
                alpineData.intentos = curso.examen?.intentos ?? 0;

                // Corregir nombres de campos de preguntas según el modelo
                alpineData.cantidadPreguntas = curso.examen?.cantidad_preguntas ?? curso.examen?.preguntas_balotario ?? 0;
                alpineData.preguntasBalotario = curso.examen?.preguntas_balotario ?? 0;
            } else {
                console.warn("No se encontró el elemento Alpine formCursoGestion o Alpine no está disponible");
            }

            if (btnDownload) {
                if (curso.examen && curso.examen.file_tiene == 1 && curso.examen.file_ruta) {
                    btnDownload.href = `${VITE_URL_APP}/storage/${curso.examen.file_ruta}`;
                    btnDownload.setAttribute('download', curso.examen.file_nombre || 'plantilla');
                    const nombreMostrar = curso.examen.file_nombre_original || curso.examen.file_nombre || 'plantilla';
                    btnDownload.innerHTML = `<i class='bx bxs-cloud-download'></i>&nbsp;Descargar ${nombreMostrar}`;
                    btnDownload.classList.remove('hidden');
                    btnDownload.target = "_blank";
                } else {
                    btnDownload.classList.add('hidden');
                    btnDownload.href = '#';
                }
            }

            if (btn) btn.classList.add('hidden');
            if (btnEdit) btnEdit.classList.remove('hidden');

            // Abrir el modal
            window.dispatchEvent(new CustomEvent('open-modal-gestion'));

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

// window.gestionListarCursos = (op) => {
//     if (op === 1) {
//         listarCursos();
//     } else {
//         listarCursosFiltro(0);
//     }
// }

window.gestionListarCursos = (op) => {
    if (op === 1) {
        listarCursos(1);
    } else {
        listarCursos(0);
    }
}

window.editarFormGestionCurso = (e) => {
    if (e) e.preventDefault();

    const formElement = document.querySelector('[x-data="formCursoGestion()"]');
    if (!formElement || !window.Alpine) return;
    const alpineData = Alpine.$data(formElement);

    if (!alpineData.codResponsable) {
        Swal.fire('Atención', 'Debe seleccionar un responsable para el curso', 'warning');
        return;
    }

    if (!alpineData.areaResponsable) {
        Swal.fire('Atención', 'Debe seleccionar un área responsable', 'warning');
        return;
    }

    if (!alpineData.dirigido) {
        Swal.fire('Atención', 'Debe seleccionar a quién va dirigido el curso', 'warning');
        return;
    }

    // Validación de campos obligatorios antes de actualizar
    const camposObligatorios = ['nombre', 'tipoCurso', 'areaConocimiento'];
    const vacio = camposObligatorios.some(campo => !alpineData[campo]);

    if (vacio) {
        Swal.fire('Atención', 'Debe completar los campos obligatorios (*)', 'warning');
        return;
    }

    // Validación de examen (mínimos)
    if (alpineData.aplicaEvaluacion) {
        const tiempoOk = parseInt(alpineData.limiteTiempo) >= 5;
        const notaOk = parseFloat(alpineData.nota) >= 5;
        const intentosOk = parseInt(alpineData.intentos) >= 1;
        const preguntasOk = parseInt(alpineData.cantidadPreguntas) >= 5;
        const balotarioOk = parseInt(alpineData.preguntasBalotario) >= 5;
        if (!tiempoOk || !notaOk || !intentosOk || !preguntasOk || !balotarioOk) {
            Swal.fire('Atención', 'Complete correctamente los datos del examen: Tiempo (mín. 5 min), Nota mínima (mín. 5), Intentos (mín. 1), Cant. preguntas (mín. 5) y Balotario (mín. 5)', 'warning');
            return;
        }
    }

    const data = {
        codigo: alpineData.codigo,
        nombre: alpineData.nombre,
        tipo_curso: alpineData.tipoCurso,
        area_conocimiento: alpineData.areaConocimiento,
        tiempo: alpineData.aplicaEvaluacion ? (parseInt(alpineData.limiteTiempo) || 0) : 0,
        nota: alpineData.aplicaEvaluacion ? (parseFloat(alpineData.nota) || 0) : 0,
        intentos: alpineData.aplicaEvaluacion ? (parseInt(alpineData.intentos) || 0) : 0,
        cantidad_preguntas: alpineData.aplicaEvaluacion ? (parseInt(alpineData.cantidadPreguntas) || 0) : 0,
        preguntas_balotario: alpineData.aplicaEvaluacion ? (parseInt(alpineData.preguntasBalotario) || 0) : 0,
    }

    const formData = new FormData();
    formData.append('codigo', data.codigo);
    formData.append('nombre', data.nombre);
    formData.append('tipo_curso', data.tipo_curso);
    formData.append('area_conocimiento', data.area_conocimiento);
    formData.append('tiempo', data.tiempo);
    formData.append('nota', data.nota);
    formData.append('intentos', data.intentos);
    formData.append('cantidad_preguntas', data.cantidad_preguntas);
    formData.append('preguntas_balotario', data.preguntas_balotario);
    formData.append('frecuencia', alpineData.frecuencia);
    formData.append('es_periodico', alpineData.frecuencia ? 1 : 0);
    // NUEVO: Enviar estados de los checks
    formData.append('aplica_evaluacion', alpineData.aplicaEvaluacion ? 1 : 0);
    formData.append('obligatorio_alta', alpineData.obligatorioAlta ? 1 : 0);
    formData.append('es_demanda', alpineData.esDemanda ? 1 : 0);
    formData.append('target_group', alpineData.targetGroup || 'TODOS');
    formData.append('cod_responsable', alpineData.codResponsable);
    formData.append('area_responsable', alpineData.areaResponsable);
    formData.append('cod_moodle_area', alpineData.codMoodleArea);
    formData.append('descripcion', alpineData.descripcion);
    formData.append('dirigido_a', alpineData.dirigido);

    //formData.append('archivo', archivoSeleccionado);

    // Append Sucursales (PAC / PCU / PCI) - Read from Alpine
    if (alpineData.esPAC && alpineData.sucursalesAsignadas.length > 0) {
        alpineData.sucursalesAsignadas.forEach(suc => formData.append('sucursales_asignadas[]', suc));
    }
    if (alpineData.tipoCurso == '6' && alpineData.clienteSeleccionado) {
        formData.append('sucursales_asignadas[]', alpineData.clienteSeleccionado);
    }
    if (alpineData.tipoCurso == '7' && alpineData.areasAsignadas.length > 0) {
        alpineData.areasAsignadas.forEach(a => formData.append('sucursales_asignadas[]', a));
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
                // Cerrar el modal antes de mostrar el éxito
                window.dispatchEvent(new CustomEvent('close-modal-gestion'));

                Swal.fire('Éxito', res.data.message || 'Curso actualizado correctamente', 'success')

                await listarCursos();

                restaurarFormCurso(false);
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

window.restaurarFormCurso = (abrir = true) => {
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

    if (mensaje) {
        mensaje.textContent = 'Nuevo';
        mensaje.classList.add('bg-primary/25', 'text-primary-800');
        mensaje.classList.remove('bg-warning/25', 'text-warning-800');
    }

    if (title) {
        title.textContent = 'Actualizar plantilla';
    }

    if (view) {
        view.classList.add('hidden');
    }

    const codEditar = document.getElementById('codGestionEditar');
    if (codEditar) codEditar.value = '';

    // Al llamar a restaurarFormCurso (Crear curso), limpiamos el estado de Alpine
    const formElement = document.querySelector('[x-data="formCursoGestion()"]');
    if (formElement && window.Alpine) {
        const alpineData = Alpine.$data(formElement);
        if (typeof alpineData.limpiarCampos === 'function') {
            alpineData.limpiarCampos();
        }
    }

    if (btn) btn.classList.remove('hidden');
    if (btnEdit) btnEdit.classList.add('hidden');

    // Abrir o cerrar el modal según el parámetro
    if (abrir) {
        window.dispatchEvent(new CustomEvent('open-modal-gestion'));
    } else {
        window.dispatchEvent(new CustomEvent('close-modal-gestion'));
    }
}

window.formCursoGestion = function () {
    return {
        codigo: '',
        // Información de Sistema
        sys_codigo: '-',
        sys_creado_por: '-',
        sys_fecha_creacion: '-',
        sys_modificado_por: '-',
        sys_fecha_modificacion: '-',

        nombre: '',
        tipoCurso: '5',
        areaConocimiento: '',
        area: '',
        areaResponsable: '',
        codMoodleArea: '',
        areasResponsables: [],
        lastSistemaId: null,
        async cargarAreasResponsables(sistemaId) {
            if (!sistemaId) {
                this.areasResponsables = [];
                this.areaResponsable = '';
                this.lastSistemaId = null;
                return;
            }

            if (sistemaId == this.lastSistemaId) return;

            try {
                this.lastSistemaId = sistemaId;
                const res = await axios.get(`${VITE_URL_APP}/api/obtener-areas-por-sistema/${sistemaId}`);
                if (res.data.success) {
                    this.areasResponsables = res.data.areas;
                }
            } catch (e) {
                console.error("Error cargando áreas responsables:", e);
                this.lastSistemaId = null;
            }
        },
        async cargarAreasResponsablesPCA() {
            try {
                const res = await axios.get(`${VITE_URL_APP}/api/obtener-areas`);
                if (res.data && res.data.success && Array.isArray(res.data.areas)) {
                    this.areasResponsables = res.data.areas;
                }
                this.lastSistemaId = null;
            } catch (e) {
                console.error("Error cargando áreas para PCA:", e);
            }
        },
        areasEncargadas: [],
        frecuencia: '',
        fechaInicio: '',
        fechaFinal: '',
        dirigido: '',
        // Removed: nombreExa, descripcion
        limiteTiempo: '',
        nota: '',
        intentos: '',
        cantidadPreguntas: '',
        preguntasBalotario: '',
        targetGroup: 'TODOS',
        fechaActual: new Date().toISOString().split('T')[0],

        tipoResponsable: 'ADMINISTRATIVO_5',
        codResponsable: '',
        nombreResponsable: '',
        descripcion: '',

        aplicaEvaluacion: false,
        obligatorioAlta: true, // Siempre true por regla de negocio
        esDemanda: false,      // Retirado


        // Procesamiento Word 2026
        archivoWord: null,
        archivoWordNombre: '',
        cargandoWord: false,
        preguntasExamen: [],  // Almacena las preguntas extraídas hasta que se guarde el curso
        wordMetrics: {
            tokensInput: 0,
            tokensOutput: 0,
            tokensTotal: 0,
            costoUSD: 0,
            tiempoSeg: 0
        },

        imageFilePortada: null,
        imagePreviewPortada: null,
        imageFileAfiche: null,
        imagePreviewAfiche: null,

        modalPreviewAbierto: false,
        modalPreviewSrc: '',
        modalPreviewTitulo: '',

        previewImage(src, titulo) {
            this.modalPreviewSrc = src;
            this.modalPreviewTitulo = titulo;
            this.modalPreviewAbierto = true;
        },

        async handleImageUpload(event, type = 'portada') {

            let file = event.target.files[0];
            if (!file) return;

            const allowed = ['image/jpeg', 'image/jpg', 'image/png'];

            const maxSizeKB = 1990;
            const maxSizeBytes = maxSizeKB * 1024;

            if (!allowed.includes(file.type)) {

                Swal.fire(
                    'Atención',
                    'Solo se permiten imágenes .jpg, .jpeg o .png',
                    'warning'
                );

                event.target.value = '';
                return;
            }

            if (file.size > maxSizeBytes) {

                await Swal.fire({
                    title: 'Imagen pesada',
                    text: 'La imagen supera los 1990 KB (1.9 MB). Se intentará comprimir automáticamente, lo que podría reducir ligeramente la calidad.',
                    icon: 'info',
                    confirmButtonText: 'Entendido'
                });

                try {

                    const originalSizeKB = (file.size / 1024).toFixed(2);

                    const options = {
                        maxSizeMB: 1.9,
                        maxWidthOrHeight: 1920,
                        useWebWorker: true,
                    };

                    file = await imageCompression(file, options);

                    if (!file.name || file.name === 'blob') {
                        file = new File([file], event.target.files[0].name, { type: file.type });
                    }

                    const compressedSizeKB = (file.size / 1024).toFixed(2);

                    if (file.size > maxSizeBytes) {
                        Swal.fire(
                            'No se pudo procesar',
                            'Incluso después de la compresión, la imagen sigue superando el límite permitido.',
                            'warning'
                        );

                        event.target.value = '';
                        return;
                    }

                } catch (error) {
                    Swal.fire(
                        'Error',
                        'Ocurrió un problema al comprimir la imagen.',
                        'error'
                    );
                    event.target.value = '';
                    return;
                }
            }

            const reader = new FileReader();
            const _this = this;

            reader.onload = (e) => {

                if (type === 'portada') {
                    _this.imagePreviewPortada = e.target.result;
                    _this.imageFilePortada = file;
                } else {
                    _this.imagePreviewAfiche = e.target.result;
                    _this.imageFileAfiche = file;
                }

            };

            reader.readAsDataURL(file);
        },

        async analizarExamenWord() {
            if (!this.archivoWord) return;

            const extension = this.archivoWord.name.split('.').pop().toLowerCase();
            if (extension === 'doc' || extension === 'dot') {
                Swal.fire({
                    title: 'Formato Deprecado',
                    html: `El archivo <b>.${extension}</b> es un formato antiguo de Word.<br><br>Para usar la extracción por estilos, por favor abre el archivo y <b>Guárdalo como .docx</b> antes de subirlo.`,
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#3b82f6'
                });
                return;
            }
            this.cargandoWord = true;
            try {
                const formData = new FormData();
                formData.append('archivo', this.archivoWord);

                const res = await axios.post(`${VITE_URL_APP}/api/capacitacion/procesar-examen-word`, formData, {
                    headers: { 'Content-Type': 'multipart/form-data' }
                });

                if (res.data.success) {
                    this.preguntasExamen = res.data.preguntas;

                    if (res.data.metrics) {
                        this.wordMetrics = {
                            tokensInput: res.data.metrics.tokens_input,
                            tokensOutput: res.data.metrics.tokens_output,
                            tokensTotal: res.data.metrics.tokens_total,
                            costoUSD: res.data.metrics.costo_usd,
                            tiempoSeg: res.data.metrics.tiempo_seg
                        };
                    }

                    const count = res.data.preguntas.length;
                    this.cantidadPreguntas = count;
                    this.preguntasBalotario = count;

                    this.verVistaPrevia();
                } else {
                    Swal.fire('Error', res.data.message, 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'No se pudo procesar el archivo Word', 'error');
            } finally {
                this.cargandoWord = false;
            }
        },

        verVistaPrevia() {
            if (this.preguntasExamen.length === 0) {
                Swal.fire('Sin preguntas', 'Primero debe analizar un archivo Word.', 'info');
                return;
            }
            window.dispatchEvent(new CustomEvent('abrir-modal-word', {
                detail: {
                    preguntas: this.preguntasExamen,
                    cursoId: this.codigo,
                    examenId: document.getElementById('codGestionEditar')?.value || -1,
                    nombreArc: this.archivoWordNombre,
                    metrics: this.wordMetrics
                }
            }));
        },

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

        clientesDisponibles: [],
        clienteSeleccionado: '',
        empresasDisponibles: [],
        areasAsignadas: [],
        busquedaCliente: '',
        busquedaEmpresa: '',
        busquedaAreaPCI: '',

        get areasPCIFiltradas() {
            if (!this.busquedaAreaPCI) return this.areasEncargadas;
            const search = this.busquedaAreaPCI.toLowerCase();
            return this.areasEncargadas.filter(ar =>
                (ar.descripcion || '').toLowerCase().includes(search) ||
                (ar.codigo || '').toLowerCase().includes(search)
            );
        },

        get clientesFiltrados() {
            if (!this.busquedaCliente) return this.clientesDisponibles;
            const search = this.busquedaCliente.toLowerCase();
            return this.clientesDisponibles.filter(clie =>
                (clie.descripcion || '').toLowerCase().includes(search) ||
                (clie.codigo || '').toLowerCase().includes(search)
            );
        },

        get empresasFiltradas() {
            if (!this.busquedaEmpresa) return this.empresasDisponibles;
            const search = this.busquedaEmpresa.toLowerCase();
            return this.empresasDisponibles.filter(emp =>
                (emp.descripcion || '').toLowerCase().includes(search) ||
                (emp.codigo || '').toLowerCase().includes(search)
            );
        },

        init() {
            this.$watch('areaConocimiento', (val) => {
                if (this.tipoCurso != '6') {
                    this.cargarAreasResponsables(val);
                }
            });

            this.$watch('tipoCurso', (val) => {
                if (val == '6') {
                    this.areaConocimiento = '';
                    this.area = '';
                    this.cargarAreasResponsablesPCA();
                } else {
                    this.areasResponsables = [];
                    this.areaResponsable = '';
                    this.lastSistemaId = null;
                }
            });
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

            // Cargar áreas encargadas (AV_AREA)
            axios.get(`${VITE_URL_APP}/api/get-areas-encargadas`)
                .then(res => {
                    this.areasEncargadas = res.data;
                    window.dispatchEvent(new CustomEvent('areas-encargadas-loaded', { detail: res.data }));
                })
                .catch(err => {
                    console.error("Error al cargar áreas encargadas", err);
                });

            // Cargar Clientes para PCU
            axios.get(`${VITE_URL_APP}/api/get-clientes-pac`)
                .then(res => {
                    this.clientesDisponibles = res.data || [];
                });

            // Cargar Empresas para PCI
            axios.get(`${VITE_URL_APP}/api/get-empresas`)
                .then(res => {
                    this.empresasDisponibles = res.data || [];
                });

            // Eliminado el bloque de exclusión mutua ($watch) entre obligatorioAlta y esDemanda,
            // ya que ahora obligatorioAlta siempre es true y esDemanda siempre es false por requerimiento.

            this.$watch('frecuencia', (val) => {
                // Mantenemos otras lógicas de frecuencia si existen
            });
        },

        // checkEsPAC() {
        //     // Depurado: Esto funcionaba con el select, pero ahora preferimos checkEsPACByText para radios
        // },

        get formularioCompleto() {
            const nombreOk = this.nombre?.trim().length > 0;
            const tipoOk = this.tipoCurso !== undefined && this.tipoCurso !== null && this.tipoCurso !== '';
            const responsableOk = this.codResponsable?.length > 0;
            const areaResponsableOk = this.areaResponsable !== undefined && this.areaResponsable !== null && this.areaResponsable !== '';
            const dirigidoOk = this.dirigido?.length > 0;
            const areaOk = this.tipoCurso == '6' || (this.areaConocimiento !== undefined && this.areaConocimiento !== null && this.areaConocimiento !== '');

            let examenOk = true;
            if (this.aplicaEvaluacion) {
                examenOk = parseInt(this.limiteTiempo) >= 5
                    && parseFloat(this.nota) >= 5
                    && parseInt(this.intentos) >= 1
                    && parseInt(this.cantidadPreguntas) >= 5
                    && parseInt(this.preguntasBalotario) >= 5;
            }

            return nombreOk && tipoOk && responsableOk && areaResponsableOk && dirigidoOk && areaOk && examenOk;
        },

        get tituloCamposFaltantes() {
            const faltantes = [];

            if (!this.nombre?.trim()) faltantes.push('Nombre del curso');
            if (!this.codResponsable) faltantes.push('Responsable');
            if (this.tipoCurso === undefined || this.tipoCurso === null || this.tipoCurso === '') faltantes.push('Plan de capacitación');
            if (this.areaResponsable === undefined || this.areaResponsable === null || this.areaResponsable === '') faltantes.push('Área responsable');
            if (!this.dirigido) faltantes.push('Dirigido a');
            if (this.tipoCurso != '6' && (this.areaConocimiento === undefined || this.areaConocimiento === null || this.areaConocimiento === '')) faltantes.push('Área de conocimiento');

            if (this.aplicaEvaluacion) {
                if (parseInt(this.limiteTiempo) < 5) faltantes.push('Tiempo de examen (mín. 5 min)');
                if (parseFloat(this.nota) < 5) faltantes.push('Nota mínima (mín. 5)');
                if (parseInt(this.intentos) < 1) faltantes.push('Intentos (mín. 1)');
                if (parseInt(this.cantidadPreguntas) < 5) faltantes.push('Cant. preguntas (mín. 5)');
                if (parseInt(this.preguntasBalotario) < 5) faltantes.push('Balotario (mín. 5)');
            }

            if (faltantes.length === 0) return 'Completa los campos requeridos';

            return 'Faltan: ' + faltantes.join(', ');
        },

        checkEsPACByText(text) {
            if (!text) return;
            this.esPAC = text.toUpperCase().includes('PAC');
            if (!this.esPAC) {
                this.sucursalesAsignadas = [];
            }
        },

        limpiarCampos() {
            this.codigo = '';
            this.nombre = '';
            this.tipoCurso = '5';
            this.areaConocimiento = '';
            this.area = '';
            this.areaResponsable = '';
            this.tipoResponsable = 'ADMINISTRATIVO_5';
            this.codResponsable = '';
            this.nombreResponsable = '';
            this.lastSistemaId = null;
            this.areasResponsables = [];
            this.codMoodleArea = '';
            this.frecuencia = '';
            this.fechaInicio = '';
            this.fechaFinal = '';
            this.dirigido = '';
            this.limiteTiempo = '30';
            this.nota = '10';
            this.intentos = '1';
            this.cantidadPreguntas = '1';
            this.preguntasBalotario = '1';
            this.esPAC = false;
            this.sucursalesAsignadas = [];
            this.busquedaSucursal = '';
            this.clienteSeleccionado = '';
            this.areasAsignadas = [];
            this.busquedaCliente = '';
            this.busquedaAreaPCI = '';
            this.busquedaEmpresa = '';
            this.descripcion = '';

            // Word 2026: limpiar preguntas cargadas
            this.archivoWord = null;
            this.archivoWordNombre = '';
            this.preguntasExamen = [];

            this.imageFilePortada = null;
            this.imagePreviewPortada = null;
            this.imageFileAfiche = null;
            this.imagePreviewAfiche = null;
            const portInput = document.getElementById('inputImagePortada');
            const aficInput = document.getElementById('inputImageAfiche');
            if (portInput) portInput.value = '';
            if (aficInput) aficInput.value = '';

            this.aplicaEvaluacion = true;
            this.obligatorioAlta = true; // Forzado a true por requerimiento
            this.esDemanda = false;

            this.targetGroup = 'TODOS';

            // Forzar limpieza de inputs de archivos y estados visuales
            const wordInput = document.getElementById('inputWordExamen');
            if (wordInput) wordInput.value = '';

            const excelInput = document.getElementById('inputExcelMatricula');
            if (excelInput) excelInput.value = '';

            archivoSeleccionado = null;
            if (typeof resumenPlantilla !== 'undefined' && resumenPlantilla) resumenPlantilla.innerHTML = "";
            if (typeof btnAnalizar !== 'undefined' && btnAnalizar) btnAnalizar.disabled = true;
        },

        async registrar(e) {
            e?.preventDefault();

            // Validación PAC General
            if (this.esPAC && this.sucursalesAsignadas.length === 0) {
                Swal.fire('Atención', 'Debe asignar al menos una sucursal para cursos PAC', 'warning');
                return;
            }

            // Validaciones específicas PCU (6) y PCI (7)
            if (this.tipoCurso == '6' && !this.clienteSeleccionado) {
                Swal.fire('Atención', 'Debe seleccionar un cliente para cursos PCU', 'warning');
                return;
            }
            if (this.tipoCurso == '7' && this.areasAsignadas.length === 0) {
                Swal.fire('Atención', 'Debe asignar al menos un área operativa para cursos PCI', 'warning');
                return;
            }

            if (!this.codResponsable) {
                Swal.fire('Atención', 'Debe seleccionar un responsable para el curso', 'warning');
                return;
            }

            if (!this.areaResponsable) {
                Swal.fire('Atención', 'Debe seleccionar un área responsable', 'warning');
                return;
            }

            if (!this.dirigido) {
                Swal.fire('Atención', 'Debe seleccionar a quién va dirigido el curso', 'warning');
                return;
            }

            const camposObligatorios = this.tipoCurso == '6'
                ? ['nombre', 'tipoCurso']
                : ['nombre', 'tipoCurso', 'areaConocimiento'];

            const vacio = camposObligatorios.some(campo => {
                const valor = this[campo];
                return valor === null || valor === undefined || valor === '';
            });

            if (vacio) {
                Swal.fire('Atención', 'Completar los campos obligatorios (*)', 'warning')
                return
            }

            // Validación separada para campos del examen (mínimos según requerimientos)
            if (this.aplicaEvaluacion) {
                const tiempoOk = parseInt(this.limiteTiempo) >= 5;
                const notaOk = parseFloat(this.nota) >= 5;
                const intentosOk = parseInt(this.intentos) >= 1;
                const preguntasOk = parseInt(this.cantidadPreguntas) >= 5;
                const balotarioOk = parseInt(this.preguntasBalotario) >= 5;
                if (!tiempoOk || !notaOk || !intentosOk || !preguntasOk || !balotarioOk) {
                    Swal.fire('Atención', 'Complete correctamente los datos del examen: Tiempo (mín. 5 min), Nota mínima (mín. 5), Intentos (mín. 1), Cant. preguntas (mín. 5) y Balotario (mín. 5)', 'warning');
                    return;
                }
            }

            if (this.aplicaEvaluacion && this.archivoWord && this.preguntasExamen.length === 0) {
                const confirm = await Swal.fire({
                    title: 'Archivo sin analizar',
                    text: 'Subiste un archivo Word pero no lo analizaste. ¿Deseas continuar sin cargar las preguntas?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, continuar',
                    cancelButtonText: 'Cancelar'
                });
                if (!confirm.isConfirmed) return;
            }

            const formData = new FormData();
            formData.append('nombre', this.nombre);
            formData.append('tipo_curso', this.tipoCurso);
            formData.append('area_conocimiento', this.areaConocimiento);
            formData.append('area', this.area);
            formData.append('frecuencia', this.frecuencia);
            formData.append('es_periodico', this.frecuencia ? 1 : 0);

            formData.append('aplica_evaluacion', this.aplicaEvaluacion ? 1 : 0);
            formData.append('obligatorio_alta', this.obligatorioAlta ? 1 : 0);
            formData.append('es_demanda', this.esDemanda ? 1 : 0);
            formData.append('target_group', this.targetGroup || 'TODOS');

            // Campos del examen solo cuando aplica evaluación
            if (this.aplicaEvaluacion) {
                formData.append('tiempo', this.limiteTiempo);
                formData.append('nota', this.nota);
                formData.append('intentos', this.intentos);
                formData.append('cantidad_preguntas', this.cantidadPreguntas);
                formData.append('preguntas_balotario', this.preguntasBalotario);

                if (this.preguntasExamen.length > 0) {
                    formData.append('preguntas_word', JSON.stringify(this.preguntasExamen));
                }
            }

            if (this.esPAC && this.sucursalesAsignadas.length > 0) {
                this.sucursalesAsignadas.forEach(suc => formData.append('sucursales_asignadas[]', suc));
            }

            if (this.tipoCurso == '6' && this.clienteSeleccionado) {
                formData.append('sucursales_asignadas[]', this.clienteSeleccionado);
            }

            if (this.tipoCurso == '7' && this.areasAsignadas.length > 0) {
                this.areasAsignadas.forEach(a => formData.append('sucursales_asignadas[]', a));
            }

            formData.append('cod_responsable', this.codResponsable);
            formData.append('area_responsable', this.areaResponsable);
            formData.append('cod_moodle_area', this.codMoodleArea);
            formData.append('descripcion', this.descripcion);
            formData.append('dirigido_a', this.dirigido);

            if (this.archivoWord) {
                formData.append('archivo', this.archivoWord);
            }

            if (this.imageFilePortada) {
                formData.append('image_portada', this.imageFilePortada);
            }

            if (this.imageFileAfiche) {
                formData.append('image_afiche', this.imageFileAfiche);
            }

            Swal.fire({
                title: 'Registrando curso...',
                html: 'Por favor espera mientras se procesa la información.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

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
                            frecuencia: "",
                            limiteTiempo: 10,
                            nota: 10,
                            intentos: 1,
                            descripcion: ""
                        };

                        // Reset Alpine Data
                        Object.assign(this, valoresPorDefecto);
                        this.limpiarCampos();

                        await listarCursos();
                        restaurarFormCurso(false);

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
                        <td class="${textCol}">${prog.codigo}</td>
                        <td class="text-sm font-medium ${textCol}">${fInicio} - ${fFin} ${badge}</td>
                        <td>
                            <div class="flex justify-center gap-3">
                                <button class="btn btn-sm rounded-full bg-info/25 text-info hover:bg-info hover:text-white transition-colors duration-200 shadow-sm" 
                                    onclick="editarProgramacion('${prog.codigo}', '${prog.tipo}', '${prog.periodo}', '${prog.fecha_inicio}', '${prog.fecha_final}')"
                                    title="Editar">
                                    <i class="bx bx-edit text-base"></i>
                                </button>
                                <button class="btn btn-sm rounded-full bg-danger/25 text-danger hover:bg-danger hover:text-white transition-colors duration-200 shadow-sm" 
                                    onclick="eliminarProgramacion('${prog.codigo}', '${codigoCurso}')"
                                    title="Eliminar">
                                    <i class="bx bx-trash text-base"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="3" class="text-center text-gray-500 py-4">No hay programaciones registradas</td></tr>';
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
                cod_curso: this.codigoCurso,
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

window.searchablePersonnel = function () {
    return {
        open: false,
        query: '',
        results: [],
        jefaturasCache: [],
        loading: false,
        error: null,
        toggle() {
            this.open = !this.open;
            if (this.open) {
                if (this.jefaturasCache.length === 0) {
                    this.cargarJefaturas();
                } else if (this.query.length > 0) {
                    this.filtrarLocal();
                }
            }
        },
        cargarJefaturas() {
            this.loading = true;
            this.error = null;
            axios.get(`${VITE_URL_APP}/api/listar-jefaturas`)
                .then(res => {
                    this.jefaturasCache = res.data.personal || [];
                    this.filtrarLocal();
                })
                .catch(err => {
                    console.error(err);
                    this.error = 'Error al cargar jefaturas. Verifique la consola.';
                })
                .finally(() => this.loading = false);
        },
        filtrarLocal() {
            const q = this.query.toLowerCase().trim();
            if (!q) {
                this.results = this.jefaturasCache;
            } else {
                this.results = this.jefaturasCache.filter(p =>
                    (p.nombre_completo || '').toLowerCase().includes(q) ||
                    (p.dni || '').includes(q) ||
                    (p.area || '').toLowerCase().includes(q)
                );
            }
        },
        search() {
            this.filtrarLocal();
        },
        select(p) {
            const formElement = document.querySelector('[x-data^="formCursoGestion"]');
            if (formElement && window.Alpine) {
                const alpineData = Alpine.$data(formElement);
                alpineData.codResponsable = p.codigo;
                alpineData.nombreResponsable = p.nombre_completo;
            }
            this.open = false;
        }
    }
}

window.abrirModalAplazarCurso = function(courseId, courseName) {
    window.dispatchEvent(new CustomEvent('open-aplazar-modal', {
        detail: { codigo: courseId, nombre: courseName }
    }));
};

window.modalAplazarCurso = function() {
    return {
        cursoNombre: '',
        cursoCodigo: '',
        programacionActual: null,
        fechaNuevaFin: '',
        cargando: false,
        guardando: false,
        errorFecha: '',
        errorAPI: '',
        fechaValida: false,

        get fechaMinima() {
            if (this.programacionActual && this.programacionActual.fecha_final) {
                const partes = this.programacionActual.fecha_final.split(' ')[0].split('-');
                return `${partes[0]}-${partes[1]}-${partes[2]}`;
            }
            return new Date().toISOString().split('T')[0];
        },

        get diasExtension() {
            if (!this.fechaNuevaFin || !this.programacionActual) return 0;
            const fechaFinActual = new Date(this.programacionActual.fecha_final.split(' ')[0]);
            const fechaNueva = new Date(this.fechaNuevaFin);
            const diff = Math.ceil((fechaNueva - fechaFinActual) / (1000 * 60 * 60 * 24));
            return diff > 0 ? diff : 0;
        },

        init() {
            this.$watch('fechaNuevaFin', () => this.validarFecha());
        },

        formatearFecha(fechaStr) {
            if (!fechaStr) return '-';
            const partes = fechaStr.split(' ')[0].split('-');
            return `${partes[2]}/${partes[1]}/${partes[0]}`;
        },

        validarFecha() {
            this.errorFecha = '';
            this.fechaValida = false;
            if (!this.fechaNuevaFin || !this.programacionActual) return;

            const fechaFinActual = new Date(this.programacionActual.fecha_final.split(' ')[0]);
            const fechaNueva = new Date(this.fechaNuevaFin);

            fechaFinActual.setHours(0, 0, 0, 0);
            fechaNueva.setHours(0, 0, 0, 0);

            if (fechaNueva <= fechaFinActual) {
                this.errorFecha = 'La nueva fecha de fin debe ser posterior a la fecha actual de fin.';
                return;
            }
            this.fechaValida = true;
        },

        openModal(data) {
            this.cursoCodigo = data.codigo;
            this.cursoNombre = data.nombre;
            this.programacionActual = null;
            this.fechaNuevaFin = '';
            this.errorFecha = '';
            this.errorAPI = '';
            this.fechaValida = false;
            this.cargando = true;
            this.guardando = false;

            window.dispatchEvent(new CustomEvent('cambiar-panel', {
                detail: { panel: 'aplazar_curso', titulo: this.cursoNombre }
            }));

            this.cargarProgramacionActual();
        },

        closeModal() {
            window.dispatchEvent(new CustomEvent('cambiar-panel', {
                detail: { panel: 'registro' }
            }));
            this.cursoCodigo = '';
            this.cursoNombre = '';
            this.programacionActual = null;
            this.fechaNuevaFin = '';
            this.cargando = false;
            this.guardando = false;
            this.errorFecha = '';
            this.errorAPI = '';
            this.fechaValida = false;
        },

        async cargarProgramacionActual() {
            this.errorAPI = '';
            try {
                const res = await axios.get(`${VITE_URL_APP}/api/cursos/obtener-prog-actual/${this.cursoCodigo}`);
                if (res.data.success && res.data.data) {
                    this.programacionActual = res.data.data;
                } else {
                    this.errorAPI = res.data.message || 'No se encontró programación actual para este curso.';
                    this.programacionActual = null;
                }
            } catch (err) {
                console.error('Error al obtener programación actual:', err);
                this.errorAPI = 'Error al consultar la programación actual del curso.';
                this.programacionActual = null;
            } finally {
                this.cargando = false;
            }
        },

        async guardarExtension() {
            if (!this.fechaValida || !this.fechaNuevaFin) return;
            this.guardando = true;
            this.errorAPI = '';
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const headers = { 'Content-Type': 'application/json' };
                if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

                const res = await axios.post(`${VITE_URL_APP}/api/cursos/aplazar-curso`, {
                    cod_curso: this.cursoCodigo,
                    nueva_fecha_final: this.fechaNuevaFin
                }, { headers });

                if (res.data.success) {
                    window.dispatchEvent(new CustomEvent('mostrar-alerta', {
                        detail: {
                            mensaje: res.data.message || 'Plazo extendido correctamente.',
                            tipo: 'success',
                            toast: true,
                            recargar: true
                        }
                    }));
                    this.closeModal();
                } else {
                    this.errorAPI = res.data.message || 'No se pudo extender el plazo.';
                }
            } catch (err) {
                console.error('Error al aplazar curso:', err);
                this.errorAPI = err.response?.data?.message || 'Error de conexión al servidor.';
            } finally {
                this.guardando = false;
            }
        }
    };
};

