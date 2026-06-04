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
    const { data } = await axios.get("/api/obtener-capacitacion-sistemas");
    return data;
}

async function _fetchAreas() {
    const { data } = await axios.get(`/api/obtener-areas`);
    return data.success ? data.areas : [];
}

async function _fetchSucursales() {
    const { data } = await axios.get(`/api/get-sucursales`);
    return data.success ? data.sucursales : [];
}

async function _fetchPersonales() {
    const { data } = await axios.get('/api/obtener-personal');
    return data.success ? data.personal : [];
}

function _ordenarPersonal(datos, columna, direccion) {
    if (columna && direccion) {
        datos.sort((a, b) => {
            if (columna === "Nota_Final") {
                const valA = parseFloat(a[columna]) || 0;
                const valB = parseFloat(b[columna]) || 0;
                return direccion === "asc" ? valA - valB : valB - valA;
            }
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
        selectedEstado: 0,
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

        exportando: false,

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

        formatearFecha(valor) {
            if (!valor || valor <= 0) {
                return null;
            }

            let fecha;
            if (typeof valor === "number" || /^\d+$/.test(valor)) {
                fecha = new Date(Number(valor) * 1000);
            } else {
                fecha = new Date(valor.includes("T") ? valor : valor.replace(" ", "T"));
            }

            if (isNaN(fecha.getTime())) {
                return null;
            }

            const dia = String(fecha.getDate()).padStart(2, "0");
            const mes = String(fecha.getMonth() + 1).padStart(2, "0");
            const anio = fecha.getFullYear();

            return `${dia}/${mes}/${anio}`;
        },

        obtenerPeriodo(curso) {
            const creacion = this.formatearFecha(curso.Fecha_Creacion);

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
                    "/api/obtener-cursos",
                );

                if (response.data.success) {
                    this.todosLosCursos = [...response.data.Cursos];
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

        formatearFechaISO(valor) {
            if (!valor || valor <= 0) return null;
            let fecha;
            if (typeof valor === "number" || /^\d+$/.test(valor)) {
                fecha = new Date(Number(valor) * 1000);
            } else {
                fecha = new Date(valor.includes("T") ? valor : valor.replace(" ", "T"));
            }
            if (isNaN(fecha.getTime())) return null;
            const dia = String(fecha.getDate()).padStart(2, "0");
            const mes = String(fecha.getMonth() + 1).padStart(2, "0");
            const anio = fecha.getFullYear();
            return `${anio}-${mes}-${dia}`;
        },

        filtrarCursosPorFecha() {
            this.cursos = this.todosLosCursos.filter((curso) => {
                const fechaCreacion = this.formatearFechaISO(curso.Fecha_Creacion);

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
            this.selectedEstado = 0;
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
                this.cursos.find((c) => c.Id == this.selectedCurso)?.Nombre ||
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
                "Nota Final",
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
                    p.Nota_Final || "—",
                    p.Estado,
                ]);
                row.eachCell((cell) => _estiloDatoExcel(cell));
            });

            return startRow + 1 + dataOrdenada.length;
        },

        async exportarExcel() {
            this.exportando = true;
            try {
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
                        sheet.mergeCells(`A${currentRow}:G${currentRow}`);
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
                        sheet.mergeCells(`A${currentRow}:G${currentRow}`);
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
                        { width: 12 },
                        { width: 15 },
                    ];
                } else if (this.hayAgrupacionPorSucursal) {
                    const curso = this.cursos.find(
                        (c) => c.Id == this.selectedCurso,
                    );
                    const nombreCurso = curso ? curso.Nombre : "reporte";

                    this._escribirEncabezadoExcel(
                        sheet,
                        logoImageId,
                        `Curso: ${nombreCurso}`,
                        "TODAS LAS SUCURSALES",
                        `Total general: ${this.personal.length} personal(es)`,
                    );

                    let currentRow = 6;

                    this.personalPorSucursal.forEach((grupo, gi) => {
                        if (gi > 0) {
                            currentRow += 1;
                        }

                        const sucRow = sheet.getRow(currentRow);
                        sucRow.height = 22;
                        const sucCell = sucRow.getCell(1);
                        sucCell.value = grupo.SucursalNombre;
                        sucCell.font = {
                            bold: true,
                            size: 11,
                            color: { argb: "FF1F4E79" },
                        };
                        sucCell.alignment = {
                            vertical: "middle",
                        };
                        sheet.mergeCells(`A${currentRow}:G${currentRow}`);
                        currentRow++;

                        const subRow = sheet.getRow(currentRow);
                        subRow.height = 16;
                        const subCell = subRow.getCell(1);
                        subCell.value = `Total: ${grupo.Personales.length} personal(es)`;
                        subCell.font = {
                            size: 9,
                            italic: true,
                            color: { argb: "FF666666" },
                        };
                        sheet.mergeCells(`A${currentRow}:G${currentRow}`);
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
                        { width: 12 },
                        { width: 15 },
                    ];
                } else {
                    const curso = this.cursos.find(
                        (c) => c.Id == this.selectedCurso,
                    );
                    const nombreCurso = curso ? curso.Nombre : "reporte";

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
                        { width: 12 },
                        { width: 15 },
                    ];

                    sheet.autoFilter = {
                        from: `A5`,
                        to: `H5`,
                    };
                }

                const buffer = await workbook.xlsx.writeBuffer();
                const blob = _blobExcel(buffer);
                const d = new Date();
                const fechaRep = `${d.getFullYear()}_${String(d.getMonth()+1).padStart(2,"0")}_${String(d.getDate()).padStart(2,"0")}_${String(d.getHours()).padStart(2,"0")}_${String(d.getMinutes()).padStart(2,"0")}`;
                const nombreArchivo = `REPORTE_POR_CAPACITACION_${fechaRep}.xlsx`;

                await this.registrarReporteEnHistorial(nombreArchivo, null, blob);

                saveAs(blob, nombreArchivo);

                Swal.fire(
                    "Éxito",
                    "Reporte exportado a Excel correctamente.",
                    "success",
                );
            } catch (error) {
                console.error(error);
                Swal.fire("Error", "No se pudo exportar el Excel.", "error");
            } finally {
                this.exportando = false;
            }
        },

        async exportarPDF() {
            this.exportando = true;
            try {
                const { jsPDF } = window.jspdf;

                const doc = new jsPDF({
                    orientation: "landscape",
                    unit: "mm",
                    format: "a4",
                });

                const curso = this.cursos.find(
                    (c) => c.Id == this.selectedCurso,
                );

                const nombreCurso = curso ? curso.Nombre : "REPORTE GENERAL";

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
                        p.Cargo || "Sin cargo",
                        p.Nota_Final || "Sin nota",
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
                                "Nota final",
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
                                cellWidth: 17,
                            },
                            1: {
                                cellWidth: "auto",
                                halign: "left",
                            },
                            2: {
                                cellWidth: "auto",
                                halign: "center",
                            },
                            3: {
                                cellWidth: "auto",
                                halign: "center",
                            },
                            4: {
                                cellWidth: "auto",
                                halign: "center",
                            },
                            5: {
                                cellWidth: "auto",
                                halign: "center",
                            },
                            6: {
                                cellWidth: "auto",
                                halign: "center",
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
                } else if (this.hayAgrupacionPorSucursal) {
                    const { startX, startY } = dibujarEncabezado();
                    let currentY = startY;

                    const pageWidth = doc.internal.pageSize.width;
                    const centerX = pageWidth / 2;
                    const titulo = nombreCurso.toUpperCase();

                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(20);
                    doc.setTextColor(0, 0, 0);
                    doc.text(titulo, centerX, currentY, { align: "center" });

                    currentY += 10;

                    this.personalPorSucursal.forEach((grupo, index) => {
                        const pageHeight = doc.internal.pageSize.height;
                        const marginBottom = 20;
                        const espacioMinimo = 25;

                        if (index > 0) {
                            currentY += 10;
                        }

                        if (currentY + espacioMinimo > pageHeight - marginBottom) {
                            doc.addPage();
                            currentY = 20;
                        }

                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(11);
                        doc.setTextColor(0, 0, 0);
                        doc.text(grupo.SucursalNombre, startX, currentY);
                        currentY += 5;

                        doc.setFont("helvetica", "normal");
                        doc.setFontSize(9);
                        doc.text(`${grupo.Personales.length} personal(es)`, startX, currentY);
                        currentY += 4;

                        const personalOrdenado = [...grupo.Personales].sort((a, b) =>
                            (a.NombreCompleto || "").localeCompare(b.NombreCompleto || ""),
                        );

                        const filas = personalOrdenado.map((p) => [
                            p.CodigoPers || "",
                            p.NombreCompleto || "",
                            p.DNI || "",
                            p.TipoTrabajador || "",
                            p.Cargo || "Sin cargo",
                            p.Nota_Final || "Sin nota",
                            p.Estado || "",
                        ]);

                        doc.autoTable({
                            startY: currentY,
                            head: [[
                                "Código\nPers.",
                                "Nombre Completo",
                                "DNI",
                                "Tipo Trabajador",
                                "Cargo",
                                "Nota final",
                                "Estado",
                            ]],
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
                                0: { cellWidth: 17 },
                                1: { cellWidth: "auto", halign: "left" },
                                2: { cellWidth: "auto", halign: "center" },
                                3: { cellWidth: "auto", halign: "center" },
                                4: { cellWidth: "auto", halign: "center" },
                                5: { cellWidth: "auto", halign: "center" },
                                6: { cellWidth: "auto", halign: "center" },
                            },
                            margin: { left: 14, right: 14 },
                        });

                        currentY = doc.lastAutoTable.finalY;
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

                const d = new Date();
                const fechaRep = `${d.getFullYear()}_${String(d.getMonth()+1).padStart(2,"0")}_${String(d.getDate()).padStart(2,"0")}_${String(d.getHours()).padStart(2,"0")}_${String(d.getMinutes()).padStart(2,"0")}`;
                const nombreArchivo = `REPORTE_POR_CAPACITACION_${fechaRep}.pdf`;

                doc.setProperties({
                    title: nombreArchivo,
                });

                const pdfBlob = doc.output("blob");

                await this.registrarReporteEnHistorial(
                    nombreArchivo,
                    pdfBlob,
                    null,
                );

                saveAs(pdfBlob, nombreArchivo);

                Swal.fire(
                    "Éxito",
                    "PDF generado correctamente.",
                    "success",
                );
            } catch (error) {
                console.error(error);

                Swal.fire("Error", "No se pudo generar el PDF.", "error");
            } finally {
                this.exportando = false;
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

        get personalPorSucursal() {
            if (this.selectedSucursal || this.esReportePorCursos) {
                return [];
            }
            const grupos = {};
            this.personal.forEach((p) => {
                const codigo = p.SucursalCodigo || "SIN_SUCURSAL";
                if (!grupos[codigo]) {
                    const suc = this.sucursales.find(
                        (s) => s.codigo == p.SucursalCodigo,
                    );
                    grupos[codigo] = {
                        SucursalCodigo: codigo,
                        SucursalNombre: suc
                            ? suc.sucursal.toUpperCase()
                            : "SIN SUCURSAL",
                        Personales: [],
                    };
                }
                grupos[codigo].Personales.push(p);
            });
            return Object.values(grupos);
        },

        get hayAgrupacionPorSucursal() {
            return (
                !this.selectedSucursal &&
                !this.esReportePorCursos &&
                this.personal.length > 0
            );
        },

        get personalAplanado() {
            if (!this.hayAgrupacionPorSucursal) return [];
            const result = [];
            this.personalPorSucursal.forEach((grupo) => {
                result.push({ tipo: "separador", grupo });
                grupo.Personales.forEach((persona, pi) => {
                    result.push({ tipo: "fila", persona, idx: pi + 1 });
                });
            });
            return result;
        },

        async obtenerPersonal() {
            const cacheKey = [
                this.selectedCurso || "todos",
                this.selectedSucursal || "todas",
                this.selectedEstado || 0,
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
            
            params.estadoId = this.selectedEstado ?? 0;

            try {
                const response = await axios.get(
                    "/api/obtener-personal-reporte",
                    {
                        params,
                    },
                );

                if (response.data.success) {
                    const cursos = response.data.Cursos || [];

                    this.personal = cursos.flatMap(
                        (curso) => curso.Personales || [],
                    );

                    this.totalPersonal = this.personal.length;

                    this.cacheReportes[cacheKey] = {
                        personal: this.personal,
                        total: this.totalPersonal,
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

        exportando: false,

        cacheReportes: {},

        async init() {
            window.addEventListener("abrir-cursos-area", () => {
                this.abrir();
            });
            await this.cargarSistemas();
            await this.cargarAreas();
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

        async cargarAreas() {
            this.loadingAreas = true;
            try {
                this.areas = await _fetchAreas();
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

            const cCre = this.timestampInicioDia(curso.Fecha_Creacion);

            if (cCre === null) {
                return true;
            }

            const rangoIni = uIni !== null ? uIni : -Infinity;
            const rangoFin = uFin !== null ? uFin : Infinity;

            return cCre >= rangoIni && cCre <= rangoFin;
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
            const iniTs = this.timestampInicioDia(curso.Fecha_Inicio);
            const finTs = this.timestampInicioDia(curso.Fecha_Fin);
            const creTs = this.timestampInicioDia(curso.Fecha_Creacion);

            const nombre = (curso.Nombre || "").toString().trim();
            const descripcion = this.stripHtml(curso.Descripcion || "");
            const sistema = (curso.Sistema || "").toString().trim();
            const area = (curso.Area || "").toString().trim();
            const responsable = (curso.Responsable || "").toString().trim();
            const matriculados = curso.Total_Matriculados;
            const fechaIniStr = this.formatearFecha(curso.Fecha_Inicio);
            const fechaFinStr = this.formatearFecha(curso.Fecha_Fin);
            const fechaCreStr = this.formatearFecha(curso.Fecha_Creacion);

            return {
                id: curso.Id,
                Nombre: nombre || "Sin nombre de curso",
                Descripcion: descripcion || "Sin descripción",
                Sistema: sistema || "Sin sistema",
                Area: area || "Sin área",
                Responsable: responsable || "Sin responsable",
                Total_Matriculados: matriculados != null ? matriculados : "—",
                Fecha_Inicio: fechaIniStr || "Sin fecha de inicio",
                Fecha_Fin: fechaFinStr || "Sin fecha de fin",
                Fecha_Creacion: fechaCreStr || "Sin fecha de creación",
                _sortInicio: iniTs,
                _sortFin: finTs,
                _sortCreacion: creTs,
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
            if (columna === "Fecha_Inicio") {
                return fila._sortInicio;
            }
            if (columna === "Fecha_Fin") {
                return fila._sortFin;
            }
            if (columna === "Fecha_Creacion") {
                return fila._sortCreacion;
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
            return "";
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

            this.exportando = true;
            try {
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
                "Capacitación",
                "Descripción",
                "Sistema",
                "Área",
                "Matriculados",
                "Responsable",
                "Fecha inicio",
                "Fecha fin",
                "Fecha creación",
            ];

            const headerRow = sheet.getRow(headerRowNumber);

            headerRow.values = headers;

            headerRow.eachCell((cell) => _estiloEncabezadoExcel(cell));

            filasExport.forEach((fila, i) => {
                const row = sheet.addRow([
                    i + 1,
                    fila.Nombre,
                    fila.Descripcion,
                    fila.Sistema,
                    fila.Area,
                    fila.Total_Matriculados,
                    fila.Responsable,
                    fila.Fecha_Inicio,
                    fila.Fecha_Fin,
                    fila.Fecha_Creacion,
                ]);

                row.eachCell((cell) => _estiloDatoExcel(cell));
            });

            sheet.columns = [
                { width: 5 },
                { width: 35 },
                { width: 30 },
                { width: 18 },
                { width: 18 },
                { width: 12 },
                { width: 26 },
                { width: 14 },
                { width: 14 },
                { width: 14 },
            ];

            sheet.autoFilter = {
                from: `A${headerRowNumber}`,
                to: `J${headerRowNumber}`,
            };

            const buffer = await workbook.xlsx.writeBuffer();
            const blob = _blobExcel(buffer);

            const d = new Date();
            const fechaRep = `${d.getFullYear()}_${String(d.getMonth()+1).padStart(2,"0")}_${String(d.getDate()).padStart(2,"0")}_${String(d.getHours()).padStart(2,"0")}_${String(d.getMinutes()).padStart(2,"0")}`;
            const nombreArchivo = `REPORTE_HISTORIAL_CURSOS_${fechaRep}.xlsx`;

            await this.registrarReporteEnHistorial(nombreArchivo, null, blob);

                saveAs(blob, nombreArchivo);

                Swal.fire(
                    "Éxito",
                    "Historial exportado a Excel correctamente.",
                    "success",
                );
            } catch (error) {
                console.error(error);
                Swal.fire("Error", "No se pudo exportar el Excel.", "error");
            } finally {
                this.exportando = false;
            }
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

            this.exportando = true;
            try {
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
                    f.Nombre || "",
                    f.Descripcion || "",
                    f.Sistema || "",
                    f.Area || "",
                    f.Total_Matriculados != null ? f.Total_Matriculados : "",
                    f.Responsable || "",
                    f.Fecha_Inicio || "",
                    f.Fecha_Fin || "",
                    f.Fecha_Creacion || "",
                ]);

                doc.autoTable({
                    startY: tableStartY,
                    head: [
                        [
                            "It",
                            "Capacitación",
                            "Descripción",
                            "Sistema",
                            "Área",
                            "Matriculados",
                            "Responsable",
                            "Fecha inicio",
                            "Fecha fin",
                            "Fecha creación",
                        ],
                    ],
                    body: filas,
                    theme: "grid",
                    styles: {
                        fontSize: 7,
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
                        0: { cellWidth: 10, halign: "center" },
                        1: { cellWidth: "auto", halign: "left" },
                        2: { cellWidth: "auto", halign: "left" },
                        3: { cellWidth: "auto", halign: "left" },
                        4: { cellWidth: "auto" },
                        5: { cellWidth: "auto" },
                        6: { cellWidth: "auto" },
                        7: { cellWidth: "auto" },
                        8: { cellWidth: "auto" },
                        9: { cellWidth: "auto" },
                    },
                    margin: { left: 10, right: 10 },
                });

                const d = new Date();
                const fechaRep = `${d.getFullYear()}_${String(d.getMonth()+1).padStart(2,"0")}_${String(d.getDate()).padStart(2,"0")}_${String(d.getHours()).padStart(2,"0")}_${String(d.getMinutes()).padStart(2,"0")}`;
                const nombreArchivo = `REPORTE_HISTORIAL_CURSOS_${fechaRep}.pdf`;

                const pdfBlob = doc.output("blob");

                await this.registrarReporteEnHistorial(
                    nombreArchivo,
                    pdfBlob,
                    null,
                );

                saveAs(pdfBlob, nombreArchivo);

                Swal.fire(
                    "Éxito",
                    "PDF generado correctamente.",
                    "success",
                );
            } catch (error) {
                console.error(error);
                Swal.fire("Error", "No se pudo generar el PDF.", "error");
            } finally {
                this.exportando = false;
            }
        },

        async obtenerCursos() {
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

            const cacheKey = `${this.selectedSistema || "_"}_${this.selectedArea || "_"}`;

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
                const params = {};
                if (this.selectedSistema) params.systemId = this.selectedSistema;
                if (this.selectedArea) params.areaId = this.selectedArea;

                const response = await axios.get("/api/obtener-cursos", { params });

                const cursosRaw = response.data.Cursos || response.data.cursos || [];

                if (!response.data.success || !Array.isArray(cursosRaw)) {
                    this.cursosFilas = [];
                    this.currentPage = 1;
                    return;
                }

                this.cacheReportes[cacheKey] = cursosRaw;

                const filas = cursosRaw
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
                    "warning",
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

        async abrir() {
            this.open = true;
            await this.cargarAreas();
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
                );

                window.dispatchEvent(
                    new CustomEvent("historial-reportes-actualizado"),
                );
            } catch (error) {
                console.error(
                    "Error al registrar reporte en historial:",
                    error,
                );
                Swal.fire(
                    "Error",
                    error.response?.data?.message || "No se pudo guardar en el historial de reportes.",
                    "warning",
                );
            }
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

        async eliminarDefinitivamente(id) {
            try {
                const result = await Swal.fire({
                    title: "Eliminar definitivamente",
                    text: "¿Está seguro? Esta acción no se puede deshacer.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#ef4444",
                    confirmButtonText: "Sí, eliminar permanentemente",
                    cancelButtonText: "Cancelar",
                });

                if (!result.isConfirmed) return;

                const response = await axios.delete(
                    `/api/capacitacion/eliminar-reporte/${id}`,
                );

                if (response.data.success) {
                    this.reportes = this.reportes.filter((r) => r.id !== id);
                    this.cacheLoaded = false;
                    Swal.fire("Eliminado", response.data.message, "success");
                }
            } catch (error) {
                console.error("Error al eliminar reporte permanentemente:", error);
                Swal.fire(
                    "Error",
                    "No se pudo eliminar el reporte permanentemente.",
                    "error",
                );
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
                const d = new Date();
                const fechaRep = `${d.getFullYear()}_${String(d.getMonth()+1).padStart(2,"0")}_${String(d.getDate()).padStart(2,"0")}_${String(d.getHours()).padStart(2,"0")}_${String(d.getMinutes()).padStart(2,"0")}`;
                let nombreArchivo = `REPORTE_${fechaRep}.${tipo === "pdf" ? "pdf" : "xlsx"}`;
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

    Alpine.data("modalRecordPersonal", () => ({
        open: false,
        view: "filters",

        loadingSistemas: false,
        loadingAreas: false,
        loadingCursos: false,
        loadingPersonal: false,
        loadingClientes: false,
        loadingSucursales: false,
        buscando: false,
        loadingInicial: true,

        selectedSistema: "",
        selectedArea: "",
        selectedCliente: "",
        selectedSucursal: "",
        selectedTipoTrabajador: "",
        selectedCargo: "",
        searchPersonal: "",
        searchCurso: "",
        selectedEstadoId: "0",
        selectedFechaDesde: "",
        selectedFechaHasta: "",

        sistemas: [],
        areas: [],
        cursos: [],
        todosLosCursos: [],
        personalesFiltrados: [],
        todosLosPersonales: [],
        clientes: [],
        sucursales: [],
        tiposTrabajador: [],
        cargos: [],

        selectedCourseIds: [],
        selectedUsernames: [],
        selectAllCursos: false,
        selectAllPersonal: false,

        personalPerPage: 20,
        cursosPerPage: 15,
        personalPage: 1,
        cursosPage: 1,

        get personalTotalPages() {
            return Math.max(
                1,
                Math.ceil(
                    this.personalesFiltrados.length / this.personalPerPage,
                ),
            );
        },
        get cursosTotalPages() {
            return Math.max(
                1,
                Math.ceil(this.cursos.length / this.cursosPerPage),
            );
        },

        personalesPaginados() {
            const start = (this.personalPage - 1) * this.personalPerPage;
            return this.personalesFiltrados.slice(
                start,
                start + this.personalPerPage,
            );
        },
        cursosPaginados() {
            const start = (this.cursosPage - 1) * this.cursosPerPage;
            return this.cursos.slice(start, start + this.cursosPerPage);
        },

        resultados: [],
        totalResultados: 0,

        async init() {
            window.addEventListener("abrir-record-personal", () => {
                this.abrir();
            });
        },

        async abrir() {
            this.open = true;
            this.loadingInicial = true;
            this.cargarSistemas();
            this.cargarClientes();
            this.cargarSucursales();
            await Promise.all([this.cargarCursos(), this.cargarPersonales()]);
            this.loadingInicial = false;
        },

        async cargarSistemas() {
            this.loadingSistemas = true;
            try {
                this.sistemas = await _fetchSistemas();
            } catch (e) {
                console.error(e);
                this.sistemas = [];
            } finally {
                this.loadingSistemas = false;
            }
        },

        async cargarAreas() {
            this.loadingAreas = true;
            try {
                let areas = [];
                if (this.selectedSistema) {
                    const { data } = await axios.get(
                        `/api/obtener-areas-por-sistema/${this.selectedSistema}`,
                    );
                    if (data.success) {
                        areas = (data.areas || []).map((a) => ({
                            codModdle: a.codModdle || a.codArea || "",
                            Area: a.Area || a.nombre || a.descripcion || "",
                        }));
                    }
                } else {
                    areas = await _fetchAreas();
                }
                this.areas = areas;
            } catch (e) {
                console.error(e);
                this.areas = [];
            } finally {
                this.loadingAreas = false;
            }
        },

        async cargarCursos() {
            this.loadingCursos = true;
            try {
                const response = await axios.get("/api/obtener-cursos");
                if (response.data.success) {
                    this.todosLosCursos = response.data.Cursos || [];
                    this.filtrarCursos();
                }
            } catch (e) {
                console.error(e);
                this.cursos = [];
                this.todosLosCursos = [];
            } finally {
                this.loadingCursos = false;
            }
        },

        async cargarPersonales() {
            this.loadingPersonal = true;
            try {
                const personales = await _fetchPersonales();
                this.todosLosPersonales = personales;
                const tipos = [
                    ...new Set(
                        personales
                            .map((p) => p.tipo_trabajador)
                            .filter(Boolean),
                    ),
                ];
                this.tiposTrabajador = tipos.sort();
                const cargos = [
                    ...new Set(personales.map((p) => p.cargo).filter(Boolean)),
                ];
                this.cargos = cargos.sort();
                this.filtrarPersonales();
            } catch (e) {
                console.error(e);
                this.todosLosPersonales = [];
                this.personalesFiltrados = [];
            } finally {
                this.loadingPersonal = false;
            }
        },

        async cargarClientes() {
            this.loadingClientes = true;
            try {
                const { data } = await axios.get("/api/get-clientes-pac");
                this.clientes = Array.isArray(data) ? data : [];
            } catch (e) {
                console.error(e);
                this.clientes = [];
            } finally {
                this.loadingClientes = false;
            }
        },

        async cargarSucursales() {
            this.loadingSucursales = true;
            try {
                this.sucursales = await _fetchSucursales();
            } catch (e) {
                console.error(e);
                this.sucursales = [];
            } finally {
                this.loadingSucursales = false;
            }
        },

        async onSistemaChange() {
            this.selectedArea = "";
            this.areas = [];
            await this.cargarAreas();
            this.filtrarCursos();
        },

        async onAreaChange() {
            this.filtrarCursos();
        },

        filtrarCursos() {
            this.cursos = this.todosLosCursos.filter((c) => {
                if (
                    this.selectedSistema &&
                    String(c.SistemaId || c.sistema_id || "") !==
                        String(this.selectedSistema)
                ) {
                    const sistema = (c.Sistema || "").toString().toLowerCase();
                    const sistSel = this.sistemas.find(
                        (s) =>
                            String(s.codigo) === String(this.selectedSistema),
                    );
                    if (
                        !sistSel ||
                        !sistema.includes(sistSel.descripcion.toLowerCase())
                    ) {
                        return false;
                    }
                }
                if (this.selectedArea) {
                    const areaId = String(
                        c.AreaId || c.area_id || c.codModdle || "",
                    );
                    if (areaId !== String(this.selectedArea)) {
                        const area = (c.Area || "").toString().toLowerCase();
                        const areaSel = this.areas.find(
                            (a) =>
                                String(a.codModdle) ===
                                String(this.selectedArea),
                        );
                        if (
                            !areaSel ||
                            !area.includes(areaSel.Area.toLowerCase())
                        ) {
                            return false;
                        }
                    }
                }
                if (this.selectedFechaDesde && c.Fecha_Creacion) {
                    const desdeTs =
                        new Date(this.selectedFechaDesde).getTime() / 1000;
                    if (c.Fecha_Creacion < desdeTs) return false;
                }
                if (this.selectedFechaHasta && c.Fecha_Creacion) {
                    const hastaTs =
                        new Date(
                            this.selectedFechaHasta + " 23:59:59",
                        ).getTime() / 1000;
                    if (c.Fecha_Creacion > hastaTs) return false;
                }
                if (this.searchCurso) {
                    const term = this.searchCurso.toLowerCase();
                    if (!(c.Nombre || "").toLowerCase().includes(term))
                        return false;
                }
                return true;
            });
            this.cursosPage = 1;
        },

        filtrarPersonales() {
            let resultados = [...this.todosLosPersonales];
            if (this.selectedCliente) {
                const clienteSel = this.clientes.find(
                    (c) => String(c.codigo) === String(this.selectedCliente),
                );
                if (clienteSel) {
                    resultados = resultados.filter(
                        (p) =>
                            String(p.cliente) ===
                            String(clienteSel.descripcion),
                    );
                } else {
                    resultados = [];
                }
            }
            if (this.selectedSucursal) {
                const sucursalSel = this.sucursales.find(
                    (s) => String(s.codigo) === String(this.selectedSucursal),
                );
                if (sucursalSel) {
                    resultados = resultados.filter(
                        (p) =>
                            String(p.sucursal) === String(sucursalSel.sucursal),
                    );
                } else {
                    resultados = [];
                }
            }
            if (this.selectedTipoTrabajador) {
                resultados = resultados.filter(
                    (p) =>
                        String(p.tipo_trabajador) ===
                        String(this.selectedTipoTrabajador),
                );
            }
            if (this.selectedCargo) {
                resultados = resultados.filter(
                    (p) => String(p.cargo) === String(this.selectedCargo),
                );
            }
            if (this.searchPersonal) {
                const term = this.searchPersonal.toLowerCase();
                resultados = resultados.filter(
                    (p) =>
                        (p.nombre_completo || "")
                            .toLowerCase()
                            .includes(term) || (p.dni || "").includes(term),
                );
            }
            this.personalesFiltrados = resultados;
            this.personalPage = 1;
        },

        toggleCurso(id) {
            const idx = this.selectedCourseIds.indexOf(id);
            if (idx === -1) {
                this.selectedCourseIds.push(id);
            } else {
                this.selectedCourseIds.splice(idx, 1);
            }
            this.selectAllCursos =
                this.selectedCourseIds.length === this.cursos.length &&
                this.cursos.length > 0;
        },

        toggleAllCursos() {
            if (this.selectAllCursos) {
                this.selectedCourseIds = [];
                this.selectAllCursos = false;
            } else {
                this.selectedCourseIds = this.cursos
                    .map((c) => c.Id)
                    .filter(Boolean);
                this.selectAllCursos = true;
            }
        },

        togglePersonal(dni) {
            const idx = this.selectedUsernames.indexOf(dni);
            if (idx === -1) {
                this.selectedUsernames.push(dni);
            } else {
                this.selectedUsernames.splice(idx, 1);
            }
            this.selectAllPersonal =
                this.selectedUsernames.length ===
                    this.personalesFiltrados.length &&
                this.personalesFiltrados.length > 0;
        },

        toggleAllPersonal() {
            if (this.selectAllPersonal) {
                this.selectedUsernames = [];
                this.selectAllPersonal = false;
            } else {
                this.selectedUsernames = this.personalesFiltrados
                    .map((p) => p.dni)
                    .filter(Boolean);
                this.selectAllPersonal = true;
            }
        },

        cerrar() {
            this.open = false;
            this.view = "filters";
            this.selectedSistema = "";
            this.selectedArea = "";
            this.selectedCliente = "";
            this.selectedSucursal = "";
            this.selectedTipoTrabajador = "";
            this.selectedCargo = "";
            this.searchPersonal = "";
            this.searchCurso = "";
            this.selectedEstadoId = "0";
            this.selectedFechaDesde = "";
            this.selectedFechaHasta = "";
            this.areas = [];
            this.selectedCourseIds = [];
            this.selectedUsernames = [];
            this.selectAllCursos = false;
            this.selectAllPersonal = false;
            this.resultados = [];
            this.totalResultados = 0;
            this.buscando = false;
        },

        volverAFiltros() {
            this.view = "filters";
            this.resultados = [];
            this.totalResultados = 0;
            this.buscando = false;
        },

        async exportarPDFRecord() {
            if (this.selectedUsernames.length === 0) {
                Swal.fire(
                    "Atención",
                    "Debe seleccionar al menos un personal.",
                    "warning",
                );
                return;
            }
            if (this.selectedCourseIds.length === 0) {
                Swal.fire(
                    "Atención",
                    "Debe seleccionar al menos un curso.",
                    "warning",
                );
                return;
            }

            this.buscando = true;

            try {
                const payload = {
                    usernames: this.selectedUsernames || null,
                    courseIds: this.selectedCourseIds || null,
                    desde: this.selectedFechaDesde || null,
                    hasta: this.selectedFechaHasta || null,
                    estadoId: parseInt(this.selectedEstadoId) || 0,
                };

                const response = await axios.post(
                    "/api/obtener-personal-record",
                    payload,
                );

                if (!response.data.success) {
                    Swal.fire(
                        "Error",
                        response.data.message || "No se obtuvieron resultados.",
                        "warning",
                    );
                    return;
                }

                const personales = response.data.Personales || [];
                if (personales.length === 0) {
                    Swal.fire(
                        "Atención",
                        "No se encontraron resultados para generar el PDF.",
                        "warning",
                    );
                    return;
                }

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF({
                    orientation: "portrait",
                    unit: "mm",
                    format: "a4",
                });

                const logoSol = await _cargarImagen("/images/logo_sol.png");

                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                const marginL = 14;
                const marginR = 14;
                const contentWidth = pageWidth - marginL - marginR;

                // ── Formatea fecha "YYYY-MM-DD" → "DD/MM/YYYY" ──────────────────
                const fmtFecha = (val) => {
                    if (!val) return "";
                    const m = String(val).match(/(\d{4})-(\d{2})-(\d{2})/);
                    return m ? `${m[3]}/${m[2]}/${m[1]}` : val;
                };

                // ── Rango de fechas para el subtítulo ────────────────────────────
                const rangoTexto = (() => {
                    const d = this.selectedFechaDesde
                        ? fmtFecha(this.selectedFechaDesde)
                        : null;
                    const h = this.selectedFechaHasta
                        ? fmtFecha(this.selectedFechaHasta)
                        : null;
                    if (d && h) return `Del ${d} al ${h}`;
                    if (d) return `Desde ${d}`;
                    if (h) return `Hasta ${h}`;
                    return "";
                })();

                // ── Cabecera (logo + título) – se dibuja en la página actual ─────
                const dibujarCabecera = () => {
                    let logoBottomY = 26;
                    const logoWidth = 60;

                    if (logoSol) {
                        const ratio = logoSol.height / logoSol.width;
                        const logoH = logoWidth * ratio;
                        doc.addImage(
                            logoSol,
                            "PNG",
                            marginL,
                            10,
                            logoWidth,
                            logoH,
                        );
                        logoBottomY = 10 + logoH;
                    }

                    // Línea + dirección bajo el logo
                    const lineY = logoBottomY + 2;
                    doc.setDrawColor(150, 150, 150);
                    doc.setLineWidth(0.2);
                    doc.line(marginL, lineY, marginL + 75, lineY);

                    doc.setFont("helvetica", "italic");
                    doc.setFontSize(7);
                    doc.setTextColor(80, 80, 80);
                    doc.text(
                        "Chimbote: Calle Los Laureles Nº206 Urb. La Caleta",
                        marginL,
                        lineY + 4,
                    );
                    doc.text("RUC: 20445414833", marginL, lineY + 7);

                    // Título centrado (debajo del logo y dirección)
                    const tituloY = logoBottomY + 25;
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(13);
                    doc.setTextColor(0, 0, 0);
                    const titulo = "RECORD DE CAPACITACIONES";
                    doc.text(titulo, pageWidth / 2, tituloY, { align: "center" });

                    // Subrayado del título
                    const tW = doc.getTextWidth(titulo);
                    doc.setLineWidth(0.3);
                    doc.setDrawColor(0, 0, 0);
                    doc.line(
                        pageWidth / 2 - tW / 2,
                        tituloY + 1.5,
                        pageWidth / 2 + tW / 2,
                        tituloY + 1.5,
                    );

                    // Subtítulo con rango
                    if (rangoTexto) {
                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(9);
                        doc.text(rangoTexto, pageWidth / 2, tituloY + 7, {
                            align: "center",
                        });
                    }

                    // Y de inicio del contenido
                    return rangoTexto ? tituloY + 15 : tituloY + 10;
                };

                // ── Primera cabecera ─────────────────────────────────────────────
                let yPos = dibujarCabecera();

                // ── Iterar por personal ──────────────────────────────────────────
                for (let idx = 0; idx < personales.length; idx++) {
                    const personal = personales[idx];

                    // Espacio mínimo estimado antes de decidir nueva página
                    // (etiqueta sucursal ~6 + tabla datos ~22 + encabezado cursos ~8 = ~36 mm)
                    if (idx > 0) {
                        if (yPos + 36 > pageHeight - 10) {
                            doc.addPage();
                            yPos = 14;
                        } else {
                            yPos += 6; // separación entre personas
                        }
                    }

                    // ── Etiqueta de sucursal ─────────────────────────────────────
                    doc.setFillColor(255, 255, 255);
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(9);
                    doc.setTextColor(0, 0, 0);
                    doc.text(personal.Sucursal || "—", marginL, yPos + 4);
                    yPos += 7;

                    // ── Tabla DATOS GENERALES ────────────────────────────────────
                    // Cabecera de sección
                    doc.setFillColor(253, 245, 230);
                    doc.rect(marginL, yPos, contentWidth, 6, "F");
                    doc.setDrawColor(0, 0, 0);
                    doc.setLineWidth(0.2);
                    doc.rect(marginL, yPos, contentWidth, 6, "S");
                    doc.setFont("helvetica", "bold");
                    doc.setFontSize(8);
                    doc.setTextColor(0, 0, 0);
                    doc.text("DATOS GENERALES", marginL + 2, yPos + 4);
                    yPos += 6;

                    // Fila 1: Nº Documento | valor | Apellidos y Nombres | valor
                    const col1W = 28,
                        col2W = 38,
                        col3W = 38,
                        col4W = contentWidth - col1W - col2W - col3W;
                    const rowH = 6;

                    const drawCell = (
                        x,
                        y,
                        w,
                        h,
                        text,
                        bold = false,
                        bg = null,
                    ) => {
                        if (bg) {
                            doc.setFillColor(...bg);
                            doc.rect(x, y, w, h, "F");
                        }
                        doc.setDrawColor(0, 0, 0);
                        doc.setLineWidth(0.2);
                        doc.rect(x, y, w, h, "S");
                        doc.setFont("helvetica", bold ? "bold" : "normal");
                        doc.setFontSize(8);
                        doc.setTextColor(0, 0, 0);
                        // Truncar si es necesario
                        const maxW = w - 3;
                        let t = text || "—";
                        while (doc.getTextWidth(t) > maxW && t.length > 1)
                            t = t.slice(0, -1);
                        doc.text(t, x + 2, y + 4);
                    };

                    let cx = marginL;
                    // Fila 1
                    drawCell(
                        cx,
                        yPos,
                        col1W,
                        rowH,
                        "Nº Documento",
                        true,
                        [253, 245, 230],
                    );
                    drawCell(
                        cx + col1W,
                        yPos,
                        col2W,
                        rowH,
                        personal.NroDoc || "—",
                    );
                    drawCell(
                        cx + col1W + col2W,
                        yPos,
                        col3W,
                        rowH,
                        "Apellidos y Nombres",
                        true,
                        [253, 245, 230],
                    );
                    drawCell(
                        cx + col1W + col2W + col3W,
                        yPos,
                        col4W,
                        rowH,
                        personal.NombreCompleto || "—",
                    );
                    yPos += rowH;

                    // Fila 2: Código Pers. | valor | Cargo | valor
                    drawCell(
                        cx,
                        yPos,
                        col1W,
                        rowH,
                        "Código Pers.",
                        true,
                        [253, 245, 230],
                    );
                    drawCell(
                        cx + col1W,
                        yPos,
                        col2W,
                        rowH,
                        personal.CodigoPersonal || "—",
                    );
                    drawCell(
                        cx + col1W + col2W,
                        yPos,
                        col3W,
                        rowH,
                        "Cargo",
                        true,
                        [253, 245, 230],
                    );
                    drawCell(
                        cx + col1W + col2W + col3W,
                        yPos,
                        col4W,
                        rowH,
                        personal.Cargo || "—",
                    );
                    yPos += rowH;

                    // Fila 3: Tipo Trabajador | valor | Cliente | valor
                    drawCell(
                        cx,
                        yPos,
                        col1W,
                        rowH,
                        "Tipo Trabajador",
                        true,
                        [253, 245, 230],
                    );
                    drawCell(
                        cx + col1W,
                        yPos,
                        col2W,
                        rowH,
                        personal.TipoTrabajador || "—",
                    );
                    drawCell(
                        cx + col1W + col2W,
                        yPos,
                        col3W,
                        rowH,
                        "Cliente",
                        true,
                        [253, 245, 230],
                    );
                    drawCell(
                        cx + col1W + col2W + col3W,
                        yPos,
                        col4W,
                        rowH,
                        personal.Cliente || "—",
                    );
                    yPos += rowH;

                    // ── Tabla de cursos con autoTable ────────────────────────────
                    const cursos = personal.Cursos || [];
                    const filas = cursos.map((c, i) => [
                        i + 1,
                        c.Nombre || "",
                        c.Estado || "",
                        c.Nota_Final != null
                            ? (c.Fecha_Nota ? fmtFecha(c.Fecha_Nota) : "")
                            : (c.Fecha_Ultimo_Acceso ? fmtFecha(c.Fecha_Ultimo_Acceso) : ""),
                        c.Nota_Final != null ? c.Nota_Final : "",
                    ]);

                    doc.autoTable({
                        startY: yPos,
                        head: [
                            [
                                "It",
                                "Capacitación",
                                "Estado",
                                "Fecha / Últ. acceso",
                                "Nota",
                            ],
                        ],
                        body: filas,
                        theme: "grid",
                        styles: {
                            fontSize: 7.5,
                            textColor: [0, 0, 0],
                            lineColor: [0, 0, 0],
                            lineWidth: 0.1,
                            valign: "middle",
                            cellPadding: 1.5,
                        },
                        headStyles: {
                            fillColor: [253, 245, 230],
                            textColor: [0, 0, 0],
                            fontStyle: "bold",
                            halign: "center",
                        },
                        columnStyles: {
                            0: { cellWidth: 8, halign: "center" },
                            1: { cellWidth: "auto", halign: "left" },
                            2: { cellWidth: 24, halign: "center" },
                            3: { cellWidth: 20, halign: "center" },
                            4: { cellWidth: 12, halign: "center" },
                        },
                        margin: { left: marginL, right: marginR },
                        // Si la tabla se corta en otra página, redibujar cabecera
                        didDrawPage: (data) => {
                            if (data.pageNumber > 1 || idx > 0) {
                                // la cabecera ya fue dibujada al addPage arriba,
                                // aquí solo actualizamos yPos si autoTable salto de página
                            }
                        },
                    });

                    yPos = doc.lastAutoTable.finalY + 2;
                }

                // ── Guardar y descargar ──────────────────────────────────────────
                const d = new Date();
                const fechaRep = `${d.getFullYear()}_${String(d.getMonth() + 1).padStart(2, "0")}_${String(d.getDate()).padStart(2, "0")}_${String(d.getHours()).padStart(2, "0")}_${String(d.getMinutes()).padStart(2, "0")}`;
                const nombreArchivo = `RECORD_CAPACITACIONES_${fechaRep}.pdf`;

                const pdfBlob = doc.output("blob");

                try {
                    const formData = new FormData();
                    formData.append("nombre_archivo", nombreArchivo);
                    formData.append("archivo_pdf", pdfBlob, nombreArchivo);
                    await axios.post(
                        "/api/capacitacion/registrar-reporte",
                        formData,
                    );
                    window.dispatchEvent(
                        new CustomEvent("historial-reportes-actualizado"),
                    );
                } catch (error) {
                    console.error("Error al guardar en historial:", error);
                }

                saveAs(pdfBlob, nombreArchivo);
                Swal.fire("Éxito", "PDF generado correctamente.", "success");
            } catch (error) {
                console.error(error);
                Swal.fire("Error", "No se pudo generar el PDF.", "error");
            } finally {
                this.buscando = false;
            }
        },
    }));
});
