import axios from "axios";

function _cargarImagen(url) {
    return new Promise((resolve) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = url;
    });
}

export default document.addEventListener("alpine:init", () => {
    Alpine.data("exportExamenes", () => ({
        cursos: [],
        cursosAgrupados: [],
        searchCurso: "",
        selectedYear: "",
        anios: [],
        loadingCursos: false,
        errorCursos: false,
        totalCursos: 0,

        expandedCourseId: null,
        seleccionado: "",

        page: 1,
        perPage: 7,

        personal: [],
        loadingPersonal: false,
        errorPersonal: false,
        busquedaPersonal: "",
        filtroSucursal: "",
        filtroTipoTrabajador: "",
        filtroVigencia: "1",
        filtroCargo: "",
        personalPage: 1,
        personalPerPage: 6,

        seleccionadosPersonal: [],
        maxPersonal: 5,
        exportandoPDF: false,

        async init() {
            await Promise.all([this.cargarCursos(), this.cargarPersonal()]);
        },

        async cargarCursos() {
            this.loadingCursos = true;
            this.errorCursos = false;
            try {
                const { data } = await axios.get(
                    `${VITE_URL_APP}/api/obtener-cursos-av`,
                );
                if (data.success) {
                    this.cursos = Array.isArray(data.data?.cursos)
                        ? data.data.cursos
                        : [];
                    this.totalCursos = data.data?.total || this.cursos.length;
                    this.agruparPorCurso();
                } else {
                    this.cursos = [];
                    this.errorCursos = true;
                }
            } catch (e) {
                console.error("Error cargando cursos:", e);
                this.cursos = [];
                this.errorCursos = true;
            } finally {
                this.loadingCursos = false;
            }
        },

        agruparPorCurso() {
            const mapa = new Map();
            const aniosSet = new Set();

            this.cursos.forEach((item) => {
                const anio = item.course_created
                    ? String(item.course_created).slice(0, 4)
                    : "";
                if (anio) aniosSet.add(anio);

                if (!mapa.has(item.course_id)) {
                    mapa.set(item.course_id, {
                        course_id: item.course_id,
                        course_name: item.course_name,
                        course_created: item.course_created,
                        anio,
                        quizzes: [],
                    });
                }
                mapa.get(item.course_id).quizzes.push({
                    quiz_id: item.quiz_id,
                    quiz_name: item.quiz_name,
                });
            });

            this.cursosAgrupados = [...mapa.values()];
            this.anios = [...aniosSet].sort((a, b) => b.localeCompare(a));
            this.totalCursos = this.cursosAgrupados.length;
            this.page = 1;
        },

        get cursosFiltrados() {
            let cursos = this.cursosAgrupados;

            if (this.selectedYear) {
                cursos = cursos.filter(
                    (curso) => curso.anio === this.selectedYear,
                );
            }

            const termino = (this.searchCurso || "").trim().toLowerCase();
            if (!termino) return cursos;

            return cursos
                .map((curso) => ({
                    ...curso,
                    quizzes: curso.quizzes.filter(
                        (quiz) =>
                            quiz.quiz_name.toLowerCase().includes(termino) ||
                            curso.course_name.toLowerCase().includes(termino),
                    ),
                }))
                .filter((curso) => curso.quizzes.length > 0);
        },

        get cursosPaginados() {
            const inicio = (this.page - 1) * this.perPage;
            return this.cursosFiltrados.slice(inicio, inicio + this.perPage);
        },

        get totalPaginas() {
            return Math.max(
                1,
                Math.ceil(this.cursosFiltrados.length / this.perPage),
            );
        },

        get paginas() {
            const total = this.totalPaginas;
            const actual = this.page;
            const inicio = Math.max(1, actual - 2);
            const fin = Math.min(total, inicio + 4);
            const rango = [];
            for (let i = inicio; i <= fin; i++) rango.push(i);
            return rango;
        },

        get textoMostrando() {
            const total = this.cursosFiltrados.length;
            if (total === 0) return "0 resultado(s)";
            const inicio = (this.page - 1) * this.perPage + 1;
            const fin = Math.min(this.page * this.perPage, total);
            return `${inicio} - ${fin} de ${total}`;
        },

        irAPagina(pagina) {
            if (pagina < 1 || pagina > this.totalPaginas) return;
            this.page = pagina;
            this.expandedCourseId = null;
        },

        toggleExpandirCurso(courseId) {
            this.expandedCourseId =
                this.expandedCourseId === courseId ? null : courseId;
        },

        keySeleccion(curso, quiz) {
            return `${curso.course_id}:${quiz.quiz_id}`;
        },

        esSeleccionado(curso, quiz) {
            return this.seleccionado === this.keySeleccion(curso, quiz);
        },

        seleccionarExamen(curso, quiz) {
            const key = this.keySeleccion(curso, quiz);
            this.seleccionado = this.esSeleccionado(curso, quiz) ? "" : key;
        },

        get totalSeleccionados() {
            return this.seleccionado ? 1 : 0;
        },

        get hayExamenSeleccionado() {
            return this.seleccionado !== "";
        },

        cursoBloqueado(curso) {
            return (
                this.hayExamenSeleccionado &&
                !curso.quizzes.some((q) => this.esSeleccionado(curso, q))
            );
        },

        async cargarPersonal() {
            this.loadingPersonal = true;
            this.errorPersonal = false;
            try {
                const { data } = await axios.get(
                    `${VITE_URL_APP}/api/obtener-personal`,
                    { params: { vigente: "2" } },
                );
                if (data.success) {
                    this.personal = Array.isArray(data.personal)
                        ? data.personal
                        : [];
                } else {
                    this.personal = [];
                    this.errorPersonal = true;
                }
            } catch (e) {
                console.error("Error cargando personal:", e);
                this.personal = [];
                this.errorPersonal = true;
            } finally {
                this.loadingPersonal = false;
            }
        },

        get sucursales() {
            return this.valoresUnicos(this.personal, "sucursal");
        },

        get tiposTrabajador() {
            return this.valoresUnicos(this.personal, "tipo_trabajador");
        },

        get cargos() {
            return this.valoresUnicos(this.personal, "cargo");
        },

        valoresUnicos(lista, campo) {
            const valores = new Set(
                lista
                    .map((p) => (p[campo] || "").toString().trim())
                    .filter((v) => v !== ""),
            );
            return [...valores].sort((a, b) => a.localeCompare(b));
        },

        get personalFiltrado() {
            let lista = this.personal;

            const termino = (this.busquedaPersonal || "").trim().toLowerCase();
            if (termino) {
                lista = lista.filter(
                    (p) =>
                        (p.nombre_completo || "")
                            .toLowerCase()
                            .includes(termino) ||
                        (p.dni || "").toLowerCase().includes(termino),
                );
            }

            if (this.filtroSucursal) {
                lista = lista.filter((p) => p.sucursal === this.filtroSucursal);
            }

            if (this.filtroTipoTrabajador) {
                lista = lista.filter(
                    (p) => p.tipo_trabajador === this.filtroTipoTrabajador,
                );
            }

            if (this.filtroCargo) {
                lista = lista.filter((p) => p.cargo === this.filtroCargo);
            }

            if (this.filtroVigencia === "1") {
                lista = lista.filter((p) => p.vigente === true);
            } else if (this.filtroVigencia === "0") {
                lista = lista.filter((p) => p.vigente === false);
            }

            const idxSeleccionados = new Map();
            lista.forEach((p, i) =>
                idxSeleccionados.set(this.clavePersonal(p), i),
            );

            return lista.slice().sort((a, b) => {
                const aSel = this.estaSeleccionado(a) ? 0 : 1;
                const bSel = this.estaSeleccionado(b) ? 0 : 1;
                if (aSel !== bSel) return aSel - bSel;
                return (
                    (idxSeleccionados.get(this.clavePersonal(a)) ?? 0) -
                    (idxSeleccionados.get(this.clavePersonal(b)) ?? 0)
                );
            });
        },

        get personalPaginado() {
            const inicio = (this.personalPage - 1) * this.personalPerPage;
            return this.personalFiltrado.slice(
                inicio,
                inicio + this.personalPerPage,
            );
        },

        get totalPaginasPersonal() {
            return Math.max(
                1,
                Math.ceil(this.personalFiltrado.length / this.personalPerPage),
            );
        },

        get paginasPersonal() {
            const total = this.totalPaginasPersonal;
            const actual = this.personalPage;
            const inicio = Math.max(1, actual - 2);
            const fin = Math.min(total, inicio + 4);
            const rango = [];
            for (let i = inicio; i <= fin; i++) rango.push(i);
            return rango;
        },

        get textoMostrandoPersonal() {
            const total = this.personalFiltrado.length;
            if (total === 0) return "0 resultado(s)";
            const inicio = (this.personalPage - 1) * this.personalPerPage + 1;
            const fin = Math.min(
                this.personalPage * this.personalPerPage,
                total,
            );
            return `${inicio} - ${fin} de ${total}`;
        },

        irAPaginaPersonal(pagina) {
            if (pagina < 1 || pagina > this.totalPaginasPersonal) return;
            this.personalPage = pagina;
        },

        clavePersonal(p) {
            return `${p.codigo ?? ""}:${p.dni ?? ""}`;
        },

        estaSeleccionado(p) {
            return this.seleccionadosPersonal.includes(this.clavePersonal(p));
        },

        toggleSeleccionarPersonal(p) {
            const clave = this.clavePersonal(p);
            const idx = this.seleccionadosPersonal.indexOf(clave);
            if (idx !== -1) {
                this.seleccionadosPersonal.splice(idx, 1);
                return;
            }
            if (this.seleccionadosPersonal.length >= this.maxPersonal) return;
            this.seleccionadosPersonal.push(clave);
        },

        personalCompleto() {
            return this.seleccionadosPersonal.length;
        },

        get examenSeleccionadoInfo() {
            if (!this.seleccionado) return null;
            const [courseId, quizId] = this.seleccionado.split(":");
            const curso = this.cursosAgrupados.find(
                (c) => String(c.course_id) === String(courseId),
            );
            if (!curso) return null;
            const quiz = curso.quizzes.find(
                (q) => String(q.quiz_id) === String(quizId),
            );
            return {
                course_name: curso.course_name,
                quiz_name: quiz ? quiz.quiz_name : "",
            };
        },

        get personalSeleccionadoDetalle() {
            return this.seleccionadosPersonal
                .map((clave) =>
                    this.personal.find((p) => this.clavePersonal(p) === clave),
                )
                .filter(Boolean);
        },

        get puedeExportar() {
            return (
                this.hayExamenSeleccionado &&
                this.seleccionadosPersonal.length > 0
            );
        },

        async exportar() {
            if (!this.puedeExportar || this.exportandoPDF) return;

            const personas = this.personalSeleccionadoDetalle;
            const quizId = this.seleccionado.split(":")[1];
            if (!personas.length || !quizId) return;

            this.exportandoPDF = true;
            try {
                const { data } = await axios.post(
                    `${VITE_URL_APP}/api/obtener-datos-reporte-av`,
                    {
                        dnis: personas.map((p) => p.dni),
                        quizId,
                    },
                );

                if (!data.success) {
                    Swal.fire(
                        "Sin resultados",
                        data.message ||
                            "El personal no tiene intentos registrados en este examen.",
                        "warning",
                    );
                    return;
                }

                const resultados = Array.isArray(data.data?.resultados)
                    ? data.data.resultados
                    : [];

                const fallidos = [];
                const exitos = [];
                for (const resultado of resultados) {
                    if (resultado.success && resultado.data) {
                        exitos.push(resultado.data);
                    } else {
                        fallidos.push(
                            resultado.dni || resultado.message || "desconocido",
                        );
                    }
                }

                if (exitos.length > 0) {
                    await this.generarPdfReporte(exitos);
                }

                if (fallidos.length > 0) {
                    Swal.fire(
                        "Reporte parcial",
                        `${fallidos.length} personal(es) no tienen intentos registrados en este examen.`,
                        "warning",
                    );
                }
            } catch (e) {
                console.error(e);
                const msg = e.response?.data?.message;
                if (msg) {
                    Swal.fire("Sin resultados", msg, "warning");
                } else {
                    Swal.fire(
                        "Error",
                        "No se pudo obtener el reporte del examen.",
                        "error",
                    );
                }
            } finally {
                this.exportandoPDF = false;
            }
        },

        async generarPdfReporte(detalles) {
            const { jsPDF } = window.jspdf;

            if (!jsPDF) {
                Swal.fire(
                    "Error",
                    "jsPDF no está disponible. Revise su conexión e intente nuevamente.",
                    "error",
                );
                return;
            }

            const doc = new jsPDF({
                orientation: "portrait",
                unit: "mm",
                format: "a4",
            });
            const pageWidth = doc.internal.pageSize.getWidth();
            const margen = 14;

            const logoSol = await _cargarImagen(
                "/sisolmar/images/logo_sol.png",
            );
            const logoAV = await _cargarImagen("/sisolmar/images/AV.png");

            detalles.forEach((detalle, index) => {
                if (index > 0) doc.addPage();

                let y = 16;

                const logoAVWidth = 22;
                const logoSolWidth = 36;
                const gapTitulo = 6;

                if (logoAV) {
                    const logoHeight =
                        logoAVWidth * (logoAV.height / logoAV.width);
                    const offsetAV = 5;
                    doc.addImage(
                        logoAV,
                        "PNG",
                        margen,
                        y - offsetAV,
                        logoAVWidth,
                        logoHeight,
                    );
                }

                if (logoSol) {
                    const logoHeight =
                        logoSolWidth * (logoSol.height / logoSol.width);
                    doc.addImage(
                        logoSol,
                        "PNG",
                        pageWidth - margen - logoSolWidth,
                        y,
                        logoSolWidth,
                        logoHeight,
                    );
                }

                const anchoDisponible =
                    pageWidth -
                    margen * 2 -
                    logoAVWidth -
                    logoSolWidth -
                    gapTitulo * 2;

                doc.setFont("helvetica", "bold");
                doc.setFontSize(14);
                doc.setTextColor(20, 40, 80);
                doc.text(
                    (detalle.course_name || "CURSO").toUpperCase(),
                    pageWidth / 2,
                    y + 10,
                    { align: "center", maxWidth: anchoDisponible },
                );

                y += 19;

                // --- TABLA DE DATOS DEL ALUMNO ---
                doc.autoTable({
                    startY: y,
                    theme: "grid",
                    styles: {
                        fontSize: 8,
                        cellPadding: 1.3,
                        valign: "middle",
                        lineColor: [20, 40, 80],
                        lineWidth: 0.2,
                        textColor: [20, 20, 20],
                    },
                    body: [
                        [
                            {
                                content: "Alumno",
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [225, 232, 245],
                                    halign: "center",
                                },
                            },
                            {
                                content: (
                                    detalle.full_name || "—"
                                ).toUpperCase(),
                                colSpan: 3,
                                styles: { fontStyle: "bold", halign: "center" },
                            },
                            {
                                content: "DNI",
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [225, 232, 245],
                                    halign: "center",
                                },
                            },
                            {
                                content: detalle.dni || "—",
                                styles: { halign: "center" },
                            },
                        ],
                        [
                            {
                                content: "Fecha",
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [225, 232, 245],
                                    halign: "center",
                                },
                            },
                            {
                                content: detalle.attempt_date || "—",
                                styles: { halign: "center" },
                            },
                            {
                                content: "Hora de Inicio",
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [225, 232, 245],
                                    halign: "center",
                                },
                            },
                            {
                                content: detalle.start_time || "—",
                                styles: { halign: "center" },
                            },
                            {
                                content: "Hora de Término",
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [225, 232, 245],
                                    halign: "center",
                                },
                            },
                            {
                                content: detalle.end_time || "—",
                                styles: { halign: "center" },
                            },
                        ],
                        [
                            {
                                content: "Duración",
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [225, 232, 245],
                                    halign: "center",
                                },
                            },
                            {
                                content: detalle.duration || "—",
                                colSpan: 5,
                                styles: { halign: "center" },
                            },
                        ],
                        [
                            {
                                content: "NOTA",
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [225, 232, 245],
                                    halign: "center",
                                },
                            },
                            {
                                content:
                                    detalle.obtained_grade != null
                                        ? `${detalle.obtained_grade} de 20`
                                        : "—",
                                styles: { fontStyle: "bold", halign: "center" },
                            },
                            {
                                content: "N° Intento",
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [225, 232, 245],
                                    halign: "center",
                                },
                            },
                            {
                                content: String(detalle.attempt_number ?? "—"),
                                styles: { halign: "center" },
                            },
                            {
                                content: "% Aprobatorio",
                                rowSpan: 2,
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [225, 232, 245],
                                    halign: "center",
                                    valign: "middle",
                                },
                            },
                            {
                                content:
                                    detalle.passing_percentage != null
                                        ? `${detalle.passing_percentage}%`
                                        : "—",
                                rowSpan: 2,
                                styles: { halign: "center", valign: "middle" },
                            },
                        ],
                        [
                            {
                                content: "Correctas",
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [225, 232, 245],
                                    halign: "center",
                                },
                            },
                            {
                                content: String(detalle.correct_answers ?? "—"),
                                styles: { halign: "center" },
                            },
                            {
                                content: "Incorrectas",
                                styles: {
                                    fontStyle: "bold",
                                    fillColor: [255, 225, 225],
                                    halign: "center",
                                },
                            },
                            {
                                content: String(
                                    detalle.incorrect_answers ?? "—",
                                ),
                                styles: {
                                    halign: "center",
                                    textColor: [180, 0, 0],
                                    fontStyle: "bold",
                                },
                            },
                            {
                                content: "",
                                colSpan: 2,
                                styles: { fillColor: [255, 255, 255] },
                            },
                        ],
                    ],
                    columnStyles: {
                        0: { cellWidth: 26 },
                        1: { cellWidth: 32 },
                        2: { cellWidth: 30 },
                        3: { cellWidth: 32 },
                        4: { cellWidth: 30 },
                        5: { cellWidth: "auto" },
                    },
                    margin: { left: margen, right: margen },
                });

                y = doc.lastAutoTable.finalY + 2;

                // --- PREGUNTAS: una tabla independiente por pregunta ---
                const preguntas = Array.isArray(detalle.questions_answers)
                    ? detalle.questions_answers
                    : [];

                if (preguntas.length > 0) {
                    const letras = ["a", "b", "c", "d", "e", "f", "g", "h"];

                    preguntas.forEach((q, i) => {
                        const opciones =
                            Array.isArray(q.options) && q.options.length
                                ? q.options
                                : ["—"];

                        const bodyOpciones = opciones.map((opt, j) => {
                            const letra = letras[j] || "-";
                            const esSeleccionada = q.response === opt;
                            return [
                                {
                                    content: `${letra}) ${opt}`,
                                    styles: {
                                        fontStyle: esSeleccionada
                                            ? "bold"
                                            : "normal",
                                        textColor: [40, 40, 40],
                                        fillColor: esSeleccionada
                                            ? [235, 235, 235]
                                            : [255, 255, 255],
                                        cellPadding: {
                                            top: 0.8,
                                            bottom: 0.8,
                                            left: 7,
                                            right: 2,
                                        },
                                    },
                                },
                            ];
                        });

                        doc.autoTable({
                            startY: y,
                            theme: "grid",
                            head: [
                                [
                                    {
                                        content: `${i + 1}. ${q.question || "—"}`,
                                        styles: {
                                            fillColor: [225, 235, 250],
                                            textColor: [20, 40, 80],
                                            fontStyle: "bold",
                                            fontSize: 7.5,
                                            halign: "left",
                                            valign: "middle",
                                            cellPadding: {
                                                top: 1.5,
                                                bottom: 1.5,
                                                left: 4,
                                                right: 4,
                                            },
                                            lineColor: [180, 195, 215],
                                            lineWidth: 0.2,
                                        },
                                    },
                                ],
                            ],
                            body: bodyOpciones,
                            styles: {
                                fontSize: 7,
                                cellPadding: 1,
                                lineColor: [200, 210, 225],
                                lineWidth: 0.15,
                                valign: "middle",
                                overflow: "linebreak",
                            },
                            margin: { left: margen, right: margen },
                            rowPageBreak: "avoid",
                        });

                        y = doc.lastAutoTable.finalY + 1.5;
                    });
                }
            });

            // --- PIE DE PÁGINA (con fecha AM/PM) ---
            const totalPaginas = doc.getNumberOfPages();
            for (let i = 1; i <= totalPaginas; i++) {
                doc.setPage(i);
                doc.setFont("helvetica", "normal");
                doc.setFontSize(8);
                doc.setTextColor(140, 140, 140);

                const fechaGeneracion = new Date().toLocaleString("es-PE", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                    hour: "2-digit",
                    minute: "2-digit",
                    second: "2-digit",
                    hour12: true,
                });

                doc.text(
                    `Generado por Sisolmar Web · ${fechaGeneracion}`,
                    margen,
                    290,
                );
                doc.text(
                    `Página ${i} de ${totalPaginas}`,
                    pageWidth - margen,
                    290,
                    { align: "right" },
                );
            }

            // --- NOMBRE DINÁMICO DEL ARCHIVO ---
            const hoy = new Date();
            const dd = String(hoy.getDate()).padStart(2, "0");
            const mm = String(hoy.getMonth() + 1).padStart(2, "0");
            const yyyy = hoy.getFullYear();
            const fechaStr = `${dd}-${mm}-${yyyy}`;

            const sanitizar = (str) =>
                (str || "")
                    .normalize("NFD")
                    .replace(/[\u0300-\u036f]/g, "")
                    .replace(/[\\/:*?"<>|]/g, "")
                    .trim();

            const primerDetalle = detalles[0] || {};
            const courseName = sanitizar(primerDetalle.course_name || "CURSO");

            let nombreArchivo;
            if (detalles.length === 1) {
                const fullName = sanitizar(
                    primerDetalle.full_name || "PERSONAL",
                )
                    .toUpperCase()
                    .replace(/\s+/g, "_");
                nombreArchivo = `${fullName} - ${courseName} - ${fechaStr}.pdf`;
            } else {
                nombreArchivo = `REPORTE_EXAMENES - ${courseName} - ${fechaStr}.pdf`;
            }

            doc.save(nombreArchivo);
        },
    }));
});
