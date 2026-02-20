
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

            // Helper: fitText (Tamaño estandarizado a 8)
            function fitText(text, maxWidth, initialFontSize = 8, minFontSize = 6) {
                pdf.setFontSize(initialFontSize)
                let textWidth = pdf.getTextWidth(text)
                let currentSize = initialFontSize
                while (textWidth > maxWidth && currentSize > minFontSize) {
                    currentSize -= 0.3
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

                // 1) Fill areas (sin borde)
                pdf.setFillColor(...colors.labelBg)
                pdf.rect(x, fieldY, labelWidth, inputHeight, "F")
                pdf.setFillColor(255, 255, 255)
                pdf.rect(x + labelWidth, fieldY, valueWidth, inputHeight, "F")

                // 2) UN solo borde exterior + divisor interno
                pdf.setDrawColor(...colors.borderColor)
                pdf.setLineWidth(0.10)
                // Borde exterior: solo lados necesarios
                // Borde superior solo para la primera fila o si omitTopBorder no está activo
                if (!(arguments.length > 7 && arguments[7])) {
                    pdf.line(x, fieldY, x + width, fieldY); // arriba
                }
                pdf.line(x, fieldY, x, fieldY + inputHeight); // izquierda
                // Borde derecho solo si omitRightBorder no está activo
                if (!(arguments.length > 8 && arguments[8])) {
                    pdf.line(x + width, fieldY, x + width, fieldY + inputHeight); // derecha
                }
                pdf.line(x, fieldY + inputHeight, x + width, fieldY + inputHeight); // abajo
                // Línea divisoria fina entre etiqueta gris y campo blanco
                // Línea divisoria fina entre etiqueta gris y campo blanco solo si omitRightBorder no está activo
                if (!(arguments.length > 8 && arguments[8])) {
                    pdf.line(x + labelWidth, fieldY, x + labelWidth, fieldY + inputHeight);
                }

                // Label text
                pdf.setFont("Arial", "normal")
                pdf.setTextColor(...colors.labelText)
                pdf.setFontSize(8)
                const maxLabelW = labelWidth - 2
                const labelTextWidth = pdf.getTextWidth(label)
                if (labelTextWidth <= maxLabelW) {
                    pdf.text(label, x + labelPadding, fieldY + inputHeight / 2 + 1, { align: "left" })
                } else {
                    const labelLines = pdf.splitTextToSize(label, maxLabelW)
                    const lblLineH = 8 * 0.3527 * 1.15
                    const lblBlockH = labelLines.length * lblLineH
                    const lblY = fieldY + (inputHeight - lblBlockH) / 2 + lblLineH
                    pdf.text(labelLines, x + labelPadding, lblY, { align: "left", lineHeightFactor: 1.15 })
                }

                // Value text
                pdf.setFont("Arial", "normal")
                pdf.setTextColor(...colors.inputText)
                const maxValW = valueWidth - (alignValue === "center" ? 1 : 2)
                const valFontSize = fitText(valStr, maxValW, 8, 6)
                pdf.setFontSize(valFontSize)
                const textY = fieldY + inputHeight / 2 + 1
                const valX = alignValue === "center" ? x + labelWidth + valueWidth / 2 : x + labelWidth + 1
                pdf.text(valStr, valX, textY, { maxWidth: maxValW, align: alignValue })
            }

            function drawSectionTitle(title, yPos) {
                pdf.setFillColor(...colors.sectionBg)
                pdf.rect(boxX, yPos, boxWidth, 5, "F") // 5mm altura header
                pdf.setDrawColor(...colors.borderColor)
                pdf.setLineWidth(0.10)
                pdf.rect(boxX, yPos, boxWidth, 5)

                pdf.setFontSize(8)
                pdf.setFont("Arial", "bold")
                pdf.setTextColor(...colors.sectionText)
                pdf.text(title, boxX + boxWidth / 2, yPos + 3, { align: "center" })
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
            await drawLogo(boxX, y, logoW, headerH)

            // Title
            const titleX = boxX + logoW

            pdf.setFontSize(10)
            pdf.setTextColor(200, 0, 0)
            pdf.setFont("Arial", "bold")
            pdf.text("SISTEMA INTEGRADO SOLMAR – SISOLMAR", titleX + titleW / 2, y + 6, { align: "center" })

            pdf.setFontSize(14) // Aumentado significativamente
            pdf.setTextColor(0, 0, 0)
            pdf.text("DECLARACION JURADA DEL TRABAJADOR", titleX + titleW / 2, y + 13, { align: "center" })

            // Code RH 02
            const codeX = titleX + titleW
            pdf.setFillColor(255, 255, 255)
            pdf.rect(codeX, y, codeW, headerH, "F")

            // UN solo borde exterior + divisores internos del header
            pdf.setDrawColor(0); pdf.setLineWidth(0.2);
            pdf.rect(boxX, y, boxWidth, headerH)
            pdf.line(boxX + logoW, y, boxX + logoW, y + headerH)
            pdf.line(codeX, y, codeX, y + headerH)
            pdf.setFontSize(18) // "Aumentado"
            pdf.setFont(undefined, "bold")
            pdf.setTextColor(0)
            pdf.text("RH 02", codeX + codeW / 2, y + 11, { align: "center" }) // Centered vertically approx

            y += headerH // Eliminar espacio extra

            // Declaración Texto (Dinámica con ajuste)
            const nombres = (document.getElementById("nombres_apellidos")?.value || "").toUpperCase().trim()
            const dni = document.getElementById("dni")?.value || "".trim()

            pdf.setFontSize(8)
            const lineHeight = 3.5
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
            pdf.setDrawColor(0); pdf.setLineWidth(0.15);
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
            const fotoH = rowH * 6 // 6 filas (Incluye Afiliacion)
            pdf.setDrawColor(0); pdf.setLineWidth(0.15);
            pdf.rect(boxX + colMain, y, colFoto, fotoH)
            pdf.setFontSize(8); pdf.setFont(undefined, "normal"); pdf.setTextColor(150);
            pdf.text("FOTO", boxX + colMain + colFoto / 2, y + fotoH / 2, { align: "center" })
            y += rowH

            // Fila 2: DNI...
            const w1 = colMain / 4
            drawField("DNI", dni, boxX, w1, y, rowH, 0.3)
            drawField("Caduca", document.getElementById("caduca")?.value || "", boxX + w1, boxWidth * 0.381 - w1, y, rowH, 0.461)
            drawField("Estado Civil", document.getElementById("estado_civil")?.value || "", boxX + boxWidth * 0.381, boxWidth * 0.6279 - boxWidth * 0.381, y, rowH, 0.589)
            drawField("Sexo", document.getElementById("sexo")?.value || "", boxX + boxWidth * 0.6279, colMain - boxWidth * 0.6279, y, rowH, 0.55)
            y += rowH

            // Fila 3: Fecha...
            const w2 = colMain / 2
            drawField("Fecha Nacimiento", formatDateToDMY(document.getElementById("fecha_nacimiento")?.value), boxX, boxWidth * 0.381, y, rowH, 0.394)
            drawField("Ciudad", document.getElementById("provincia_actual")?.options[document.getElementById("provincia_actual")?.selectedIndex]?.text || "", boxX + boxWidth * 0.381, boxWidth * 0.6279 - boxWidth * 0.381, y, rowH, 0.589)
            y += rowH

            // Fila 4: Tipo Sangre...
            const w3 = colMain / 4
            drawField("Tipo Sangre", document.getElementById("tipo_sangre")?.value || "", boxX, w3, y, rowH, 0.735)
            drawField("Peso (Kg.)", document.getElementById("peso")?.value || "", boxX + w3, boxWidth * 0.381 - w3, y, rowH, 0.461)
            drawField("Talla (Mt.)", document.getElementById("talla")?.value || "", boxX + boxWidth * 0.381, boxWidth * 0.6279 - boxWidth * 0.381, y, rowH, 0.589)
            drawField("Celular", document.getElementById("celular")?.value || "", boxX + boxWidth * 0.6279, colMain - boxWidth * 0.6279, y, rowH, 0.55)
            y += rowH

            // Fila 5: Correo...
            const wMail = w3 * 3
            const wWsp = w3
            drawField("Correo electrónico", document.getElementById("correo")?.value || "", boxX, boxWidth * 0.6279, y, rowH, 0.239)
            drawField("WhatsApp", document.getElementById("whatsapp")?.value || "", boxX + boxWidth * 0.6279, colMain - boxWidth * 0.6279, y, rowH, 0.55)
            y += rowH

            // Fila 6: Afiliacion Texto (Ajustado a colMain para dejar espacio a Foto)
            // Etiqueta debe terminar alineada con fin de etiqueta Talla (Mt.)
            const row6W = colMain
            const row6LabelW = boxWidth * 0.5264 // Alineado con inicio de Carrera
            const row6InputW = row6W - row6LabelW

            pdf.setFillColor(220); pdf.rect(boxX, y, row6LabelW, rowH, "F");
            pdf.setFillColor(255); pdf.rect(boxX + row6LabelW, y, row6InputW, rowH, "F");
            pdf.setDrawColor(0); pdf.setLineWidth(0.15);
            // Dibujar solo el borde inferior, izquierdo y derecho, sin doble trazo
            pdf.line(boxX, y, boxX + row6W, y); // borde superior
            pdf.line(boxX, y, boxX, y + rowH); // borde izquierdo
            pdf.line(boxX + row6W, y, boxX + row6W, y + rowH); // borde derecho
            pdf.line(boxX, y + rowH, boxX + row6W, y + rowH); // borde inferior
            pdf.line(boxX + row6LabelW, y, boxX + row6LabelW, y + rowH); // divisoria interna
            pdf.setTextColor(0); pdf.setFont(undefined, "normal"); pdf.setFontSize(8);
            pdf.text("No estoy afiliado a ninguna AFP o ONP y deseo afiliarme a:", boxX + 2, y + 4)
            y += rowH

            // Fila 7: AFP/ONP
            const sysPrev = document.getElementById("sistema_previsional")?.value || ""
            const isAFP = sysPrev.includes("AFP")
            const isONP = sysPrev.includes("ONP")
            drawField("Estoy afiliado a la AFP", isAFP ? "X" : "", boxX, boxWidth * 0.5264, y, rowH, 0.3875, "center")
            drawField("Estoy afiliado a la ONP", isONP ? "X" : "", boxX + boxWidth * 0.5264, boxWidth * 0.4736, y, rowH, 0.441, "center")
            y += rowH

            // Fila 8: Educacion
            // Helper para auto-ajuste de texto (Centrado y escalado)
            const drawAutoFitField = (label, value, x, w, y, h, labelPct) => {
                const labelW = w * labelPct
                const valW = w - labelW

                // 1) Fill areas (sin borde)
                pdf.setFillColor(220) // Gris
                pdf.rect(x, y, labelW, h, "F")
                pdf.setFillColor(255)
                pdf.rect(x + labelW, y, valW, h, "F")

                // 2) UN solo borde exterior + divisor interno
                pdf.setDrawColor(0)
                pdf.setLineWidth(0.15)
                pdf.rect(x, y, w, h)
                pdf.line(x + labelW, y, x + labelW, y + h)

                // Label — tamaño estandarizado 8
                pdf.setFont(undefined, "normal")
                pdf.setTextColor(0)
                const lblFontSize = fitText(label, labelW - 2, 8, 6)
                pdf.setFontSize(lblFontSize)
                const maxLabelW = labelW - 2
                const labelLines = pdf.splitTextToSize(label, maxLabelW)
                const lblLineH = lblFontSize * 0.3527 * 1.15
                const lblBlockH = labelLines.length * lblLineH
                const lblY = labelLines.length === 1
                    ? y + h / 2 + 1
                    : y + (h - lblBlockH) / 2 + lblLineH
                pdf.text(labelLines, x + labelW / 2, lblY, { align: "center", lineHeightFactor: 1.15 })
                pdf.setFont(undefined, "normal")

                if (!value) return

                // Auto-fit: tamaño estandarizado 8
                let fontSize = 8
                pdf.setFontSize(fontSize)
                const maxValW = valW - 2 // padding

                while (pdf.getTextWidth(value) > maxValW && fontSize > 6) {
                    fontSize -= 0.3
                    pdf.setFontSize(fontSize)
                }

                // Si no cabe en 1 linea → multilinea capped a 2 lineas max
                const MAX_LINES = 2
                let lines = [value]

                if (pdf.getTextWidth(value) > maxValW) {
                    fontSize = 6
                    pdf.setFontSize(fontSize)
                    const allLines = pdf.splitTextToSize(value, maxValW)

                    if (allLines.length <= MAX_LINES) {
                        lines = allLines
                    } else {
                        // Truncar en linea 2 con "..."
                        lines = allLines.slice(0, MAX_LINES)
                        let last = lines[MAX_LINES - 1]
                        while (pdf.getTextWidth(last + "...") > maxValW && last.length > 1) {
                            last = last.slice(0, -1)
                        }
                        lines[MAX_LINES - 1] = last + "..."
                    }
                }

                // Posicion segura dentro del cuadro
                const textX = x + labelW + valW / 2
                const textY = lines.length === 1
                    ? y + h / 2 + 1   // centrado vertical 1 linea
                    : y + 2           // top-aligned multilinea (2 lineas caben en 6.5mm)

                pdf.text(lines, textX, textY, { align: "center", lineHeightFactor: 1.1 })
            }

            // Fila 8: Educacion - 4 columnas - Alineación con fila inferior (Embargos/BCP)
            // Institución inicia donde termina el label de "Embargos en inst. financieras" (embW*0.60 = 22.5%)
            const col1 = boxWidth * 0.285  // Grado de Instrucción (28.5% - alineado con fin label Embargos)
            const col2 = boxWidth * 0.2414  // Institución - termina alineado con fin label BCP
            const col3 = (boxWidth * 0.5264 + boxWidth * 0.4736 * 0.441) - col1 - col2  // Carrera: hasta fin label ONP
            const col4 = boxWidth - (boxWidth * 0.5264 + boxWidth * 0.4736 * 0.441)       // Año de egreso: alineado fin label ONP

            drawAutoFitField("Grado de Instrucción", document.getElementById("grado_instruccion")?.options[document.getElementById("grado_instruccion")?.selectedIndex]?.text || "", boxX, col1, y, rowH, 0.526)
            drawAutoFitField("Institución", document.getElementById("institucion")?.options[document.getElementById("institucion")?.selectedIndex]?.text || "", boxX + col1, col2, y, rowH, 0.398)
            drawAutoFitField("Carrera", document.getElementById("carrera")?.options[document.getElementById("carrera")?.selectedIndex]?.text || "", boxX + col1 + col2, col3, y, rowH, 0.486)
            drawField("Año de egreso", document.getElementById("anio_egreso")?.value || "", boxX + col1 + col2 + col3, col4, y, rowH, 0.50)
            y += rowH

            // Fila 9: Embargos
            const embW = boxWidth * 0.381      // Embargos: 38.1% (alineado con fin label Institución)
            const interbankStart = col1 + col2 + col3 * 0.486  // Inicio INTERBANK alineado con fin de etiqueta gris de Carrera
            const bcpW = interbankStart - embW
            const bcpLabelRatio = ((wMail - embW) * 0.63) / bcpW  // Preserva posición exacta del label gris de BCP
            const interbankW = boxWidth - interbankStart

            drawField("Embargos en instituciones financieras", document.getElementById("embargos")?.value || "", boxX, embW, y, rowH, 0.75)
            drawField("Cuenta sueldo BCP", "", boxX + embW, bcpW, y, rowH, bcpLabelRatio)
            drawField("Cuenta sueldo INTERBANK", "", boxX + interbankStart, interbankW, y, rowH, 0.644)
            y += rowH

            // Fila 10: Direccion Actual
            drawField("Dirección Actual", document.getElementById("direccion_actual")?.value || "", boxX, boxWidth, y, rowH, 0.15)
            y += rowH

            // Fila 11: Direccion DNI
            drawField("Dirección DNI", document.getElementById("direccion_dni")?.value || "", boxX, boxWidth, y, rowH, 0.15)
            y += rowH

            // Fila 12: Emergencia 1
            drawField("En caso de Emergencia llamar a", document.getElementById("contacto_emergencia")?.value || "", boxX, boxWidth, y, rowH, 0.286)
            y += rowH

            // Fila 13: Emergencia 2 - 50/50
            const wCelEmergencia = boxWidth * 0.5264
            const wParEmergencia = boxWidth * 0.4736
            drawField("Número de celular", document.getElementById("celular_emergencia")?.value || "", boxX, wCelEmergencia, y, rowH, 0.403)
            drawField("Parentesco", document.getElementById("parentesco_emergencia")?.value || "", boxX + wCelEmergencia, wParEmergencia, y, rowH, 0.25)
            y += rowH
            // ========== DATOS LABORALES ==========
            checkPageBreak(5 * rowH + 5 + 3)
            drawSectionTitle("MIS DATOS LABORALES", y)
            y += 5 // Corregido overlap (4->5)

            // Fila 1
            drawField("Profesión u Ocupación Principal", "", boxX, boxWidth * 0.5264, y, rowH, 0.475)
            drawField("Tiempo Experiencia", "", boxX + boxWidth * 0.5264, boxWidth * 0.4736, y, rowH, 0.4)
            y += rowH

            // Fila 2
            drawField("Familiar en la Empresa", "", boxX, boxWidth * 0.25, y, rowH, 0.816)
            drawField("Nombre Completo", "", boxX + boxWidth * 0.25, boxWidth * 0.46584, y, rowH, 0.3)
            drawField("Parentesco", "", boxX + boxWidth * 0.71584, boxWidth * 0.28416, y, rowH, 0.4)
            y += rowH

            // Fila 3: SMO...
            const wLab3 = boxWidth / 6
            drawField("SMO", document.getElementById("smo")?.value || "", boxX, wLab3, y, rowH, 0.4)
            drawField("Institución", document.getElementById("institucion_laboral")?.value || "", boxX + wLab3 * 0.75, wLab3 * 1.25, y, rowH, 0.379)
            drawField("Nº Brevete", document.getElementById("brevete")?.value || "", boxX + wLab3 * 1.85, wLab3 * 1.15, y, rowH, 0.522)
            drawField("Clase", document.getElementById("clase_brevete")?.value || "", boxX + wLab3 * 3, wLab3, y, rowH, 0.4)
            drawField("Tipo", "", boxX + wLab3 * 4, wLab3, y, rowH, 0.289)
            drawField("Vehículo Propio", document.getElementById("vehiculo_propio")?.value || "", boxX + boxWidth * 0.755, boxWidth * 0.245, y, rowH, 0.52)
            y += rowH

            // Fila 4 - Duración alineada con inicio de Interbank (62.5%)
            drawField("Empresa Anterior", document.getElementById("empresa_anterior")?.value || "", boxX, boxWidth * 0.375, y, rowH, 0.40)
            // El campo blanco de Cargo se extiende hasta el inicio de Duración
            const cargoStart = boxX + wLab3 * 1.85;
            const duracionStart = boxX + boxWidth * 0.625;
            drawField("Cargo", document.getElementById("cargo_anterior")?.value || "", cargoStart, duracionStart - cargoStart, y, rowH, 0.25)
            drawField("Duración", document.getElementById("tiempo_servicio_anterior")?.value || "", boxX + boxWidth * 0.625, boxWidth * 0.375, y, rowH, 0.25, undefined, undefined, true)
            y += rowH

            // Fila 5
            drawField("Profesión u Ocupación Alterna 1", "", boxX, boxWidth / 2, y, rowH, 0.45)
            drawField("Profesión u Ocupación Alterna 2", "", boxX + boxWidth / 2, boxWidth / 2, y, rowH, 0.45)
            y += rowH

            // ========== DATOS FAMILIARES ==========
            checkPageBreak(40)
            drawSectionTitle("MIS DATOS FAMILIARES", y)
            y += 5 // Corregido overlap (4->5)

            // Headers - Fecha Nacimiento más estrecha con texto en 2 líneas
            const fmC1 = boxWidth * 0.15
            const fmC2 = boxWidth * 0.70
            const fmC3 = boxWidth * 0.15
            const fmHeaderH = rowH * 1.3 // Altura extra para 2 líneas en header

            // Fill headers (sin borde)
            pdf.setFillColor(...colors.labelBg)
            pdf.rect(boxX, y, fmC1, fmHeaderH, "F")
            pdf.rect(boxX + fmC1, y, fmC2, fmHeaderH, "F")
            pdf.rect(boxX + fmC1 + fmC2, y, fmC3, fmHeaderH, "F")
            // UN solo borde exterior + divisores internos
            pdf.setDrawColor(0); pdf.setLineWidth(0.10);
            pdf.rect(boxX, y, boxWidth, fmHeaderH)
            pdf.line(boxX + fmC1, y, boxX + fmC1, y + fmHeaderH)
            pdf.line(boxX + fmC1 + fmC2, y, boxX + fmC1 + fmC2, y + fmHeaderH)
            // Textos
            pdf.setFontSize(8)
            pdf.setFont(undefined, "normal")
            pdf.text("Parentesco", boxX + fmC1 / 2, y + fmHeaderH / 2 + 1, { align: "center" })
            pdf.text("Apellidos y Nombres", boxX + fmC1 + fmC2 / 2, y + fmHeaderH / 2 + 1, { align: "center" })
            // Fecha Nacimiento en 2 líneas
            const fnLines = pdf.splitTextToSize("Fecha Nacimiento", fmC3 - 4)
            pdf.text(fnLines, boxX + fmC1 + fmC2 + fmC3 / 2, y + fmHeaderH / 2 - (fnLines.length > 1 ? 1.5 : 0) + 1, { align: "center" })
            y += fmHeaderH

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

                // UN solo borde exterior + divisores internos
                pdf.setDrawColor(0); pdf.setLineWidth(0.15);
                pdf.rect(boxX, y, boxWidth, rowH)
                pdf.line(boxX + fmC1, y, boxX + fmC1, y + rowH)
                pdf.line(boxX + fmC1 + fmC2, y, boxX + fmC1 + fmC2, y + rowH)
                pdf.setTextColor(0)
                pdf.text(par.toUpperCase(), boxX + 2, y + 3)
                pdf.text(nom.toUpperCase(), boxX + fmC1 + 2, y + 3)
                pdf.text(fec, boxX + fmC1 + fmC2 + 2, y + 3)
                y += rowH
            }
            // ========== CONFORMIDAD ==========
            checkPageBreak(60)
            drawSectionTitle("MI CONFORMIDAD CON LA DECLARACION JURADA", y)
            y += 5 // Corregido overlap (4->5)

            pdf.setFontSize(8)
            pdf.setFont(undefined, "normal")
            const confText = "De acuerdo con lo dispuesto por mi empleador por norma interna, cumpliré con mi obligación de actualizar cada 12 meses esta Declaración Jurada y también hacerlo, cuando varíe cualquiera de mis datos registrados, asumiendo la responsabilidad en caso de incumplimiento."

            const confLines = pdf.splitTextToSize(confText, boxWidth - 4)

            // Calculate height based on new font size and desired line height (3.5)
            const confBoxH = confLines.length * 3.5 + 5 // Adjusted line height from 4 to 3.5
            pdf.setDrawColor(0); pdf.setLineWidth(0.15);
            pdf.rect(boxX, y, boxWidth, confBoxH)
            pdf.text(confLines, boxX + 2, y + 4) // Ajuste Y+4
            y += confBoxH

            // Firmas
            // Calcular espacio restante para firmas antes del pie de pagina
            // pageHeight - marginBottom - rowH (footer) - y actual
            const espacioDisponible = pageHeight - marginBottom - rowH - y - 2
            const firmaH = Math.max(60, espacioDisponible) // Usar todo el espacio, minimo 60mm
            const firmaW = boxWidth * 0.6;
            const huellaW = boxWidth * 0.4;

            pdf.setLineWidth(0.15);
            pdf.rect(boxX, y, boxWidth, firmaH);
            pdf.line(boxX + firmaW, y, boxX + firmaW, y + firmaH);

            pdf.setFont(undefined, "bold");
            pdf.setFontSize(8);

            const footerY = y + firmaH - 5;
            pdf.text("Firma Registrada", boxX + firmaW / 2, footerY, { align: "center" });
            pdf.text("GRANDE Y CLARA SIMILAR AL DNI", boxX + firmaW / 2, footerY + 2.5, { align: "center" });

            pdf.text("Huella Registrada", boxX + firmaW + huellaW / 2, footerY, { align: "center" });
            pdf.text("INDICE DERECHO", boxX + firmaW + huellaW / 2, footerY + 2.5, { align: "center" });

            y += firmaH;

            // Barra inferior final (si entra) - 2 columnas 50/50
            if (y + rowH <= pageHeight - marginBottom) {
                const halfBar = boxWidth / 2
                // Fill areas (sin borde)
                pdf.setFillColor(...colors.labelBg)
                pdf.rect(boxX, y, halfBar * 0.55, rowH, "F")
                pdf.setFillColor(255)
                pdf.rect(boxX + halfBar * 0.55, y, halfBar - halfBar * 0.55, rowH, "F")
                pdf.setFillColor(...colors.labelBg)
                pdf.rect(boxX + halfBar, y, halfBar * 0.3, rowH, "F")
                pdf.setFillColor(255)
                pdf.rect(boxX + halfBar + halfBar * 0.3, y, halfBar - halfBar * 0.3, rowH, "F")
                // UN solo borde exterior + divisores internos
                pdf.setDrawColor(0); pdf.setLineWidth(0.15);
                pdf.rect(boxX, y, boxWidth, rowH)
                pdf.line(boxX + halfBar * 0.55, y, boxX + halfBar * 0.55, y + rowH)
                pdf.line(boxX + halfBar, y, boxX + halfBar, y + rowH)
                pdf.line(boxX + halfBar + halfBar * 0.3, y, boxX + halfBar + halfBar * 0.3, y + rowH)
                // Textos
                pdf.setFont(undefined, "normal")
                pdf.text("Fecha de la declaración", boxX + 2, y + 4)
                const fechaHoy = new Date().toLocaleDateString("es-PE")
                pdf.text(fechaHoy, boxX + halfBar * 0.55 + 2, y + 4)
                pdf.text("Nombre", boxX + halfBar + 2, y + 4)
                pdf.text(nombres, boxX + halfBar + halfBar * 0.3 + 2, y + 4)
            }


            async function drawLogo(x, y, w, h) {
                if (!window.logoUrl) {
                    // Fallback
                    pdf.setFontSize(8); pdf.setTextColor(0);
                    pdf.setFont(undefined, "normal");
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