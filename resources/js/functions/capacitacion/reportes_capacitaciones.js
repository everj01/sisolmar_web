import axios from "axios";
import ExcelJS from "exceljs";
import { saveAs } from "file-saver";

async function _cargarLogoExcel(workbook) {
    try {
        const response = await fetch("/images/logo_sol.png");
        const arrayBuffer = await response.arrayBuffer();
        return workbook.addImage({ buffer: arrayBuffer, extension: "png" });
    } catch (e) {
        console.error("Error cargando logo:", e);
        return null;
    }
}

function _estiloEncabezadoExcel(cell) {
    cell.font = { bold: true, color: { argb: "FFFFFFFF" } };
    cell.fill = {
        type: "pattern",
        pattern: "solid",
        fgColor: { argb: "FF1F4E79" },
    };
    cell.alignment = { vertical: "middle", horizontal: "center" };
    cell.border = {
        top: { style: "thin" },
        left: { style: "thin" },
        bottom: { style: "thin" },
        right: { style: "thin" },
    };
}

function _estiloDatoExcel(cell) {
    cell.border = {
        top: { style: "thin" },
        left: { style: "thin" },
        bottom: { style: "thin" },
        right: { style: "thin" },
    };
    cell.alignment = { vertical: "middle", horizontal: "left", wrapText: true };
}

function _blobExcel(buffer) {
    return new Blob([buffer], {
        type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    });
}

function _cargarImagen(url) {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = url;
    });
}

async function _fetchSistemas() {
    const { data } = await axios.get("/api/get-capacitacion-areas");
    return data;
}

async function _fetchAreas(sistemaId) {
    if (!sistemaId) return [];
    const { data } = await axios.get(`/api/get-areas-por-sistema/${sistemaId}`);
    return data.success ? data.areas : [];
}

function _ordenarPersonal(datos, columna, direccion) {
    if (columna && direccion) {
        datos.sort((a, b) => {
            const valA = (a[columna] || "").toString();
            const valB = (b[columna] || "").toString();
            const cmp = valA.localeCompare(valB, "es", { sensitivity: "base" });
            return direccion === "asc" ? cmp : -cmp;
        });
    } else {
        datos.sort((a, b) =>
            (a.NombreCompleto || "").localeCompare(
                b.NombreCompleto || "",
                "es",
                { sensitivity: "base" },
            ),
        );
    }
    return datos;
}

function _ordenarCursosConFechas(datos, columna, direccion, obtenerValor) {
    if (columna && direccion) {
        datos.sort((a, b) => {
            if (columna === "FechaInicio" || columna === "FechaFin") {
                const va = obtenerValor(a, columna);
                const vb = obtenerValor(b, columna);
                const na = va == null ? -Infinity : va;
                const nb = vb == null ? -Infinity : vb;
                return (direccion === "asc" ? 1 : -1) * (na - nb);
            }
            const valA = obtenerValor(a, columna);
            const valB = obtenerValor(b, columna);
            const cmp = valA.localeCompare(valB, "es", {
                sensitivity: "base",
            });
            return direccion === "asc" ? cmp : -cmp;
        });
    } else {
        datos.sort((a, b) =>
            (a.NombreCurso || "").localeCompare(b.NombreCurso || "", "es", {
                sensitivity: "base",
            }),
        );
    }
    return datos;
}

export default document.addEventListener("alpine:init", () => {
    Alpine.data("reportesApp", () => ({
        abrirModalReporte() {
            window.dispatchEvent(new CustomEvent("abrir-reporte"));
        },
        abrirModalCursosArea() {
            window.dispatchEvent(new CustomEvent("abrir-cursos-area"));
        },
        abrirModalRecordPersonal() {
            window.dispatchEvent(new CustomEvent("abrir-record-personal"));
        },
        abrirModalHistorial() {
            window.dispatchEvent(new CustomEvent("abrir-historial-reportes"));
        },
    }));

    Alpine.data("modalReportePorCapacitacion", () => ({
        open: false,
        view: "filters",

        selectedCurso: "",
        selectedPeriodo: "",
        selectedFechaInicio: "",
        selectedFechaFin: "",
        selectedEstado: "",
        selectedSucursal: "",

        cursos: [],
        todosLosCursos: [],
        periodos: [],
        fechasInicio: [],
        fechasFin: [],
        sucursales: [],

        personal: [],
        totalPersonal: 0,
        loadingPersonal: false,
        loadingCursos: false,
        loadingSucursales: false,

        currentPage: 1,
        perPage: 15,

        sortColumn: null,
        sortDirection: null,

        cacheReportes: {},

        async init() {
            window.addEventListener("abrir-reporte", () => {
                this.abrir();
            });

            await this.cargarCursos();
            await this.cargarSucursales();
        },

        async cargarSucursales() {
            this.loadingSucursales = true;
            try {
                const response = await axios.get("/api/get-sucursales");
                if (response.data.success) {
                    this.sucursales = response.data.sucursales;
                }
            } catch (error) {
                console.error(error);
                this.sucursales = [];
            } finally {
                this.loadingSucursales = false;
            }
        },

        formatearFecha(timestamp) {
            if (!timestamp || timestamp <= 0) {
                return null;
            }

            const fecha = new Date(timestamp * 1000);

            const dia = String(fecha.getDate()).padStart(2, "0");
            const mes = String(fecha.getMonth() + 1).padStart(2, "0");
            const anio = fecha.getFullYear();

            return `${dia}/${mes}/${anio}`;
        },

        obtenerPeriodo(curso) {
            const creacion = this.formatearFecha(curso.timecreated);

            if (!creacion) {
                return null;
            }

            return `${creacion}`;
        },

        obtenerFecha(timestamp) {
            return this.formatearFecha(timestamp);
        },

        async cargarCursos() {
            this.loadingCursos = true;
            try {
                const response = await axios.get(
                    "/api/get-cursos-por-categoria",
                );

                if (response.data.success) {
                    this.todosLosCursos = [...response.data.cursos];
                    this.filtrarCursosPorFecha();
                } else {
                    this.cursos = [];
                    this.todosLosCursos = [];
                }
            } catch (error) {
                console.error(error);

                this.cursos = [];
                this.todosLosCursos = [];
            } finally {
                this.loadingCursos = false;
            }
        },

        formatearFechaISO(timestamp) {
            if (!timestamp || timestamp <= 0) return null;
            const fecha = new Date(timestamp * 1000);
            const dia = String(fecha.getDate()).padStart(2, "0");
            const mes = String(fecha.getMonth() + 1).padStart(2, "0");
            const anio = fecha.getFullYear();
            return `${anio}-${mes}-${dia}`;
        },

        filtrarCursosPorFecha() {
            this.cursos = this.todosLosCursos.filter((curso) => {
                const fechaCreacion = this.formatearFechaISO(curso.timecreated);

                if (
                    this.selectedFechaInicio &&
                    fechaCreacion < this.selectedFechaInicio
                ) {
                    return false;
                }

                if (
                    this.selectedFechaFin &&
                    fechaCreacion > this.selectedFechaFin
                ) {
                    return false;
                }

                return true;
            });

            this.selectedCurso = "";
        },

        abrir() {
            this.open = true;
        },

        cerrar() {
            this.open = false;
            this.view = "filters";

            this.selectedCurso = "";
            this.selectedPeriodo = "";
            this.selectedFechaInicio = "";
            this.selectedFechaFin = "";
            this.selectedEstado = "";
            this.selectedSucursal = "";

            this.cursos = [...this.todosLosCursos];
            this.periodos = [];
            this.fechasInicio = [];
            this.fechasFin = [];

            this.personal = [];
            this.totalPersonal = 0;
            this.currentPage = 1;
            this.loadingPersonal = false;

            this.cacheReportes = {};
        },

        volverAFiltros() {
            this.view = "filters";
            this.personal = [];
            this.totalPersonal = 0;
            this.currentPage = 1;
            this.sortColumn = null;
            this.sortDirection = null;
            this.loadingPersonal = false;
        },

        ordenar(columna) {
            if (this.sortColumn === columna) {
                if (this.sortDirection === "asc") {
                    this.sortDirection = "desc";
                } else {
                    this.sortColumn = null;
                    this.sortDirection = null;
                }
            } else {
                this.sortColumn = columna;
                this.sortDirection = "asc";
            }
            this.currentPage = 1;
        },

        get nombreCurso() {
            if (!this.selectedCurso) return "TODOS LOS CURSOS";
            return (
                this.cursos.find((c) => c.id == this.selectedCurso)?.fullname ||
                ""
            );
        },

        get personalPaginado() {
            if (this.esReportePorCursos) return [];
            const datos = _ordenarPersonal(
                [...this.personal],
                this.sortColumn,
                this.sortDirection,
            );
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            return datos.slice(start, end);
        },

        get totalPages() {
            if (this.esReportePorCursos) return 1;
            return Math.ceil(this.personal.length / this.perPage);
        },

        _escribirEncabezadoExcel(
            sheet,
            logoImageId,
            titulo,
            sucursalNombre,
            totalTexto,
        ) {
            sheet.getRow(1);
            sheet.getRow(2);
            sheet.getRow(3);
            sheet.getRow(4);

            if (logoImageId !== null) {
                sheet.addImage(logoImageId, {
                    tl: { col: 1, row: 0 },
                    br: { col: 3, row: 2 },
                    editAs: "absolute",
                });
            }

            sheet.getRow(1).height = 50;

            sheet.mergeCells("D1:F1");
            const infoCell = sheet.getCell("D1");
            infoCell.value = titulo;
            infoCell.font = {
                bold: true,
                size: 13,
                color: { argb: "FF1F4E79" },
            };
            infoCell.alignment = {
                vertical: "middle",
                horizontal: "right",
            };

            sheet.mergeCells("D2:F2");
            const sucursalCell = sheet.getCell("D2");
            sucursalCell.value = `Sucursal: ${sucursalNombre}`;
            sucursalCell.font = {
                size: 11,
                color: { argb: "FF333333" },
            };
            sucursalCell.alignment = {
                vertical: "middle",
                horizontal: "right",
            };

            sheet.mergeCells("D3:F3");
            const totalCell = sheet.getCell("D3");
            totalCell.value = totalTexto;
            totalCell.font = {
                size: 11,
                color: { argb: "FF333333" },
            };
            totalCell.alignment = {
                vertical: "middle",
                horizontal: "right",
            };

            sheet.getRow(4).height = 8;
        },

        _agregarFilasPersonalExcel(sheet, personal, startRow) {
            const headers = [
                "#",
                "Código Pers.",
                "Nombre Completo",
                "DNI",
                "Tipo Trabajador",
                "Cargo",
                "Estado",
            ];

            const headerRow = sheet.getRow(startRow);
            headerRow.values = headers;
            headerRow.eachCell((cell) => _estiloEncabezadoExcel(cell));

            const dataOrdenada = [...personal].sort((a, b) =>
                (a.NombreCompleto || "").localeCompare(b.NombreCompleto || ""),
            );

            dataOrdenada.forEach((p, i) => {
                const row = sheet.addRow([
                    i + 1,
                    p.CodigoPers,
                    p.NombreCompleto,
                    p.DNI,
                    p.TipoTrabajador,
                    p.Cargo,
                    p.Estado,
                ]);
                row.eachCell((cell) => _estiloDatoExcel(cell));
            });

            return startRow + 1 + dataOrdenada.length;
        },

        async exportarExcel() {
            const sucursal = this.sucursales.find(
                (s) => s.codigo == this.selectedSucursal,
            );
            const sucursalNombre = sucursal ? sucursal.sucursal : "SUCURSAL";

            const workbook = new ExcelJS.Workbook();
            const logoImageId = await _cargarLogoExcel(workbook);
            const sheet = workbook.addWorksheet("Personal");

            if (this.esReportePorCursos) {
                this._escribirEncabezadoExcel(
                    sheet,
                    logoImageId,
                    "REPORTE GENERAL - TODOS LOS CURSOS",
                    sucursalNombre,
                    `Total general: ${this.totalPersonal} personal(es)`,
                );

                let currentRow = 6;

                this.personal.forEach((grupo, gi) => {
                    if (gi > 0) {
                        currentRow += 1;
                    }

                    const cursoRow = sheet.getRow(currentRow);
                    cursoRow.height = 22;
                    const cursoCell = cursoRow.getCell(1);
                    cursoCell.value = grupo.Curso?.toUpperCase() || "CURSO";
                    cursoCell.font = {
                        bold: true,
                        size: 11,
                        color: { argb: "FF1F4E79" },
                    };
                    cursoCell.alignment = {
                        vertical: "middle",
                    };
                    sheet.mergeCells(`A${currentRow}:F${currentRow}`);
                    currentRow++;

                    const subRow = sheet.getRow(currentRow);
                    subRow.height = 16;
                    const subCell = subRow.getCell(1);
                    subCell.value = `Total: ${grupo.Total} personal(es) - ${sucursalNombre}`;
                    subCell.font = {
                        size: 9,
                        italic: true,
                        color: { argb: "FF666666" },
                    };
                    sheet.mergeCells(`A${currentRow}:F${currentRow}`);
                    currentRow++;

                    currentRow = this._agregarFilasPersonalExcel(
                        sheet,
                        grupo.Personales || [],
                        currentRow,
                    );
                });

                sheet.columns = [
                    { width: 5 },
                    { width: 15 },
                    { width: 40 },
                    { width: 15 },
                    { width: 25 },
                    { width: 30 },
                    { width: 15 },
                ];
            } else {
                const curso = this.cursos.find(
                    (c) => c.id == this.selectedCurso,
                );
                const nombreCurso = curso ? curso.fullname : "reporte";

                this._escribirEncabezadoExcel(
                    sheet,
                    logoImageId,
                    `Curso: ${nombreCurso}`,
                    sucursalNombre,
                    `Total: ${this.personal.length} personal(es)`,
                );

                this._agregarFilasPersonalExcel(sheet, this.personal, 5);

                sheet.columns = [
                    { width: 5 },
                    { width: 15 },
                    { width: 40 },
                    { width: 15 },
                    { width: 25 },
                    { width: 30 },
                    { width: 15 },
                ];

                sheet.autoFilter = {
                    from: `A5`,
                    to: `F5`,
                };
            }

            const buffer = await workbook.xlsx.writeBuffer();
            const blob = _blobExcel(buffer);
            const nombreArchivo = this.esReportePorCursos
                ? `reporte_general_todos_cursos.xlsx`
                : `reporte_${this.cursos.find((c) => c.id == this.selectedCurso)?.fullname || "reporte"}_${this.selectedEstado || "TODOS"}.xlsx`;

            await this.registrarReporteEnHistorial(nombreArchivo, null, blob);

            saveAs(blob, nombreArchivo);

            Swal.fire(
                "Éxito",
                "Reporte exportado a Excel correctamente.",
                "success",
            );
        },

        async exportarPDF() {
            const ventanaPdf = window.open("", "_blank");

            try {
                const { jsPDF } = window.jspdf;

                const doc = new jsPDF({
                    orientation: "landscape",
                    unit: "mm",
                    format: "a4",
                });

                const curso = this.cursos.find(
                    (c) => c.id == this.selectedCurso,
                );

                const nombreCurso = curso ? curso.fullname : "REPORTE GENERAL";

                const logoSol = await _cargarImagen("/images/logo_sol.png");

                const dibujarEncabezado = () => {
                    let logoBottomY = 26;
                    const logoWidth = 60;
                    const startX = 14;

                    if (logoSol) {
                        const ratio = logoSol.height / logoSol.width;

                        const height = logoWidth * ratio;

                        doc.addImage(
                            logoSol,
                            "PNG",
                            startX,
                            10,
                            logoWidth,
                            height,
                        );

                        logoBottomY = 10 + height;
                    }

                    const lineY = logoBottomY + 2;

                    doc.setDrawColor(150, 150, 150);
                    doc.setLineWidth(0.2);
                    doc.line(startX, lineY, startX + 75, lineY);

                    doc.setFont("helvetica", "italic");
                    doc.setFontSize(7);
                    doc.setTextColor(80, 80, 80);

                    doc.text(
                        "Chimbote: Calle Los Laureles Nº206 Urb. La Caleta",
                        startX,
                        lineY + 4,
                    );

                    doc.text("RUC: 20445414833", startX, lineY + 7);

                    return {
                        startX,
                        startY: 66,
                    };
                };

                const sucursal = this.sucursales.find(
                    (s) => s.codigo == this.selectedSucursal,
                );

                const sucursalNombre = sucursal
                    ? sucursal.sucursal.toUpperCase()
                    : "TODOS";

                const generarTablaCurso = (
                    nombreCurso,
                    personalCurso,
                    startX,
                    startY,
                ) => {
                    doc.setTextColor(0, 0, 0);

                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(11);

                    doc.text(
                        (nombreCurso || "CURSO").toUpperCase(),
                        startX,
                        startY - 10,
                    );

                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(9);

                    doc.text(
                        `${sucursalNombre} - ${personalCurso.length} personal(es)`,
                        startX,
                        startY - 4,
                    );

                    const personalOrdenado = [...personalCurso].sort((a, b) =>
                        (a.NombreCompleto || "").localeCompare(
                            b.NombreCompleto || "",
                        ),
                    );

                    const filas = personalOrdenado.map((p) => [
                        p.CodigoPers || "",
                        p.NombreCompleto || "",
                        p.DNI || "",
                        p.TipoTrabajador || "",
                        p.Cargo || "",
                        p.Estado || "",
                    ]);

                    doc.autoTable({
                        startY,
                        head: [
                            [
                                "Código\nPers.",
                                "Nombre Completo",
                                "DNI",
                                "Tipo Trabajador",
                                "Cargo",
                                "Estado",
                            ],
                        ],
                        body: filas,
                        theme: "grid",
                        styles: {
                            fontSize: 8,
                            textColor: [0, 0, 0],
                            lineColor: [0, 0, 0],
                            lineWidth: 0.1,
                            valign: "middle",
                            halign: "center",
                        },
                        headStyles: {
                            fillColor: [253, 245, 230],
                            textColor: [0, 0, 0],
                            fontStyle: "bold",
                            halign: "center",
                        },
                        columnStyles: {
                            0: {
                                cellWidth: 20,
                            },
                            1: {
                                cellWidth: "auto",
                                halign: "left",
                            },
                            2: {
                                cellWidth: 22,
                            },
                            3: {
                                cellWidth: 30,
                            },
                            4: {
                                cellWidth: 45,
                                halign: "left",
                            },
                            5: {
                                cellWidth: 25,
                            },
                        },
                        margin: {
                            left: 14,
                            right: 14,
                        },
                    });
                };

                if (this.esReportePorCursos) {
                    this.personal.forEach((cursoData, index) => {
                        if (index > 0) {
                            doc.addPage();
                        }

                        const { startX, startY } = dibujarEncabezado();

                        generarTablaCurso(
                            cursoData.Curso,
                            cursoData.Personales || [],
                            startX,
                            startY,
                        );
                    });
                } else {
                    const { startX, startY } = dibujarEncabezado();

                    generarTablaCurso(
                        nombreCurso,
                        this.personal,
                        startX,
                        startY,
                    );
                }

                const nombreCursoArchivo = (nombreCurso || "CURSO")
                    .toUpperCase()
                    .replace(/[^\w\s]/g, "")
                    .replace(/\s+/g, "_");

                const nombreArchivo = this.esReportePorCursos
                    ? `REPORTE_POR_CAPAC_TODOS_${this.selectedEstado || "TODOS"}.pdf`
                    : `REPORTE_POR_CAPAC_${nombreCursoArchivo}_${this.selectedEstado || "TODOS"}.pdf`;

                doc.setProperties({
                    title: nombreArchivo,
                });

                const pdfBlob = doc.output("blob");

                // await this.registrarReporteEnHistorial(
                //     nombreArchivo,
                //     pdfBlob,
                //     null,
                // );

                const pdfUrl = URL.createObjectURL(pdfBlob);

                if (ventanaPdf) {
                    ventanaPdf.location.href = pdfUrl;
                }
            } catch (error) {
                if (ventanaPdf) {
                    ventanaPdf.close();
                }

                console.error(error);

                Swal.fire("Error", "No se pudo generar el PDF.", "error");
            }
        },

        async registrarReporteEnHistorial(nombreArchivo, pdfBlob, excelBlob) {
            try {
                const formData = new FormData();
                formData.append("nombre_archivo", nombreArchivo);
                formData.append("descripcion", "");

                if (pdfBlob) {
                    formData.append(
                        "archivo_pdf",
                        pdfBlob,
                        nombreArchivo.replace(/\.pdf$/i, "") + ".pdf",
                    );
                }

                if (excelBlob) {
                    formData.append(
                        "archivo_excel",
                        excelBlob,
                        nombreArchivo.replace(/\.xlsx$/i, "") + ".xlsx",
                    );
                }

                await axios.post(
                    "/api/capacitacion/registrar-reporte",
                    formData,
                    {
                        headers: { "Content-Type": "multipart/form-data" },
                    },
                );

                window.dispatchEvent(
                    new CustomEvent("historial-reportes-actualizado"),
                );
            } catch (error) {
                console.error(
                    "Error al registrar reporte en historial:",
                    error,
                );
            }
        },

        get esReportePorCursos() {
            return (
                !this.selectedCurso &&
                Array.isArray(this.personal) &&
                this.personal.length > 0
            );
        },

        async obtenerPersonal() {
            const cacheKey = [
                this.selectedCurso || "todos",
                this.selectedSucursal || "todas",
                this.selectedEstado,
            ].join("_");

            if (this.cacheReportes[cacheKey]) {
                this.personal = this.cacheReportes[cacheKey].personal;
                this.totalPersonal = this.cacheReportes[cacheKey].total;

                this.currentPage = 1;
                this.view = "personal";

                return;
            }

            this.view = "personal";
            this.personal = [];
            this.loadingPersonal = true;

            const params = {};
            if (this.selectedCurso) {
                params.courseId = this.selectedCurso;
            }
            if (this.selectedSucursal) {
                params.sucursalId = this.selectedSucursal;
            }
            if (this.selectedEstado) {
                params.estado = this.selectedEstado;
            }

            try {
                const response = await axios.get("/api/get-personal-reporte", {
                    params,
                });

                if (response.data.success) {
                    this.personal = response.data.Personales;
                    this.totalPersonal = response.data.Total;

                    this.cacheReportes[cacheKey] = {
                        personal: response.data.Personales,
                        total: response.data.Total,
                    };

                    this.currentPage = 1;
                } else {
                    this.personal = [];
                    this.totalPersonal = 0;
                }
            } catch (error) {
                console.error(error);
                this.personal = [];
                this.totalPersonal = 0;
                Swal.fire(
                    "Error",
                    "No se pudo obtener el personal. Intente nuevamente.",
                    "error",
                );
            } finally {
                this.loadingPersonal = false;
            }
        },
    }));

    Alpine.data("modalReporteDeCapacitaciones", () => ({
        open: false,
        view: "filters",

        selectedSistema: "",
        selectedArea: "",
        selectedFechaInicio: "",
        selectedFechaFin: "",

        sistemas: [],
        areas: [],
        cursosFilas: [],

        loadingSistemas: false,
        loadingAreas: false,
        loadingCursos: false,

        currentPage: 1,
        perPage: 15,

        sortColumn: null,
        sortDirection: null,

        cacheReportes: {},

        async init() {
            window.addEventListener("abrir-cursos-area", () => {
                this.abrir();
            });
            await this.cargarSistemas();
        },

        async cargarSistemas() {
            this.loadingSistemas = true;
            try {
                this.sistemas = await _fetchSistemas();
            } catch (error) {
                console.error(error);
                this.sistemas = [];
            } finally {
                this.loadingSistemas = false;
            }
        },

        async cargarAreas(sistemaId) {
            if (!sistemaId) {
                this.areas = [];
                this.selectedArea = "";
                return;
            }
            this.loadingAreas = true;
            try {
                this.areas = await _fetchAreas(sistemaId);
            } catch (error) {
                console.error(error);
                this.areas = [];
            } finally {
                this.loadingAreas = false;
            }
        },

        formatearFecha(timestamp) {
            if (!timestamp || timestamp <= 0) {
                return "";
            }
            const fecha = new Date(timestamp * 1000);
            const dia = String(fecha.getDate()).padStart(2, "0");
            const mes = String(fecha.getMonth() + 1).padStart(2, "0");
            const anio = fecha.getFullYear();
            return `${dia}/${mes}/${anio}`;
        },

        timestampInicioDia(timestamp) {
            if (!timestamp || timestamp <= 0) {
                return null;
            }
            const fecha = new Date(timestamp * 1000);
            fecha.setHours(0, 0, 0, 0);
            return fecha.getTime();
        },

        parseYmdToTime(ymd) {
            if (!ymd) {
                return null;
            }
            const [y, m, d] = ymd.split("-").map(Number);
            if (!y || !m || !d) {
                return null;
            }
            const fecha = new Date(y, m - 1, d);
            fecha.setHours(0, 0, 0, 0);
            return fecha.getTime();
        },

        cursoSolapaRangoUsuario(curso) {
            const uIni = this.parseYmdToTime(this.selectedFechaInicio);
            const uFin = this.parseYmdToTime(this.selectedFechaFin);

            if (uIni === null && uFin === null) {
                return true;
            }

            let cIni = this.timestampInicioDia(curso.startdate);
            let cFin = this.timestampInicioDia(curso.enddate);

            if (cIni === null && cFin === null) {
                return true;
            }

            if (cIni !== null && cFin === null) {
                cFin = cIni;
            }
            if (cFin !== null && cIni === null) {
                cIni = cFin;
            }

            const rangoIni = uIni !== null ? uIni : -Infinity;
            const rangoFin = uFin !== null ? uFin : Infinity;

            return cIni <= rangoFin && cFin >= rangoIni;
        },

        stripHtml(html) {
            if (!html) {
                return "";
            }
            const tmp = document.createElement("div");
            tmp.innerHTML = html;
            return (tmp.textContent || tmp.innerText || "").trim();
        },

        filaDesdeCurso(curso) {
            const sistema = this.sistemas.find(
                (s) => String(s.codigo) === String(this.selectedSistema),
            );
            const area = this.areas.find(
                (a) => String(a.codModdle) === String(this.selectedArea),
            );

            const iniTs = this.timestampInicioDia(curso.startdate);
            const finTs = this.timestampInicioDia(curso.enddate);

            const responsable = (curso.responsible ?? curso.Responsible ?? "")
                .toString()
                .trim();

            const fechaIniStr = this.formatearFecha(curso.startdate);
            const fechaFinStr = this.formatearFecha(curso.enddate);
            const descripcion = this.stripHtml(curso.summary || "");

            return {
                id: curso.id,
                SistemaGestion:
                    (sistema?.descripcion || "").trim() ||
                    "Sin sistema de gestión",
                Area: (area?.Area || "").trim() || "Sin área",
                NombreCurso:
                    (curso.fullname || "").toString().trim() ||
                    "Sin nombre de curso",
                FechaInicio: fechaIniStr || "Sin fecha de inicio",
                FechaFin: fechaFinStr || "Sin fecha de fin",
                Responsable: responsable || "Sin responsable",
                Descripcion: descripcion || "Sin descripción",
                _sortInicio: iniTs,
                _sortFin: finTs,
            };
        },

        ordenar(columna) {
            if (this.sortColumn === columna) {
                if (this.sortDirection === "asc") {
                    this.sortDirection = "desc";
                } else {
                    this.sortColumn = null;
                    this.sortDirection = null;
                }
            } else {
                this.sortColumn = columna;
                this.sortDirection = "asc";
            }
            this.currentPage = 1;
        },

        valorOrden(fila, columna) {
            if (columna === "FechaInicio") {
                return fila._sortInicio;
            }
            if (columna === "FechaFin") {
                return fila._sortFin;
            }
            return (fila[columna] || "").toString();
        },

        get cursosPaginado() {
            const datos = _ordenarCursosConFechas(
                [...this.cursosFilas],
                this.sortColumn,
                this.sortDirection,
                (fila, col) => this.valorOrden(fila, col),
            );
            const start = (this.currentPage - 1) * this.perPage;
            return datos.slice(start, start + this.perPage);
        },

        get totalPagesCursos() {
            const n = this.cursosFilas.length;
            if (n === 0) {
                return 1;
            }
            return Math.ceil(n / this.perPage);
        },

        formatearYmdADmY(ymd) {
            if (!ymd) {
                return "";
            }
            const parts = String(ymd).split("-");
            if (parts.length !== 3) {
                return String(ymd);
            }
            const [y, m, d] = parts;
            return `${String(d).padStart(2, "0")}/${String(m).padStart(2, "0")}/${y}`;
        },

        get textoRangoFechasHistorial() {
            const ini = this.formatearYmdADmY(this.selectedFechaInicio);
            const fin = this.formatearYmdADmY(this.selectedFechaFin);
            if (ini && fin) {
                return `Del ${ini} al ${fin}`;
            }
            if (ini) {
                return `Desde el ${ini}`;
            }
            if (fin) {
                return `Hasta el ${fin}`;
            }
            return "Todo el período";
        },

        observacionParaExportacion(fila) {
            return fila.Descripcion || "Sin descripción";
        },

        obtenerCursosFilasOrdenadosParaExport() {
            return _ordenarCursosConFechas(
                [...this.cursosFilas],
                this.sortColumn,
                this.sortDirection,
                (fila, col) => this.valorOrden(fila, col),
            );
        },

        async exportarExcelHistorialCursos() {
            if (!this.cursosFilas.length) {
                Swal.fire(
                    "Atención",
                    "No hay cursos para exportar.",
                    "warning",
                );
                return;
            }

            const filasExport = this.obtenerCursosFilasOrdenadosParaExport();
            const sistema = this.sistemas.find(
                (s) => String(s.codigo) === String(this.selectedSistema),
            );
            const area = this.areas.find(
                (a) => String(a.codModdle) === String(this.selectedArea),
            );
            const nombreArea = area ? area.Area : "Área";
            const nombreSistema = sistema
                ? sistema.descripcion
                : "Sistema de gestión";

            const workbook = new ExcelJS.Workbook();

            const logoImageId = await _cargarLogoExcel(workbook);

            const sheet = workbook.addWorksheet("Historial");

            const headerRowNumber = 5;

            sheet.getRow(1);
            sheet.getRow(2);
            sheet.getRow(3);
            sheet.getRow(4);

            if (logoImageId !== null) {
                sheet.addImage(logoImageId, {
                    tl: { col: 1, row: 0 },
                    br: { col: 3, row: 2 },
                    editAs: "absolute",
                });
            }

            sheet.getRow(1).height = 50;

            sheet.mergeCells("D1:H1");

            const infoCell = sheet.getCell("D1");
            infoCell.value = `Área responsable: ${nombreArea}`;
            infoCell.font = {
                bold: true,
                size: 13,
                color: { argb: "FF1F4E79" },
            };
            infoCell.alignment = {
                vertical: "middle",
                horizontal: "right",
            };

            sheet.mergeCells("D2:H2");

            const sistemaCell = sheet.getCell("D2");
            sistemaCell.value = `Sistema de gestión: ${nombreSistema}`;
            sistemaCell.font = {
                size: 11,
                color: { argb: "FF333333" },
            };
            sistemaCell.alignment = {
                vertical: "middle",
                horizontal: "right",
            };

            sheet.mergeCells("D3:H3");

            const totalCell = sheet.getCell("D3");
            totalCell.value = `Total: ${filasExport.length} curso(s) · ${this.textoRangoFechasHistorial}`;
            totalCell.font = {
                size: 11,
                color: { argb: "FF333333" },
            };
            totalCell.alignment = {
                vertical: "middle",
                horizontal: "right",
            };

            sheet.getRow(4).height = 8;

            const headers = [
                "#",
                "Área",
                "Sub área",
                "Capacitación",
                "Fecha inicio",
                "Fecha fin",
                "Responsable",
                "Observaciones",
            ];

            const headerRow = sheet.getRow(headerRowNumber);

            headerRow.values = headers;

            headerRow.eachCell((cell) => _estiloEncabezadoExcel(cell));

            filasExport.forEach((fila, i) => {
                const row = sheet.addRow([
                    i + 1,
                    fila.SistemaGestion,
                    fila.Area,
                    fila.NombreCurso,
                    fila.FechaInicio,
                    fila.FechaFin,
                    fila.Responsable,
                    this.observacionParaExportacion(fila),
                ]);

                row.eachCell((cell) => _estiloDatoExcel(cell));
            });

            sheet.columns = [
                { width: 5 },
                { width: 28 },
                { width: 22 },
                { width: 40 },
                { width: 14 },
                { width: 14 },
                { width: 26 },
                { width: 38 },
            ];

            sheet.autoFilter = {
                from: `A${headerRowNumber}`,
                to: `H${headerRowNumber}`,
            };

            const buffer = await workbook.xlsx.writeBuffer();
            const blob = _blobExcel(buffer);

            const slug = (nombreArea || "historial")
                .toString()
                .replace(/[^\w\-]+/g, "_")
                .slice(0, 40);
            const nombreArchivo = `reporte_historial_${slug}_${new Date().toISOString().slice(0, 10)}.xlsx`;

            await this.registrarReporteEnHistorial(nombreArchivo, null, blob);

            saveAs(blob, nombreArchivo);

            Swal.fire(
                "Éxito",
                "Historial exportado a Excel correctamente.",
                "success",
            );
        },

        async exportarPDFHistorialCursos() {
            if (!this.cursosFilas.length) {
                Swal.fire(
                    "Atención",
                    "No hay cursos para exportar.",
                    "warning",
                );
                return;
            }

            const { jsPDF } = window.jspdf;

            const doc = new jsPDF({
                orientation: "landscape",
                unit: "mm",
                format: "a4",
            });

            const pageWidth = doc.internal.pageSize.getWidth();

            const filasExport = this.obtenerCursosFilasOrdenadosParaExport();

            const sistema = this.sistemas.find(
                (s) => String(s.codigo) === String(this.selectedSistema),
            );
            const area = this.areas.find(
                (a) => String(a.codModdle) === String(this.selectedArea),
            );

            const logoSol = await _cargarImagen("/images/logo_sol.png");

            let logoBottomY = 26;
            const logoWidth = 60;
            const startX = 14;

            if (logoSol) {
                const ratio = logoSol.height / logoSol.width;
                const height = logoWidth * ratio;
                doc.addImage(logoSol, "PNG", startX, 10, logoWidth, height);
                logoBottomY = 10 + height;
            }

            const lineY = logoBottomY + 2;
            doc.setDrawColor(150, 150, 150);
            doc.setLineWidth(0.2);
            doc.line(startX, lineY, startX + 75, lineY);

            doc.setFont("helvetica", "italic");
            doc.setFontSize(7);
            doc.setTextColor(80, 80, 80);
            doc.text(
                "Chimbote: Calle Los Laureles Nº206 Urb. La Caleta",
                startX,
                lineY + 4,
            );
            doc.text("RUC: 20445414833", startX, lineY + 7);

            const title = sistema ? `${sistema.descripcion.toUpperCase()}` : "";
            const subtitle = area ? area.Area : "";

            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(12);

            const titleX = pageWidth - 14;
            doc.text(title, titleX, 40, { align: "right" });
            const titleWidth = doc.getTextWidth(title);
            doc.setLineWidth(0.3);
            doc.setDrawColor(0, 0, 0);
            if (titleWidth > 0) {
                doc.line(titleX - titleWidth, 41, titleX, 41);
            }

            doc.setFont("helvetica", "normal");
            doc.setFontSize(10);
            doc.text(subtitle, titleX, 46, { align: "right" });

            const textoListado = `HISTORIAL - ${filasExport.length} CURSO(S)`;

            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text(textoListado, startX, 56);

            const periodoLinea = this.textoRangoFechasHistorial.toUpperCase();
            doc.setFont("helvetica", "normal");
            doc.setFontSize(9);
            const twPeriodo = pageWidth - startX - 100;
            const lineasPeriodo = doc.splitTextToSize(periodoLinea, twPeriodo);
            doc.text(lineasPeriodo, startX, 61);

            const tableStartY =
                lineasPeriodo.length > 1
                    ? 61 + lineasPeriodo.length * 4.2 + 4
                    : 66;

            const filas = filasExport.map((f, i) => [
                i + 1,
                f.SistemaGestion || "",
                f.Area || "",
                f.NombreCurso || "",
                f.FechaInicio || "",
                f.FechaFin || "",
                f.Responsable || "",
                this.observacionParaExportacion(f),
            ]);

            doc.autoTable({
                startY: tableStartY,
                head: [
                    [
                        "It",
                        "Área",
                        "Sub área",
                        "Capacitación",
                        "Fecha inicio",
                        "Fecha fin",
                        "Responsable",
                        "Observaciones",
                    ],
                ],
                body: filas,
                theme: "grid",
                styles: {
                    fontSize: 8,
                    textColor: [0, 0, 0],
                    lineColor: [0, 0, 0],
                    lineWidth: 0.1,
                    halign: "center",
                    valign: "middle",
                },
                headStyles: {
                    fillColor: [253, 245, 230],
                    textColor: [0, 0, 0],
                    fontStyle: "bold",
                    halign: "center",
                },
                columnStyles: {
                    0: { cellWidth: 10 },
                    1: { cellWidth: 26 },
                    2: { cellWidth: 22 },
                    3: { cellWidth: "auto", halign: "left" },
                    4: { cellWidth: 22 },
                    5: { cellWidth: 22 },
                    6: { cellWidth: 28 },
                    7: { cellWidth: 32, halign: "left" },
                },
                margin: { left: 14, right: 14 },
            });

            const slug = (area?.Area || "historial")
                .toString()
                .replace(/[^\w\-]+/g, "_")
                .slice(0, 40);
            const nombreArchivo = `reporte_historial_${slug}_${new Date().toISOString().slice(0, 10)}.pdf`;

            const pdfBlob = doc.output("blob");

            await this.registrarReporteEnHistorial(
                nombreArchivo,
                pdfBlob,
                null,
            );

            doc.save(nombreArchivo);

            Swal.fire(
                "Éxito",
                "Historial exportado a PDF correctamente.",
                "success",
            );
        },

        async obtenerCursos() {
            if (!this.selectedArea) {
                Swal.fire(
                    "Atención",
                    "Seleccione un área responsable.",
                    "warning",
                );
                return;
            }

            if (
                this.selectedFechaInicio &&
                this.selectedFechaFin &&
                this.selectedFechaInicio > this.selectedFechaFin
            ) {
                Swal.fire(
                    "Atención",
                    "La fecha de inicio no puede ser posterior a la fecha de fin.",
                    "warning",
                );
                return;
            }

            this.view = "cursos";
            this.cursosFilas = [];

            const cacheKey = String(this.selectedArea);

            if (this.cacheReportes[cacheKey]) {
                const filas = this.cacheReportes[cacheKey]
                    .filter((c) => this.cursoSolapaRangoUsuario(c))
                    .map((c) => this.filaDesdeCurso(c));

                this.cursosFilas = filas;
                this.sortColumn = null;
                this.sortDirection = null;
                this.currentPage = 1;
                this.loadingCursos = false;

                return;
            }

            this.loadingCursos = true;
            try {
                const response = await axios.get(
                    `/api/get-cursos-por-categoria/${this.selectedArea}`,
                );

                if (
                    !response.data.success ||
                    !Array.isArray(response.data.cursos)
                ) {
                    this.cursosFilas = [];
                    this.currentPage = 1;
                    return;
                }

                this.cacheReportes[cacheKey] = response.data.cursos;

                const filas = response.data.cursos
                    .filter((c) => this.cursoSolapaRangoUsuario(c))
                    .map((c) => this.filaDesdeCurso(c));

                this.cursosFilas = filas;
                this.sortColumn = null;
                this.sortDirection = null;
                this.currentPage = 1;
            } catch (error) {
                console.error(error);
                this.cursosFilas = [];
                Swal.fire(
                    "Error",
                    "No se pudieron cargar los cursos. Intente nuevamente.",
                    "error",
                );
            } finally {
                this.loadingCursos = false;
            }
        },

        volverAFiltros() {
            this.view = "filters";
            this.cursosFilas = [];
            this.currentPage = 1;
            this.sortColumn = null;
            this.sortDirection = null;
        },

        abrir() {
            this.open = true;
        },

        cerrar() {
            this.open = false;
            this.view = "filters";
            this.selectedSistema = "";
            this.selectedArea = "";
            this.selectedFechaInicio = "";
            this.selectedFechaFin = "";
            this.areas = [];
            this.cursosFilas = [];
            this.currentPage = 1;
            this.loadingCursos = false;
            this.sortColumn = null;
            this.sortDirection = null;
            this.cacheReportes = {};
        },

        async registrarReporteEnHistorial(nombreArchivo, pdfBlob, excelBlob) {
            try {
                const formData = new FormData();
                formData.append("nombre_archivo", nombreArchivo);
                formData.append("descripcion", "");

                if (pdfBlob) {
                    formData.append(
                        "archivo_pdf",
                        pdfBlob,
                        nombreArchivo.replace(/\.pdf$/i, "") + ".pdf",
                    );
                }

                if (excelBlob) {
                    formData.append(
                        "archivo_excel",
                        excelBlob,
                        nombreArchivo.replace(/\.xlsx$/i, "") + ".xlsx",
                    );
                }

                await axios.post(
                    "/api/capacitacion/registrar-reporte",
                    formData,
                    {
                        headers: { "Content-Type": "multipart/form-data" },
                    },
                );

                window.dispatchEvent(
                    new CustomEvent("historial-reportes-actualizado"),
                );
            } catch (error) {
                console.error(
                    "Error al registrar reporte en historial:",
                    error,
                );
            }
        },
    }));

    Alpine.data("modalReporteRecordDeCapacPorPersonal", () => ({
        open: false,
        view: "filters",

        selectedSistema: "",
        selectedArea: "",
        selectedCurso: "",
        selectedFechaInicio: "",
        selectedFechaFin: "",
        selectedSucursal: "",
        selectedPersonal: "",
        searchPersonal: "",
        selectedEstado: "PENDIENTE",

        sistemas: [],
        areas: [],
        cursos: [],
        todosLosCursos: [],
        fechasInicio: [],
        fechasFin: [],
        sucursales: [],
        personalOptions: [],
        personalRecord: [],

        loadingSistemas: false,
        loadingAreas: false,
        loadingCursos: false,
        loadingSucursales: false,
        loadingPersonal: false,
        loadingRecord: false,

        currentPage: 1,
        perPage: 15,

        sortColumn: null,
        sortDirection: null,

        cacheReportes: {},

        async init() {
            window.addEventListener("abrir-record-personal", () => {
                this.abrir();
            });
            await this.cargarSistemas();
            await this.cargarSucursales();
        },

        async cargarSistemas() {
            this.loadingSistemas = true;
            try {
                this.sistemas = await _fetchSistemas();
            } catch (error) {
                console.error(error);
                this.sistemas = [];
            } finally {
                this.loadingSistemas = false;
            }
        },

        async cargarAreas(sistemaId) {
            if (!sistemaId) {
                this.areas = [];
                this.selectedArea = "";
                return;
            }
            this.loadingAreas = true;
            try {
                this.areas = await _fetchAreas(sistemaId);
            } catch (error) {
                console.error(error);
                this.areas = [];
            } finally {
                this.loadingAreas = false;
            }
        },

        async cargarSucursales() {
            this.loadingSucursales = true;
            try {
                const response = await axios.get("/api/get-sucursales");
                if (response.data.success) {
                    this.sucursales = response.data.sucursales;
                }
            } catch (error) {
                console.error(error);
                this.sucursales = [];
            } finally {
                this.loadingSucursales = false;
            }
        },

        async cargarPersonalPorSucursal() {
            if (!this.selectedSucursal) {
                this.personalOptions = [];
                this.selectedPersonal = "";
                return;
            }
            this.loadingPersonal = true;
            try {
                const response = await axios.get(
                    `/api/get-personal-por-sucursal/${this.selectedSucursal}`,
                );
                if (response.data.success) {
                    this.personalOptions = response.data.personal;
                } else {
                    this.personalOptions = [];
                }
            } catch (error) {
                console.error(error);
                this.personalOptions = [];
            } finally {
                this.loadingPersonal = false;
            }
        },

        async cargarCursos(categoriaId) {
            if (!categoriaId) {
                this.cursos = [];
                this.todosLosCursos = [];
                this.fechasInicio = [];
                this.fechasFin = [];
                this.selectedCurso = "";
                return;
            }
            this.loadingCursos = true;
            try {
                const response = await axios.get(
                    `/api/get-cursos-por-categoria/${categoriaId}`,
                );
                if (response.data.success) {
                    this.todosLosCursos = response.data.cursos;
                    this.cursos = response.data.cursos;

                    const inicioUnicos = [];
                    const finUnicos = [];

                    this.todosLosCursos.forEach((curso) => {
                        const inicio = this.formatearFecha(curso.startdate);
                        const fin = this.formatearFecha(curso.enddate);
                        if (
                            inicio &&
                            !inicioUnicos.some((f) => f.fecha === inicio)
                        ) {
                            inicioUnicos.push({ fecha: inicio });
                        }
                        if (fin && !finUnicos.some((f) => f.fecha === fin)) {
                            finUnicos.push({ fecha: fin });
                        }
                    });

                    this.fechasInicio = inicioUnicos;
                    this.fechasFin = finUnicos;
                } else {
                    this.cursos = [];
                    this.todosLosCursos = [];
                    this.fechasInicio = [];
                    this.fechasFin = [];
                }
            } catch (error) {
                console.error(error);
                this.cursos = [];
                this.todosLosCursos = [];
                this.fechasInicio = [];
                this.fechasFin = [];
            } finally {
                this.loadingCursos = false;
            }
        },

        filtrarCursosPorFecha() {
            this.cursos = this.todosLosCursos.filter((curso) => {
                const fechaInicio = this.formatearFecha(curso.startdate);
                const fechaFin = this.formatearFecha(curso.enddate);
                if (
                    this.selectedFechaInicio &&
                    fechaInicio !== this.selectedFechaInicio
                )
                    return false;
                if (this.selectedFechaFin && fechaFin !== this.selectedFechaFin)
                    return false;
                return true;
            });
            this.selectedCurso = "";
        },

        formatearFecha(timestamp) {
            if (!timestamp || timestamp <= 0) return "";
            const fecha = new Date(timestamp * 1000);
            const dia = String(fecha.getDate()).padStart(2, "0");
            const mes = String(fecha.getMonth() + 1).padStart(2, "0");
            const anio = fecha.getFullYear();
            return `${dia}/${mes}/${anio}`;
        },

        async obtenerRecord() {
            if (!this.selectedPersonal) {
                Swal.fire("Atención", "Seleccione un personal.", "warning");
                return;
            }

            const personal = this.personalOptions.find(
                (p) => p.codigo == this.selectedPersonal,
            );
            if (!personal || !personal.dni) {
                Swal.fire(
                    "Atención",
                    "El personal seleccionado no tiene DNI registrado.",
                    "warning",
                );
                return;
            }

            const cacheKey = [
                personal.dni,
                this.selectedArea,
                this.selectedEstado,
                this.selectedFechaInicio,
                this.selectedFechaFin,
            ].join("_");

            if (this.cacheReportes[cacheKey]) {
                this.personalRecord = this.cacheReportes[cacheKey];
                this.currentPage = 1;
                this.sortColumn = null;
                this.sortDirection = null;
                this.view = "resultados";
                return;
            }

            this.loadingRecord = true;
            this.view = "resultados";
            this.personalRecord = [];

            try {
                const response = await axios.get(
                    `/api/get-cursos-alumno/${personal.dni}`,
                );

                if (response.data.success) {
                    let cursos = response.data.cursos.map((c) => ({
                        nombre_curso: c.course_nombre,
                        area:
                            (
                                this.areas.find((a) => a.codModdle == c.area) ||
                                {}
                            ).Area || c.area,
                        fecha_inicio: c.fecha_inicio_matricula || "",
                        fecha_final: c.fecha_fin_matricula || "",
                        fecha_matricula: c.fecha_matricula || "",
                        tipo_matricula: "",
                        categoria: c.area,
                        cargo: personal.cargo || "",
                        estado:
                            c.estado === "sin_iniciar"
                                ? "PENDIENTE"
                                : c.estado === "finalizado"
                                  ? "APROBADO"
                                  : c.estado === "en_curso"
                                    ? "EN_CURSO"
                                    : (c.estado || "").toUpperCase(),
                    }));

                    if (this.selectedArea) {
                        cursos = cursos.filter(
                            (c) => c.categoria == this.selectedArea,
                        );
                    }

                    if (this.selectedFechaInicio) {
                        cursos = cursos.filter(
                            (c) => c.fecha_inicio === this.selectedFechaInicio,
                        );
                    }

                    if (this.selectedFechaFin) {
                        cursos = cursos.filter(
                            (c) => c.fecha_final === this.selectedFechaFin,
                        );
                    }

                    if (this.selectedEstado === "PENDIENTE") {
                        cursos = cursos.filter((c) => c.estado === "PENDIENTE");
                    } else if (this.selectedEstado === "APROBADO") {
                        cursos = cursos.filter((c) => c.estado === "APROBADO");
                    } else if (this.selectedEstado === "DESAPROBADO") {
                        cursos = cursos.filter(
                            (c) => c.estado === "DESAPROBADO",
                        );
                    }

                    this.personalRecord = cursos;

                    this.cacheReportes[cacheKey] = cursos;
                } else {
                    this.personalRecord = [];
                }
            } catch (error) {
                console.error(error);
                this.personalRecord = [];
                Swal.fire(
                    "Error",
                    "No se pudo obtener el récord del personal.",
                    "error",
                );
            } finally {
                this.loadingRecord = false;
                this.currentPage = 1;
                this.sortColumn = null;
                this.sortDirection = null;
            }
        },

        abrir() {
            this.open = true;
        },

        cerrar() {
            this.open = false;
            this.view = "filters";
            this.selectedSistema = "";
            this.selectedArea = "";
            this.selectedCurso = "";
            this.selectedFechaInicio = "";
            this.selectedFechaFin = "";
            this.selectedSucursal = "";
            this.selectedPersonal = "";
            this.searchPersonal = "";
            this.selectedEstado = "PENDIENTE";
            this.areas = [];
            this.cursos = [];
            this.todosLosCursos = [];
            this.fechasInicio = [];
            this.fechasFin = [];
            this.personalOptions = [];
            this.personalRecord = [];
            this.currentPage = 1;
            this.loadingPersonal = false;
            this.loadingRecord = false;
            this.cacheReportes = {};
        },

        async registrarReporteEnHistorial(nombreArchivo, pdfBlob, excelBlob) {
            try {
                const formData = new FormData();
                formData.append("nombre_archivo", nombreArchivo);
                formData.append("descripcion", "");

                if (pdfBlob) {
                    formData.append(
                        "archivo_pdf",
                        pdfBlob,
                        nombreArchivo.replace(/\.pdf$/i, "") + ".pdf",
                    );
                }

                if (excelBlob) {
                    formData.append(
                        "archivo_excel",
                        excelBlob,
                        nombreArchivo.replace(/\.xlsx$/i, "") + ".xlsx",
                    );
                }

                await axios.post(
                    "/api/capacitacion/registrar-reporte",
                    formData,
                    {
                        headers: { "Content-Type": "multipart/form-data" },
                    },
                );

                window.dispatchEvent(
                    new CustomEvent("historial-reportes-actualizado"),
                );
            } catch (error) {
                console.error(
                    "Error al registrar reporte en historial:",
                    error,
                );
            }
        },

        volverAFiltros() {
            this.view = "filters";
            this.personalRecord = [];
            this.currentPage = 1;
            this.sortColumn = null;
            this.sortDirection = null;
        },

        ordenar(columna) {
            if (this.sortColumn === columna) {
                if (this.sortDirection === "asc") {
                    this.sortDirection = "desc";
                } else {
                    this.sortColumn = null;
                    this.sortDirection = null;
                }
            } else {
                this.sortColumn = columna;
                this.sortDirection = "asc";
            }
            this.currentPage = 1;
        },

        get recordPaginado() {
            const datos = this.recordOrdenado;
            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;
            return datos.slice(start, end);
        },

        get totalPages() {
            return Math.ceil(this.personalRecord.length / this.perPage);
        },

        get filteredPersonalOptions() {
            if (!this.searchPersonal.trim()) return this.personalOptions;
            const q = this.searchPersonal
                .trim()
                .toLowerCase()
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "");
            return this.personalOptions.filter(
                (p) =>
                    (p.nombre_completo || "")
                        .toLowerCase()
                        .normalize("NFD")
                        .replace(/[\u0300-\u036f]/g, "")
                        .includes(q) || (p.dni || "").includes(q),
            );
        },

        get nombrePersonal() {
            const p = this.personalOptions.find(
                (o) => o.codigo == this.selectedPersonal,
            );
            return p ? p.nombre_completo : "";
        },

        get textoRangoFechasRecord() {
            const ini = this.selectedFechaInicio;
            const fin = this.selectedFechaFin;
            if (ini && fin) {
                return `Del ${ini} al ${fin}`;
            }
            if (ini) {
                return `Desde el ${ini}`;
            }
            if (fin) {
                return `Hasta el ${fin}`;
            }
            return "Todo el período";
        },

        get recordOrdenado() {
            let datos = [...this.personalRecord];
            if (this.sortColumn && this.sortDirection) {
                datos.sort((a, b) => {
                    const valA = (a[this.sortColumn] || "").toString();
                    const valB = (b[this.sortColumn] || "").toString();
                    const cmp = valA.localeCompare(valB, "es", {
                        sensitivity: "base",
                    });
                    return this.sortDirection === "asc" ? cmp : -cmp;
                });
            } else {
                datos.sort((a, b) =>
                    (a.nombre_curso || "").localeCompare(
                        b.nombre_curso || "",
                        "es",
                        { sensitivity: "base" },
                    ),
                );
            }
            return datos;
        },

        async exportarExcelRecord() {
            if (!this.personalRecord.length) {
                Swal.fire(
                    "Atención",
                    "No hay registros para exportar.",
                    "warning",
                );
                return;
            }

            const workbook = new ExcelJS.Workbook();
            const logoImageId = await _cargarLogoExcel(workbook);
            const sheet = workbook.addWorksheet("Récord de Capacitaciones");

            const headerRowNumber = 5;

            sheet.getRow(1);
            sheet.getRow(2);
            sheet.getRow(3);
            sheet.getRow(4);

            if (logoImageId !== null) {
                sheet.addImage(logoImageId, {
                    tl: { col: 1, row: 0 },
                    br: { col: 3, row: 2 },
                    editAs: "absolute",
                });
            }

            sheet.getRow(1).height = 50;

            sheet.mergeCells("D1:F1");

            const infoCell = sheet.getCell("D1");
            infoCell.value = `Personal: ${this.nombrePersonal}`;
            infoCell.font = {
                bold: true,
                size: 13,
                color: { argb: "FF1F4E79" },
            };
            infoCell.alignment = {
                vertical: "middle",
                horizontal: "right",
            };

            sheet.mergeCells("D2:F2");

            const area = this.areas.find(
                (a) => String(a.codModdle) === String(this.selectedArea),
            );
            const nombreArea = area ? area.Area : "Todas las áreas";
            const areaCell = sheet.getCell("D2");
            areaCell.value = `Área responsable: ${nombreArea}`;
            areaCell.font = {
                size: 11,
                color: { argb: "FF333333" },
            };
            areaCell.alignment = {
                vertical: "middle",
                horizontal: "right",
            };

            sheet.mergeCells("D3:F3");

            const totalCell = sheet.getCell("D3");
            totalCell.value = `Total: ${this.personalRecord.length} curso(s) · ${this.textoRangoFechasRecord}`;
            totalCell.font = {
                size: 11,
                color: { argb: "FF333333" },
            };
            totalCell.alignment = {
                vertical: "middle",
                horizontal: "right",
            };

            sheet.getRow(4).height = 8;

            const headers = [
                "It",
                "Área",
                "Capacitación",
                "Estado",
                "Fecha",
                "Nota",
            ];

            const headerRow = sheet.getRow(headerRowNumber);
            headerRow.values = headers;
            headerRow.eachCell((cell) => _estiloEncabezadoExcel(cell));

            const dataOrdenada = this.recordOrdenado;
            dataOrdenada.forEach((c, i) => {
                const isPendiente = c.estado === "PENDIENTE";
                const row = sheet.addRow([
                    i + 1,
                    c.area,
                    c.nombre_curso,
                    c.estado,
                    isPendiente ? "" : c.fecha_inicio,
                    isPendiente ? "" : "",
                ]);
                row.eachCell((cell) => _estiloDatoExcel(cell));
            });

            sheet.columns = [
                { width: 5 },
                { width: 30 },
                { width: 50 },
                { width: 25 },
                { width: 25 },
                { width: 25 },
            ];

            sheet.autoFilter = {
                from: `A${headerRowNumber}`,
                to: `F${headerRowNumber}`,
            };

            const buffer = await workbook.xlsx.writeBuffer();
            const blob = _blobExcel(buffer);

            const slug = (this.nombrePersonal || "record")
                .toString()
                .replace(/[^\w\-]+/g, "_")
                .slice(0, 40);
            const nombreArchivo = `record_capacitaciones_${slug}_${new Date().toISOString().slice(0, 10)}.xlsx`;

            await this.registrarReporteEnHistorial(nombreArchivo, null, blob);

            saveAs(blob, nombreArchivo);

            Swal.fire(
                "Éxito",
                "Récord exportado a Excel correctamente.",
                "success",
            );
        },

        async exportarPDFRecord() {
            if (!this.personalRecord.length) {
                Swal.fire(
                    "Atención",
                    "No hay registros para exportar.",
                    "warning",
                );
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF({
                orientation: "landscape",
                unit: "mm",
                format: "a4",
            });

            const pageWidth = doc.internal.pageSize.getWidth();

            const sistema = this.sistemas.find(
                (s) => String(s.codigo) === String(this.selectedSistema),
            );
            const area = this.areas.find(
                (a) => String(a.codModdle) === String(this.selectedArea),
            );

            const logoSol = await _cargarImagen("/images/logo_sol.png");

            let logoBottomY = 26;
            const logoWidth = 60;
            const startX = 14;

            if (logoSol) {
                const ratio = logoSol.height / logoSol.width;
                const height = logoWidth * ratio;
                doc.addImage(logoSol, "PNG", startX, 10, logoWidth, height);
                logoBottomY = 10 + height;
            }

            const lineY = logoBottomY + 2;
            doc.setDrawColor(150, 150, 150);
            doc.setLineWidth(0.2);
            doc.line(startX, lineY, startX + 75, lineY);

            doc.setFont("helvetica", "italic");
            doc.setFontSize(7);
            doc.setTextColor(80, 80, 80);
            doc.text(
                "Chimbote: Calle Los Laureles Nº206 Urb. La Caleta",
                startX,
                lineY + 4,
            );
            doc.text("RUC: 20445414833", startX, lineY + 7);

            const title = sistema ? `${sistema.descripcion.toUpperCase()}` : "";
            const subtitle = area ? area.Area : "";

            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(12);

            const titleX = pageWidth - 14;
            doc.text(title, titleX, 40, { align: "right" });
            const titleWidth = doc.getTextWidth(title);
            doc.setLineWidth(0.3);
            doc.setDrawColor(0, 0, 0);
            if (titleWidth > 0) {
                doc.line(titleX - titleWidth, 41, titleX, 41);
            }

            doc.setFont("helvetica", "normal");
            doc.setFontSize(10);
            doc.text(subtitle, titleX, 46, { align: "right" });

            // DATOS GENERALES Section
            doc.setFont("helvetica", "bold");
            doc.setFontSize(9);
            doc.text("DATOS GENERALES", startX, 55);

            // Personal Info
            const personal = this.personalOptions.find(
                (p) => p.codigo == this.selectedPersonal,
            );
            const dni = personal ? personal.dni : "";
            const nombre = personal ? personal.nombre_completo : "";
            const codigo = personal ? personal.codigo : "";
            const cargo = personal ? personal.cargo : "";

            doc.autoTable({
                startY: 58,
                head: [],
                body: [
                    ["N° Documento", dni, "Apellidos y Nombres", nombre],
                    ["Código Pers.", codigo, "Cargo", cargo],
                ],
                theme: "grid",
                styles: {
                    fontSize: 8,
                    textColor: [0, 0, 0],
                    lineColor: [0, 0, 0],
                    lineWidth: 0.1,
                    halign: "left",
                    valign: "middle",
                },
                headStyles: {
                    fillColor: [253, 245, 230],
                    textColor: [0, 0, 0],
                    fontStyle: "bold",
                },
                columnStyles: {
                    0: { cellWidth: 30, fontStyle: "bold" },
                    1: { cellWidth: 40 },
                    2: { cellWidth: 40, fontStyle: "bold" },
                    3: { cellWidth: "auto" },
                },
                margin: { left: 14, right: 14 },
            });

            const tableStartY = doc.lastAutoTable.finalY + 5;

            const dataOrdenada = this.recordOrdenado;
            const filas = dataOrdenada.map((c, i) => {
                const isPendiente = c.estado === "PENDIENTE";
                return [
                    i + 1,
                    c.area || "",
                    c.nombre_curso || "",
                    c.estado || "",
                    isPendiente ? "" : c.fecha_inicio || "",
                    isPendiente ? "" : "",
                ];
            });

            doc.autoTable({
                startY: tableStartY,
                head: [
                    ["It", "Área", "Capacitación", "Estado", "Fecha", "Nota"],
                ],
                body: filas,
                theme: "grid",
                styles: {
                    fontSize: 8,
                    textColor: [0, 0, 0],
                    lineColor: [0, 0, 0],
                    lineWidth: 0.1,
                    halign: "center",
                    valign: "middle",
                },
                headStyles: {
                    fillColor: [253, 245, 230],
                    textColor: [0, 0, 0],
                    fontStyle: "bold",
                    halign: "center",
                },
                columnStyles: {
                    0: { cellWidth: 10 },
                    1: { cellWidth: 30 },
                    2: { cellWidth: "auto", halign: "left" },
                    3: { cellWidth: 25 },
                    4: { cellWidth: 25 },
                    5: { cellWidth: 25 },
                },
                margin: { left: 14, right: 14 },
            });

            const slug = (this.nombrePersonal || "record")
                .toString()
                .replace(/[^\w\-]+/g, "_")
                .slice(0, 40);
            const nombreArchivo = `record_capacitaciones_${slug}_${new Date().toISOString().slice(0, 10)}.pdf`;

            const pdfBlob = doc.output("blob");

            await this.registrarReporteEnHistorial(
                nombreArchivo,
                pdfBlob,
                null,
            );

            doc.save(nombreArchivo);

            Swal.fire(
                "Éxito",
                "Récord exportado a PDF correctamente.",
                "success",
            );
        },
    }));

    Alpine.data("modalHistorialReportes", () => ({
        open: false,
        reportes: [],
        loading: false,
        cacheLoaded: false,

        editingId: null,
        editForm: { nombre_archivo: "", descripcion: "" },
        savingEdit: false,

        sortColumn: null,
        sortDirection: null,
        searchQuery: "",
        showDeletedOnly: false,

        selectedReportes: [],
        downloadingZip: false,

        async init() {
            window.addEventListener("abrir-historial-reportes", () => {
                this.abrir();
            });
            window.addEventListener("historial-reportes-actualizado", () => {
                this.cacheLoaded = false;
                this.sortColumn = null;
                this.sortDirection = null;
                this.searchQuery = "";
                this.showDeletedOnly = false;
                this.selectedReportes = [];
            });
        },

        async abrir() {
            this.open = true;
            if (!this.cacheLoaded) {
                await this.cargarReportes();
            }
        },

        cerrar() {
            this.open = false;
            this.sortColumn = null;
            this.sortDirection = null;
            this.searchQuery = "";
            this.showDeletedOnly = false;
            this.selectedReportes = [];
        },

        async cargarReportes() {
            this.loading = true;
            try {
                const response = await axios.get(
                    "/api/capacitacion/listar-reportes",
                );
                if (response.data.success) {
                    this.reportes = response.data.reportes;
                    this.cacheLoaded = true;
                    this.searchQuery = "";
                }
            } catch (error) {
                console.error(error);
                this.reportes = [];
            } finally {
                this.loading = false;
            }
        },

        iniciarEdicion(reporte) {
            this.editingId = reporte.id;
            this.editForm = {
                nombre_archivo: reporte.nombre_archivo,
                descripcion: reporte.descripcion,
            };
        },

        cancelarEdicion() {
            this.editingId = null;
            this.editForm = { nombre_archivo: "", descripcion: "" };
        },

        ordenar(columna) {
            if (this.sortColumn === columna) {
                if (this.sortDirection === "asc") {
                    this.sortDirection = "desc";
                } else if (this.sortDirection === "desc") {
                    this.sortColumn = null;
                    this.sortDirection = null;
                } else {
                    this.sortDirection = "asc";
                }
            } else {
                this.sortColumn = columna;
                this.sortDirection = "asc";
            }
        },

        get reportesFiltrados() {
            let resultados = this.reportes;

            if (this.showDeletedOnly) {
                resultados = resultados.filter((r) => !r.habilitado);
            }

            if (this.searchQuery.trim()) {
                const query = this.searchQuery.trim().toLowerCase();
                resultados = resultados.filter((r) => {
                    const nombre = (r.nombre_archivo || "").toLowerCase();
                    const descripcion = (r.descripcion || "").toLowerCase();
                    const id = String(r.id);
                    const fecha = this.formatearFecha(
                        r.fecha_creacion,
                    ).toLowerCase();
                    return (
                        nombre.includes(query) ||
                        descripcion.includes(query) ||
                        id.includes(query) ||
                        fecha.includes(query)
                    );
                });
            }

            if (!this.sortColumn || !this.sortDirection) {
                return resultados;
            }

            return [...resultados].sort((a, b) => {
                let valA = a[this.sortColumn] || "";
                let valB = b[this.sortColumn] || "";

                if (this.sortColumn === "fecha_creacion") {
                    valA = valA ? new Date(valA).getTime() : 0;
                    valB = valB ? new Date(valB).getTime() : 0;
                    return this.sortDirection === "asc"
                        ? valA - valB
                        : valB - valA;
                }

                const cmp = valA
                    .toString()
                    .localeCompare(valB.toString(), "es", {
                        sensitivity: "base",
                    });
                return this.sortDirection === "asc" ? cmp : -cmp;
            });
        },

        get reportesSeleccionables() {
            return this.reportesFiltrados.filter((r) => r.habilitado);
        },

        get todosSeleccionados() {
            return (
                this.reportesSeleccionables.length > 0 &&
                this.selectedReportes.length ===
                    this.reportesSeleccionables.length
            );
        },

        async guardarEdicion() {
            if (!this.editingId) return;
            this.savingEdit = true;
            try {
                const response = await axios.put(
                    `/api/capacitacion/actualizar-reporte/${this.editingId}`,
                    {
                        nombre_archivo: this.editForm.nombre_archivo,
                        descripcion: this.editForm.descripcion,
                    },
                );

                if (response.data.success) {
                    const idx = this.reportes.findIndex(
                        (r) => r.id === this.editingId,
                    );
                    if (idx !== -1) {
                        this.reportes[idx].nombre_archivo =
                            this.editForm.nombre_archivo;
                        this.reportes[idx].descripcion =
                            this.editForm.descripcion;
                    }
                    this.cancelarEdicion();
                    this.cacheLoaded = false;
                }
            } catch (error) {
                console.error("Error al editar reporte:", error);
                Swal.fire(
                    "Error",
                    "No se pudo actualizar el reporte.",
                    "error",
                );
            } finally {
                this.savingEdit = false;
            }
        },

        async cambiarEstado(id, habilitado) {
            const accion = habilitado ? "recuperar" : "eliminar";
            const mensaje = habilitado
                ? "¿Desea recuperar este reporte?"
                : "¿Desea eliminar este reporte? Podrá recuperarlo luego.";

            try {
                await Swal.fire({
                    title: habilitado
                        ? "Recuperar reporte"
                        : "Eliminar reporte",
                    text: mensaje,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonColor: habilitado ? "#10b981" : "#ef4444",
                    confirmButtonText: habilitado
                        ? "Sí, recuperar"
                        : "Sí, eliminar",
                    cancelButtonText: "Cancelar",
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        const response = await axios.patch(
                            `/api/capacitacion/actualizar-estado-reporte/${id}`,
                            {
                                habilitado: habilitado,
                            },
                        );

                        if (response.data.success) {
                            const idx = this.reportes.findIndex(
                                (r) => r.id === id,
                            );
                            if (idx !== -1) {
                                this.reportes[idx].habilitado = habilitado;
                            }
                            this.cacheLoaded = false;
                            Swal.fire(
                                "Éxito",
                                response.data.message,
                                "success",
                            );
                        }
                    }
                });
            } catch (error) {
                console.error(`Error al ${accion} reporte:`, error);
                Swal.fire("Error", `No se pudo ${accion} el reporte.`, "error");
            }
        },

        async descargarArchivo(id, tipo) {
            try {
                const response = await axios.get(
                    `/api/capacitacion/descargar-reporte/${id}/${tipo}`,
                    {
                        responseType: "blob",
                    },
                );

                const contentDisposition =
                    response.headers["content-disposition"];
                let nombreArchivo = `reporte_${id}.${tipo === "pdf" ? "pdf" : "xlsx"}`;
                if (contentDisposition) {
                    const match =
                        contentDisposition.match(/filename="?(.+?)"?$/);
                    if (match) {
                        nombreArchivo = match[1];
                    }
                }

                const blob = new Blob([response.data]);
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = nombreArchivo;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            } catch (error) {
                console.error("Error al descargar archivo:", error);
                Swal.fire(
                    "Error",
                    "No se pudo descargar el archivo. Intente nuevamente.",
                    "error",
                );
            }
        },

        toggleSeleccion(id, seleccionado) {
            if (seleccionado) {
                if (!this.selectedReportes.includes(id)) {
                    this.selectedReportes.push(id);
                }
            } else {
                this.selectedReportes = this.selectedReportes.filter(
                    (rId) => rId !== id,
                );
            }
        },

        toggleSeleccionTodos(seleccionado) {
            if (seleccionado) {
                this.selectedReportes = this.reportesSeleccionables.map(
                    (r) => r.id,
                );
            } else {
                this.selectedReportes = [];
            }
        },

        async descargarSeleccionadosZip() {
            if (this.selectedReportes.length === 0) return;

            this.downloadingZip = true;
            try {
                const response = await axios.post(
                    "/api/capacitacion/descargar-reportes-zip",
                    {
                        ids: this.selectedReportes,
                    },
                    {
                        responseType: "blob",
                    },
                );

                const blob = new Blob([response.data], {
                    type: "application/zip",
                });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement("a");
                a.href = url;
                a.download = "reportes_capacitaciones.zip";
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                Swal.fire(
                    "Éxito",
                    "Archivo ZIP descargado correctamente.",
                    "success",
                );
            } catch (error) {
                console.error("Error al descargar ZIP:", error);
                Swal.fire(
                    "Error",
                    "No se pudo generar el archivo ZIP.",
                    "error",
                );
            } finally {
                this.downloadingZip = false;
            }
        },

        formatearFecha(fecha) {
            if (!fecha) return "—";
            const d = new Date(fecha);
            const dia = String(d.getDate()).padStart(2, "0");
            const mes = String(d.getMonth() + 1).padStart(2, "0");
            const anio = d.getFullYear();
            const hora = String(d.getHours()).padStart(2, "0");
            const min = String(d.getMinutes()).padStart(2, "0");
            return `${dia}/${mes}/${anio} ${hora}:${min}`;
        },
    }));
});
