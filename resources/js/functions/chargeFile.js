
import axios from 'axios';
import Swal from 'sweetalert2';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

let usuarioActual = null;

// Al inicio del archivo, reemplaza getPersonal() por esto:
(async () => {
    usuarioActual = await getUsuario();
    getPersonal();

    seleccionarPrimeraSucursalValida();
    reloadTabla();
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

    ajaxURL: `${VITE_URL_APP}/api/get-personal-total`,
    ajaxParams: { page: 1, size: pageSizePersonas },
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
            title: "Acciones", field: "acciones", width: 160, hozAlign: "center", headerSort: false, responsive: false,
            formatter: function (cell) {
                var docsBtn = `<button type="button" class="btn rounded-full docs-btn bg-success/25 text-success hover:bg-success hover:text-white">Folios</button>`;

                // Solo mostrar Legajos si el rol NO es 8
                var legajoBtn = '';
                if (usuarioActual?.tipo_rol != 8) {
                    legajoBtn = `<button type="button" class="btn rounded-full legajo-btn bg-warning/25 text-warning hover:bg-warning hover:text-white">Legajos</button>`;
                }

                return docsBtn + ' ' + legajoBtn;
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
                } else {
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


function seleccionarPrimeraSucursalValida() {
    const select = document.getElementById("sucursal");
    if (!select) return;

    const primeraOpcionValida = [...select.options].find(opt =>
        opt.value &&
        opt.value !== "00" &&
        opt.value !== "-Seleccionar-" &&
        !opt.disabled
    );

    if (primeraOpcionValida) {
        select.value = primeraOpcionValida.value;
    }
}

function reloadTabla() {

    let search = document.getElementById("buscarPersonal").value.trim();
    let codSucursalSelect = document.getElementById("sucursal");
    let codSucursal = codSucursalSelect.value;

    if (!codSucursal || codSucursal == "-Seleccionar-" || codSucursal == "00") {
        codSucursal = "0"; // fallback cuando no selecciona nada
    }
    let tipo_per = document.querySelector('input[name="tipo_per"]:checked')?.value || "TODOS";
    let vigencia = document.querySelector('input[name="vigencia"]:checked')?.value || "";

    tblPersonas.setData(`${VITE_URL_APP}/get-personal-total`, {
        page: 1,
        size: pageSizePersonas,
        search: search,
        codSucursal: codSucursal,
        tipo_per: tipo_per,
        vigencia: vigencia
    });

    // Guardar el valor para usarlo tras cambios de página
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
                    const codFolio = dataTbl.codFolio;
                    const codPersonal = dataTbl.codPersonal;
                    const nombre = dataTbl.personal;
                    const documento = dataTbl.documento;

                    axios.get(`${VITE_URL_APP}/api/get-view-documents/${codPersonal}/${codFolio}`)
                        .then(response => {
                            console.log(response);

                            if (response.data.success !== true) {
                                Swal.fire({
                                    title: "No se encontro documentos válidos",
                                    icon: "info"
                                });
                                return;
                            }

                            document.querySelector('#modal-view-docs .modal-title').textContent = `${nombre}`;
                            document.querySelector('#modal-view-docs #txtDocSelec').textContent = `${documento}`;

                            const rutas = response.data.rutas;
                            const visor = document.getElementById('visorDocs');
                            visor.innerHTML = '';

                            // ✅ Si codFolio es 25, previsualizar como PDF
                            if (parseInt(codFolio) === 25) {
                                rutas.forEach(ruta => {
                                    visor.insertAdjacentHTML('beforeend', `
                                        <iframe 
                                            src="http://${ruta}" 
                                            class="w-full mb-3 rounded-md" 
                                            style="height: 600px; border: none;"
                                        ></iframe>
                                    `);
                                });
                            } else {
                                // Para los demás folios → imágenes como antes
                                rutas.forEach(ruta => {
                                    visor.insertAdjacentHTML('beforeend', `
                                        <img src="http://${ruta}" class="w-full max-w-[700px] mb-3 rounded-md" />
                                    `);
                                });
                            }

                            document.getElementById('btn-modal-view-docs').click();
                        })
                        .catch(error => {
                            console.error("Error al obtener los datos:", error);
                            Swal.fire({
                                title: "Problema al encontrar documentos",
                                icon: "error"
                            });
                        });
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




