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
            return this.cursosFiltrados.slice(
                inicio,
                inicio + this.perPage,
            );
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
            lista.forEach((p, i) => idxSeleccionados.set(this.clavePersonal(p), i));

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

            // Por ahora se genera el reporte para la primera persona seleccionada
            const persona = this.personalSeleccionadoDetalle[0];
            const quizId = this.seleccionado.split(":")[1];
            if (!persona || !quizId) return;

            this.exportandoPDF = true;
            try {
                const { data } = await axios.get(
                    `${VITE_URL_APP}/api/obtener-datos-reporte-av`,
                    { params: { dni: persona.dni, quizId } },
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

                await this.generarPdfReporte(data.data);
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

        async generarPdfReporte(detalle) {
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
            let y = 16;

            const logoSol = await _cargarImagen("/images/logo_sol.png");

            if (logoSol) {
                const logoWidth = 55;
                const logoHeight = logoWidth * (logoSol.height / logoSol.width);
                doc.addImage(logoSol, "PNG", margen, y, logoWidth, logoHeight);
            }

            doc.setFont("helvetica", "bold");
            doc.setFontSize(11);
            doc.setTextColor(20, 40, 80);
            doc.text(
                "SOLMAR SEGURIDAD INTEGRAL S.R.L.",
                pageWidth - margen,
                y + 4,
                { align: "right" },
            );
            doc.setFont("helvetica", "normal");
            doc.setFontSize(8);
            doc.setTextColor(90, 90, 90);
            doc.text("RUC: 20445414833", pageWidth - margen, y + 9, {
                align: "right",
            });
            doc.text(
                "Chimbote: Calle Los Laureles Nº206 Urb. La Caleta",
                pageWidth - margen,
                y + 13,
                { align: "right" },
            );

            y = logoSol ? y + 24 : y + 20;

            doc.setDrawColor(180, 180, 180);
            doc.setLineWidth(0.3);
            doc.line(margen, y, pageWidth - margen, y);
            y += 10;

            doc.setFont("helvetica", "bold");
            doc.setFontSize(14);
            doc.setTextColor(20, 40, 80);
            doc.text("REPORTE DE EVALUACIÓN", pageWidth / 2, y, {
                align: "center",
            });
            y += 6;
            doc.setFont("helvetica", "normal");
            doc.setFontSize(10);
            doc.setTextColor(60, 60, 60);
            doc.text((detalle.course_name || "CURSO").toUpperCase(), pageWidth / 2, y, {
                align: "center",
            });
            y += 8;

            doc.setFillColor(242, 245, 250);
            doc.roundedRect(margen, y, pageWidth - margen * 2, 34, 2, 2, "F");

            const colMitad = (pageWidth - margen * 2) / 2;

            const pintarDato = (x, yBas, label, value) => {
                doc.setFont("helvetica", "bold");
                doc.setFontSize(7.5);
                doc.setTextColor(120, 130, 145);
                doc.text(label.toUpperCase(), x, yBas);
                doc.setFont("helvetica", "normal");
                doc.setFontSize(9.5);
                doc.setTextColor(30, 30, 30);
                doc.text(String(value || "—"), x, yBas + 5);
            };

            pintarDato(
                margen + 4,
                y + 8,
                "Trabajador",
                detalle.full_name,
            );
            pintarDato(
                margen + 4,
                y + 20,
                "Fecha de examen",
                detalle.attempt_date,
            );
            pintarDato(
                margen + colMitad + 4,
                y + 8,
                "Intento",
                detalle.attempt_number != null
                    ? `${detalle.attempt_number}° (ID ${detalle.attempt_id})`
                    : "—",
            );
            pintarDato(
                margen + colMitad + 4,
                y + 20,
                "Duración",
                detalle.duration,
            );

            doc.setFont("helvetica", "normal");
            doc.setFontSize(8);
            doc.setTextColor(120, 130, 145);
            doc.text(
                `Horario: ${detalle.start_time || "—"} a ${detalle.end_time || "—"}`,
                margen + 4,
                y + 31,
            );

            const nota = parseFloat(detalle.obtained_grade);
            const minimo = parseFloat(detalle.passing_percentage);
            const aprobado =
                !isNaN(nota) && !isNaN(minimo) ? nota >= minimo : false;

            doc.autoTable({
                startY: y + 42,
                head: [
                    [
                        "Correctas",
                        "Incorrectas",
                        "Nota obtenida",
                        "Nota mínima",
                        "Resultado",
                    ],
                ],
                body: [
                    [
                        String(detalle.correct_answers ?? "—"),
                        String(detalle.incorrect_answers ?? "—"),
                        detalle.obtained_grade || "—",
                        detalle.passing_percentage || "—",
                        aprobado ? "APROBADO" : "NO APROBADO",
                    ],
                ],
                theme: "grid",
                headStyles: { fillColor: [20, 40, 80], fontStyle: "bold", fontSize: 9 },
                styles: { fontSize: 9, halign: "center", valign: "middle" },
                margin: { left: margen, right: margen },
            });

            const preguntas = Array.isArray(detalle.questions_answers)
                ? detalle.questions_answers
                : [];

            const startQuestions = doc.lastAutoTable.finalY + 10;

            doc.setFont("helvetica", "bold");
            doc.setFontSize(11);
            doc.setTextColor(20, 40, 80);
            doc.text("PREGUNTAS Y RESPUESTAS", margen, startQuestions);

            doc.autoTable({
                startY: startQuestions + 4,
                head: [["N°", "Pregunta", "Respuesta"]],
                body: preguntas.map((q, i) => [
                    i + 1,
                    q.question || "—",
                    q.response || "—",
                ]),
                theme: "striped",
                headStyles: { fillColor: [20, 40, 80], fontStyle: "bold", fontSize: 9 },
                bodyStyles: { fontSize: 9 },
                columnStyles: {
                    0: { cellWidth: 10, halign: "center" },
                    1: { cellWidth: 112 },
                    2: { cellWidth: 60 },
                },
                margin: { left: margen, right: margen },
            });

            const totalPaginas = doc.getNumberOfPages();
            for (let i = 1; i <= totalPaginas; i++) {
                doc.setPage(i);
                doc.setFont("helvetica", "normal");
                doc.setFontSize(8);
                doc.setTextColor(140, 140, 140);
                doc.text(
                    `Generado por Sisolmar Web · ${new Date().toLocaleString("es-PE")}`,
                    margen,
                    290,
                );
                doc.text(`Página ${i} de ${totalPaginas}`, pageWidth - margen, 290, {
                    align: "right",
                });
            }

            const nombreArchivo = `reporte_examen_${(detalle.full_name || "personal")
                .trim()
                .replace(/\s+/g, "_")}.pdf`;
            doc.save(nombreArchivo);
        },
    }));
});