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

btnSeleccionar.addEventListener("click", () => {
    archivoInput.click();
});

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
    btnAnalizar.disabled = false;
    archivoInput.value = "";
});


btnAnalizar.addEventListener("click", async () => {
    if (!archivoSeleccionado) return;

    const formData = new FormData();
    formData.append("plantilla", archivoSeleccionado);

    try {
      btnAnalizar.disabled = true;
      btnAnalizar.textContent = "Analizando...";

      const res = await axios.post(
          `${VITE_URL_APP}/api/cursos/analizar-plantilla`,
          formData,
          { headers: { "Content-Type": "multipart/form-data" } }
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
      console.error("Error al analizar plantilla", err);
      resumenPlantilla.innerHTML = `<p class="text-red-600">Error al analizar plantilla</p>`;
  } finally {
      btnAnalizar.disabled = false;
      btnAnalizar.textContent = "Analizar Plantilla";
  }


    // try {
    //     btnAnalizar.disabled = true;
    //     btnAnalizar.textContent = "Analizando...";

    //     const res = await axios.post(`${VITE_URL_APP}/api/cursos/analizar-plantilla`, formData, {
    //         headers: { "Content-Type": "multipart/form-data" },
    //     });

    //     console.log("Respuesta backend:", res.data);
    //     resumenPlantilla.innerHTML = `<pre>${JSON.stringify(res.data, null, 2)}</pre>`;
    // } catch (err) {
    //     console.error("Error al analizar plantilla", err);
    //     resumenPlantilla.innerHTML = `<p class="text-red-600">Error al analizar plantilla</p>`;
    // } finally {
    //     btnAnalizar.disabled = false;
    //     btnAnalizar.textContent = "Analizar Plantilla";
    // }
});


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
        document.getElementById("btnQuitar").addEventListener("click", () => {
            archivoSeleccionado = null;
            actualizarLista();
            resumenPlantilla.innerHTML = "";
            btnAnalizar.disabled = true;
        });
    }
}

document.addEventListener('DOMContentLoaded', async () => {
  
  await listarTipoCurso("slcTipoCurso")
  await listarTipoCurso("slcFiltroTipoCurso", true)
  await listarAreas("slcArea")
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


// async function listarCursos() {
//   try {
//     const res = await axios.get(`${VITE_URL_APP}/api/get-cursos/1`)
//     cursosData = res.data
//     renderTablaCursos(cursosData)
//   } catch (err) {
//     console.error("Error al obtener cursos", err)
//     Swal.fire("Error", "No se pudieron cargar los cursos", "error")
//   }
// }


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


// async function listarTipoCurso() {
//   try {
//     const res = await axios.get(`${VITE_URL_APP}/api/get-capacitacion-tipo-cursos`)
//     const tipoCursosData = res.data
//     const select = document.getElementById("slcTipoCurso");

//     select.innerHTML = '<option value="">-- Seleccione --</option>';

//     tipoCursosData.forEach(curso => {
//       const option = document.createElement("option");
//       option.value = curso.codigo;
//       option.textContent = curso.descripcion;
//       select.appendChild(option);
//     });
//   } catch (err) {
//     console.error("Error al obtener tipos de cursos", err)
//     Swal.fire("Error", "No se pudieron cargar los tipos de cursos", "error")
//   }
// }


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


// async function listarAreas() {
//   try {
//     const res = await axios.get(`${VITE_URL_APP}/api/get-capacitacion-areas`)
//     const areasData = Array.isArray(res.data) ? res.data : [];

//     const select = document.getElementById("slcArea");
//     select.innerHTML = '<option value="">-- Seleccione --</option>';

//     if (areasData.length === 0) {
//         console.warn("No hay áreas disponibles");
//     } else {
//         areasData.forEach(area => {
//             const option = document.createElement("option");
//             option.value = area.codigo;
//             option.textContent = area.descripcion;
//             select.appendChild(option);
//         });
//     }

//   } catch (err) {
//     console.error("Error al obtener las áreas", err)
//     Swal.fire("Error", "No se pudieron cargar las áreas", "error")
//   }
// }

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
        <!-- <td style="display: none;" hidden>${curso.periodo_inicio ?? ''}&nbsp;<i class='bx bx-right-arrow-alt'></i>&nbsp;${curso.periodo_fin ?? ''} </td> -->
        <td>
            <button type="button"  @click="gestionCurso('EDIT', '${ curso.codigo }')"
            class="me-3 btn rounded-full  bg-info/25 text-info hover:bg-info hover:text-white">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>

            ${curso.habilitado == '1' ?
                `<button type="button"  @click="gestionCurso('DEL', '${ curso.codigo }')"
                class="btn rounded-full bg-danger/25 text-danger hover:bg-danger hover:text-white">
                    <i class="fa-solid fa-trash-can"></i>
                </button>`
                :
                `<button type="button"  @click="gestionCurso('ACT', '${ curso.codigo }')"
                class="btn rounded-full  bg-success/25 text-success hover:bg-success hover:text-white">
                    <i class='bx bx-check' ></i>
                </button>`
            }
        </td>
        `
        tbody.appendChild(tr)
    })
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

window.gestionCurso = async (op, cod) =>{
    if(op === 'EDIT'){
        const dataget = await obtenerCursoXId(cod);

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
            document.getElementById('txtNombreCurso').value = curso.nombre;

            document.getElementById('slcTipoCurso').value = curso.tipo_curso?.codigo ?? "";
            document.getElementById('slcArea').value = curso.area?.codigo ?? "";
            document.getElementById('txtperiodicidad').value = curso.periodicidad ?? "";

            document.getElementById('txtNombreExamen').value = curso.examen.nombre;
            document.getElementById('txtDescripcion').value = curso.examen.descripcion;
            document.getElementById('txtLimite').value = curso.examen.tiempo;
            document.getElementById('txtNota').value = curso.examen.nota_minima;
            document.getElementById('txtIntentos').value = curso.examen.intentos;

            btn.classList.add('hidden');
            btnEdit.classList.remove('hidden');

        } else {
            Swal.fire('Advertencia', 'No se encontró el curso', 'warning');
        }

    }else{
        const data = {
            habilitado: op === 'DEL' ? 0 : 1
        };

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

window.gestionListarCursos = (op) =>{
    if(op === 1){
        listarCursos();
    }else{
        listarCursosFiltro(0);
    }
}

window.editarFormGestionCurso = (e) => {
    if (e) e.preventDefault();

    //  const camposObligatorios = [
    //     'nombre','nombreExa', 'limiteTiempo', 'nota', 'intentos'
    // ];

    // const vacio = camposObligatorios.some(campo => !this[campo]);

    // if (vacio) {
    //     Swal.fire('Atención', 'Completar los campos obligatorios', 'warning')
    //     return
    // }

    // if(!archivoSeleccionado){
    //     Swal.fire('Atención', 'Debe importar la plantilla', 'warning')
    //     return
    // }

    const data = {
        codigo: document.getElementById('codGestionEditar').value,
        nombre: document.getElementById('txtNombreCurso').value,
        tipo_curso: document.getElementById('slcTipoCurso').value,
        area: document.getElementById('slcArea').value,
        periodicidad: document.getElementById('txtperiodicidad').value,
        nombre_exa: document.getElementById('txtNombreExamen').value,
        descripcion: document.getElementById('txtDescripcion').value,
        tiempo: parseInt(document.getElementById('txtLimite').value),
        nota: document.getElementById('txtNota').value,
        intentos: document.getElementById('txtIntentos').value,
    }

    const formData = new FormData();
    formData.append('codigo', data.codigo);
    formData.append('nombre', data.nombre);
    formData.append('tipo_curso', data.tipo_curso);
    formData.append('area', data.area);
    formData.append('periodicidad', data.periodicidad);
    formData.append('nombre_exa', data.nombre_exa);
    formData.append('descripcion', data.descripcion);
    formData.append('tiempo', data.tiempo);
    formData.append('nota', data.nota);
    formData.append('intentos', data.intentos);
    //formData.append('archivo', archivoSeleccionado);

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
        console.error(err)
        Swal.fire('Error', 'Ocurrió un problema al actualizar el curso', 'error')
    })
}

window.restaurarFormCurso = () => {
    const mensaje = document.getElementById('txtMensajeNuevo');
    const view = document.getElementById('viewEditCreate');
    const btn = document.getElementById('btnGestion');
    const btnEdit = document.getElementById('btnGestionEditar');
    const title = document.getElementById('txtTitleFile');
    const btnDownload = document.getElementById('btnDownloadPlantilla');

    mensaje.textContent = 'Nuevo';
    title.textContent = 'Actualizar plantilla';

    mensaje.classList.add('bg-primary/25', 'text-primary-800');
    mensaje.classList.remove('bg-warning/25', 'text-warning-800');

    view.classList.add('hidden');
    view.classList.add('hidden');

    document.getElementById('codGestionEditar').value = '-1';
    document.getElementById('txtNombreCurso').value = '';
    document.getElementById('slcTipoCurso').value = '';
    document.getElementById('slcArea').value = '';
    document.getElementById('txtperiodicidad').value = '';
    document.getElementById('txtNombreExamen').value = '';
    document.getElementById('txtDescripcion').value = '';
    document.getElementById('txtLimite').value = '';
    document.getElementById('txtNota').value = '';
    document.getElementById('txtIntentos').value = '';

    btn.classList.remove('hidden');
    btnEdit.classList.add('hidden');
}

window.formCursoGestion = () => {
    return {
        codigo: '-1',
        nombre: '',
        tipoCurso: '',
        area: '',
        periodicidad: '',
        nombreExa: '',
        descripcion: '',
        limiteTiempo: '',
        nota: '',
        intentos: '',
        fechaActual: new Date().toISOString().split('T')[0],
        registrar(e) {
            e?.preventDefault();

            const camposObligatorios = [
                'nombre','nombreExa', 'limiteTiempo', 'nota', 'intentos'
            ];

            const vacio = camposObligatorios.some(campo => !this[campo]);

            if (vacio) {
                Swal.fire('Atención', 'Completar los campos obligatorios', 'warning')
                return
            }

            if(!archivoSeleccionado){
                Swal.fire('Atención', 'Debe importar la plantilla', 'warning')
                return
            }

            const formData = new FormData();
            formData.append('nombre', this.nombre);
            formData.append('tipo_curso', this.tipoCurso);
            formData.append('area', this.area);
            formData.append('periodicidad', this.periodicidad);
            formData.append('nombre_exa', this.nombreExa);
            formData.append('descripcion', this.descripcion);
            formData.append('tiempo', this.limiteTiempo);
            formData.append('nota', this.nota);
            formData.append('intentos', this.intentos);
            formData.append('archivo', archivoSeleccionado);

            axios.post(`${VITE_URL_APP}/api/save-cursos`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
            .then(async (res) => {
                if (res.status === 200 && res.data.success) {
                    Swal.fire('Éxito', res.data.message || 'Curso registrado correctamente', 'success')

                    const valoresPorDefecto = {
                        nombre: '',
                        tipoCurso: '',
                        area: '',
                        periodicidad: '1',
                        nombreExa: '',
                        descripcion: '',
                        limiteTiempo: '0',
                        nota: '0',
                        intentos: '0',
                        archivo: null
                    }

                    Object.entries(valoresPorDefecto).forEach(([campo, valor]) => {
                        this[campo] = valor
                    })

                    document.getElementById('archivoInput').value='';
                    
                    archivoSeleccionado = null;
                    actualizarLista();
                    resumenPlantilla.innerHTML = "";
                    btnAnalizar.disabled = true;

                    await listarCursos();

                } else {
                    document.getElementById('archivoInput').value='';
                    Swal.fire('Error', res.data.message || 'No se pudo registrar el curso', 'error')
                }
            })
            .catch(err => {
                console.error(err)
                Swal.fire('Error', 'Ocurrió un problema al registrar el curso', 'error')
            })
        }
    }
}

async function analizarArchivo() {
    if (!archivoSeleccionado) return;

    const formData = new FormData();
    formData.append("plantilla", archivoSeleccionado);

    try {
        const res = await axios.post(`${VITE_URL_APP}/api/cursos/analizar-plantilla`, formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        console.log("Respuesta backend:", res.data);
        document.getElementById("resumenPlantilla").innerHTML =
            `<pre>${JSON.stringify(res.data, null, 2)}</pre>`;
    } catch (err) {
        console.error("Error al analizar plantilla", err);
        document.getElementById("resumenPlantilla").innerHTML =
            `<p class="text-red-600">Error al analizar plantilla</p>`;
    }
}


// document.getElementById('btnDescargarPlantilla').addEventListener('click', function() {
//     const url = '/storage/plantilla.docx';
//     window.location.href = url; // fuerza la descarga o apertura según el navegador
// });
