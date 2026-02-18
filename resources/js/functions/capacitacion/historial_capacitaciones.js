/**
 * Historial de Capacitaciones por Empleado
 * Script para visualizar el historial de cursos de un empleado específico
 */

import Swal from "sweetalert2";
import axios from "axios";
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';


// Configurar Axios para enviar cookies de sesión y el token CSRF
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// Estado global
let personalSeleccionado = null;
let tabulatorPersonal = null;

// Inicialización
document.addEventListener('DOMContentLoaded', () => {
    inicializarTablaPersonal(); // Nueva tabla de personal
    configurarEventos();
    cargarSucursales(); // Cargar lista de sucursales
    buscarPersonal('', 20000); // Cargar TODO el personal (hasta 20k)
});

/**
 * Cargar sucursales
 */
async function cargarSucursales() {
    try {
        const response = await axios.get('/api/get-sucursales');

        if (response.data.success) {
            const select = document.getElementById('filtroSucursal');

            response.data.sucursales.forEach((suc) => {
                const option = document.createElement('option');
                option.value = suc.sucursal;
                option.textContent = suc.sucursal;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error al cargar sucursales:', error);
    }
}

/**
 * Buscar personal
 */
async function buscarPersonal(termino, limite = 100, sucursal = '') {
    try {
        // Mostrar loading solo si es la carga inicial masiva
        if (limite > 100) {
            Swal.fire({
                title: 'Cargando personal...',
                text: 'Por favor espere',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });
        }

        const response = await axios.get('/api/buscar-personal-capacitacion', {
            params: { q: termino, limite: limite, sucursal: sucursal }
        });

        if (limite > 100) Swal.close();

        if (response.data.success) {
            if (tabulatorPersonal) {
                tabulatorPersonal.setData(response.data.personal);
            }
        }
    } catch (error) {
        if (limite > 100) Swal.close();
        console.error('Error al buscar personal:', error);
    }
}

/**
 * Inicializar Tabla de Personal (Tabulator)
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
        height: "450px", // Altura fija con scroll interno para la tabla
        langs: {
            "default": {
                "pagination": {
                    "counter": {
                        "showing": "Mostrando",
                        "of": "de",
                        "rows": "filas",
                        "pages": "páginas",
                    }
                }
            }
        },
        columns: [
            {
                title: "DNI",
                field: "dni",
                width: 100,
                headerSort: false
            },
            {
                title: "Nombre Completo",
                field: "nombre_completo",
                minWidth: 200
            },
            {
                title: "Cargo",
                field: "cargo",
                width: 150,
                headerSort: false,
                formatter: function (cell) {
                    return `<span class="text-xs text-gray-600">${cell.getValue() || ''}</span>`;
                }
            },
            {
                title: "Sucursal",
                field: "sucursal",
                width: 120,
                headerSort: false,
                formatter: function (cell) {
                    return `<span class="text-xs text-blue-600/80">${cell.getValue() || ''}</span>`;
                }
            },
            {
                title: "Cursos",
                field: "total_capacitaciones",
                width: 80,
                hozAlign: "center",
                headerSort: false,
                formatter: function (cell) {
                    const val = cell.getValue() || 0;
                    const color = val > 0 ? 'text-blue-600 font-medium' : 'text-gray-400';
                    return `<span class="${color}">${val}</span>`;
                }
            },
            {
                title: "Acción",
                headerSort: false,
                width: 80,
                hozAlign: "center",
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
 * Seleccionar persona y cargar historial
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
 * Mostrar Modal con Historial
 */
function mostrarModalHistorial(nombre, solicitudes) {
    let tablaHtml = '';

    if (solicitudes.length === 0) {
        tablaHtml = `
            <div class="text-center py-6 text-gray-500">
                <i class="i-tabler-school text-4xl mb-2 text-gray-300"></i>
                <p>No está matriculado o no tiene cursos.</p>
            </div>`;
    } else {
        const filas = solicitudes.map(s => {
            const formatDate = (dateStr) => {
                if (!dateStr) return '';
                const part = dateStr.includes('T') ? dateStr.split('T')[0] : dateStr.split(' ')[0];
                const [y, m, d] = part.split('-');
                return (y && m && d) ? `${d}/${m}/${y}` : dateStr;
            };

            let fechaStr = '-';
            if (s.fecha_inicio && s.fecha_final) {
                fechaStr = `${formatDate(s.fecha_inicio)} - ${formatDate(s.fecha_final)}`;
            } else if (s.fecha_matricula) {
                fechaStr = formatDate(s.fecha_matricula);
            }

            const estadoClass = obtenerClaseEstado(s.estado, true);

            return `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="p-2 text-left font-medium text-gray-700">${s.nombre_curso || ''}</td>
                    <td class="p-2 text-xs text-center text-gray-600 whitespace-nowrap">${fechaStr}</td>
                    <td class="p-2 text-xs text-center">${estadoClass}</td>
                </tr>
            `;
        }).join('');

        tablaHtml = `
            <div class="overflow-x-auto max-h-[400px]">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-500 uppercase bg-gray-50 sticky top-0">
                        <tr>
                            <th class="p-2">Curso</th>
                            <th class="p-2 text-center">Programación</th>
                            <th class="p-2 text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${filas}
                    </tbody>
                </table>
            </div>
        `;
    }

    Swal.fire({
        title: `<div class="text-lg">Historial de <br><small class="text-primary font-bold">${escapeHtml(nombre)}</small></div>`,
        html: tablaHtml,
        width: '700px',
        showCloseButton: true,
        showConfirmButton: false,
        customClass: {
            container: 'z-50'
        }
    });
}

/**
 * Configurar eventos
 */
function configurarEventos() {
    // Buscador local (filtra sobre los datos ya cargados)
    const inputBuscar = document.getElementById('buscarPersonal');
    if (inputBuscar) {
        inputBuscar.addEventListener('keyup', (e) => {
            const term = e.target.value.toLowerCase().trim();
            const sucursal = document.getElementById('filtroSucursal').value;

            if (tabulatorPersonal) {
                if (term === '' && sucursal === '') {
                    tabulatorPersonal.clearFilter();
                } else {
                    tabulatorPersonal.setFilter((data) => {
                        const nombre = (data.nombre_completo || '').toLowerCase();
                        const dni = (data.dni || '').toString().toLowerCase();
                        const matchTerm = term === '' || nombre.includes(term) || dni.includes(term);
                        const matchSucursal = sucursal === '' || data.sucursal === sucursal;
                        return matchTerm && matchSucursal;
                    });
                }
            }
        });
    }

    // Filtro de sucursal
    const selectSucursal = document.getElementById('filtroSucursal');
    if (selectSucursal) {
        selectSucursal.addEventListener('change', () => {
            const sucursal = selectSucursal.value;
            buscarPersonal('', 20000, sucursal);
        });
    }
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

function obtenerClaseEstado(estado, isBadge = false) {
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

