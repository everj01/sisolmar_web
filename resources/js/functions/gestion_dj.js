
import axios from 'axios';
import Swal from 'sweetalert2';
import { TabulatorFull as Tabulator } from 'tabulator-tables';
import 'tabulator-tables/dist/css/tabulator_simple.min.css';

import Tagify from '@yaireo/tagify';
import '@yaireo/tagify/dist/tagify.css';


document.addEventListener('DOMContentLoaded', function () {

    getPersonal();

    //Tabla de Personas
    const tblPersonas = new Tabulator("#tblPersonas", {
        height: "100%",
        layout: "fitData",
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
            { title: "N°", formatter: "rownum", hozAlign: "center", width: 60 },

            {
                title: "Nombres",
                field: "nombres",
                hozAlign: "left",
                widthGrow: 3,
                formatter: function (cell) {
                    let data = cell.getData();
                    return `${data.nombres ?? ''} ${data.apellido1 ?? ''} ${data.apellido2 ?? ''} `.trim();
                }
            },

            { title: "DNI", field: "dni", hozAlign: "center", widthGrow: 2 },

            {
                title: "Acciones",
                field: "acciones",
                hozAlign: "center",
                headerSort: false,
                widthGrow: 1,
                formatter: function (cell) {
                    return `<button type="button" class="btn rounded-full form-btn bg-success/25 text-success hover:bg-success hover:text-white">Formulario</button>`;
                },
                cellClick: function (e, cell) {

                    if (e.target.classList.contains('form-btn')) {
                        var registro = cell.getRow().getData();

                        abrirFormulario(registro);
                    }
                }
            },
        ],
        layout: "fitColumns",

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

    document.getElementById('btnNuevaDJ').addEventListener('click', function () {
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

    document.getElementById('cerrarModal').addEventListener('click', function () {
        cerrarFormulario();
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

    // Función para obtener el listados de personas
    function getPersonal() {
        axios.get(`${VITE_URL_APP}/api/get-postulantes`)
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

        if (data) {
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
                } catch (e) {
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

        // MODO VISTA: Ocultar listado y mostrar formulario
        document.getElementById('divListado').classList.add('hidden');
        document.getElementById('divCoincidencias').classList.add('hidden'); // Asegurar ocultar coincidencias
        document.getElementById('formModal').classList.remove('hidden');
    };

    window.cerrarFormulario = function () {
        // MODO VISTA: Ocultar formulario y mostrar listado
        document.getElementById('formModal').classList.add('hidden');
        document.getElementById('divListado').classList.remove('hidden');
        // No mostramos Coincidencias por defecto, eso lo maneja la búsqueda si aplica
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
        try {
            const { jsPDF } = window.jspdf
            const pdf = new jsPDF({ unit: "mm", format: "a4", compress: true })

            // ---------- Parámetros de estilo y layout (MAXIMIZADO) ----------
            const pageWidth = 210
            const pageHeight = 297
            const marginLeft = 10     // Aumentado a 10mm
            const marginRight = 10    // Aumentado a 10mm
            const marginTop = 10      // Aumentado a 10mm
            const marginBottom = 10   // Aumentado a 10mm
            const boxWidth = pageWidth - marginLeft - marginRight
            const boxX = marginLeft
            let y = marginTop

            const colors = {
                headerText: [0, 0, 0],
                sectionBg: [220, 220, 220],
                sectionText: [0, 0, 0],
                labelBg: [220, 220, 220],
                labelText: [0, 0, 0],
                inputText: [0, 0, 0],
                borderColor: [0, 0, 0],
            }

            // Helper: fitText
            // Helper: fitText (Base font aumentada)
            // Helper: fitText (Base font aumentada)
            function fitText(text, maxWidth, initialFontSize = 8.5, minFontSize = 5) {
                pdf.setFontSize(initialFontSize)
                let textWidth = pdf.getTextWidth(text)
                let currentSize = initialFontSize
                while (textWidth > maxWidth && currentSize > minFontSize) {
                    currentSize -= 0.4
                    pdf.setFontSize(currentSize)
                    textWidth = pdf.getTextWidth(text)
                }
                return currentSize
            }

            function drawField(label, value, x, width, fieldY, inputHeight = 6, labelRatio = 0.35, alignValue = "left") {
                const labelWidth = width * labelRatio
                const valueWidth = width * (1 - labelRatio)
                const labelPadding = 1
                const valStr = String(value || "").toUpperCase()

                // Label box
                pdf.setFillColor(...colors.labelBg)
                pdf.rect(x, fieldY, labelWidth, inputHeight, "F")
                pdf.setDrawColor(...colors.borderColor)
                pdf.setLineWidth(0.2)
                pdf.rect(x, fieldY, labelWidth, inputHeight)

                // Label text
                pdf.setFont(undefined, "bold")
                pdf.setTextColor(...colors.labelText)
                const labelFontSize = fitText(label, labelWidth - 2, 7, 4.5)
                pdf.setFontSize(labelFontSize)
                pdf.text(label, x + labelPadding, fieldY + inputHeight / 2 + 1, { align: "left" })

                // Value box
                pdf.setFillColor(255, 255, 255)
                pdf.rect(x + labelWidth, fieldY, valueWidth, inputHeight, "F")
                pdf.rect(x + labelWidth, fieldY, valueWidth, inputHeight)

                // Value text
                pdf.setFont(undefined, "normal")
                pdf.setTextColor(...colors.inputText)
                const maxValW = valueWidth - (alignValue === "center" ? 1 : 2)
                const valFontSize = fitText(valStr, maxValW, 7.5, 4.5)
                pdf.setFontSize(valFontSize)
                const textY = fieldY + inputHeight / 2 + 1
                const valX = alignValue === "center" ? x + labelWidth + valueWidth / 2 : x + labelWidth + 1
                pdf.text(valStr, valX, textY, { maxWidth: maxValW, align: alignValue })
            }

            function drawSectionTitle(title, yPos) {
                pdf.setFillColor(...colors.sectionBg)
                pdf.rect(boxX, yPos, boxWidth, 5, "F") // 5mm altura header
                pdf.setDrawColor(...colors.borderColor)
                pdf.setLineWidth(0.2)
                pdf.rect(boxX, yPos, boxWidth, 5)

                pdf.setFontSize(8)
                pdf.setFont(undefined, "bold")
                pdf.setTextColor(...colors.sectionText)
                pdf.text(title, boxX + boxWidth / 2, yPos + 2.8, { align: "center" })
            }

            function formatDateToDMY(fechaStr) {
                if (!fechaStr) return "";
                const partes = fechaStr.split("-");
                return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : fechaStr;
            }

            function checkPageBreak(heightNeeded) {
                if (y + heightNeeded > pageHeight - marginBottom - 1) { // 1mm tolerancia
                    pdf.addPage()
                    y = marginTop
                    return true
                }
                return false
            }

            // ========== ENCABEZADO ==========
            const headerH = 19 // Ajustado a 19mm (Seguridad 1 pag)
            const logoW = 30   // 30mm logo
            const codeW = 20   // 20mm RH 02 code
            const titleW = boxWidth - logoW - codeW

            // Logo
            pdf.setDrawColor(0); pdf.setLineWidth(0.3);
            pdf.rect(boxX, y, logoW, headerH)
            await drawLogo(boxX, y, logoW, headerH)

            // Title
            const titleX = boxX + logoW
            pdf.rect(titleX, y, titleW, headerH)

            pdf.setFontSize(10)
            pdf.setTextColor(200, 0, 0)
            pdf.setFont(undefined, "bold")
            pdf.text("SISTEMA INTEGRADO SOLMAR – SISOLMAR", titleX + titleW / 2, y + 6, { align: "center" })

            pdf.setFontSize(14) // Aumentado significativamente
            pdf.setTextColor(0, 0, 0)
            pdf.text("DECLARACION JURADA DEL TRABAJADOR", titleX + titleW / 2, y + 13, { align: "center" })

            // Code RH 02
            const codeX = titleX + titleW
            pdf.rect(codeX, y, codeW, headerH)
            pdf.setFontSize(12)
            pdf.text("RH 02", codeX + codeW / 2, y + 9, { align: "center" })

            y += headerH // Eliminar espacio extra

            // Declaración Texto (Dinámica con ajuste)
            const nombres = (document.getElementById("nombres_apellidos")?.value || "").toUpperCase().trim()
            const dni = document.getElementById("dni")?.value || "".trim()

            pdf.setFontSize(7)
            const lineHeight = 3 // Altura de linea para texto
            const maxWidth = boxWidth - 4
            let currentX = boxX + 2
            let currentY = y + 3.5

            // Segmentos de texto
            const segments = [
                { text: "Yo, ", font: "normal" },
                { text: nombres, font: "bold" },
                { text: ", identificado con DNI ", font: "normal" },
                { text: dni, font: "bold" },
                { text: ", declaro bajo juramento que los datos personales, laborales y familiares que consigno en este documento son correctos, por lo que asumo la responsabilidad por su veracidad, cumplimiento y actualización, estando conforme con esta declaración jurada.", font: "normal" }
            ]

            // Calculo previo de altura (Simulacion)
            // Para dibujar la caja primero, necesitamos saber cuantos renglones ocupa
            let simX = 0
            let simLines = 1
            segments.forEach(seg => {
                pdf.setFont(undefined, seg.font)
                const words = seg.text.split(" ")
                words.forEach((word, i) => {
                    const wWidth = pdf.getTextWidth(word + " ")
                    if (simX + wWidth > maxWidth) {
                        simLines++
                        simX = wWidth // Nueva linea empieza con esta palabra
                    } else {
                        simX += wWidth
                    }
                })
            })

            const declBoxH = (simLines * lineHeight) + 3

            // Dibujar caja
            pdf.setDrawColor(0); pdf.setLineWidth(0.2);
            pdf.rect(boxX, y, boxWidth, declBoxH)

            // Renderizado Real
            currentX = boxX + 2
            currentY = y + 3 // Ajuste inicial Y dentro de caja

            segments.forEach(seg => {
                pdf.setFont(undefined, seg.font)
                // Si es un bloque largo (el ultimo), lo procesamos palabra por palabra para wrapping
                // Si son los cortos (Yo, nombre, DNI), intentamos mantenerlos juntos si caben, o wrap palabra por palabra igual

                // Logica unificada: Palabra por palabra
                // Preservar espacios? split(" ") elimina espacios. Agregamos " " al dibujar.
                // Para el nombre completo, quiza queramos mantenerlo junto? No necesariamente.

                const words = seg.text.split(/\s+/) // Split por cualquier espacio

                words.forEach((word, i) => {
                    // Reconstruir espacio excepto ultimo
                    const wordWithSpace = word + ((i < words.length - 1) || seg.text.endsWith(" ") ? " " : "")
                    const wWidth = pdf.getTextWidth(wordWithSpace)

                    if (currentX + wWidth > boxX + maxWidth + 2) {
                        currentX = boxX + 2
                        currentY += lineHeight
                    }

                    pdf.text(word, currentX, currentY)
                    // Subrayado para datos (bold)
                    // Subrayado para datos (bold) - ELIMINADO
                    /*if (seg.font === "bold") {
                        pdf.setLineWidth(0.1)
                        pdf.line(currentX, currentY + 0.5, currentX + pdf.getTextWidth(word), currentY + 0.5)
                    }*/

                    currentX += wWidth
                })

                // Añadir espacio visual entre segmentos si el segmento original tenia espacio al final
                // Ojo: split consume espacios.
                // Solucion simple: siempre añadir espacio tras cada palabra, pero manejar puntuacion pegada.
                // Mejor: split manual preservando delimitadores? Complejo.
                // Aceptable: Agregar espacio siempre, el PDF lo soporta bien.
                if (!seg.text.endsWith(" ") && !seg.text.startsWith(" ") && segments.indexOf(seg) < segments.length - 1) {
                    currentX += 1 // Small gap manual? O check logic arriba
                }
            })

            y += declBoxH

            // ========== DATOS PERSONALES ==========
            drawSectionTitle("MIS DATOS PERSONALES", y)
            y += 5 // Corregido overlap (4->5)

            const colMain = boxWidth - 35 // Foto mas ancha (35mm)
            const colFoto = 35
            const rowH = 6.5 // 6.5mm altura fila (Optimizado 1 pag)

            // Fila 1: Nombres
            drawField("Nombres y Apellidos", nombres, boxX, colMain, y, rowH, 0.25)

            // Foto
            // Foto
            const fotoH = rowH * 6 // 6 filas (Incluye Afiliacion)
            pdf.rect(boxX + colMain, y, colFoto, fotoH)
            pdf.setFontSize(6); pdf.setFont(undefined, "normal"); pdf.setTextColor(150);
            pdf.text("FOTO", boxX + colMain + colFoto / 2, y + fotoH / 2, { align: "center" })
            y += rowH

            // Fila 2: DNI...
            const w1 = colMain / 4
            drawField("DNI", dni, boxX, w1, y, rowH, 0.3)
            drawField("Caduca", document.getElementById("caduca")?.value || "", boxX + w1, w1, y, rowH, 0.4)
            drawField("Estado Civil", document.getElementById("estado_civil")?.value || "", boxX + w1 * 2, w1, y, rowH, 0.45)
            drawField("Sexo", document.getElementById("sexo")?.value || "", boxX + w1 * 3, w1, y, rowH, 0.3)
            y += rowH

            // Fila 3: Fecha...
            const w2 = colMain / 2
            drawField("Fecha Nacimiento", formatDateToDMY(document.getElementById("fecha_nacimiento")?.value), boxX, w2, y, rowH, 0.3)
            drawField("Ciudad", document.getElementById("provincia_actual")?.options[document.getElementById("provincia_actual")?.selectedIndex]?.text || "", boxX + w2, w2, y, rowH, 0.2)
            y += rowH

            // Fila 4: Tipo Sangre...
            const w3 = colMain / 4
            drawField("Tipo Sangre", document.getElementById("tipo_sangre")?.value || "", boxX, w3, y, rowH, 0.5)
            drawField("Peso (Kg.)", document.getElementById("peso")?.value || "", boxX + w3, w3, y, rowH, 0.5)
            drawField("Talla (Mt.)", document.getElementById("talla")?.value || "", boxX + w3 * 2, w3, y, rowH, 0.5)
            drawField("Celular", document.getElementById("celular")?.value || "", boxX + w3 * 3, w3, y, rowH, 0.4)
            y += rowH

            // Fila 5: Correo...
            const wMail = w3 * 3
            const wWsp = w3
            drawField("Correo electrónico", document.getElementById("correo")?.value || "", boxX, wMail, y, rowH, 0.2)
            drawField("WhatsApp", document.getElementById("whatsapp")?.value || "", boxX + wMail, wWsp, y, rowH, 0.45)
            y += rowH

            // Fila 6: Afiliacion Texto (Ajustado a colMain para dejar espacio a Foto)
            const row6W = colMain
            const row6LabelW = row6W * 0.75 // Dar mas espacio al texto
            const row6InputW = row6W - row6LabelW

            pdf.setFillColor(220); pdf.rect(boxX, y, row6LabelW, rowH, "F"); pdf.rect(boxX, y, row6LabelW, rowH);
            pdf.setTextColor(0); pdf.setFont(undefined, "normal"); pdf.setFontSize(6.5);
            pdf.text("No estoy afiliado a ninguna AFP o ONP y deseo afiliarme a:", boxX + 2, y + 3)

            pdf.setFillColor(255); // Reset fill
            pdf.rect(boxX + row6LabelW, y, row6InputW, rowH)
            y += rowH

            // Fila 7: AFP/ONP
            const sysPrev = document.getElementById("sistema_previsional")?.value || ""
            const isAFP = sysPrev.includes("AFP")
            const isONP = sysPrev.includes("ONP")
            drawField("Estoy afiliado a la AFP", isAFP ? "X" : "", boxX, boxWidth / 2, y, rowH, 0.8, "center")
            drawField("Estoy afiliado a la ONP", isONP ? "X" : "", boxX + boxWidth / 2, boxWidth / 2, y, rowH, 0.8, "center")
            y += rowH

            // Fila 8: Educacion
            const wEdu = boxWidth / 4
            drawField("Grado de instrucción", document.getElementById("grado_instruccion")?.options[document.getElementById("grado_instruccion")?.selectedIndex]?.text || "", boxX, wEdu, y, rowH, 0.45)
            drawField("Institución", document.getElementById("institucion")?.options[document.getElementById("institucion")?.selectedIndex]?.text || "", boxX + wEdu, wEdu, y, rowH, 0.3)
            drawField("Carrera", document.getElementById("carrera")?.options[document.getElementById("carrera")?.selectedIndex]?.text || "", boxX + wEdu * 2, wEdu, y, rowH, 0.3)
            drawField("Año de egreso", document.getElementById("anio_egreso")?.value || "", boxX + wEdu * 3, wEdu, y, rowH, 0.5)
            y += rowH

            // Fila 9: Embargos
            const wFin = boxWidth / 3
            drawField("Embargos en instituciones financieras", document.getElementById("embargos")?.value || "", boxX, wFin, y, rowH, 0.8)
            drawField("Cuenta sueldo", "", boxX + wFin, wFin, y, rowH, 0.4)
            drawField("Cuenta sueldo", "", boxX + wFin * 2, wFin, y, rowH, 0.4)
            y += rowH

            // Fila 10: Direccion Actual
            drawField("Dirección Actual", document.getElementById("direccion_actual")?.value || "", boxX, boxWidth, y, rowH, 0.15)
            y += rowH

            // Fila 11: Direccion DNI
            drawField("Dirección DNI", document.getElementById("direccion_dni")?.value || "", boxX, boxWidth, y, rowH, 0.15)
            y += rowH

            // Fila 12: Emergencia 1
            const wEmer1 = boxWidth * 0.6
            drawField("En caso de Emergencia llamar a", document.getElementById("contacto_emergencia")?.value || "", boxX, wEmer1, y, rowH, 0.4)
            y += rowH

            // Fila 13: Emergencia 2
            drawField("Número de celular", document.getElementById("celular_emergencia")?.value || "", boxX, boxWidth / 2, y, rowH, 0.3)
            drawField("Parentesco", document.getElementById("parentesco_emergencia")?.value || "", boxX + boxWidth / 2, boxWidth / 2, y, rowH, 0.3)
            // ========== DATOS LABORALES ==========
            checkPageBreak(5 * rowH + 5 + 3)
            drawSectionTitle("MIS DATOS LABORALES", y)
            y += 5 // Corregido overlap (4->5)

            // Fila 1
            drawField("Profesión u Ocupación Principal", "", boxX, boxWidth * 0.6, y, rowH, 0.4)
            drawField("Tiempo Experiencia", "", boxX + boxWidth * 0.6, boxWidth * 0.4, y, rowH, 0.4)
            y += rowH

            // Fila 2
            drawField("Familiar en la Empresa", "", boxX, boxWidth * 0.25, y, rowH, 0.6)
            drawField("Nombre Completo", "", boxX + boxWidth * 0.25, boxWidth * 0.5, y, rowH, 0.3)
            drawField("Parentesco", "", boxX + boxWidth * 0.75, boxWidth * 0.25, y, rowH, 0.4)
            y += rowH

            // Fila 3: SMO...
            const wLab3 = boxWidth / 6
            drawField("SMO", document.getElementById("smo")?.value || "", boxX, wLab3, y, rowH, 0.4)
            drawField("Institución", document.getElementById("institucion_laboral")?.value || "", boxX + wLab3, wLab3, y, rowH, 0.5)
            drawField("Nº Brevete", document.getElementById("brevete")?.value || "", boxX + wLab3 * 2, wLab3, y, rowH, 0.6)
            drawField("Clase", document.getElementById("clase_brevete")?.value || "", boxX + wLab3 * 3, wLab3, y, rowH, 0.4)
            drawField("Tipo", "", boxX + wLab3 * 4, wLab3, y, rowH, 0.4)
            drawField("Vehículo Propio", document.getElementById("vehiculo_propio")?.value || "", boxX + wLab3 * 5, wLab3, y, rowH, 0.65)
            y += rowH

            // Fila 4
            drawField("Empresa Anterior", document.getElementById("empresa_anterior")?.value || "", boxX, boxWidth * 0.4, y, rowH, 0.3)
            drawField("Cargo", document.getElementById("cargo_anterior")?.value || "", boxX + boxWidth * 0.4, boxWidth * 0.3, y, rowH, 0.3)
            drawField("Duración", document.getElementById("tiempo_servicio_anterior")?.value || "", boxX + boxWidth * 0.7, boxWidth * 0.3, y, rowH, 0.3)
            y += rowH

            // Fila 5
            drawField("Profesión u Ocupación Alterna 1", "", boxX, boxWidth / 2, y, rowH, 0.4)
            drawField("Profesión u Ocupación Alterna 2", "", boxX + boxWidth / 2, boxWidth / 2, y, rowH, 0.4)
            // ========== DATOS FAMILIARES ==========
            checkPageBreak(40)
            drawSectionTitle("MIS DATOS FAMILIARES", y)
            y += 5 // Corregido overlap (4->5)

            // Headers
            const fmC1 = boxWidth * 0.2
            const fmC2 = boxWidth * 0.6
            const fmC3 = boxWidth * 0.2

            pdf.setFillColor(...colors.labelBg)
            pdf.rect(boxX, y, fmC1, rowH, "FD")
            pdf.setFontSize(7)
            pdf.text("Parentesco", boxX + 2, y + 3)

            pdf.setFillColor(...colors.labelBg) // Re-set fill color
            pdf.rect(boxX + fmC1, y, fmC2, rowH, "FD")
            pdf.text("Apellidos y Nombres", boxX + fmC1 + 2, y + 3)

            pdf.setFillColor(...colors.labelBg) // Re-set fill color
            pdf.rect(boxX + fmC1 + fmC2, y, fmC3, rowH, "FD")
            pdf.text("Fecha Nacimiento", boxX + fmC1 + fmC2 + 2, y + 3)
            y += rowH

            // Filas datos
            const parentescos = document.getElementsByName("parentesco[]")
            const nombresFam = document.getElementsByName("apellidosNombres[]")
            const fechasFam = document.getElementsByName("fechaNacimiento[]")
            const rowCount = Math.max(parentescos.length, 5)

            for (let i = 0; i < rowCount; i++) {
                checkPageBreak(rowH)
                const par = parentescos[i]?.value || ""
                const nom = nombresFam[i]?.value || ""
                const fec = formatDateToDMY(fechasFam[i]?.value || "")

                pdf.setFillColor(255);
                pdf.rect(boxX, y, fmC1, rowH); pdf.text(par.toUpperCase(), boxX + 2, y + 3)
                pdf.rect(boxX + fmC1, y, fmC2, rowH); pdf.text(nom.toUpperCase(), boxX + fmC1 + 2, y + 3)
                pdf.rect(boxX + fmC1 + fmC2, y, fmC3, rowH); pdf.text(fec, boxX + fmC1 + fmC2 + 2, y + 3)
                y += rowH
            }
            // ========== CONFORMIDAD ==========
            checkPageBreak(60)
            drawSectionTitle("MI CONFORMIDAD CON LA DECLARACION JURADA", y)
            y += 5 // Corregido overlap (4->5)

            pdf.setFontSize(7)
            pdf.setFont(undefined, "normal")
            const confText = "De acuerdo con lo dispuesto por mi empleador por norma interna, cumpliré con mi obligación de actualizar cada 12 meses esta Declaración Jurada y también hacerlo, cuando varíe cualquiera de mis datos registrados, asumiendo la responsabilidad en caso de incumplimiento."
            const splitConf = pdf.splitTextToSize(confText, boxWidth - 4)

            const confBoxH = splitConf.length * 4 + 5
            pdf.rect(boxX, y, boxWidth, confBoxH)
            pdf.text(splitConf, boxX + 2, y + 4)
            y += confBoxH

            // Firmas
            // Calcular espacio restante para firmas antes del pie de pagina
            // pageHeight - marginBottom - rowH (footer) - y actual
            const espacioDisponible = pageHeight - marginBottom - rowH - y - 2
            const firmaH = Math.max(30, espacioDisponible) // Usar todo el espacio, minimo 30mm
            const halfW = boxWidth / 2

            pdf.rect(boxX, y, halfW, firmaH)
            pdf.rect(boxX + halfW, y, halfW, firmaH)

            pdf.setFont(undefined, "bold")
            pdf.setFontSize(6.5)

            const footerY = y + firmaH - 5
            pdf.text("Firma Registrada", boxX + halfW / 2, footerY, { align: "center" })
            pdf.text("GRANDE Y CLARA SIMILAR AL DNI", boxX + halfW / 2, footerY + 2.5, { align: "center" })

            pdf.text("Huella Registrada", boxX + halfW + halfW / 2, footerY, { align: "center" })
            pdf.text("INDICE DERECHO", boxX + halfW + halfW / 2, footerY + 2.5, { align: "center" })

            y += firmaH

            // Barra inferior final (si entra)
            if (y + rowH <= pageHeight - marginBottom) {
                pdf.setFillColor(...colors.labelBg)
                pdf.rect(boxX, y, 40, rowH, "FD")
                pdf.text("Fecha de la declaración", boxX + 2, y + 4)

                const fechaHoy = new Date().toLocaleDateString("es-PE")
                pdf.setFillColor(255)
                pdf.rect(boxX + 40, y, 40, rowH, "FD")
                pdf.setFont(undefined, "normal")
                pdf.text(fechaHoy, boxX + 42, y + 4)

                pdf.setFillColor(...colors.labelBg)
                pdf.setFont(undefined, "bold")
                pdf.rect(boxX + 80, y, 20, rowH, "FD")
                pdf.text("Nombre", boxX + 82, y + 4)

                pdf.setFillColor(255)
                pdf.rect(boxX + 100, y, boxWidth - 100, rowH, "FD")
                pdf.text(nombres, boxX + 102, y + 4)
            }


            async function drawLogo(x, y, w, h) {
                if (!window.logoUrl) {
                    // Fallback
                    pdf.setFontSize(9); pdf.setTextColor(0);
                    pdf.setFont(undefined, "bold");
                    pdf.text("SOLMAR", x + w / 2, y + h / 2, { align: "center" });
                    return;
                }
                try {
                    const response = await fetch(window.logoUrl);
                    const blob = await response.blob();
                    const reader = new FileReader();
                    await new Promise(resolve => {
                        reader.onload = (e) => {
                            pdf.addImage(e.target.result, "PNG", x + 1, y + 1, w - 2, h - 2);
                            resolve();
                        };
                        reader.readAsDataURL(blob);
                    });
                } catch (e) {
                    console.error("error logo", e);
                    pdf.text("SOLMAR", x + w / 2, y + h / 2, { align: "center" });
                }
            }

            window.open(pdf.output('bloburl'), '_blank');
        } catch (error) {
            console.error("Error al generar PDF:", error);
            Swal.fire({
                icon: 'error',
                title: 'Error de PDF',
                text: 'Hubo un error al generar el documento: ' + error.message,
            });
        }
    }

});