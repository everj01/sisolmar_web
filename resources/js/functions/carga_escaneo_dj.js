import axios from 'axios';
import Swal from 'sweetalert2';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

let pageSizePersonas = 10;

const archivoDJ    = document.getElementById('archivoDJ');
const zonaDropDJ   = document.getElementById('zonaDropDJ');
const listaArchivosDJ = document.getElementById('listaArchivosDJ');

zonaDropDJ.addEventListener('click', () => archivoDJ.click());

// ============================================================
// INIT
// ============================================================
(function init() {
    const run = () => {
        seleccionarPrimeraSucursalValida();
        setTimeout(() => reloadTabla(), 100);
    };

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        run();
    } else {
        document.addEventListener('DOMContentLoaded', run);
    }
})();

// ============================================================
// TABULATOR — Personal
// ============================================================
const tblPersonas = new Tabulator('#tblPersonas', {
    height: '100%',
    layout: 'fitColumns',
    responsiveLayout: 'collapse',
    ajaxConfig: 'GET',
    pagination: true,
    paginationMode: 'remote',
    paginationSize: pageSizePersonas,
    paginationDataSent: { page: 'page', size: 'size' },
    paginationDataReceived: { data: 'data', last_page: 'last_page', last_row: 'total' },
    paginationElement: document.getElementById('tablaPaginacion'),
    rowHeader: { formatter: 'responsiveCollapse', width: 30, minWidth: 30, hozAlign: 'center', resizable: false, headerSort: false },
    locale: 'es',
    langs: {
        es: { pagination: { first: '«', prev: '‹', next: '›', last: '»' } }
    },

    ajaxResponse: function (url, params, response) {
        this._totalFiltrado = response.total;
        return response;
    },

    ajaxURLGenerator: function (url, config, params) {
        let codSucursal = document.getElementById('sucursal').value;
        if (!codSucursal || codSucursal === '— Seleccionar —' || codSucursal === '00') codSucursal = '0';

        params.search      = document.getElementById('buscarPersonal').value.trim();
        params.codSucursal = codSucursal;
        params.tipo_per    = document.getElementById('tipo_per')?.value || 'TODOS';
        params.vigencia    = document.querySelector('input[name="vigencia"]:checked')?.value || '';

        const filtroDJ = document.getElementById('filtroDJ')?.value || 'TODOS';
        if (filtroDJ === 'SI') params.tiene_folio_25 = '1';
        else if (filtroDJ === 'NO') params.tiene_folio_25 = '0';

        return `${url}?${new URLSearchParams(params).toString()}`;
    },

    rowFormatter: function (row) {
        if (row.getData().PERS_VIGENCIA !== 'SI') {
            row.getElement().style.backgroundColor = '#ffe5e5';
            row.getElement().style.color = '#7a1f1f';
        }
    },

    columns: [
        { title: 'Cód.',     field: 'CODI_PERS', hozAlign: 'center', minWidth: 60,  widthGrow: 0.5, responsive: false },
        { title: 'Apellidos', field: 'apellidos', hozAlign: 'left',   minWidth: 120, widthGrow: 2,   responsive: false },
        { title: 'Nombres',  field: 'nombres',   hozAlign: 'left',   minWidth: 120, widthGrow: 2,   responsive: false },
        { title: 'Nro Doc.', field: 'nroDoc',    hozAlign: 'center', minWidth: 90,  widthGrow: 0.8, responsive: false },
        { title: 'Sucursal', field: 'sucursal',  hozAlign: 'center', minWidth: 80,  widthGrow: 0.8, responsive: 0 },
        {
            title: 'Tipo', field: 'TIPOTRAB2', hozAlign: 'center', minWidth: 120, widthGrow: 1.2, responsive: false,
            formatter: function (cell) {
                const val = cell.getValue() || '';
                return val.replace('OPER', 'OPERATIVO').replace('ADMIN', 'ADMINISTRATIVO');
            }
        },
        {
            title: 'Escaneo DJ', field: 'tiene_folio_25', hozAlign: 'center', minWidth: 90, widthGrow: 0.6, responsive: false, headerSort: true,
            formatter: function (cell) {
                return cell.getValue() == 1
                    ? `<i class="fa fa-check-circle text-green-600 cursor-pointer" style="font-size:1.2rem;" title="DJ subida — clic para reemplazar"></i>`
                    : `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700 cursor-pointer hover:bg-yellow-200 transition-colors" title="Clic para subir DJ">
                            <i class="fa fa-clock-o"></i> Pendiente
                       </span>`;
            },
            cellClick: function (e, cell) {
                const data = cell.getRow().getData();
                abrirModalSubirDJ(data.CODI_PERS, data.personal);
            }
        },
        {
            title: 'Acciones', field: 'acciones', minWidth: 140, widthGrow: 0,
            hozAlign: 'center', headerSort: false, responsive: false,
            formatter: function (cell) {
                const tieneDJ = cell.getRow().getData().tiene_folio_25 == 1;
                let html = `<button type="button" class="btn rounded-full subir-dj-btn bg-primary/20 text-primary hover:bg-primary hover:text-white text-xs px-3 py-1">
                                <i class="fa fa-cloud-upload subir-dj-btn"></i> Subir DJ
                            </button>`;
                if (tieneDJ) {
                    html += ` <button type="button" class="btn rounded-full ver-dj-btn bg-info/20 text-info hover:bg-info hover:text-white text-xs px-2 py-1" title="Ver DJ">
                                  <i class="fa fa-eye ver-dj-btn"></i>
                              </button>`;
                }
                return html;
            },
            cellClick: function (e, cell) {
                const data   = cell.getRow().getData();
                const codigo = data.CODI_PERS;
                const nombre = data.personal;

                if (e.target.classList.contains('subir-dj-btn')) {
                    abrirModalSubirDJ(codigo, nombre);
                }

                if (e.target.classList.contains('ver-dj-btn')) {
                    window.open(`${VITE_URL_APP}/ver-dj/${codigo}`, '_blank');
                }
            }
        },
    ],
});

tblPersonas.on('dataLoaded', function () {
    const total = this._totalFiltrado || 0;
    const page  = this.getPage();
    const size  = this.getPageSize();
    const start = (page - 1) * size + 1;
    const end   = Math.min(page * size, total);
    document.getElementById('tablaInfo').innerText = `${start}-${end} de ${total} registros`;
});

window.addEventListener('sidebar-toggled', () => tblPersonas?.redraw(true));

// ============================================================
// MODAL — Subir DJ
// ============================================================
function abrirModalSubirDJ(codigo, nombre) {
    document.getElementById('codPersonalDJ').value = codigo;
    document.querySelector('.nombre-personal').textContent = nombre ?? '';
    limpiarModal();
    document.getElementById('btn-modal-dj').click();
}

function limpiarModal() {
    document.getElementById('fecha_emision_dj').value = '';
    archivoDJ.value = '';
    listaArchivosDJ.innerHTML = '';
}

// Validación al seleccionar archivo
archivoDJ.addEventListener('change', function () {
    const archivos = Array.from(this.files);
    if (!archivos.length) return;

    const maxSize = 1.2 * 1024 * 1024;

    for (const archivo of archivos) {
        if (archivo.type !== 'application/pdf') {
            Swal.fire({ title: 'Solo se permite PDF para el DJ', icon: 'warning' });
            this.value = ''; listaArchivosDJ.innerHTML = ''; return;
        }
        if (archivo.size > maxSize) {
            Swal.fire({
                title: 'Archivo demasiado grande',
                text: `"${archivo.name}" pesa ${(archivo.size / 1024 / 1024).toFixed(2)} MB. Límite: 1 MB.`,
                icon: 'warning'
            });
            this.value = ''; listaArchivosDJ.innerHTML = ''; return;
        }
    }

    listaArchivosDJ.innerHTML = archivos.map(a => `
        <li class="flex items-center gap-2 text-sm text-gray-700">
            <i class="fa fa-file-pdf-o text-red-500"></i>
            <span>${a.name}</span>
            <span class="text-gray-400">(${(a.size / 1024).toFixed(1)} KB)</span>
        </li>
    `).join('');
});

// Submit
document.getElementById('formSubirDJ').addEventListener('submit', function (e) {
    e.preventDefault();

    const fechaEmision = document.getElementById('fecha_emision_dj').value;
    const codPersonal  = document.getElementById('codPersonalDJ').value;
    const archivo      = archivoDJ?.files?.[0];
    const maxSize      = 1.2 * 1024 * 1024;

    if (!fechaEmision) { Swal.fire({ title: 'Ingrese la fecha de emisión', icon: 'warning' }); return; }
    if (!archivo)      { Swal.fire({ title: 'Seleccione un archivo PDF', icon: 'warning' });   return; }
    if (archivo.type !== 'application/pdf') { Swal.fire({ title: 'Solo se permite PDF', icon: 'warning' }); return; }
    if (archivo.size > maxSize) { Swal.fire({ title: 'El archivo supera 1 MB', icon: 'warning' }); return; }

    const btnGuardar = document.getElementById('btn-guardar-dj');
    btnGuardar.disabled = true;
    btnGuardar.textContent = 'Guardando...';

    const formData = new FormData();
    formData.append('fecha_emision', fechaEmision);
    formData.append('codPersonal',   codPersonal);
    formData.append('pdf',           archivo);

    axios.post(`${VITE_URL_APP}/save-dj-folio-2`, formData, { headers: { 'Accept': 'application/json' } })
        .then(() => {
            document.getElementById('btn-modal-dj-close').click();
            limpiarModal();
            reloadTabla();
            Swal.fire({ title: 'DJ subida correctamente', icon: 'success', timer: 2000, showConfirmButton: false });
        })
        .catch(error => {
            const msg = error.response?.data?.error || error.response?.data?.message || 'Error al guardar el DJ';
            Swal.fire({ title: msg, icon: 'error' });
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = '<i class="i-tabler-upload me-1"></i> Subir DJ';
        });
});

// ============================================================
// LISTENERS — Filtros
// ============================================================
document.getElementById('page-size-personas').addEventListener('change', function () {
    pageSizePersonas = parseInt(this.value);
    tblPersonas.setPageSize(pageSizePersonas);
    reloadTabla();
});

document.getElementById('buscarPersonal').addEventListener('keyup', () => reloadTabla());
document.getElementById('sucursal').addEventListener('change', () => reloadTabla());
document.getElementById('tipo_per')?.addEventListener('change', () => reloadTabla());
document.getElementById('filtroDJ').addEventListener('change', () => reloadTabla());
document.querySelectorAll('input[name="vigencia"]').forEach(r => r.addEventListener('change', () => reloadTabla()));

// ============================================================
// FUNCIONES
// ============================================================
function reloadTabla() {
    tblPersonas.setData(`${VITE_URL_APP}/get-personal-total`, { page: 1, size: pageSizePersonas });
}

function seleccionarPrimeraSucursalValida() {
    const select = document.getElementById('sucursal');
    if (!select) return;
    const opciones = [...select.options].filter(opt =>
        opt.value && opt.value !== '— Seleccionar —' && !opt.disabled
    );
    if (opciones.length > 0) select.value = opciones[0].value;
}
