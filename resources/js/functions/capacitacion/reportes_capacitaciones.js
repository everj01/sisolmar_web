import axios from "axios";

export default document.addEventListener("alpine:init", () => {
    Alpine.data("reportesApp", () => ({
        abrirModalReporte() {
            window.dispatchEvent(new CustomEvent("abrir-reporte"));
        },
    }));

    Alpine.data("modalReporte", () => ({
        open: false,

        selectedSistema: "",
        selectedArea: "",
        selectedCurso: "",
        selectedPeriodo: "",
        selectedEstado: "PENDIENTE",

        sistemas: [],
        areas: [],
        cursos: [],
        todosLosCursos: [],
        periodos: [],

        async init() {
            window.addEventListener("abrir-reporte", () => {
                this.abrir();
            });

            await this.cargarSistemas();
        },

        async cargarSistemas() {
            try {
                const response = await axios.get("/api/get-capacitacion-areas");

                this.sistemas = response.data;
            } catch (error) {
                console.error(error);
            }
        },

        async cargarAreas(sistemaId) {
            if (!sistemaId) {
                this.areas = [];
                this.selectedArea = "";
                return;
            }

            try {
                const response = await axios.get(
                    `/api/get-areas-por-sistema/${sistemaId}`,
                );

                if (response.data.success) {
                    this.areas = response.data.areas;

                    console.log(this.areas);
                } else {
                    this.areas = [];
                }
            } catch (error) {
                console.error(error);
                this.areas = [];
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

                    console.log(this.periodos);
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

            this.selectedSistema = "";
            this.selectedArea = "";
            this.selectedCurso = "";
            this.selectedPeriodo = "";
            this.selectedEstado = "PENDIENTE";

            this.areas = [];
            this.cursos = [];
            this.todosLosCursos = [];
            this.periodos = [];
        },

        generarReporte() {
            console.log({
                sistema: this.selectedSistema,
                area: this.selectedArea,
                curso: this.selectedCurso,
                periodo: this.selectedPeriodo,
                estado: this.selectedEstado,
            });
        },
    }));
});
