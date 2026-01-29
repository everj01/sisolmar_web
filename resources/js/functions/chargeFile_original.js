
import axios from 'axios';
import Swal from 'sweetalert2';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

getPersonal();
window.archivosSeleccionados = [];

//Tabla de Personas
const tblPersonas = new Tabulator("#tblPersonas", {
    height: "100%",
    layout:"fitData",
    responsiveLayout:"collapse",
    pagination: true,
    paginationSize: 10,
    rowHeader:{formatter:"responsiveCollapse", width:30, minWidth:30, hozAlign:"center", resizable:false, headerSort:false},
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
                "default": "Filtrar...", // Texto en filtros de encabezado
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
    columns:[
        {title:"Cód.", field:"CODI_PERS", hozAlign:"center", width: '10%'},
        {title:"Personal", field:"personal", hozAlign:"left", width: '30%'},
        {title:"Nro Doc.", field:"nroDoc", hozAlign:"center", width: '15%'},
        {title:"Sucursal", field:"sucursal", hozAlign:"center", width: '20%'},
        {title: "Acciones", field: "acciones", width: '25%', hozAlign: "center", headerSort: false,
            formatter: function(cell, formatterParams, onRendered) {
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


//Tabla de Folios
const tblDocs = new Tabulator("#tblDocs", {
    height: "100%",
    layout:"fitDataFill",
    responsiveLayout: "collapse",
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
        { title: "Emision", field: "fecha_emision", hozAlign: "center", width: '20%',
            formatter: function(cell, formatterParams){
                var emision = cell.getValue();
                if (emision === null){
                    return '<span class="rounded-full bg-warning/25">&nbsp;&nbsp;PENDIENTE&nbsp;&nbsp;</span>';
                }else{
                    return emision;
                }
            }
        },
        { title: "Caducidad", field: "fecha_caducidad", hozAlign: "center", width: '20%',
            formatter: function(cell, formatterParams) {
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
        { title: "Acciones", field: "acciones", hozAlign: "center", width: '20%', headerSort: false,
            formatter: function(cell, formatterParams, onRendered) {
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
            cellClick: function(e, cell) {
                if (e.target.classList.contains('charge-btn')) {
                    const documento = cell.getRow().getData().documento;
                    const periodo = cell.getRow().getData().periodo;
                    const cantidad_hojas = cell.getRow().getData().cantidad_hojas;
                    const meses = cell.getRow().getData().meses;
                    const codFolio = cell.getRow().getData().codFolio;
                    const vencimiento = cell.getRow().getData().vencimiento;

                    document.querySelector('#modal-file h3.modal-title').textContent = `Documento: ${documento}`;
                    document.querySelector('#txtPeriodo').textContent = `${periodo}`;
                    document.querySelector('#txtCantHojas').textContent = `${cantidad_hojas}`;
                    document.getElementById('cantArchivos').value = cantidad_hojas;
                    document.getElementById('codFolio').value = codFolio;
                    document.getElementById('meses').value = meses;

                    // Verificar si vencimiento es 0 y ocultar el campo de caducidad
                    if (vencimiento == 0) {
                        document.getElementById('divCaducidad').classList.add('hidden');
                        document.getElementById('fecha_caducidad').removeAttribute('required');
                    } else {
                        document.getElementById('divCaducidad').classList.remove('hidden');
                        document.getElementById('fecha_caducidad').setAttribute('required', 'required');
                    };

                    limpiarModal();
                    document.getElementById('btn-modal-docs').click();
                }

                if(e.target.classList.contains('viewdoc-btn')){
                    const dataTbl = cell.getRow().getData();
                    const codFolio = dataTbl.codFolio;
                    const codPersonal = dataTbl.codPersonal;
                    const nombre = dataTbl.personal;
                    const documento = dataTbl.documento;

                    axios.get(`${ VITE_URL_APP }/api/get-view-documents/${codPersonal}/${codFolio}`)
                    .then(response => {
                        console.log(response);

                        if(response.data.success !== true){
                            Swal.fire({
                                title: "No se encontro documentos válidos",
                                icon: "info"
                            });
                            return;
                        }

                        document.querySelector('#modal-view-docs .modal-title').textContent = `${nombre}`;
                        document.querySelector('#modal-view-docs #txtDocSelec').textContent = `${documento}`;

                        const rutas = response.data.rutas; // ← tu array del backend
                        const visor = document.getElementById('visorDocs');

                        visor.innerHTML = '';

                        rutas.forEach(ruta => {
                            visor.insertAdjacentHTML('beforeend', `
                                <img src="http://${ruta}" class="w-full max-w-[700px] mb-3 rounded-md" />
                            `);
                        });

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
            rowFormatter: function(row) {
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
        { title: "Emision", field: "fecha_emision", hozAlign: "center", width: '20%',
            formatter: function(cell, formatterParams){
                var emision = cell.getValue();
                if (emision === null){
                    return '<span class="rounded-full bg-warning/25">&nbsp;&nbsp;PENDIENTE&nbsp;&nbsp;</span>';
                }else{
                    return emision;
                }
            }
        },
        { title: "Caducidad", field: "fecha_caducidad", hozAlign: "center", width: '20%',
            formatter: function(cell, formatterParams) {
                var vigente = cell.getRow().getData().vigente;
                var fechaCaducidad = cell.getValue();
                if (vigente == 1) {
                    return `<span class="text-vigente-800 font-bold">${fechaCaducidad == null ? '--' : fechaCaducidad}</span>`
                } else if (vigente == 0) {
                    return `<span class="text-vencido-800 font-bold">${fechaCaducidad == null ? '--' : fechaCaducidad }</span>`
                } else {
                    return '<span class="rounded-full bg-warning/25">&nbsp;&nbsp;PENDIENTE&nbsp;&nbsp;</span>';
                }
            }
         },
        { title: "Acciones", field: "accionesy", hozAlign: "center", width: '20%', headerSort: false,
            formatter: function(cell, formatterParams, onRendered) {
                var filePath = cell.getRow().getData().ruta_archivo;
                var url = '/storage/' + filePath; // Concatenar el link a la ruta del archivo
                if(filePath){
                    var viewBtn = `<a href="${url}" target="_blank" class="btn rounded-full view-btn bg-info/25 text-info hover:bg-info hover:text-white"><i class="fa fa-eye view-btn"></i></a>`;
                }else{
                    var viewBtn = `<a href="${url}" target="_blank" class="pointer-events-none btn rounded-full view-btn bg-warning/25 text-warning-opa bg-gray-200 hover:bg-gray-200"><i class="fa fa-eye"></i></a>`;
                }
                var chargeBtnLeg = `<button type="button" class="btn rounded-full charge-btn-leg bg-success/25 text-success hover:bg-success hover:text-white"><i class="fa fa-cloud-upload charge-btn-leg"></i></button>`;
                return chargeBtnLeg+' '+viewBtn;
            },
            cellClick: function(e, cell) {
                if (e.target.classList.contains('charge-btn-leg')) {
                    const documento = cell.getRow().getData().documento;
                    const periodo = cell.getRow().getData().periodo;
                    const meses = cell.getRow().getData().meses;
                    const codFolio = cell.getRow().getData().codFolio;
                    const vencimiento = cell.getRow().getData().vencimiento;

                    document.querySelector('#modal-file h3.modal-title').textContent = `Documento: ${documento}`;
                    document.querySelector('#txtPeriodo').textContent = `${periodo}`;
                    document.getElementById('codFolio').value = codFolio;
                    document.getElementById('meses').value = meses;

                    // Verificar si vencimiento es 0 y ocultar el campo de caducidad
                    if (vencimiento == 0) {
                        document.getElementById('divCaducidad').classList.add('hidden');  // Ocultar el div
                        document.getElementById('fecha_caducidad').removeAttribute('required');  // Quitar el atributo required
                    } else {
                        document.getElementById('divCaducidad').classList.remove('hidden');  // Mostrar el div
                        document.getElementById('fecha_caducidad').setAttribute('required', 'required');  // Asegurarse de que sea requerido
                    };

                    document.getElementById('btn-modal-docs').click();
                }
            }
        },
    ]
});


//Tabla de Coincidencias
const tblPersonasCN = new Tabulator("#tblPersonasCN", {
    height: "100%",
    layout:"fitDataFill",
    responsiveLayout: "collapse",
    columns: [
        {title:"Código", field:"CODI_PERS", hozAlign:"center", width: '10%'},
        {title:"Personal", field:"personal", hozAlign:"left", width: '30%'},
        {title:"Nro Documento", field:"nroDoc", hozAlign:"center", width: '15%'},
        {title:"Sucursal", field:"sucursal", hozAlign:"center", width: '18%'},
    ],
});

// Función para actualizar la tabla de personas por SUCURSAL
function filtroXSucursal() {
    const sucursalSeleccionada = document.getElementById('sucursal').value;
    if (!sucursalSeleccionada) {
        tblPersonas.clearFilter();
    } else if(sucursalSeleccionada == 'TODOS'){
        tblPersonas.clearFilter();
    } else{
        tblPersonas.setFilter("sucursal","=",sucursalSeleccionada);
    }
}
document.getElementById('sucursal').addEventListener('change', filtroXSucursal);

// Función para actualizar la tabla de folios por TIPO
function filterTableByTipoFolio() {
    const tipoFolioSeleccionado = document.querySelector('input[name="tipo_folio"]:checked').value;
    tblDocs.setFilter("tipo_folio", "=", tipoFolioSeleccionado);
}
function filterTableByTipoPersonal() {
    const tipoPersonalSeleccionado = document.querySelector('input[name="tipo_per"]:checked').value;
    if (tipoPersonalSeleccionado == 'TODOS') {
        tblPersonas.clearFilter();
    }else{
        tblPersonas.setFilter("TIPOTRAB", "=", tipoPersonalSeleccionado);
    }
    document.getElementById('buscarPersonal').value='';
}

// Escuchar los cambios en los radio buttons
document.querySelectorAll('input[name="tipo_folio"]').forEach(radio => {
    radio.addEventListener('change', filterTableByTipoFolio);
});
document.querySelectorAll('input[name="tipo_per"]').forEach(radio => {
    radio.addEventListener('change', filterTableByTipoPersonal);
});


//Tabla de Legajos
document.addEventListener('DOMContentLoaded', function() {

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


     // Evento de cambio de fecha de emisión
     document.getElementById('fecha_emision').addEventListener('change', function () {
        const fechaEmision = document.getElementById('fecha_emision').value;
        if (fechaEmision) {
            // Calculamos la fecha de caducidad
            const fechaCalculada = calcularFechaCaducidad(fechaEmision);
            document.getElementById('fecha_caducidad').value = fechaCalculada; // Llenamos la fecha de caducidad
        }
    });
});

// Función para asignar nombre a la card de documentos
function updateCardTitle(nombrePersona) {
    const cardTitle = document.querySelector('.nombrePersDocs');
    cardTitle.textContent = `Folios de ${nombrePersona}`;
    const cardTitleLeg = document.querySelector('.nombrePersLeg');
    cardTitleLeg.textContent = `Legajos para ${nombrePersona}`;
}

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

document.getElementById("buscarFolio").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblDocs.setFilter([
        [
            { field: "documento", type: 'like',  value: valor },
        ]
    ]);

    // Guardar el valor para usarlo tras cambios de página
    //tblPersonas._ultimoFiltro = valor;

    //setTimeout(() => resaltarTexto(valor), 10);

});


//Función para resaltar el texto del que se hace la búsqueda
function resaltarTexto(valor){
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

// Función para calcular la fecha de caducidad
function calcularFechaCaducidad(fechaEmision) {
    const periodoVigencia = parseInt(document.getElementById('meses').value);
    if(periodoVigencia > 1){
        const fecha = new Date(fechaEmision);
        fecha.setMonth(fecha.getMonth() + periodoVigencia);
        const anio = fecha.getFullYear();
        const mes = ('0' + (fecha.getMonth() + 1)).slice(-2);
        const dia = ('0' + fecha.getDate()).slice(-2);
        return `${anio}-${mes}-${dia}`;
    }
}

//Función para limpia los campos del modal
function limpiarModal(){
    document.getElementById('fecha_emision').value="";
    document.getElementById('fecha_caducidad').value="";

    window.archivosSeleccionados = [];
    //document.getElementById('cantArchivos').value="";
    document.getElementById('listaArchivos').innerHTML='';
    document.getElementById('archivoInput').value='';
}

//========================================== DATA CON AXIOS ==========================================//
// Función para obtener los folios por persona
function getDocsObligatorios(codigo){
    axios.get(`${ VITE_URL_APP }/api/get-documentos/${codigo}`)
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
function getPersonal(){
    axios.get(`${ VITE_URL_APP }/api/get-personal`)
    .then(response => {
        const datosTabla = response.data;
        tblPersonas.setData(datosTabla);

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
    axios.get(`${ VITE_URL_APP }/api/get-legajos`, {
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
    axios.get(`${ VITE_URL_APP }/api/get-cargos`, {
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
    axios.get(`${ VITE_URL_APP }/api/get-coincidencias`, {
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



//================================ GUARDAR LOS DATOS POR AXIOS ================================//
document.getElementById('formFolioPersonal').addEventListener('submit', function(event) {
    event.preventDefault();
    var fechaEmision = document.getElementById('fecha_emision').value;
    var fechaCaducidad = document.getElementById('fecha_caducidad').value;
    var codigoPer = document.getElementById('codPersonal').value;
    var codFolio = document.getElementById('codFolio').value;

    var formData = new FormData();

    formData.append('fecha_emision', fechaEmision);
    formData.append('fecha_caducidad', fechaCaducidad);
    formData.append('codFolio', codFolio);
    formData.append('codPersonal', codigoPer);

    for (let i = 0; i < archivosSeleccionados.length; i++) {
        formData.append('imagenes[]', archivosSeleccionados[i]);
    }

    if (fechaEmision /*&& fechaCaducidad*/) {
        // Enviar los datos al servidor usando Axios
        axios.post(`${ VITE_URL_APP }/api/save_folio_persona`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data' // Es necesario para enviar archivos
            }
        })
        .then(function(response) {
            document.getElementById('btn-modal-docs-close').click();
            getDocsObligatorios(codigoPer);
            getLegajos();
            limpiarModal();
        })
        .catch(function(error) {
            console.error('Error al guardar los datos:', error);
        });
    }
});






