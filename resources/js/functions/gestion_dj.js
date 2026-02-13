
import axios from 'axios';
import Swal from 'sweetalert2';
import {TabulatorFull as Tabulator} from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

import Tagify from '@yaireo/tagify';
import '@yaireo/tagify/dist/tagify.css';


document.addEventListener('DOMContentLoaded', function () {

    getPersonal();

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
        columns:[
            {title:"N°", formatter:"rownum", hozAlign:"center", width:60},

            {
                title:"Nombres",
                field:"nombres",
                hozAlign:"left",
                widthGrow:3,
                formatter: function(cell){
                    let data = cell.getData();
                    return `${data.nombres ?? ''} ${data.apellido1 ?? ''} ${data.apellido2 ?? ''} `.trim();
                }
            },

            {title:"DNI", field:"dni", hozAlign:"center", widthGrow:2},

            {
                title:"Acciones",
                field:"acciones",
                hozAlign:"center",
                headerSort:false,
                widthGrow:1,
                formatter: function(cell){
                    return `<button type="button" class="btn rounded-full form-btn bg-success/25 text-success hover:bg-success hover:text-white">Formulario</button>`;
                },
                cellClick: function(e, cell) {

                    if (e.target.classList.contains('form-btn')) {
                        var registro = cell.getRow().getData();

                        abrirFormulario(registro);
                    }
                }
            },
        ],
        layout:"fitColumns",

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

    document.getElementById("buscarPersonal").addEventListener("keyup", function () {
        let valor = this.value.toLowerCase().trim();
        
        tblPersonas.setFilter([
            [
                { field: "nombres", type: "like", value: valor },
                { field: "dni", type: "like", value: valor },
            ]
        ]);

        tblPersonas._ultimoFiltro = valor;

        setTimeout(() => resaltarTexto(valor), 10);
    });

    document.getElementById('btnNuevaDJ').addEventListener('click', function() {
        abrirFormulario();
    });

    document.addEventListener('click', function (event) {
        const modal = document.getElementById('formModal');
        const contenedor = modal.querySelector('.bg-white');

        if (event.target.closest('#btnNuevaDJ')) return;
        
        if (!modal.classList.contains('hidden')) { 
            if (!contenedor.contains(event.target) && !event.target.classList.contains('form-btn')) {
                cerrarFormulario();
            }
        }
    });

    document.getElementById('cerrarModal').addEventListener('click', function() {
        cerrarFormulario();
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

    // Función para obtener el listados de personas
    function getPersonal(){
        axios.get(`${ VITE_URL_APP }/api/get-postulantes`)
        .then(response => {
            const datosTabla = response.data;
            tblPersonas.setData(datosTabla);

        })
        .catch(error => {
            console.error("Hubo un error:", error);
        });
    }

    // Gestión del formulario de familiares
    const container = document.getElementById('familyContainer');
    const addBtn = document.getElementById('addFamilyMember');

    function makeFamilyRow() {
        return `
        <div class="family-row grid grid-cols-1 md:grid-cols-3 gap-4 p-4 border rounded-lg relative" data-familia-row>
            <div>
            <label class="text-sm font-medium inline-block mb-2">Parentesco</label>
            <select name="parentesco[]" class="form-select w-full">
                <option value="">Seleccionar</option>
                <option value="PADRE">Padre</option>
                <option value="MADRE">Madre</option>
                <option value="ESPOSO">Esposo</option>
                <option value="ESPOSA">Esposa</option>
                <option value="HIJO">Hijo</option>
                <option value="HIJA">Hija</option>
                <option value="HERMANO">Hermano</option>
                <option value="HERMANA">Hermana</option>
                <option value="ABUELO">Abuelo</option>
                <option value="ABUELA">Abuela</option>
            </select>
            </div>
            <div>
            <label class="text-sm font-medium inline-block mb-2">Apellidos y Nombres</label>
            <input type="text" name="apellidosNombres[]" class="form-input w-full" placeholder="Apellidos y nombres completos">
            </div>
            <div class="flex gap-2 items-end">
            <div class="flex-1">
                <label class="text-sm font-medium inline-block mb-2">Fecha Nacimiento</label>
                <input type="date" name="fechaNacimiento[]" class="form-input w-full">
            </div>
            <button type="button" class="remove-family self-end px-3 py-1 bg-red-100 text-red-600 rounded hover:bg-red-200">
                Eliminar
            </button>
            </div>
        </div>
        `;
    }

    // Agregar fila
    if (addBtn) {
        addBtn.addEventListener('click', function (e) {
        e.preventDefault();
        container.insertAdjacentHTML('beforeend', makeFamilyRow());
        });
    }

    // Eliminar fila con delegación
    container.addEventListener('click', function (e) {
        const btn = e.target.closest('button.remove-family');
        if (!btn) return;

        e.preventDefault();
        e.stopPropagation(); // evita cierre del modal

        const row = btn.closest('.family-row');
        if (row) row.remove();
    });

    window.abrirFormulario = function (data = null) {

        limpiarFormulario();

        if(data){
            document.getElementById("cod_postulante").value = data.id;
            document.getElementById("nombres_apellidos").value = data.nombres + ' ' + data.apellido1 + ' ' + data.apellido2;
            document.getElementById("dni").value = data.dni ?? '';
            document.getElementById("fecha_nacimiento").value = data.fecha_nacimiento ?? '';

            //Seleccionar departamento
            const departamentoSelect = document.getElementById("departamento_actual");
            const provinciaSelect = document.getElementById("provincia_actual");
            const distritoSelect = document.getElementById("distrito_actual");

            if (data.departamento) {
                departamentoSelect.value = data.departamento;

                departamentoSelect.dispatchEvent(new Event("change"));

                setTimeout(() => {
                    if (data.provincia) {
                        provinciaSelect.value = data.provincia;
                        provinciaSelect.dispatchEvent(new Event("change"));

                        setTimeout(() => {
                            if (data.distrito) {
                                distritoSelect.value = data.distrito;
                            }
                        }, 150);
                    }
                }, 150);
            }

            document.getElementById("celular").value = data.celular ?? '';
            document.getElementById("correo").value = data.correo ?? '';
            document.getElementById("grado_instruccion").value = data.grado_instruccion ?? '';

            document.getElementById("curso_sucamec").value = (data.sucamec && data.sucamec.toUpperCase() === "SI") ? "SI" : "NO";

            const inputLicencia = document.getElementById("licencia_arma");
            const tagify = new Tagify(inputLicencia, {
                maxTags: 2
            });

            let licencias = data.licencia_arma;

            tagify.removeAllTags();

            if (typeof licencias === "string") {
                try {
                    licencias = JSON.parse(licencias);
                } catch(e) {
                    licencias = [licencias]; 
                }
            }

            if (licencias && Array.isArray(licencias)) {
                tagify.addTags(licencias);
            }



        }

        inputFoto.value = '';
        preview.src = '';
        preview.classList.add("hidden");
        container.innerHTML = '';
        container.insertAdjacentHTML('beforeend', makeFamilyRow());
        document.getElementById('formModal').classList.remove('hidden');
    };

    window.cerrarFormulario = function () {
        document.getElementById('formModal').classList.add('hidden');
    };


    function limpiarFormulario() {

        console.log("LIMPIAR FORMULARIO");

        const form = document.getElementById('formDatos');
        form.reset();

        // const inputFoto = document.getElementById("inputFoto");
        // const previewFoto = document.getElementById("previewFoto");

        // const departamentoSelect = document.getElementById("departamento-actual");
        // const provinciaSelect = document.getElementById("provincia-actual");
        // const distritoSelect = document.getElementById("distrito-actual");

        // const departamentoSelectDni = document.getElementById("departamento-dni");
        // const provinciaSelectDni = document.getElementById("provincia-dni");
        // const distritoSelectDni = document.getElementById("distrito-dni");

        // departamentoSelect.innerHTML = '<option value="">Seleccionar</option>';
        // provinciaSelect.innerHTML = '<option value="">Seleccionar</option>';
        // distritoSelect.innerHTML = '<option value="">Seleccionar</option>';

        // departamentoSelectDni.innerHTML = '<option value="">Seleccionar</option>';
        // provinciaSelectDni.innerHTML = '<option value="">Seleccionar</option>';
        // distritoSelectDni.innerHTML = '<option value="">Seleccionar</option>';

        
        // previewFoto.src = '';
        // previewFoto.classList.add("hidden");


    }


    const inputFoto = document.getElementById("inputFoto");
    const preview = document.getElementById("previewFoto");
    const placeholder = document.getElementById("placeholderFoto");
    const btnSubir = document.getElementById("btnSubirFoto");
    const btnEliminar = document.getElementById("btnEliminarFoto");

    const cursoSucamec = document.getElementById("curso_sucamec");
    const institucionContainer = document.getElementById("institucion_container");
    const institucionInput = document.getElementById("institucion_laboral");

    cursoSucamec.addEventListener("change", () => {
        if (cursoSucamec.value === "SI") {
        institucionContainer.classList.remove("hidden");
        } else {
        institucionContainer.classList.add("hidden");
        institucionInput.value = "";
        }
    });


    // Abrir selector al dar click en Subir
    btnSubir.addEventListener("click", () => {
        inputFoto.click();
    });

    // Cuando selecciona una foto
    inputFoto.addEventListener("change", () => {
        const file = inputFoto.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (e) => {
            preview.src = e.target.result;
            preview.classList.remove("hidden");
            placeholder.classList.add("hidden");
            btnEliminar.classList.remove("hidden"); // Mostrar "Eliminar"
            };
            reader.readAsDataURL(file);
        }
    });

    // Eliminar foto y restaurar placeholder
    btnEliminar.addEventListener("click", () => {
        inputFoto.value = ""; // limpia input
        preview.src = "";
        preview.classList.add("hidden");
        placeholder.classList.remove("hidden");
        btnEliminar.classList.add("hidden"); // ocultar botón eliminar
    });


    const departamentoSelect = document.getElementById("departamento_actual");
    const provinciaSelect = document.getElementById("provincia_actual");
    const distritoSelect = document.getElementById("distrito_actual");

    const departamentoSelectDni = document.getElementById("departamento_dni");
    const provinciaSelectDni = document.getElementById("provincia_dni");
    const distritoSelectDni = document.getElementById("distrito_dni");

    const API_BASE = `${VITE_URL_APP}/api/ubicacion`;

    // Cargar departamentos al inicio
    axios.get(`${API_BASE}/departamentos`)
        .then(response => {
            response.data.forEach(dep => {
                let option1 = new Option(dep.depa_descripcion, dep.depa_codigo);
                let option2 = new Option(dep.depa_descripcion, dep.depa_codigo);
                departamentoSelect.add(option1);
                departamentoSelectDni.add(option2);
            });
        })
        .catch(error => {
            console.error("Error cargando departamentos:", error);
        });

    departamentoSelect.addEventListener("change", function () {
        const departamentoId = this.value;
        provinciaSelect.innerHTML = '<option value="">Seleccionar</option>';
        distritoSelect.innerHTML = '<option value="">Seleccionar</option>';

        if (departamentoId) {
            axios.get(`${API_BASE}/provincias/${departamentoId}`)
                .then(response => {
                    response.data.forEach(prov => {
                        let option = new Option(prov.provi_descripcion, prov.provi_codigo);
                        provinciaSelect.add(option);
                    });
                })
                .catch(error => {
                    console.error("Error cargando provincias:", error);
                });
        }
    });

    provinciaSelect.addEventListener("change", function () {
        const provinciaId = this.value;
        distritoSelect.innerHTML = '<option value="">Seleccionar</option>';

        if (provinciaId) {
            axios.get(`${API_BASE}/distritos/${provinciaId}`)
                .then(response => {
                    response.data.forEach(dist => {
                        let option = new Option(dist.dist_descripcion, dist.dist_codigo);
                        distritoSelect.add(option);
                    });
                })
                .catch(error => {
                    console.error("Error cargando distritos:", error);
                });
        }
    });

    departamentoSelectDni.addEventListener("change", function () {
        const departamentoId = this.value;
        provinciaSelectDni.innerHTML = '<option value="">Seleccionar</option>';
        distritoSelectDni.innerHTML = '<option value="">Seleccionar</option>';

        if (departamentoId) {
            axios.get(`${API_BASE}/provincias/${departamentoId}`)
                .then(response => {
                    response.data.forEach(prov => {
                        let option = new Option(prov.provi_descripcion, prov.provi_codigo);
                        provinciaSelectDni.add(option);
                    });
                })
                .catch(error => {
                    console.error("Error cargando provincias:", error);
                });
        }
    });

    provinciaSelectDni.addEventListener("change", function () {
        const provinciaId = this.value;
        distritoSelectDni.innerHTML = '<option value="">Seleccionar</option>';

        if (provinciaId) {
            axios.get(`${API_BASE}/distritos/${provinciaId}`)
                .then(response => {
                    response.data.forEach(dist => {
                        let option = new Option(dist.dist_descripcion, dist.dist_codigo);
                        distritoSelectDni.add(option);
                    });
                })
                .catch(error => {
                    console.error("Error cargando distritos:", error);
                });
        }
    });


    //document.getElementById("btnPrevisualizar")?.addEventListener("click", generarDeclaracionJuradaPDF)

    document.getElementById("btnPrevisualizar")?.addEventListener("click", function (e) {
        e.preventDefault();
        generarDeclaracionJuradaPDF();
    });

    document.getElementById("page-size").addEventListener("change", function () {
        const size = parseInt(this.value);
        tblPersonas.setPageSize(size);
    });


    async function generarDeclaracionJuradaPDF() {
        const { jsPDF } = window.jspdf
        const pdf = new jsPDF({ unit: "mm", format: "a4", compress: true })

        // ---------- Parámetros de estilo y layout ----------
        const pageWidth = 210
        const pageHeight = 297
        const marginLeft = 10
        const marginRight = 10
        const marginTop = 10
        const marginBottom = 12
        const boxWidth = pageWidth - marginLeft - marginRight
        const boxX = marginLeft
        let y = marginTop

        const colors = {
            headerText: [0, 0, 0],
            sectionBg: [40, 40, 40],
            sectionText: [255, 255, 255],
            labelBg: [220, 220, 220],
            labelText: [0, 0, 0],
            inputText: [0, 0, 0],
            borderColor: [80, 80, 80],
        }

        function checkPageBreak(heightNeeded) {
            if (y + heightNeeded > pageHeight - marginBottom) {
            pdf.addPage()
            y = marginTop
            return true
            }
            return false
        }

        function drawField(label, value, x, width, fieldY, inputHeight = 6, labelRatio = 0.35) {
            const labelWidth = width * labelRatio
            const valueWidth = width * (1 - labelRatio)
            const labelPadding = 1

            // Label box (gray background)
            pdf.setFillColor(...colors.labelBg)
            pdf.rect(x, fieldY, labelWidth, inputHeight, "F")

            // Label border
            pdf.setDrawColor(...colors.borderColor)
            pdf.setLineWidth(0.2)
            pdf.rect(x, fieldY, labelWidth, inputHeight)

            // Label text - SIEMPRE EN UNA LÍNEA, sin maxWidth
            pdf.setFontSize(6.5)
            pdf.setTextColor(...colors.labelText)
            pdf.setFont(undefined, "bold")
            pdf.text(label, x + labelPadding, fieldY + inputHeight / 2 + 1, {
            align: "left",
            })

            // Value box (white background)
            pdf.setFillColor(255, 255, 255)
            pdf.rect(x + labelWidth, fieldY, valueWidth, inputHeight, "F")

            // Value border
            pdf.setDrawColor(...colors.borderColor)
            pdf.setLineWidth(0.2)
            pdf.rect(x + labelWidth, fieldY, valueWidth, inputHeight)

            // Value text
            pdf.setFontSize(6.5)
            pdf.setTextColor(...colors.inputText)
            pdf.setFont(undefined, "normal")
            const textY = fieldY + inputHeight / 2 + 1
            pdf.text(String(value).substring(0, 50), x + labelWidth + 1, textY, { 
            maxWidth: valueWidth - 2 
            })
        }

        function drawFieldsInRow(fields, startY, fieldHeight = 6) {
            let currentX = boxX
            fields.forEach((field) => {
            const labelRatio = field.labelRatio || 0.35
            drawField(field.label, field.value, currentX, field.width, startY, fieldHeight, labelRatio)
            currentX += field.width
            })
        }

        function drawSectionTitle(title, yPos) {
            pdf.setFillColor(...colors.sectionBg)
            pdf.rect(boxX, yPos, boxWidth, 6, "F")
            
            pdf.setDrawColor(...colors.borderColor)
            pdf.setLineWidth(0.2)
            pdf.rect(boxX, yPos, boxWidth, 6)

            pdf.setFontSize(8)
            pdf.setFont(undefined, "bold")
            pdf.setTextColor(...colors.sectionText)
            pdf.text(title, boxX + 2, yPos + 4)
        }

        function formatDateToDMY(fechaStr) {
            if (!fechaStr) return "";
            const partes = fechaStr.split("-");
            return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : fechaStr;
        }



        // ========== ENCABEZADO CON TABLA DE 3 COLUMNAS ==========
        const headerY = y
        const headerHeight = 18
        const logoWidth = boxWidth * 0.20   
        const tituloWidth = boxWidth * 0.65  
        const codigoWidth = boxWidth * 0.15 

        const logoX = boxX
        pdf.rect(logoX, headerY, logoWidth, headerHeight, "F")
        pdf.setDrawColor(0, 0, 0)
        pdf.setLineWidth(0.3)
        pdf.rect(logoX, headerY, logoWidth, headerHeight)
        await drawLogo();

        // const logoX = boxX;
        // //pdf.setFillColor(20, 30, 70); // Fondo azul oscuro para el logo
        // pdf.rect(logoX, headerY, logoWidth, headerHeight, "F");
        // pdf.setDrawColor(0, 0, 0);
        // pdf.setLineWidth(0.3);
        // pdf.rect(logoX, headerY, logoWidth, headerHeight);

        // // Verificar que la variable global esté definida
        // if (window.logoUrl) {
        //     fetch(window.logoUrl)
        //         .then(response => {

        //             console.log("Cargando logo desde:", window.logoUrl);

        //             if (!response.ok) throw new Error("No se pudo cargar la imagen");
        //             return response.blob();
        //         })
        //         .then(blob => {
        //             const reader = new FileReader();
        //             reader.onload = (e) => {
        //                 try {
        //                     pdf.addImage(
        //                         e.target.result,
        //                         "PNG",
        //                         logoX + 2,
        //                         headerY + 2,
        //                         logoWidth - 4,
        //                         headerHeight - 4
        //                     );
        //                 } catch (err) {
        //                     console.error("Error al agregar logo:", err);
        //                     drawFallbackLogo();
        //                 }
        //             };
        //             reader.readAsDataURL(blob);
        //         })
        //         .catch(err => {
        //             console.error("Error al cargar el logo:", err);
        //             drawFallbackLogo();
        //         });
        // } else {
        //     drawFallbackLogo();
        // }


        function drawFallbackLogo() {
            pdf.setFillColor(20, 30, 70);
            pdf.rect(logoX, headerY, logoWidth, headerHeight, "F");
            pdf.setFontSize(9);
            pdf.setTextColor(255, 255, 255);
            pdf.setFont(undefined, "bold");
            pdf.text("SOLMAR", logoX + logoWidth / 2, headerY + headerHeight / 2, { align: "center" });
        }

        async function drawLogo() {
            if (!window.logoUrl) {
                drawFallbackLogo();
                return;
            }

            try {
                console.log("Cargando logo desde:", window.logoUrl);
                const response = await fetch(window.logoUrl);
                if (!response.ok) throw new Error("No se pudo cargar la imagen");
                const blob = await response.blob();

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        pdf.addImage(
                            e.target.result,
                            "PNG",
                            logoX + 2,
                            headerY + 2,
                            logoWidth - 4,
                            headerHeight - 4
                        );
                    } catch (err) {
                        console.error("Error al agregar logo:", err);
                        drawFallbackLogo();
                    }
                };
                reader.readAsDataURL(blob);
            } catch (err) {
                console.error("Error al cargar el logo:", err);
                drawFallbackLogo();
            }
        }

        // COLUMNA 2: TÍTULOS
        const tituloX = logoX + logoWidth
        pdf.setFillColor(255, 255, 255)
        pdf.rect(tituloX, headerY, tituloWidth, headerHeight, "F")
        pdf.rect(tituloX, headerY, tituloWidth, headerHeight)

        pdf.setFontSize(9)
        pdf.setTextColor(200, 0, 0)  // Rojo para el primer título
        pdf.setFont(undefined, "bold")
        pdf.text("SISTEMA INTEGRADO SOLMAR – SISOLMAR", tituloX + tituloWidth / 2, headerY + 7, { align: "center" })

        pdf.setFontSize(10)
        pdf.setTextColor(0, 0, 0)
        pdf.text("DECLARACION JURADA DEL TRABAJADOR", tituloX + tituloWidth / 2, headerY + 13, { align: "center" })

        // COLUMNA 3: CÓDIGO RH 01
        const codigoX = tituloX + tituloWidth
        pdf.setFillColor(255, 255, 255)
        pdf.rect(codigoX, headerY, codigoWidth, headerHeight, "F")
        pdf.rect(codigoX, headerY, codigoWidth, headerHeight)

        pdf.setFontSize(14)
        pdf.setTextColor(0, 0, 0)
        pdf.setFont(undefined, "bold")
        pdf.text("RH 01", codigoX + codigoWidth / 2, headerY + headerHeight / 2 + 2, { align: "center" })

        y = headerY + headerHeight + 4

        // ========== DECLARACIÓN INICIAL CON BORDE ==========
        const declY = y
        pdf.setFontSize(7)
        pdf.setTextColor(...colors.inputText)
        pdf.setFont(undefined, "normal")

        const nombres = (document.getElementById("nombres_apellidos")?.value || "").toUpperCase()
        const dni = document.getElementById("dni")?.value || ""

        const declaracionText = `Yo, ${nombres}, identificado con DNI ${dni}, declaro bajo juramento que los datos personales, laborales y familiares que consigno, así como las declaraciones de aceptación que realizo en este documento, son correctos, por lo que asumo la responsabilidad por su veracidad, cumplimiento y actualización, estando conforme con esta declaración jurada.`

        const lineasDeclaracion = pdf.splitTextToSize(declaracionText, boxWidth - 4)

        // Calcular altura del texto
        const declHeight = lineasDeclaracion.length * 3 + 2

        // Dibujar recuadro alrededor de la declaración
        pdf.setFillColor(255, 255, 255)
        pdf.rect(boxX, declY, boxWidth, declHeight, "F")
        pdf.setDrawColor(0, 0, 0)
        pdf.setLineWidth(0.3)
        pdf.rect(boxX, declY, boxWidth, declHeight)

        // Texto de la declaración
        let declTextY = declY + 3
        lineasDeclaracion.forEach((linea) => {
        pdf.text(linea, boxX + 2, declTextY)
        declTextY += 3
        })

        y = declY + declHeight + 4


        // ========== MIS DATOS PERSONALES ==========
        checkPageBreak(100)
        drawSectionTitle("MIS DATOS PERSONALES", y)
        y += 7

        let fieldY = y
        const col1W = boxWidth * 0.88  // 88% para los campos
        const col2W = boxWidth * 0.12  // 12% para la foto (mucho más delgada)

        // Solo el campo "Nombres y Apellidos" en la primera fila
        drawField("Nombres y Apellidos", nombres, boxX, col1W, fieldY, 6, 0.25)

        // Recuadro de foto - SE EXTIENDE HASTA LA FILA DE SISTEMA PREVISIONAL
        // La foto tiene 6 filas de altura (desde Nombres hasta Sistema Previsional inclusive)
        const fotoHeight = 6 * 6 // 6 filas × 6mm cada una
        pdf.setFillColor(245, 245, 245)
        pdf.rect(boxX + col1W, fieldY, col2W, fotoHeight, "F")
        pdf.setDrawColor(...colors.borderColor)
        pdf.setLineWidth(0.2)
        pdf.rect(boxX + col1W, fieldY, col2W, fotoHeight)

        const fotoInput = document.getElementById("foto")
        if (fotoInput && fotoInput.files && fotoInput.files[0]) {
            const reader = new FileReader()
            reader.onload = (e) => {
            try {
                pdf.addImage(e.target.result, "JPEG", boxX + col1W + 0.5, fieldY + 0.5, col2W - 1, fotoHeight - 1)
            } catch (err) {
                console.log("Error al agregar foto:", err)
            }
            }
            reader.readAsDataURL(fotoInput.files[0])
        } else {
            pdf.setFontSize(8)
            pdf.setTextColor(120)
            pdf.setFont(undefined, "bold")
            pdf.text("FOTO", boxX + col1W + col2W / 2, fieldY + fotoHeight / 2, { align: "center" })
        }

        y += 6

        // FILA 2: DNI, Caduca, Estado Civil, Sexo
        fieldY = y
        const col1 = col1W * 0.25
        const col2 = col1W * 0.25
        const col3 = col1W * 0.25
        const col4 = col1W * 0.25

        drawFieldsInRow(
            [
            { label: "DNI", value: dni, width: col1, labelRatio: 0.25 },
            { label: "Caduca", value: formatDateToDMY(document.getElementById("caduca")?.value), width: col2 },
            { label: "Estado Civil", value: document.getElementById("estado_civil")?.value || "", width: col3, labelRatio: 0.45 },
            { label: "Sexo", value: document.getElementById("sexo")?.value || "", width: col4 },
            ],
            fieldY,
            6,
        )

        y += 6

        // FILA 3: Fecha Nacimiento, Ciudad, Sabe nadar
        fieldY = y
        const col1_3 = col1W * 0.3375
        const col2_3 = col1W * 0.275
        const col3_3 = col1W * 0.3875

        drawFieldsInRow(
            [
            { label: "Fecha Nacimiento", value: formatDateToDMY(document.getElementById("fecha_nacimiento")?.value), width: col1_3, labelRatio: 0.50 },
            { label: "Ciudad", value: document.getElementById("ciudad")?.value || "", width: col2_3 },
            { label: "Sabe nadar", value: document.getElementById("sabe_nadar")?.value || "", width: col3_3, labelRatio: 0.58 },
            ],
            fieldY,
            6,
        )

        y += 6

        // FILA 4: Tipo Sangre, Peso, Talla, Celular
        fieldY = y
        drawFieldsInRow(
            [
            { label: "Tipo Sangre", value: document.getElementById("tipo_sangre")?.value || "", width: col1, labelRatio: 0.6750 },
            { label: "Peso (Kg.)", value: document.getElementById("peso")?.value || "", width: col2 },
            { label: "Talla (Mt.)", value: document.getElementById("talla")?.value || "", width: col3, labelRatio: 0.45 },
            { label: "Celular", value: document.getElementById("celular")?.value || "", width: col4 },
            ],
            fieldY,
            6,
        )

        y += 6

        // FILA 5: Correo electrónico, WhatsApp
        fieldY = y
        const col1_2 = col1W * 0.75
        const col2_2 = col1W * 0.25

        drawFieldsInRow(
            [
            { label: "Correo electrónico", value: document.getElementById("correo")?.value || "", width: col1_2, labelRatio: 0.225 },
            { label: "WhatsApp", value: document.getElementById("whatsapp")?.value || "", width: col2_2, labelRatio: 0.35 },
            ],
            fieldY,
            6,
        )

        y += 6

        // FILA 6: Sistema Previsional, ESSALUD, Vida, Pensionista (última fila con foto)
        fieldY = y
        drawFieldsInRow(
            [
            { label: "Sistema Previsional", value: document.getElementById("sistema_previsional")?.value || "", width: col1W * 0.50, labelRatio: 0.3375 },
            { label: "ESSALUD Vida", value: document.getElementById("essalud")?.value || "", width: col1W * 0.25, labelRatio: 0.45 },
            { label: "Pensionista", value: document.getElementById("pensionista")?.value || "", width: col1W * 0.25, labelRatio: 0.35 },
            ],
            fieldY,
            6,
        )

        y += 6

        // AHORA SÍ CONTINÚAN LAS FILAS COMPLETAS (sin foto al costado)
        // FILA 7: Grado de instrucción, Institución, Carrera, Año de egreso
        fieldY = y
        const fullCol1 = boxWidth * 0.25
        const fullCol2 = boxWidth * 0.25
        const fullCol3 = boxWidth * 0.32
        const fullCol4 = boxWidth * 0.18  // Ancho normal, no tan delgado

        drawFieldsInRow(
            [
            { label: "Grado de instrucción", value: document.getElementById("grado_instruccion")?.value || "", width: fullCol1, labelRatio: 0.5950 },
            { label: "Institución", value: document.getElementById("institucion")?.value || "", width: fullCol2, labelRatio: 0.42 },
            { label: "Carrera", value: document.getElementById("carrera")?.value || "", width: fullCol3, labelRatio: 0.28 },
            { label: "Año de egreso", value: document.getElementById("anio_egreso")?.value || "", width: fullCol4, labelRatio: 0.52 },
            ],
            fieldY,
            6,
        )

        y += 6

        // FILA 8: Embargos, Consumo de sustancias
        fieldY = y
        const fullCol1_2 = boxWidth * 0.5
        const fullCol2_2 = boxWidth * 0.5

        drawFieldsInRow(
            [
            {
                label: "Embargos en instituciones financieras",
                value: document.getElementById("embargos")?.value || "",
                width: fullCol1_2,
                labelRatio: 0.60,
            },
            {
                label: "Consumo de sustancias ilícitas",
                value: document.getElementById("consumo_sustancias")?.value || "",
                width: fullCol2_2,
                labelRatio: 0.58,
            },
            ],
            fieldY,
            6,
        )

        y += 6

        // FILA 9: Dirección Actual, Dirección DNI
        fieldY = y
        drawFieldsInRow(
            [
            { label: "Dirección Actual", value: document.getElementById("direccion_actual")?.value || "", width: fullCol1_2, labelRatio: 0.30 },
            { label: "Dirección DNI", value: document.getElementById("direccion_dni")?.value || "", width: fullCol2_2, labelRatio: 0.30 },
            ],
            fieldY,
            6,
        )

        y += 6

        // FILA 10: En caso de Emergencia llamar a
        fieldY = y

        drawFieldsInRow(
            [
            {
                label: "En caso de Emergencia llamar a",
                value: document.getElementById("contacto_emergencia")?.value || "",
                width: boxWidth,
                labelRatio: 0.30,
            },
            ],
            fieldY,
            6,
        )

        y += 6

        // FILA 11: Número de celular, Parentesco
        fieldY = y

        drawFieldsInRow(
            [
            { label: "Número de celular", value: document.getElementById("celular_emergencia")?.value || "", width: boxWidth / 2, labelRatio: 0.45 },
            { label: "Parentesco", value: document.getElementById("parentesco_emergencia")?.value || "", width: boxWidth / 2, labelRatio: 0.45 },
            ],
            fieldY,
            6,
        )

        y += 8

        // ========== MIS DATOS LABORALES ==========
        checkPageBreak(50)
        drawSectionTitle("MIS DATOS LABORALES", y)
        y += 7

        fieldY = y
        const fullCol1_3 = boxWidth * 0.30
        const fullCol2_3 = boxWidth * 0.40
        const fullCol3_3 = boxWidth * 0.30

        drawFieldsInRow(
            [
            { label: "Curso SUCAMEC", value: document.getElementById("curso_sucamec")?.value || "", width: fullCol1_3, labelRatio: 0.50 },
            { label: "S.M.O.", value: document.getElementById("smo")?.value || "", width: fullCol2_3, labelRatio: 0.25 },
            { label: "Institución", value: document.getElementById("institucion_laboral")?.value || "", width: fullCol3_3, labelRatio: 0.40 },
            ],
            fieldY,
            6,
        )

        y += 6

        fieldY = y

        let licenciaArmaRaw = document.getElementById("licencia_arma")?.value || "";
        let licenciaArma = "";

        if (licenciaArmaRaw) {
            try {
                const parsed = JSON.parse(licenciaArmaRaw);
                if (Array.isArray(parsed)) {
                    licenciaArma = parsed.map(item => item.value).join(", ");
                } else {
                    licenciaArma = parsed.value || licenciaArmaRaw;
                }
            } catch (err) {
                licenciaArma = licenciaArmaRaw;
            }
        }

        drawFieldsInRow(
            [
            { label: "Licencia de Arma", value: licenciaArma, width: fullCol1_3, labelRatio: 0.50 },
            { label: "Tipo", value: document.getElementById("tipo_arma")?.value || "", width: fullCol2_3, labelRatio: 0.25 },
            { label: "Arma Propia", value: document.getElementById("arma_propia")?.value || "", width: fullCol3_3, labelRatio: 0.40 },
            ],
            fieldY,
            6,
        )

        y += 6

        fieldY = y
        const fullCol1_4 = boxWidth * 0.30
        const fullCol2_4 = boxWidth * 0.20
        const fullCol3_4 = boxWidth * 0.20
        const fullCol4_4 = boxWidth * 0.30
        
        drawFieldsInRow(
            [
            { label: "N° Brevete", value: document.getElementById("brevete")?.value || "", width: fullCol1_4, labelRatio: 0.50 },
            { label: "Clase", value: document.getElementById("clase_brevete")?.value || "", width: fullCol2_4, labelRatio: 0.50 },
            { label: "Tipo", value: document.getElementById("tipo_vehiculo")?.value || "", width: fullCol3_4, labelRatio: 0.28 },
            { label: "Vehículo Propio", value: document.getElementById("vehiculo_propio")?.value || "", width: fullCol4_4, labelRatio: 0.40 },
            ],
            fieldY,
            6,
        )

        y += 6

        fieldY = y
        drawFieldsInRow(
            [
            { label: "Empresa Anterior", value: document.getElementById("empresa_anterior")?.value || "", width: fullCol1_3, labelRatio: 0.50 },
            { label: "Cargo", value: document.getElementById("cargo_anterior")?.value || "", width: fullCol2_3, labelRatio: 0.25 },
            { label: "Duración", value: document.getElementById("duracion_anterior")?.value || "", width: fullCol3_3, labelRatio: 0.40 },
            ],
            fieldY,
            6,
        )

        y += 6

        fieldY = y
        drawFieldsInRow(
            [
            {
                label: "Profesión u Ocupación Alterna",
                value: document.getElementById("profesion_alterna")?.value || "",
                width: boxWidth,
                labelRatio: 0.25,
            },
            ],
            fieldY,
            6,
        )

        y += 8

        // ========== MIS DATOS FAMILIARES ==========
        checkPageBreak(50)
        drawSectionTitle("MIS DATOS FAMILIARES", y)
        y += 7

        // Encabezados de tabla
        pdf.setFillColor(...colors.labelBg)
        const colParentesco = boxWidth * 0.25
        const colNombres = boxWidth * 0.5
        const colFecha = boxWidth * 0.25

        pdf.rect(boxX, y, colParentesco, 5, "F")
        pdf.rect(boxX + colParentesco, y, colNombres, 5, "F")
        pdf.rect(boxX + colParentesco + colNombres, y, colFecha, 5, "F")

        pdf.setDrawColor(...colors.borderColor)
        pdf.setLineWidth(0.2)
        pdf.rect(boxX, y, colParentesco, 5)
        pdf.rect(boxX + colParentesco, y, colNombres, 5)
        pdf.rect(boxX + colParentesco + colNombres, y, colFecha, 5)

        pdf.setFontSize(6.5)
        pdf.setFont(undefined, "bold")
        pdf.setTextColor(...colors.labelText)
        pdf.text("Parentesco", boxX + 1, y + 3.2)
        pdf.text("Apellidos y Nombres", boxX + colParentesco + 1, y + 3.2)
        pdf.text("Fecha Nacimiento", boxX + colParentesco + colNombres + 1, y + 3.2)

        y += 5

        // SIEMPRE 7 FILAS FIJAS
        const familiaresContainer = document.getElementById("familyContainer")
        const familiares = []
        
        if (familiaresContainer) {
            const filas = familiaresContainer.querySelectorAll("[data-familia-row]")
            filas.forEach((fila) => {
            familiares.push({
                parentesco: fila.querySelector('[name="parentesco[]"]')?.value || "",
                apellidosNombres: fila.querySelector('[name="apellidosNombres[]"]')?.value || "",
                fechaNacimiento: formatDateToDMY(
                    fila.querySelector('[name="fechaNacimiento[]"]')?.value || ""
                ),
            })
            })
        }

        // Rellenar hasta 7 filas
        for (let i = 0; i < 7; i++) {
            checkPageBreak(5)

            const familiar = familiares[i] || { parentesco: "", apellidosNombres: "", fechaNacimiento: "" }

            pdf.setFontSize(6.5)
            pdf.setTextColor(...colors.inputText)
            pdf.setFont(undefined, "normal")
            pdf.setDrawColor(...colors.borderColor)
            pdf.setLineWidth(0.2)

            pdf.rect(boxX, y, colParentesco, 5)
            pdf.text(familiar.parentesco, boxX + 1, y + 3.2)

            pdf.rect(boxX + colParentesco, y, colNombres, 5)
            pdf.text(familiar.apellidosNombres, boxX + colParentesco + 1, y + 3.2)

            pdf.rect(boxX + colParentesco + colNombres, y, colFecha, 5)
            pdf.text(familiar.fechaNacimiento, boxX + colParentesco + colNombres + 1, y + 3.2)

            y += 5
        }

        y += 5

        // ========== MI ACEPTACION DE LOS PROCEDIMIENTOS ==========
        // NO hacer checkPageBreak aquí para que continúe en la misma página si hay espacio
        if (y + 15 > pageHeight - marginBottom) {
            pdf.addPage()
            y = marginTop
        }
        
        drawSectionTitle("MI ACEPTACION DE LOS PROCEDIMIENTOS DE LA EMPRESA", y)
        y += 7

        const procedimientos = [
            {
            numero: "1.",
            titulo: "MI SISTEMA DE INFORMACION PERSONAL - SIP",
            items: [
                "Utilizaré la plataforma virtual personal SIP que la empresa me proporciona con usuario y clave.",
                "Visitaré el SIP, las veces que sea necesario para recibir información relacionada con mis funciones, obligaciones y derechos.",
                "La información en el SIP es de propiedad de mi empleador por lo que cuidaré de la confidencialidad de su contenido.",
            ],
            },
            {
            numero: "2.",
            titulo: "MIS DECLARACIONES Y BOLETAS DE REMUNERACIONES",
            items: [
                "Apruebo que mi correo electrónico personal, sea utilizado por la empresa para declarar mis remuneraciones en el T-Registro de SUNAT.",
                "Utilizaré el Sistema de Información Personal - SIP que me proporciona mi empleador con Usuario y Clave para recibir mis Boletas de Remuneraciones y firmar el Cargo de Recepción correspondiente.",
            ],
            },
            {
            numero: "3.",
            titulo: "MIS CANALES DE COMUNICACIONES",
            items: [
                "Autorizo de manera libre y voluntaria a mi empleador para enviarme documentos e información vinculada a mi relación laboral, a través de mi correo electrónico y/o WhatsApp personales, siendo éstos, los medios de comunicación oficiales entre ambas partes.",
                "Atenderé las llamadas que la empresa realice a mi teléfono celular personal para coordinaciones relacionadas al servicio, estando obligado a contestar estas llamadas o devolverlas en todos los casos.",
            ],
            },
            {
            numero: "4.",
            titulo: "MI FIRMA Y HUELLA REGISTRADAS",
            items: [
                "Autorizo que mi firma y huella registradas, sean utilizadas para los reportes de procesos internos que me involucren.",
                "Conozco y acepto que mi firma física en un formato o mi firma digital en el sistema, se utilicen en reportes de la empresa empleando mi firma manuscrita escaneada.",
            ],
            },
            {
            numero: "5.",
            titulo: "MIS CAPACITACIONES",
            items: [
                "Acepto la modalidad de capacitación que la empresa ha establecido para el mejor cumplimiento de mis funciones.",
                "Asistiré a las capacitaciones presenciales y virtuales registrando mi firma de manera física y electrónica respectivamente.",
                "Cuando firme asistencia empleando los sistemas de capacitación virtuales, acepto que se consigne mi firma digital en los reportes correspondientes.",
            ],
            },
        ]

        procedimientos.forEach((seccion) => {
            checkPageBreak(25)

            // Título con fondo oscuro (como los títulos de sección)
            pdf.setFillColor(...colors.sectionBg)
            pdf.rect(boxX, y, boxWidth, 5, "F")
            pdf.setDrawColor(...colors.borderColor)
            pdf.setLineWidth(0.2)
            pdf.rect(boxX, y, boxWidth, 5)

            pdf.setFontSize(7)
            pdf.setFont(undefined, "bold")
            pdf.setTextColor(...colors.sectionText)
            pdf.text(`${seccion.numero} ${seccion.titulo}`, boxX + 2, y + 3.5)
            y += 5

            // Items con letras a, b, c en recuadros blancos
            pdf.setFont(undefined, "normal")
            pdf.setTextColor(...colors.inputText)
            
            seccion.items.forEach((item, idx) => {
            const letra = String.fromCharCode(97 + idx) // a, b, c...
            const textoCompleto = `${letra}. ${item}`
            const lineas = pdf.splitTextToSize(textoCompleto, boxWidth - 6)
            
            // Calcular altura necesaria para este item
            const itemHeight = lineas.length * 2.8 + 1
            checkPageBreak(itemHeight)
            
            // Recuadro blanco para el item
            pdf.setFillColor(255, 255, 255)
            pdf.rect(boxX, y, boxWidth, itemHeight, "F")
            pdf.setDrawColor(...colors.borderColor)
            pdf.setLineWidth(0.2)
            pdf.rect(boxX, y, boxWidth, itemHeight)
            
            // Texto del item
            let itemY = y + 2.8
            lineas.forEach((linea, lineIdx) => {
                const indent = lineIdx === 0 ? 2 : 4
                pdf.text(linea, boxX + indent, itemY)
                itemY += 2.8
            })
            
            y += itemHeight
            })

            y += 1
        })


        // ========== MI CONFORMIDAD CON LA DECLARACION JURADA ==========
        checkPageBreak(35)

        y += 5

        drawSectionTitle("MI CONFORMIDAD CON LA DECLARACION JURADA", y)

        y += 7

        // Layout tipo tabla: 3 columnas en una fila grande
        const rowYStart = y
        const colTextoWidth = boxWidth * 0.58   // Columna 1: Texto + Fecha/Nombre
        const colFirmaWidth = boxWidth * 0.21   // Columna 2: Firma
        const colHuellaWidth = boxWidth * 0.21  // Columna 3: Huella
        const rowHeight = 30  // Altura total de la fila

        // ===== COLUMNA 1: TEXTO + FECHA + NOMBRE =====
        const textoX = boxX
        pdf.setFillColor(255, 255, 255)
        pdf.rect(textoX, rowYStart, colTextoWidth, rowHeight, "F")
        pdf.setDrawColor(...colors.borderColor)
        pdf.setLineWidth(0.2)
        pdf.rect(textoX, rowYStart, colTextoWidth, rowHeight)

        // Texto principal
        pdf.setFontSize(6.5)
        pdf.setFont(undefined, "normal")
        pdf.setTextColor(...colors.inputText)

        const conformidadText =
        "De acuerdo con lo dispuesto por mi empleador por norma interna, cumpliré con mi obligación de actualizar cada 12 meses esta Declaración Jurada y también hacerlo, cuando varíe cualquiera de mis datos registrados, asumiendo la responsabilidad en caso de incumplimiento."
        const lineasConformidad = pdf.splitTextToSize(conformidadText, colTextoWidth - 4)

        let textoY = rowYStart + 3
        lineasConformidad.forEach((linea) => {
        pdf.text(linea, textoX + 2, textoY)
        textoY += 2.8
        })

        // Subceldas para Fecha y Nombre (con bordes internos)
        const subCeldaY = rowYStart + 18
        const subCeldaHeight = 6

        // Subcelta "Fecha de la declaración:"
        pdf.setFillColor(240, 240, 240)
        pdf.rect(textoX, subCeldaY, colTextoWidth, subCeldaHeight, "F")
        pdf.setDrawColor(...colors.borderColor)
        pdf.setLineWidth(0.2)
        pdf.rect(textoX, subCeldaY, colTextoWidth, subCeldaHeight)

        pdf.setFontSize(6.5)
        pdf.setFont(undefined, "normal")
        pdf.text("Fecha de la declaración:", textoX + 2, subCeldaY + 4)

        // Subcelda "Nombre"
        pdf.setFillColor(240, 240, 240)
        pdf.rect(textoX, subCeldaY + subCeldaHeight, colTextoWidth, subCeldaHeight, "F")
        pdf.rect(textoX, subCeldaY + subCeldaHeight, colTextoWidth, subCeldaHeight)

        pdf.text("Nombre:", textoX + 2, subCeldaY + subCeldaHeight + 4)

        // ===== COLUMNA 2: FIRMA REGISTRADA =====
        const firmaX = textoX + colTextoWidth
        pdf.setFillColor(255, 255, 255)
        pdf.rect(firmaX, rowYStart, colFirmaWidth, rowHeight, "F")
        pdf.rect(firmaX, rowYStart, colFirmaWidth, rowHeight)

        pdf.setFontSize(7)
        pdf.setFont(undefined, "bold")
        pdf.setTextColor(...colors.labelText)
        pdf.text("Firma Registrada", firmaX + colFirmaWidth / 2, rowYStart + 28, { align: "center" })

        // ===== COLUMNA 3: HUELLA REGISTRADA =====
        const huellaX = firmaX + colFirmaWidth
        pdf.setFillColor(255, 255, 255)
        pdf.rect(huellaX, rowYStart, colHuellaWidth, rowHeight, "F")
        pdf.rect(huellaX, rowYStart, colHuellaWidth, rowHeight)

        pdf.setFontSize(7)
        pdf.setFont(undefined, "bold")
        pdf.text("Huella Registrada", huellaX + colHuellaWidth / 2, rowYStart + 25, { align: "center" })
        pdf.setFontSize(6)
        pdf.setFont(undefined, "normal")
        pdf.text("Indice Derecho", huellaX + colHuellaWidth / 2, rowYStart + 28, { align: "center" })

        y = rowYStart + rowHeight + 2

        const pdfUrl = pdf.output('bloburl')
        window.open(pdfUrl, '_blank')
    }


    //-------------- GUARDAR EN BD --------------//
    
    document.getElementById("formDatos")?.addEventListener("submit", async function (e) {
        e.preventDefault();

        try {
            const btnGuardar = document.getElementById("btnGuardar");
            btnGuardar.disabled = true;
            btnGuardar.textContent = "Guardando...";

            const form = e.target;
            const formData = new FormData(form);

            // if (familiares && Array.isArray(familiares)) {
            //     formData.append("familiares", JSON.stringify(familiares));
            // }

            // if (tagifyLicencias) {
            //     const licencias = tagifyLicencias.value.map(tag => tag.value);
            //     formData.append("licencia_arma", JSON.stringify(licencias));
            // }

            const response = await axios.post(`${VITE_URL_APP}/api/save-declaracion-jurada`, formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            });

            if (response.data.success) {
                Swal.fire({
                    icon: "success",
                    title: "Guardado correctamente",
                    text: "La declaración jurada se ha registrado.",
                });

                limpiarFormulario();

            } else {
                Swal.fire({
                    icon: "warning",
                    title: "Ocurrió un problema",
                    text: response.data.message || "No se pudo guardar los datos.",
                });
            }

        } catch (error) {
            console.error("Error al guardar:", error);
            Swal.fire({
                icon: "error",
                title: "Error al guardar",
                text: error.response?.data?.message || "Error de conexión con el servidor.",
            });
        } finally {
            const btnGuardar = e.target.querySelector('button[type="submit"]');
            btnGuardar.disabled = false;
            btnGuardar.textContent = "Guardar";
        }
    });



});


