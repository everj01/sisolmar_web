import axios from "axios";
import jsPDF from "jspdf";
import autoTable from "jspdf-autotable";
import ExcelJS from "exceljs";
import { saveAs } from "file-saver";

/** Escapa texto antes de inyectarlo como HTML (datos provenientes de la API). */
function _escapeHtml(valor) {
    if (valor === null || valor === undefined) return "";
    return String(valor)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/** Máximo de certificados que se pueden seleccionar. */
const MAX_CERTIFICADOS = 5;

/** Códigos de certificados considerados "portuarios". */
const CERTIFICADOS_PORTUARIOS = ["25", "26", "35", "36", "42"];

/** Columnas del reporte PDF. */
const PDF_COLUMNAS = [
    { label: "#", width: 8, halign: "center" },
    { label: "Código", width: 18 },
    { label: "Nombre completo", width: 45 },
    { label: "DNI", width: 18 },
    { label: "Cargo", width: 40 },
    { label: "Ingreso", width: 18, halign: "center" },
    { label: "Vigencia", width: 16, halign: "center" },
    { label: "Resultado", width: 22, halign: "center" },
    { label: "Emisión", width: 18, halign: "center" },
    { label: "Caduca", width: 18, halign: "center" },
    { label: "Estado", width: 16, halign: "center" },
    { label: "Observación", width: 28 },
    { label: "Escaneo", width: 16, halign: "center" },
];

/** Anchos de columna (caracteres) para el reporte Excel. */
const EXCEL_ANCHOS = [6, 16, 42, 15, 34, 13, 11, 16, 13, 13, 11, 32, 12];

/** Borde delgado para las celdas del reporte Excel. */
const EXCEL_BORDE = {
    top: { style: "thin", color: { argb: "FFCBD5E1" } },
    left: { style: "thin", color: { argb: "FFCBD5E1" } },
    bottom: { style: "thin", color: { argb: "FFCBD5E1" } },
    right: { style: "thin", color: { argb: "FFCBD5E1" } },
};

/** Convierte el índice de columna (1-based) en letra(s) de Excel: 1 -> A, 27 -> AA. */
function _letraColumnaExcel(idx) {
    let letra = "";
    while (idx > 0) {
        const resto = (idx - 1) % 26;
        letra = String.fromCharCode(65 + resto) + letra;
        idx = Math.floor((idx - 1) / 26);
    }
    return letra;
}

/** Aplica fondo y borde a todas las celdas de una fila. */
function _estilizarFilaExcel(sheet, numeroFila, totalColumnas, fillArgb) {
    const fila = sheet.getRow(numeroFila);
    for (let c = 1; c <= totalColumnas; c++) {
        const cell = fila.getCell(c);
        if (fillArgb) {
            cell.fill = {
                type: "pattern",
                pattern: "solid",
                fgColor: { argb: fillArgb },
            };
        }
        cell.border = EXCEL_BORDE;
    }
    return fila;
}

export default document.addEventListener("alpine:init", () => {
    Alpine.data("cursosExternosApp", () => ({
        abrirModalReporteCertificados() {
            window.dispatchEvent(new CustomEvent("abrir-reporte-certificados"));
        },
    }));

    Alpine.data("modalReporteCertificados", () => ({
        open: false,
        /** 'filters' | 'results' */
        view: "filters",

        MAX_CERTIFICADOS,

        loadingSucursales: false,
        loadingTiposPers: false,
        loadingCertificados: false,
        loadingReporte: false,

        sucursales: [],
        tiposPers: [],
        certificados: [],

        selectedSucursal: "",
        selectedTipoPersonal: "",
        selectedVigencia: "",
        selectedEstado: "",
        selectedVencimiento: "",

        selectedCertificados: [],
        searchCertificado: "",
        soloPortuarios: false,

        // Datos del reporte
        reporteData: [],

        // Navegación interna de resultados
        activeCertificado: null,
        activeSucursal: null,
        activeTipo: null,

        // Paginación
        paginaActual: 1,
        porPagina: 50,

        exportandoPdf: false,
        exportandoExcel: false,

        init() {
            window.addEventListener("abrir-reporte-certificados", () => {
                this.abrir();
            });
        },

        async abrir() {
            this.view = "filters";
            this.open = true;
            await this.cargarCatalogos();
        },

        async cargarCatalogos() {
            await Promise.all([
                this.cargarSucursales(),
                this.cargarTiposPers(),
                this.cargarCertificados(),
            ]);
        },

        async cargarSucursales() {
            this.loadingSucursales = true;
            try {
                const { data } = await axios.get(
                    `${VITE_URL_APP}/api/obtener-sucursales`,
                );
                this.sucursales =
                    data?.success && Array.isArray(data?.data) ? data.data : [];
            } catch (e) {
                console.error("Error al cargar sucursales", e);
                this.sucursales = [];
            } finally {
                this.loadingSucursales = false;
            }
        },

        async cargarTiposPers() {
            this.loadingTiposPers = true;
            try {
                const { data } = await axios.get(
                    `${VITE_URL_APP}/api/obtener-tipos-pers`,
                );
                this.tiposPers =
                    data?.success && Array.isArray(data?.data) ? data.data : [];
            } catch (e) {
                console.error("Error al cargar tipos de personal", e);
                this.tiposPers = [];
            } finally {
                this.loadingTiposPers = false;
            }
        },

        async cargarCertificados() {
            this.loadingCertificados = true;
            try {
                const { data } = await axios.get(
                    `${VITE_URL_APP}/api/obtener-certificados`,
                );
                this.certificados =
                    data?.success && Array.isArray(data?.data) ? data.data : [];
            } catch (e) {
                console.error("Error al cargar certificados", e);
                this.certificados = [];
            } finally {
                this.loadingCertificados = false;
            }
        },

        /* =========================================================
         *  Selección de certificados (vista filtros)
         * ========================================================= */

        get certificadosFiltrados() {
            // 1) Filtro por tipo (portuarios / generales)
            let lista = this.certificados;

            if (this.soloPortuarios) {
                // Solo portuarios
                lista = lista.filter((c) =>
                    CERTIFICADOS_PORTUARIOS.includes(String(c.CODIGO)),
                );
            } else {
                // Excluir portuarios de la lista general
                lista = lista.filter(
                    (c) => !CERTIFICADOS_PORTUARIOS.includes(String(c.CODIGO)),
                );
            }

            // 2) Filtro por término de búsqueda
            const term = (this.searchCertificado || "").trim().toLowerCase();
            if (!term) return lista;

            return lista.filter((c) =>
                (c.DESCRIPCION || "").toLowerCase().includes(term),
            );
        },

        get limiteAlcanzado() {
            return this.selectedCertificados.length >= this.MAX_CERTIFICADOS;
        },

        toggleSoloPortuarios() {
            this.soloPortuarios = !this.soloPortuarios;

            // Si activamos "solo portuarios", quitamos de la selección
            // cualquier certificado que ya no aplique.
            if (this.soloPortuarios) {
                this.selectedCertificados = this.selectedCertificados.filter(
                    (codigo) =>
                        CERTIFICADOS_PORTUARIOS.includes(String(codigo)),
                );
            } else {
                // Si desactivamos, quitamos los portuarios de la selección
                // (porque ya no están visibles en la lista general).
                this.selectedCertificados = this.selectedCertificados.filter(
                    (codigo) =>
                        !CERTIFICADOS_PORTUARIOS.includes(String(codigo)),
                );
            }
        },

        toggleCertificado(codigo) {
            const valor = String(codigo);
            const idx = this.selectedCertificados.indexOf(valor);

            if (idx !== -1) {
                this.selectedCertificados.splice(idx, 1);
                return;
            }

            if (this.limiteAlcanzado) {
                Swal.fire({
                    icon: "warning",
                    title: "Límite alcanzado",
                    html: `Solo puedes seleccionar hasta <strong>${this.MAX_CERTIFICADOS}</strong> certificados.<br>Desmarca uno para agregar otro.`,
                    confirmButtonText: "Entendido",
                    confirmButtonColor: "#14b8a6",
                });
                return;
            }

            this.selectedCertificados.push(valor);
        },

        descripcionCertificado(codigo) {
            const found = this.certificados.find(
                (c) => String(c.CODIGO) === String(codigo),
            );
            return found ? found.DESCRIPCION : codigo;
        },

        /* =========================================================
         *  Helpers de formato
         * ========================================================= */

        _mapearSiNoT(valor) {
            if (valor === "1") return "SI";
            if (valor === "2") return "NO";
            return "T";
        },

        _formatFecha(valor) {
            if (
                valor === null ||
                valor === undefined ||
                valor === "" ||
                valor === "NULL"
            ) {
                return "—";
            }
            try {
                const d = new Date(valor);
                if (isNaN(d.getTime())) return String(valor);
                const dd = String(d.getUTCDate()).padStart(2, "0");
                const mm = String(d.getUTCMonth() + 1).padStart(2, "0");
                const yyyy = d.getUTCFullYear();
                return `${dd}/${mm}/${yyyy}`;
            } catch {
                return String(valor);
            }
        },

        _labelVigencia(valor) {
            const v = String(valor || "")
                .trim()
                .toUpperCase();
            if (v === "SI") return "SI";
            if (v === "NO") return "NO";
            return v || "—";
        },

        _badgeVigencia(valor) {
            const v = String(valor || "")
                .trim()
                .toUpperCase();
            if (v === "SI") return "bg-green-100 text-green-700";
            if (v === "NO") return "bg-red-100 text-red-700";
            return "bg-default-100 text-default-500";
        },

        _labelEstado(valor) {
            const v = String(valor || "")
                .trim()
                .toUpperCase();
            if (v === "SI") return "SI";
            if (v === "NO") return "NO";
            return "—";
        },

        _badgeEstado(valor) {
            const v = String(valor || "")
                .trim()
                .toUpperCase();
            if (v === "SI") return "bg-green-100 text-green-700";
            if (v === "NO") return "bg-red-100 text-red-700";
            return "bg-default-100 text-default-500";
        },

        _labelEscaneo(valor) {
            const v = String(valor ?? "").trim();
            if (v === "1") return "Escaneado";
            if (v === "0") return "Sin escaneo";
            return v || "Sin dato";
        },

        _iconoEscaneo(valor) {
            const v = String(valor ?? "").trim();
            if (v === "1") return "ti-check";
            if (v === "0") return "ti-x";
            return "ti-minus";
        },

        _badgeEscaneo(valor) {
            const v = String(valor ?? "").trim();
            if (v === "1") return "bg-green-100 text-green-700";
            if (v === "0") return "bg-red-100 text-red-700";
            return "bg-default-100 text-default-500";
        },

        /* =========================================================
         *  Agrupación de resultados:  Certificado → Sucursal → Tipo → Personas
         * ========================================================= */

        get resultadosAgrupados() {
            if (
                !Array.isArray(this.reporteData) ||
                this.reporteData.length === 0
            ) {
                return [];
            }

            const groups = {};
            for (const row of this.reporteData) {
                const cert = String(row.REQUISITO || "Sin certificado").trim();
                const suc = String(row.SUCURSAL || "Sin sucursal").trim();
                const tipo = String(row.TIPO || "Sin tipo").trim();

                if (!groups[cert]) groups[cert] = {};
                if (!groups[cert][suc]) groups[cert][suc] = {};
                if (!groups[cert][suc][tipo]) groups[cert][suc][tipo] = [];
                groups[cert][suc][tipo].push(row);
            }

            return Object.keys(groups)
                .sort((a, b) => a.localeCompare(b))
                .map((cert) => {
                    const sucMap = groups[cert];
                    const sucursales = Object.keys(sucMap)
                        .sort((a, b) => a.localeCompare(b))
                        .map((suc) => {
                            const tipoMap = sucMap[suc];
                            const tipos = Object.keys(tipoMap)
                                .sort((a, b) => a.localeCompare(b))
                                .map((tipo) => ({
                                    tipo: tipo,
                                    personas: tipoMap[tipo].sort((a, b) =>
                                        String(a.PERSONAL || "").localeCompare(
                                            String(b.PERSONAL || ""),
                                        ),
                                    ),
                                    total: tipoMap[tipo].length,
                                }));
                            const total = tipos.reduce(
                                (acc, t) => acc + t.total,
                                0,
                            );
                            return { sucursal: suc, tipos, total };
                        });
                    const total = sucursales.reduce(
                        (acc, s) => acc + s.total,
                        0,
                    );
                    return { certificado: cert, sucursales, total };
                });
        },

        /* =========================================================
         *  Selección de certificado / sucursal / tipo activos (vista resultados)
         * ========================================================= */

        selectCert(certificado) {
            this.activeCertificado = certificado;
            this.activeSucursal = null;
            this.activeTipo = null;
            this.paginaActual = 1;
        },

        selectSucursal(certificado, sucursal) {
            this.activeCertificado = certificado;
            this.activeSucursal = sucursal;
            this.activeTipo = null;
            this.paginaActual = 1;
        },

        selectTipo(certificado, sucursal, tipo) {
            this.activeCertificado = certificado;
            this.activeSucursal = sucursal;
            this.activeTipo = tipo;
            this.paginaActual = 1;
        },

        /** Lista de personal según el certificado + sucursal + tipo activos. */
        get personalActual() {
            if (!this.activeCertificado) return [];
            const cert = this.resultadosAgrupados.find(
                (c) => c.certificado === this.activeCertificado,
            );
            if (!cert) return [];

            let personas;
            if (this.activeSucursal === null) {
                // Todas las sucursales: aplanar todo el personal
                personas = cert.sucursales.flatMap((s) =>
                    s.tipos.flatMap((t) => t.personas),
                );
            } else {
                const suc = cert.sucursales.find(
                    (s) => s.sucursal === this.activeSucursal,
                );
                if (!suc) return [];
                personas = suc.tipos.flatMap((t) => t.personas);
            }

            if (this.activeTipo !== null) {
                personas = personas.filter(
                    (p) =>
                        String(p.TIPO || "Sin tipo").trim() === this.activeTipo,
                );
            }

            return personas;
        },

        /** Página visible de personal. */
        get personalPaginado() {
            const start = (this.paginaActual - 1) * this.porPagina;
            return this.personalActual.slice(start, start + this.porPagina);
        },

        get totalPaginas() {
            return Math.max(
                1,
                Math.ceil(this.personalActual.length / this.porPagina),
            );
        },

        /** Páginas a mostrar, con "…" cuando hay páginas ocultas. */
        get paginasVisibles() {
            const total = this.totalPaginas;
            const actual = this.paginaActual;

            if (total <= 1) return total === 1 ? [1] : [];

            const visibles = new Set();

            if (total <= 7) {
                for (let i = 1; i <= total; i++) visibles.add(i);
            } else {
                visibles.add(1);
                visibles.add(total);
                visibles.add(actual);
                visibles.add(Math.max(1, actual - 1));
                visibles.add(Math.min(total, actual + 1));
            }

            const paginas = [];
            let ultima = 0;
            for (let i = 1; i <= total; i++) {
                if (!visibles.has(i)) continue;
                if (ultima && i - ultima > 1) paginas.push("…");
                paginas.push(i);
                ultima = i;
            }

            return paginas;
        },

        paginaAnterior() {
            if (this.paginaActual > 1) this.paginaActual--;
        },

        paginaSiguiente() {
            if (this.paginaActual < this.totalPaginas) this.paginaActual++;
        },

        irPagina(pagina) {
            if (typeof pagina !== "number") return;
            if (pagina < 1 || pagina > this.totalPaginas) return;
            this.paginaActual = pagina;
        },

        /* =========================================================
         *  Payload y generación del reporte
         * ========================================================= */

        _construirPayload() {
            return {
                sucursal: this.selectedSucursal || "T",
                tipo_personal: this.selectedTipoPersonal || "T",
                vigencia: this._mapearSiNoT(this.selectedVigencia),
                estado: this._mapearSiNoT(this.selectedEstado),
                certificados: this.selectedCertificados,
                vencimiento: this.selectedVencimiento || null,
            };
        },

        async generarReporte() {
            if (this.selectedCertificados.length === 0) {
                Swal.fire({
                    icon: "warning",
                    title: "Sin certificados",
                    text: "Selecciona al menos un certificado para generar el reporte.",
                    confirmButtonText: "Entendido",
                    confirmButtonColor: "#14b8a6",
                });
                return;
            }

            this.view = "results";
            this.loadingReporte = true;
            this.reporteData = [];
            this.activeCertificado = null;
            this.activeSucursal = null;
            this.activeTipo = null;
            this.paginaActual = 1;

            try {
                const payload = this._construirPayload();
                console.log("[Reporte] payload →", payload);

                const { data } = await axios.post(
                    `${VITE_URL_APP}/api/cursos-externos/reporte-certificados`,
                    payload,
                );

                if (!data?.success) {
                    throw new Error(
                        data?.message || "Error al generar el reporte.",
                    );
                }

                this.reporteData = Array.isArray(data.data) ? data.data : [];
                console.log("[Reporte] respuesta →", data);
            } catch (e) {
                console.error("Error al generar el reporte", e);
                this.view = "filters";
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text:
                        e?.response?.data?.message ||
                        e?.message ||
                        "Ocurrió un error al generar el reporte.",
                    confirmButtonText: "Entendido",
                });
            } finally {
                this.loadingReporte = false;
            }
        },

        /* =========================================================
         *  Exportación
         * ========================================================= */

        exportar(tipo) {
            if (tipo === "pdf") {
                this._exportarPDF();
                return;
            }

            if (tipo === "excel") {
                this._exportarExcel();
            }
        },

        /** Resumen de filtros para el encabezado del PDF. */
        _resumenFiltrosPDF() {
            const sucursal = this.sucursales.find(
                (s) => String(s.Codigo) === String(this.selectedSucursal),
            );
            const tipoPers = this.tiposPers.find(
                (t) => String(t.CODIGO) === String(this.selectedTipoPersonal),
            );

            const partes = [
                "Sucursal: " + (sucursal ? sucursal.Sucursal : "Todas"),
                "Tipo de personal: " +
                    (tipoPers ? tipoPers.DESCRIPCION : "Todos"),
                "Vigencia: " +
                    (this.selectedVigencia === "1"
                        ? "SI"
                        : this.selectedVigencia === "2"
                          ? "NO"
                          : "Todos"),
                "Estado: " +
                    (this.selectedEstado === "1"
                        ? "SI"
                        : this.selectedEstado === "2"
                          ? "NO"
                          : "Todos"),
                "Vencimiento: " + (this.selectedVencimiento || "Sin filtro"),
                "Certificados: " + this.selectedCertificados.length,
            ];

            return partes.join("   ·   ");
        },

        /**
         * Dibuja una banda de título y devuelve la nueva coordenada Y.
         * @param {jsPDF} doc
         * @param {string} texto
         * @param {number} y
         * @param {{fill: number[], color: number[], size: number, alto: number}} opts
         */
        _bandaPDF(doc, texto, y, opts) {
            const margen = 8;
            const pageWidth = doc.internal.pageSize.getWidth();
            const ancho = pageWidth - margen * 2;

            doc.setFillColor(opts.fill[0], opts.fill[1], opts.fill[2]);
            doc.rect(margen, y, ancho, opts.alto, "F");

            doc.setFont("helvetica", "bold");
            doc.setFontSize(opts.size);
            doc.setTextColor(opts.color[0], opts.color[1], opts.color[2]);

            // Trunca el texto a una sola línea si excede el ancho disponible
            const lineas = doc.splitTextToSize(String(texto), ancho - 4);
            const textoFmt =
                lineas.length > 1
                    ? lineas[0].replace(/\s+\S*$/, "") + "…"
                    : lineas[0] || "";

            doc.text(textoFmt, margen + 2, y + opts.alto - 2);
            doc.setTextColor(30, 41, 59);

            return y + opts.alto;
        },

        /** Exporta el reporte agrupado por certificado → sucursal → tipo de trabajador. */
        _exportarPDF() {
            const grupos = this.resultadosAgrupados;

            if (!grupos.length) {
                Swal.fire({
                    icon: "info",
                    title: "Sin datos",
                    text: "Genere primero el reporte para poder exportarlo.",
                    confirmButtonText: "Entendido",
                    confirmButtonColor: "#14b8a6",
                });
                return;
            }

            this.exportandoPdf = true;

            try {
                const doc = new jsPDF({
                    orientation: "landscape",
                    unit: "mm",
                    format: "a4",
                });

                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                const margen = 8;
                const anchoUtil = pageWidth - margen * 2;
                const limiteInferior = pageHeight - 16;

                /* ---------- Encabezado del documento ---------- */
                doc.setFont("helvetica", "bold");
                doc.setFontSize(13);
                doc.setTextColor(36, 39, 70);
                doc.text("REPORTE DE CERTIFICADOS", pageWidth / 2, 13, {
                    align: "center",
                });

                doc.setFont("helvetica", "normal");
                doc.setFontSize(7.5);
                doc.setTextColor(100, 116, 139);
                doc.text(this._resumenFiltrosPDF(), pageWidth / 2, 18, {
                    align: "center",
                    maxWidth: anchoUtil,
                });
                doc.setTextColor(30, 41, 59);

                let y = 24;

                const nuevaPagina = () => {
                    doc.addPage();
                    return margen + 2;
                };

                /* ---------- Secciones ---------- */
                for (const cert of grupos) {
                    if (y + 20 > limiteInferior) y = nuevaPagina();

                    y = this._bandaPDF(
                        doc,
                        "CERTIFICADO: " + cert.certificado.toUpperCase(),
                        y,
                        {
                            fill: [36, 39, 70],
                            color: [255, 255, 255],
                            size: 9.5,
                            alto: 7,
                        },
                    );
                    y += 2;

                    for (const suc of cert.sucursales) {
                        if (y + 18 > limiteInferior) y = nuevaPagina();

                        y = this._bandaPDF(
                            doc,
                            "SUCURSAL " + suc.sucursal.toUpperCase(),
                            y,
                            {
                                fill: [226, 232, 240],
                                color: [36, 39, 70],
                                size: 8.5,
                                alto: 6,
                            },
                        );
                        y += 1.5;

                        for (const tipo of suc.tipos) {
                            if (y + 16 > limiteInferior) y = nuevaPagina();

                            // Subtítulo del tipo de trabajador
                            doc.setFont("helvetica", "bold");
                            doc.setFontSize(8);
                            doc.setTextColor(51, 65, 85);
                            doc.text(
                                `${tipo.tipo.toUpperCase()}   ·   ${tipo.total} personal(es)`,
                                margen + 1,
                                y + 4,
                            );
                            doc.setTextColor(30, 41, 59);
                            y += 5.5;

                            const body = tipo.personas.map((p, i) => [
                                i + 1,
                                p.CODIGO || "—",
                                p.PERSONAL || "—",
                                p.DNI || "—",
                                p.CARGO || "—",
                                this._formatFecha(p.INGRESO),
                                this._labelVigencia(p.VIGENCIA),
                                p.RESULTADO || "—",
                                this._formatFecha(p.FEC_EMISION),
                                this._formatFecha(p.FEC_CADUCA),
                                this._labelEstado(p.ESTADO),
                                p.OBSERVACION || "—",
                                this._labelEscaneo(p.ESCANEO),
                            ]);

                            const columnStyles = {};
                            PDF_COLUMNAS.forEach((col, idx) => {
                                columnStyles[idx] = {
                                    cellWidth: col.width,
                                    halign: col.halign || "left",
                                };
                            });

                            autoTable(doc, {
                                startY: y,
                                margin: {
                                    left: margen,
                                    right: margen,
                                    bottom: 16,
                                },
                                head: [PDF_COLUMNAS.map((c) => c.label)],
                                body,
                                theme: "grid",
                                styles: {
                                    font: "helvetica",
                                    fontSize: 6.5,
                                    cellPadding: 1,
                                    valign: "middle",
                                    lineColor: [203, 213, 225],
                                    lineWidth: 0.1,
                                    overflow: "linebreak",
                                },
                                headStyles: {
                                    fillColor: [241, 245, 249],
                                    textColor: [30, 41, 59],
                                    fontStyle: "bold",
                                    fontSize: 6.5,
                                    halign: "center",
                                },
                                columnStyles,
                                rowPageBreak: "avoid",
                            });

                            y = doc.lastAutoTable.finalY + 4;
                        }

                        y += 1;
                    }

                    y += 2;
                }

                /* ---------- Pie de página en cada hoja ---------- */
                const totalPaginas = doc.getNumberOfPages();
                const fechaGeneracion = new Date().toLocaleString("es-PE", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                    hour: "2-digit",
                    minute: "2-digit",
                    hour12: true,
                });

                for (let i = 1; i <= totalPaginas; i++) {
                    doc.setPage(i);
                    doc.setFont("helvetica", "normal");
                    doc.setFontSize(7.5);
                    doc.setTextColor(120, 130, 150);

                    doc.text(
                        `Fecha de generación: ${fechaGeneracion}`,
                        margen,
                        pageHeight - 5,
                    );
                    doc.setDrawColor(226, 232, 240);
                    doc.setLineWidth(0.2);
                    doc.line(
                        margen,
                        pageHeight - 8.5,
                        pageWidth - margen,
                        pageHeight - 8.5,
                    );
                    doc.text(
                        `Página ${i} de ${totalPaginas}`,
                        pageWidth - margen,
                        pageHeight - 5,
                        { align: "right" },
                    );
                }

                /* ---------- Nombre del archivo ---------- */
                const hoy = new Date();
                const fechaArchivo = [
                    String(hoy.getDate()).padStart(2, "0"),
                    String(hoy.getMonth() + 1).padStart(2, "0"),
                    hoy.getFullYear(),
                ].join("-");

                doc.save(`REPORTE_CERTIFICADOS_${fechaArchivo}.pdf`);
            } catch (e) {
                console.error("Error al exportar el PDF", e);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se pudo generar el PDF.",
                    confirmButtonText: "Entendido",
                });
            } finally {
                this.exportandoPdf = false;
            }
        },

        /** Exporta el reporte en Excel agrupado por certificado → sucursal → tipo de trabajador. */
        async _exportarExcel() {
            const grupos = this.resultadosAgrupados;

            if (!grupos.length) {
                Swal.fire({
                    icon: "info",
                    title: "Sin datos",
                    text: "Genere primero el reporte para poder exportarlo.",
                    confirmButtonText: "Entendido",
                    confirmButtonColor: "#14b8a6",
                });
                return;
            }

            this.exportandoExcel = true;

            try {
                const workbook = new ExcelJS.Workbook();
                workbook.creator = "Sisolmar Web";
                workbook.created = new Date();

                const sheet = workbook.addWorksheet("Certificados");

                const totalColumnas = PDF_COLUMNAS.length;
                const ultimaColumna = _letraColumnaExcel(totalColumnas);

                sheet.columns = EXCEL_ANCHOS.map((width) => ({ width }));

                /* ---------- Encabezado del documento ---------- */
                const filaTitulo = sheet.getRow(1);
                filaTitulo.height = 26;
                filaTitulo.getCell(1).value = "REPORTE DE CERTIFICADOS";
                filaTitulo.getCell(1).font = {
                    bold: true,
                    size: 14,
                    color: { argb: "FF242746" },
                };
                filaTitulo.getCell(1).alignment = {
                    vertical: "middle",
                    horizontal: "center",
                };
                sheet.mergeCells(`A1:${ultimaColumna}1`);

                const filaSub = sheet.getRow(2);
                filaSub.height = 16;
                filaSub.getCell(1).value = this._resumenFiltrosPDF();
                filaSub.getCell(1).font = {
                    size: 9,
                    italic: true,
                    color: { argb: "FF64748B" },
                };
                filaSub.getCell(1).alignment = {
                    vertical: "middle",
                    horizontal: "center",
                };
                sheet.mergeCells(`A2:${ultimaColumna}2`);

                let fila = 4;

                /* ---------- Secciones ---------- */
                for (const cert of grupos) {
                    // Certificado
                    const filaCert = _estilizarFilaExcel(
                        sheet,
                        fila,
                        totalColumnas,
                        "FF242746",
                    );
                    filaCert.height = 22;
                    const celdaCert = filaCert.getCell(1);
                    celdaCert.value =
                        "CERTIFICADO: " + cert.certificado.toUpperCase();
                    celdaCert.font = {
                        bold: true,
                        size: 11,
                        color: { argb: "FFFFFFFF" },
                    };
                    celdaCert.alignment = {
                        vertical: "middle",
                        horizontal: "left",
                        indent: 1,
                    };
                    sheet.mergeCells(`A${fila}:${ultimaColumna}${fila}`);
                    fila++;

                    for (const suc of cert.sucursales) {
                        // Sucursal
                        const filaSuc = _estilizarFilaExcel(
                            sheet,
                            fila,
                            totalColumnas,
                            "FFE2E8F0",
                        );
                        filaSuc.height = 18;
                        const celdaSuc = filaSuc.getCell(1);
                        celdaSuc.value =
                            "SUCURSAL " + suc.sucursal.toUpperCase();
                        celdaSuc.font = {
                            bold: true,
                            size: 10,
                            color: { argb: "FF242746" },
                        };
                        celdaSuc.alignment = {
                            vertical: "middle",
                            horizontal: "left",
                            indent: 1,
                        };
                        sheet.mergeCells(`A${fila}:${ultimaColumna}${fila}`);
                        fila++;

                        for (const tipo of suc.tipos) {
                            // Tipo de trabajador
                            const filaTipo = sheet.getRow(fila);
                            filaTipo.height = 18;
                            const celdaTipo = filaTipo.getCell(1);
                            celdaTipo.value = `${tipo.tipo.toUpperCase()}   ·   ${tipo.total} personal(es)`;
                            celdaTipo.font = {
                                bold: true,
                                size: 10,
                                color: { argb: "FF0F766E" },
                            };
                            celdaTipo.alignment = {
                                vertical: "middle",
                                horizontal: "left",
                                indent: 1,
                            };
                            sheet.mergeCells(
                                `A${fila}:${ultimaColumna}${fila}`,
                            );
                            fila++;

                            // Cabecera de la tabla
                            const filaHead = sheet.getRow(fila);
                            filaHead.height = 20;
                            PDF_COLUMNAS.forEach((col, idx) => {
                                const cell = filaHead.getCell(idx + 1);
                                cell.value = col.label;
                                cell.font = {
                                    bold: true,
                                    color: { argb: "FFFFFFFF" },
                                };
                                cell.fill = {
                                    type: "pattern",
                                    pattern: "solid",
                                    fgColor: { argb: "FF1F4E79" },
                                };
                                cell.alignment = {
                                    vertical: "middle",
                                    horizontal: "center",
                                    wrapText: true,
                                };
                                cell.border = EXCEL_BORDE;
                            });
                            fila++;

                            // Filas de personal
                            tipo.personas.forEach((p, idx) => {
                                const filaDato = sheet.getRow(fila);
                                const valores = [
                                    idx + 1,
                                    p.CODIGO || "—",
                                    p.PERSONAL || "—",
                                    p.DNI || "—",
                                    p.CARGO || "—",
                                    this._formatFecha(p.INGRESO),
                                    this._labelVigencia(p.VIGENCIA),
                                    p.RESULTADO || "—",
                                    this._formatFecha(p.FEC_EMISION),
                                    this._formatFecha(p.FEC_CADUCA),
                                    this._labelEstado(p.ESTADO),
                                    p.OBSERVACION || "—",
                                    this._labelEscaneo(p.ESCANEO),
                                ];

                                valores.forEach((valor, colIdx) => {
                                    const esCentrado =
                                        colIdx === 0 ||
                                        PDF_COLUMNAS[colIdx].halign ===
                                            "center";

                                    const cell = filaDato.getCell(colIdx + 1);
                                    cell.value = valor;
                                    cell.font = { size: 9 };
                                    cell.alignment = {
                                        vertical: "middle",
                                        horizontal: esCentrado
                                            ? "center"
                                            : "left",
                                        wrapText: colIdx === 11,
                                    };
                                    cell.border = EXCEL_BORDE;
                                });

                                fila++;
                            });

                            fila++;
                        }

                        fila++;
                    }

                    fila++;
                }

                /* ---------- Nombre del archivo ---------- */
                const buffer = await workbook.xlsx.writeBuffer();
                const blob = new Blob([buffer], {
                    type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                });

                const hoy = new Date();
                const fechaArchivo = [
                    String(hoy.getDate()).padStart(2, "0"),
                    String(hoy.getMonth() + 1).padStart(2, "0"),
                    hoy.getFullYear(),
                ].join("-");

                saveAs(blob, `REPORTE_CERTIFICADOS_${fechaArchivo}.xlsx`);
            } catch (e) {
                console.error("Error al exportar el Excel", e);
                Swal.fire({
                    icon: "error",
                    title: "Error",
                    text: "No se pudo generar el Excel.",
                    confirmButtonText: "Entendido",
                });
            } finally {
                this.exportandoExcel = false;
            }
        },

        /* =========================================================
         *  Navegación
         * ========================================================= */

        volver() {
            this.view = "filters";
        },

        cerrar() {
            this.open = false;
            this.view = "filters";
            this.selectedSucursal = "";
            this.selectedTipoPersonal = "";
            this.selectedVigencia = "";
            this.selectedEstado = "";
            this.selectedVencimiento = "";
            this.selectedCertificados = [];
            this.searchCertificado = "";
            this.soloPortuarios = false;
            this.reporteData = [];
            this.activeCertificado = null;
            this.activeSucursal = null;
            this.activeTipo = null;
            this.paginaActual = 1;
        },
    }));
});
