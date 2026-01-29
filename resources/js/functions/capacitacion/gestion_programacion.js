import Swal from "sweetalert2"
import axios from "axios"
import DataTable from "vanilla-datatables"

let cursosData = [];

const cardProg = document.getElementById('cardProgramacion');

window.cursoSeleccionado = null;

document.addEventListener('DOMContentLoaded', async () => {
    await listarTipoCurso('slcFiltroTipoCurso', true)
    await listarAreas('slcFiltroArea', true)
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

async function obtenerProgramacionXId(id) {
  try {
    const res = await axios.get(`${VITE_URL_APP}/api/get-programacion-id/${id}`)
    return res.data;
  } catch (err) {
    console.error("Error al obtener la programación", err)
    Swal.fire("Error", "No se pudo cargar la programación", "error");
    return false;
  }
}

function renderTablaCursos(data) {
  const tbody = document.querySelector("#tblCursos tbody")
  if (!tbody) return

  tbody.innerHTML = ""

  if(data.length > 0){
    data.forEach((curso, index) => {
        const tr = document.createElement("tr");
        tr.style.backgroundColor = curso.habilitado == '1' ? "" : '#fff1f1';
        tr.innerHTML = `
        <td>${index + 1}</td>
         <td>${curso.codigoCurso}</td>
        <td>${curso.nombre}</td>
        <td>${curso.periodicidad ?? 'No definido'}</td>
        <!-- <td style="display: none;" hidden>${curso.periodo_inicio ?? ''}&nbsp;<i class='bx bx-right-arrow-alt'></i>&nbsp;${curso.periodo_fin ?? ''} </td> -->
        <td>
            <button type="button" @click="programacionCurso('${ curso.codigo }', '${ curso.nombre }')"
            class="btn rounded-full bg-success/25 text-success hover:bg-success hover:text-white">
                <i class='bx bxs-calendar-edit'></i>
            </button>
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

function formatFecha(fecha) {
    if (!fecha) return '';
    const d = new Date(fecha.replace(' ', 'T'));
    if (isNaN(d)) return fecha;
    const dia = String(d.getDate()).padStart(2, '0');
    const mes = String(d.getMonth() + 1).padStart(2, '0');
    const anio = d.getFullYear();
    return `${dia}/${mes}/${anio}`;
}

function renderTablaProgramaciones(data) {

  console.log('Datos de programaciones:', data);

  const tbody = document.querySelector("#tblProgramacion tbody")
  if (!tbody) return

  tbody.innerHTML = ""

  if(data.length > 0){
    data.forEach((programacion, index) => {
        const tr = document.createElement("tr");
        tr.style.backgroundColor = programacion.habilitado == '1' ? "" : '#fff1f1';
        tr.innerHTML = `
        <td>${index + 1}</td>
        <td>${programacion.codigo_programacion}</td>
        <td>${formatFecha(programacion.fecha_inicio)} - ${formatFecha(programacion.fecha_final)}</td>
        <!-- <td style="display: none;" hidden>${programacion.periodo_inicio ?? ''}&nbsp;<i class='bx bx-right-arrow-alt'></i>&nbsp;${programacion.periodo_fin ?? ''} </td> -->
        <td>
            <button type="button"  @click="gestionProgramacion('EDIT', '${ programacion.codigo }')"
            class="me-3 btn rounded-full  bg-info/25 text-info hover:bg-info hover:text-white">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>

            ${programacion.habilitado == '1' ?
                `<button type="button"  @click="gestionProgramacion('DEL', '${ programacion.codigo }')"
                class="btn rounded-full bg-danger/25 text-danger hover:bg-danger hover:text-white">
                    <i class="fa-solid fa-trash-can"></i>
                </button>`
                :
                `<button type="button"  @click="gestionProgramacion('ACT', '${ programacion.codigo }')"
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


window.gestionProgramacion = async (op, cod) => {
    if (op === 'EDIT') {
        try {
            //const res = await axios.get(`${VITE_URL_APP}/api/get-programacion/${cod}`);
            const data = await obtenerProgramacionXId(cod);

          console.log('Datos obtenidos para edición:', data);
            
            if (data.success && data.programacion) {
                const prog = data.programacion;

                // Asignamos valores al componente Alpine
                const alpineComponent = document.querySelector('[x-data="formProgramacionGestion()"]')?._x_dataStack?.[0];
                if (alpineComponent) {
                  console.log('Asignando valores al componente Alpine con codigo:', prog.codigo);
                    alpineComponent.codigo = prog.codigo;
                    alpineComponent.codigoCurso = prog.cod_cursos;
                    alpineComponent.fechaInicio = prog.fecha_inicio.split(' ')[0];
                    alpineComponent.fechaFinal = prog.fecha_final.split(' ')[0];
                    alpineComponent.habilitado = prog.habilitado;
                }

                // Abrir modal
                abrirModalEdit();

            } else {
                Swal.fire('Advertencia', 'No se encontró la programación', 'warning');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('Error', 'Ocurrió un problema al obtener la programación', 'error');
        }

    } else {
        const data = {
            habilitado: op === 'DEL' ? 0 : 1
        };

        axios.patch(`${VITE_URL_APP}/api/programaciones/${cod}/habilitado`, data)
        .then(async (res) => {
            if (res.status === 200 && res.data.success) {
                Swal.fire('Éxito', res.data.message || (op === 'DEL' ? 'Programación eliminada' : 'Programación habilitada'), 'success')
                if (window.cursoSeleccionado) {
                    const resProg = await axios.get(`${VITE_URL_APP}/api/get-curso-programacion/${window.cursoSeleccionado}`);
                    renderTablaProgramaciones(resProg.data.programaciones || []);
                }
            } else {
                Swal.fire('Error', res.data.message || 'No se pudo actualizar la programación', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Ocurrió un problema al actualizar la programación', 'error');
        });
    }
}

window.programacionCurso = async (cod, name) => {

    window.cursoSeleccionado = cod;
    cardProg.classList.remove('hidden');
    document.getElementById('txtTituloCurso').textContent = name;
    document.getElementById('nombreCurso').value = name;

    try {
        const res = await axios.get(`${VITE_URL_APP}/api/get-curso-programacion/${cod}`);
        
        console.log(res.data.message);

        if (res.status === 200 && res.data.success) {
            const programaciones = res.data.programaciones || [];
            renderTablaProgramaciones(programaciones);
        } else {
            Swal.fire('Error', res.data.message || 'No se pudo cargar las programaciones', 'error');
        }
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'Ocurrió un problema al actualizar el curso', 'error');
    }
}


window.formProgramacionGestion = () => {
    return {
        codigo: '-1',
        codigoCurso: '',
        fechaInicio: '',
        fechaFinal: '',
        habilitado: '1',
        fechaActual: new Date().toISOString().split('T')[0],
        limpiarCampos() {
            this.codigo = '-1';
            this.codigoCurso = '';
            this.fechaInicio = '';
            this.fechaFinal = '';
            this.habilitado = '1';
        },
        registrar(e) {
            e?.preventDefault();

            const camposObligatorios = [
                'fechaInicio', 'fechaFinal'
            ];

            const vacio = camposObligatorios.some(campo => !this[campo]);

            if (vacio) {
                Swal.fire('Atención', 'Completar los campos obligatorios', 'warning')
                return
            }

            const formData = new FormData();
            formData.append('cod_cursos', this.codigoCurso || window.cursoSeleccionado);
            formData.append('fecha_inicio', this.fechaInicio);
            formData.append('fecha_final', this.fechaFinal);
            formData.append('habilitado', this.habilitado);

            axios.post(`${VITE_URL_APP}/api/save-programacion`, formData)
            .then(async (res) => {
                if (res.status === 200 && res.data.success) {
                    Swal.fire('Éxito', res.data.message || 'Programación registrada correctamente', 'success')

                    this.limpiarCampos();

                    // Cerrar modal (si usas HS Overlay)
                    if (window.HSOverlay) {
                        window.HSOverlay.close(document.getElementById('modal-registro'));
                    }

                    // Listar programaciones del curso seleccionado
                    if (window.cursoSeleccionado) {
                        const resProg = await axios.get(`${VITE_URL_APP}/api/get-curso-programacion/${window.cursoSeleccionado}`);
                        const programaciones = resProg.data.programaciones || [];
                        renderTablaProgramaciones(programaciones);
                    }

                } else {
                    Swal.fire('Error', res.data.message || 'No se pudo registrar la programación', 'error')
                }
            })
            .catch(err => {
                console.error(err)
                if (err.response && err.response.data && err.response.data.message) {
                    Swal.fire('Error', err.response.data.message, 'error');
                } else {
                    Swal.fire('Error', 'Ocurrió un problema al registrar la programación', 'error');
                }
            })
        }
    }
}


window.actualizarProgramacion = async (e, alpineComponent) => {
    if (e) e.preventDefault();

    // Validar campos obligatorios
    const camposObligatorios = ['fechaInicio', 'fechaFinal'];
    const vacio = camposObligatorios.some(campo => !alpineComponent[campo]);

    if (vacio) {
        Swal.fire('Atención', 'Completar los campos obligatorios', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('codigo', alpineComponent.codigo);
    formData.append('cod_cursos', alpineComponent.codigoCurso || window.cursoSeleccionado);
    formData.append('fecha_inicio', alpineComponent.fechaInicio);
    formData.append('fecha_final', alpineComponent.fechaFinal);
    formData.append('habilitado', alpineComponent.habilitado);

    try {
        const res = await axios.post(
            `${VITE_URL_APP}/api/update-programacion`,
            formData,
            { headers: { 'Content-Type': 'multipart/form-data' } }
        );

        if (res.status === 200 && res.data.success) {
            Swal.fire('Éxito', res.data.message || 'Programación actualizada correctamente', 'success');

            // Cerrar modal
            if (window.HSOverlay) {
                window.HSOverlay.close(document.getElementById('modal-registro'));
            }

            // Recargar tabla
            if (window.cursoSeleccionado) {
                const resProg = await axios.get(`${VITE_URL_APP}/api/get-curso-programacion/${window.cursoSeleccionado}`);
                renderTablaProgramaciones(resProg.data.programaciones || []);
            }
        } else {
            Swal.fire('Error', res.data.message || 'No se pudo actualizar la programación', 'error');
        }
    } catch (err) {
        console.error(err);
        if (err.response && err.response.data && err.response.data.message) {
            Swal.fire('Error', err.response.data.message, 'error');
        } else {
            Swal.fire('Error', 'Ocurrió un problema al registrar la programación', 'error');
        }
    }
};


document.querySelector('[data-hs-overlay="#modal-registro"]').addEventListener('click', function() {
    if (window.cursoSeleccionado) {
        document.getElementById('codigoCursoInput').value = window.cursoSeleccionado;
    }
});

window.abrirModalRegistro = () => {
    const alpineComponent = document.querySelector('[x-data="formProgramacionGestion()"]')?._x_dataStack?.[0];

    console.log('Abriendo modal registro');

    // Limpiar campos del formulario
    if (alpineComponent) {
        alpineComponent.limpiarCampos();
        alpineComponent.codigoCurso = window.cursoSeleccionado || '';
    }

    // Abrir modal
    if (window.HSOverlay) {
        window.HSOverlay.open(document.getElementById('modal-registro'));
    }

    // Configurar botón como "Registrar"
    const btn = document.getElementById('btnGestionProgramacion');
    btn.innerHTML = `Registrar Programación&nbsp;<i class="fa-solid fa-floppy-disk"></i>`;
    btn.onclick = (e) => alpineComponent.registrar(e);
};

window.abrirModalEdit = () => {
    const alpineComponent = document.querySelector('[x-data="formProgramacionGestion()"]')?._x_dataStack?.[0];

    console.log('Abriendo modal edicion');

    // Abrir modal
    if (window.HSOverlay) {
        window.HSOverlay.open(document.getElementById('modal-registro'));
    }

    // Cambiar botón para que diga "Actualizar"
    const btn = document.getElementById('btnGestionProgramacion');
    btn.innerHTML = `Actualizar Programación&nbsp;<i class="fa-solid fa-floppy-disk"></i>`;
    btn.onclick = (e) => actualizarProgramacion(e, alpineComponent);
};