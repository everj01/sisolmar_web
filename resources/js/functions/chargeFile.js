
import axios from 'axios';
import Swal from 'sweetalert2';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

let usuarioActual = null;

// Al inicio del archivo, reemplaza getPersonal() por esto:
// (async () => {
//     usuarioActual = await getUsuario();
//     getPersonal();

//     seleccionarPrimeraSucursalValida();
//     setTimeout(() => reloadTabla(), 100); // ← dale tiempo a Tabulator
// })();

(async () => {
    usuarioActual = await getUsuario();
    getPersonal();
    
    document.addEventListener('DOMContentLoaded', () => {
        seleccionarPrimeraSucursalValida();
        setTimeout(() => reloadTabla(), 100);
    });

    // Por si DOMContentLoaded ya disparó (scripts al final del body)
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        seleccionarPrimeraSucursalValida();
        setTimeout(() => reloadTabla(), 100);
    }
})();
let pageSizePersonas = 10;



document.getElementById("page-size-personas")
    .addEventListener("change", function () {

        pageSizePersonas = parseInt(this.value);
        tblPersonas.setPageSize(pageSizePersonas);

        reloadTabla();
    });

const tblPersonas = new Tabulator("#tblPersonas", {
    height: "100%",
    layout: "fitDataFill",
    responsiveLayout: "collapse",

    // ajaxURL: `${VITE_URL_APP}/api/get-personal-total`,
    // ajaxParams: { page: 1, size: pageSizePersonas },
    ajaxConfig: "GET",

    pagination: true,
    paginationMode: "remote",
    paginationSize: pageSizePersonas,

    paginationDataSent: {
        page: "page",
        size: "size",
    },

    rowHeader: { formatter: "responsiveCollapse", width: 30, minWidth: 30, hozAlign: "center", resizable: false, headerSort: false },
    paginationElement: document.getElementById("tablaPaginacion"),
    locale: "es",
    langs: {
        es: { pagination: { first: "«", prev: "‹", next: "›", last: "»" } }
    },
    columns: [
        { title: "Cód.", field: "CODI_PERS", hozAlign: "center", width: '10%', responsive: false },
        { title: "Personal", field: "personal", hozAlign: "left", width: '30%', responsive: false },
        { title: "Nro Doc.", field: "nroDoc", hozAlign: "center", width: '15%', responsive: false },
        { title: "Sucursal", field: "sucursal", hozAlign: "center", width: '18%', responsive: 0 },
        {
            title: "Acciones", field: "acciones", width: 220, hozAlign: "center", headerSort: false, responsive: false,
            formatter: function (cell) {
                var docsBtn = `<button type="button" class="btn rounded-full docs-btn bg-success/25 text-success hover:bg-success hover:text-white">Folios</button>`;

                // Solo mostrar Legajos si el rol NO es 8
                var legajoBtn = '';
                var bioBtn = ``;

                if (usuarioActual?.tipo_rol != 8) {
                    bioBtn = `<button type="button" class="btn rounded-full bio-btn bg-info/25 text-info hover:bg-info hover:text-white"><i class="fa fa-fingerprint bio-btn"></i></button>`
                    legajoBtn = `<button type="button" class="btn rounded-full legajo-btn bg-warning/25 text-warning hover:bg-warning hover:text-white">Legajos</button>`;
                }

                return docsBtn + ' ' + legajoBtn + ' ' + bioBtn;
            },
            cellClick: function (e, cell) {
                const registro = cell.getRow().getData();
                const codigo = registro.CODI_PERS;
                const persona = registro.personal;
                document.getElementById('codPersonal').value = codigo;

                getDocsObligatorios(codigo);

                if (e.target.classList.contains('docs-btn')) {
                    document.getElementById('dataDocs').classList.remove('hidden');
                    document.getElementById('dataDocsLeg').classList.add('hidden');
                    document.getElementById('divCoincidencias').classList.add('hidden');
                } else if (e.target.classList.contains('bio-btn')) {
                    verBiometrico(codigo, persona); // ← directo, sin Swal de selección
                } else if (e.target.classList.contains('legajo-btn')) {
                    document.getElementById('dataDocsLeg').classList.remove('hidden');
                    document.getElementById('dataDocs').classList.add('hidden');
                }

                updateCardTitle(persona);
            }
        },
    ],

    // <-- Aquí es donde guardamos el total remoto
    ajaxResponse: function (url, params, response) {
        this._totalFiltrado = response.total;   // total filtrado
        this._totalOriginal = response.total;   // total original si quieres
        return response; // devuelves todo el objeto, Tabulator usará "data" automáticamente
    },

    paginationDataReceived: {
        data: "data",        // <- Tabulator tomará los datos de aquí
        last_page: "last_page",
        last_row: "total"    // <- total remoto
    },

    rowFormatter: function(row) {
        const data = row.getData();
        const el = row.getElement();

        console.log('debug data perosnal, ', data.PERS_VIGENCIA);

        // ajusta aquí el nombre real del campo que te manda el backend
        const vigente = data.PERS_VIGENCIA;

        if (vigente != 'SI') {
            el.style.backgroundColor = "#ffe5e5"; // rojo tenue
            el.style.color = "#7a1f1f";
        }
    },

     ajaxURLGenerator: function(url, config, params) {
        // Leer filtros activos en el momento exacto de cada petición
        let search = document.getElementById("buscarPersonal").value.trim();
        let codSucursalSelect = document.getElementById("sucursal");
        let codSucursal = codSucursalSelect.value;

        if (!codSucursal || codSucursal === "-Seleccionar-" || codSucursal === "00") {
            codSucursal = "0";
        }

        let tipo_per = document.querySelector('input[name="tipo_per"]:checked')?.value || "TODOS";
        let vigencia = document.querySelector('input[name="vigencia"]:checked')?.value || "";

        // Inyectar en los params que Tabulator ya construyó (page, size, etc.)
        params.search     = search;
        params.codSucursal = codSucursal;
        params.tipo_per   = tipo_per;
        params.vigencia   = vigencia;

        // Construir query string manualmente
        const query = new URLSearchParams(params).toString();
        return `${url}?${query}`;
    },

});

tblPersonas.on("dataLoaded", function () {
    const page = this.getPage();
    const size = this.getPageSize();

    const total = this._totalFiltrado || 0; // <- usamos la variable que guardamos en ajaxResponse

    const start = (page - 1) * size + 1;
    const end = Math.min(page * size, total);

    document.getElementById("tablaInfo").innerText =
        `${start}-${end} de ${total} registros`;
});


// function seleccionarPrimeraSucursalValida() {
//     const select = document.getElementById("sucursal");
//     if (!select) return;

//     const primeraOpcionValida = [...select.options].find(opt =>
//         opt.value &&
//        // opt.value !== "00" &&
//         opt.value !== "-Seleccionar-" &&
//         !opt.disabled
//     );

//     if (primeraOpcionValida) {
//         select.value = primeraOpcionValida.value;
//     }
// }
function renderImagen(img, esDni = false, reverso = null) {
    if (!img || typeof img !== 'string' || !img.startsWith('data:')) {
        return `
            <div style="
                width:100%;
                ${esDni ? 'height:280px;' : 'height:130px;'}
                display:flex; flex-direction:column;
                align-items:center; justify-content:center;
                background:#f8fafc; border:1.5px dashed #e2e8f0;
                border-radius:12px; color:#94a3b8; gap:8px;">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <rect x="3" y="3" width="18" height="18" rx="3"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path d="m21 15-5-5L5 21"/>
                </svg>
                <span style="font-size:12px; font-weight:500;">Sin imagen</span>
            </div>`;
    }

    const id = 'img_' + Math.random().toString(36).substr(2, 9);
    let mostrandoReverso = false;

    // Botón toggle anverso/reverso solo para DNI
    const toggleBtn = esDni ? `
        <button onclick="toggleDni_${id}()" id="toggleBtn_${id}" style="
            width:100%; font-size:12px; padding:7px 0;
            background:#f1f5f9; border:none;
            border-top:1px solid #e2e8f0;
            border-radius:0 0 12px 12px;
            cursor:pointer; color:#475569;
            font-weight:500; letter-spacing:0.3px;
            transition:background .15s;">
            <svg style="display:inline;vertical-align:-2px;margin-right:4px;" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
            </svg>
            Ver reverso
        </button>` : '';

    setTimeout(() => {
        if (esDni) {
            window[`toggleDni_${id}`] = function () {
                const imgEl = document.getElementById(id);
                const btnEl = document.getElementById('toggleBtn_' + id);
                mostrandoReverso = !mostrandoReverso;
                imgEl.src = mostrandoReverso ? (reverso || img) : img;
                btnEl.innerHTML = mostrandoReverso
                    ? `<svg style="display:inline;vertical-align:-2px;margin-right:4px;" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg> Ver anverso`
                    : `<svg style="display:inline;vertical-align:-2px;margin-right:4px;" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg> Ver reverso`;
                const badge = document.getElementById('badge_' + id);
                if (badge) badge.textContent = mostrandoReverso ? 'REVERSO' : 'ANVERSO';
            };
        }
        const btn = document.getElementById('toggleBtn_' + id);
        if (btn) {
            btn.onmouseover = () => btn.style.background = '#e2e8f0';
            btn.onmouseout  = () => btn.style.background = '#f1f5f9';
        }
    }, 0);

    // ── Botón acción: LUPA para huella/firma, LIGHTBOX para DNI ──
    const btnAccion = esDni
        ? `<button onclick="abrirLightbox('${id}')" style="
                position:absolute; bottom:8px; right:8px;
                background:rgba(99,102,241,0.9); color:white;
                border:none; border-radius:8px;
                padding:5px 10px; font-size:11px; font-weight:500;
                cursor:pointer; z-index:11;
                display:flex; align-items:center; gap:5px;
                box-shadow:0 2px 8px rgba(99,102,241,0.4);
                transition:background .15s;"
                onmouseover="this.style.background='#4f46e5'"
                onmouseout="this.style.background='rgba(99,102,241,0.9)'">
                <svg width="12" height="12" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M15 3h6m0 0v6m0-6-7 7M9 21H3m0 0v-6m0 6 7-7"/>
                </svg>
                Ver
           </button>`
        : `<button onclick="toggleLupa('${id}')" id="lupaBtn_${id}" style="
                position:absolute; bottom:8px; right:8px;
                background:rgba(99,102,241,0.9); color:white;
                border:none; border-radius:50%;
                width:30px; height:30px;
                cursor:pointer; z-index:11;
                display:flex; align-items:center; justify-content:center;
                box-shadow:0 2px 8px rgba(99,102,241,0.4);
                transition:transform .15s, background .15s;"
                onmouseover="this.style.transform='scale(1.1)';this.style.background='#4f46e5'"
                onmouseout="this.style.transform='scale(1)';this.style.background='rgba(99,102,241,0.9)'"
                title="Activar lupa">
                <svg width="13" height="13" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
                </svg>
           </button>`;

    // Div lupa solo para huella/firma
    const lupaDiv = !esDni ? `
        <div id="lupa_${id}" style="
            display:none; position:absolute;
            width:130px; height:130px;
            border-radius:50%;
            border:2.5px solid #6366f1;
            box-shadow:0 0 0 3px rgba(99,102,241,0.15);
            pointer-events:none;
            background-repeat:no-repeat;
            z-index:10;"></div>` : '';

    return `
        <div style="border-radius:12px; border:1px solid #e2e8f0; overflow:hidden; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,0.06); width:100%;">
            <div id="cont_${id}" style="
                position:relative; width:100%;
                ${esDni ? 'height:280px;' : 'height:130px;'}
                background:#f8fafc; overflow:hidden;
                display:flex; align-items:center; justify-content:center;
                ${!esDni ? 'cursor:crosshair;' : ''}">

                <img id="${id}" src="${img}"
                     style="max-width:100%; max-height:100%; width:auto; height:auto;
                            object-fit:contain; display:block;
                            cursor:${esDni ? 'zoom-in' : 'crosshair'};"
                     ${esDni ? `onclick="abrirLightbox('${id}')"` : ''}
                     onerror="this.parentElement.innerHTML='<div style=\'width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px;flex-direction:column;gap:6px;\'><svg width=32 height=32 fill=none stroke=currentColor stroke-width=1.5 viewBox=\'0 0 24 24\'><rect x=3 y=3 width=18 height=18 rx=3/><circle cx=8.5 cy=8.5 r=1.5/><path d=\'m21 15-5-5L5 21\'/></svg>Sin imagen</div>'" />

                ${lupaDiv}

                ${esDni ? `<span id="badge_${id}" style="
                    position:absolute; top:8px; left:8px;
                    background:rgba(99,102,241,0.9); color:#fff;
                    font-size:10px; font-weight:600;
                    padding:3px 8px; border-radius:20px;
                    letter-spacing:0.5px; z-index:5;">ANVERSO</span>` : ''}

                ${btnAccion}
            </div>
            ${toggleBtn}
        </div>`;
}

window.bioSwitchTab = function(tab) {
    const panelFH  = document.getElementById('bio-panel-fh');
    const panelDoc = document.getElementById('bio-panel-doc');
    const btnFH    = document.getElementById('bio-tab-fh');
    const btnDoc   = document.getElementById('bio-tab-doc');

    if (tab === 'fh') {
        panelFH.style.display  = 'flex';
        panelDoc.style.display = 'none';
        btnFH.classList.add('border-indigo-500', 'text-indigo-600');
        btnFH.classList.remove('border-transparent', 'text-gray-500');
        btnDoc.classList.add('border-transparent', 'text-gray-500');
        btnDoc.classList.remove('border-indigo-500', 'text-indigo-600');
    } else {
        panelFH.style.display  = 'none';
        panelDoc.style.display = 'block';
        btnDoc.classList.add('border-indigo-500', 'text-indigo-600');
        btnDoc.classList.remove('border-transparent', 'text-gray-500');
        btnFH.classList.add('border-transparent', 'text-gray-500');
        btnFH.classList.remove('border-indigo-500', 'text-indigo-600');
    }
};


function verBiometrico(codigo, persona) {
    Swal.fire({ title: 'Cargando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    axios.get(`${VITE_URL_APP}/api/get-biometrico/${codigo}`)
        .then(response => {
            Swal.close();
            const data = response.data;

            // DEBUG: ver qué IDs existen realmente
            const ids = [
                'modal-bio-title',
                'bio-huella-antigua', 'bio-huella-nueva',
                'bio-firma-antigua',  'bio-firma-nueva',
                'bio-doc-dni-antiguo',
                'bio-doc-firma-nueva', 'bio-doc-huella-nueva'
            ];
            ids.forEach(id => {
                console.log(id, '→', document.getElementById(id) ? 'OK' : 'NO EXISTE');
            });

            document.getElementById('modal-bio-title').textContent = persona;

            // — Pestaña Firmas y Huellas —
            document.getElementById('bio-huella-antigua').innerHTML = renderImagen(data.huella_antigua, false);
            document.getElementById('bio-huella-nueva').innerHTML   = renderImagen(data.huella_nueva, false);
            document.getElementById('bio-firma-antigua').innerHTML  = renderImagen(data.firma_antigua, false);
            document.getElementById('bio-firma-nueva').innerHTML    = renderImagen(data.firma_nueva, false);

            // — Pestaña DOC —
            document.getElementById('bio-doc-dni-antiguo').innerHTML  = renderImagen(data.dni_anverso_antigua, true, data.dni_reverso_antigua);
            document.getElementById('bio-doc-firma-nueva').innerHTML  = renderImagen(data.firma_nueva, false);
            document.getElementById('bio-doc-huella-nueva').innerHTML = renderImagen(data.huella_nueva, false);

            // Resetear siempre a la primera pestaña al abrir
            bioSwitchTab('fh');

            //document.getElementById('btn-modal-biometrico').click();
            window.HSOverlay?.open(document.getElementById('modal-biometrico'));
        })
        .catch(() => {
            Swal.fire({ title: 'Error al obtener biométrico', icon: 'error' });
        });
}

function seleccionarPrimeraSucursalValida() {
    const select = document.getElementById("sucursal");
    if (!select) return;

    const opciones = [...select.options].filter(opt =>
        opt.value &&
        opt.value !== "-Seleccionar-" &&
        opt.value !== "— Seleccionar —" &&
        !opt.disabled
    );

    // Si solo hay una opción válida, selecciónala automáticamente
    if (opciones.length === 1) {
        select.value = opciones[0].value;
    } else if (opciones.length > 1) {
        // Si hay varias, selecciona la primera igualmente
        select.value = opciones[0].value;
    }
}

// function reloadTabla() {

//     //let search = document.getElementById("buscarPersonal").value.trim();
//     let codSucursalSelect = document.getElementById("sucursal");
//     let codSucursal = codSucursalSelect.value;

//     if (!codSucursal || codSucursal == "-Seleccionar-" || codSucursal == "00") {
//         codSucursal = "0"; // fallback cuando no selecciona nada
//     }
//     let tipo_per = document.querySelector('input[name="tipo_per"]:checked')?.value || "TODOS";
//     let vigencia = document.querySelector('input[name="vigencia"]:checked')?.value || "";

//     tblPersonas.setData(`${VITE_URL_APP}/get-personal-total`, {
//         page: 1,
//         size: pageSizePersonas,
//         search: search,
//         codSucursal: codSucursal,
//         tipo_per: tipo_per,
//         vigencia: vigencia
//     });

//     // Guardar el valor para usarlo tras cambios de página
//     //tblPersonas._ultimoFiltro = search;

//     let search = document.getElementById("buscarPersonal").value.trim();
//     tblPersonas._ultimoFiltro = search;
//     setTimeout(() => resaltarTexto(search), 10);

//     //setTimeout(() => resaltarTexto(search), 10);
// }


function reloadTabla() {
    const search = document.getElementById("buscarPersonal").value.trim();

    tblPersonas.setData(`${VITE_URL_APP}/get-personal-total`, {
        page: 1,
        size: pageSizePersonas,
    });

    tblPersonas._ultimoFiltro = search;
    setTimeout(() => resaltarTexto(search), 10);
}

/*
formatter: function(cell){
                return `<button class="btn docs-btn bg-success">Folios</button> 
                        <button class="btn legajo-btn bg-warning">Legajos</button>`;
            }
*/


//tblPersonas.setData(dataFromApi);

/*
//Tabla de Personas
const tblPersonas2 = new Tabulator("#tblPersonas", {
    height: "100%",
    layout:"fitData",
    responsiveLayout:"collapse",
    ajaxURL: `${VITE_URL_APP}/api/get-personal-total`,
    ajaxParams: { page: 1, size: 50 },
    ajaxConfig: "GET",
    pagination: "remote",
    paginationSize: 50,
    paginationDataSent: {
        "page": "page",
        "size": "size"
    },
    paginationDataReceived: {
        "data": "data",
        "last_page": "last_page",
        "total": "total"
    },
    rowHeader:{formatter:"responsiveCollapse", width:30, minWidth:30, hozAlign:"center", resizable:false, headerSort:false},
    locale: "es",
    columns:[
        {title:"Cód.", field:"CODI_PERS", hozAlign:"center", width: '10%'},
        {title:"Personal", field:"personal", hozAlign:"left", width: '30%'},
        {title:"Nro Doc.", field:"nroDoc", hozAlign:"center", width: '15%'},
        {title:"Sucursal", field:"sucursal", hozAlign:"center", width: '20%'},
        {title: "Acciones", field: "acciones", width: '25%', hozAlign: "center", headerSort: false,
            formatter: function(cell) {
                var docsBtn = `<button type="button" class="btn rounded-full docs-btn bg-success/25 text-success hover:bg-success hover:text-white" >Folios</button>`;
                var legajoBtn = `<button type="button" class="btn rounded-full legajo-btn bg-warning/25 text-warning hover:bg-warning hover:text-white">Legajos</button>`;
                return docsBtn + ' ' + legajoBtn;
            },
            cellClick: function(e, cell) {
                var registro = cell.getRow().getData();
                var codigo = registro.CODI_PERS;
                var persona = registro.personal;
                document.getElementById('codPersonal').value = codigo;

                getDocsObligatorios(codigo);

                if (e.target.classList.contains('docs-btn')) {
                    document.getElementById('dataDocs').classList.remove('hidden');
                    document.getElementById('dataDocsLeg').classList.add('hidden');
                    document.getElementById('divCoincidencias').classList.add('hidden');
                }else{
                    document.getElementById('dataDocsLeg').classList.remove('hidden');
                    document.getElementById('dataDocs').classList.add('hidden');
                }

                updateCardTitle(persona);
            }
        },
    ],
});
*/

//Tabla de Folios
const tblDocs = new Tabulator("#tblDocs", {
    height: "100%",
    layout: "fitDataFill",
    responsiveLayout: "collapse",
    responsiveLayoutCollapseUseRowFormatter: true,
    pagination: true,
    paginationSize: 10,
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
                "all": "Todo"
            },
            "headerFilters": {
                "default": "Filtrar...",
            },
            "ajax": {
                "loading": "Cargando datos...",
                "error": "Error al cargar datos"
            },
            "data": {
                "empty": "No hay datos disponibles"
            }
        }
    },
    columns: [
        { title: "Folio", field: "documento", hozAlign: "left", width: '40%' },
        {
            title: "Emision", field: "fecha_emision", hozAlign: "center", width: '20%',
            formatter: function (cell, formatterParams) {
                var emision = cell.getValue();
                if (emision === null) {
                    return '<span class="rounded-full bg-warning/25">&nbsp;&nbsp;PENDIENTE&nbsp;&nbsp;</span>';
                } else {
                    return emision;
                }
            }
        },
        {
            title: "Caducidad", field: "fecha_caducidad", hozAlign: "center", width: '20%',
            formatter: function (cell) {
                const data = cell.getRow().getData();
                const fechaCaducidad = cell.getValue();
                const vigente = parseInt(data.vigente);

                if (!fechaCaducidad) {
                    // Sin fecha → PENDIENTE
                    return '<span class="rounded-full bg-warning/25">&nbsp;&nbsp;PENDIENTE&nbsp;&nbsp;</span>';
                }

                // Con fecha → color según vigencia
                if (vigente === 1) {
                    return `<span class="text-vigente-800 font-bold">${fechaCaducidad}</span>`;
                } else {
                    return `<span class="text-vencido-800 font-bold">${fechaCaducidad}</span>`;
                }
            }
        },

        /*        
        { title: "Caducidad", field: "fecha_caducidad", hozAlign: "center", width: '20%',
            formatter: function(cell, formatterParams) {
                //var vigente = Number(cell.getRow().getData().vigente);
                let vigente = parseInt(data.vigente);
                var fechaCaducidad = cell.getValue();
                if (vigente == 1) {
                    return `<span class="text-vigente-800 font-bold">${fechaCaducidad == null ? '--' : fechaCaducidad}</span>`
                } else if (vigente == 0) {
                    return `<span class="text-vencido-800 font-bold">${fechaCaducidad == null ? '--' : fechaCaducidad}</span>`
                } else {
                    return '<span class="rounded-full bg-warning/25">&nbsp;&nbsp;PENDIENTE&nbsp;&nbsp;</span>';
                }
            }
        },
        */
        {
            title: "Acciones", field: "acciones", hozAlign: "center", width: '20%', headerSort: false,
            formatter: function (cell, formatterParams, onRendered) {
                // var filePath = cell.getRow().getData().ruta_archivo;
                // var url = filePath;
                // if(filePath){
                //     var viewBtn = `<a href="${url}" target="_blank" class="btn rounded-full view-btn bg-info/25 text-info hover:bg-info hover:text-white"><i class="fa fa-eye view-btn"></i></a>`;
                // }else{
                //     var viewBtn = `<a href="${url}" target="_blank" class="pointer-events-none btn rounded-full view-btn bg-warning/25 text-warning-opa bg-gray-200 hover:bg-gray-200"><i class="fa fa-eye"></i></a>`;
                // }

                let chargeBtn = `<button type="button"
                class="btn rounded-full charge-btn bg-success/25 text-success hover:bg-success hover:text-white">
                    <i class="fa fa-cloud-upload charge-btn"></i>
                </button>`;

                let viewDocBtn = `<button type="button"
                class="btn rounded-full viewdoc-btn bg-warning/25 text-warning hover:bg-warning hover:text-white">
                    <i class="fa fa-eye viewdoc-btn"></i>
                </button>`;

                return chargeBtn + ' ' + viewDocBtn;
            },
            cellClick: function (e, cell) {
                if (e.target.classList.contains('charge-btn')) {
                    const documento = cell.getRow().getData().documento;
                    document.querySelector('#modal-file h3.modal-title').textContent = `Documento: ${documento}`;
                    limpiarModalDj();
                    document.getElementById('btn-modal-docs').click();
                }

                if (e.target.classList.contains('viewdoc-btn')) {
                     const dataTbl = cell.getRow().getData();
    const codPersonal = dataTbl.codPersonal;

    window.open(`${VITE_URL_APP}/ver-dj/${codPersonal}`, '_blank');
                    // const dataTbl = cell.getRow().getData();
                    // const codFolio = dataTbl.codFolio;
                    // const codPersonal = dataTbl.codPersonal;
                    // const nombre = dataTbl.personal;
                    // const documento = dataTbl.documento;

                    // axios.get(`${VITE_URL_APP}/api/get-view-documents/${codPersonal}/${codFolio}`)
                    //     .then(response => {
                    //         console.log(response);

                    //         if (response.data.success !== true) {
                    //             Swal.fire({
                    //                 title: "No se encontro documentos válidos",
                    //                 icon: "info"
                    //             });
                    //             return;
                    //         }

                    //         document.querySelector('#modal-view-docs .modal-title').textContent = `${nombre}`;
                    //         document.querySelector('#modal-view-docs #txtDocSelec').textContent = `${documento}`;

                    //         const rutas = response.data.rutas;
                    //         const visor = document.getElementById('visorDocs');
                    //         visor.innerHTML = '';

                    //         // ✅ Si codFolio es 25, previsualizar como PDF
                    //         if (parseInt(codFolio) === 25) {
                    //             rutas.forEach(ruta => {
                    //                 visor.insertAdjacentHTML('beforeend', `
                    //                     <iframe 
                    //                         src="${ruta}" 
                    //                         class="w-full mb-3 rounded-md" 
                    //                         style="height: 600px; border: none;"
                    //                     ></iframe>
                    //                 `);
                    //             });
                    //         } else {
                    //             // Para los demás folios → imágenes como antes
                    //             rutas.forEach(ruta => {
                    //                 visor.insertAdjacentHTML('beforeend', `
                    //                     <img src="http://${ruta}" class="w-full max-w-[700px] mb-3 rounded-md" />
                    //                 `);
                    //             });
                    //         }

                    //         document.getElementById('btn-modal-view-docs').click();
                    //     })
                    //     .catch(error => {
                    //         console.error("Error al obtener los datos:", error);
                    //         Swal.fire({
                    //             title: "Problema al encontrar documentos",
                    //             icon: "error"
                    //         });
                    //     });
                }
            },
            rowFormatter: function (row) {
                row.getElement().classList.add("hover:bg-indigo-500");  // Cambia "indigo-500" al color que desees
            }
        },
    ],
});



//Tabla de Documentos LEGAJOS
const tblLegajos = new Tabulator("#tblDocsLegajo", {
    height: "100%",
    layout: "fitDataFill",
    responsiveLayout: "collapse",
    columns: [
        { title: "Folio", field: "documento", hozAlign: "left", width: '40%' },
        {
            title: "Emision", field: "fecha_emision", hozAlign: "center", width: '20%',
            formatter: function (cell, formatterParams) {
                var emision = cell.getValue();
                if (emision === null) {
                    return '<span class="rounded-full bg-warning/25">&nbsp;&nbsp;PENDIENTE&nbsp;&nbsp;</span>';
                } else {
                    return emision;
                }
            }
        },
        {
            title: "Caducidad", field: "fecha_caducidad", hozAlign: "center", width: '20%',
            formatter: function (cell, formatterParams) {
                var vigente = cell.getRow().getData().vigente;
                var fechaCaducidad = cell.getValue();
                if (vigente == 1) {
                    return `<span class="text-vigente-800 font-bold">${fechaCaducidad == null ? '--' : fechaCaducidad}</span>`
                } else if (vigente == 0) {
                    return `<span class="text-vencido-800 font-bold">${fechaCaducidad == null ? '--' : fechaCaducidad}</span>`
                } else {
                    return '<span class="rounded-full bg-warning/25">&nbsp;&nbsp;PENDIENTE&nbsp;&nbsp;</span>';
                }
            }
        },
        {
            title: "Acciones", field: "accionesy", hozAlign: "center", width: '20%', headerSort: false,
            formatter: function (cell, formatterParams, onRendered) {
                var filePath = cell.getRow().getData().ruta_archivo;
                var url = '/storage/' + filePath; // Concatenar el link a la ruta del archivo
                if (filePath) {
                    var viewBtn = `<a href="${url}" target="_blank" class="btn rounded-full view-btn bg-info/25 text-info hover:bg-info hover:text-white"><i class="fa fa-eye view-btn"></i></a>`;
                } else {
                    var viewBtn = `<a href="${url}" target="_blank" class="pointer-events-none btn rounded-full view-btn bg-warning/25 text-warning-opa bg-gray-200 hover:bg-gray-200"><i class="fa fa-eye"></i></a>`;
                }
                var chargeBtnLeg = `<button type="button" class="btn rounded-full charge-btn-leg bg-success/25 text-success hover:bg-success hover:text-white"><i class="fa fa-cloud-upload charge-btn-leg"></i></button>`;
                return chargeBtnLeg + ' ' + viewBtn;
            },
            cellClick: function (e, cell) {
                // Lógica pendiente de implementar
            }
        },
    ]
});


//Tabla de Coincidencias
const tblPersonasCN = new Tabulator("#tblPersonasCN", {
    height: "100%",
    layout: "fitDataFill",
    responsiveLayout: "collapse",
    columns: [
        { title: "Código", field: "CODI_PERS", hozAlign: "center", width: '10%' },
        { title: "Personal", field: "personal", hozAlign: "left", width: '30%' },
        { title: "Nro Documento", field: "nroDoc", hozAlign: "center", width: '15%' },
        { title: "Sucursal", field: "sucursal", hozAlign: "center", width: '18%' },
    ],
});

// Función para actualizar la tabla de folios por TIPO
function filterTableByTipoFolio() {
    const tipoFolioSeleccionado = document.querySelector('input[name="tipo_folio"]:checked').value;
    tblDocs.setFilter("tipo_folio", "=", tipoFolioSeleccionado);
}


// Escuchar los cambios en los radio buttons
document.querySelectorAll('input[name="tipo_folio"]').forEach(radio => {
    radio.addEventListener('change', filterTableByTipoFolio);
});

//Tabla de Legajos
document.addEventListener('DOMContentLoaded', function () {

    const params = new URLSearchParams(window.location.search);
    const codPersonal = params.get('codPersonal');
    const nombre = params.get('nombre');

    if (codPersonal) {
        abrirFoliosDesdeNotificacion(codPersonal, nombre);
    }


    document.getElementById('cargos').addEventListener('change', function () {
        getLegajos();
        //COINCIDENCIAS
        //document.getElementById('divCoincidencias').classList.remove('hidden');
        //getCoincidencias(clienteSeleccionado, cargoSeleccionado);
    });

    document.getElementById('clientes').addEventListener('change', function () {
        const clienteLeg = document.getElementById('clientes').value;
        getCargos(clienteLeg);
    });


});

function abrirFoliosDesdeNotificacion(codPersonal, nombre) {

    // Setear código
    document.getElementById('codPersonal').value = codPersonal;

    // Cargar folios
    getDocsObligatorios(codPersonal);

    // Mostrar / ocultar bloques
    document.getElementById('dataDocs').classList.remove('hidden');
    document.getElementById('dataDocsLeg').classList.add('hidden');
    document.getElementById('divCoincidencias').classList.add('hidden');

    // Título genérico (no tienes el nombre aún)
    updateCardTitle(nombre);

    // Scroll suave
    setTimeout(() => {
        document.getElementById('dataDocs')
            ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 300);
}





// Función para asignar nombre a la card de documentos
function updateCardTitle(nombrePersona) {
    const cardTitle = document.querySelector('.nombrePersDocs');
    cardTitle.textContent = `Folios de ${nombrePersona}`;
    const cardTitleLeg = document.querySelector('.nombrePersLeg');
    cardTitleLeg.textContent = `Legajos para ${nombrePersona}`;
}
//Listeners para aplicar filtros
document.getElementById("buscarPersonal").addEventListener("keyup", function () {
    reloadTabla();
});
document.getElementById("sucursal").addEventListener("change", function () {
    reloadTabla();
});
document.querySelectorAll('input[name="tipo_per"]').forEach(radio => {
    radio.addEventListener("change", function () {
        reloadTabla();
    });
});
document.querySelectorAll('input[name="vigencia"]').forEach(radio => {
    radio.addEventListener("change", function () {
        reloadTabla();
    });
});

/*
document.getElementById("buscarPersonal").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    const tipoPersonalSeleccionado = document.querySelector('input[name="tipo_per"]:checked').value;
    if (!tipoPersonalSeleccionado) {
        tblPersonas.setFilter([
            [
                { field: "CODI_PERS", type: 'like',  value: valor },
                { field: "personal", type: 'like',  value: valor },
                { field: "nroDoc", type: 'like', value: valor },
                { field: "sucursal", type: 'like', value: valor },
                { field: "col", type: 'like', value: valor },
            ]
        ]);
    } else if(tipoPersonalSeleccionado == 'TODOS'){
        tblPersonas.setFilter([
            [
                { field: "CODI_PERS", type: 'like',  value: valor },
                { field: "personal", type: 'like',  value: valor },
                { field: "nroDoc", type: 'like', value: valor },
                { field: "sucursal", type: 'like', value: valor },
                { field: "col", type: 'like', value: valor },
            ]
        ]);
    }else{
        //alert(tipoPersonalSeleccionado);


        tblPersonas.clearFilter();

        let filtrosBusqueda = [
            { field: "CODI_PERS", type: 'like',  value: valor },
            { field: "personal", type: 'like',  value: valor },
            { field: "nroDoc", type: 'like', value: valor },
            { field: "sucursal", type: 'like', value: valor },
            { field: "col", type: 'like', value: valor },
        ];

        // 1) aplicar tu array como filtro OR
        tblPersonas.setFilter(function(data){
            let v = valor.toLowerCase();

            return filtrosBusqueda.some(f =>
                (data[f.field] + "").toLowerCase().includes(v)
            );
        });

        // 2) añadir el filtro fijo TIPOTRAB
        tblPersonas.addFilter("TIPOTRAB", "=", tipoPersonalSeleccionado);


    }
    

    // Guardar el valor para usarlo tras cambios de página
    tblPersonas._ultimoFiltro = valor;

    setTimeout(() => resaltarTexto(valor), 10);

});
*/
document.getElementById("buscarFolio").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblDocs.setFilter([
        [
            { field: "documento", type: 'like', value: valor },
        ]
    ]);

    // Guardar el valor para usarlo tras cambios de página
    //tblPersonas._ultimoFiltro = valor;

    //setTimeout(() => resaltarTexto(valor), 10);

});


//Función para resaltar el texto del que se hace la búsqueda
function resaltarTexto(valor) {
    tblPersonas.getRows().forEach(row => {
        row.getElement().querySelectorAll(".tabulator-cell").forEach((cell, i, cells) => {
            if (i === cells.length - 1) return; // excluir última columna

            const text = cell.textContent;
            if (valor && text.toLowerCase().includes(valor)) {
                const regex = new RegExp(`(${valor})`, "gi");
                cell.innerHTML = text.replace(regex, "<span class='bg-warning/25'>$1</span>");
            } else {
                cell.innerHTML = text;
            }
        });
    });
};

// Cada vez que se renderiza una página en la tabla de personal
tblPersonas.on("renderComplete", function () {
    if (tblPersonas._ultimoFiltro) {
        resaltarTexto(tblPersonas._ultimoFiltro);
    }
});



//========================================== MODAL DJ ==========================================//
function limpiarModalDj() {
    document.getElementById('fecha_emision').value = '';
    document.getElementById('archivoInput').value = '';
    document.getElementById('listaArchivos').innerHTML = '';
}
document.getElementById('formFolioPersonal').addEventListener('submit', function (event) {
    event.preventDefault();

    const fechaEmision = document.getElementById('fecha_emision').value;
    const codigoPer    = document.getElementById('codPersonal').value;
    const inputFile    = document.getElementById('archivoInput');
    const archivo      = inputFile?.files?.[0];

    if (!fechaEmision) {
        Swal.fire({ title: 'Ingrese la fecha de emisión', icon: 'warning' });
        return;
    }

    if (!archivo) {
        Swal.fire({ title: 'Seleccione un archivo PDF', icon: 'warning' });
        return;
    }

    if (archivo.type !== 'application/pdf') {
        Swal.fire({ title: 'El archivo debe ser PDF', icon: 'warning' });
        return;
    }

    const formData = new FormData();
    formData.append('fecha_emision', fechaEmision);
    formData.append('codPersonal', codigoPer);
    formData.append('pdf', archivo);

    axios.post(`${VITE_URL_APP}/save-dj-folio-2`, formData, {
        headers: {
            'Accept': 'application/json',
        }
    })
    .then(function () {
        document.getElementById('btn-modal-docs-close').click();
        getDocsObligatorios(codigoPer);
        limpiarModalDj();

        Swal.fire({
            title: 'DJ guardado correctamente',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    })
    .catch(function (error) {
        let msg = 'Error al guardar el documento';

        if (error.response) {
            msg = error.response.data?.error 
               || error.response.data?.message 
               || JSON.stringify(error.response.data);
        }

        Swal.fire({ title: msg, icon: 'error' });
    });
});

//========================================== DATA CON AXIOS ==========================================//
// Función para obtener los folios por persona
function getDocsObligatorios(codigo) {
    axios.get(`${VITE_URL_APP}/get-documentos/${codigo}`)
        .then(response => {
            tblDocs.setData(response.data);
            // Aplicar filtro "PRINCIPAL" por defecto después de cargar los datos
            filterTableByTipoFolio();
        })
        .catch(error => {
            console.error("Error al obtener los datos:", error);
        });
}
// Función para obtener el listados de personas
function getPersonal() {
    axios.get(`${VITE_URL_APP}/api/get-personal`)
        .then(response => {
            const datosTabla = response.data;
            //tblPersonas.setData(datosTabla);

        })
        .catch(error => {
            console.error("Hubo un error:", error);
        });
}
// Función para obtener los legajos
function getLegajos() {
    tblLegajos.clearData();
    document.getElementById('tblDocsLegajo').classList.remove('hidden');
    const cliente = document.getElementById('clientes').value;
    const cargo = document.getElementById('cargos').value;
    const codigoPer = document.getElementById('codPersonal').value;
    axios.get(`${VITE_URL_APP}/api/get-legajos`, {
        params: {
            cliente: cliente,
            cargo: cargo,
            codigo: codigoPer
        }
    })
        .then(function (response) {
            tblLegajos.clearData();
            tblLegajos.setData(response.data);
        })
        .catch(function (error) {
            console.error("Error al obtener los legajos:", error);
        });
};

// Función para obtener los cargos con legajos
function getCargos(clienteLeg) {
    axios.get(`${VITE_URL_APP}/api/get-cargos`, {
        params: {
            cliente: clienteLeg,
        }
    })
        .then(function (response) {
            const select = document.getElementById("cargos");
            select.innerHTML = '<option disabled selected>-Seleccionar-</option>';
            response.data.forEach(cargo => {
                const option = document.createElement("option");
                option.value = cargo.codigo;
                option.textContent = cargo.nombre;
                select.appendChild(option);
            })
        })
        .catch(function (error) {
            console.error("Error al obtener los legajos:", error);
        });
};

// Función para obtener las coincidencias
function getCoincidencias(cliente, cargo) {
    axios.get(`${VITE_URL_APP}/api/get-coincidencias`, {
        params: {
            cliente: cliente,
            cargo: cargo
        }
    })
        .then(response => {
            tblPersonasCN.setData(response.data);
        })
        .catch(error => {
            console.error("Hubo un error:", error);
        });
};




window.addEventListener("sidebar-toggled", () => {
    tblPersonas?.redraw(true);
    //tblCargo?.redraw(true);
});





async function getUsuario() {
    try {
       
        const response = await axios.get(`${VITE_URL_APP}/usuario`);

        return response.data;

    } catch (error) {
        if (error.response) {
            // Error del servidor (Laravel)
            console.error('Error:', error.response.data.message);
        } else if (error.request) {
            // No hubo respuesta
            console.error('Sin respuesta del servidor');
        } else {
            // Error interno
            console.error('Error:', error.message);
        }

        return null;
    }
}


// Lupa — para huella y firma
window.toggleLupa = function(id) {
    const lupa = document.getElementById('lupa_' + id);
    const img  = document.getElementById(id);
    const cont = document.getElementById('cont_' + id);
    if (!lupa || !img || !cont) return;

    const activa = lupa.style.display === 'block';

    if (!activa) {
        // Activar lupa
        lupa.style.display = 'block';
        cont.style.overflow = 'visible'; // ← permite que la lupa salga del borde

        cont.onmousemove = function(e) {
            const contRect = cont.getBoundingClientRect();
            const imgRect  = img.getBoundingClientRect();

            // Posición del cursor relativa al CONTENEDOR
            const cx = e.clientX - contRect.left;
            const cy = e.clientY - contRect.top;

            // Posición del cursor relativa a la IMAGEN real
            const ix = e.clientX - imgRect.left;
            const iy = e.clientY - imgRect.top;

            const lw    = lupa.offsetWidth;
            const lh    = lupa.offsetHeight;
            const scale = 2.8;

            // Mover lupa centrada en el cursor (relativa al cont)
            lupa.style.left = (cx - lw / 2) + 'px';
            lupa.style.top  = (cy - lh / 2) + 'px';

            // Background: la imagen escalada posicionada para mostrar
            // la zona bajo el cursor ampliada
            const bgW = imgRect.width  * scale;
            const bgH = imgRect.height * scale;
            const bgX = -(ix * scale - lw / 2);
            const bgY = -(iy * scale - lh / 2);

            lupa.style.backgroundImage    = `url('${img.src}')`;
            lupa.style.backgroundSize     = `${bgW}px ${bgH}px`;
            lupa.style.backgroundPosition = `${bgX}px ${bgY}px`;
        };

        cont.onmouseleave = function() {
            lupa.style.display  = 'none';
            cont.style.overflow = 'hidden';
            cont.onmousemove    = null;
            cont.onmouseleave   = null;
        };

    } else {
        // Desactivar lupa
        lupa.style.display  = 'none';
        cont.style.overflow = 'hidden';
        cont.onmousemove    = null;
        cont.onmouseleave   = null;
    }
};

(function() {
    const lb = document.createElement('div');
    lb.id = 'lb-overlay';
    lb.style.cssText = `
        display:none; position:fixed; inset:0; z-index:9999;
        background:rgba(0,0,0,0.92);
        flex-direction:column; align-items:center; justify-content:center;`;

    lb.innerHTML = `
        <!-- Barra superior -->
        <div style="
            width:100%; padding:10px 20px;
            background:rgba(0,0,0,0.7);
            display:flex; align-items:center; justify-content:space-between;
            border-bottom:1px solid rgba(255,255,255,0.1);">
            <span id="lb-titulo" style="color:#e5e7eb; font-size:13px; font-weight:500;">Vista de imagen</span>
            <button id="lb-close-top" title="Cerrar (Esc)" style="
                background:rgba(220,38,38,0.7); border:1px solid rgba(220,38,38,0.5);
                color:white; border-radius:8px; width:34px; height:34px;
                cursor:pointer; display:flex; align-items:center; justify-content:center;">
                <svg width="14" height="14" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Canvas imagen -->
        <div id="lb-canvas" style="
    flex:1; width:100%; display:flex; align-items:center; justify-content:center;
    overflow:hidden; cursor:grab; user-select:none; position:relative;">
            <img id="lb-img" style="
                max-width:90vw; max-height:75vh;
                transform-origin:center center;
                pointer-events:none; display:block;"/>

            <!-- Controles flotantes sobre la imagen -->
            <div style="
                position:absolute; bottom:16px; left:50%; transform:translateX(-50%);
                background:rgba(0,0,0,0.65); backdrop-filter:blur(6px);
                border:1px solid rgba(255,255,255,0.15);
                border-radius:12px; padding:8px 14px;
                display:flex; align-items:center; gap:8px;">

                <button id="lb-zout" title="Alejar (-)" style="
                    background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2);
                    color:white; border-radius:7px; width:34px; height:34px;
                    cursor:pointer; display:flex; align-items:center; justify-content:center;
                    transition:background .15s;">
                    <svg width="15" height="15" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3M8 11h6"/>
                    </svg>
                </button>

                <span id="lb-zoom-label" style="
                    color:#e5e7eb; font-size:12px; font-weight:600;
                    min-width:40px; text-align:center;">100%</span>

                <button id="lb-zin" title="Acercar (+)" style="
                    background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2);
                    color:white; border-radius:7px; width:34px; height:34px;
                    cursor:pointer; display:flex; align-items:center; justify-content:center;
                    transition:background .15s;">
                    <svg width="15" height="15" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3M11 8v6M8 11h6"/>
                    </svg>
                </button>

                <div style="width:1px; height:24px; background:rgba(255,255,255,0.2);"></div>

                <button id="lb-reset" title="Restablecer (0)" style="
                    background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2);
                    color:white; border-radius:7px; width:34px; height:34px;
                    cursor:pointer; display:flex; align-items:center; justify-content:center;
                    transition:background .15s;">
                    <svg width="14" height="14" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
                        <path d="M3 3v5h5"/>
                    </svg>
                </button>

                <div style="width:1px; height:24px; background:rgba(255,255,255,0.2);"></div>

                <button id="lb-fullscreen" title="Pantalla completa" style="
                    background:rgba(255,255,255,0.12); border:1px solid rgba(255,255,255,0.2);
                    color:white; border-radius:7px; width:34px; height:34px;
                    cursor:pointer; display:flex; align-items:center; justify-content:center;
                    transition:background .15s;">
                    <svg id="lb-fs-icon" width="14" height="14" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24">
                        <path d="M15 3h6m0 0v6m0-6-7 7M9 21H3m0 0v-6m0 6 7-7"/>
                    </svg>
                </button>
            </div>
        </div>`;

    document.body.appendChild(lb);

    let scale = 1, posX = 0, posY = 0, dragging = false, startX = 0, startY = 0;
    const lbImg    = document.getElementById('lb-img');
    const lbCanvas = document.getElementById('lb-canvas');
    const lbLabel  = document.getElementById('lb-zoom-label');

    function applyTransform() {
        lbImg.style.transform = `translate(${posX}px, ${posY}px) scale(${scale})`;
        lbLabel.textContent   = Math.round(scale * 100) + '%';
    }

    function resetView() {
        scale = 1; posX = 0; posY = 0;
        applyTransform();
    }

    // Zoom rueda
    lbCanvas.addEventListener('wheel', (e) => {
        e.preventDefault();
        scale = Math.min(Math.max(scale + (e.deltaY > 0 ? -0.15 : 0.15), 0.3), 8);
        applyTransform();
    }, { passive: false });

    // Drag
    lbCanvas.addEventListener('mousedown', (e) => {
        if (e.target.closest('button')) return;
        dragging = true;
        startX = e.clientX - posX;
        startY = e.clientY - posY;
        lbCanvas.style.cursor = 'grabbing';
    });
    document.addEventListener('mousemove', (e) => {
        if (!dragging) return;
        posX = e.clientX - startX;
        posY = e.clientY - startY;
        applyTransform();
    });
    document.addEventListener('mouseup', () => {
        dragging = false;
        lbCanvas.style.cursor = 'grab';
    });

    // Botones
    document.getElementById('lb-zin').onclick    = () => { scale = Math.min(scale + 0.25, 8); applyTransform(); };
    document.getElementById('lb-zout').onclick   = () => { scale = Math.max(scale - 0.25, 0.3); applyTransform(); };
    document.getElementById('lb-reset').onclick  = resetView;
    document.getElementById('lb-close-top').onclick = cerrarLightbox;

    document.getElementById('lb-fullscreen').onclick = () => {
        if (!document.fullscreenElement) {
            lb.requestFullscreen?.();
            document.getElementById('lb-fs-icon').innerHTML = `<path d="M8 3H3m0 0v5m0-5 7 7M16 21h5m0 0v-5m0 5-7-7"/>`;
        } else {
            document.exitFullscreen?.();
            document.getElementById('lb-fs-icon').innerHTML = `<path d="M15 3h6m0 0v6m0-6-7 7M9 21H3m0 0v-6m0 6 7-7"/>`;
        }
    };

    // Teclado
    document.addEventListener('keydown', (e) => {
        if (lb.style.display === 'none') return;
        if (e.key === 'Escape') cerrarLightbox();
        if (e.key === '+' || e.key === '=') { scale = Math.min(scale + 0.25, 8); applyTransform(); }
        if (e.key === '-') { scale = Math.max(scale - 0.25, 0.3); applyTransform(); }
        if (e.key === '0') resetView();
    });

    // Hover botones
    ['lb-zin','lb-zout','lb-reset','lb-fullscreen'].forEach(id => {
        const b = document.getElementById(id);
        b.onmouseover = () => b.style.background = 'rgba(255,255,255,0.25)';
        b.onmouseout  = () => b.style.background = 'rgba(255,255,255,0.12)';
    });

    window.abrirLightbox = function(imgId) {
        const imgEl = document.getElementById(imgId);
        if (!imgEl) return;
        lbImg.src = imgEl.src;
        lb.style.display = 'flex';
        resetView();
    };

    function cerrarLightbox() {
        lb.style.display = 'none';
        lbImg.src = '';
        if (document.fullscreenElement) document.exitFullscreen?.();
    }
})();

