import axios from 'axios';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';
import { jsPDF } from "jspdf";

axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

new TomSelect('#cargos');
new TomSelect('#clientes');


getPersonal();
getFolios();

// Tabla de Personas
const tblPersonas = new Tabulator("#tblPersonas", {
      height: "380px",
      layout: "fitColumns",
      responsiveLayout: "collapse",
      pagination: true,
      paginationSize: 10,
      rowHeader: { formatter: "responsiveCollapse", width: 30, minWidth: 30, hozAlign: "center", resizable: false, headerSort: false },
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
              "data": {
                  "empty": "No hay datos disponibles"
              }
          }
      },
      columns: [
          { title: "Código", field: "CODI_PERS", hozAlign: "center", widthGrow: 1 },
          { title: "Personal", field: "personal", hozAlign: "left", widthGrow: 3 },
          { title: "Nro DOC", field: "nroDoc", hozAlign: "center", widthGrow: 1.5 },
          { title: "Sucursal", field: "sucursal", hozAlign: "center", widthGrow: 1.2 },
          {
              title: "Tipo", field: "TIPOTRAB", hozAlign: "center", widthGrow: 1, headerSort: false,
              formatter: function (cell) {
                  const val = cell.getValue();
                  if (val === 'OPER') return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">OPER</span>`;
                  if (val === 'ADMIN') return `<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-700">ADMIN</span>`;
                  return val || '';
              }
          },
          {
              title: "", field: "select", hozAlign: "center", width: 40, headerSort: false,
              formatter: function (cell, formatterParams, onRendered) {
                  const checkbox = document.createElement("input");
                  checkbox.type = "checkbox";
                  checkbox.classList.add("form-checkbox", "rounded", "text-dark");
                  checkbox.checked = cell.getValue() || false;
                  checkbox.addEventListener("change", function () {
                      cell.setValue(checkbox.checked);
                      setTimeout(() => {
                          tblPersonas.clearFilter();
                          document.getElementById('buscarPer').value = "";
                          const allData = tblPersonas.getData();
                          const selected = allData.filter(row => row.select === true);
                          const unselected = allData.filter(row => row.select !== true);
                          tblPersonas.replaceData(selected.concat(unselected));
                      }, 300);
                  });
                  return checkbox;
              },
          }
      ],
  });


// Para activar todos los checkbox del listado de personas
document.getElementById('select-all-per').addEventListener('change', function () {
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
        { title: "Folios", field: "nombre", hozAlign: "left", width: '60%' },
        {
            title: "Tipo", field: "tipoFolio", hozAlign: "center", width: '32%',
            formatter: function (cell, formatterParams) {
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
        {
            title: "", field: "select", hozAlign: "center", width: '5%', headerSort: false,
            formatter: function (cell, formatterParams, onRendered) {
                const checkbox = document.createElement("input");
                checkbox.type = "checkbox";
                checkbox.classList.add("form-checkbox", "rounded", "text-dark");
                checkbox.checked = cell.getValue() || false; // Establece si está seleccionado según el valor de la celda
                checkbox.addEventListener("change", function () {
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
      layout: "fitColumns",
      responsiveLayout: "collapse",
      columns: [
          { title: "Documento", field: "documento", hozAlign: "left", widthGrow: 1 },
      ]
  });


document.getElementById('select-all-fol').addEventListener('change', function () {
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


function getPersonasSeleccionadas() {
      return tblPersonas.getRows()
          .filter(row => row.getCell("select").getValue())
          .map(row => row.getData());
  }

  function getFoliosSeleccionados() {
      return tblFolios.getRows()
          .filter(row => row.getCell("select").getValue())
          .map(row => row.getData());
  }

  function getModoGenerar() {
      return document.querySelector('input[name="modoGenerar"]:checked')?.value ?? 'separado';
  }

links.forEach(link => {
    link.addEventListener('click', function (e) {
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
 document.getElementById("legajo1").addEventListener("click", function () {
      document.getElementById('personasDiv').classList.remove("hidden");
      document.getElementById('foliosDiv').classList.remove("hidden");
      document.getElementById('legajosDiv').classList.add("hidden");
      document.getElementById('btnLeg2').classList.add("hidden");
      document.getElementById('btnLeg3').classList.add("hidden");
      document.getElementById('btnLeg1').classList.remove("hidden");
      document.getElementById('modoGenerarDiv').classList.remove("hidden");
      tblPersonas.redraw();
  });

  document.getElementById("legajo2").addEventListener("click", function () {
      document.getElementById('personasDiv').classList.remove("hidden");
      document.getElementById('legajosDiv').classList.remove("hidden");
      document.getElementById('foliosDiv').classList.add("hidden");
      document.getElementById('btnLeg1').classList.add("hidden");
      document.getElementById('btnLeg3').classList.add("hidden");
      document.getElementById('btnLeg2').classList.remove("hidden");
      document.getElementById('modoGenerarDiv').classList.remove("hidden");
      tblPersonas.redraw();
  });


// document.getElementById("legajo3").addEventListener("click", function () {
//     document.getElementById('personasDiv').classList.add("hidden");
//     document.getElementById('legajosDiv').classList.add("hidden");
//     document.getElementById('foliosDiv').classList.add("hidden");
//     document.getElementById('btnLeg1').classList.add("hidden");
//     document.getElementById('btnLeg2').classList.add("hidden");
//     document.getElementById('btnLeg3').classList.remove("hidden");

// });
// document.getElementById("legajo4").addEventListener("click", function () {
//     document.getElementById('personasDiv').classList.add("hidden");
//     document.getElementById('legajosDiv').classList.add("hidden");
//     document.getElementById('foliosDiv').classList.add("hidden");
//     document.getElementById('btnLeg1').classList.add("hidden");
//     document.getElementById('btnLeg2').classList.add("hidden");

// });

// Llenado de la tabla de legajos
document.addEventListener('DOMContentLoaded', function () {
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
    tblPersonas.getRows().forEach(function (row) {
        if (row.getCell("select").getValue()) {
            var codiPers = row.getData().CODI_PERS;

            // Verifica si el valor de CODI_PERS no está vacío
            if (codiPers) {
                selectedPersona = codiPers;
            }
        }
    });

    const codigoPer = selectedPersona;
    axios.get(`${VITE_URL_APP}/api/get-legajos`, {
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
   function aplicarFiltrosPersonal() {
      const filtros = [];
      const sucursalEl = document.getElementById('sucursal');
      if (sucursalEl.selectedIndex > 0 && sucursalEl.value !== 'TODOS') {
          filtros.push({ field: 'sucursal', type: '=', value: sucursalEl.value });
      }
      const tipo = document.querySelector('input[name="tipoPerFiltro"]:checked')?.value;
      if (tipo && tipo !== 'TODOS') filtros.push({ field: 'TIPOTRAB', type: '=', value: tipo });
      const buscar = document.getElementById('buscarPer').value.toLowerCase().trim();
      if (buscar) filtros.push([
          { field: 'CODI_PERS', type: 'like', value: buscar },
          { field: 'personal', type: 'like', value: buscar },
          { field: 'nroDoc', type: 'like', value: buscar },
          { field: 'sucursal', type: 'like', value: buscar },
      ]);
      filtros.length > 0 ? tblPersonas.setFilter(filtros) : tblPersonas.clearFilter();
  }

  document.getElementById('sucursal').addEventListener('change', aplicarFiltrosPersonal);
  document.querySelectorAll('input[name="tipoPerFiltro"]').forEach(r => r.addEventListener('change', aplicarFiltrosPersonal));

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
 document.getElementById("buscarPer").addEventListener("keyup", aplicarFiltrosPersonal);

document.getElementById("buscarFol").addEventListener("keyup", function () {
    let valor = this.value.toLowerCase().trim();
    tblFolios.setFilter([
        [
            { field: "nombre", type: 'like', value: valor },
            { field: "periodo", type: 'like', value: valor },
            { field: "tipoFolio", type: 'like', value: valor },
        ]
    ]);
});

// Función para el BOTON GENERAR PDF
  document.getElementById("btnLeg1").addEventListener("click", async function () {
      const personas = getPersonasSeleccionadas();
      const folios   = getFoliosSeleccionados();

      if (personas.length === 0) {
          Swal.fire('Atención', 'Seleccione al menos una persona.', 'warning');
          return;
      }
      if (folios.length === 0) {
          Swal.fire('Atención', 'Seleccione al menos un folio.', 'warning');
          return;
      }

      if (getModoGenerar() === 'unico') {
          await getArchivosXPersonas(personas, folios);
      } else {
          for (const persona of personas) {
              try {
                  await getArchivosXPersona_uno(persona.CODI_PERS, folios, 1);
              } catch (e) {
                  console.warn("Falló para persona:", persona.CODI_PERS);
              }
          }
      }
  });


 document.getElementById("btnLeg2").addEventListener("click", async function () {
      const personas = getPersonasSeleccionadas();

      if (personas.length === 0) {
          Swal.fire('Atención', 'Seleccione al menos una persona.', 'warning');
          return;
      }

      const cliente = document.getElementById('clientes').value;
      const cargo   = document.getElementById('cargos').value;

      if (!cliente || !cargo) {
          Swal.fire('Atención', 'Seleccione cliente y cargo.', 'warning');
          return;
      }

      const foliosData = await getFoliosClienteCargo(cliente, cargo);
      const folios = foliosData.map(f => ({ nombre: f.folio, codigo: f.codigo }));

      if (folios.length === 0) {
          Swal.fire('Atención', 'No hay folios configurados para ese cliente y cargo.', 'warning');
          return;
      }

      if (getModoGenerar() === 'unico') {
          await getArchivosXPersonas(personas, folios);
      } else {
          for (const persona of personas) {
              try {
                  await getArchivosXPersona_uno(persona.CODI_PERS, folios, 1);
              } catch (e) {
                  console.warn("Falló para persona:", persona.CODI_PERS);
              }
          }
      }
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
document.getElementById("btnLeg3").addEventListener("click", async function () {
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
        const response = await axios.post(`${VITE_URL_APP}/pdf_vacio`, {}, {
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
        axios.post(`${VITE_URL_APP}/generar-pdf`, {
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
    axios.post(`${VITE_URL_APP}/generar-pdf2`, {
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
function getPersonal() {
    axios.get(`${VITE_URL_APP}/api/get-personal`, {
        params: {
            pagination: 'off'
        }
    })
        .then(response => {
            const datosTabla = response.data;
            tblPersonas.setData(datosTabla);
        })
        .catch(error => {
            console.error("Hubo un error:", error);
        });
}
// Función para obtener los folios
function getFolios() {
    axios.get(`${VITE_URL_APP}/api/get-folios`)
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
        const response = await axios.get(`${VITE_URL_APP}/api/get-folios-persona_uno`, {
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




  async function getArchivosXPersonas(selectedPersonas, selectedFolios) {
      try {
          const response = await axios.get(`${VITE_URL_APP}/api/get-folios-personas`, {
              params: {
                  personas: selectedPersonas,
                  folios: selectedFolios,
              }
          });
          await generarPDF(response.data);
      } catch (error) {
          Swal.fire('Error', 'No se pudo obtener los archivos.', 'error');
          console.error("Error en getArchivosXPersonas:", error);
      }
  }

// Función para obtener los folios por persona
function getDocsObligatorios(codigo) {
    axios.get(`${VITE_URL_APP}/api/get-documentos/${codigo}`)
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
        const response = await axios.get(`${VITE_URL_APP}/api/get-folios-cliente-cargo`, {
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
// document.getElementById('formFolioPersonal').addEventListener('submit', function (event) {
//     event.preventDefault();
//     var fechaEmision = document.getElementById('fecha_emision').value;
//     var fechaCaducidad = document.getElementById('fecha_caducidad').value;
//     var codigoPer = document.getElementById('codPersonal').value;
//     var codFolio = document.getElementById('codFolio').value;

//     if (fechaEmision /*&& fechaCaducidad*/) {
//         // Enviar los datos al servidor usando Axios
//         axios.post(`${VITE_URL_APP}/api/save_folio_persona`, {
//             fecha_emision: fechaEmision,
//             fecha_caducidad: fechaCaducidad,
//             codFolio: codFolio,
//             codPersonal: codigoPer,
//         })
//             .then(function (response) {
//                 //console.log('Datos guardados:', response.data);
//                 document.getElementById('btn-modal-docs-close').click();
//                 getDocsObligatorios(codigoPer);
//                 document.getElementById('btnTraerFolios').click();
//                 limpiarModal();
//             })
//             .catch(function (error) {
//                 console.error('Error al guardar las fechas:', error);
//             });
//     }
// });