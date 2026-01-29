import axios from 'axios';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import { jsPDF } from "jspdf";

axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');


getPersonal();
getFolios();
new TomSelect('#cargos');

// Tabla de Personas
const tblPersonas = new Tabulator("#tblPersonas", {
    height: "100%",
    layout: "fitDataFill",
    responsiveLayout: "collapse",
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
        {title:"Código", field:"CODI_PERS", hozAlign:"center", width: '15%'},
        {title:"Personal", field:"personal", hozAlign:"left", width: '40%'},
        {title:"Nro DOC", field:"nroDoc", hozAlign:"center", width: '19%'},
        {title:"Sucursal", field:"sucursal", hozAlign:"center", width: '20%'},
        {title: "", field: "select", hozAlign:"center", width: '5%', headerSort: false,
            formatter: function(cell, formatterParams, onRendered) {
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.classList.add("form-checkbox", "rounded", "text-dark");
                checkbox.checked = cell.getValue() || false; // Establece si está seleccionado según el valor de la celda
                checkbox.addEventListener("change", function() {
                    cell.setValue(checkbox.checked);

                    setTimeout(() => {
                        // Mostrar todos los registros (limpiar filtro) y el input
                        tblPersonas.clearFilter();
                        document.getElementById('buscarPer').value = "";

                        // Reordenar: seleccionados primero
                        const allData = tblPersonas.getData();

                        const selected = allData.filter(row => row.select === true);
                        const unselected = allData.filter(row => row.select !== true);

                        const sortedData = selected.concat(unselected);

                        tblPersonas.replaceData(sortedData);
                    }, 300);
                });
                return checkbox;
            },
        }
    ],
});


// Para activar todos los checkbox del listado de personas
document.getElementById('select-all-per').addEventListener('change', function() {
    const isChecked = this.checked;
    const rows = tblPersonas.getRows();

    rows.forEach(row => {
        const rowCheckbox = row.getCell("select").getElement().querySelector('input[type="checkbox"]');
        rowCheckbox.checked = isChecked; // Marcar o desmarcar el checkbox de la fila
        row.getCell("select").setValue(isChecked); // Cambiar el valor de la celda
    });
});

// Tabla de Folios
const tblFolios = new Tabulator("#tblFolios", {
    height: "100%",
    layout: "fitDataFill",
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
        { title: "Folios", field: "nombre", hozAlign: "left", width: '60%'},
        {title: "Tipo",  field: "tipoFolio",  hozAlign: "center", width: '32%',
            formatter: function(cell, formatterParams) {
                var tipo = cell.getValue();
                if (tipo === "FORMATO") {
                    return '<span class="text-yellow2 font-bold">FORMATO</span>'
                } else if (tipo === "DOCUMENTO") {
                    return '<span class="text-barnie font-bold">DOCUMENTO</span>'
                } else if (tipo === "CERTIFICADO") {
                    return '<span class="text-green font-bold">CERTIFICADO</span>'
                }
                return tipo;
            }
        },
        {title: "", field: "select", hozAlign:"center", width: '5%', headerSort: false,
            formatter: function(cell, formatterParams, onRendered) {
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.classList.add("form-checkbox", "rounded", "text-dark");
                checkbox.checked = cell.getValue() || false; // Establece si está seleccionado según el valor de la celda
                checkbox.addEventListener("change", function() {
                    cell.setValue(checkbox.checked);

                    setTimeout(() => {
                        // Mostrar todos los registros (limpiar filtro) y el input
                        tblFolios.clearFilter();
                        document.getElementById('buscarFol').value = "";

                        // Reordenar: seleccionados primero
                        const allData = tblFolios.getData();

                        const selected = allData.filter(row => row.select === true);
                        const unselected = allData.filter(row => row.select !== true);

                        const sortedData = selected.concat(unselected);

                        tblFolios.replaceData(sortedData);
                    }, 300);
                });
                return checkbox;
            },
        }
    ]
});


// Tabla de Legajos
const tblLegajos = new Tabulator("#tblLegajos", {
    height: "100%",
    layout: "fitDataFill",
    responsiveLayout: "collapse",
    columns: [
        { title: "Folio", field: "documento", hozAlign: "left", width: '40%' },
        { title: "Emision", field: "fecha_emision", hozAlign: "center", width: '20%',
            formatter: function(cell, formatterParams){
                var emision = cell.getValue();
                if (emision === null){
                    return '-';
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
                    return `<span class="text-vigente-800 font-bold">${fechaCaducidad}</span>`
                } else if (vigente == 0) {
                    return `<span class="text-vencido-800 font-bold">${fechaCaducidad}</span>`
                } else {
                    return '-';
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
                var chargeBtnLeg = `<button type="button" class="btn rounded-full charge-btn bg-success/25 text-success hover:bg-success hover:text-white"><i class="fa fa-cloud-upload charge-btn"></i></button>`;
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
                        document.getElementById('divCaducidad').classList.add('hidden');
                        document.getElementById('fecha_caducidad').removeAttribute('required');
                    } else {
                        document.getElementById('divCaducidad').classList.remove('hidden');
                        document.getElementById('fecha_caducidad').setAttribute('required', 'required');
                    };

                    document.getElementById('btn-modal-docs').click();
                }
            }
        },
    ]
});


document.getElementById('select-all-fol').addEventListener('change', function() {
    const isChecked = this.checked;
    const rows = tblFolios.getRows(); // Obtener todas las filas de la tabla

    rows.forEach(row => {
        const rowCheckbox = row.getCell("select").getElement().querySelector('input[type="checkbox"]');
        rowCheckbox.checked = isChecked; // Marcar o desmarcar el checkbox de la fila
        row.getCell("select").setValue(isChecked); // Cambiar el valor de la celda
    });
});

document.getElementById('btnLeg2').classList.add("hidden");
document.getElementById('btnLeg1').classList.add("hidden");
document.getElementById('btnLeg3').classList.add("hidden");

// Obtener todos los enlaces dentro de las cards
const links = document.querySelectorAll('.card a');

links.forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault(); // Prevenir el comportamiento por defecto del enlace

        // Eliminar la clase 'active' de todas las cards
        document.querySelectorAll('.card').forEach(card => {
            card.classList.remove('active');
        });

        // Agregar la clase 'active' a la card actual
        this.closest('.card').classList.add('active');
    });
});

// Función para el MOSTRAR LAS CARDS de cada LEGAJO
document.getElementById("legajo1").addEventListener("click", function() {
    document.getElementById('personasDiv').classList.remove("hidden");
    document.getElementById('foliosDiv').classList.remove("hidden");
    document.getElementById('legajosDiv').classList.add("hidden");
    document.getElementById('btnLeg2').classList.add("hidden");
    document.getElementById('btnLeg3').classList.add("hidden");
    document.getElementById('btnLeg1').classList.remove("hidden");
    tblPersonas.redraw();
    //tblFolios.redraw();

});
document.getElementById("legajo2").addEventListener("click", function() {
    document.getElementById('personasDiv').classList.remove("hidden");
    document.getElementById('legajosDiv').classList.remove("hidden");
    document.getElementById('foliosDiv').classList.add("hidden");
    document.getElementById('btnLeg1').classList.add("hidden");
    document.getElementById('btnLeg3').classList.add("hidden");
    document.getElementById('btnLeg2').classList.remove("hidden");
    tblPersonas.redraw();
    //tblFolios.redraw();

});
document.getElementById("legajo3").addEventListener("click", function() {
    document.getElementById('personasDiv').classList.add("hidden");
    document.getElementById('legajosDiv').classList.add("hidden");
    document.getElementById('foliosDiv').classList.add("hidden");
    document.getElementById('btnLeg1').classList.add("hidden");
    document.getElementById('btnLeg2').classList.add("hidden");
    document.getElementById('btnLeg3').classList.remove("hidden");

});
document.getElementById("legajo4").addEventListener("click", function() {
    document.getElementById('personasDiv').classList.add("hidden");
    document.getElementById('legajosDiv').classList.add("hidden");
    document.getElementById('foliosDiv').classList.add("hidden");
    document.getElementById('btnLeg1').classList.add("hidden");
    document.getElementById('btnLeg2').classList.add("hidden");

});

// Llenado de la tabla de legajos
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('clientes').addEventListener('change', function () {
        document.getElementById('divCargos').classList.remove("hidden");
    });

    document.getElementById('cargos').addEventListener('change', function () {
        getLegajos();
    });
});

// Función para obtener los legajos
function getLegajos() {
    document.getElementById('tblLegajos').classList.remove('hidden');
    const cliente = document.getElementById('clientes').value;
    const cargo = document.getElementById('cargos').value;

    // Obtener las personas seleccionadas
    var selectedPersona = null;
    tblPersonas.getRows().forEach(function(row) {
        if (row.getCell("select").getValue()) {
            var codiPers = row.getData().CODI_PERS;

            // Verifica si el valor de CODI_PERS no está vacío
            if (codiPers) {
                selectedPersona = codiPers;
            }
        }
    });

    const codigoPer = selectedPersona;
    axios.get(`${ VITE_URL_APP }/api/get-legajos`, {
        params: {
            cliente: cliente,
            cargo: cargo,
            codigo: codigoPer
        }
    })
    .then(function (response) {
        console.log(response.data);
        tblLegajos.setData(response.data);
    })
    .catch(function (error) {
        console.error("Error al obtener los legajos tabla:", error);
    });
};

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

// Escuchar los cambios en los radio buttons
document.querySelectorAll('input[name="tipo_folio"]').forEach(radio => {
    radio.addEventListener('change', filterTableByTipoFolio);
});

// Función para BUSCAR
document.getElementById("buscarPer").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblPersonas.setFilter([
        [
            { field: "CODI_PERS", type: 'like',  value: valor },
            { field: "personal", type: 'like',  value: valor },
            { field: "nroDoc", type: 'like', value: valor },
            { field: "sucursal", type: 'like', value: valor },
            { field: "col", type: 'like', value: valor },
        ]
    ]);
});
document.getElementById("buscarFol").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblFolios.setFilter([
        [
            { field: "nombre", type: 'like',  value: valor },
            { field: "periodo", type: 'like',  value: valor },
            { field: "tipoFolio", type: 'like', value: valor },
        ]
    ]);
});

// Función para el BOTON GENERAR PDF
document.getElementById("btnLeg1").addEventListener("click", async function () {
    var selectedFolios = [];
    tblFolios.getRows().forEach(function(row) {
        if (row.getCell("select").getValue()) {
            selectedFolios.push(row.getData());
        }
    });

    for (const row of tblPersonas.getRows()) {
        if (row.getCell("select").getValue()) {
            const personaData = row.getData();
            const tempCod = personaData.CODI_PERS;

            try {
                await getArchivosXPersona_uno(tempCod, selectedFolios, 1);
            } catch (e) {
                console.warn("Falló para persona:", tempCod);
            }
        }
    }

    console.log("Todos los legajos han sido generados.");
});


document.getElementById("btnLeg2").addEventListener("click", async function () {
    const cliente = document.getElementById('clientes').value;
    const cargo = document.getElementById('cargos').value;
    const foliosData = await getFoliosClienteCargo(cliente, cargo);

    var selectedFolios = foliosData.map(folio => ({
        nombre: folio.folio,
        codigo: folio.codigo
    }));

    for (const row of tblPersonas.getRows()) {
        if (row.getCell("select").getValue()) {
            const personaData = row.getData();
            const tempCod = personaData.CODI_PERS;

            try {
                await getArchivosXPersona_uno(tempCod, selectedFolios, 1);
            } catch (e) {
                console.warn("Falló para persona:", tempCod);
            }
        }
    }

    console.log("Todos los legajos han sido generados.");
});




// Función para el BOTON GENERAR PDF 2
// document.getElementById("btnLeg2").addEventListener("click", async function() {

//     tblPersonas.getRows().forEach(function(row) {
//         if (row.getCell("select").getValue()) {

//         }
//     });

//     const cliente = document.getElementById('clientes').value;
//     const cargo = document.getElementById('cargos').value;
//     const foliosData = await getFoliosClienteCargo(cliente, cargo);

//     var selectedFolios = foliosData.map(folio => ({
//         nombre: folio.folio,
//         codigo: folio.codigo
//     }));

//     getArchivosXPersonas(selectedPersonas, selectedFolios, 2);
// });

// Función para el BOTON GENERAR PDF 3
document.getElementById("btnLeg3").addEventListener("click", async function() {
    /*var selectedPersonas = [];
    tblPersonas.getRows().forEach(function(row) {
        if (row.getCell("select").getValue()) {
            selectedPersonas.push(row.getData());
        }
    });

    const cliente = document.getElementById('clientes').value;
    const cargo = document.getElementById('cargos').value;
    const foliosData = await getFoliosClienteCargo(cliente, cargo);

    var selectedFolios = foliosData.map(folio => ({
        nombre: folio.folio,
        codigo: folio.codigo
    }));

    getArchivosXPersonas(selectedPersonas, selectedFolios, 3);*/
    //Para las pruebas del generador de PDF
    //alert("Hola");
    
    try {
        const response = await axios.post(`${ VITE_URL_APP }/pdf_vacio`, {}, {
            responseType: 'blob'  // Muy importante para recibir archivos binarios (PDF)
        });

        // Crear URL para descargar/abrir el PDF
        const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }));

        // Abrir el PDF en una nueva pestaña
        window.open(url);

        // Opcional: liberar el objeto URL después de usar
        setTimeout(() => window.URL.revokeObjectURL(url), 10000);

    } catch (error) {
        console.error('Error al generar PDF vacío:', error);
    }
        
});


// Función para generar el PDF
function generarPDF(data) {
    return new Promise((resolve, reject) => {
        Swal.fire({
            title: 'Generando LEGAJO...',
            text: 'Por favor espera unos segundos',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        axios.post(`${ VITE_URL_APP }/generar-pdf`, {
            resultados: data
        }, {
            responseType: 'blob'
        })
        .then(response => {
            Swal.close();

            const blob = new Blob([response.data], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;

            const nombreArchivo = response.headers['x-nombre-archivo'] || 'reporte.pdf';
            link.download = nombreArchivo;

            link.click();
            resolve();
        })
        .catch(error => {
            Swal.fire('Error', 'No se pudo generar el PDF', 'error');
            console.error("Error al generar el PDF:", error);
            reject(error);
        });
    });
}



// Función para generar el PDF
function generarPDF2(data) {
    axios.post(`${ VITE_URL_APP }/generar-pdf2`, {
        resultados: data
    }, {
        responseType: 'blob' // Para recibir el PDF
    })
    .then(response => {
        const blob = new Blob([response.data], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'reporte.pdf';
        link.click();
    })
    .catch(error => {
        console.error("Error al generar el PDF:", error);
    });
}



//========================================== DATA CON AXIOS ==========================================//
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
// Función para obtener los folios
function getFolios(){
    axios.get(`${ VITE_URL_APP }/api/get-folios`)
    .then(response => {
        tblFolios.setData(response.data);
    })
    .catch(error => {
        console.error("Error al obtener los datos:", error);
    });
};



async function getArchivosXPersona_uno(codPersonal, selectedFolios, tipo) {

    if (tipo == 3) {
        //await generarPDF2([]);
        return;
    }

    try {
        const response = await axios.get(`${ VITE_URL_APP }/api/get-folios-persona_uno`, {
            params: {
                codPersona: codPersonal,
                folios: selectedFolios,
            }
        });

        // ⏳ Espera que se genere y descargue el PDF
        await generarPDF(response.data);

    } catch (error) {
        console.error("Error al obtener los folios o generar el PDF:", error);
        throw error;
    }
}




function getArchivosXPersonas(selectedPersonas, selectedFolios, tipo){
    //console.log(selectedPersonas);
    //console.log(selectedFolios);
    if(tipo == 3){
        generarPDF2([]);
        return;
    }
    axios.get(`${ VITE_URL_APP }/api/get-folios-personas`, {
        params: {
            personas: selectedPersonas,
            folios: selectedFolios,
        }
    })
    .then(function (response) {
        // console.log(response);
        // return;
        generarPDF(response.data);
    })
    .catch(function (error) {
        console.error("Error al obtener los folios por persona:", error);
    });
}

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

// Función para obtener las coincidencias
async function getFoliosClienteCargo(cliente, cargo) {
    try {
        const response = await axios.get(`${ VITE_URL_APP }/api/get-folios-cliente-cargo`, {
            params: {
                cliente: cliente,
                cargo: cargo
            }
        });

        // Devuelve los datos de los folios obtenidos
        return response.data;
    } catch (error) {
        console.error("Hubo un error:", error);
        return []; // Retorna un arreglo vacío en caso de error
    }
}

//================================ GUARDAR LOS DATOS POR AXIOS ================================//
document.getElementById('formFolioPersonal').addEventListener('submit', function(event) {
    event.preventDefault();
    var fechaEmision = document.getElementById('fecha_emision').value;
    var fechaCaducidad = document.getElementById('fecha_caducidad').value;
    var codigoPer = document.getElementById('codPersonal').value;
    var codFolio = document.getElementById('codFolio').value;

    if (fechaEmision /*&& fechaCaducidad*/) {
        // Enviar los datos al servidor usando Axios
        axios.post(`${ VITE_URL_APP }/api/save_folio_persona`, {
            fecha_emision: fechaEmision,
            fecha_caducidad: fechaCaducidad,
            codFolio: codFolio,
            codPersonal: codigoPer,
        })
        .then(function(response) {
            //console.log('Datos guardados:', response.data);
            document.getElementById('btn-modal-docs-close').click();
            getDocsObligatorios(codigoPer);
            document.getElementById('btnTraerFolios').click();
            limpiarModal();
        })
        .catch(function(error) {
            console.error('Error al guardar las fechas:', error);
        });
    }
});