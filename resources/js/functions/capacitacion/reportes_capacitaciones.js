import axios from "axios";
import ExcelJS from "exceljs";
import { saveAs } from "file-saver";

export default document.addEventListener("alpine:init", () => {
    Alpine.data("reportesApp", () => ({
        abrirModalReporte() {
            window.dispatchEvent(new CustomEvent("abrir-reporte"));
        },
    }));

    Alpine.data("modalReporte", () => ({
        open: false,
        view: "filters",

        selectedSistema: "",
        selectedArea: "",
        selectedCurso: "",
        selectedPeriodo: "",
        selectedEstado: "PENDIENTE",
        selectedSucursal: "",

        sistemas: [],
        areas: [],
        cursos: [],
        todosLosCursos: [],
        periodos: [],
        sucursales: [],

        personal: [],
        totalPersonal: 0,
        loadingPersonal: false,
        loadingSistemas: false,
        loadingAreas: false,
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

            await this.cargarSistemas();
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

        async cargarSistemas() {
            this.loadingSistemas = true;
            try {
                const response = await axios.get("/api/get-capacitacion-areas");

                this.sistemas = response.data;
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
                const response = await axios.get(
                    `/api/get-areas-por-sistema/${sistemaId}`,
                );

                if (response.data.success) {
                    this.areas = response.data.areas;
                } else {
                    this.areas = [];
                }
            } catch (error) {
                console.error(error);
                this.areas = [];
            } finally {
                this.loadingAreas = false;
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
            const inicio = this.formatearFecha(curso.startdate);
            const fin = this.formatearFecha(curso.enddate);

            if (!inicio || !fin) {
                return null;
            }

            return `${inicio} - ${fin}`;
        },

        async cargarCursos(categoriaId) {
            if (!categoriaId) {
                this.cursos = [];
                this.todosLosCursos = [];
                this.periodos = [];
                this.selectedCurso = "";
                this.selectedPeriodo = "";
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

                    const periodosUnicos = [];

                    this.todosLosCursos.forEach((curso) => {
                        const periodo = this.obtenerPeriodo(curso);

                        if (!periodo) {
                            return;
                        }

                        if (
                            !periodosUnicos.some((p) => p.periodo === periodo)
                        ) {
                            periodosUnicos.push({
                                id: curso.id,
                                periodo: periodo,
                            });
                        }
                    });

                    this.periodos = periodosUnicos;
                } else {
                    this.cursos = [];
                    this.todosLosCursos = [];
                    this.periodos = [];
                }
            } catch (error) {
                console.error(error);

                this.cursos = [];
                this.todosLosCursos = [];
                this.periodos = [];
            } finally {
                this.loadingCursos = false;
            }
        },

        filtrarCursosPorPeriodo() {
            if (!this.selectedPeriodo) {
                this.cursos = this.todosLosCursos;
                return;
            }

            this.cursos = this.todosLosCursos.filter((curso) => {
                const periodoCurso = this.obtenerPeriodo(curso);

                return periodoCurso === this.selectedPeriodo;
            });

            this.selectedCurso = "";
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
            this.selectedPeriodo = "";
            this.selectedEstado = "PENDIENTE";
            this.selectedSucursal = "";

            this.areas = [];
            this.cursos = [];
            this.todosLosCursos = [];
            this.periodos = [];

            this.personal = [];
            this.totalPersonal = 0;
            this.currentPage = 1;
            this.loadingPersonal = false;

            this.cacheReportes = {};
        },

        volverAFiltros() {
            this.view = "filters";
        },

        ordenar(columna) {
            if (this.sortColumn === columna) {
                if (this.sortDirection === 'asc') {
                    this.sortDirection = 'desc';
                } else {
                    this.sortColumn = null;
                    this.sortDirection = null;
                }
            } else {
                this.sortColumn = columna;
                this.sortDirection = 'asc';
            }
            this.currentPage = 1;
        },

        get nombreCurso() {
            return (
                this.cursos.find((c) => c.id == this.selectedCurso)?.fullname ||
                ""
            );
        },

        get personalPaginado() {
            let datos = [...this.personal];

            if (this.sortColumn && this.sortDirection) {
                datos.sort((a, b) => {
                    const valA = (a[this.sortColumn] || '').toString();
                    const valB = (b[this.sortColumn] || '').toString();
                    const cmp = valA.localeCompare(valB, 'es', { sensitivity: 'base' });
                    return this.sortDirection === 'asc' ? cmp : -cmp;
                });
            } else {
                datos.sort((a, b) =>
                    (a.NombreCompleto || '').localeCompare(b.NombreCompleto || '', 'es', { sensitivity: 'base' })
                );
            }

            const start = (this.currentPage - 1) * this.perPage;
            const end = start + this.perPage;

            return datos.slice(start, end);
        },

        get totalPages() {
            return Math.ceil(this.personal.length / this.perPage);
        },

        async exportarExcel() {
            const curso = this.cursos.find((c) => c.id == this.selectedCurso);
            const nombreCurso = curso ? curso.fullname : "reporte";

            const workbook = new ExcelJS.Workbook();
            const sheet = workbook.addWorksheet("Personal");

            const headers = [
                "#",
                "Código Pers.",
                "Nombre Completo",
                "DNI",
                "Tipo Trabajador",
                "Estado",
            ];

            const headerRow = sheet.addRow(headers);

            headerRow.eachCell((cell) => {
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
            });

            const dataOrdenada = [...this.personal].sort((a, b) =>
                (a.NombreCompleto || "").localeCompare(b.NombreCompleto || ""),
            );

            dataOrdenada.forEach((p, i) => {
                sheet.addRow([
                    i + 1,
                    p.CodigoPers,
                    p.NombreCompleto,
                    p.DNI,
                    p.TipoTrabajador,
                    p.Estado,
                ]);
            });

            sheet.columns = [
                { width: 5 },
                { width: 15 },
                { width: 40 },
                { width: 15 },
                { width: 25 },
                { width: 15 },
            ];

            sheet.autoFilter = {
                from: "A1",
                to: "F1",
            };

            sheet.eachRow((row, rowNumber) => {
                row.eachCell((cell) => {
                    cell.border = {
                        top: { style: "thin" },
                        left: { style: "thin" },
                        bottom: { style: "thin" },
                        right: { style: "thin" },
                    };

                    cell.alignment = {
                        vertical: "middle",
                        horizontal: rowNumber === 1 ? "center" : "left",
                        wrapText: true,
                    };
                });
            });

            const buffer = await workbook.xlsx.writeBuffer();

            const blob = new Blob([buffer], {
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            });

            saveAs(blob, `reporte_${nombreCurso}_${this.selectedEstado}.xlsx`);

            Swal.fire("Éxito", "Reporte exportado a Excel correctamente.", "success");
        },

        async exportarPDF() {
            const { jsPDF } = window.jspdf;

            const doc = new jsPDF({
                orientation: "landscape",
                unit: "mm",
                format: "a4"
            });

            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();

            const curso = this.cursos.find((c) => c.id == this.selectedCurso);
            const nombreCurso = curso ? curso.fullname : "REPORTE";

            const sistema = this.sistemas.find((s) => s.codigo == this.selectedSistema);
            const area = this.areas.find((a) => a.codModdle == this.selectedArea);

            // Logo image area
            const loadImage = (url) => {
                return new Promise((resolve) => {
                    const img = new Image();
                    img.onload = () => resolve(img);
                    img.onerror = () => resolve(null);
                    img.src = url;
                });
            };

            const logoSol = await loadImage("/images/logo_sol.png");
            
            let logoBottomY = 26;
            let logoWidth = 60;
            const startX = 14;

            if (logoSol) {
                const ratio = logoSol.height / logoSol.width;
                const height = logoWidth * ratio;
                doc.addImage(logoSol, 'PNG', startX, 10, logoWidth, height);
                logoBottomY = 10 + height;
            }

            // Draw line and address right below the logo
            const lineY = logoBottomY + 2;
            doc.setDrawColor(150, 150, 150);
            doc.setLineWidth(0.2);
            doc.line(startX, lineY, startX + 75, lineY);

            doc.setFont("helvetica", "italic");
            doc.setFontSize(7);
            doc.setTextColor(80, 80, 80);
            doc.text("Chimbote: Calle Los Laureles Nº206 Urb. La Caleta", startX, lineY + 4);
            doc.text("RUC: 20445414833", startX, lineY + 7);

            const title = sistema ? `${sistema.descripcion.toUpperCase()}` : "";
            const subtitle = area ? area.Area : "";

            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(12);
            
            // Right aligned Title (Sistema)
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

            // Right aligned Subtitle (Área)
            doc.setFont("helvetica", "normal");
            doc.setFontSize(10);
            doc.text(subtitle, titleX, 46, { align: "right" });

            // Sucursal + Total Personal (Left)
            const sucursal = this.sucursales.find((s) => s.codigo == this.selectedSucursal);
            const sucursalNombre = sucursal ? sucursal.sucursal.toUpperCase() : "SUCURSAL";
            const textoSucursal = `${sucursalNombre} - ${this.personal.length} personal(es)`;

            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text(textoSucursal, startX, 56);

            // Nombre del curso (Left, below Sucursal)
            doc.setFont("helvetica", "normal");
            doc.setFontSize(9);
            doc.text(nombreCurso.toUpperCase(), startX, 61);

            const personalOrdenado = [...this.personal].sort((a, b) =>
                (a.NombreCompleto || "").localeCompare(b.NombreCompleto || ""),
            );

            const filas = personalOrdenado.map((p, i) => [
                i + 1,
                p.CodigoPers || "",
                p.NombreCompleto || "",
                p.DNI || "",
                p.TipoTrabajador || "",
                p.Estado || "",
            ]);

            doc.autoTable({
                startY: 66,
                head: [
                    [
                        "It",
                        "Código\nPers.",
                        "Apellidos y Nombres",
                        "N° Doc.",
                        "Tipo Trabajador",
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
                    halign: 'center',
                    valign: 'middle'
                },
                headStyles: {
                    fillColor: [253, 245, 230],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold',
                    halign: 'center'
                },
                columnStyles: {
                    0: { cellWidth: 10 },
                    1: { cellWidth: 20 },
                    2: { cellWidth: 'auto', halign: 'left' },
                    3: { cellWidth: 25 },
                    4: { cellWidth: 35 },
                    5: { cellWidth: 25 },
                },
                margin: { left: 14, right: 14 },
            });

            doc.save(`reporte_${nombreCurso}_${this.selectedEstado}.pdf`);

            Swal.fire("Éxito", "Reporte exportado a PDF correctamente.", "success");
        },

        async obtenerPersonal() {
            if (!this.selectedCurso || !this.selectedSucursal) return;

            const cacheKey = [
                this.selectedCurso,
                this.selectedSucursal,
                this.selectedEstado,
            ].join("_");

            if (this.cacheReportes[cacheKey]) {
                this.personal = this.cacheReportes[cacheKey].personal;
                this.totalPersonal = this.cacheReportes[cacheKey].total;

                this.currentPage = 1;
                this.view = "personal";

                return;
            }

            this.loadingPersonal = true;

            try {
                const response = await axios.get("/api/get-personal-reporte", {
                    params: {
                        courseId: this.selectedCurso,
                        sucursalId: this.selectedSucursal,
                        estado: this.selectedEstado,
                    },
                });

                if (response.data.success) {
                    this.personal = response.data.personal;
                    this.totalPersonal = response.data.total;

                    this.cacheReportes[cacheKey] = {
                        personal: response.data.personal,
                        total: response.data.total,
                    };

                    this.currentPage = 1;
                    this.view = "personal";
                }
            } catch (error) {
                console.error(error);
            } finally {
                this.loadingPersonal = false;
            }
        },
    }));
});
