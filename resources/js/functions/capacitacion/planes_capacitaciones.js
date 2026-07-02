import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import axios from "axios";

export default document.addEventListener("alpine:init", () => {
    Alpine.data("planesCapacApp", () => ({
        pdfUrl: "",
        open: false,
        openConsultas: false,
        planes: [],
        loadingPlanes: false,
        selectedCodigo: null,
        selectedPlan: null,
        selectedAbreviatura: null,
        cursos: [],
        loadingCursos: false,
        filtroSistema: "",
        filtroArea: "",
        filtroCliente: "",
        filtroMes: "",
        filtroAnio: "",
        openSeleccionCliente: false,
        clientesPCA: [],
        loadingClientesPCA: false,
        selectedClientePCA: "",

        async abrirPDF() {
            Swal.fire({
                title: "Generando PDF...",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading(),
            });

            try {
                const { data } = await axios.get("/api/obtener-plan-pce");
                const anio = data.Anio;

                const doc = new jsPDF({
                    orientation: "landscape",
                    unit: "mm",
                    format: "a4",
                });

                const pageWidth = doc.internal.pageSize.getWidth();

                const months = [
                    "ENERO",
                    "FEBRERO",
                    "MARZO",
                    "ABRIL",
                    "MAYO",
                    "JUNIO",
                    "JULIO",
                    "AGOSTO",
                    "SETIEMBRE",
                    "OCTUBRE",
                    "NOVIEMBRE",
                    "DICIEMBRE",
                ];

                const monthHeaders = [
                    "ENE",
                    "FEB",
                    "MAR",
                    "ABR",
                    "MAY",
                    "JUN",
                    "JUL",
                    "AGO",
                    "SEP",
                    "OCT",
                    "NOV",
                    "DIC",
                ];
                const TOTAL_COLS = 5 + 24;

                let item = 0;
                const rawRows = [];

                for (const sistema of data.Sistemas) {
                    rawRows.push({
                        isSystem: true,
                        label: sistema.Nombre.toUpperCase(),
                    });

                    if (sistema.Cursos.length === 0) {
                        rawRows.push({ isEmpty: true });
                        continue;
                    }

                    let item = 0;

                    for (const curso of sistema.Cursos) {
                        item++;
                        const row = {
                            isSystem: false,
                            item: String(item),
                            curso: curso.Nombre.toUpperCase(),
                            dirigido: curso.Dirigido_a.toUpperCase(),
                            area: curso.Area_Resp.toUpperCase(),
                            tiempo: "01 HORA",
                        };

                        for (const m of months) {
                            row[`${m}_1`] = "";
                            row[`${m}_2`] = "";
                        }

                        for (const prog of curso.Programaciones) {
                            const idx = months.indexOf(prog.Mes.toUpperCase());
                            if (idx < 0) continue;
                            const key = `${months[idx]}_${prog.Bloque}`;
                            if (prog.Ejecutado) row[key] = "E";
                            else if (prog.Programado) row[key] = "P";
                            else if (prog.Reprogramado) row[key] = "R";
                        }

                        rawRows.push(row);
                    }
                }

                const bodyRows = rawRows.map((r) => {
                    if (r.isSystem) {
                        return [
                            {
                                content: r.label,
                                colSpan: TOTAL_COLS,
                                styles: {
                                    fillColor: [184, 204, 228],
                                    textColor: [0, 0, 0],
                                    fontStyle: "bold",
                                    fontSize: 7,
                                    halign: "left",
                                    cellPadding: {
                                        top: 2,
                                        bottom: 2,
                                        left: 2,
                                        right: 2,
                                    },
                                },
                            },
                        ];
                    }

                    if (r.isEmpty) {
                        return Array.from({ length: TOTAL_COLS }, () => ({
                            content: "",
                            styles: {
                                cellPadding: {
                                    top: 3,
                                    bottom: 3,
                                    left: 2,
                                    right: 2,
                                },
                                lineColor: [0, 0, 0],
                                lineWidth: 0.1,
                            },
                        }));
                    }

                    return [
                        r.item,
                        r.curso,
                        r.dirigido,
                        r.area,
                        r.tiempo,
                        ...months.flatMap((m) => [
                            r[`${m}_1`] || "",
                            r[`${m}_2`] || "",
                        ]),
                    ];
                });

                autoTable(doc, {
                    startY: 10,
                    margin: { left: 5, right: 5 },
                    head: [
                        [
                            {
                                content: `PROGRAMA DE CAPACITACIÓN ESTÁNDAR DEL AÑO ${anio}`,
                                colSpan: TOTAL_COLS,
                                styles: {
                                    fillColor: [184, 204, 228],
                                    textColor: [0, 0, 0],
                                    fontStyle: "bold",
                                    fontSize: 9,
                                    halign: "center",
                                    valign: "middle",
                                },
                            },
                        ],
                        [
                            {
                                content: "ITEM",
                                rowSpan: 2,
                                styles: { halign: "center", valign: "middle" },
                            },
                            {
                                content: "TEMA",
                                rowSpan: 2,
                                styles: { halign: "center", valign: "middle" },
                            },
                            {
                                content: "DIRIGIDO A",
                                rowSpan: 2,
                                styles: { halign: "center", valign: "middle" },
                            },
                            {
                                content: "ÁREA\nRESPONSABLE",
                                rowSpan: 2,
                                styles: { halign: "center", valign: "middle" },
                            },
                            {
                                content: "TIEMPO",
                                rowSpan: 2,
                                styles: { halign: "center", valign: "middle" },
                            },
                            {
                                content: `CRONOGRAMA ${anio}`,
                                colSpan: 24,
                                styles: {
                                    halign: "center",
                                    valign: "middle",
                                    fontStyle: "bold",
                                },
                            },
                        ],
                        [
                            ...monthHeaders.map((m) => ({
                                content: m,
                                colSpan: 2,
                                styles: { halign: "center", valign: "middle" },
                            })),
                        ],
                    ],
                    body: bodyRows,
                    styles: {
                        fontSize: 6,
                        cellPadding: {
                            top: 1.5,
                            bottom: 1.5,
                            left: 1.5,
                            right: 1.5,
                        },
                        valign: "middle",
                        lineColor: [0, 0, 0],
                        lineWidth: 0.1,
                    },
                    headStyles: {
                        fillColor: [255, 255, 255],
                        textColor: [0, 0, 0],
                        fontSize: 6,
                        fontStyle: "bold",
                        halign: "center",
                        valign: "middle",
                    },
                    columnStyles: {
                        0: { cellWidth: 8, halign: "center" }, // ITEM
                        4: { cellWidth: 12, halign: "center" }, // TIEMPO
                        ...Object.fromEntries(
                            Array.from({ length: 24 }, (_, i) => [
                                i + 5,
                                { cellWidth: 5, halign: "center" },
                            ]),
                        ),
                    },
                    didParseCell(data) {
                        if (data.section === "body" && data.column.index >= 5) {
                            const val = data.cell.text
                                ?.join("")
                                ?.trim()
                                ?.toUpperCase();
                            if (val === "P") {
                                data.cell.styles.fillColor = [220, 38, 38];
                                data.cell.styles.textColor = [255, 255, 255];
                                data.cell.styles.fontStyle = "bold";
                            } else if (val === "E") {
                                data.cell.styles.fillColor = [37, 99, 235];
                                data.cell.styles.textColor = [255, 255, 255];
                                data.cell.styles.fontStyle = "bold";
                            } else if (val === "R") {
                                data.cell.styles.fillColor = [234, 88, 12];
                                data.cell.styles.textColor = [255, 255, 255];
                                data.cell.styles.fontStyle = "bold";
                            }
                        }
                    },
                });

                const blob = doc.output("blob");
                if (this.pdfUrl) URL.revokeObjectURL(this.pdfUrl);
                this.pdfUrl = URL.createObjectURL(blob);

                Swal.close();
                this.open = true;
            } catch (e) {
                Swal.close();
                console.error(e);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se pudo generar el PDF.",
                });
            }
        },

        get sistemasUnicos() {
            return [...new Set(this.cursos.map(c => c.Sistema).filter(Boolean))].sort();
        },

        get areasUnicas() {
            return [...new Set(this.cursos.map(c => c.Area).filter(Boolean))].sort();
        },

        get clientesUnicos() {
            return [...new Set(this.cursos.map(c => c.Cliente).filter(Boolean))].sort();
        },

        _parseFecha(fecha) {
            if (!fecha) return null;
            const partes = fecha.split('/');
            if (partes.length < 3) return null;
            const dia = +partes[0];
            const mes = +partes[1] - 1;
            const anioHora = partes[2].split(' ');
            return new Date(+anioHora[0], mes, dia);
        },

        _meses: [
            { valor: '1', label: 'Enero' }, { valor: '2', label: 'Febrero' },
            { valor: '3', label: 'Marzo' }, { valor: '4', label: 'Abril' },
            { valor: '5', label: 'Mayo' }, { valor: '6', label: 'Junio' },
            { valor: '7', label: 'Julio' }, { valor: '8', label: 'Agosto' },
            { valor: '9', label: 'Setiembre' }, { valor: '10', label: 'Octubre' },
            { valor: '11', label: 'Noviembre' }, { valor: '12', label: 'Diciembre' },
        ],

        get mesesUnicos() {
            const meses = new Set();
            this.cursos.forEach(c => {
                const d = this._parseFecha(c.Fecha_Creacion);
                if (d) meses.add(d.getMonth() + 1);
            });
            return [...meses].sort((a, b) => a - b).map(m => ({
                valor: String(m),
                label: this._meses[m - 1].label,
            }));
        },

        get aniosUnicos() {
            const anios = new Set();
            this.cursos.forEach(c => {
                const d = this._parseFecha(c.Fecha_Creacion);
                if (d) anios.add(d.getFullYear());
            });
            return [...anios].sort((a, b) => b - a);
        },

        get cursosFiltrados() {
            return this.cursos.filter(c => {
                if (this.filtroSistema && c.Sistema !== this.filtroSistema) return false;
                if (this.filtroArea && c.Area !== this.filtroArea) return false;
                if (this.filtroCliente && c.Cliente !== this.filtroCliente) return false;
                if (this.filtroMes || this.filtroAnio) {
                    const d = this._parseFecha(c.Fecha_Creacion);
                    if (!d) return false;
                    if (this.filtroMes && String(d.getMonth() + 1) !== this.filtroMes) return false;
                    if (this.filtroAnio && String(d.getFullYear()) !== this.filtroAnio) return false;
                }
                return true;
            });
        },

        abrirConsultas() {
            this.openConsultas = true;
            if (this.planes.length === 0) {
                this.cargarPlanes();
            }
        },

        cerrarConsultas() {
            this.openConsultas = false;
            this.selectedCodigo = null;
            this.selectedPlan = null;
            this.selectedAbreviatura = null;
            this.cursos = [];
            this.filtroSistema = "";
            this.filtroArea = "";
            this.filtroCliente = "";
            this.filtroMes = "";
            this.filtroAnio = "";
        },

        async abrirSeleccionCliente() {
            this.openSeleccionCliente = true;
            this.selectedClientePCA = "";
            if (this.clientesPCA.length === 0) {
                this.loadingClientesPCA = true;
                try {
                    const { data } = await axios.get("/get-clientes-pac");
                    this.clientesPCA = Array.isArray(data) ? data : [];
                } catch (e) {
                    console.error(e);
                    this.clientesPCA = [];
                } finally {
                    this.loadingClientesPCA = false;
                }
            }
        },

        cerrarSeleccionCliente() {
            this.openSeleccionCliente = false;
            this.selectedClientePCA = "";
        },

        obtenerPDF_PCA() {
            // TODO: Implementar generación de PDF para PCA
        },

        async cargarPlanes() {
            this.loadingPlanes = true;
            try {
                const { data } = await axios.get("/api/obtener-tipos-curso");
                this.planes = data.Tipos || [];
            } catch (e) {
                console.error(e);
                this.planes = [];
            } finally {
                this.loadingPlanes = false;
            }
        },

        async seleccionarPlan(plan) {
            this.selectedCodigo = plan.Codigo;
            this.selectedPlan = plan.Nombre;
            this.selectedAbreviatura = plan.Abreviatura;
            this.filtroSistema = "";
            this.filtroArea = "";
            this.filtroCliente = "";
            this.filtroMes = "";
            this.filtroAnio = "";
            this.loadingCursos = true;
            this.cursos = [];
            try {
                const { data } = await axios.get(`/api/obtener-cursos-por-plan/${plan.Codigo}`);
                this.cursos = data.Cursos || [];
            } catch (e) {
                console.error(e);
                this.cursos = [];
            } finally {
                this.loadingCursos = false;
            }
        },

        cerrar() {
            this.open = false;
            if (this.pdfUrl) {
                URL.revokeObjectURL(this.pdfUrl);
                this.pdfUrl = "";
            }
        },
    }));
});
