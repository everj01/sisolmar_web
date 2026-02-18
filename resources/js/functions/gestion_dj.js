
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
            // ... código imagen ...
        } else {
            pdf.setFontSize(6)
            pdf.setTextColor(150, 150, 150)
            pdf.text("FOTO", boxX + col1W + col2W / 2, fieldY + fotoHeight / 2, { align: "center" })
        }

        y += 6

        // Fila 2: DNI, Nacionalidad, Estado Civil
        drawFieldsInRow([
            { label: "DNI", value: dni, width: col1W * 0.33 },
            { label: "Nacionalidad", value: "PERUANA", width: col1W * 0.33 },
            { label: "Estado Civil", value: document.getElementById("estado_civil")?.value, width: col1W * 0.34 },
        ], y)
        y += 6

        // Fila 3: Fecha Nacimiento, Lugar Nacimiento
        drawFieldsInRow([
            { label: "Fecha Nacimiento", value: formatDateToDMY(document.getElementById("fecha_nacimiento")?.value), width: col1W * 0.33 },
            { label: "Lugar Nacimiento", value: "", width: col1W * 0.67 }, // Falta campo en HTML
        ], y)
        y += 6

        // Fila 4: Dirección
        drawFieldsInRow([
            { label: "Dirección", value: document.getElementById("direccion_actual")?.value, width: col1W }
        ], y)
        y += 6

        // Fila 5: Distrito, Provincia, Departamento
        const dist = document.getElementById("distrito_actual")
        const prov = document.getElementById("provincia_actual")
        const dep = document.getElementById("departamento_actual")

        drawFieldsInRow([
            { label: "Distrito", value: dist.options[dist.selectedIndex]?.text || "", width: col1W * 0.33 },
            { label: "Provincia", value: prov.options[prov.selectedIndex]?.text || "", width: col1W * 0.33 },
            { label: "Dpto.", value: dep.options[dep.selectedIndex]?.text || "", width: col1W * 0.34 },
        ], y)
        y += 6

        // Fila 6: Sistema Previsional, Cuenta Sueldo
        drawFieldsInRow([
            { label: "Sistema Previsional", value: document.getElementById("sistema_previsional")?.value, width: col1W * 0.50 },
            { label: "Cuenta Sueldo", value: "", width: col1W * 0.50 },
        ], y)
        y += 10 // Espacio extra después de la sección personal


        // ========== INFORMACIÓN DE CONTACTO ==========
        checkPageBreak(50)
        drawSectionTitle("INFORMACIÓN DE CONTACTO", y)
        y += 7

        drawFieldsInRow([
            { label: "Celular", value: document.getElementById("celular")?.value, width: boxWidth * 0.30 },
            { label: "Correo Electrónico", value: document.getElementById("correo")?.value, width: boxWidth * 0.40 },
            { label: "Teléfono Fijo", value: document.getElementById("telefono_fijo")?.value || "", width: boxWidth * 0.30 }, // Falta en HTML
        ], y)
        y += 6

        drawFieldsInRow([
            { label: "Referencia Domicilio", value: "", width: boxWidth }
        ], y)
        y += 10


        // ========== DATOS LABORALES ==========
        checkPageBreak(80)
        drawSectionTitle("DATOS LABORALES", y)
        y += 7

        // Fila 1: Curso Sucamec, Licencia Arma, Brevete
        drawFieldsInRow([
            { label: "Curso SUCAMEC", value: document.getElementById("curso_sucamec")?.value, width: boxWidth * 0.33 },
            { label: "Licencia Arma", value: document.getElementById("licencia_arma")?.value, width: boxWidth * 0.33 },
            { label: "Brevete", value: document.getElementById("brevete")?.value, width: boxWidth * 0.34 },
        ], y)
        y += 6

        // Fila 2: Tallas
        drawFieldsInRow([
            { label: "Talla Zapatos", value: "", width: boxWidth * 0.25 },
            { label: "Talla Pantalón", value: "", width: boxWidth * 0.25 },
            { label: "Talla Camisa", value: "", width: boxWidth * 0.25 },
            { label: "Estatura", value: document.getElementById("talla")?.value, width: boxWidth * 0.25 },
        ], y)
        y += 6

        // Fila 3: Banco, CTS
        drawFieldsInRow([
            { label: "Banco Sueldo", value: "", width: boxWidth * 0.50 },
            { label: "Cuenta CTS", value: "", width: boxWidth * 0.50 },
        ], y)
        y += 10


        // ========== EDUCACIÓN ==========
        checkPageBreak(50)
        drawSectionTitle("EDUCACIÓN", y)
        y += 7

        drawFieldsInRow([
            { label: "Grado Instrucción", value: document.getElementById("grado_instruccion")?.value, width: boxWidth * 0.40 },
            { label: "Profesión/Ocupación", value: "", width: boxWidth * 0.60 },
        ], y)
        y += 10


        // ========== DATOS FAMILIARES (TABLA) ==========
        checkPageBreak(100)
        drawSectionTitle("DATOS FAMILIARES", y)
        y += 7

        // Encabezados de tabla
        const colW = boxWidth / 4
        pdf.setFillColor(...colors.labelBg)
        pdf.setFontSize(7)
        pdf.setFont(undefined, "bold")
        pdf.setTextColor(0, 0, 0)
        pdf.setDrawColor(0, 0, 0)

        // Headers
        pdf.rect(boxX, y, colW, 6, "FD")
        pdf.text("PARENTESCO", boxX + 2, y + 4)

        pdf.rect(boxX + colW, y, colW * 2, 6, "FD")
        pdf.text("NOMBRES Y APELLIDOS", boxX + colW + 2, y + 4)

        pdf.rect(boxX + colW * 3, y, colW, 6, "FD")
        pdf.text("FECHA NACIMIENTO", boxX + colW * 3 + 2, y + 4)

        y += 6

        // Iterar sobre los familiares
        const parentescos = document.getElementsByName("parentesco[]")
        const nombresFam = document.getElementsByName("apellidosNombres[]")
        const fechasFam = document.getElementsByName("fechaNacimiento[]")

        pdf.setFontSize(7)
        pdf.setFont(undefined, "normal")
        pdf.setFillColor(255, 255, 255) // Fondo blanco para filas

        for (let i = 0; i < parentescos.length; i++) {
            const par = parentescos[i].value
            const nom = nombresFam[i].value
            const fec = formatDateToDMY(fechasFam[i].value)

            if (!par && !nom) continue

            pdf.rect(boxX, y, colW, 6)
            pdf.text(par.toUpperCase(), boxX + 2, y + 4)

            pdf.rect(boxX + colW, y, colW * 2, 6)
            pdf.text(nom.toUpperCase(), boxX + colW + 2, y + 4)

            pdf.rect(boxX + colW * 3, y, colW, 6)
            pdf.text(fec, boxX + colW * 3 + 2, y + 4)

            y += 6
        }

        y += 4

        // ========== CONTACTO EMERGENCIA ==========
        checkPageBreak(40)
        pdf.setFontSize(8)
        pdf.setFont(undefined, "bold")
        pdf.text("EN CASO DE EMERGENCIA LLAMAR A:", boxX, y)
        y += 5

        const contacto = document.getElementById("contacto_emergencia")?.value || ""
        const celEmergencia = document.getElementById("celular_emergencia")?.value || ""
        const parentescoEmergencia = document.getElementById("parentesco_emergencia")?.value || ""

        drawFieldsInRow([
            { label: "Nombre", value: contacto, width: boxWidth * 0.50 },
            { label: "Parentesco", value: parentescoEmergencia, width: boxWidth * 0.25 },
            { label: "Celular", value: celEmergencia, width: boxWidth * 0.25 },
        ], y)

        y += 20

        // ========== PIE DE PÁGINA / FIRMAS ==========
        // checkPageBreak(40)

        // y = pageHeight - marginBottom - 30

        // pdf.setLineWidth(0.5)
        // pdf.line(boxX + 20, y, boxX + 80, y) // Línea firma 1
        // pdf.line(boxX + 110, y, boxX + 170, y) // Línea firma 2

        // pdf.setFontSize(8)
        // pdf.text("FIRMA DEL TRABAJADOR", boxX + 30, y + 5)
        // pdf.text("HUELLA DIGITAL", boxX + 130, y + 5)


        // pdf.save("declaracion_jurada.pdf")
        window.open(pdf.output('bloburl'), '_blank');
    }

});