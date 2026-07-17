import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

const API = `${VITE_URL_APP}/api`;

function formatFecha(val) {
    if (!val || val === 'sin cambios') return '—';
    return String(val).substring(0, 10);
}

function getParams() {
    const params = { codSucursal: document.getElementById('filtroSucursal').value || '0' };
    const tipo   = document.getElementById('filtroTipo').value;
    const vig    = document.getElementById('filtroVigencia').value;
    const search = document.getElementById('buscarRep').value.trim();
    if (tipo)   params.tipo_per = tipo;
    if (vig)    params.vigencia = vig;
    if (search) params.search   = search;
    return params;
}

const tbl = new Tabulator('#tblReportePersonal', {
    ajaxURL:      `${API}/get-personal-reporte-personal`,
    ajaxParams:   getParams,
    ajaxResponse: (_url, _p, res) => {
        document.getElementById('cntTotal').textContent = res.total ?? 0;
        return res;
    },
    pagination:       true,
    paginationMode:   'remote',
    paginationSize:   20,
    height:           '520px',
    layout:           'fitColumns',
    responsiveLayout: 'collapse',
    rowHeader: {
        formatter: 'responsiveCollapse', width: 30, minWidth: 30,
        hozAlign: 'center', resizable: false, headerSort: false,
    },
    locale: 'es',
    langs: {
        es: {
            pagination: {
                first: 'Primero', first_title: 'Primera', last: 'Último', last_title: 'Última',
                prev: 'Anterior', prev_title: 'Anterior', next: 'Siguiente', next_title: 'Siguiente', all: 'Todo',
            },
            data: { empty: 'No hay datos disponibles', loading: 'Cargando...' },
        },
    },
    // Mismo estilo que gestion_dj: fondo rojo leve para inactivos
    rowFormatter: row => {
        const d = row.getData();
        if (d.vigencia && d.vigencia.toString().trim().toUpperCase() === 'NO') {
            row.getElement().style.backgroundColor = '#fff5f5';
        } else {
            row.getElement().style.backgroundColor = '';
        }
    },
    columns: [
        {
            title: 'N°', hozAlign: 'center', width: 55, headerSort: false,
            formatter: cell => {
                const pos = cell.getRow().getPosition(true);
                if (pos <= 0) return '';
                const page = cell.getTable().getPage() || 1;
                const size = cell.getTable().getPageSize() || 20;
                return ((page - 1) * size) + pos;
            },
        },
        { title: 'Código', field: 'codPersonal', hozAlign: 'center', width: 80 },
        {
            title: 'Apellidos', hozAlign: 'left', widthGrow: 2,
            formatter: cell => {
                const d = cell.getData();
                return `${d.apellido1 ?? ''} ${d.apellido2 ?? ''}`.trim() || '—';
            },
        },
        {
            title: 'Nombres', hozAlign: 'left', widthGrow: 1.5,
            formatter: cell => {
                const d = cell.getData();
                return `${d.NOMB_1 ?? ''} ${d.NOMB_2 ?? ''}`.trim() || '—';
            },
        },
        { title: 'Nro Documento', field: 'dni', hozAlign: 'center', width: 120 },
        { title: 'Sucursal',      field: 'sucursal', hozAlign: 'center', width: 90 },
        {
            title: 'Tipo', field: 'tipoPer', hozAlign: 'center', width: 100, headerSort: false,
            formatter: cell => {
                const v = cell.getValue() ?? '';
                if (!v) return '—';
                let color = 'border-gray-300 bg-gray-100 text-gray-800';
                if (v.startsWith('OPER'))      color = 'border-blue-300 bg-blue-100 text-blue-800';
                else if (v.startsWith('ADM'))  color = 'border-purple-300 bg-purple-100 text-purple-800';
                else if (v === 'ESP')          color = 'border-orange-300 bg-orange-100 text-orange-800';
                return `<span class="inline-flex items-center rounded-full border ${color} px-2 py-0.5 text-[10px] font-bold tracking-wider whitespace-nowrap">${v}</span>`;
            },
        },
        {
            title: 'F. Ingreso', field: 'FECH_INGRE', hozAlign: 'center', width: 105,
            formatter: cell => formatFecha(cell.getValue()),
        },
        {
            title: 'Cese', field: 'CESE', hozAlign: 'center', width: 80, headerSort: false,
            formatter: cell => cell.getValue() ?? '—',
        },
        {
            title: 'Tareo', field: 'TAREO', hozAlign: 'center', width: 80, headerSort: false,
            formatter: cell => cell.getValue() ?? '—',
        },
        {
            title: 'Lista Negra', field: 'LISTA_NEGRA', hozAlign: 'center', width: 100, headerSort: false,
            formatter: cell => cell.getValue() ?? '—',
        },
        {
            title: 'Acciones', hozAlign: 'center', width: 105, headerSort: false,
            formatter: cell => {
                const d      = cell.getData();
                const nombre = `${d.apellido1 ?? ''} ${d.apellido2 ?? ''}, ${d.NOMB_1 ?? ''} ${d.NOMB_2 ?? ''}`.trim();
                return `<button data-cod="${d.codPersonal}" data-nombre="${nombre}"
                    class="btn-ver-rep px-2 py-1 text-xs rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors whitespace-nowrap">
                    Ver detalles
                </button>`;
            },
        },
    ],
});

function reformat() { tbl.getRows('active').forEach(r => r.reformat()); }
tbl.on('dataLoaded', reformat);
tbl.on('pageLoaded', reformat);

// Filtros con debounce
let debTimer;
function refrescar() {
    clearTimeout(debTimer);
    debTimer = setTimeout(() => tbl.setPage(1), 350);
}

document.getElementById('buscarRep').addEventListener('input', refrescar);
document.getElementById('filtroSucursal').addEventListener('change', refrescar);
document.getElementById('filtroTipo').addEventListener('change', refrescar);
document.getElementById('filtroVigencia').addEventListener('change', refrescar);

document.getElementById('pageSizeRep').addEventListener('change', function () {
    tbl.setPageSize(parseInt(this.value));
    tbl.setPage(1);
});

// Modal ver detalles
const modalDetalle   = document.getElementById('modalDetallePersonal');
const modalTitulo    = document.getElementById('modalDetalleTitulo');
const modalCodigo    = document.getElementById('modalDetalleCodigo');
const btnCerrarModal = document.getElementById('btnCerrarModalDetalle');

function abrirModal(cod, nombre) {
    modalTitulo.textContent = nombre || 'Personal';
    modalCodigo.textContent = `Código: ${cod}`;
    modalDetalle.classList.remove('hidden');
    modalDetalle.classList.add('flex');
}

function cerrarModal() {
    modalDetalle.classList.add('hidden');
    modalDetalle.classList.remove('flex');
}

btnCerrarModal.addEventListener('click', cerrarModal);
modalDetalle.addEventListener('click', e => { if (e.target === modalDetalle) cerrarModal(); });

document.getElementById('tblReportePersonal').addEventListener('click', e => {
    const btn = e.target.closest('.btn-ver-rep');
    if (!btn) return;
    abrirModal(btn.dataset.cod, btn.dataset.nombre);
});
