/**
 * Consulta de Matrículas por Curso
 * Script para visualizar todas las matrículas de un curso específico
 */

import Swal from "sweetalert2";
import axios from "axios";
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import DataTable from "vanilla-datatables";
import _ from 'lodash';
import * as XLSX from 'xlsx'; // Revertir a xlsx estándar para corregir error 504
window.XLSX = XLSX;

// Configurar Axios para enviar cookies de sesión y el token CSRF
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Estado global
let cursoSeleccionado = null;
let matriculasData = [];
let tabulatorMatriculas = null;

// Estado para Historial (Tab 1)
let personalSeleccionado = null;
let tabulatorPersonal = null;

// Estado para Modal de Matrícula
let tblPersonalMatriculaModal = null;
let personasSeleccionadas = new Set();
let cursoActualParaMatricula = null;

// Variable global para la instancia de DataTable
let dataTableCursos = null;

// Inicialización
document.addEventListener('DOMContentLoaded', async () => {
    await cargarFiltros();
    cargarSucursales(); // Cargar lista de sucursales (Historial)
    inicializarTablaPersonal(); // Tabla de Directorio
    buscarPersonal('', 20000); // Cargar personal inicial
    inicializarTabulator();
    configurarEventos();
});

/**
 * Cargar filtros de tipo de curso y áreas
 */
async function cargarFiltros() {
    try {
        const [resTipos, resAreas] = await Promise.all([
            axios.get('/api/get-capacitacion-tipo-cursos'),
            axios.get('/api/get-capacitacion-areas')
        ]);

        const selectTipo = document.getElementById('slcFiltroTipoCurso');

        if (resTipos.data && Array.isArray(resTipos.data)) {
            // Poblar global para Alpine
            window.opcionesTipoCurso = resTipos.data.map(tipo => ({
                codigo: tipo.codigo,
                descripcion: tipo.descripcion
            }));
            // Despachar evento
            window.dispatchEvent(new CustomEvent('tipos-cursos-loaded', { detail: window.opcionesTipoCurso }));
        }

        if (resAreas.data && Array.isArray(resAreas.data)) {
            // Populate global for Alpine
            window.opcionesArea = resAreas.data.map(area => ({
                codigo: area.codigo,
                descripcion: area.descripcion
            }));
            // Dispatch event
            window.dispatchEvent(new CustomEvent('areas-loaded', { detail: window.opcionesArea }));
        }
    } catch (error) {
        console.error('Error al cargar filtros:', error);
    }
}

/**
 * Cargar sucursales para el filtro (Tab 1)
 */
async function cargarSucursales() {
    try {
        const response = await axios.get('/api/get-sucursales');
        if (response.data.success) {
            // Poblar global para Alpine
            window.opcionesSucursales = response.data.sucursales.map(suc => ({
                codigo: suc.sucursal,
                descripcion: suc.sucursal
            }));

            // Despachar evento para Alpine
            window.dispatchEvent(new CustomEvent('sucursales-loaded', { detail: window.opcionesSucursales }));

            const select = document.getElementById('filtroSucursalPersonal');
            if (select) {
                // Limpiar opciones previas excepto la primera
                select.innerHTML = '<option value="">Todas las sucursales</option>';
                response.data.sucursales.forEach((suc) => {
                    const option = document.createElement('option');
                    option.value = suc.sucursal;
                    option.textContent = suc.sucursal;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error al cargar sucursales:', error);
    }
}

/**
 * Funciones globales para que Alpine.js pueda disparar los filtros
 */
window.aplicarFiltroSucursalPersonal = function (sucursal) {
    const txtBusquedaPersonal = document.getElementById('txtBusquedaPersonal');
    const termino = txtBusquedaPersonal ? txtBusquedaPersonal.value.trim() : '';
    buscarPersonal(termino, 100, sucursal);
};

window.aplicarFiltroSedeMatricula = function (sede) {
    const select = document.getElementById('slcFiltroSede');
    if (select) {
        select.value = sede;
        // Disparar evento change manualmente para que el listener de JS reaccione
        select.dispatchEvent(new Event('change'));
    }
};

/**
 * Buscar personal (Tab 1)
 */
async function buscarPersonal(termino, limite = 100, sucursal = '') {
    try {
        if (limite > 100) {
            Swal.fire({ title: 'Cargando personal...', text: 'Por favor espere', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        }
        const response = await axios.get('/api/buscar-personal-capacitacion', {
            params: { q: termino, limite: limite, sucursal: sucursal }
        });
        if (limite > 100) Swal.close();
        if (response.data.success && tabulatorPersonal) {
            tabulatorPersonal.setData(response.data.personal);
        }
    } catch (error) {
        if (limite > 100) Swal.close();
        console.error('Error al buscar personal:', error);
    }
}

/**
 * Inicializar Tabla de Personal en Tab 1 (Tabulator)
 */
function inicializarTablaPersonal() {
    tabulatorPersonal = new Tabulator("#tblPersonal", {
        data: [],
        layout: "fitColumns",
        placeholder: "No se encontraron empleados",
        pagination: "local",
        paginationSize: 15,
        paginationSizeSelector: [15, 25, 50],
        paginationCounter: "rows",
        locale: true,
        height: "450px",
        langs: {
            "default": {
                "pagination": {
                    "first": "Primero",
                    "first_title": "Primera página",
                    "last": "Último",
                    "last_title": "Última página",
                    "prev": "Anterior",
                    "prev_title": "Página anterior",
                    "next": "Siguiente",
                    "next_title": "Página siguiente",
                    "all": "Todos",
                    "counter": {
                        "showing": "Mostrando",
                        "of": "de",
                        "rows": "filas",
                        "pages": "páginas",
                    },
                    "page_size": "Filas por página",
                }
            }
        },
        columns: [
            { title: "DNI", field: "dni", width: 100, headerSort: false },
            { title: "Nombre Completo", field: "nombre_completo", minWidth: 200 },
            {
                title: "Cargo", field: "cargo", width: 150, headerSort: false,
                formatter: function (cell) {
                    let nombreCargo = cell.getValue() || '';
                    if (nombreCargo.toUpperCase() === 'OPER') nombreCargo = 'OPERARIO';
                    else if (nombreCargo.toUpperCase() === 'ADMIN') nombreCargo = 'ADMINISTRATIVO';
                    return `<span class="text-xs text-gray-600">${nombreCargo}</span>`;
                }
            },
            {
                title: "Sucursal", field: "sucursal", width: 120, headerSort: false,
                formatter: function (cell) {
                    return `<span class="text-xs text-blue-600/80">${cell.getValue() || ''}</span>`;
                }
            },
            {
                title: "Cursos", field: "total_capacitaciones", width: 80, hozAlign: "center", headerSort: false,
                formatter: function (cell) {
                    const val = cell.getValue() || 0;
                    const color = val > 0 ? 'text-blue-600 font-medium' : 'text-gray-400';
                    return `<span class="${color}">${val}</span>`;
                }
            },
            {
                title: "Acción", headerSort: false, width: 80, hozAlign: "center",
                formatter: function () {
                    return `<button class="btn-seleccionar px-2 py-1 rounded bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors text-xs font-medium">
                                <i class="i-tabler-eye"></i> Ver
                            </button>`;
                },
                cellClick: function (e, cell) {
                    e.stopPropagation();
                    const data = cell.getRow().getData();
                    seleccionarPersonal(data);
                }
            }
        ],
        rowClick: function (e, row) {
            seleccionarPersonal(row.getData());
        }
    });
}

/**
 * Seleccionar persona y cargar historial (Desde Tab 1)
 */
async function seleccionarPersonal(persona) {
    personalSeleccionado = persona;
    await cargarHistorial(persona.codigo, persona.nombre_completo);
}

/**
 * Cargar historial de capacitaciones
 */
async function cargarHistorial(personalId, nombrePersonal) {
    try {
        Swal.fire({
            title: 'Cargando...',
            text: 'Obteniendo historial de capacitaciones',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const response = await axios.get(`/api/get-historial-capacitaciones/${personalId}`);

        Swal.close();

        if (response.data.success) {
            const historial = response.data.historial;
            mostrarModalHistorial(nombrePersonal, historial);
        } else {
            Swal.fire('Error', response.data.message || 'No se pudo cargar el historial', 'error');
        }
    } catch (error) {
        Swal.close();
        console.error('Error al cargar historial:', error);
        Swal.fire('Error', 'Ocurrió un error al cargar el historial', 'error');
    }
}

/**
 * Mostrar Modal con Historial - VERSIÓN OBRA MAESTRA (Timeline)
 */
function mostrarModalHistorial(nombre, solicitudes) {
    const avatarContainer = document.getElementById('avatarPersonal');
    const nameEl = document.getElementById('nombrePersonal');

    // Initials for avatar
    const initials = nombre.split(' ').map(n => n[0]).join('').substring(0, 2);
    if (avatarContainer) avatarContainer.textContent = initials;
    if (nameEl) nameEl.textContent = nombre;

    const container = document.getElementById('historialContainer');
    const emptyMsg = document.getElementById('noDataMessage');

    if (solicitudes.length === 0) {
        container.innerHTML = '';
        emptyMsg.classList.remove('hidden');
    } else {
        emptyMsg.classList.add('hidden');

        container.innerHTML = solicitudes.map((s, index) => {
            const statusFull = s.estado || 'MATRICULADO';
            const colors = {
                'APROBADO': {
                    bg: 'bg-emerald-50',
                    text: 'text-emerald-700',
                    icon: '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
                    border: 'border-emerald-100',
                    dot: 'bg-emerald-500'
                },
                'COMPLETADO': {
                    bg: 'bg-emerald-50',
                    text: 'text-emerald-700',
                    icon: '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
                    border: 'border-emerald-100',
                    dot: 'bg-emerald-500'
                },
                'REPROBADO': {
                    bg: 'bg-rose-50',
                    text: 'text-rose-700',
                    icon: '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>',
                    border: 'border-rose-100',
                    dot: 'bg-rose-500'
                },
                'EN_PROGRESO': {
                    bg: 'bg-amber-50',
                    text: 'text-amber-700',
                    icon: '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg>',
                    border: 'border-amber-100',
                    dot: 'bg-amber-500'
                },
                'MATRICULADO': {
                    bg: 'bg-blue-50',
                    text: 'text-blue-700',
                    icon: '<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M17 11l2 2 4-4"/></svg>',
                    border: 'border-blue-100',
                    dot: 'bg-blue-500'
                }
            };

            const c = colors[statusFull] || colors['MATRICULADO'];
            const fecha = s.fecha_matricula ? new Date(s.fecha_matricula).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' }) : 'N/A';
            const delay = index * 100;

            return `
                <div class="relative pl-12 pb-8 group animate-fade-in-up" style="animation-delay: ${delay}ms">
                    <!-- Timeline Line Pulse Effect -->
                    <div class="absolute left-3 top-0 h-full w-0.5 bg-gray-100 group-last:bg-transparent"></div>
                    
                    <!-- Timeline Dot -->
                    <div class="absolute left-0 top-1 z-10 flex items-center justify-center w-7 h-7 rounded-full bg-white shadow-lg border-2 ${c.border} transition-transform group-hover:scale-110">
                        <span class="${c.text}">
                            ${c.icon}
                        </span>
                    </div>

                    <!-- Card Content -->
                    <div class="relative flex flex-col p-5 bg-white border border-gray-100 rounded-3xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.05)] transition-all hover:shadow-[0_8px_30px_-4px_rgba(0,0,0,0.08)] hover:border-primary/20 hover:-translate-y-1">
                        <!-- Background Glow -->
                        <div class="absolute top-0 right-0 -mt-2 -mr-2 w-16 h-16 ${c.bg} opacity-20 blur-2xl rounded-full"></div>

                        <div class="flex items-center justify-between mb-3 relative z-10">
                            <div class="flex items-center gap-2">
                                <div class="w-1.5 h-1.5 rounded-full ${c.dot} animate-pulse"></div>
                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.15em]">${fecha}</span>
                            </div>
                            <span class="px-3 py-1 rounded-xl text-[10px] font-black tracking-wider ${c.bg} ${c.text} border ${c.border} shadow-sm">
                                ${statusFull}
                            </span>
                        </div>

                        <h5 class="text-[15px] font-black text-gray-800 leading-tight mb-2 group-hover:text-primary transition-colors">${s.nombre_curso}</h5>
                        
                        <div class="flex flex-wrap items-center gap-4 mt-1">
                            <div class="flex items-center gap-1.5 bg-gray-50/80 px-2.5 py-1.5 rounded-lg border border-gray-100">
                                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                <span class="text-[11px] font-bold text-gray-600">Inicio:</span>
                                <span class="text-[11px] text-gray-500">${s.prog_fecha_inicio ? new Date(s.prog_fecha_inicio).toLocaleDateString() : 'N/A'}</span>
                            </div>
                            <div class="flex items-center gap-1.5 bg-gray-50/80 px-2.5 py-1.5 rounded-lg border border-gray-100">
                                <svg class="w-3.5 h-3.5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                                <span class="text-[11px] font-bold text-gray-600">Fin:</span>
                                <span class="text-[11px] text-gray-500">${s.prog_fecha_final ? new Date(s.prog_fecha_final).toLocaleDateString() : 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    try {
        if (typeof HSOverlay !== 'undefined') {
            HSOverlay.open('#modal-historial');
        }
    } catch (e) {
        console.error('Error al abrir modal historial:', e);
    }
}

function obtenerClaseEstadoHistorial(estado, isBadge = false) {
    const colores = {
        'MATRICULADO': 'bg-blue-100 text-blue-700',
        'EN_PROGRESO': 'bg-yellow-100 text-yellow-700',
        'COMPLETADO': 'bg-green-100 text-green-700',
        'APROBADO': 'bg-green-100 text-green-700',
        'REPROBADO': 'bg-red-100 text-red-700',
        'CANCELADO': 'bg-gray-100 text-gray-700',
        'NO MATRICULADO': 'bg-gray-100 text-gray-500'
    };

    const clase = colores[estado] || 'bg-gray-100 text-gray-700';

    if (isBadge) {
        return `<span class="px-2 py-0.5 rounded-full text-xs font-semibold ${clase}">${estado}</span>`;
    }
    return clase;
}

/**
 * Listar cursos según filtros (expuesta globalmente para Alpine.js)
 */
window.listarCursosConsulta = async function (habilitado, area, tipoCurso) {
    try {
        let url = `/api/get-cursos/${habilitado}`;
        const params = new URLSearchParams();

        if (area) params.append('filtro_area', area);
        if (tipoCurso) params.append('filtro_tipo', tipoCurso);

        if (params.toString()) {
            url += '?' + params.toString();
        }

        const response = await axios.get(url);
        const cursos = response.data || [];

        renderizarTablaCursos(cursos);
    } catch (error) {
        console.error('Error al listar cursos:', error);
    }
};

/**
 * Renderizar tabla de cursos usando Vanilla-DataTables
 */
function renderizarTablaCursos(cursos) {
    // Destroy existing DataTable instance
    if (dataTableCursos) {
        dataTableCursos.destroy();
        dataTableCursos = null;
    }

    // Get the container and completely remove the old table
    const container = document.getElementById('tblCursos').parentElement;
    const oldTable = document.getElementById('tblCursos');
    oldTable.remove();

    // Create a completely fresh table element
    const newTable = document.createElement('table');
    newTable.id = 'tblCursos';
    newTable.className = 'table table-bordered table-hover';

    // Create thead
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr>
            <th>Código</th>
            <th>Nombre del Curso</th>
            <th class="text-center">Acciones</th>
        </tr>
    `;
    newTable.appendChild(thead);

    // Create tbody with data
    const tbody = document.createElement('tbody');
    tbody.id = 'tbodyCursos';

    if (cursos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="3" class="px-3 py-6 text-center text-gray-500">
                    No se encontraron cursos
                </td>
            </tr>
        `;
    } else {
        // Construir filas
        cursos.forEach(curso => {
            const tr = document.createElement('tr');
            tr.className = 'cursor-pointer border-b border-gray-100';
            tr.dataset.cursoId = curso.codigo;

            // Si el curso está eliminado lógicamente (habilitado == 0), darle fondo rojizo pastel
            if (curso.habilitado == 0 || curso.habilitado === '0') {
                tr.style.backgroundColor = '#fff1f1';
                tr.onmouseenter = () => tr.style.backgroundColor = '#fde8e8';
                tr.onmouseleave = () => tr.style.backgroundColor = '#fff1f1';
            } else {
                tr.classList.add('hover:bg-gray-50');
            }

            // Determinar si está seleccionado
            const isSelected = cursoSeleccionado && cursoSeleccionado.codigo === curso.codigo;
            if (isSelected) {
                // Si estaba eliminado y seleccionado a la vez, podríamos agregar outline o un borde primario
                tr.classList.add('bg-primary/10', 'border-l-4', 'border-primary');
            }

            tr.innerHTML = `
                <td class="px-3 py-2 text-sm font-mono">${curso.codigoCurso}</td>
                <td class="px-3 py-2 text-sm">${escapeHtml(curso.nombre)}</td>
                <td class="px-3 py-2 text-center">
                    <button type="button" class="btn-ver-matriculas px-3 py-1 rounded bg-primary text-white hover:bg-primary/80 transition-colors text-sm"
                        data-curso-id="${curso.codigo}" data-curso-nombre="${escapeHtml(curso.nombre)}" data-curso-codigo="${curso.codigoCurso}">
                        Ver
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });
    }

    newTable.appendChild(tbody);
    container.appendChild(newTable);

    // Initialize DataTables on the completely fresh table
    dataTableCursos = new DataTable(newTable, {
        perPage: 8,
        perPageSelect: [8, 15, 25],
        searchable: false,
        sortable: true,
        fixedHeight: true,
        columns: [
            { select: 2, sortable: false }
        ],
        labels: {
            placeholder: "Buscar...",
            perPage: "{select} Filas por página",
            noRows: "No hay cursos encontrados",
            info: "Mostrando {start} - {end} de {rows} cursos"
        }
    });

    // Configurar eventos usando delegación
    newTable.addEventListener('click', manejarClickTablaCursos);

    // Re-aplicar estilos de selección si la paginación cambia
    dataTableCursos.on('datatable.page', () => {
        marcarCursoSeleccionadoVisualmente();
    });
}

/**
 * Manejador de clicks delegado para la tabla de cursos
 */
function manejarClickTablaCursos(e) {
    const target = e.target;

    // Caso 1: Click en botón "Ver"
    const btn = target.closest('.btn-ver-matriculas');
    if (btn) {
        e.stopPropagation();
        const cursoId = btn.dataset.cursoId;
        const cursoNombre = btn.dataset.cursoNombre;
        const cursoCodigo = btn.dataset.cursoCodigo;

        seleccionarCurso({
            codigo: cursoId,
            nombre: cursoNombre,
            codigo_curso: cursoCodigo
        });
        return;
    }

    // Caso 2: Click en la fila (opcional, si queremos que toda la fila seleccione)
    // El usuario pidió "otra pestaña dentro del listado", asumo que se refiere a paginación estándar.
    // Mantenemos la funcionalidad de seleccionar al hacer click en "Ver" principalmente.
}

/**
 * Helper para mantener el resaltado visual entre páginas
 */
function marcarCursoSeleccionadoVisualmente() {
    if (!cursoSeleccionado) return;

    // Buscar filas visibles y marcar
    const rows = document.querySelectorAll('#tbodyCursos tr');
    rows.forEach(tr => {
        // En DataTable las filas originales pueden perder data-attributes si no se configuran bien,
        // pero como insertamos HTML string, a veces se preservan o hay que buscar por contenido.
        // La forma más robusta con HTML strings es regenerar la clase en render o buscar botones hijos.

        const btn = tr.querySelector('.btn-ver-matriculas');
        if (btn && btn.dataset.cursoId == cursoSeleccionado.codigo) {
            tr.classList.add('bg-primary/10');
        } else {
            tr.classList.remove('bg-primary/10');
        }
    });
}

/**
 * Seleccionar curso y cargar matrículas
 */
async function seleccionarCurso(curso) {
    cursoSeleccionado = curso;

    // Actualizar UI
    document.getElementById('infoCursoSeleccionadoTitulo').textContent = curso.nombre;
    document.getElementById('infoCursoSeleccionado').innerHTML = `
        <span class="font-bold text-gray-700">Código:</span> ${curso.codigo_curso}
    `;

    document.getElementById('buscarMatricula').disabled = false;
    document.getElementById('slcFiltroProgramacion').disabled = false;

    document.getElementById('estadoVacio').style.display = 'none';
    document.getElementById('estadisticasMatriculas').style.display = 'grid';
    document.getElementById('filtrosMatriculaContainer').style.display = 'grid';
    document.getElementById('contenedorTblMatriculas').style.display = 'block';

    // Habilitar botón Matricular
    const btnMatricular = document.getElementById('btnAbrirModalMatricula');
    if (btnMatricular) btnMatricular.classList.remove('hidden');

    // Cambiar a Tab de "Matrículas del Curso"
    window.dispatchEvent(new CustomEvent('cambiar-tab-curso'));

    // Actualizar Badge del Tab 2
    const badgeCurrentCourse = document.getElementById('badgeCurrentCourse');
    if (badgeCurrentCourse) {
        badgeCurrentCourse.textContent = escapeHtml(curso.codigo_curso);
        badgeCurrentCourse.classList.remove('hidden');
    }

    // Resaltar fila seleccionada (usando el helper compatible con DataTable)
    marcarCursoSeleccionadoVisualmente();

    await cargarMatriculas(curso.codigo);
}

/**
 * Cargar matrículas del curso usando MigraPersonal
 */
async function cargarMatriculas(cursoId) {
    try {
        Swal.fire({
            title: 'Cargando...',
            text: 'Obteniendo matrículas del curso',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        // Cambiar endpoint para usar MigraPersonal
        const response = await axios.get(`/api/get-matriculas-migra-personal/${cursoId}`);
        Swal.close();

        if (response.data.success) {
            matriculasData = response.data.matriculas;
            actualizarEstadisticas(matriculasData);
            actualizarTabulator(matriculasData);
            document.getElementById('badgeTotalMatriculas').textContent = `Total: ${response.data.total}`;
            if (response.data.total > 0) {
                document.getElementById('btnExportarExcel').classList.remove('hidden');
            } else {
                document.getElementById('btnExportarExcel').classList.add('hidden');
            }
        } else {
            Swal.fire('Error', response.data.message || 'No se pudieron cargar las matrículas', 'error');
        }
    } catch (error) {
        Swal.close();
        console.error('Error al cargar matrículas:', error);
        Swal.fire('Error', 'Ocurrió un error al cargar las matrículas', 'error');
    }
}

/**
 * Actualizar estadísticas
 */
function actualizarEstadisticas(matriculas) {
    const estados = {
        matriculados: 0,
        enProgreso: 0,
        aprobados: 0,
        reprobados: 0
    };

    matriculas.forEach(m => {
        switch (m.estado) {
            case 'MATRICULADO':
                estados.matriculados++;
                break;
            case 'EN_PROGRESO':
                estados.enProgreso++;
                break;
            case 'APROBADO':
            case 'COMPLETADO':
                estados.aprobados++;
                break;
            case 'REPROBADO':
                estados.reprobados++;
                break;
            default:
                estados.matriculados++;
        }
    });

    // Actualizar textos
    document.getElementById('countMatriculados').textContent = estados.matriculados;
    document.getElementById('countEnProgreso').textContent = estados.enProgreso;
    document.getElementById('countAprobados').textContent = estados.aprobados;
    document.getElementById('countReprobados').textContent = estados.reprobados;

    // Poblar filtros
    poblarFiltroProgramaciones(matriculas);
    poblarFiltroSede(matriculas);
}

/**
 * Poblar el select de filtro de Sedes
 */
function poblarFiltroSede(matriculas) {
    const sedes = [...new Set(matriculas.map(m => m.sucursal).filter(s => s))].sort();

    // Despachar evento para Alpine
    const sedesData = sedes.map(s => ({ codigo: s, descripcion: s }));
    window.dispatchEvent(new CustomEvent('sedes-matriculas-loaded', { detail: sedesData }));

    const select = document.getElementById('slcFiltroSede');
    if (!select) return;

    const currentVal = select.value;
    select.innerHTML = '<option value="">🏢 Todas las Sedes</option>';

    sedes.forEach(sede => {
        const option = document.createElement('option');
        option.value = sede;
        option.textContent = sede;
        if (sede === currentVal) option.selected = true;
        select.appendChild(option);
    });
}

/**
 * Poblar el select de filtro de programaciones
 */
function poblarFiltroProgramaciones(matriculas) {
    const select = document.getElementById('slcFiltroProgramacion');
    if (!select) return;

    select.innerHTML = '<option value="">-- Todas las programaciones --</option>';
    select.disabled = false;

    // Obtener programaciones únicas
    const programacionesMap = new Map();

    matriculas.forEach(m => {
        // Generar clave única basada en fecha inicio y fin
        if (m.prog_fecha_inicio && m.prog_fecha_final) {
            const key = `${m.prog_fecha_inicio}|${m.prog_fecha_final}`;
            if (!programacionesMap.has(key)) {
                programacionesMap.set(key, {
                    inicio: m.prog_fecha_inicio,
                    final: m.prog_fecha_final
                });
            }
        }
    });

    if (programacionesMap.size === 0) {
        const option = document.createElement('option');
        option.textContent = "Sin programaciones registradas";
        select.appendChild(option);
        return;
    }

    // Ordenar por fecha reciente
    const sortedProgs = Array.from(programacionesMap.values()).sort((a, b) => {
        return new Date(b.inicio) - new Date(a.inicio);
    });

    sortedProgs.forEach(prog => {
        const fi = new Date(prog.inicio).toLocaleDateString();
        const ff = new Date(prog.final).toLocaleDateString();
        const val = `${prog.inicio}|${prog.final}`;

        const option = document.createElement('option');
        option.value = val;
        option.textContent = `${fi} - ${ff}`;
        select.appendChild(option);
    });
}

/**
 * Inicializar Tabulator
 */
function inicializarTabulator() {
    tabulatorMatriculas = new Tabulator("#tblMatriculas", {
        data: [],
        layout: "fitColumns",
        height: "550px",
        pagination: "local",
        paginationSize: 20,
        paginationSizeSelector: [10, 20, 50, 100],
        locale: true,
        langs: {
            "default": {
                "pagination": {
                    "first": "Primero",
                    "first_title": "Primera página",
                    "last": "Último",
                    "last_title": "Última página",
                    "prev": "Anterior",
                    "prev_title": "Página anterior",
                    "next": "Siguiente",
                    "next_title": "Página siguiente",
                    "all": "Todos",
                    "counter": {
                        "showing": "Mostrando",
                        "of": "de",
                        "rows": "filas",
                        "pages": "páginas",
                    },
                    "page_size": "Filas por página",
                }
            }
        },
        columns: [
            {
                title: "#",
                formatter: "rownum",
                width: 40,
                hozAlign: "center",
                headerSort: false
            },
            {
                title: "DNI",
                field: "dni",
                width: 100,
                cellClick: function (e, cell) {
                    const data = cell.getRow().getData();
                    seleccionarPersonal({
                        codigo: data.cod_personal,
                        nombre_completo: data.nombre_completo,
                        dni: data.dni
                    });
                },
                formatter: function (cell) {
                    return `<span class="text-primary font-bold hover:underline cursor-pointer">${cell.getValue()}</span>`;
                }
            },
            {
                title: "Nombre Completo",
                field: "nombre_completo",
                minWidth: 200,
                formatter: function (cell) {
                    return `<span class="font-bold text-gray-700 uppercase text-[11px]">${cell.getValue()}</span>`;
                }
            },
            {
                title: "Sede / Sucursal",
                field: "sucursal",
                minWidth: 150,
                formatter: function (cell) {
                    const val = cell.getValue();
                    return `<div class="flex items-center gap-1.5"><i class="i-tabler-map-pin text-gray-300"></i> <span class="text-[10px] font-medium text-gray-500">${val || '-'}</span></div>`;
                }
            },
            {
                title: "Programación",
                field: "prog_fecha_inicio",
                minWidth: 160,
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    if (!data.prog_fecha_inicio || !data.prog_fecha_final) {
                        return '<span class="text-gray-400 italic text-xs">Sin fecha</span>';
                    }
                    const fi = new Date(data.prog_fecha_inicio).toLocaleDateString();
                    const ff = new Date(data.prog_fecha_final).toLocaleDateString();
                    return `<span class="font-bold text-[10px] text-gray-500 bg-gray-50 px-2 py-1 rounded border border-gray-200">${fi} - ${ff}</span>`;
                }
            },
            {
                title: "Estado",
                field: "estado",
                width: 120,
                hozAlign: "center",
                formatter: function (cell) {
                    const estado = cell.getValue() || 'MATRICULADO';
                    const colores = {
                        'MATRICULADO': 'bg-blue-500 shadow-blue-100 py-2 px-3',
                        'EN_PROGRESO': 'bg-amber-600 shadow-amber-100',
                        'COMPLETADO': 'bg-emerald-500 shadow-emerald-100',
                        'APROBADO': 'bg-emerald-500 shadow-emerald-100',
                        'REPROBADO': 'bg-rose-500 shadow-rose-100',
                        'CANCELADO': 'bg-gray-600 shadow-gray-100'
                    };
                    const color = colores[estado] || 'bg-gray-400';
                    return `<span class="px-2.5 py-1 rounded-full text-[10px] font-black text-white shadow-sm ${color} uppercase tracking-wider">${estado}</span>`;
                }
            },
        ]
    });
}

/**
 * Actualizar datos del Tabulator
 */
function actualizarTabulator(data) {
    if (tabulatorMatriculas) {
        tabulatorMatriculas.setData(data);
    }
}

/**
 * Configurar eventos
 */
function configurarEventos() {
    // 1. Buscador texto (Matrículas Tab 2)
    const inputBuscar = document.getElementById('buscarMatricula');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', _.debounce(aplicarFiltrosCombine, 300));
    }

    // 2. Filtro Programacion (Matrículas Tab 2)
    const selectProg = document.getElementById('slcFiltroProgramacion');
    if (selectProg) {
        selectProg.addEventListener('change', aplicarFiltrosCombine);
    }

    // 2.1 Filtro Sede (Matrículas Tab 2)
    const selectSede = document.getElementById('slcFiltroSede');
    if (selectSede) {
        selectSede.addEventListener('change', aplicarFiltrosCombine);
    }

    // 3. Búsqueda y Filtros de Personal (Historial Tab 1)
    const txtBusquedaPersonal = document.getElementById('txtBusquedaPersonal');
    const filtroSucursalPersonal = document.getElementById('filtroSucursalPersonal');

    if (txtBusquedaPersonal) {
        // Uso de keyup con Enter o debounce para evitar saturación
        txtBusquedaPersonal.addEventListener('keyup', _.debounce((e) => {
            const termino = e.target.value.trim();
            const sucursal = filtroSucursalPersonal ? filtroSucursalPersonal.value : '';
            if (termino.length >= 3 || termino.length === 0) {
                buscarPersonal(termino, 100, sucursal);
            }
        }, 500));
    }

    if (filtroSucursalPersonal) {
        filtroSucursalPersonal.addEventListener('change', (e) => {
            const sucursal = e.target.value;
            const termino = txtBusquedaPersonal ? txtBusquedaPersonal.value.trim() : '';
            buscarPersonal(termino, 100, sucursal);
        });
    }

    // 3. Filtros Cards Estado
    // Agregamos cursor pointer a los cards
    const cards = document.querySelectorAll('#estadisticasMatriculas > div');
    cards.forEach(card => {
        card.style.cursor = 'pointer';
        card.title = "Click para filtrar por este estado";
        card.addEventListener('click', () => {
            // Determinar qué estado filtrar basándonos en el ID del contador hijo
            const childId = card.querySelector('p[id^="count"]').id;
            let estadoFilter = '';

            // Resetear estilos de selección previos
            cards.forEach(c => c.classList.remove('ring-2', 'ring-offset-1', 'ring-indigo-500'));

            if (window.filtroEstadoActivo === childId) {
                // Si ya estaba activo, desactivar (toggle off)
                window.filtroEstadoActivo = null;
            } else {
                // Activar nuevo filtro
                window.filtroEstadoActivo = childId;
                card.classList.add('ring-2', 'ring-offset-1', 'ring-indigo-500');
            }

            aplicarFiltrosCombine();
        });
    });

    // Exportar Excel
    document.getElementById('btnExportarExcel').addEventListener('click', () => {
        if (tabulatorMatriculas && cursoSeleccionado) {
            tabulatorMatriculas.download("xlsx", `matriculas_${cursoSeleccionado.codigo_curso}.xlsx`, {
                sheetName: "Matrículas"
            });
        }
    });

    // --- NUEVO: EVENTOS PARA MODAL DE MATRÍCULA ---

    // 1. Abrir Modal
    const btnAbrirModal = document.getElementById('btnAbrirModalMatricula');
    if (btnAbrirModal) {
        btnAbrirModal.addEventListener('click', async () => {
            if (!cursoSeleccionado) {
                Swal.fire("Atención", "Seleccione un curso primero.", "warning");
                return;
            }

            // Actualizar UI del Modal
            document.getElementById('nombreCursoModal').textContent = cursoSeleccionado.nombre;
            cursoActualParaMatricula = cursoSeleccionado.codigo;

            // Limpiar estados previos
            personasSeleccionadas.clear();
            actualizarContadorSeleccionados();
            const selectSucursal = document.getElementById("slcFiltroSucursalModal");
            if (selectSucursal) selectSucursal.value = "";

            // Cargar datos
            await cargarProgramacionesModal(cursoSeleccionado.codigo);
            await cargarPersonalModal(cursoSeleccionado.codigo);
        });
    }

    // 2. Guardar Matrícula
    const btnGuardar = document.getElementById('btnGuardarMatricula');
    if (btnGuardar) {
        btnGuardar.addEventListener('click', async () => {
            const seleccionados = Array.from(personasSeleccionadas);
            const programacionId = document.getElementById("slcProgramacionMatriculaModal").value;

            if (!programacionId) {
                Swal.fire("Atención", "Seleccione una programación.", "warning");
                return;
            }

            if (seleccionados.length === 0) {
                Swal.fire("Atención", "Seleccione al menos una persona.", "warning");
                return;
            }

            try {
                const response = await axios.post(`/capacitacion/save-matricula`, {
                    cursoId: cursoActualParaMatricula,
                    programacionId: programacionId,
                    personalIds: seleccionados
                });

                if (response.status === 202 || response.data.success) {
                    // Cerrar modal
                    // @ts-ignore
                    HSOverlay.close('#modal-registro');

                    Swal.fire({
                        title: "¡Procesando!",
                        text: "La matrícula se está procesando en segundo plano. La lista se actualizará en breve.",
                        icon: "info",
                        timer: 4000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });

                    // POLLING: Reintentar actualización para dar tiempo al Job
                    // Intento 1 (2s)
                    setTimeout(() => cargarMatriculas(cursoActualParaMatricula), 2000);
                    // Intento 2 (4s)
                    setTimeout(() => cargarMatriculas(cursoActualParaMatricula), 4000);
                    // Intento 3 (Final - 6s)
                    setTimeout(async () => {
                        await cargarMatriculas(cursoActualParaMatricula);
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                        Toast.fire({
                            icon: 'success',
                            title: 'Lista de matrículas actualizada'
                        });
                    }, 6000);

                } else {
                    Swal.fire("Error", response.data.message || "Error al guardar", "error");
                }
            } catch (error) {
                console.error("Error al matricular:", error);
                Swal.fire("Error", "Ocurrió un error al procesar la matrícula.", "error");
            }
        });
    }
}


// Variable global para filtro de estado
window.filtroEstadoActivo = null;

/**
 * Función centralizada para aplicar todos los filtros
 */
function aplicarFiltrosCombine() {
    let filtrados = [...matriculasData];
    const tagsContainer = document.getElementById('activeFiltersContainer');
    let activeTags = [];

    // 1. Filtro Texto
    const termino = document.getElementById('buscarMatricula')?.value.toLowerCase().trim() || '';
    if (termino) {
        filtrados = filtrados.filter(m =>
            (m.nombre_completo && m.nombre_completo.toLowerCase().includes(termino)) ||
            (m.dni && m.dni.toLowerCase().includes(termino)) ||
            (m.sucursal && m.sucursal.toLowerCase().includes(termino))
        );
        activeTags.push(`<span class="px-2 py-0.5 bg-gray-100 rounded text-[10px] border border-gray-200">🔍 "${termino}"</span>`);
    }

    // 2. Filtro Programacion
    const progVal = document.getElementById('slcFiltroProgramacion')?.value || '';
    if (progVal) {
        const [inicioWanted, finalWanted] = progVal.split('|');
        filtrados = filtrados.filter(m =>
            m.prog_fecha_inicio === inicioWanted && m.prog_fecha_final === finalWanted
        );
        const text = document.getElementById('slcFiltroProgramacion').selectedOptions[0].text;
        activeTags.push(`<span class="px-2 py-0.5 bg-blue-50 text-blue-600 rounded text-[10px] border border-blue-100">📅 ${text}</span>`);
    }

    // 3. Filtro Sede
    const sedeVal = document.getElementById('slcFiltroSede')?.value || '';
    if (sedeVal) {
        filtrados = filtrados.filter(m => m.sucursal === sedeVal);
        activeTags.push(`<span class="px-2 py-0.5 bg-indigo-50 text-indigo-600 rounded text-[10px] border border-indigo-100">🏢 ${sedeVal}</span>`);
    }

    // 4. Filtro Estado (desde las cards)
    if (window.filtroEstadoActivo) {
        let label = '';
        filtrados = filtrados.filter(m => {
            const st = m.estado || 'MATRICULADO';
            switch (window.filtroEstadoActivo) {
                case 'countMatriculados': label = 'MATRICULADOS'; return true;
                case 'countEnProgreso': label = 'EN PROGRESO'; return st === 'EN_PROGRESO';
                case 'countAprobados': label = 'APROBADOS'; return st === 'APROBADO' || st === 'COMPLETADO';
                case 'countReprobados': label = 'REPROBADOS'; return st === 'REPROBADO';
                default: return true;
            }
        });
        if (label) activeTags.push(`<span class="px-2 py-0.5 bg-emerald-50 text-emerald-600 rounded text-[10px] border border-emerald-100 font-bold">⭐ ${label}</span>`);
    }

    if (tagsContainer) {
        tagsContainer.innerHTML = activeTags.length > 0 ? activeTags.join('') : '<span class="text-[10px] text-gray-400 italic">Ninguno aplicado</span>';
    }

    actualizarTabulator(filtrados);
}


/**
 * Escapar HTML para prevenir XSS
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}


// =========================================================================================
// LÓGICA DEL MODAL DE MATRÍCULA (MIGRADA Y ADAPTADA)
// =========================================================================================

async function cargarProgramacionesModal(cursoId) {
    const select = document.getElementById("slcProgramacionMatriculaModal");
    if (!select) return;

    select.innerHTML = '<option value="">Cargando...</option>';
    select.disabled = true;

    try {
        const res = await axios.get(`/api/get-curso-programacion/${cursoId}`);
        select.innerHTML = '<option value="">-- Seleccione programación --</option>';
        select.disabled = false;

        if (res.data.success && res.data.programaciones?.length > 0) {
            res.data.programaciones.forEach(prog => {
                const fi = new Date(prog.fecha_inicio).toLocaleDateString();
                const ff = new Date(prog.fecha_final).toLocaleDateString();
                const codigo = prog.codigo;

                // Validar si la fecha ha pasado
                const fechaFinal = new Date(prog.fecha_final);
                const hoy = new Date();
                // Ajustar al final del día para comparación justa
                fechaFinal.setHours(23, 59, 59, 999);

                const esPasada = fechaFinal < hoy;

                const option = document.createElement("option");
                option.value = codigo;
                option.textContent = `${prog.codigo_programacion} | ${fi} - ${ff}${esPasada ? ' (Finalizada)' : ''}`;

                if (esPasada) {
                    option.disabled = true;
                    option.style.backgroundColor = "#e5e7eb"; // Gris claro visual
                    option.style.color = "#9ca3af";
                }

                select.appendChild(option);
            });
        } else {
            select.innerHTML = '<option value="">Sin programaciones activas</option>';
        }
    } catch (err) {
        console.error("Error programaciones:", err);
        select.innerHTML = '<option value="">Error al cargar</option>';
    }
}

async function cargarPersonalModal(cursoId) {
    // Si ya existe instancia, destruir
    if (tblPersonalMatriculaModal) {
        tblPersonalMatriculaModal.destroy();
    }

    // Inicializar Tabulator Modal
    tblPersonalMatriculaModal = new Tabulator("#tblPersonalMatriculaModal", {
        ajaxURL: `/api/buscar-personal-capacitacion`,
        ajaxParams: { cursoId: cursoId, pagination: "off" },
        ajaxResponse: function (url, params, response) {
            return response.personal || response;
        },
        pagination: "local",
        paginationSize: 10,
        height: "400px",
        layout: "fitColumns",
        columns: [
            {
                title: "Sel",
                field: "seleccionar",
                width: 50,
                hozAlign: "center",
                headerSort: false,
                formatter: function (cell) {
                    const data = cell.getRow().getData();
                    const matriculado = data.matriculado || false;
                    if (matriculado) {
                        return `<input type="checkbox" checked disabled class="opacity-50 cursor-not-allowed">`;
                    }
                    const isChecked = personasSeleccionadas.has(data.codigo) ? 'checked' : '';
                    return `<input type="checkbox" class="chk-personal-modal" ${isChecked} data-id="${data.codigo}">`;
                }
            },
            { title: "Nombre", field: "nombre_completo", minWidth: 280 },
            { title: "DNI", field: "dni", width: 100 },
            { title: "Sucursal", field: "sucursal", width: 150 }
        ],
        rowFormatter: function (row) {
            if (row.getData().matriculado) {
                row.getElement().style.backgroundColor = "#f0fdf4";
                row.getElement().style.color = "#888";
            }
        }
    });

    // Eventos de tabla
    tblPersonalMatriculaModal.on("tableBuilt", () => {
        configurarBuscadorModal();
        configurarCheckboxesModal();
        configurarFiltroSucursalModal();
        const data = tblPersonalMatriculaModal.getData();
        poblarFiltroSucursalModal(data);
        actualizarContadoresModal(data);
    });

    tblPersonalMatriculaModal.on("dataLoaded", (data) => {
        poblarFiltroSucursalModal(data);
        actualizarContadoresModal(data);
    });
}

function poblarFiltroSucursalModal(data) {
    const select = document.getElementById("slcFiltroSucursalModal");
    if (!select) return;

    const sucursales = [...new Set(data.map(d => d.sucursal).filter(s => s))].sort();
    const currentVal = select.value;

    select.innerHTML = '<option value="">Todas las sucursales</option>';
    sucursales.forEach(s => {
        const opt = document.createElement("option");
        opt.value = s;
        opt.textContent = s;
        if (s === currentVal) opt.selected = true;
        select.appendChild(opt);
    });
}

function configurarFiltroSucursalModal() {
    const select = document.getElementById("slcFiltroSucursalModal");
    if (!select) return;
    if (select.dataset.listenerSucursal) return;

    select.addEventListener("change", function () {
        const val = this.value;
        if (tblPersonalMatriculaModal) {
            if (!val) {
                tblPersonalMatriculaModal.clearFilter();
            } else {
                tblPersonalMatriculaModal.setFilter("sucursal", "=", val);
            }
        }
    });
    select.dataset.listenerSucursal = "true";
}

function configurarCheckboxesModal() {
    const tableEl = document.getElementById("tblPersonalMatriculaModal");
    if (!tableEl || tableEl.dataset.listener) return;

    tableEl.addEventListener('change', (e) => {
        // @ts-ignore
        if (e.target && e.target.classList.contains('chk-personal-modal')) {
            // @ts-ignore
            const id = e.target.dataset.id;
            // @ts-ignore
            if (e.target.checked) {
                personasSeleccionadas.add(id);
            } else {
                personasSeleccionadas.delete(id);
            }
            actualizarContadorSeleccionados();
        }
    });
    tableEl.dataset.listener = "true";
}

function configurarBuscadorModal() {
    const input = document.getElementById("buscarPersonalModal");
    if (!input) return;

    input.value = "";
    // Clonar nodo para limpiar listeners anteriores si los hubiera (paranoia mode)
    const newInput = input.cloneNode(true);
    // @ts-ignore
    input.parentNode.replaceChild(newInput, input);

    newInput.addEventListener("input", _.debounce(function (e) {
        // @ts-ignore
        const term = e.target.value.toLowerCase();
        if (tblPersonalMatriculaModal) {
            if (!term) tblPersonalMatriculaModal.clearFilter();
            else {
                tblPersonalMatriculaModal.setFilter(data => {
                    const n = (data.nombre_completo || "").toLowerCase();
                    const d = (data.dni || "").toString();
                    return n.includes(term) || d.includes(term);
                });
            }
        }
    }, 300));
}

function actualizarContadorSeleccionados() {
    const count = personasSeleccionadas.size;
    const badge = document.getElementById("countSeleccionadosModal");
    if (badge) badge.textContent = count.toString();
}

function actualizarContadoresModal(data) {
    if (!Array.isArray(data)) return;
    const matriculados = data.filter(d => d.matriculado).length;
    const disponibles = data.length - matriculados;

    const bMat = document.getElementById("countMatriculadosModal");
    const bDisp = document.getElementById("countDisponiblesModal");

    if (bMat) bMat.textContent = matriculados.toString();
    if (bDisp) bDisp.textContent = disponibles.toString();
}
// =========================================================================================
// ACCIONES DE MATRÍCULA
// =========================================================================================

window.eliminarMatricula = function (codPersonal, moodleUserId, nombre) {
    if (!cursoSeleccionado) return;

    Swal.fire({
        title: '¿Desmatricular Usuario?',
        html: `¿Estás seguro de que deseas eliminar la matrícula de <br><b>${nombre}</b>? <br><br><span class="text-xs text-danger font-bold uppercase tracking-widest">Esta acción también lo desmatriculará de Moodle</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar matrícula',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
        showLoaderOnConfirm: true,
        preConfirm: (observacion) => {
            return axios.post('/api/capacitacion/desmatricular-usuario', {
                cursoId: cursoSeleccionado.codigo,
                codPersonal: codPersonal,
                moodleUserId: moodleUserId || null,
                observacion: 'Desmatriculación manual desde Intranet'
            })
                .then(response => {
                    if (!response.data.success) {
                        throw new Error(response.data.message || 'Error al desmatricular');
                    }
                    return response.data;
                })
                .catch(error => {
                    Swal.showValidationMessage(
                        `Error: ${error.message}`
                    );
                });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: '¡Eliminado!',
                text: result.value.message || 'El usuario ha sido desmatriculado correctamente.',
                icon: 'success'
            });
            // Recargar la lista
            cargarMatriculas(cursoSeleccionado.codigo);
        }
    });
};
