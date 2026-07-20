import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import ExcelJS from 'exceljs';
import Swal from 'sweetalert2';

const API = `${VITE_URL_APP}/api`;

function formatFecha(val) {
    if (!val || val === 'sin cambios') return null;
    const s = String(val).substring(0, 10);
    const [y, m, d] = s.split('-');
    if (!y || !m || !d) return null;
    return `${d}/${m}/${y}`;
}

const fechaColors = {
    gray: 'bg-gray-100 border-gray-200 text-gray-500',
    red:  'bg-red-50  border-red-200  text-red-400',
    blue: 'bg-blue-50 border-blue-200 text-blue-400',
    dark: 'bg-neutral-200 border-neutral-400 text-neutral-600',
};

function fechaCell(val, icon = 'bx-calendar', color = 'gray') {
    const f = formatFecha(val);
    if (!f) return `<span class="text-gray-300 text-xs">—</span>`;
    const cls = fechaColors[color] ?? fechaColors.gray;
    return `<span class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[11px] font-medium whitespace-nowrap ${cls}">
        <i class='bx ${icon}'></i>${f}
    </span>`;
}

const tbl = new Tabulator('#tblReportePersonal', {
    pagination:       true,
    paginationMode:   'local',
    paginationSize:   20,
    placeholder:      'Cargando datos...',
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
        {
            title: 'Código', field: 'codPersonal', hozAlign: 'center', width: 85, headerSort: false,
            formatter: cell => {
                const d  = cell.getData();
                const v  = cell.getValue() ?? '';
                const ln = (d.en_lista_negra ?? 'NO').toString().trim().toUpperCase() === 'SI';
                if (!v) return `<span class="text-gray-300 text-xs">—</span>`;
                return `<span class="inline-flex items-center rounded border px-2 py-0.5 text-[11px] font-mono font-semibold whitespace-nowrap ${ln ? 'bg-red-50 border-red-300 text-red-600' : 'bg-slate-100 border-slate-300 text-slate-700'}">${v}</span>`;
            },
        },
        {
            title: 'Apellidos', hozAlign: 'left', widthGrow: 2,
            formatter: cell => {
                const d  = cell.getData();
                const ln = (d.en_lista_negra ?? 'NO').toString().trim().toUpperCase() === 'SI';
                const txt = `${d.apellido1 ?? ''} ${d.apellido2 ?? ''}`.trim() || '—';
                return `<span class="${ln ? 'text-red-600 font-semibold' : ''}">${txt}</span>`;
            },
        },
        {
            title: 'Nombres', hozAlign: 'left', widthGrow: 1.5,
            formatter: cell => {
                const d  = cell.getData();
                const ln = (d.en_lista_negra ?? 'NO').toString().trim().toUpperCase() === 'SI';
                const txt = `${d.NOMB_1 ?? ''} ${d.NOMB_2 ?? ''}`.trim() || '—';
                return `<span class="${ln ? 'text-red-600 font-semibold' : ''}">${txt}</span>`;
            },
        },
        {
            title: 'DNI', field: 'dni', hozAlign: 'center', width: 110,
            formatter: cell => {
                const d  = cell.getData();
                const v  = cell.getValue() ?? '';
                const ln = (d.en_lista_negra ?? 'NO').toString().trim().toUpperCase() === 'SI';
                if (!v) return `<span class="text-gray-300 text-xs">—</span>`;
                return `<span class="inline-flex items-center gap-1 text-xs font-mono ${ln ? 'text-red-600 font-bold' : 'text-gray-700'}">
                    <i class='bx bx-id-card ${ln ? 'text-red-400' : 'text-gray-400'}'></i>${v}
                </span>`;
            },
        },
        { title: 'Sucursal', field: 'sucursal', hozAlign: 'center', width: 85 },
        {
            title: 'Cargo', field: 'cargo', hozAlign: 'left', widthGrow: 2, headerSort: false,
            formatter: cell => {
                const v = cell.getValue() ?? '';
                if (!v) return `<span class="text-gray-300 text-xs">—</span>`;
                return `<span class="text-xs text-gray-700">${v}</span>`;
            },
        },
        {
            title: 'Tipo', field: 'tipoPer', hozAlign: 'center', width: 95, headerSort: false,
            formatter: cell => {
                const v = cell.getValue() ?? '';
                if (!v) return `<span class="text-gray-300 text-xs">—</span>`;
                let color = 'border-gray-300 bg-gray-100 text-gray-700';
                if (v.startsWith('OPER'))     color = 'border-blue-300 bg-blue-100 text-blue-800';
                else if (v.startsWith('ADM')) color = 'border-purple-300 bg-purple-100 text-purple-800';
                else if (v === 'ESP')         color = 'border-orange-300 bg-orange-100 text-orange-800';
                return `<span class="inline-flex items-center rounded-full border ${color} px-2 py-0.5 text-[10px] font-bold tracking-wider whitespace-nowrap">${v}</span>`;
            },
        },
        {
            title: 'F. Ingreso', field: 'FECH_INGRE', hozAlign: 'center', width: 110,
            formatter: cell => fechaCell(cell.getValue()),
        },
        {
            title: 'Ult. F. Cese', field: 'FECH_CESE', hozAlign: 'center', width: 115,
            formatter: cell => fechaCell(cell.getValue(), 'bx-calendar-x', 'red'),
        },
        {
            title: 'Ult. Tareaje', field: 'ultima_fecha_tareaje', hozAlign: 'center', width: 115,
            formatter: cell => fechaCell(cell.getValue(), 'bx-calendar-check', 'blue'),
        },
        {
            title: 'Lista Negra', field: 'en_lista_negra', hozAlign: 'center', width: 95,
            formatter: cell => {
                const v = (cell.getValue() ?? 'NO').toString().trim().toUpperCase();
                if (v === 'SI') return `<span class="inline-flex items-center gap-1 rounded-md border px-2 py-0.5 text-[11px] whitespace-nowrap bg-black border-black text-white font-bold">
                    <i class='bx bx-block'></i>LN
                </span>`;
                return `<span class="text-gray-300 text-xs">—</span>`;
            },
        },
        {
            title: 'Acciones', hozAlign: 'center', width: 120, headerSort: false,
            formatter: cell => {
                const d      = cell.getData();
                const nombre = `${d.apellido1 ?? ''} ${d.apellido2 ?? ''}, ${d.NOMB_1 ?? ''} ${d.NOMB_2 ?? ''}`.trim();
                return `<button data-cod="${d.codPersonal}" data-nombre="${nombre}"
                    class="btn-ver-rep inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded-lg bg-primary/10 text-primary hover:bg-primary hover:text-white transition-colors whitespace-nowrap">
                    <i class='bx bx-show'></i> Más detalles
                </button>`;
            },
        },
    ],
});

function reformat() { tbl.getRows('active').forEach(r => r.reformat()); }
tbl.on('dataLoaded',     reformat);
tbl.on('pageLoaded',     reformat);
tbl.on('renderComplete', reformat);

// ── Carga desde el servidor (sucursal + tipo + vigencia) ─────────────────────
const repLoader = document.getElementById('repLoadingIndicator');

async function cargarDatos() {
    const params = new URLSearchParams({
        codSucursal: document.getElementById('filtroSucursal').value || '0',
        page: 1,
        size: 99999,
    });
    const tipo = document.getElementById('filtroTipo').value;
    const vig  = document.getElementById('filtroVigencia').value;
    if (tipo) params.set('tipo_per', tipo);
    if (vig)  params.set('vigencia', vig);

    const controlesBloquear = [
        document.getElementById('filtroSucursal'),
        document.getElementById('filtroTipo'),
        document.getElementById('filtroVigencia'),
        document.getElementById('buscarRep'),
        document.getElementById('btnExportExcelRep'),
        document.getElementById('btnExportPdfRep'),
    ];

    const bloquear = () => controlesBloquear.forEach(el => {
        if (!el) return;
        el.disabled = true;
        el.classList.add('opacity-40', 'pointer-events-none');
    });
    const desbloquear = () => controlesBloquear.forEach(el => {
        if (!el) return;
        el.disabled = false;
        el.classList.remove('opacity-40', 'pointer-events-none');
    });

    tbl.setData([]);
    bloquear();
    repLoader.classList.remove('hidden');
    repLoader.classList.add('flex');
    try {
        const res  = await fetch(`${API}/get-personal-reporte-personal?${params}`);
        const json = await res.json();

        const data = json.data ?? [];
        const vi   = json.totalVigentes ?? 0;
        const no   = json.totalCesados  ?? 0;
        const ln   = data.filter(d => (d.en_lista_negra ?? 'NO').toString().trim().toUpperCase() === 'SI').length;

        document.getElementById('cntTotal').textContent      = vi + no;
        document.getElementById('cntVigentes').textContent   = vi;
        document.getElementById('cntCesados').textContent    = no;
        document.getElementById('cntListaNegra').textContent = ln;

        // Diferir setData para no bloquear el hilo principal
        await new Promise(r => setTimeout(r, 0));
        tbl.setData(data);
    } catch (e) {
        console.error('Error cargando datos:', e);
    } finally {
        desbloquear();
        repLoader.classList.add('hidden');
        repLoader.classList.remove('flex');
    }
}

// ── Búsqueda local (sin request al servidor) ─────────────────────────────────
let buscarTimer;
document.getElementById('buscarRep').addEventListener('input', function () {
    clearTimeout(buscarTimer);
    buscarTimer = setTimeout(() => {
        const s = this.value.trim().toLowerCase();
        if (!s) { tbl.clearFilter(); return; }
        tbl.setFilter(d =>
            (d.apellido1   ?? '').toLowerCase().includes(s) ||
            (d.apellido2   ?? '').toLowerCase().includes(s) ||
            (d.NOMB_1      ?? '').toLowerCase().includes(s) ||
            (d.NOMB_2      ?? '').toLowerCase().includes(s) ||
            (d.dni         ?? '').toLowerCase().includes(s) ||
            (d.codPersonal ?? '').toLowerCase().includes(s)
        );
    }, 200);
});

// ── Filtros del servidor: recargan todos los datos ───────────────────────────
document.getElementById('filtroSucursal').addEventListener('change', cargarDatos);
document.getElementById('filtroTipo').addEventListener('change', cargarDatos);
document.getElementById('filtroVigencia').addEventListener('change', cargarDatos);

document.getElementById('pageSizeRep').addEventListener('change', function () {
    tbl.setPageSize(parseInt(this.value));
    tbl.setPage(1);
});

// ── Modal ver detalles ───────────────────────────────────────────────────────
let modalDatosActuales = { rowData: {}, ceses: [], tareajes: [], listaNegra: [] };

const modalDetalle   = document.getElementById('modalDetallePersonal');
const modalTitulo    = document.getElementById('modalDetalleTitulo');
const modalCodigo    = document.getElementById('modalDetalleCodigo');
const btnCerrarModal = document.getElementById('btnCerrarModalDetalle');

function campo(label, valor, extra = '') {
    return `<div class="bg-gray-50 rounded-lg px-3 py-2 ${extra}">
        <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">${label}</p>
        <p class="text-sm text-gray-700 font-medium mt-0.5">${valor || '—'}</p>
    </div>`;
}

const TIPO_PER_TEXTO = {
    'OPER 4°': 'Operativo 4°',
    'OPER 5°': 'Operativo 5°',
    'ADM 4°':  'Administrativo 4°',
    'ADM 5°':  'Administrativo 5°',
    'ESP':     'Especiales',
};
function tipoPerCompleto(val) {
    return TIPO_PER_TEXTO[val] ?? val ?? '—';
}

function campoColor(label, valor, esquema) {
    const esqs = {
        green: { bg: 'bg-green-50 border border-green-200', lbl: 'text-green-600', val: 'text-green-800 font-semibold' },
        red:   { bg: 'bg-red-50 border border-red-200',     lbl: 'text-red-500',   val: 'text-red-700 font-semibold'   },
        dark:  { bg: 'bg-neutral-100 border border-neutral-300', lbl: 'text-neutral-500', val: 'text-neutral-900 font-semibold' },
    };
    const e = esqs[esquema] ?? esqs.green;
    return `<div class="${e.bg} rounded-lg px-3 py-2">
        <p class="text-[10px] ${e.lbl} font-medium uppercase tracking-wide">${label}</p>
        <p class="text-sm ${e.val} mt-0.5">${valor || '—'}</p>
    </div>`;
}

function renderFilas(columnas, filas, highlightFirst = false) {
    const ths = columnas.map(c =>
        `<th class="px-3 py-2 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">${c.label}</th>`
    ).join('');
    const trs = filas.map((f, i) => {
        const tds = columnas.map(c => {
            const v = f[c.field] ?? null;
            if (c.date) return `<td class="px-3 py-2 text-xs whitespace-nowrap ${highlightFirst && i === 0 ? 'font-semibold' : 'text-gray-700'}">${fechaCell(v, c.icon ?? 'bx-calendar', c.color ?? 'gray')}</td>`;
            return `<td class="px-3 py-2 text-xs ${highlightFirst && i === 0 ? 'text-gray-800 font-semibold' : 'text-gray-700'}">${v ?? '—'}</td>`;
        }).join('');
        const rowCls = highlightFirst && i === 0
            ? 'border-t border-gray-100 bg-amber-50 border-l-4 border-l-amber-400'
            : 'border-t border-gray-100 hover:bg-gray-50';
        return `<tr class="${rowCls}">${tds}</tr>`;
    }).join('');
    return `<div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr>${ths}</tr></thead>
            <tbody>${trs}</tbody>
        </table>
    </div>`;
}

function renderTimeline(contenedorId, filas, vacio) {
    const el = document.getElementById(contenedorId);
    if (!filas || !filas.length) {
        el.innerHTML = `<p class="text-sm text-gray-400 text-center py-4">${vacio}</p>`;
        return;
    }

    // Ordenar ascendente para asignar etiquetas correctas
    const asc = [...filas].sort((a, b) =>
        (a.FEC_INGRESO ?? '').toString().localeCompare((b.FEC_INGRESO ?? '').toString())
    );

    // Enriquecer con label, luego invertir para mostrar el más reciente primero
    const conLabel = asc.map((f, i) => ({ ...f, _label: i === 0 ? 'Ingreso inicial' : `Reingreso #${i}`, _esReingreso: i > 0 }));
    const desc = [...conLabel].reverse();

    const items = desc.map((f, idx) => {
        const esPrimero    = idx === 0;
        const dotCls       = f._esReingreso ? 'bg-blue-500 ring-blue-200'  : 'bg-green-500 ring-green-200';
        const cardCls      = f._esReingreso ? 'bg-blue-50 border-blue-200' : 'bg-green-50 border-green-200';
        const labelCls     = f._esReingreso ? 'text-blue-600'  : 'text-green-600';
        const fechaIngCls  = f._esReingreso ? 'text-blue-700'  : 'text-green-700';
        const primeroExtra = esPrimero ? 'ring-2 ring-offset-1 ring-blue-300 shadow-sm' : '';

        const fi = formatFecha(f.FEC_INGRESO) ?? '—';
        const fc = formatFecha(f.FEC_CESE);

        const recienteBadge = esPrimero
            ? `<span class="ml-auto text-[9px] font-bold uppercase tracking-wider bg-white border border-blue-300 text-blue-500 rounded-full px-1.5 py-0.5">Último</span>`
            : '';

        const ceseHtml = fc
            ? `<div class="flex flex-wrap items-center gap-1 text-xs text-red-500 mt-1.5">
                <i class='bx bx-calendar-x'></i><span>Cese: ${fc}</span>
                ${f.OBSE_CESE ? `<span class="text-gray-400">— ${f.OBSE_CESE}</span>` : ''}
               </div>`
            : `<div class="flex items-center gap-1 text-xs text-emerald-600 font-medium mt-1.5">
                <i class='bx bx-check-circle'></i><span>Vigente actualmente</span>
               </div>`;

        return `<div class="relative">
            <div class="absolute -left-[17px] top-2 w-3 h-3 rounded-full border-2 border-white ring-2 ${dotCls}"></div>
            <div class="border rounded-lg px-3 py-2.5 ${cardCls} ${primeroExtra}">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-bold uppercase tracking-wider ${labelCls}">${f._label}</span>
                    <span class="text-xs font-medium ${fechaIngCls}">
                        <i class='bx bx-log-in-circle'></i> ${fi}
                    </span>
                    ${recienteBadge}
                </div>
                ${ceseHtml}
            </div>
        </div>`;
    }).join('');

    el.innerHTML = `<div class="relative pl-6 space-y-3">
        <div class="absolute left-2 top-0 bottom-0 w-0.5 bg-gray-200 rounded-full"></div>
        ${items}
    </div>`;
}

function renderPaginado(contenedorId, filas, columnas, vacio, pageSize = 10, highlightFirst = false) {
    if (!filas || !filas.length) {
        document.getElementById(contenedorId).innerHTML =
            `<p class="text-sm text-gray-400 text-center py-4">${vacio}</p>`;
        return;
    }
    let pag = 1;
    const totalPags = Math.max(1, Math.ceil(filas.length / pageSize));

    function render() {
        const inicio = (pag - 1) * pageSize;
        const slice  = filas.slice(inicio, inicio + pageSize);
        const paginador = totalPags > 1 ? `
            <div class="flex items-center justify-between mt-2 px-1">
                <span class="text-[11px] text-gray-400">${inicio + 1}–${Math.min(inicio + pageSize, filas.length)} de ${filas.length}</span>
                <div class="flex items-center gap-1">
                    <button data-dir="-1" class="btn-pag px-2.5 py-1 text-xs rounded border border-gray-300 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed" ${pag === 1 ? 'disabled' : ''}>‹</button>
                    <span class="text-xs text-gray-500 px-1">${pag} / ${totalPags}</span>
                    <button data-dir="1" class="btn-pag px-2.5 py-1 text-xs rounded border border-gray-300 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed" ${pag === totalPags ? 'disabled' : ''}>›</button>
                </div>
            </div>` : '';

        const el = document.getElementById(contenedorId);
        // Solo destacar primera fila en la primera página
        el.innerHTML = renderFilas(columnas, slice, highlightFirst && pag === 1) + paginador;
        el.querySelectorAll('.btn-pag').forEach(btn => {
            btn.addEventListener('click', () => {
                const dir = parseInt(btn.dataset.dir);
                if (dir === -1 && pag > 1)          { pag--; render(); }
                if (dir ===  1 && pag < totalPags)  { pag++; render(); }
            });
        });
    }
    render();
}

function cargandoSpinner() {
    return `<div class="flex items-center justify-center gap-2 py-6 text-gray-400 text-sm">
        <i class='bx bx-loader-alt bx-spin'></i> Cargando...
    </div>`;
}

async function abrirModal(cod, rowData) {
    modalDatosActuales = { rowData, ceses: [], tareajes: [], listaNegra: [] };

    const nombre   = `${rowData.apellido1 ?? ''} ${rowData.apellido2 ?? ''}, ${rowData.NOMB_1 ?? ''} ${rowData.NOMB_2 ?? ''}`.trim();
    const vigente  = (rowData.vigencia ?? 'SI').toString().trim().toUpperCase() === 'SI';
    const esLN     = (rowData.en_lista_negra ?? 'NO').toString().trim().toUpperCase() === 'SI';

    modalTitulo.textContent = nombre || 'Personal';
    modalCodigo.textContent = `Cód: ${cod}${rowData.dni ? '  •  DNI: ' + rowData.dni : ''}`;

    // Badge vigencia en header
    const badgeVig = document.getElementById('modalVigenciaBadge');
    if (badgeVig) {
        badgeVig.className = vigente
            ? 'inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full border bg-green-50 border-green-300 text-green-700'
            : 'inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full border bg-red-50 border-red-300 text-red-600';
        badgeVig.innerHTML = vigente
            ? `<i class='bx bx-check-circle'></i> VIGENTE`
            : `<i class='bx bx-x-circle'></i> CESADO`;
    }

    // Foto
    const imgFoto         = document.getElementById('modalFotoPersonal');
    const fotoPlaceholder = document.getElementById('modalFotoPlaceholder');
    imgFoto.classList.add('hidden');
    fotoPlaceholder.classList.remove('hidden');
    fetch(`${VITE_URL_APP}/api/dj/proxy-foto?codi_pers=${cod}`)
        .then(r => r.json())
        .then(j => {
            if (j.success && j.base64) {
                imgFoto.src = j.base64;
                imgFoto.classList.remove('hidden');
                fotoPlaceholder.classList.add('hidden');
            }
        })
        .catch(() => {});

    // Info personal desde la fila (sin request)
    const lnCampo = esLN
        ? `<div class="bg-black rounded-lg px-3 py-2">
            <p class="text-[10px] text-neutral-400 font-medium uppercase tracking-wide">Lista Negra</p>
            <p class="text-sm text-white font-bold mt-0.5 flex items-center gap-1"><i class='bx bx-block'></i> SÍ</p>
           </div>`
        : `<div class="bg-gray-50 rounded-lg px-3 py-2">
            <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">Lista Negra</p>
            <p class="text-sm text-gray-400 font-medium mt-0.5">NO</p>
           </div>`;

    document.getElementById('modalInfoPersonal').innerHTML = [
        campo('DNI',              rowData.dni),
        campo('Sucursal',         rowData.sucursal),
        campo('Cargo',            rowData.cargo),
        campo('Tipo',             tipoPerCompleto(rowData.tipoPer)),
        campoColor('Ult. F. Ingreso', formatFecha(rowData.FECH_INGRE), 'green'),
        campoColor('Ult. F. Cese',    formatFecha(rowData.FECH_CESE),  'red'),
        lnCampo,
        campo('Correo',           rowData.correo, 'col-span-2 md:col-span-2'),
    ].join('');

    // Spinners mientras carga el historial
    document.getElementById('modalHistorialCeses').innerHTML       = cargandoSpinner();
    document.getElementById('modalHistorialTareajes').innerHTML    = cargandoSpinner();
    document.getElementById('modalHistorialListaNegra').innerHTML  = cargandoSpinner();

    modalDetalle.classList.remove('hidden');
    modalDetalle.classList.add('flex');

    // Fetch historial
    try {
        const res  = await fetch(`${API}/get-personal-reporte-personal/detalle/${cod}`);
        const json = await res.json();

        modalDatosActuales.ceses      = json.ceses      ?? [];
        modalDatosActuales.tareajes   = json.tareajes   ?? [];
        modalDatosActuales.listaNegra = json.lista_negra ?? [];

        renderTimeline('modalHistorialCeses', json.ceses, 'Sin registros de cese / reingreso');

        renderPaginado('modalHistorialTareajes', json.tareajes, [
            { label: 'Ult Fecha',   field: 'fecha',   date: true, icon: 'bx-calendar-check', color: 'blue' },
            { label: 'Cliente', field: 'cliente' },
            { label: 'Puesto',  field: 'puesto'  },
        ], 'Sin registros de tareaje', 8, true);

        const lnDesc = [...(json.lista_negra ?? [])].sort((a, b) =>
            (b.FEC_CESE ?? '').toString().localeCompare((a.FEC_CESE ?? '').toString())
        );
        renderPaginado('modalHistorialListaNegra', lnDesc, [
            { label: 'F. Cese',  field: 'FEC_CESE', date: true, icon: 'bx-calendar-x', color: 'dark' },
            { label: 'Motivo',   field: 'motivo' },
            { label: 'Observ.',  field: 'OBSE_CESE' },
        ], 'Sin registros de lista negra', 8, true);

    } catch (e) {
        const err = `<p class="text-sm text-red-400 text-center py-4">Error al cargar el historial</p>`;
        document.getElementById('modalHistorialCeses').innerHTML      = err;
        document.getElementById('modalHistorialTareajes').innerHTML   = err;
        document.getElementById('modalHistorialListaNegra').innerHTML = err;
    }
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
    const rows = tbl.searchRows('codPersonal', '=', btn.dataset.cod);
    const rowData = rows.length ? rows[0].getData() : {};
    abrirModal(btn.dataset.cod, rowData);
});

// ── Carga inicial (esperar que la tabla esté lista) ──────────────────────────
tbl.on('tableBuilt', cargarDatos);

// ── Exportación individual (modal) ───────────────────────────────────────────

function nombreCompleto(d) {
    return `${d.apellido1 ?? ''} ${d.apellido2 ?? ''}, ${d.NOMB_1 ?? ''} ${d.NOMB_2 ?? ''}`.trim();
}

// Excel individual
document.getElementById('btnExportDetalleExcel').addEventListener('click', async () => {
    const { rowData, ceses, tareajes, listaNegra } = modalDatosActuales;
    if (!rowData.codPersonal) return Swal.fire('Sin datos', 'Abre el detalle de un personal primero.', 'warning');

    Swal.fire({ title: 'Generando Excel...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const nombre   = nombreCompleto(rowData);
        const fechaStr = fechaHoraStr();
        const logoB64  = await cargarLogoBase64();

        const workbook  = new ExcelJS.Workbook();
        const ws        = workbook.addWorksheet('Ficha Personal', { views: [{ showGridLines: false }] });

        // Logo
        if (logoB64) {
            const imgId = workbook.addImage({ base64: logoB64, extension: 'png' });
            ws.addImage(imgId, { tl: { col: 0, row: 0 }, ext: { width: 150, height: 45 } });
        }

        // Encabezado
        ws.mergeCells('A1:F1');
        const t1 = ws.getCell('A1');
        t1.value = 'SISTEMA INTEGRADO SOLMAR – SISOL WEB';
        t1.font  = { bold: true, color: { argb: 'FF990000' }, size: 11 };
        t1.alignment = { horizontal: 'center', vertical: 'middle' };

        ws.mergeCells('A2:F2');
        const t2 = ws.getCell('A2');
        t2.value = 'FICHA INDIVIDUAL DE PERSONAL';
        t2.font  = { bold: true, size: 13 };
        t2.alignment = { horizontal: 'center', vertical: 'middle' };

        ws.mergeCells('A3:F3');
        const t3 = ws.getCell('A3');
        t3.value = nombre;
        t3.font  = { bold: true, size: 11 };
        t3.alignment = { horizontal: 'center', vertical: 'middle' };

        ws.getCell('F1').value = `Generado: ${fechaStr}`;
        ws.getCell('F1').font  = { size: 8, color: { argb: 'FF888888' } };
        ws.getCell('F1').alignment = { horizontal: 'right' };

        // ── Sección 1: Datos personales ──
        let fila = 5;
        const secHeader = (txt, cols = 'A:F') => {
            const ref = `A${fila}:F${fila}`;
            ws.mergeCells(ref);
            const c = ws.getCell(`A${fila}`);
            c.value = txt;
            c.font  = { bold: true, color: { argb: 'FFFFFFFF' }, size: 10 };
            c.fill  = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF374151' } };
            c.alignment = { horizontal: 'left', vertical: 'middle', indent: 1 };
            c.border = { top:{style:'thin'}, bottom:{style:'thin'} };
            fila++;
        };

        const infoFila = (label, valor) => {
            ws.getCell(`A${fila}`).value = label;
            ws.getCell(`A${fila}`).font  = { bold: true, color: { argb: 'FF6B7280' }, size: 9 };
            ws.getCell(`A${fila}`).fill  = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF9FAFB' } };
            ws.mergeCells(`B${fila}:F${fila}`);
            ws.getCell(`B${fila}`).value = valor || '—';
            ws.getCell(`B${fila}`).font  = { size: 9 };
            [`A${fila}`,`B${fila}`].forEach(r => {
                ws.getCell(r).border = { top:{style:'thin'}, left:{style:'thin'}, bottom:{style:'thin'}, right:{style:'thin'} };
            });
            fila++;
        };

        secHeader('INFORMACIÓN DEL PERSONAL');
        infoFila('Código',    rowData.codPersonal);
        infoFila('Apellidos y Nombres', nombre);
        infoFila('DNI',       rowData.dni);
        infoFila('Sucursal',  rowData.sucursal);
        infoFila('Cargo',     rowData.cargo);
        infoFila('Tipo',      rowData.tipoPer);
        infoFila('F. Ingreso', formatFecha(rowData.FECH_INGRE));
        infoFila('Ult. F. Cese', formatFecha(rowData.FECH_CESE));
        infoFila('Ult. Tareaje', formatFecha(rowData.ultima_fecha_tareaje));
        infoFila('Lista Negra', (rowData.en_lista_negra ?? 'NO').toString().trim().toUpperCase() === 'SI' ? 'SÍ' : 'NO');
        infoFila('Correo',    rowData.correo);

        const tablaSeccion = (titulo, cabeceras, filas) => {
            fila++;
            secHeader(titulo);
            if (!filas.length) {
                ws.mergeCells(`A${fila}:F${fila}`);
                ws.getCell(`A${fila}`).value = 'Sin registros';
                ws.getCell(`A${fila}`).font  = { italic: true, color: { argb: 'FF9CA3AF' }, size: 9 };
                ws.getCell(`A${fila}`).alignment = { horizontal: 'center' };
                fila++;
                return;
            }
            // Cabecera tabla
            cabeceras.forEach((h, ci) => {
                const col = String.fromCharCode(65 + ci);
                const c   = ws.getCell(`${col}${fila}`);
                c.value   = h;
                c.font    = { bold: true, color: { argb: 'FFFFFFFF' }, size: 9 };
                c.fill    = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF6B7280' } };
                c.alignment = { horizontal: 'center', vertical: 'middle' };
                c.border  = { top:{style:'thin'}, left:{style:'thin'}, bottom:{style:'thin'}, right:{style:'thin'} };
            });
            fila++;
            // Datos
            filas.forEach((f2, ri) => {
                f2.forEach((val, ci) => {
                    const col = String.fromCharCode(65 + ci);
                    const c   = ws.getCell(`${col}${fila}`);
                    c.value   = val ?? '—';
                    c.font    = { size: 9 };
                    c.fill    = ri % 2 === 0
                        ? { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFFFFF' } }
                        : { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFF9FAFB' } };
                    c.border  = { top:{style:'thin'}, left:{style:'thin'}, bottom:{style:'thin'}, right:{style:'thin'} };
                    c.alignment = { vertical: 'middle', horizontal: ci === 0 ? 'center' : 'left' };
                });
                fila++;
            });
        };

        // Ceses (ordenados más reciente primero)
        const cesOrd = [...ceses].sort((a,b) => (b.FEC_INGRESO??'').localeCompare(a.FEC_INGRESO??''));
        tablaSeccion('HISTORIAL DE CESES',
            ['#','F. Ingreso','F. Cese','Observación'],
            cesOrd.map((c, i) => [i+1, formatFecha(c.FEC_INGRESO)??'—', formatFecha(c.FEC_CESE)??'Vigente', c.OBSE_CESE??'—'])
        );

        // Tareajes
        tablaSeccion('HISTORIAL DE TAREAJES',
            ['#','Fecha','Cliente','Puesto'],
            tareajes.map((t, i) => [i+1, formatFecha(t.fecha)??'—', t.cliente??'—', t.puesto??'—'])
        );

        // Lista negra
        const lnOrd = [...listaNegra].sort((a,b) => (b.FEC_CESE??'').localeCompare(a.FEC_CESE??''));
        tablaSeccion('LISTA NEGRA',
            ['#','F. Cese','Motivo','Observación'],
            lnOrd.map((l, i) => [i+1, formatFecha(l.FEC_CESE)??'—', l.motivo??'—', l.OBSE_CESE??'—'])
        );

        ws.columns = [{width:18},{width:30},{width:18},{width:20},{width:20},{width:28}];

        const buffer = await workbook.xlsx.writeBuffer();
        const blob   = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const link   = document.createElement('a');
        link.href    = URL.createObjectURL(blob);
        link.download = `FichaPersonal_${rowData.codPersonal}_${(rowData.apellido1??'').replace(/ /g,'_')}_${timestampArchivo()}.xlsx`;
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
        Swal.close();
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'No se pudo generar el Excel.', 'error');
    }
});

// PDF individual
document.getElementById('btnExportDetallePdf').addEventListener('click', async () => {
    const { rowData, ceses, tareajes, listaNegra } = modalDatosActuales;
    if (!rowData.codPersonal) return Swal.fire('Sin datos', 'Abre el detalle de un personal primero.', 'warning');

    Swal.fire({ title: 'Generando PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const nombre   = nombreCompleto(rowData);
        const fechaStr = fechaHoraStr();
        const logoB64  = await cargarLogoBase64();

        const { jsPDF } = window.jspdf;
        const doc  = new jsPDF('portrait');
        const pageW = doc.internal.pageSize.getWidth();
        let curY = 10;

        // Encabezado página 1
        const dibujarHeader = (num) => {
            if (num !== 1) return;
            if (logoB64) doc.addImage(logoB64, 'PNG', 14, curY, 32, 10);
            doc.setFontSize(9); doc.setTextColor(180,0,0); doc.setFont('helvetica','bold');
            doc.text('SISTEMA INTEGRADO SOLMAR – SISOL WEB', pageW/2, curY+5, { align:'center' });
            doc.setFontSize(11); doc.setTextColor(0,0,0);
            doc.text('FICHA INDIVIDUAL DE PERSONAL', pageW/2, curY+11, { align:'center' });
            doc.setFontSize(10); doc.setFont('helvetica','bold');
            doc.text(nombre, pageW/2, curY+17, { align:'center' });
            doc.setFontSize(7); doc.setFont('helvetica','normal'); doc.setTextColor(120,120,120);
            doc.text(`Generado: ${fechaStr}`, pageW-14, curY+5, { align:'right' });
            curY += 24;
        };

        dibujarHeader(1);

        // Helper título de sección
        const secTitle = (txt) => {
            doc.setFillColor(55,65,81); doc.rect(14, curY, pageW-28, 7, 'F');
            doc.setTextColor(255,255,255); doc.setFontSize(8); doc.setFont('helvetica','bold');
            doc.text(txt, 16, curY+5);
            curY += 9;
        };

        // Sección 1: Info personal
        secTitle('INFORMACIÓN DEL PERSONAL');
        const infoItems = [
            ['Código',             rowData.codPersonal],
            ['Apellidos y Nombres', nombre],
            ['DNI',               rowData.dni],
            ['Sucursal',          rowData.sucursal],
            ['Cargo',             rowData.cargo],
            ['Tipo',              rowData.tipoPer],
            ['F. Ingreso',        formatFecha(rowData.FECH_INGRE)],
            ['Ult. F. Cese',      formatFecha(rowData.FECH_CESE)],
            ['Ult. Tareaje',      formatFecha(rowData.ultima_fecha_tareaje)],
            ['Lista Negra',       (rowData.en_lista_negra??'NO').toString().trim().toUpperCase()==='SI'?'SÍ':'NO'],
            ['Correo',            rowData.correo],
        ];
        doc.autoTable({
            startY: curY, theme: 'plain',
            styles: { fontSize: 8, cellPadding: 2 },
            columnStyles: { 0: { fontStyle:'bold', fillColor:[249,250,251], cellWidth:45, textColor:[107,114,128] }, 1: { cellWidth: pageW-28-45 } },
            body: infoItems.map(([l,v]) => [l, v||'—']),
            didDrawPage: d => { curY = d.cursor.y + 4; },
        });
        curY = (doc.lastAutoTable?.finalY ?? curY) + 6;

        // Helper tabla de sección
        const tablaSecPDF = (titulo, head, body) => {
            secTitle(titulo);
            if (!body.length) {
                doc.setFontSize(8); doc.setTextColor(156,163,175); doc.setFont('helvetica','italic');
                doc.text('Sin registros', pageW/2, curY+4, { align:'center' });
                curY += 10;
                return;
            }
            doc.autoTable({
                startY: curY, theme: 'grid',
                headStyles: { fillColor:[107,114,128], textColor:[255,255,255], fontSize:7, fontStyle:'bold', halign:'center' },
                bodyStyles: { fontSize: 7 },
                columnStyles: { 0: { halign:'center', cellWidth:10 } },
                head: [head],
                body,
                didDrawPage: d => { if(d.pageNumber>1) curY = 14; },
            });
            curY = (doc.lastAutoTable?.finalY ?? curY) + 6;
        };

        // Ceses
        const cesOrd = [...ceses].sort((a,b)=>(b.FEC_INGRESO??'').localeCompare(a.FEC_INGRESO??''));
        tablaSecPDF('HISTORIAL DE INGRESOS / CESES',
            ['#','F. Ingreso','F. Cese','Observación'],
            cesOrd.map((c,i)=>[i+1, formatFecha(c.FEC_INGRESO)??'—', formatFecha(c.FEC_CESE)??'Vigente', c.OBSE_CESE??'—'])
        );

        // Tareajes
        tablaSecPDF('HISTORIAL DE TAREAJES',
            ['#','Fecha','Cliente','Puesto'],
            tareajes.map((t,i)=>[i+1, formatFecha(t.fecha)??'—', t.cliente??'—', t.puesto??'—'])
        );

        // Lista negra
        const lnOrd = [...listaNegra].sort((a,b)=>(b.FEC_CESE??'').localeCompare(a.FEC_CESE??''));
        tablaSecPDF('LISTA NEGRA',
            ['#','F. Cese','Motivo','Observación'],
            lnOrd.map((l,i)=>[i+1, formatFecha(l.FEC_CESE)??'—', l.motivo??'—', l.OBSE_CESE??'—'])
        );

        doc.save(`FichaPersonal_${rowData.codPersonal}_${(rowData.apellido1??'').replace(/ /g,'_')}_${timestampArchivo()}.pdf`);
        Swal.close();
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'No se pudo generar el PDF.', 'error');
    }
});

// ── Exportación ──────────────────────────────────────────────────────────────
function getFiltrosRep() {
    const selSuc = document.getElementById('filtroSucursal');
    const selVig = document.getElementById('filtroVigencia');
    const selTip = document.getElementById('filtroTipo');
    const sucText = selSuc.options[selSuc.selectedIndex]?.text || 'TODAS';
    const vigText = selVig.value || 'TODOS';
    const tipText = selTip.value || '';
    return { sucursal: sucText.toUpperCase(), vigencia: vigText, tipo: tipText };
}

function filaNombre(d) {
    return `${d.apellido1 ?? ''} ${d.apellido2 ?? ''}, ${d.NOMB_1 ?? ''} ${d.NOMB_2 ?? ''}`.trim();
}

function filaParaExport(d, i) {
    return [
        i + 1,
        d.codPersonal ?? '—',
        filaNombre(d),
        d.dni ?? '—',
        d.sucursal ?? '—',
        d.cargo ?? '—',
        d.tipoPer ?? '—',
        formatFecha(d.FECH_INGRE) ?? '—',
        formatFecha(d.FECH_CESE) ?? '—',
        formatFecha(d.ultima_fecha_tareaje) ?? '—',
        (d.en_lista_negra ?? 'NO').toString().trim().toUpperCase() === 'SI' ? 'SÍ' : 'NO',
    ];
}

async function cargarLogoBase64() {
    if (!window.logoUrl) return null;
    try {
        const res  = await fetch(window.logoUrl);
        const blob = await res.blob();
        return await new Promise(resolve => {
            const r = new FileReader();
            r.onloadend = () => resolve(r.result);
            r.readAsDataURL(blob);
        });
    } catch { return null; }
}

function timestampArchivo() {
    const f = new Date();
    return `${String(f.getDate()).padStart(2,'0')}_${String(f.getMonth()+1).padStart(2,'0')}_${f.getFullYear()}`;
}

function fechaHoraStr() {
    const f = new Date();
    return `${String(f.getDate()).padStart(2,'0')}/${String(f.getMonth()+1).padStart(2,'0')}/${f.getFullYear()} ${String(f.getHours()).padStart(2,'0')}:${String(f.getMinutes()).padStart(2,'0')}`;
}

const COLS_EXPORT = ["N°","Código","Apellidos y Nombres","DNI","Sucursal","Cargo","Tipo","F. Ingreso","Ult. F. Cese","Ult. Tareaje","Lista Negra"];

// ── EXCEL ────────────────────────────────────────────────────────────────────
document.getElementById('btnExportExcelRep').addEventListener('click', async () => {
    const data = tbl.getData('active');
    if (!data.length) return Swal.fire('Sin datos', 'No hay registros para exportar.', 'warning');

    const filtros  = getFiltrosRep();
    const fechaStr = fechaHoraStr();
    const vi = parseInt(document.getElementById('cntVigentes').textContent) || 0;
    const no = parseInt(document.getElementById('cntCesados').textContent)  || 0;
    const total = vi + no;

    Swal.fire({ title: 'Generando Excel...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const workbook  = new ExcelJS.Workbook();
        const worksheet = workbook.addWorksheet('Reporte Personal', { views: [{ showGridLines: false }] });

        // Logo
        const logoB64 = await cargarLogoBase64();
        if (logoB64) {
            const imgId = workbook.addImage({ base64: logoB64, extension: 'png' });
            worksheet.addImage(imgId, { tl: { col: 0, row: 0 }, ext: { width: 170, height: 50 } });
        }

        // Títulos
        const ncols = COLS_EXPORT.length;
        const lastCol = String.fromCharCode(64 + ncols);

        worksheet.mergeCells(`A1:${lastCol}1`);
        const t1 = worksheet.getCell('A1');
        t1.value = 'SISTEMA INTEGRADO SOLMAR – SISOL WEB';
        t1.font  = { bold: true, color: { argb: 'FF990000' }, size: 11 };
        t1.alignment = { horizontal: 'center', vertical: 'middle' };

        worksheet.mergeCells(`A2:${lastCol}2`);
        const t2 = worksheet.getCell('A2');
        t2.value = 'REPORTE DE PERSONAL';
        t2.font  = { bold: true, size: 14 };
        t2.alignment = { horizontal: 'center', vertical: 'middle' };

        worksheet.mergeCells(`A3:${lastCol}3`);
        const t3 = worksheet.getCell('A3');
        t3.value = filtros.sucursal !== 'TODAS' ? `Sol ${filtros.sucursal}` : '';
        t3.font  = { bold: true, size: 12 };
        t3.alignment = { horizontal: 'center', vertical: 'middle' };

        // Stats
        const sh = ['A6','B6','C6','D6','E6'];
        const sv = ['A7','B7','C7','D7','E7'];
        ['Generado','Total','Vigentes','Cesados','Vigencia'].forEach((lbl, i) => {
            const c = worksheet.getCell(sh[i]);
            c.value = lbl;
            c.font  = { bold: true };
            c.fill  = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE5E7EB' } };
            c.alignment = { horizontal: 'center', vertical: 'middle' };
            c.border = { top:{style:'thin'}, left:{style:'thin'}, bottom:{style:'thin'}, right:{style:'thin'} };
        });
        [fechaStr, total, vi, no, filtros.vigencia].forEach((val, i) => {
            const c = worksheet.getCell(sv[i]);
            c.value = val;
            c.font  = { bold: true };
            c.alignment = { horizontal: 'center', vertical: 'middle' };
            c.border = { top:{style:'thin'}, left:{style:'thin'}, bottom:{style:'thin'}, right:{style:'thin'} };
        });

        // Cabecera tabla
        const hRow = worksheet.getRow(10);
        hRow.values = COLS_EXPORT;
        hRow.eachCell(cell => {
            cell.font  = { bold: true, color: { argb: 'FFFFFFFF' } };
            cell.fill  = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF4B5563' } };
            cell.alignment = { horizontal: 'center', vertical: 'middle' };
            cell.border = { top:{style:'thin'}, left:{style:'thin'}, bottom:{style:'thin'}, right:{style:'thin'} };
        });

        // Datos
        data.forEach((d, i) => {
            const row = worksheet.addRow(filaParaExport(d, i));
            row.eachCell((cell, col) => {
                cell.border = { top:{style:'thin'}, left:{style:'thin'}, bottom:{style:'thin'}, right:{style:'thin'} };
                cell.alignment = { vertical: 'middle', horizontal: col === 3 ? 'left' : 'center' };
            });
            // Lista negra en rojo
            if ((d.en_lista_negra ?? 'NO').toString().trim().toUpperCase() === 'SI') {
                row.getCell(2).font = { color: { argb: 'FFB91C1C' }, bold: true };
                row.getCell(3).font = { color: { argb: 'FFB91C1C' }, bold: true };
                row.getCell(11).font = { color: { argb: 'FFB91C1C' }, bold: true };
            }
        });

        worksheet.columns = [
            {width:6},{width:10},{width:42},{width:13},{width:12},
            {width:30},{width:12},{width:13},{width:13},{width:13},{width:12},
        ];

        const buffer = await workbook.xlsx.writeBuffer();
        const blob   = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const link   = document.createElement('a');
        link.href    = URL.createObjectURL(blob);
        link.download = `ReportePersonal_${filtros.sucursal.replace(/ /g,'_')}_${filtros.vigencia}_${timestampArchivo()}.xlsx`;
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
        URL.revokeObjectURL(link.href);
        Swal.close();
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'No se pudo generar el Excel.', 'error');
    }
});

// ── PDF ──────────────────────────────────────────────────────────────────────
document.getElementById('btnExportPdfRep').addEventListener('click', async () => {
    const data = tbl.getData('active');
    if (!data.length) return Swal.fire('Sin datos', 'No hay registros para exportar.', 'warning');

    const filtros  = getFiltrosRep();
    const fechaStr = fechaHoraStr();
    const vi    = parseInt(document.getElementById('cntVigentes').textContent) || 0;
    const no    = parseInt(document.getElementById('cntCesados').textContent)  || 0;
    const total = vi + no;

    Swal.fire({ title: 'Generando PDF...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    try {
        const logoB64 = await cargarLogoBase64();
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('landscape');
        const pageW = doc.internal.pageSize.getWidth();

        doc.autoTable({
            startY: 58,
            theme: 'grid',
            headStyles: { fillColor: [75, 85, 99], textColor: [255,255,255], fontStyle: 'bold', halign: 'center', fontSize: 7 },
            bodyStyles: { fontSize: 7 },
            columnStyles: {
                0:  { halign: 'center', cellWidth: 8  },
                1:  { halign: 'center', cellWidth: 14 },
                2:  { halign: 'left',   cellWidth: 50 },
                3:  { halign: 'center', cellWidth: 18 },
                4:  { halign: 'center', cellWidth: 16 },
                5:  { halign: 'left',   cellWidth: 40 },
                6:  { halign: 'center', cellWidth: 16 },
                7:  { halign: 'center', cellWidth: 20 },
                8:  { halign: 'center', cellWidth: 20 },
                9:  { halign: 'center', cellWidth: 20 },
                10: { halign: 'center', cellWidth: 14 },
            },
            head: [COLS_EXPORT],
            body: data.map((d, i) => filaParaExport(d, i)),
            didParseCell: (hookData) => {
                // Fila en lista negra: texto rojo en código, nombre y LN
                const row = data[hookData.row.index];
                if (row && (row.en_lista_negra ?? 'NO').toString().trim().toUpperCase() === 'SI') {
                    if ([1,2,10].includes(hookData.column.index)) {
                        hookData.cell.styles.textColor = [185, 28, 28];
                        hookData.cell.styles.fontStyle = 'bold';
                    }
                }
            },
            didDrawPage: (pg) => {
                if (pg.pageNumber !== 1) return;

                if (logoB64) doc.addImage(logoB64, 'PNG', 14, 8, 38, 12);

                doc.setFontSize(10); doc.setTextColor(180,0,0); doc.setFont('helvetica','bold');
                doc.text('SISTEMA INTEGRADO SOLMAR – SISOL WEB', pageW / 2, 12, { align: 'center' });

                doc.setFontSize(12); doc.setTextColor(0,0,0);
                doc.text('REPORTE DE PERSONAL', pageW / 2, 18, { align: 'center' });

                if (filtros.sucursal !== 'TODAS') {
                    doc.setFontSize(10);
                    doc.text(`Sol ${filtros.sucursal}`, pageW / 2, 24, { align: 'center' });
                }

                doc.setFontSize(8); doc.setFont('helvetica','normal'); doc.setTextColor(100,100,100);
                doc.text(`Generado: ${fechaStr}`, pageW - 14, 12, { align: 'right' });

                // Cards: Total / Vigentes / Cesados
                const cardW = 40, cardH = 16, gap = 8;
                const startX = (pageW - (cardW * 3 + gap * 2)) / 2;
                const cardY  = 30;
                const cards  = [
                    { title: 'Total',    value: total, color: [75,85,99]   },
                    { title: 'Vigentes', value: vi,    color: [4,120,87]   },
                    { title: 'Cesados',  value: no,    color: [185,28,28]  },
                ];
                cards.forEach((card, i) => {
                    const x = startX + i * (cardW + gap);
                    doc.setFillColor(...card.color);
                    doc.roundedRect(x, cardY, cardW, cardH, 2, 2, 'F');
                    doc.setTextColor(255,255,255); doc.setFont('helvetica','bold');
                    doc.setFontSize(14);
                    doc.text(String(card.value), x + cardW / 2, cardY + 9, { align: 'center' });
                    doc.setFontSize(7);
                    doc.text(card.title.toUpperCase(), x + cardW / 2, cardY + 14, { align: 'center' });
                });
            },
        });

        const ts = timestampArchivo();
        doc.save(`ReportePersonal_${filtros.sucursal.replace(/ /g,'_')}_${filtros.vigencia}_${ts}.pdf`);
        Swal.close();
    } catch (err) {
        console.error(err);
        Swal.fire('Error', 'No se pudo generar el PDF.', 'error');
    }
});
